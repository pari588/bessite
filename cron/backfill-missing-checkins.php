<?php
/**
 * backfill-missing-checkins.php
 *
 * Three days carried a check-out but no check-in, because the morning punch never
 * reached the server. The "lone punch after 2pm must be a check-out" rule then did
 * the right thing with the data it had, but left workingHours at 0.
 *
 * Owner-supplied arrival times, 30 Jul 2026:
 *   Ananda Pawar   18 Jul (Sat)  10:33
 *   Ganesh Murkute 21 Jul (Tue)  10:32
 *   Sakshi Satam   28 Jul (Tue)  10:45
 *
 * Existing check-outs are preserved — only the check-in is added and the derived
 * figures recalculated, per the standing rule not to overwrite biometric data.
 *
 * Saturday note: the schedule columns saturdayStartTime / saturdayEndTime exist and
 * Pawar's Saturday is 10:00-16:00, but every stored Saturday row says 18:00. The
 * sync never applies them (see the bug note in cams-punch-processor.inc.php), so
 * this script uses the correct Saturday schedule for the 18 Jul row.
 *
 * Usage:
 *   php cron/backfill-missing-checkins.php           # dry run
 *   php cron/backfill-missing-checkins.php --apply
 */

if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST']     = 'www.bombayengg.net';
    $_SERVER['REQUEST_URI']   = '/cron/backfill-missing-checkins.php';
    $_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';
}
require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';
date_default_timezone_set('Asia/Kolkata');

$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con;
$APPLY = in_array('--apply', $argv, true);

$JOBS = [
    ['id' => 2911, 'userID' => 13, 'date' => '2026-07-18', 'in' => '10:33:00'],
    ['id' => 2923, 'userID' => 11, 'date' => '2026-07-21', 'in' => '10:32:00'],
    ['id' => 2959, 'userID' => 12, 'date' => '2026-07-28', 'in' => '10:45:00'],
];

echo $APPLY ? "APPLYING\n\n" : "DRY RUN — re-run with --apply\n\n";

foreach ($JOBS as $j) {
    $st = $db->prepare('SELECT a.checkOut, a.checkIn, e.displayName, e.workStartTime, e.workEndTime,
                               e.lateGraceMinutes, e.saturdayStartTime, e.saturdayEndTime
                        FROM mx_attendance a JOIN mx_x_admin_user e ON e.userID = a.userID
                        WHERE a.attendanceID = ? LIMIT 1');
    $st->bind_param('i', $j['id']);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$r)                       { echo "  !! row {$j['id']} missing\n"; continue; }
    if (!empty($r['checkIn']))     { echo "  ~~ {$r['displayName']} {$j['date']} already has a check-in — skipped\n"; continue; }
    if (empty($r['checkOut']))     { echo "  !! {$r['displayName']} {$j['date']} has no check-out — skipped\n"; continue; }

    // Saturday uses its own schedule where one is configured
    $isSat = (date('w', strtotime($j['date'])) == 6);
    $schedIn  = ($isSat && !empty($r['saturdayStartTime'])) ? $r['saturdayStartTime'] : ($r['workStartTime'] ?: '10:00:00');
    $schedOut = ($isSat && !empty($r['saturdayEndTime']))   ? $r['saturdayEndTime']   : ($r['workEndTime']   ?: '18:00:00');
    $grace    = (int)($r['lateGraceMinutes'] ?: 15);

    $checkInFull = $j['date'] . ' ' . $j['in'];
    $inTs   = strtotime($j['in']);
    $outTs  = strtotime(date('H:i:s', strtotime($r['checkOut'])));
    $sInTs  = strtotime($schedIn);
    $sOutTs = strtotime($schedOut);

    $lateBy   = (int)round(($inTs - $sInTs) / 60);
    $isLate   = ($lateBy > $grace) ? 1 : 0;
    $lateMin  = $isLate ? $lateBy : 0;

    $earlyBy  = (int)round(($sOutTs - $outTs) / 60);
    $isEarly  = ($earlyBy > 0) ? 1 : 0;
    $earlyMin = $isEarly ? $earlyBy : 0;

    $hours = round((strtotime($r['checkOut']) - strtotime($checkInFull)) / 3600, 2);

    printf("  %-16s %s %s  in %s  out %s  sched %s-%s  ->  %.2f h, %s%s\n",
        $r['displayName'], $j['date'], $isSat ? '(Sat)' : '     ',
        substr($j['in'], 0, 5), substr($r['checkOut'], 11, 5),
        substr($schedIn, 0, 5), substr($schedOut, 0, 5), $hours,
        $isLate ? "late {$lateMin}m" : 'on time',
        $isEarly ? ", early out {$earlyMin}m" : '');

    if (!$APPLY) continue;

    $remarks = 'Check-in backfilled 30-Jul-2026 from owner-supplied arrival time; '
             . 'morning punch never reached the server. Check-out preserved as recorded by device.';
    $u = $db->prepare('UPDATE mx_attendance
                       SET checkIn = ?, scheduledIn = ?, scheduledOut = ?,
                           workingHours = ?, isLate = ?, lateMinutes = ?,
                           isEarlyCheckout = ?, earlyMinutes = ?,
                           attendanceStatus = "present", source = "manual",
                           remarks = ?, syncedAt = NOW()
                       WHERE attendanceID = ?');
    $u->bind_param('sssdiiiisi', $checkInFull, $schedIn, $schedOut, $hours,
                   $isLate, $lateMin, $isEarly, $earlyMin, $remarks, $j['id']);
    $u->execute();
    echo "       updated attendanceID {$j['id']} ({$u->affected_rows} row)\n";
    $u->close();
}

echo "\n" . ($APPLY ? "done.\n" : "no changes made.\n");
