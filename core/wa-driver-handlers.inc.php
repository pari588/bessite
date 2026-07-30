<?php
/**
 * WhatsApp handlers for DRIVERS (overtime only).
 *
 * Drivers live in `mx_user`, separate from HRMS employees (`mx_x_admin_user`), so the
 * webhook needs its own identity branch for them — before this existed, a driver
 * messaging the business number fell through to the CUSTOMER pump-finder menu.
 *
 * SCOPE: overtime only. Mark In = early overtime, Mark Out = late overtime. This never
 * records attendance, and the nightly autoMarkin() baseline row (the normal shift) is
 * untouched — shift timings stay automatic.
 *
 * All rules and pay maths come from core/driver-overtime.inc.php, the same engine the
 * admin panel and the driver portal use. Nothing is reimplemented here.
 */

require_once dirname(__FILE__) . '/driver-overtime.inc.php';

if (!function_exists('dvWaMainMenu')) {

    /** Driver home menu. */
    function dvWaMainMenu($wa, $fromNumber, $driver)
    {
        $first = explode(' ', trim($driver['userName']))[0];
        $wa->sendInteractiveButtons(
            $fromNumber,
            "नमस्ते *{$first}* 🚗\n\nOvertime के लिए नीचे से चुनें:\n_(Choose an option below)_",
            array(
                array('id' => 'dv_markin',  'title' => 'Mark In (सुबह)'),
                array('id' => 'dv_markout', 'title' => 'Mark Out (रात)'),
                array('id' => 'dv_info',    'title' => 'My Overtime'),
            )
        );
    }

    /** Second-level menu for read-only queries. */
    function dvWaInfoMenu($wa, $fromNumber)
    {
        $wa->sendInteractiveButtons(
            $fromNumber,
            "क्या देखना है? _(What would you like to see?)_",
            array(
                array('id' => 'dv_month',   'title' => 'This Month'),
                array('id' => 'dv_pending', 'title' => 'Pending Amount'),
                array('id' => 'dv_menu',    'title' => 'Back'),
            )
        );
    }

    /** PHASE 3 — Mark In (early overtime). */
    function dvWaMarkIn($DB, $wa, $fromNumber, $driver)
    {
        $res = dvDoMarkIn($driver['userID']);
        if (!empty($res['err'])) {
            $wa->sendText($fromNumber, "⚠️ " . $res['msg']);
            dvWaMainMenu($wa, $fromNumber, $driver);
            return;
        }
        $wa->sendText($fromNumber,
            "✅ *Mark In दर्ज हो गया*\n\n"
            . "समय / Time: *" . date('g:i A', strtotime($res['time'])) . "*\n"
            . date('D, d M Y', strtotime($res['time'])) . "\n\n"
            . "_Early overtime शुरू। रात में जाते समय Mark Out करें।_\n"
            . "_(Remember to Mark Out when you leave.)_");
    }

    /** PHASE 2 — Mark Out (late overtime) + immediate pay feedback. */
    function dvWaMarkOut($DB, $wa, $fromNumber, $driver)
    {
        $res = dvDoMarkOut($driver['userID']);
        if (!empty($res['err'])) {
            $wa->sendText($fromNumber, "⚠️ " . $res['msg']);
            dvWaMainMenu($wa, $fromNumber, $driver);
            return;
        }

        $lines  = "✅ *Mark Out दर्ज हो गया*\n\n";
        $lines .= "समय / Time: *" . date('g:i A', strtotime($res['time'])) . "*\n";
        $lines .= date('D, d M Y', strtotime($res['time'])) . "\n\n";
        if ((float)$res['overtimeHrs'] > 0) {
            $lines .= "Overtime: *" . rtrim(rtrim(number_format($res['overtimeHrs'], 2), '0'), '.') . " घंटे / hrs*\n";
            $lines .= "Overtime pay: ₹" . intval($res['totalOvertimePay']) . "\n";
        }
        if (intval($res['dinnerAllowance']) > 0) $lines .= "Dinner: ₹" . intval($res['dinnerAllowance']) . "\n";
        if (intval($res['taxiAllowance'])   > 0) $lines .= "Taxi: ₹"   . intval($res['taxiAllowance'])   . "\n";
        if (intval($res['sunAllowance'])    > 0) $lines .= "Holiday/Off-day: ₹" . intval($res['sunAllowance']) . "\n";
        $lines .= "\n*कुल / Total: ₹" . intval($res['totalPay']) . "*\n\n";
        $lines .= "_Office verification के बाद payment होगा._\n_(Payable after office verification.)_";

        $wa->sendText($fromNumber, $lines);
    }

    /** PHASE 1 — read-only: this month. */
    function dvWaMonth($DB, $wa, $fromNumber, $driver)
    {
        $s = dvGetMonthSummary($driver['userID']);
        if (empty($s) || (int)$s['recs'] === 0) {
            $wa->sendText($fromNumber, "इस महीने कोई overtime दर्ज नहीं है।\n_(No overtime recorded this month.)_");
            dvWaInfoMenu($wa, $fromNumber);
            return;
        }
        $wa->sendText($fromNumber,
            "📊 *" . date('F Y') . "*\n\n"
            . "Overtime दिन / days: *" . (int)$s['recs'] . "*\n"
            . "कुल घंटे / hours: *" . rtrim(rtrim(number_format($s['hrs'], 2), '0'), '.') . "*\n\n"
            . "Overtime pay: ₹" . intval($s['otPay']) . "\n"
            . "Dinner: ₹" . intval($s['dinner']) . "\n"
            . "Taxi: ₹" . intval($s['taxi']) . "\n"
            . "Holiday/Off-day: ₹" . intval($s['sun']) . "\n\n"
            . "*कुल / Total: ₹" . intval($s['total']) . "*");
        dvWaInfoMenu($wa, $fromNumber);
    }

    /** PHASE 1 — read-only: unsettled amount. */
    function dvWaPending($DB, $wa, $fromNumber, $driver)
    {
        $o = dvGetOutstanding($driver['userID']);
        $amt = intval($o['amount'] ?? 0);
        if ($amt <= 0) {
            $wa->sendText($fromNumber, "✅ कोई बाकी राशि नहीं है — सब settle हो गया।\n_(Nothing pending — all settled.)_");
        } else {
            $wa->sendText($fromNumber,
                "💰 *बाकी राशि / Pending*\n\n"
                . "*₹" . $amt . "*\n"
                . (int)$o['recs'] . " entry settlement के लिए बाकी\n_(" . (int)$o['recs'] . " record(s) awaiting settlement)_\n\n"
                . "_यह office verification के बाद मिलेगा._");
        }
        dvWaInfoMenu($wa, $fromNumber);
    }


    /**
     * Driver state is namespaced away from HRMS employee IDs.
     * mx_wa_conversation_state is keyed (userID, fromNumber) and HRMS employees live in a
     * different table, so a raw driver userID could collide with an employee userID.
     */
    function dvStateKey($driverUserID) { return 900000 + intval($driverUserID); }

    /**
     * Step 1 of Mark In / Mark Out: validate the time window FIRST (so we never ask for a
     * location we cannot use), then request the pin.
     */
    function dvWaAskLocation($DB, $wa, $fromNumber, $driver, $which)
    {
        $chk = ($which === 'in') ? dvCanMarkIn($driver['userID']) : dvCanMarkOut($driver['userID']);
        if (empty($chk['allowed'])) {
            $wa->sendText($fromNumber, "⚠️ " . $chk['msg']);
            dvWaMainMenu($wa, $fromNumber, $driver);
            return;
        }

        setConversationState($DB, dvStateKey($driver['userID']), $fromNumber, 'dv_loc_' . $which, 1, array('which' => $which));

        $label = ($which === 'in') ? 'Mark In' : 'Mark Out';
        $wa->sendText($fromNumber,
            "📍 *{$label}* के लिए location भेजें\n_(Please share your location)_\n\n"
            . "1️⃣ नीचे 📎 (attach) दबाएँ\n"
            . "2️⃣ *Location* चुनें\n"
            . "3️⃣ *Send your current location* दबाएँ\n\n"
            . "_Location मिलने के बाद {$label} दर्ज होगा._\n"
            . "_Type *cancel* to stop._");
    }

    /** Step 2: pin received — complete the mark in/out and record the geofence result. */
    function dvWaLocationReceived($DB, $wa, $fromNumber, $driver, $lat, $lng)
    {
        $state = getConversationState($DB, dvStateKey($driver['userID']), $fromNumber);
        $flow  = $state['currentFlow'] ?? '';
        if ($flow !== 'dv_loc_in' && $flow !== 'dv_loc_out') {
            $wa->sendText($fromNumber, "Location मिली, लेकिन कोई Mark In/Out pending नहीं है.\n_(No pending action — pick an option below.)_");
            dvWaMainMenu($wa, $fromNumber, $driver);
            return;
        }
        $which = ($flow === 'dv_loc_in') ? 'in' : 'out';
        clearConversationState($DB, dvStateKey($driver['userID']));

        $res = ($which === 'in') ? dvDoMarkIn($driver['userID']) : dvDoMarkOut($driver['userID']);
        if (!empty($res['err'])) {
            $wa->sendText($fromNumber, "⚠️ " . $res['msg']);
            dvWaMainMenu($wa, $fromNumber, $driver);
            return;
        }

        $geo = dvSaveLocation($res['dmID'], $which, $lat, $lng);
        $farNote = "";
        if ($geo['inside'] === false) {
            $farNote = "\n\n⚠️ _आप गाड़ी की जगह से " . number_format($geo['distM'] / 1000, 1) . " km दूर हैं — verification के लिए भेजा गया._"
                     . "\n_(Recorded, but flagged: away from the vehicle location.)_";
        }

        if ($which === 'in') {
            $wa->sendText($fromNumber,
                "✅ *Mark In दर्ज हो गया*\n\n"
                . "समय / Time: *" . date('g:i A', strtotime($res['time'])) . "*\n"
                . date('D, d M Y', strtotime($res['time']))
                . $farNote
                . "\n\n_रात में जाते समय Mark Out करें._");
            dvNotifyOwner($DB, $wa, $driver, $res, $geo, 'in');
            return;
        }

        $lines  = "✅ *Mark Out दर्ज हो गया*\n\n";
        $lines .= "समय / Time: *" . date('g:i A', strtotime($res['time'])) . "*\n";
        $lines .= date('D, d M Y', strtotime($res['time'])) . "\n\n";
        if ((float)$res['overtimeHrs'] > 0) {
            $lines .= "Overtime: *" . rtrim(rtrim(number_format($res['overtimeHrs'], 2), '0'), '.') . " घंटे / hrs*\n";
            $lines .= "Overtime pay: ₹" . intval($res['totalOvertimePay']) . "\n";
        }
        if (intval($res['dinnerAllowance']) > 0) $lines .= "Dinner: ₹" . intval($res['dinnerAllowance']) . "\n";
        if (intval($res['taxiAllowance'])   > 0) $lines .= "Taxi: ₹"   . intval($res['taxiAllowance'])   . "\n";
        if (intval($res['sunAllowance'])    > 0) $lines .= "Holiday/Off-day: ₹" . intval($res['sunAllowance']) . "\n";
        $lines .= "\n*कुल / Total: ₹" . intval($res['totalPay']) . "*" . $farNote . "\n\n";
        $lines .= "_Office verification के बाद payment होगा._";
        $wa->sendText($fromNumber, $lines);

        dvNotifyOwner($DB, $wa, $driver, $res, $geo, 'out');
    }

    /**
     * Owner confirmation — the control that actually verifies WHEN work ended.
     * A shared WhatsApp pin can be dragged anywhere, so the geofence is only a deterrent
     * plus audit trail; the person who was being driven knows the real finish time.
     * Buttons flip isVerify on the record (handled in wa-handlers.inc.php).
     */
    function dvNotifyOwner($DB, $wa, $driver, $res, $geo, $which)
    {
        if (!defined('DRIVER_CONFIRM_NUMBER') || !DRIVER_CONFIRM_NUMBER) return;
        if ($which !== 'out') return;   // only the mark-out needs confirming

        $dist = ($geo['distM'] === null)
            ? "location not shared"
            : (($geo['inside'] ? "✅ at vehicle (" . $geo['distM'] . " m)" : "⚠️ " . number_format($geo['distM'] / 1000, 1) . " km away"));

        $body = "🚗 *" . $driver['userName'] . "* — Mark Out\n\n"
              . date('D, d M Y') . " at *" . date('g:i A', strtotime($res['time'])) . "*\n"
              . "Overtime: " . rtrim(rtrim(number_format($res['overtimeHrs'], 2), '0'), '.') . " hrs\n"
              . "Amount: *₹" . intval($res['totalPay']) . "*\n"
              . "Location: " . $dist . "\n\n"
              . "Confirm the finish time?";

        $wa->sendInteractiveButtons(DRIVER_CONFIRM_NUMBER, $body, array(
            array('id' => 'dvok_' . $res['dmID'], 'title' => 'Confirm'),
            array('id' => 'dvq_'  . $res['dmID'], 'title' => 'Query this'),
        ));
    }

    /** Router for driver messages. Mirrors the HRMS router's shape: greeting resets to the
     * menu, buttons drive the flow, and anything unrecognised falls back to the menu.
     */
    function routeDriverMessage($DB, $wa, $driver, $fromNumber, $messageBody, $buttonPayload, $messageType, $waLat = null, $waLng = null)
    {
        $text = strtolower(trim((string)$messageBody));

        // A shared pin completes a pending Mark In / Mark Out (see dvWaAskLocation).
        if ($messageType === 'location' && $waLat !== null && $waLng !== null) {
            dvWaLocationReceived($DB, $wa, $fromNumber, $driver, $waLat, $waLng);
            return;
        }

        if ($buttonPayload === 'dv_menu' || preg_match('/^(hi|hello|hey|menu|start|namaste|hola|नमस्ते)$/iu', $text)) {
            dvWaMainMenu($wa, $fromNumber, $driver);
            return;
        }

        switch ($buttonPayload) {
            case 'dv_markin':  dvWaAskLocation($DB, $wa, $fromNumber, $driver, 'in');  return;
            case 'dv_markout': dvWaAskLocation($DB, $wa, $fromNumber, $driver, 'out'); return;
            case 'dv_info':    dvWaInfoMenu($wa, $fromNumber);              return;
            case 'dv_month':   dvWaMonth($DB, $wa, $fromNumber, $driver);   return;
            case 'dv_pending': dvWaPending($DB, $wa, $fromNumber, $driver); return;
        }

        // Typed keywords, for drivers who type rather than tap.
        if (preg_match('/\b(mark ?in|markin|in)\b/i', $text))              { dvWaAskLocation($DB, $wa, $fromNumber, $driver, 'in');  return; }
        if (preg_match('/\b(mark ?out|markout|out)\b/i', $text))           { dvWaAskLocation($DB, $wa, $fromNumber, $driver, 'out'); return; }
        if (preg_match('/\b(pending|baki|बाकी|amount|paisa)\b/iu', $text)) { dvWaPending($DB, $wa, $fromNumber, $driver); return; }
        if (preg_match('/\b(month|mahina|महीना|overtime|ot)\b/iu', $text)) { dvWaMonth($DB, $wa, $fromNumber, $driver);   return; }

        dvWaMainMenu($wa, $fromNumber, $driver);
    }
}

