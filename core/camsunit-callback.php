<?php
/**
 * Cams Biometric API 3.0 Callback Handler
 *
 * This endpoint receives real-time punch data from the biometric device.
 * API Documentation: https://camsbiometrics.com/application3/biometric-web-api.html
 *
 * Webhook URL to configure in API Monitor: https://www.bombayengg.net/core/camsunit-callback.php
 *
 * Request Format (API 3.0):
 * {
 *   "RealTime": {
 *     "PunchLog": {
 *       "UserId": "1",
 *       "LogTime": "2026-01-07 09:30:00",
 *       "InputType": "0",
 *       "Temperature": "36.5",
 *       "MaskStatus": "1"
 *     }
 *   },
 *   "OperationID": "unique_id",
 *   "AuthToken": "your_token"
 * }
 *
 * Response Format:
 * { "status": "done" }
 */

// Initialize
require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';

// Set response header
header('Content-Type: application/json');

// Get STGID from query string
$stgid = $_GET['stgid'] ?? '';

// Get incoming data
$rawInput = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

// Log incoming request
logCallback('Incoming callback', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $contentType,
    'stgid' => $stgid,
    'raw_input' => $rawInput,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

// Parse JSON input
$data = json_decode($rawInput, true);

// Check for encrypted payload (if Security Key is set in API Monitor)
if ($data === null && !empty($rawInput)) {
    $decrypted = decryptPayload($rawInput);
    if ($decrypted) {
        $data = json_decode($decrypted, true);
        logCallback('Decrypted payload', ['data' => $data]);
    }
}

// If still no data, try form data
if (empty($data)) {
    $data = $_POST;
}

// Validate auth token (optional but recommended)
$authToken = $data['AuthToken'] ?? $data['auth_token'] ?? '';
if (!empty(CAMS_AUTH_TOKEN) && !empty($authToken)) {
    if (!hash_equals(CAMS_AUTH_TOKEN, $authToken)) {
        logCallback('Auth token mismatch', ['provided' => substr($authToken, 0, 8) . '...']);
        // Continue anyway as some setups don't use auth token validation
    }
}

// Process based on command type
$response = ['status' => 'done'];

try {
    // API 3.0 Format - RealTime PunchLog
    if (isset($data['RealTime']['PunchLog'])) {
        $punchLog = $data['RealTime']['PunchLog'];
        $result = processRealTimePunch($punchLog, $data);
        logCallback('RealTime punch processed', $result);
    }
    // Alternative format - direct punch data
    elseif (isset($data['PunchLog'])) {
        $punchLog = $data['PunchLog'];
        $result = processRealTimePunch($punchLog, $data);
        logCallback('Direct punch processed', $result);
    }
    // Legacy format compatibility
    elseif (isset($data['user_id']) || isset($data['userid']) || isset($data['UserId'])) {
        $result = processLegacyPunch($data);
        logCallback('Legacy punch processed', $result);
    }
    // Load command - device requesting data
    elseif (isset($data['Load'])) {
        $result = handleLoadCommand($data['Load']);
        $response = array_merge($response, $result);
        logCallback('Load command handled', $result);
    }
    // Unknown format
    else {
        logCallback('Unknown data format', ['data' => $data]);
    }
} catch (Exception $e) {
    logCallback('Exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}

// Always return success to device
echo json_encode($response);
exit;

/**
 * Process real-time punch from API 3.0 format
 */
function processRealTimePunch($punchLog, $fullData) {
    global $db;

    // Extract punch details (API 3.0 format)
    $biometricId = $punchLog['UserId'] ?? $punchLog['userid'] ?? $punchLog['user_id'] ?? '';
    $logTime = $punchLog['LogTime'] ?? $punchLog['logtime'] ?? $punchLog['punch_time'] ?? date('Y-m-d H:i:s');
    $inputType = $punchLog['InputType'] ?? '0'; // 0=Fingerprint, 1=Card, 2=Face, etc.
    $temperature = $punchLog['Temperature'] ?? null;
    $maskStatus = $punchLog['MaskStatus'] ?? null;

    $operationId = $fullData['OperationID'] ?? '';

    if (empty($biometricId)) {
        return ['success' => false, 'error' => 'Missing UserId'];
    }

    // Find employee by biometricID
    $sql = "SELECT userID, displayName, workStartTime, workEndTime, lateGraceMinutes
            FROM mx_x_admin_user
            WHERE biometricID = ? AND status = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $biometricId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return ['success' => false, 'error' => "No employee found with biometricID: $biometricId"];
    }

    $userId = $user['userID'];
    $punchDate = date('Y-m-d', strtotime($logTime));
    $scheduledIn = $user['workStartTime'] ?: '09:00:00';
    $scheduledOut = $user['workEndTime'] ?: '18:00:00';
    $graceMinutes = $user['lateGraceMinutes'] ?: 15;

    // Raw data for audit
    $rawData = json_encode([
        'punch_log' => $punchLog,
        'operation_id' => $operationId,
        'temperature' => $temperature,
        'mask_status' => $maskStatus,
        'input_type' => $inputType,
        'received_at' => date('Y-m-d H:i:s')
    ]);

    // Check existing attendance record
    $sql = "SELECT attendanceID, checkIn, checkOut FROM mx_attendance
            WHERE userID = ? AND attendanceDate = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $userId, $punchDate);
    $stmt->execute();
    $attResult = $stmt->get_result();
    $existingAtt = $attResult->fetch_assoc();
    $stmt->close();

    if ($existingAtt) {
        $attendanceId = $existingAtt['attendanceID'];

        if (empty($existingAtt['checkIn'])) {
            // First punch - check-in
            updateCheckIn($attendanceId, $logTime, $scheduledIn, $graceMinutes, $rawData);
            return ['success' => true, 'action' => 'check-in', 'attendanceId' => $attendanceId, 'employee' => $user['displayName']];
        } else {
            // Later punch - check-out
            updateCheckOut($attendanceId, $logTime, $scheduledOut, $existingAtt['checkIn'], $rawData);
            return ['success' => true, 'action' => 'check-out', 'attendanceId' => $attendanceId, 'employee' => $user['displayName']];
        }
    } else {
        // Create new attendance record
        $attendanceId = createAttendance($userId, $punchDate, $logTime, $scheduledIn, $scheduledOut, $graceMinutes, $rawData);

        if ($attendanceId) {
            return ['success' => true, 'action' => 'created', 'attendanceId' => $attendanceId, 'employee' => $user['displayName']];
        } else {
            return ['success' => false, 'error' => 'Failed to create attendance record'];
        }
    }
}

/**
 * Process legacy punch format (backward compatibility)
 */
function processLegacyPunch($data) {
    $punchLog = [
        'UserId' => $data['user_id'] ?? $data['userid'] ?? $data['UserId'] ?? '',
        'LogTime' => $data['punch_time'] ?? $data['punchtime'] ?? $data['datetime'] ?? date('Y-m-d H:i:s'),
        'InputType' => $data['input_type'] ?? '0'
    ];

    return processRealTimePunch($punchLog, $data);
}

/**
 * Handle Load command from device (user sync, etc.)
 */
function handleLoadCommand($loadData) {
    global $db;

    $commandType = key($loadData);

    switch ($commandType) {
        case 'UserInfo':
            // Device requesting user list
            return getUserList();
        default:
            return ['message' => 'Load command received'];
    }
}

/**
 * Get user list for device sync
 */
function getUserList() {
    global $db;

    $sql = "SELECT biometricID as UserId, displayName as Name, employeeCode as EmployeeCode
            FROM mx_x_admin_user
            WHERE biometricID IS NOT NULL AND biometricID != '' AND status = 1";
    $result = $db->query($sql);

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    return ['UserInfo' => $users];
}

/**
 * Update check-in time
 */
function updateCheckIn($attendanceId, $checkInTime, $scheduledIn, $graceMinutes, $rawData) {
    global $db;

    $checkInTimeOnly = date('H:i:s', strtotime($checkInTime));
    $scheduledInTimestamp = strtotime($scheduledIn);
    $actualInTimestamp = strtotime($checkInTimeOnly);
    $graceTimestamp = $scheduledInTimestamp + ($graceMinutes * 60);

    $isLate = ($actualInTimestamp > $graceTimestamp) ? 1 : 0;
    $lateMinutes = $isLate ? max(0, round(($actualInTimestamp - $scheduledInTimestamp) / 60)) : 0;

    $sql = "UPDATE mx_attendance SET
            checkIn = ?,
            isLate = ?,
            lateMinutes = ?,
            biometricRaw = ?,
            syncedAt = NOW(),
            source = 'biometric',
            attendanceStatus = 'present'
            WHERE attendanceID = ?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("siisi", $checkInTime, $isLate, $lateMinutes, $rawData, $attendanceId);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

/**
 * Update check-out time
 */
function updateCheckOut($attendanceId, $checkOutTime, $scheduledOut, $checkIn, $rawData) {
    global $db;

    $checkOutTimeOnly = date('H:i:s', strtotime($checkOutTime));
    $scheduledOutTimestamp = strtotime($scheduledOut);
    $actualOutTimestamp = strtotime($checkOutTimeOnly);

    $isEarlyCheckout = ($actualOutTimestamp < $scheduledOutTimestamp) ? 1 : 0;
    $earlyMinutes = $isEarlyCheckout ? max(0, round(($scheduledOutTimestamp - $actualOutTimestamp) / 60)) : 0;

    // Calculate working hours
    $workingHours = 0;
    if ($checkIn) {
        $workingHours = round((strtotime($checkOutTime) - strtotime($checkIn)) / 3600, 2);
    }

    $sql = "UPDATE mx_attendance SET
            checkOut = ?,
            isEarlyCheckout = ?,
            earlyMinutes = ?,
            workingHours = ?,
            biometricRaw = CONCAT(IFNULL(biometricRaw, ''), '\n', ?),
            syncedAt = NOW()
            WHERE attendanceID = ?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("siidsi", $checkOutTime, $isEarlyCheckout, $earlyMinutes, $workingHours, $rawData, $attendanceId);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

/**
 * Create new attendance record
 */
function createAttendance($userId, $attendanceDate, $checkInTime, $scheduledIn, $scheduledOut, $graceMinutes, $rawData) {
    global $db;

    $checkInTimeOnly = date('H:i:s', strtotime($checkInTime));
    $scheduledInTimestamp = strtotime($scheduledIn);
    $actualInTimestamp = strtotime($checkInTimeOnly);
    $graceTimestamp = $scheduledInTimestamp + ($graceMinutes * 60);

    $isLate = ($actualInTimestamp > $graceTimestamp) ? 1 : 0;
    $lateMinutes = $isLate ? max(0, round(($actualInTimestamp - $scheduledInTimestamp) / 60)) : 0;

    $sql = "INSERT INTO mx_attendance (
                userID, attendanceDate, scheduledIn, scheduledOut,
                checkIn, isLate, lateMinutes,
                attendanceStatus, source, biometricRaw, syncedAt, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'present', 'biometric', ?, NOW(), 1)";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("issssiss",
        $userId, $attendanceDate, $scheduledIn, $scheduledOut,
        $checkInTime, $isLate, $lateMinutes, $rawData
    );
    $success = $stmt->execute();
    $attendanceId = $db->insert_id;
    $stmt->close();

    return $success ? $attendanceId : false;
}

/**
 * Decrypt AES-256 encrypted payload
 */
function decryptPayload($encrypted) {
    if (empty(CAMS_SECURITY_KEY)) {
        return false;
    }

    $data = base64_decode($encrypted);
    if ($data === false) {
        return false;
    }

    $key = substr(hash('sha256', CAMS_SECURITY_KEY, true), 0, 32);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encryptedData = substr($data, $ivLength);

    return openssl_decrypt($encryptedData, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * Log callback events
 */
function logCallback($event, $data = []) {
    $logDir = ROOTPATH . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/camsunit-callback-' . date('Y-m-d') . '.log';

    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'data' => $data
    ];

    $line = json_encode($logEntry) . "\n";

    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

    // Also log to database
    logToDatabase($event, $data);
}

/**
 * Log to database for persistent tracking
 */
function logToDatabase($event, $data) {
    global $db;

    if (!$db) return;

    // Check if log table exists
    $tableName = 'mx_camsunit_callback_log';
    $tableCheck = $db->query("SHOW TABLES LIKE '$tableName'");

    if ($tableCheck && $tableCheck->num_rows == 0) {
        // Create table if not exists
        $createSql = "CREATE TABLE IF NOT EXISTS $tableName (
            logID INT AUTO_INCREMENT PRIMARY KEY,
            eventType VARCHAR(100),
            eventData TEXT,
            ipAddress VARCHAR(45),
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (createdAt)
        )";
        $db->query($createSql);
    }

    $eventData = json_encode($data);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $sql = "INSERT INTO $tableName (eventType, eventData, ipAddress) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sss", $event, $eventData, $ipAddress);
        $stmt->execute();
        $stmt->close();
    }
}
?>
