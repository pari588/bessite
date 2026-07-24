<?php
/**
 * CAMS Biometric Direct Integration Callback
 *
 * This is a simplified callback handler that:
 * 1. Receives punches directly from biometric device
 * 2. Stores raw punch in camsPunch table
 * 3. Syncs to mx_attendance table for HRMS integration
 *
 * Callback URL to configure on device:
 * https://www.bombayengg.net/core/cams-biometric-callback.php
 *
 * No external dependencies (Composer) required.
 */

// Initialize
require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';

// Create database connection
$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con; // Get mysqli connection object

// Set response header
header('Content-Type: application/json; charset=utf-8');

// Logging function
function camsLog($message, $level = 'INFO') {
    $logDir = ROOTPATH . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/cams-biometric-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] [$level] $message\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

// Get stgid from query string (device service tag ID)
$stgid = $_GET['stgid'] ?? null;

// Get raw input
$rawInput = file_get_contents('php://input');

// Log ALL incoming requests with full details for debugging
$allHeaders = getallheaders();
$headerStr = json_encode($allHeaders);
camsLog("Received request - stgid: $stgid, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ", Headers: " . substr($headerStr, 0, 200));
camsLog("Request body: " . substr($rawInput, 0, 500));

// Try to decrypt if data is encrypted (Base64)
$decryptedInput = null;
if (!empty($rawInput) && json_decode($rawInput) === null) {
    // Data is not valid JSON, try to decrypt
    $securityKey = defined('CAMS_SECURITY_KEY') ? CAMS_SECURITY_KEY : '';
    $oldSecurityKey = 'ZkSlVioJlraKWhHbrBW76Ou8PmM6M7ha'; // Fallback for transition period

    $encrypted = base64_decode($rawInput);
    if ($encrypted !== false) {
        // Try current key first
        if (!empty($securityKey)) {
            $decrypted = openssl_decrypt($encrypted, 'aes-256-ecb', $securityKey, OPENSSL_RAW_DATA);
            if ($decrypted !== false && json_decode(trim($decrypted)) !== null) {
                $decryptedInput = trim($decrypted);
                camsLog("Decrypted payload (new key): " . substr($decryptedInput, 0, 500));
                $rawInput = $decryptedInput;
            }
        }
        // Try old key as fallback
        if ($decryptedInput === null && !empty($oldSecurityKey)) {
            $decrypted = openssl_decrypt($encrypted, 'aes-256-ecb', $oldSecurityKey, OPENSSL_RAW_DATA);
            if ($decrypted !== false && json_decode(trim($decrypted)) !== null) {
                $decryptedInput = trim($decrypted);
                camsLog("Decrypted payload (old key): " . substr($decryptedInput, 0, 500));
                $rawInput = $decryptedInput;
            }
        }
        // Try with zero padding as last resort
        if ($decryptedInput === null && !empty($securityKey)) {
            $decrypted = openssl_decrypt($encrypted, 'aes-256-ecb', $securityKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
            if ($decrypted !== false && json_decode(trim($decrypted)) !== null) {
                $decryptedInput = trim($decrypted);
                camsLog("Decrypted payload (new key, zero pad): " . substr($decryptedInput, 0, 500));
                $rawInput = $decryptedInput;
            }
        }
    }
}

// Parse JSON
$payload = json_decode($rawInput, true);

// Handle different payload formats
$realTime = null;
$loadData = null;
$authToken = null;
$serialNumber = $stgid;

if ($payload) {
    // Format 1: RealTime (punch data, user updates)
    if (isset($payload['RealTime'])) {
        $realTime = $payload['RealTime'];
        $authToken = $realTime['AuthToken'] ?? null;
        $serialNumber = $stgid ?: ($realTime['SerialNumber'] ?? null);
    }
    // Format 2: Load (user list, punch log data from "resend")
    elseif (isset($payload['Load'])) {
        $loadData = $payload['Load'];
        $authToken = $payload['AuthToken'] ?? null;
        $serialNumber = $stgid ?: ($payload['SerialNumber'] ?? null);
        camsLog("Received Load data: " . json_encode(array_keys($loadData)));
    }
    // Format 3: Direct AuthToken at root level
    elseif (isset($payload['AuthToken'])) {
        $authToken = $payload['AuthToken'];
        $serialNumber = $stgid ?: ($payload['SerialNumber'] ?? null);
        camsLog("Received direct payload with AuthToken");
    }
}

if (!$payload) {
    camsLog("Invalid payload - could not parse JSON", 'ERROR');
    echo json_encode(['status' => 'done']);
    exit;
}

if (!$serialNumber || !$authToken) {
    camsLog("Missing stgid/SerialNumber or AuthToken", 'ERROR');
    echo json_encode(['status' => 'done']); // Always return done per API spec
    exit;
}

camsLog("Device: $serialNumber, AuthToken: " . substr($authToken, 0, 8) . "...");

try {
    // Validate device
    $stmt = $db->prepare('SELECT id, client_id FROM camsDevice WHERE serial_number = ? AND auth_token = ? AND status = ? LIMIT 1');
    $stmt->bind_param("sss", $serialNumber, $authToken, $activeStatus);
    $activeStatus = 'A';
    $stmt->execute();
    $result = $stmt->get_result();
    $device = $result->fetch_assoc();
    $stmt->close();

    if (!$device) {
        camsLog("Invalid device or token: $serialNumber", 'ERROR');
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid device or token']);
        exit;
    }

    $deviceId = $device['id'];
    camsLog("Device validated: ID=$deviceId");

    // Process RealTime data
    if ($realTime) {
        // Process PunchLog
        if (isset($realTime['PunchLog'])) {
            $punchLog = $realTime['PunchLog'];
            $result = processPunchLog($db, $deviceId, $punchLog);
            camsLog("Punch processed: " . json_encode($result));
        }

        // Process UserUpdated (optional - for user sync from device)
        if (isset($realTime['UserUpdated'])) {
            $userData = $realTime['UserUpdated'];
            processUserUpdated($db, $userData);
            camsLog("User updated from device");
        }
    }

    // Process Load data (from "resend" or bulk data push)
    if ($loadData) {
        // Process UserInfo array
        if (isset($loadData['UserInfo'])) {
            $users = $loadData['UserInfo'];
            if (is_array($users)) {
                $count = 0;
                foreach ($users as $user) {
                    processUserUpdated($db, $user);
                    $count++;
                }
                camsLog("Processed $count users from Load.UserInfo");
            }
        }

        // Process PunchLog array
        if (isset($loadData['PunchLog'])) {
            $punches = $loadData['PunchLog'];
            if (is_array($punches)) {
                $count = 0;
                foreach ($punches as $punch) {
                    processPunchLog($db, $deviceId, $punch);
                    $count++;
                }
                camsLog("Processed $count punches from Load.PunchLog");
            }
        }
    }

    echo json_encode(['status' => 'done']);

} catch (Exception $e) {
    camsLog("Error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}

/**
 * Process punch log from device
 */
function processPunchLog($db, $deviceId, $punchLog) {
    $userId = $punchLog['UserId'] ?? null;
    $logTime = $punchLog['LogTime'] ?? null;
    $type = $punchLog['Type'] ?? 'CheckIn';
    $inputType = $punchLog['InputType'] ?? 'Fingerprint';

    if (!$userId || !$logTime) {
        return ['success' => false, 'error' => 'Missing UserId or LogTime'];
    }

    // Strip timezone if present (e.g., "2026-01-07 09:30:00 GMT +0530" -> "2026-01-07 09:30:00")
    $punchTime = preg_replace('/\s+GMT\s+[+-]\d{4}.*$/', '', $logTime);
    $punchDate = substr($punchTime, 0, 10); // YYYY-MM-DD

    camsLog("Processing punch: User=$userId, Time=$punchTime, Type=$type");

    // Convert type strings to codes
    $actualPunchType = getPunchTypeCode($type);
    $inputTypeCode = getInputTypeCode($inputType);

    // Check if punch already exists
    $stmt = $db->prepare('SELECT id FROM camsPunch WHERE user_id = ? AND punch_time = ? AND device_id = ? LIMIT 1');
    $stmt->bind_param("ssi", $userId, $punchTime, $deviceId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        // Insert new punch
        $stmt = $db->prepare(
            'INSERT INTO camsPunch (user_id, punch_date_str, punch_time, actual_punch_type, punch_type, input_type, device_id, status)
             VALUES (?, ?, ?, ?, 9, ?, ?, "A")'
        );
        $stmt->bind_param("sssiii", $userId, $punchDate, $punchTime, $actualPunchType, $inputTypeCode, $deviceId);
        $stmt->execute();
        $punchId = $db->insert_id;
        $stmt->close();
        camsLog("New punch inserted: ID=$punchId");
    } else {
        $punchId = $existing['id'];
        camsLog("Punch already exists: ID=$punchId");
    }

    // Recalculate punch types for the day (First=IN, Last=OUT)
    recalculatePunchTypes($db, $userId, $punchDate);

    // Sync to mx_attendance (HRMS table)
    syncToHRMSAttendance($db, $userId, $punchDate);

    return ['success' => true, 'punchId' => $punchId, 'action' => $existing ? 'updated' : 'created'];
}

/**
 * Recalculate punch types: First punch = CheckIn, Last punch = CheckOut
 */
function recalculatePunchTypes($db, $userId, $punchDate) {
    // Get all punches for the day
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

    camsLog("Recalculated punch types for User=$userId, Date=$punchDate, Count=" . count($punches));
}

/**
 * Sync punch data to HRMS mx_attendance table
 */
function syncToHRMSAttendance($db, $camsUserId, $punchDate) {
    // Find employee by biometricID
    $stmt = $db->prepare(
        'SELECT userID, displayName, workStartTime, workEndTime, lateGraceMinutes
         FROM mx_x_admin_user
         WHERE biometricID = ? AND status = 1 LIMIT 1'
    );
    $stmt->bind_param("s", $camsUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    $stmt->close();

    if (!$employee) {
        camsLog("No HRMS employee found with biometricID: $camsUserId", 'WARN');
        return;
    }

    $hrmsUserId = $employee['userID'];

    // Check if it's Saturday - use Saturday timings if available
    $dayOfWeek = date('w', strtotime($punchDate)); // 0 = Sunday, 6 = Saturday
    $isSaturday = ($dayOfWeek == 6);

    if ($isSaturday && !empty($employee['saturdayStartTime'])) {
        $scheduledIn = $employee['saturdayStartTime'];
        $scheduledOut = $employee['saturdayEndTime'] ?: '16:00:00';
    } else {
        $scheduledIn = $employee['workStartTime'] ?: '09:00:00';
        $scheduledOut = $employee['workEndTime'] ?: '18:00:00';
    }
    $graceMinutes = $employee['lateGraceMinutes'] ?: 15;

    // Get first and last punch for the day
    $stmt = $db->prepare(
        'SELECT punch_time, punch_type FROM camsPunch
         WHERE user_id = ? AND punch_date_str = ?
         ORDER BY punch_time ASC'
    );
    $stmt->bind_param("ss", $camsUserId, $punchDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $punches = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($punches) == 0) return;

    $firstPunch = $punches[0]['punch_time'];
    $lastPunch = $punches[count($punches) - 1]['punch_time'];
    $checkIn = $firstPunch;
    $checkOut = (count($punches) > 1) ? $lastPunch : null;

    // Calculate late status
    $checkInTime = date('H:i:s', strtotime($checkIn));
    $scheduledInTimestamp = strtotime($scheduledIn);
    $actualInTimestamp = strtotime($checkInTime);
    $graceTimestamp = $scheduledInTimestamp + ($graceMinutes * 60);

    $isLate = ($actualInTimestamp > $graceTimestamp) ? 1 : 0;
    $lateMinutes = $isLate ? max(0, round(($actualInTimestamp - $scheduledInTimestamp) / 60)) : 0;

    // Calculate early checkout
    $isEarlyCheckout = 0;
    $earlyMinutes = 0;
    $workingHours = 0;

    if ($checkOut) {
        $checkOutTime = date('H:i:s', strtotime($checkOut));
        $scheduledOutTimestamp = strtotime($scheduledOut);
        $actualOutTimestamp = strtotime($checkOutTime);

        $isEarlyCheckout = ($actualOutTimestamp < $scheduledOutTimestamp) ? 1 : 0;
        $earlyMinutes = $isEarlyCheckout ? max(0, round(($scheduledOutTimestamp - $actualOutTimestamp) / 60)) : 0;

        $workingHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
    }

    // Check if attendance record exists
    $stmt = $db->prepare(
        'SELECT attendanceID, checkIn, checkOut, source FROM mx_attendance WHERE userID = ? AND attendanceDate = ? LIMIT 1'
    );
    $stmt->bind_param("is", $hrmsUserId, $punchDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingAtt = $result->fetch_assoc();
    $stmt->close();

    $rawData = json_encode([
        'cams_user_id' => $camsUserId,
        'punches' => $punches,
        'synced_at' => date('Y-m-d H:i:s')
    ]);

    if ($existingAtt) {
        // Update existing - preserve existing checkIn if already set (manual or biometric)
        $existingCheckIn = $existingAtt['checkIn'];
        $existingCheckOut = $existingAtt['checkOut'];
        $originalNewCheckIn = $checkIn; // Save original new punch time before any changes

        // If existing checkIn is set and is earlier than new checkIn, keep the existing one
        if (!empty($existingCheckIn) && strtotime($existingCheckIn) < strtotime($checkIn)) {
            $checkIn = $existingCheckIn;
            // Recalculate late status based on preserved checkIn
            $checkInTime = date('H:i:s', strtotime($checkIn));
            $actualInTimestamp = strtotime($checkInTime);
            $isLate = ($actualInTimestamp > $graceTimestamp) ? 1 : 0;
            $lateMinutes = $isLate ? max(0, round(($actualInTimestamp - $scheduledInTimestamp) / 60)) : 0;
        }

        // For checkOut, use the latest punch time
        if (count($punches) > 1) {
            $checkOut = $lastPunch;
        } elseif (!empty($existingCheckIn) && strtotime($originalNewCheckIn) > strtotime($existingCheckIn)) {
            // Single new punch that is later than existing checkIn - this new punch is checkout
            $checkOut = $originalNewCheckIn;
            // Recalculate working hours
            $workingHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
            // Recalculate early checkout
            $checkOutTime = date('H:i:s', strtotime($checkOut));
            $actualOutTimestamp = strtotime($checkOutTime);
            $isEarlyCheckout = ($actualOutTimestamp < $scheduledOutTimestamp) ? 1 : 0;
            $earlyMinutes = $isEarlyCheckout ? max(0, round(($scheduledOutTimestamp - $actualOutTimestamp) / 60)) : 0;
        }

        // Update existing
        $stmt = $db->prepare(
            'UPDATE mx_attendance SET
                checkIn = ?, checkOut = ?,
                isLate = ?, lateMinutes = ?,
                isEarlyCheckout = ?, earlyMinutes = ?,
                workingHours = ?,
                attendanceStatus = "present",
                source = CASE WHEN source = "manual" THEN "manual" ELSE "biometric" END,
                biometricRaw = ?,
                syncedAt = NOW()
             WHERE attendanceID = ?'
        );
        $stmt->bind_param("ssiiiidsi",
            $checkIn, $checkOut,
            $isLate, $lateMinutes,
            $isEarlyCheckout, $earlyMinutes,
            $workingHours,
            $rawData,
            $existingAtt['attendanceID']
        );
        $stmt->execute();
        $stmt->close();
        camsLog("Updated mx_attendance for User=$hrmsUserId, Date=$punchDate (CheckIn=$checkIn, CheckOut=$checkOut)");
    } else {
        // Insert new record
        // SMART DETECTION: If only 1 punch and it's in the afternoon (after 14:00), treat it as checkout only
        $punchHour = (int)date('H', strtotime($firstPunch));
        $midDayHour = 14; // 2 PM - punches after this are likely checkout if no checkin exists

        if (count($punches) == 1 && $punchHour >= $midDayHour) {
            // Single punch in afternoon/evening with no existing record = checkout only
            $checkIn = null;
            $checkOut = $firstPunch;
            $isLate = 0;
            $lateMinutes = 0;
            // Calculate early checkout
            $checkOutTime = date('H:i:s', strtotime($checkOut));
            $actualOutTimestamp = strtotime($checkOutTime);
            $isEarlyCheckout = ($actualOutTimestamp < $scheduledOutTimestamp) ? 1 : 0;
            $earlyMinutes = $isEarlyCheckout ? max(0, round(($scheduledOutTimestamp - $actualOutTimestamp) / 60)) : 0;
            $workingHours = 0; // Can't calculate without checkin
            camsLog("Single afternoon punch detected as CHECKOUT for User=$hrmsUserId, Time=$checkOut");
        }

        $remarks = 'Imported from CAMS';
        $stmt = $db->prepare(
            'INSERT INTO mx_attendance (
                userID, attendanceDate, scheduledIn, scheduledOut,
                checkIn, checkOut,
                isLate, lateMinutes,
                isEarlyCheckout, earlyMinutes,
                workingHours,
                attendanceStatus, source, biometricRaw, remarks, syncedAt, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "present", "biometric", ?, ?, NOW(), 1)'
        );
        $stmt->bind_param("isssssiiiiiss",
            $hrmsUserId, $punchDate, $scheduledIn, $scheduledOut,
            $checkIn, $checkOut,
            $isLate, $lateMinutes,
            $isEarlyCheckout, $earlyMinutes,
            $workingHours,
            $rawData, $remarks
        );
        $stmt->execute();
        $stmt->close();
        camsLog("Inserted mx_attendance for User=$hrmsUserId, Date=$punchDate (CheckIn=" . ($checkIn ?: 'NULL') . ", CheckOut=" . ($checkOut ?: 'NULL') . ")");
    }
}

/**
 * Process user update from device (optional)
 */
function processUserUpdated($db, $userData) {
    $userId = $userData['UserID'] ?? null;
    if (!$userId) return;

    $firstName = $userData['FirstName'] ?? '';
    $lastName = $userData['LastName'] ?? '';
    $userType = ($userData['UserType'] ?? '') === 'Admin' ? '1' : '0';

    // Check if user exists
    $stmt = $db->prepare('SELECT id FROM camsUser WHERE user_id = ? LIMIT 1');
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $stmt = $db->prepare(
            'UPDATE camsUser SET first_name = ?, last_name = ?, type = ?, time_updated = NOW() WHERE id = ?'
        );
        $stmt->bind_param("sssi", $firstName, $lastName, $userType, $existing['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $db->prepare(
            'INSERT INTO camsUser (user_id, first_name, last_name, type, status) VALUES (?, ?, ?, ?, "A")'
        );
        $stmt->bind_param("ssss", $userId, $firstName, $lastName, $userType);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Convert punch type string to code
 */
function getPunchTypeCode($type) {
    switch ($type) {
        case 'CheckIn': return 0;
        case 'CheckOut': return 1;
        case 'BreakOut': return 3;
        case 'BreakIn': return 4;
        default: return 5; // Intermediate
    }
}

/**
 * Convert input type string to code
 */
function getInputTypeCode($input) {
    switch ($input) {
        case 'Fingerprint': return 0;
        case 'Face': return 1;
        case 'Card': return 3;
        case 'Password': return 4;
        case 'Others': return 5;
        default: return 9;
    }
}
?>