if (!function_exists('handleDriverOvertimeConfirm')) {
    /**
     * Owner tapped Confirm / Query on a mark-out prompt.
     *   dvok_<id> -> isVerify = 1  (approved for settlement)
     *   dvq_<id>  -> left unverified, flagged so it stands out in the admin list
     */
    function handleDriverOvertimeConfirm($DB, $wa, $fromNumber, $buttonPayload)
    {
        $confirm = (strpos($buttonPayload, 'dvok_') === 0);
        $dmID    = intval(substr($buttonPayload, strpos($buttonPayload, '_') + 1));
        if (!$dmID) { $wa->sendText($fromNumber, "Could not read that record id."); return; }

        $DB->vals  = array(1, $dmID);
        $DB->types = "ii";
        $DB->sql   = "SELECT d.driverManagementID, d.dmDate, d.toTime, d.totalPay, d.overtimeHrs, u.userName
                      FROM `" . $DB->pre . "driver_management` d
                      LEFT JOIN `" . $DB->pre . "user` u ON u.userID = d.userID
                      WHERE d.status=? AND d.driverManagementID=?";
        $rec = $DB->dbRow();
        if ($DB->numRows <= 0) { $wa->sendText($fromNumber, "That overtime record no longer exists."); return; }

        $DB->table = $DB->pre . "driver_management";
        $DB->data  = $confirm ? array('isVerify' => 1) : array('isVerify' => 0, 'locFlagged' => 1);
        $DB->dbUpdate("driverManagementID=?", "i", array($dmID));

        $when = date('d M', strtotime($rec['dmDate'])) . " at " . date('g:i A', strtotime($rec['toTime']));
        if ($confirm) {
            $wa->sendText($fromNumber,
                "✅ Confirmed — *" . $rec['userName'] . "*, " . $when . ", ₹" . intval($rec['totalPay'])
                . "\n\nVerified and ready for settlement.");
        } else {
            $wa->sendText($fromNumber,
                "🔎 Marked for review — *" . $rec['userName'] . "*, " . $when . ", ₹" . intval($rec['totalPay'])
                . "\n\nLeft unverified and flagged. Adjust it in Driver Management when you have the details.");
        }
    }
}
