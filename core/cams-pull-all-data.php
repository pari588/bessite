<?php
/**
 * CAMS - Pull All Data via REST API
 *
 * Run this script AFTER disabling callback URL in CAMS dashboard
 * Then re-enable callback URL after data is pulled
 */

require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';

$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con;

// Configuration
$STGID = CAMS_STGID;
$AUTH_TOKEN = CAMS_AUTH_TOKEN;
$API_URL = 'https://robot.camsbiometrics.com/external/api3.0/biometric';

// Security key for encryption/decryption
// Currently using OLD key as that's what the CAMS server is using
$ENCRYPTION_KEY = defined('CAMS_SECURITY_KEY') ? CAMS_SECURITY_KEY : '';

// Try both keys for response decryption (in case server switches keys)
$KEYS = [
    'old' => defined('CAMS_OLD_SECURITY_KEY') ? CAMS_OLD_SECURITY_KEY : '',
    'new' => 'BuHq4RaINDwuNoINnJ2Isse9Vu2ctSIx'
];

echo "===========================================\n";
echo "CAMS DATA PULL - " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

/**
 * Make API request with AES-256-ECB encryption
 * IMPORTANT: Requests must be encrypted with the security key
 */
function makeRequest($url, $stgid, $authToken, $payload, $encryptionKey) {
    $payload['AuthToken'] = $authToken;

    $fullUrl = $url . '?stgid=' . $stgid;

    // Encrypt the request payload
    $jsonPayload = json_encode($payload);
    $encrypted = openssl_encrypt($jsonPayload, 'aes-256-ecb', $encryptionKey, OPENSSL_RAW_DATA);
    $encodedPayload = base64_encode($encrypted);

    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];
}

/**
 * Decrypt response trying multiple keys
 */
