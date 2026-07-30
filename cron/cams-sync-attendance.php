<?php
/**
 * CAMS Biometric Attendance Sync Cron
 *
 * Pulls punch logs from CAMS API and syncs to mx_attendance.
 * Runs as a fallback/primary sync (independent of real-time callback).
 *
 * Usage:
 *   php cams-sync-attendance.php          -- syncs last 3 days (default)
 *   php cams-sync-attendance.php --days=7 -- syncs last 7 days
 *   php cams-sync-attendance.php --from=2026-03-01 --to=2026-03-27  -- specific range
 *
 * Crontab: 0 * * * * /usr/bin/php /home/bombayengg/public_html/cron/cams-sync-attendance.php
 */

// Required for CLI execution (config.inc.php uses $_SERVER vars)
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST']    = 'www.bombayengg.net';
    $_SERVER['REQUEST_URI']  = '/cron/cams-sync-attendance.php';
    $_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';
}

require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';

/**
 * TIMEZONE — config.inc.php sets none, so this cron ran in UTC: log lines were
 * 5h30m behind IST and the sync window end-date was computed from the UTC date
 * (wrong for the first 5.5 h of every IST day). Same bug class as wa-webhook
 * and the CAMS callback. Fixed 2026-07-30.
 */
date_default_timezone_set('Asia/Kolkata');

$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con;

// Parse CLI arguments
$days = 3;
$fromDate = null;
$toDate = null;

if (PHP_SAPI === 'cli') {
    foreach ($argv as $arg) {
        if (preg_match('/--days=(\d+)/', $arg, $m)) $days = (int)$m[1];
        if (preg_match('/--from=(\d{4}-\d{2}-\d{2})/', $arg, $m)) $fromDate = $m[1];
        if (preg_match('/--to=(\d{4}-\d{2}-\d{2})/', $arg, $m)) $toDate = $m[1];
    }
}

if ($fromDate && $toDate) {
    $startDate = $fromDate . ' 00:00:00 GMT +0530';
    $endDate   = $toDate   . ' 23:59:59 GMT +0530';
} else {
    $startDate = date('Y-m-d', strtotime("-{$days} days")) . ' 00:00:00 GMT +0530';
    $endDate   = date('Y-m-d') . ' 23:59:59 GMT +0530';
}

$logFile = ROOTPATH . '/logs/cams-sync-' . date('Y-m-d') . '.log';

function syncLog($msg) {
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    echo $line;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

syncLog("=== CAMS Attendance Sync Started ===");
syncLog("Date range: $startDate → $endDate");

// ── Step 1: Pull punch logs from CAMS API ────────────────────────────────────

$apiUrl = CAMS_API_URL;
$stgid  = CAMS_STGID;
$token  = CAMS_AUTH_TOKEN;
$key    = CAMS_SECURITY_KEY;

function camsRequest($apiUrl, $stgid, $token, $key, $payload) {
    $payload['AuthToken'] = $token;
    $jsonPayload = json_encode($payload);

    // This device/account is in NON-ENCRYPTED mode — the CAMS api3.0 pull expects
    // plain JSON (encrypted bodies return API_RESPONSE_INVALID_RAW_DATA / StatusCode 19).
    // Confirmed working 2026-07-18. Send plain JSON.
    $body = $jsonPayload;

    $url = $apiUrl . '?stgid=' . $stgid;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) return ['ok' => false, 'error' => $err];

    // Try decrypting response (zero padding first, then normal)
    $raw = base64_decode($response);
    $dec = $raw ? openssl_decrypt($raw, 'aes-256-ecb', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING) : false;
    if (!$dec || !json_decode(trim($dec))) {
        $dec = $raw ? openssl_decrypt($raw, 'aes-256-ecb', $key, OPENSSL_RAW_DATA) : false;
    }
    if (!$dec) {
        // Maybe plain JSON
        $dec = $response;
    }

    $data = json_decode(trim($dec), true);
    if (!$data) return ['ok' => false, 'error' => 'Cannot parse response: ' . substr($response, 0, 100)];

    if (isset($data['StatusCode']) && $data['StatusCode'] != 0) {
        return ['ok' => false, 'error' => 'API error: ' . ($data['Status'] ?? $data['StatusCode'])];
    }

    return ['ok' => true, 'data' => $data];
}

// Fetch punch logs (paginated)
$offset = '';
$totalFetched = 0;
$newPunches = 0;
$iteration = 0;

