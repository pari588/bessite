<?php
/**
 * Driver overtime engine — regression self-test.
 *
 * Exercises dvCalculateOvertime() directly (no HTTP, no DB writes) across the scenario
 * matrix and prints the computed figures. Run this after ANY change to the overtime rules
 * or to core/driver-overtime.inc.php and compare the output to the committed expectations
 * below; a mismatch is printed as FAIL.
 *
 *   php cron/driver-overtime-selftest.php
 *
 * Safe to run on production: reads driver rates, writes nothing.
 */

$_SERVER['HTTP_HOST']     = 'www.bombayengg.net';
$_SERVER['REQUEST_URI']   = '/';
$_SERVER['SERVER_PORT']   = '443';
$_SERVER['HTTPS']         = 'on';
$_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';

require_once('/home/bombayengg/public_html/config.inc.php');
require_once('/home/bombayengg/public_html/core/core.inc.php');
require_once('/home/bombayengg/public_html/core/driver-overtime.inc.php');

global $DB;
if (!isset($DB) || !is_object($DB)) {
    $DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
}

/** label => [userID, dmDate, fromTime, toTime, expected "otHrs|otPay|dinner|taxi|sun|total"] */
$WD = '2026-07-22';   // Wednesday — working day for both drivers
$SD = '2026-07-26';   // Sunday    — Dilkhush's off day
$SA = '2026-07-25';   // Saturday  — Suraj's off day

$cases = array(
    // Dilkhush: shift 10:00-20:00, OT 75/hr, dinner 150 @22:00, taxi 100 @23:59, off = Sunday
    'd1 normal-no-OT'                => array(1, $WD, "$WD 10:00:00", "$WD 20:00:00", '0|0|0|0|0|0'),
    'd1 early-3h'                    => array(1, $WD, "$WD 07:00:00", "$WD 20:00:00", '3|225|0|0|0|225'),
    'd1 late-2h'                     => array(1, $WD, "$WD 10:00:00", "$WD 22:00:00", '2|150|150|0|0|300'),
    'd1 early3+late2'                => array(1, $WD, "$WD 07:00:00", "$WD 22:00:00", '5|375|150|0|0|525'),
    'd1 late-only-from-shiftend'     => array(1, $WD, "$WD 20:00:00", "$WD 22:00:00", '2|150|150|0|0|300'),
    'd1 dinner-boundary-just-before' => array(1, $WD, "$WD 10:00:00", "$WD 21:59:00", '1.98|148|0|0|0|148'),
    'd1 taxi-boundary-exact'         => array(1, $WD, "$WD 10:00:00", "$WD 23:59:00", '3.98|298|150|100|0|548'),
    'd1 cross-midnight'              => array(1, $WD, "$WD 10:00:00", '2026-07-23 01:30:00', '5.5|412|150|100|0|662'),
    'd1 offday-3h'                   => array(1, $SD, "$SD 10:00:00", "$SD 13:00:00", '0|0|0|0|450|450'),
    'd1 offday-6h'                   => array(1, $SD, "$SD 10:00:00", "$SD 16:00:00", '0|0|0|0|600|600'),
    'd1 offday-late-start-1h'        => array(1, $SD, "$SD 22:00:00", "$SD 23:00:00", '1|75|150|0|450|675'),
    // Suraj: shift 09:00-19:00, OT 75/hr, dinner 100 @22:00, taxi 100 @24:00, off = Saturday
    'd2 normal-no-OT'                => array(2, $WD, "$WD 09:00:00", "$WD 19:00:00", '0|0|0|0|0|0'),
    'd2 late-2h'                     => array(2, $WD, "$WD 09:00:00", "$WD 21:00:00", '2|150|0|0|0|150'),
    'd2 late-only-from-shiftend'     => array(2, $WD, "$WD 19:00:00", "$WD 21:00:00", '2|150|0|0|0|150'),
    'd2 offday-3h'                   => array(2, $SA, "$SA 09:00:00", "$SA 12:00:00", '0|0|0|0|600|600'),
    'd2 offday-late-start'           => array(2, $SA, "$SA 21:00:00", "$SA 22:30:00", '1.5|112|100|0|600|812'),
);

$ratesCache = array();
$pass = 0; $fail = 0;

printf("%-34s %-26s %s\n", 'SCENARIO', 'otHrs|otPay|din|taxi|sun|TOTAL', 'RESULT');
echo str_repeat('-', 86) . "\n";

foreach ($cases as $label => $c) {
    list($uid, $dmDate, $from, $to, $expected) = $c;
    if (!isset($ratesCache[$uid])) $ratesCache[$uid] = dvGetDriverRates($uid);
    $rates = $ratesCache[$uid];
    if (!$rates) { printf("%-34s %-26s %s\n", $label, '-', 'SKIP (no driver)'); continue; }

    $calc = dvCalculateOvertime(
        array('fromTime' => $from, 'toTime' => $to, 'dmDate' => $dmDate, 'expenseAmt' => 0),
        $rates,
        dvIsOffDay($uid, $dmDate)
    );
    // Money is stored as int (matches the historical intval() behaviour); hours keep 2dp.
    $actual = implode('|', array(
        rtrim(rtrim(number_format($calc['overtimeHrs'], 2, '.', ''), '0'), '.') ?: '0',
        intval($calc['totalOvertimePay']),
        intval($calc['dinnerAllowance']),
        intval($calc['taxiAllowance']),
        intval($calc['sunAllowance']),
        intval($calc['totalPay']),
    ));
    $ok = ($actual === $expected);
    $ok ? $pass++ : $fail++;
    printf("%-34s %-26s %s\n", $label, $actual, $ok ? 'PASS' : "FAIL (expected $expected)");
}

echo str_repeat('-', 86) . "\n";
printf("%d passed, %d failed\n", $pass, $fail);
exit($fail === 0 ? 0 : 1);