function decryptResponse($response, $keys) {
    $data = base64_decode($response);
    if ($data === false) {
        // Maybe not encrypted, try JSON directly
        $json = json_decode($response, true);
        if ($json) return ['key' => 'none', 'data' => $json];
        return null;
    }

    foreach ($keys as $name => $key) {
        // Try with zero padding
        $decrypted = openssl_decrypt($data, 'aes-256-ecb', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        if ($decrypted) {
            $clean = trim($decrypted);
            $json = json_decode($clean, true);
            if ($json) {
                return ['key' => $name, 'data' => $json];
            }
        }

        // Try without zero padding
        $decrypted = openssl_decrypt($data, 'aes-256-ecb', $key, OPENSSL_RAW_DATA);
        if ($decrypted) {
            $clean = trim($decrypted);
            $json = json_decode($clean, true);
            if ($json) {
                return ['key' => $name, 'data' => $json];
            }
        }
    }

    return null;
}

// =====================================================
// STEP 1: Load Users (Note: UserInfo API may not be enabled)
// =====================================================
echo "STEP 1: Attempting to Load Users from Device...\n";
echo "-------------------------------------------\n";
echo "Note: UserInfo API may return error if not enabled in CAMS extended settings.\n";
echo "Skipping user load - not supported by current CAMS configuration.\n";
echo "Users will be synced from punch logs instead.\n";

// =====================================================
// STEP 2: Load Punch Logs (Last 365 days to get all history)
// =====================================================
echo "\n\nSTEP 2: Loading Punch Logs (Last 365 days)...\n";
echo "-------------------------------------------\n";

$startDate = date('Y-m-d', strtotime('-365 days')) . ' 00:00:00 GMT +0530';
$endDate = date('Y-m-d') . ' 23:59:59 GMT +0530';

echo "Date Range: $startDate to $endDate\n";

$offset = '';
$totalPunches = 0;
$newPunches = 0;
$iteration = 0;
$maxIterations = 50; // Safety limit

do {
    $payload = [
        'Load' => [
            'PunchLog' => [
                'Filter' => [
                    'StartTime' => $startDate,
                    'EndTime' => $endDate
                ]
            ]
        ]
    ];

    // Add offset if we have one from previous request
    if (!empty($offset)) {
        $payload['Load']['PunchLog']['Filter']['OffSet'] = $offset;
    }

    $result = makeRequest($API_URL, $STGID, $AUTH_TOKEN, $payload, $ENCRYPTION_KEY);

    if ($result['error']) {
        echo "Error: {$result['error']}\n";
        break;
    }

    $decrypted = decryptResponse($result['response'], $KEYS);

    if (!$decrypted) {
        echo "Could not decrypt response at offset $offset\n";
        echo "Raw: " . substr($result['response'], 0, 100) . "\n";
        break;
    }

    $data = $decrypted['data'];

    // Check for API errors
    if (isset($data['StatusCode']) && $data['StatusCode'] != 0) {
        echo "API Error: " . ($data['Status'] ?? 'Unknown') . " (Code: {$data['StatusCode']})\n";
        break;
    }

    // PunchLog is directly in response, not under Load
    $punchLog = $data['PunchLog'] ?? null;
    if (!$punchLog || !isset($punchLog['Log']) || empty($punchLog['Log'])) {
        if ($iteration == 0) {
            echo "No punch logs found.\n";
        }
        break;
    }

    $punches = $punchLog['Log'];
    $count = count($punches);
    $totalPunches += $count;

    $actualCount = (int)($punchLog['ActualRowCount'] ?? $count);
    $returnCount = (int)($punchLog['ReturnRowCount'] ?? $count);

    echo "Batch " . ($iteration + 1) . ": Retrieved $count punches (Total available: $actualCount)\n";

    foreach ($punches as $punch) {
        $userId = $punch['UserId'] ?? null;
        $logTime = $punch['LogTime'] ?? null;
        $type = $punch['Type'] ?? 'CheckIn';
        $inputType = $punch['InputType'] ?? 'Fingerprint';

        if (!$userId || !$logTime) continue;

        // Parse time - remove timezone suffix
        $punchTime = preg_replace('/\s+GMT\s+[+-]\d{4}.*$/', '', $logTime);
        $punchDate = substr($punchTime, 0, 10);

        // Check if exists
        $stmt = $db->prepare('SELECT id FROM camsPunch WHERE user_id = ? AND punch_time = ? LIMIT 1');
        $stmt->bind_param('ss', $userId, $punchTime);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existing) {
            // Get device ID
            $deviceResult = $db->query("SELECT id FROM camsDevice WHERE serial_number = '" . $db->real_escape_string($STGID) . "' LIMIT 1");
            $deviceId = $deviceResult->fetch_assoc()['id'] ?? 1;

            $actualType = ($type === 'CheckOut') ? 1 : 0;

            $stmt = $db->prepare(
                'INSERT INTO camsPunch (user_id, punch_date_str, punch_time, actual_punch_type, punch_type, input_type, device_id, status)
                 VALUES (?, ?, ?, ?, 9, 0, ?, "A")'
            );
            $stmt->bind_param('sssii', $userId, $punchDate, $punchTime, $actualType, $deviceId);
            $stmt->execute();
            $stmt->close();
            $newPunches++;

            echo "  New: User $userId @ $punchTime ($type)\n";
        }
    }

    // Get next offset from response
    $offset = $punchLog['OffSet'] ?? '';

    // Continue if we haven't got all records yet
    if (empty($offset) || $returnCount >= $actualCount) {
        break;
    }

    $iteration++;
    usleep(300000); // 300ms delay between requests

} while ($iteration < $maxIterations);

echo "\nTotal punches retrieved: $totalPunches\n";
echo "New punches added: $newPunches\n";

// =====================================================
// STEP 3: Sync to HRMS Attendance
// =====================================================
echo "\n\nSTEP 3: Syncing to HRMS Attendance...\n";
echo "-------------------------------------------\n";