// Get device ID
$devRow = $db->query("SELECT id FROM camsDevice WHERE serial_number = '" . $db->real_escape_string($stgid) . "' LIMIT 1")->fetch_assoc();
$deviceId = $devRow['id'] ?? 1;

do {
    $payload = [
        'Load' => [
            'PunchLog' => [
                'Filter' => [
                    'StartTime' => $startDate,
                    'EndTime'   => $endDate,
                ]
            ]
        ]
    ];
    if (!empty($offset)) {
        $payload['Load']['PunchLog']['Filter']['OffSet'] = $offset;
    }

    $res = camsRequest($apiUrl, $stgid, $token, $key, $payload);

    if (!$res['ok']) {
        syncLog("ERROR fetching punch logs: " . $res['error']);
        break;
    }

    $punchLog = $res['data']['PunchLog'] ?? null;
    if (!$punchLog || empty($punchLog['Log'])) {
        syncLog("No (more) punch logs returned.");
        break;
    }

    $punches = $punchLog['Log'];
    $count = count($punches);
    $totalFetched += $count;
    $actualTotal = (int)($punchLog['ActualRowCount'] ?? $count);
    $returnCount  = (int)($punchLog['ReturnRowCount'] ?? $count);

    syncLog("Batch " . ($iteration + 1) . ": got $count punches (total available: $actualTotal)");

    foreach ($punches as $punch) {
        $userId  = $punch['UserId']  ?? null;
        $logTime = $punch['LogTime'] ?? null;
        $type    = $punch['Type']    ?? 'CheckIn';
        $inputType = $punch['InputType'] ?? 'Fingerprint';

        if (!$userId || !$logTime) continue;

        // Normalise time string
        $punchTime = preg_replace('/\s+GMT\s+[+-]\d{4}.*$/', '', $logTime);
        $punchDate = substr($punchTime, 0, 10);

        // Dedup check
        $stmt = $db->prepare('SELECT id FROM camsPunch WHERE user_id = ? AND punch_time = ? LIMIT 1');
        $stmt->bind_param('ss', $userId, $punchTime);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existing) {
            $actualPunchType = ($type === 'CheckOut') ? 1 : 0;
            $inputTypeCode   = getInputTypeCode($inputType);

            // Same device-clock guard as the callback (2026-07-30): a punch stamped
            // >2h in the future or >90d in the past is stored quarantined ('Q') and
            // never feeds attendance. This pull path is dormant while the account
            // lacks PunchLog permission, but must be safe if CAMS re-enables it.
            $pTs = strtotime($punchTime);
            $punchStatus = ($pTs === false || $pTs > time() + 7200 || $pTs < time() - 90*86400) ? 'Q' : 'A';
            if ($punchStatus === 'Q') syncLog("  QUARANTINED (bad device clock): User $userId @ $punchTime");

            $stmt = $db->prepare(
                'INSERT INTO camsPunch (user_id, punch_date_str, punch_time, actual_punch_type, punch_type, input_type, device_id, status)
                 VALUES (?, ?, ?, ?, 9, ?, ?, ?)'
            );
            $stmt->bind_param('sssiiis', $userId, $punchDate, $punchTime, $actualPunchType, $inputTypeCode, $deviceId, $punchStatus);
            $stmt->execute();
            $stmt->close();
            $newPunches++;
            syncLog("  + New: User $userId @ $punchTime ($type)");
        }
    }

    // Pagination
    $offset = $punchLog['OffSet'] ?? '';
    $iteration++;

    if (empty($offset) || $returnCount >= $actualTotal) break;

    usleep(300000); // 300 ms between batches

} while ($iteration < 50);

syncLog("Fetched $totalFetched punches total, $newPunches new.");

// ── Step 2: Recalculate punch types & sync to mx_attendance ──────────────────

// Get all distinct user/date combos touched in the date range
$rangeStart = date('Y-m-d', strtotime(str_replace(' GMT +0530', '', $startDate)));
$rangeEnd   = date('Y-m-d', strtotime(str_replace(' GMT +0530', '', $endDate)));

