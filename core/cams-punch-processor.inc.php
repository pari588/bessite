<?php
/**
 * Shared CAMS/eSSL punch-processing pipeline (extracted from cams-biometric-callback.php 2026-07-20).
 * Used by the new eSSL iclock endpoint (essl-push.php). Requires $db (mysqli) passed to each fn.
 * Downstream logic identical to the live CAMS callback: dedupe -> camsPunch -> recalc IN/OUT -> mx_attendance.
 */

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