// Get all punches that need syncing
$result = $db->query("
    SELECT DISTINCT user_id, punch_date_str
    FROM camsPunch
    WHERE punch_date_str >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY punch_date_str DESC
");

$synced = 0;
while ($row = $result->fetch_assoc()) {
    $userId = $row['user_id'];
    $punchDate = $row['punch_date_str'];

    // Find employee
    $empResult = $db->query("
        SELECT userID, workStartTime, workEndTime, lateGraceMinutes
        FROM mx_x_admin_user
        WHERE biometricID = '" . $db->real_escape_string($userId) . "' AND status = 1
        LIMIT 1
    ");
    $employee = $empResult->fetch_assoc();

    if (!$employee) continue;

    $hrmsUserId = $employee['userID'];
    $scheduledIn = $employee['workStartTime'] ?: '09:00:00';
    $scheduledOut = $employee['workEndTime'] ?: '18:00:00';
    $graceMinutes = $employee['lateGraceMinutes'] ?: 15;

    // Get punches for this day
    $punchResult = $db->query("
        SELECT punch_time FROM camsPunch
        WHERE user_id = '" . $db->real_escape_string($userId) . "'
        AND punch_date_str = '$punchDate'
        ORDER BY punch_time ASC
    ");

    $punches = [];
    while ($p = $punchResult->fetch_assoc()) {
        $punches[] = $p['punch_time'];
    }

    if (empty($punches)) continue;

    $checkIn = $punches[0];
    $checkOut = count($punches) > 1 ? $punches[count($punches) - 1] : null;

    // Calculate late
    $checkInTime = date('H:i:s', strtotime($checkIn));
    $graceTimestamp = strtotime($scheduledIn) + ($graceMinutes * 60);
    $actualInTimestamp = strtotime($checkInTime);

    $isLate = ($actualInTimestamp > $graceTimestamp) ? 1 : 0;
    $lateMinutes = $isLate ? max(0, round(($actualInTimestamp - strtotime($scheduledIn)) / 60)) : 0;

    // Calculate early checkout
    $isEarly = 0;
    $earlyMinutes = 0;
    $workingHours = 0;

    if ($checkOut) {
        $checkOutTime = date('H:i:s', strtotime($checkOut));
        $actualOutTimestamp = strtotime($checkOutTime);
        $scheduledOutTimestamp = strtotime($scheduledOut);

        $isEarly = ($actualOutTimestamp < $scheduledOutTimestamp) ? 1 : 0;
        $earlyMinutes = $isEarly ? max(0, round(($scheduledOutTimestamp - $actualOutTimestamp) / 60)) : 0;
        $workingHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
    }

    // Upsert attendance
    $db->query("
        INSERT INTO mx_attendance (userID, attendanceDate, scheduledIn, scheduledOut, checkIn, checkOut,
            isLate, lateMinutes, isEarlyCheckout, earlyMinutes, workingHours, attendanceStatus, source, syncedAt, status)
        VALUES ($hrmsUserId, '$punchDate', '$scheduledIn', '$scheduledOut', '$checkIn', " . ($checkOut ? "'$checkOut'" : "NULL") . ",
            $isLate, $lateMinutes, $isEarly, $earlyMinutes, $workingHours, 'present', 'biometric', NOW(), 1)
        ON DUPLICATE KEY UPDATE
            checkIn = VALUES(checkIn),
            checkOut = VALUES(checkOut),
            isLate = VALUES(isLate),
            lateMinutes = VALUES(lateMinutes),
            isEarlyCheckout = VALUES(isEarlyCheckout),
            earlyMinutes = VALUES(earlyMinutes),
            workingHours = VALUES(workingHours),
            syncedAt = NOW()
    ");

    $synced++;
}

echo "Synced $synced attendance records to HRMS.\n";

echo "\n===========================================\n";
echo "DONE! Now re-enable callback URL in CAMS dashboard.\n";
echo "===========================================\n";
?>
