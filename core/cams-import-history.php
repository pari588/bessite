<?php
/**
 * CAMS Biometric - Import Historical Punch Data
 *
 * This script pulls existing punch logs from the device using the REST API
 * and imports them into your database.
 *
 * Usage:
 * - Via browser: https://www.bombayengg.net/core/cams-import-history.php?days=30
 * - Via CLI: php cams-import-history.php
 *
 * Note: CAMS API only allows fetching last 30 days of data, max 50 records per request
 */

// Initialize
require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';

// Create database connection
$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con;

// Configuration
$CAMS_API_URL = 'https://robot.camsunit.com/external/api3.0/biometric';
$SERIAL_NUMBER = 'AWXH181060065';
$AUTH_TOKEN = 'sjSyrdgyeOyWgrJfVEBdQlwYkQfWCMg1';

// Get days parameter (default 30)
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
$days = min($days, 30); // CAMS API limit

// Calculate date range
$endDate = date('Y-m-d H:i:s') . ' GMT +0530';
$startDate = date('Y-m-d H:i:s', strtotime("-{$days} days")) . ' GMT +0530';

// Output header
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>CAMS Import History</title>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .warn{color:orange;}</style>";
echo "</head><body>";
echo "<h2>CAMS Biometric - Import Historical Data</h2>";
echo "<p>Device: <strong>$SERIAL_NUMBER</strong></p>";
echo "<p>Date Range: <strong>" . date('Y-m-d', strtotime("-{$days} days")) . "</strong> to <strong>" . date('Y-m-d') . "</strong></p>";
echo "<hr>";

// Function to generate operation ID
function generateOperationID() {
    return substr(bin2hex(random_bytes(10)), 0, 13);
}

// Function to fetch punch logs
function fetchPunchLogs($apiUrl, $serialNumber, $authToken, $startTime, $endTime, $offset = 0) {
    $payload = [
        'Load' => [
            'PunchLog' => [
                'Filter' => [
                    'StartTime' => $startTime,
                    'EndTime' => $endTime,
                    'OffSet' => (string)$offset
                ]
            ]
        ],
        'OperationID' => generateOperationID(),
        'AuthToken' => $authToken,
        'Time' => date('Y-m-d H:i:s') . ' GMT +0530'
    ];

    $url = $apiUrl . '?stgid=' . $serialNumber;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error];
    }

    $data = json_decode($response, true);
    return ['success' => true, 'data' => $data, 'httpCode' => $httpCode];
}