$result = $db->query("
    SELECT DISTINCT user_id, punch_date_str
    FROM camsPunch
    WHERE punch_date_str >= '$rangeStart' AND punch_date_str <= '$rangeEnd'
    ORDER BY punch_date_str ASC, user_id ASC
");

$synced = 0;
while ($row = $result->fetch_assoc()) {
    $userId    = $row['user_id'];
    $punchDate = $row['punch_date_str'];

    recalculatePunchTypes($db, $userId, $punchDate);
    syncToHRMSAttendance($db, $userId, $punchDate);
    $synced++;
}

syncLog("Synced $synced user/date combinations to mx_attendance.");
syncLog("=== Sync Complete ===\n");

// ── Helper functions ──────────────────────────────────────────────────────────

function recalculatePunchTypes($db, $userId, $punchDate) {
    $stmt = $db->prepare(
        // status <> 'Q': quarantined punches (bad device clock) must never feed attendance
        'SELECT id FROM camsPunch WHERE user_id = ? AND punch_date_str = ? AND status <> "Q" ORDER BY punch_time ASC'
    );
    $stmt->bind_param('ss', $userId, $punchDate);
    $stmt->execute();
    $punches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($punches)) return;

    $last = count($punches) - 1;
    foreach ($punches as $i => $p) {
        $type = ($i === 0) ? 0 : (($i === $last) ? 1 : 5);
        $stmt = $db->prepare('UPDATE camsPunch SET punch_type = ? WHERE id = ?');
        $stmt->bind_param('ii', $type, $p['id']);
        $stmt->execute();
        $stmt->close();
    }
}

