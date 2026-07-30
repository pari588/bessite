<?php
/**
 * PHASE 4 — "you forgot to Mark Out" WhatsApp reminder.
 *
  * Schedule: 22:30 IST daily. NOTE: cron on this server runs in IST (verified — a
 *           30 21 cron wrote its log at 21:30), so use IST times directly, NOT UTC.
  *   30 22 * * * /usr/bin/php /home/bombayengg/public_html/cron/driver-markout-reminder.php
 *
 * WHO GETS IT: only drivers who actually pressed Mark In today (recordType = 2) and have
 * not marked out. Deliberately NOT every driver with an open row — autoMarkin() creates a
 * baseline row every working day, so reminding on that would message them every night.
 * A driver who never marked in has nothing to forget.
 *
 * TEMPLATE REQUIREMENT: this is a business-initiated message outside the 24-hour customer
 * service window, so WhatsApp requires an APPROVED template. Submit in WhatsApp Manager:
 *
 *   Name:     driver_markout_reminder
 *   Category: UTILITY
 *   Language: en   (Hinglish body is fine under 'en')
 *   Body:     Namaste {{1}}, aapne aaj ka Mark Out nahi kiya hai. Overtime record karne ke
 *             liye is chat me "Out" bhejein. / You have not marked out today. Reply "Out"
 *             to record your overtime.
 *   Variable: {{1}} = driver first name
 *
 * Until it is approved this script will log a failure per driver and send nothing — it is
 * safe to schedule now; it simply becomes active once Meta approves.
 */

$_SERVER['HTTP_HOST']     = 'www.bombayengg.net';
$_SERVER['REQUEST_URI']   = '/';
$_SERVER['SERVER_PORT']   = '443';
$_SERVER['HTTPS']         = 'on';
$_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';

require_once('/home/bombayengg/public_html/config.inc.php');
require_once('/home/bombayengg/public_html/core/core.inc.php');
require_once('/home/bombayengg/public_html/core/driver-overtime.inc.php');
require_once('/home/bombayengg/public_html/core/whatsapp-api.inc.php');

global $DB;
if (!isset($DB) || !is_object($DB)) $DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);

$TEMPLATE = 'driver_markout_reminder';
$LANG     = 'en';
$today    = date('Y-m-d');

function rlog($m) { echo '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n"; }

rlog("Mark-out reminder check for {$today}");

// Drivers who pressed Mark In today and have not marked out.
$DB->vals  = array(1, $today, 1);
$DB->types = "isi";
$DB->sql   = "SELECT d.driverManagementID, d.userID, d.fromTime, u.userName, u.userMobileNo
              FROM `" . $DB->pre . "driver_management` d
              JOIN `" . $DB->pre . "user` u ON u.userID = d.userID AND u.status = ?
              WHERE d.status = ? AND d.dmDate = ? AND d.recordType = 2
                AND (d.toTime IS NULL OR d.toTime = '' OR d.toTime = '0000-00-00 00:00:00'
                     OR d.toTime <= d.fromTime)";
$DB->vals  = array(1, 1, $today);
$DB->types = "iis";
$rows = $DB->dbRows();

if ($DB->numRows <= 0) {
    rlog("No driver has an unclosed Mark In today — nothing to send.");
    exit(0);
}

$wa = new WhatsAppAPI($DB);
$sent = 0; $failed = 0;

foreach ($rows as $r) {
    // Never chase a driver who is on recorded leave.
    if (function_exists('isDriverOnLeave') && isDriverOnLeave($r['userID'], $today)) {
        rlog("Skipped {$r['userName']} — on leave.");
        continue;
    }
    $phone = preg_replace('/[^0-9]/', '', (string)$r['userMobileNo']);
    if ($phone === '') { rlog("Skipped {$r['userName']} — no mobile number on file."); continue; }
    if (strlen($phone) === 10) $phone = '91' . $phone;

    $first = explode(' ', trim($r['userName']))[0];
    $components = array(array(
        'type' => 'body',
        'parameters' => array(array('type' => 'text', 'text' => $first)),
    ));

    $res = $wa->sendTemplate($phone, $TEMPLATE, $LANG, $components);
    if (!empty($res['messages']) || (isset($res['success']) && $res['success'])) {
        $sent++;
        rlog("Reminder sent to {$r['userName']} ({$phone}) — marked in at " . date('g:i A', strtotime($r['fromTime'])));
    } else {
        $failed++;
        rlog("FAILED for {$r['userName']} ({$phone}) — " . json_encode($res)
           . "  [if this is a template error, '{$TEMPLATE}' is not approved yet]");
    }
}

rlog("Done. sent={$sent} failed={$failed}");
exit(0);