// Function to process and store punch
function storePunch($db, $deviceId, $punch) {
    $userId = $punch['UserId'] ?? null;
    $logTime = $punch['LogTime'] ?? null;
    $type = $punch['Type'] ?? 'CheckIn';
    $inputType = $punch['InputType'] ?? 'Fingerprint';

    if (!$userId || !$logTime) {
        return ['success' => false, 'error' => 'Missing data'];
    }

    // Strip timezone
    $punchTime = preg_replace('/\s+GMT\s+[+-]\d{4}.*$/', '', $logTime);
    $punchDate = substr($punchTime, 0, 10);

    // Convert types
    $actualPunchType = getPunchTypeCode($type);
    $inputTypeCode = getInputTypeCode($inputType);

    // Check if exists
    $stmt = $db->prepare('SELECT id FROM camsPunch WHERE user_id = ? AND punch_time = ? AND device_id = ? LIMIT 1');
    $stmt->bind_param("ssi", $userId, $punchTime, $deviceId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    if ($existing) {
        return ['success' => true, 'action' => 'exists', 'id' => $existing['id']];
    }

    // Insert new
    $stmt = $db->prepare(
        'INSERT INTO camsPunch (user_id, punch_date_str, punch_time, actual_punch_type, punch_type, input_type, device_id, status)
         VALUES (?, ?, ?, ?, 9, ?, ?, "A")'
    );
    $stmt->bind_param("sssiii", $userId, $punchDate, $punchTime, $actualPunchType, $inputTypeCode, $deviceId);
    $stmt->execute();
    $punchId = $db->insert_id;
    $stmt->close();

    return ['success' => true, 'action' => 'inserted', 'id' => $punchId];
}

function getPunchTypeCode($type) {
    switch ($type) {
        case 'CheckIn': return 0;
        case 'CheckOut': return 1;
        case 'BreakOut': return 3;
        case 'BreakIn': return 4;
        default: return 5;
    }
}

function getInputTypeCode($input) {
    switch ($input) {
        case 'Fingerprint': return 0;
        case 'Face': return 1;
        case 'Card': return 3;
        case 'Password': return 4;
        default: return 9;
    }
}

// Get device ID
$stmt = $db->prepare('SELECT id FROM camsDevice WHERE serial_number = ? LIMIT 1');
$stmt->bind_param("s", $SERIAL_NUMBER);
$stmt->execute();
$result = $stmt->get_result();
$device = $result->fetch_assoc();
$stmt->close();

if (!$device) {
    echo "<p class='error'>Device not found in database!</p>";
    exit;
}

$deviceId = $device['id'];
echo "<p>Device ID: $deviceId</p>";

// Fetch data in batches
$offset = 0;
$totalImported = 0;
$totalSkipped = 0;
$batchNum = 0;
$maxBatches = 100; // Safety limit

echo "<h3>Fetching punch logs...</h3>";
echo "<pre>";

while ($batchNum < $maxBatches) {
    $batchNum++;
    echo "\n--- Batch $batchNum (offset: $offset) ---\n";
    flush();

    $result = fetchPunchLogs($CAMS_API_URL, $SERIAL_NUMBER, $AUTH_TOKEN, $startDate, $endDate, $offset);

    if (!$result['success']) {
        echo "<span class='error'>API Error: " . $result['error'] . "</span>\n";
        break;
    }

    $data = $result['data'];

    if (isset($data['Status']) && $data['Status'] === 'error') {
        echo "<span class='error'>API returned error: " . ($data['Message'] ?? 'Unknown') . "</span>\n";
        echo "StatusCode: " . ($data['StatusCode'] ?? 'N/A') . "\n";
        break;
    }

    // Check for punch logs in response
    $punchLogs = $data['Load']['PunchLog'] ?? $data['PunchLog'] ?? [];

    if (empty($punchLogs)) {
        echo "No more punch logs found.\n";
        break;
    }

    if (!is_array($punchLogs)) {
        $punchLogs = [$punchLogs];
    }

    echo "Found " . count($punchLogs) . " punch records\n";

    foreach ($punchLogs as $punch) {
        $storeResult = storePunch($db, $deviceId, $punch);

        if ($storeResult['success']) {
            if ($storeResult['action'] === 'inserted') {
                $totalImported++;
                echo "<span class='success'>+ Imported: User {$punch['UserId']} @ {$punch['LogTime']}</span>\n";
            } else {
                $totalSkipped++;
                echo "<span class='warn'>= Exists: User {$punch['UserId']} @ {$punch['LogTime']}</span>\n";
            }
        } else {
            echo "<span class='error'>! Error: " . $storeResult['error'] . "</span>\n";
        }
    }

    // Check if more data
    if (count($punchLogs) < 50) {
        echo "\nAll records fetched.\n";
        break;
    }

    $offset += 50;
    sleep(1); // Rate limiting
}

echo "</pre>";

// Recalculate punch types for all users/dates
echo "<h3>Recalculating punch types...</h3>";

$stmt = $db->prepare('SELECT DISTINCT user_id, punch_date_str FROM camsPunch ORDER BY punch_date_str');
$stmt->execute();
$result = $stmt->get_result();
$userDates = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($userDates as $ud) {
    recalculatePunchTypes($db, $ud['user_id'], $ud['punch_date_str']);
}

function recalculatePunchTypes($db, $userId, $punchDate) {
    $stmt = $db->prepare(
        'SELECT id FROM camsPunch WHERE user_id = ? AND punch_date_str = ? ORDER BY punch_time ASC'
    );
    $stmt->bind_param("ss", $userId, $punchDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $punches = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($punches) == 0) return;

    $lastIndex = count($punches) - 1;
    foreach ($punches as $i => $punch) {
        if ($i === 0) {
            $punchType = 0; // First = CheckIn
        } elseif ($i === $lastIndex) {
            $punchType = 1; // Last = CheckOut
        } else {
            $punchType = 5; // Intermediate
        }

        $stmt = $db->prepare('UPDATE camsPunch SET punch_type = ? WHERE id = ?');
        $stmt->bind_param("ii", $punchType, $punch['id']);
        $stmt->execute();
        $stmt->close();
    }
}

echo "<p>Punch types recalculated for " . count($userDates) . " user/date combinations.</p>";

// Summary
echo "<hr>";
echo "<h3>Import Summary</h3>";
echo "<p class='success'><strong>Imported:</strong> $totalImported records</p>";
echo "<p class='warn'><strong>Skipped (already exists):</strong> $totalSkipped records</p>";
echo "<p><strong>Total batches:</strong> $batchNum</p>";

echo "<hr>";
echo "<p><a href='cams-import-history.php?days=30'>Run again (last 30 days)</a></p>";
echo "<p><a href='cams-import-history.php?days=7'>Run for last 7 days</a></p>";

echo "</body></html>";
?>