function syncToHRMSAttendance($db, $camsUserId, $punchDate) {
    // Find employee
    $stmt = $db->prepare(
        // saturdayStartTime/saturdayEndTime must be in this SELECT — the callback had
        // the same columns missing and every Saturday was scored against weekday hours
        // for 7 months. Fixed here at the same time (2026-07-30).
        'SELECT userID, workStartTime, workEndTime, lateGraceMinutes,
                saturdayStartTime, saturdayEndTime
         FROM mx_x_admin_user
         WHERE biometricID = ? AND status = 1 LIMIT 1'
    );
    $stmt->bind_param('s', $camsUserId);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$employee) return; // No HRMS employee mapped to this biometricID

    $hrmsUserId  = $employee['userID'];
    $isSaturday  = (date('w', strtotime($punchDate)) == 6);
    if ($isSaturday && !empty($employee['saturdayStartTime'])) {
        $scheduledIn  = $employee['saturdayStartTime'];
        $scheduledOut = $employee['saturdayEndTime'] ?: '16:00:00';
    } else {
        $scheduledIn  = $employee['workStartTime']  ?: '09:00:00';
        $scheduledOut = $employee['workEndTime']     ?: '18:00:00';
    }
    $graceMinutes = $employee['lateGraceMinutes'] ?: 15;

    // Get punches for the day sorted ascending
    $stmt = $db->prepare(
        'SELECT punch_time FROM camsPunch WHERE user_id = ? AND punch_date_str = ? AND status <> "Q" ORDER BY punch_time ASC'
    );
    $stmt->bind_param('ss', $camsUserId, $punchDate);
    $stmt->execute();
    $punches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($punches)) return;

    $firstPunch = $punches[0]['punch_time'];
    $lastPunch  = $punches[count($punches) - 1]['punch_time'];

    $checkIn  = $firstPunch;
    $checkOut = (count($punches) > 1) ? $lastPunch : null;

    // Smart afternoon-only detection
    if (count($punches) === 1 && (int)date('H', strtotime($firstPunch)) >= 14) {
        $checkIn  = null;
        $checkOut = $firstPunch;
    }

    // Late calculation
    $isLate = 0; $lateMinutes = 0;
    if ($checkIn) {
        $inTimestamp = strtotime(date('H:i:s', strtotime($checkIn)));
        $schInTs     = strtotime($scheduledIn);
        $graceTs     = $schInTs + ($graceMinutes * 60);
        if ($inTimestamp > $graceTs) {
            $isLate      = 1;
            $lateMinutes = max(0, (int)(($inTimestamp - $schInTs) / 60));
        }
    }

    // Early checkout & working hours
    $isEarly = 0; $earlyMinutes = 0; $workingHours = 0;
    if ($checkOut) {
        $outTimestamp = strtotime(date('H:i:s', strtotime($checkOut)));
        $schOutTs     = strtotime($scheduledOut);
        if ($outTimestamp < $schOutTs) {
            $isEarly      = 1;
            $earlyMinutes = max(0, (int)(($schOutTs - $outTimestamp) / 60));
        }
        if ($checkIn) {
            $workingHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
        }
    }

    $rawData = json_encode(['cams_user_id' => $camsUserId, 'punches' => $punches, 'synced_at' => date('Y-m-d H:i:s')]);

    // Check existing attendance record
    $stmt = $db->prepare('SELECT attendanceID, checkIn, checkOut, scheduledIn, scheduledOut, source
                          FROM mx_attendance WHERE userID = ? AND attendanceDate = ? LIMIT 1');
    $stmt->bind_param('is', $hrmsUserId, $punchDate);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        /**
         * PRESERVE EXISTING DATA (2026-07-30). This UPDATE used to overwrite checkIn
         * unconditionally — on 30 Jul it nulled a manually backfilled check-in within
         * 20 minutes of the correction, because the morning punch never reached
         * camsPunch so the derived checkIn was NULL. Rules now:
         *   - never replace a non-null checkIn with NULL;
         *   - if both exist, keep the EARLIER checkIn (same rule as the callback);
         *   - keep the LATER checkOut;
         *   - score against the ROW'S stored schedule, not the employee's current
         *     one, so historical rows are not silently re-stamped.
         */
        if (!empty($existing['checkIn']) &&
            ($checkIn === null || strtotime($existing['checkIn']) < strtotime($checkIn))) {
            $checkIn = $existing['checkIn'];
        }
        if (!empty($existing['checkOut']) &&
            ($checkOut === null || strtotime($existing['checkOut']) > strtotime($checkOut))) {
            $checkOut = $existing['checkOut'];
        }
        $rowSchedIn  = $existing['scheduledIn']  ?: $scheduledIn;
        $rowSchedOut = $existing['scheduledOut'] ?: $scheduledOut;

        // recompute derived fields from the FINAL values against the row's schedule
        $isLate = 0; $lateMinutes = 0;
        if ($checkIn) {
            $inT  = strtotime(date('H:i:s', strtotime($checkIn)));
            $sInT = strtotime($rowSchedIn);
            if ($inT > $sInT + ($graceMinutes * 60)) {
                $isLate = 1;
                $lateMinutes = max(0, (int)(($inT - $sInT) / 60));
            }
        }
        $isEarly = 0; $earlyMinutes = 0; $workingHours = 0;
        if ($checkOut) {
            $outT  = strtotime(date('H:i:s', strtotime($checkOut)));
            $sOutT = strtotime($rowSchedOut);
            if ($outT < $sOutT) {
                $isEarly = 1;
                $earlyMinutes = max(0, (int)(($sOutT - $outT) / 60));
            }
            if ($checkIn) $workingHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
        }

        $attId = $existing['attendanceID'];
        $stmt  = $db->prepare(
            'UPDATE mx_attendance SET
                checkIn = ?, checkOut = ?,
                isLate = ?, lateMinutes = ?,
                isEarlyCheckout = ?, earlyMinutes = ?,
                workingHours = ?,
                attendanceStatus = "present",
                source = CASE WHEN source = "manual" THEN "manual" ELSE "biometric" END,
                biometricRaw = ?, syncedAt = NOW()
             WHERE attendanceID = ?'
        );
        $stmt->bind_param('ssiiiidsi', $checkIn, $checkOut, $isLate, $lateMinutes, $isEarly, $earlyMinutes, $workingHours, $rawData, $attId);
        $stmt->execute();
        $stmt->close();
        syncLog("  Updated attendance: UserID=$hrmsUserId Date=$punchDate IN=" . ($checkIn ?: '-') . " OUT=" . ($checkOut ?: '-'));
    } else {
        $remarks = 'Synced from CAMS';
        $stmt = $db->prepare(
            'INSERT INTO mx_attendance (
                userID, attendanceDate, scheduledIn, scheduledOut,
                checkIn, checkOut, isLate, lateMinutes,
                isEarlyCheckout, earlyMinutes, workingHours,
                attendanceStatus, source, biometricRaw, remarks, syncedAt, status
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "present", "biometric", ?, ?, NOW(), 1)'
        );
        $stmt->bind_param('isssssiiiiiss',
            $hrmsUserId, $punchDate, $scheduledIn, $scheduledOut,
            $checkIn, $checkOut, $isLate, $lateMinutes, $isEarly, $earlyMinutes, $workingHours,
            $rawData, $remarks
        );
        $stmt->execute();
        $stmt->close();
        syncLog("  Inserted attendance: UserID=$hrmsUserId Date=$punchDate IN=" . ($checkIn ?: '-') . " OUT=" . ($checkOut ?: '-'));
    }
}

function getInputTypeCode($input) {
    switch ($input) {
        case 'Fingerprint': return 0;
        case 'Face':        return 1;
        case 'Card':        return 3;
        case 'Password':    return 4;
        default:            return 9;
    }
}
