<?php
/**
 * recalc-saturday-schedules.php
 *
 * Repairs Saturday attendance rows scored against weekday hours.
 *
 * The sync branched on saturdayStartTime / saturdayEndTime but never SELECTed those
 * columns, so the branch could not fire and every Saturday used the weekday schedule
 * (typically 10:00-18:00) instead of the real Saturday shift (10:00-16:00). Staff who
 * left on time at 16:00 were recorded as roughly two hours early, every week since
 * January 2026. The code bug is fixed in cams-punch-processor.inc.php and
 * cams-biometric-callback.php; this repairs the history.
 *
 * Only schedule-derived fields are touched:
 *   scheduledOut, isEarlyCheckout, earlyMinutes
 *
 * checkIn, checkOut and workingHours are NOT changed — the punches were always
 * correct, and workingHours is measured between them, not against the schedule.
 * source stays as recorded: this is a rescoring, not a manual override of the data.
 *
 * Usage:
 *   php cron/recalc-saturday-schedules.php           # dry run, shows every change
 *   php cron/recalc-saturday-schedules.php --apply
 */

if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST']     = 'www.bombayengg.net';
    $_SERVER['REQUEST_URI']   = '/cron/recalc-saturday-schedules.php';
    $_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';
}
require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';
date_default_timezone_set('Asia/Kolkata');

$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con;
$APPLY = in_array('--apply', $argv, true);

$rows = $db->query(
    "SELECT a.attendanceID, a.attendanceDate, a.checkIn, a.checkOut,
            a.scheduledIn, a.scheduledOut, a.isLate, a.lateMinutes,
            a.isEarlyCheckout, a.earlyMinutes, a.remarks,
            e.displayName, e.lateGraceMinutes, e.saturdayStartTime, e.saturdayEndTime
     FROM mx_attendance a
     JOIN mx_x_admin_user e ON e.userID = a.userID
     WHERE DAYOFWEEK(a.attendanceDate) = 7
       AND e.saturdayStartTime IS NOT NULL
       AND e.saturdayEndTime IS NOT NULL
       -- END TIME ONLY. Deliberately not matching on scheduledIn: 39 rows from
       -- Apr-Dec 2025 (all Sakshi) carry a 10:00 start against her current 11:00
       -- Saturday schedule. That is a real schedule change, not the bug, and
       -- rescoring them against today's timings would falsify last year's records.
       AND a.scheduledOut <> e.saturdayEndTime
     ORDER BY a.attendanceDate, e.displayName"
);

echo $APPLY ? "APPLYING\n\n" : "DRY RUN — re-run with --apply\n\n";

$n = $earlyCleared = $lateChanged = 0;
$minutesCleared = 0;

while ($r = $rows->fetch_assoc()) {
    // keep whatever start time the row was recorded with — only the end time was wrong
    $schedIn  = $r['scheduledIn'];
    $schedOut = $r['saturdayEndTime'];
    $grace    = (int)($r['lateGraceMinutes'] ?: 15);

    /**
     * Lateness is deliberately NOT recomputed. The bug only ever affected the end
     * time, so the stored start time and late flags were already right for the day
     * they were recorded. Three of Ganesh's rows are flagged late at 50-60 min
     * against his CURRENT 60-min grace, which means his grace was raised at some
     * point — recalculating would silently erase lateness that was real at the time.
     */
    $isLate  = (int)$r['isLate'];
    $lateMin = (int)$r['lateMinutes'];
    $isEarly = $earlyMin = 0;
    if (!empty($r['checkOut'])) {
        $earlyBy  = (int)round((strtotime($schedOut) - strtotime(date('H:i:s', strtotime($r['checkOut'])))) / 60);
        $isEarly  = ($earlyBy > 0) ? 1 : 0;
        $earlyMin = $isEarly ? $earlyBy : 0;
    }

    $n++;
    if ($r['isEarlyCheckout'] && !$isEarly) { $earlyCleared++; $minutesCleared += (int)$r['earlyMinutes']; }
    // lateness carried through unchanged by design

    if (!$APPLY) {
        if ($n <= 12) {
            printf("  %s  %-16s out %s  sched %s->%s  early %dm -> %dm\n",
                $r['attendanceDate'], $r['displayName'],
                $r['checkOut'] ? substr($r['checkOut'], 11, 5) : '  -  ',
                substr($r['scheduledOut'], 0, 5), substr($schedOut, 0, 5),
                (int)$r['earlyMinutes'], $earlyMin);
        }
        continue;
    }

    $note = trim(($r['remarks'] ?? '') . ' [Saturday schedule corrected to '
          . substr($schedIn, 0, 5) . '-' . substr($schedOut, 0, 5) . ' on 30-Jul-2026]');
    $u = $db->prepare('UPDATE mx_attendance
                       SET scheduledOut = ?, isEarlyCheckout = ?, earlyMinutes = ?, remarks = ?
                       WHERE attendanceID = ?');
    $u->bind_param('siisi', $schedOut, $isEarly, $earlyMin, $note, $r['attendanceID']);
    $u->execute();
    $u->close();
}

if (!$APPLY && $n > 12) echo "  ... and " . ($n - 12) . " more\n";

echo "\n";
printf("  rows rescored          : %d\n", $n);
printf("  false 'early' cleared  : %d rows, %d minutes (%.1f hours)\n",
       $earlyCleared, $minutesCleared, $minutesCleared / 60);
printf("  late flags            : untouched by design\n");
echo "\n" . ($APPLY ? "done. checkIn/checkOut/workingHours untouched.\n" : "no changes made.\n");
