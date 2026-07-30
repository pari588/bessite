<?php
/**
 * fix-clock-drift-attendance.php
 *
 * One-off repair for the 30 Jul 2026 biometric clock jump.
 *
 * The device clock ran 13d 12h ahead between 10:16 and 10:55 on 30 Jul 2026.
 * Two real punches were stamped 2026-08-12 23:09 / 23:20, so the pipeline:
 *   - filed the attendance under 12 August instead of today, and
 *   - because the fake hour was 23:xx, the "afternoon = checkout" rule recorded
 *     them as a check-OUT with no check-in.
 * Result: Ganesh and Sakshi read as absent on a day they had worked, and a
 * future date carried phantom attendance.
 *
 * This script:
 *   1. quarantines the two bad raw punches (evidence kept, but they can no
 *      longer feed attendance now that the processors honour status 'Q'),
 *   2. deletes the phantom 12 August rows,
 *   3. writes today's rows with the corrected check-in times,
 *      recalculating isLate / lateMinutes from each employee's own schedule.
 *
 * Usage:
 *   php cron/fix-clock-drift-attendance.php            # dry run, shows the plan
 *   php cron/fix-clock-drift-attendance.php --apply    # writes
 *
 * Backup taken before first run:
 *   /home/bombayengg/attendance-backups/attendance_fix_20260730_114731.sql
 */

if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST']     = 'www.bombayengg.net';
    $_SERVER['REQUEST_URI']   = '/cron/fix-clock-drift-attendance.php';
    $_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';
}
require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';

$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con;

$APPLY = in_array('--apply', $argv, true);
$TODAY = date('Y-m-d');

/**
 * Corrected check-in times.
 *
 * Ganesh: 10:30 — stated by the owner.
 * Sakshi: 10:41 — inferred. The two corrupt punches are 11m 10s apart in device
 *         time and were flushed together, so her punch followed his by that
 *         interval. Flagged in remarks as inferred so it can be challenged.
 */
$FIX = [
    ['userID' => 11, 'name' => 'Ganesh Murkute', 'checkIn' => '10:30:00',
     'badPunchId' => 1300, 'phantomID' => 2970, 'source' => 'owner-confirmed'],
    ['userID' => 12, 'name' => 'Sakshi Satam',  'checkIn' => '10:41:00',
     'badPunchId' => 1301, 'phantomID' => 2971, 'source' => 'inferred from 11m10s punch gap'],
];

echo $APPLY ? "APPLYING\n\n" : "DRY RUN — nothing will be written. Re-run with --apply\n\n";

foreach ($FIX as $f) {
    // employee's own schedule drives lateness — never assume a global 10:00
    $st = $db->prepare('SELECT displayName, workStartTime, workEndTime, lateGraceMinutes
                        FROM mx_x_admin_user WHERE userID = ? LIMIT 1');
    $st->bind_param('i', $f['userID']);
    $st->execute();
    $emp = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$emp) { echo "  !! no employee {$f['userID']}\n"; continue; }

    $schedIn   = $emp['workStartTime'] ?: '10:00:00';
    $schedOut  = $emp['workEndTime']   ?: '18:00:00';
    $grace     = (int)($emp['lateGraceMinutes'] ?: 15);

    $inTs      = strtotime($f['checkIn']);
    $schedTs   = strtotime($schedIn);
    $lateBy    = (int)round(($inTs - $schedTs) / 60);
    $isLate    = ($lateBy > $grace) ? 1 : 0;
    $lateMin   = $isLate ? $lateBy : 0;

    printf("  %-15s in %s  sched %s  grace %dm  ->  %s%s\n",
        $emp['displayName'], substr($f['checkIn'],0,5), substr($schedIn,0,5), $grace,
        $isLate ? "LATE by {$lateMin}m" : 'on time',
        $lateBy < 0 ? sprintf(' (%dm early)', abs($lateBy)) : '');

    if (!$APPLY) continue;

    // 1. quarantine the corrupt raw punch — keep it, but stop it feeding attendance
    $q = $db->prepare("UPDATE camsPunch SET status='Q' WHERE id = ?");
    $q->bind_param('i', $f['badPunchId']);
    $q->execute(); $q->close();

    // 2. remove the phantom future-dated row
    $d = $db->prepare('DELETE FROM mx_attendance WHERE attendanceID = ? AND attendanceDate > CURDATE()');
    $d->bind_param('i', $f['phantomID']);
    $d->execute();
    $delRows = $d->affected_rows; $d->close();

    // 3. write today's row (update if the sync has since created one)
    $checkInFull = $TODAY . ' ' . $f['checkIn'];
    $remarks = 'Corrected after biometric clock drift of 13d12h on 30 Jul 2026. '
             . 'Device stamped this punch 2026-08-12. Check-in ' . $f['source'] . '.';
    $raw = 'orig_punch_id=' . $f['badPunchId'] . '; quarantined';

    $chk = $db->prepare('SELECT attendanceID FROM mx_attendance WHERE userID = ? AND attendanceDate = ? LIMIT 1');
    $chk->bind_param('is', $f['userID'], $TODAY);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($existing) {
        $u = $db->prepare('UPDATE mx_attendance SET checkIn=?, scheduledIn=?, scheduledOut=?,
                             isLate=?, lateMinutes=?, attendanceStatus="present", source="manual",
                             biometricRaw=?, remarks=?, syncedAt=NOW() WHERE attendanceID=?');
        $u->bind_param('sssiissi', $checkInFull, $schedIn, $schedOut, $isLate, $lateMin,
                        $raw, $remarks, $existing['attendanceID']);
        $u->execute(); $u->close();
        echo "     updated attendanceID {$existing['attendanceID']}; phantom rows removed: $delRows\n";
    } else {
        $i = $db->prepare('INSERT INTO mx_attendance
            (userID, attendanceDate, scheduledIn, scheduledOut, checkIn, checkOut,
             isLate, lateMinutes, isEarlyCheckout, earlyMinutes, workingHours,
             attendanceStatus, source, biometricRaw, remarks, syncedAt, status)
            VALUES (?,?,?,?,?,NULL,?,?,0,0,0,"present","manual",?,?,NOW(),1)');
        $i->bind_param('issssiiss', $f['userID'], $TODAY, $schedIn, $schedOut,
                        $checkInFull, $isLate, $lateMin, $raw, $remarks);
        $i->execute();
        echo "     inserted attendanceID {$db->insert_id}; phantom rows removed: $delRows\n";
        $i->close();
    }
}

echo "\n";
echo $APPLY ? "done. check-out will attach normally when they punch this evening.\n"
            : "no changes made.\n";
