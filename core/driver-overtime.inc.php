<?php
/**
 * Driver overtime engine — shared, side-effect-free.
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * The pay logic used to live only inside the xadmin driver-management controller and read
 * straight from $_POST plus a set of globals. Because it could not be called as a function,
 * the driver portal's markOut() and the autoMarkout cron POSTed to that admin endpoint over
 * cURL (an HTTP request from the server to itself). That indirection caused three problems:
 *
 *   1. the admin endpoint had to run with authentication DISABLED so the internal calls
 *      would work, leaving ADD/UPDATE/verifyMarkin/settlePayment publicly callable;
 *   2. a mark-out depended on the site being reachable from itself — the same failure mode
 *      that silently broke the CAMS biometric callback;
 *   3. the update wrote `$DB->data = $_POST`, so any column absent from the request was
 *      blanked — which silently erased supportingDoc on records that had one.
 *
 * Everything here is pure: no $_POST, no $_SESSION, no HTTP. dvCalculateOvertime() is the
 * money function and is deliberately DB-free so it can be regression-tested directly.
 *
 * BUSINESS RULES ARE UNCHANGED by the extraction. Rates, thresholds and the flat off-day
 * amounts all still come from mx_user per driver.
 */

if (!function_exists('dvGetDriverRates')) {

    /**
     * Per-driver shift + rate configuration from mx_user.
     * Returns null when the driver does not exist / is inactive.
     */
    function dvGetDriverRates($userID = 0)
    {
        global $DB;
        $userID = intval($userID);
        if (!$userID) return null;

        $DB->vals  = array(1, $userID);
        $DB->types = "ii";
        $DB->sql   = "SELECT userFromTime, userToTime, workingHrs, overtimeAllowance,
                             dinnerTime, dinnerAllowance, taxiAllowanceTime, taxiAllowance,
                             offDayPriceBelow4Hr, offDayPriceAbove4Hr
                      FROM `" . $DB->pre . "user` WHERE status=? AND userID=?";
        $r = $DB->dbRow();
        if ($DB->numRows <= 0) return null;

        return array(
            'shiftStart'   => $r['userFromTime'],
            'shiftEnd'     => $r['userToTime'],
            'workingHrs'   => (float)$r['workingHrs'],
            'otRate'       => (float)$r['overtimeAllowance'],
            'dinnerTime'   => $r['dinnerTime'],
            'dinnerAllow'  => (float)$r['dinnerAllowance'],
            'taxiTime'     => $r['taxiAllowanceTime'],
            'taxiAllow'    => (float)$r['taxiAllowance'],
            'offDayUpto4'  => (float)$r['offDayPriceBelow4Hr'],
            'offDayOver4'  => (float)$r['offDayPriceAbove4Hr'],
        );
    }

    /**
     * Is $date one of this driver's weekly off days? (mx_user_off_days holds weekday numbers.)
     * Takes userID explicitly — the old isWeekend() read $_POST['userID'], which was both
     * caller-dependent and attacker-influenced while the endpoint was unauthenticated.
     */
    function dvIsOffDay($userID = 0, $date = "")
    {
        global $DB;
        $userID = intval($userID);
        if (!$userID || $date == "") return false;

        $DB->vals  = array(1, $userID, (int)date('N', strtotime($date)));
        $DB->types = "iii";
        $DB->sql   = "SELECT userOffDayID FROM `" . $DB->pre . "user_off_days`
                      WHERE status=? AND userID=? AND weekdayNo=? LIMIT 1";
        $DB->dbRow();
        return ($DB->numRows > 0);
    }

    /**
     * THE MONEY FUNCTION. Pure — no DB, no superglobals.
     *
     * @param array $job   fromTime, toTime (both 'Y-m-d H:i:s'), dmDate, expenseAmt
     * @param array $rates from dvGetDriverRates()
     * @param bool  $isOffDay
     * @return array overtimeHrs, totalOvertimePay, dinnerAllowance, taxiAllowance,
     *               sunAllowance, expenseAmt, totalPay  (raw floats — callers round/int)
     */
    function dvCalculateOvertime($job, $rates, $isOffDay = false)
    {
        $out = array(
            "overtimeHrs" => 0, "expenseAmt" => 0.00, "totalOvertimePay" => 0.00,
            "totalPay" => 0.00, "dinnerAllowance" => 0.00, "taxiAllowance" => 0.00,
            "sunAllowance" => 0.00,
        );
        if (empty($job['fromTime']) || empty($job['toTime']) || empty($rates)) return $out;

        $tsFrom = strtotime($job['fromTime']);
        $tsTo   = strtotime($job['toTime']);
        if ($tsFrom === false || $tsTo === false) return $out;

        $arrivalDate  = date('Y-m-d', $tsFrom);
        $out["expenseAmt"] = isset($job['expenseAmt']) ? (float)$job['expenseAmt'] : 0.00;

        // Thresholds are anchored to the ARRIVAL date so a shift running past midnight
        // still compares against the same day's dinner / taxi / shift-end times.
        $tsShiftEnd = strtotime($arrivalDate . ' ' . $rates['shiftEnd']);
        $tsDinner   = strtotime($arrivalDate . ' ' . $rates['dinnerTime']);

        // A taxi cut-off stored as "00:00:00" means midnight of the FOLLOWING day.
        $taxiDate = $arrivalDate;
        if ($rates['taxiTime'] === "00:00:00") {
            $taxiDate = date("Y-m-d", strtotime($arrivalDate . " +1 day"));
        }
        $tsTaxi = strtotime($taxiDate . ' ' . $rates['taxiTime']);

        if (!$isOffDay) {
            // NORMAL WORKING DAY: overtime = time before shift start + time after shift end.
            // (Was `totalWorked - workingHrs`, which returned NEGATIVE — i.e. paid nothing —
            //  for a record whose fromTime is the shift END, exactly what markOut() creates
            //  for late-overtime-only entries.)
            $tsShiftStart = $tsShiftEnd - ($rates['workingHrs'] * 3600);
            $earlyHrs = max(0, ($tsShiftStart - $tsFrom) / 3600);
            $lateHrs  = max(0, ($tsTo - $tsShiftEnd) / 3600);

            $out["overtimeHrs"] = round($earlyHrs + $lateHrs, 2);
            if ($out["overtimeHrs"] > 0) {
                $out["totalOvertimePay"] = $rates['otRate'] * $out["overtimeHrs"];
            }
        } else {
            // WEEKLY OFF DAY: flat day rate by hours worked, plus overtime past shift end.
            $workedHrs = ($tsTo - $tsFrom) / 3600;
            if ($workedHrs > 0) {
                $out["sunAllowance"] = ($workedHrs <= 4) ? $rates['offDayUpto4'] : $rates['offDayOver4'];
            }
            if ($tsTo >= $tsShiftEnd) {
                // Count from whichever is later: shift end, or actual arrival. (Counting
                // always from shift end over-paid a stint that began after it — 22:00->23:00
                // was billed as three hours.)
                $otStart = max($tsFrom, $tsShiftEnd);
                if ($tsTo > $otStart) {
                    $out["overtimeHrs"]      = round(($tsTo - $otStart) / 3600, 2);
                    $out["totalOvertimePay"] = $rates['otRate'] * $out["overtimeHrs"];
                }
            }
        }

        // Dinner / taxi apply on both normal and off days.
        if ($tsTo >= $tsDinner) $out["dinnerAllowance"] = $rates['dinnerAllow'];
        if ($tsTo >= $tsTaxi)   $out["taxiAllowance"]   = $rates['taxiAllow'];

        $out["totalPay"] = $out["expenseAmt"] + $out["totalOvertimePay"]
                         + $out["dinnerAllowance"] + $out["taxiAllowance"] + $out["sunAllowance"];
        return $out;
    }

    /**
     * Calculate and persist. Writes an EXPLICIT column list — never `$DB->data = $_POST` —
     * so fields the caller does not own (supportingDoc, isVerify, isSettled, recordType,
     * userID, dmDate) are left exactly as they were.
     *
     * @param int   $driverManagementID
     * @param array $job fromTime, toTime, expenseAmt (optional; falls back to the stored row)
     * @return array err/msg + the computed figures
     */
    function dvSaveOvertime($driverManagementID = 0, $job = array())
    {
        global $DB;
        $driverManagementID = intval($driverManagementID);
        if (!$driverManagementID) return array("err" => 1, "msg" => "Missing record id.");

        // The row is the source of truth for identity; the caller only supplies times.
        $DB->vals  = array(1, $driverManagementID);
        $DB->types = "ii";
        $DB->sql   = "SELECT driverManagementID, userID, dmDate, fromTime, toTime, expenseAmt
                      FROM `" . $DB->pre . "driver_management`
                      WHERE status=? AND driverManagementID=?";
        $row = $DB->dbRow();
        if ($DB->numRows <= 0) return array("err" => 1, "msg" => "Record not found.");

        $userID   = intval($row['userID']);
        $fromTime = !empty($job['fromTime']) ? $job['fromTime'] : $row['fromTime'];
        $toTime   = !empty($job['toTime'])   ? $job['toTime']   : $row['toTime'];
        $expense  = isset($job['expenseAmt']) && $job['expenseAmt'] !== "" ? (float)$job['expenseAmt'] : (float)$row['expenseAmt'];
        $dmDate   = !empty($job['dmDate']) ? $job['dmDate'] : $row['dmDate'];

        if (empty($toTime)) return array("err" => 1, "msg" => "Mark-out time missing.");
        if (strtotime($toTime) < strtotime($fromTime)) {
            return array("err" => 1, "msg" => "Mark-out time is before mark-in time.");
        }

        $rates = dvGetDriverRates($userID);
        if (!$rates) return array("err" => 1, "msg" => "Driver rate configuration not found.");

        $calc = dvCalculateOvertime(
            array('fromTime' => $fromTime, 'toTime' => $toTime, 'dmDate' => $dmDate, 'expenseAmt' => $expense),
            $rates,
            dvIsOffDay($userID, $dmDate)
        );

        // Money columns are stored as integers, matching the previous intval() behaviour.
        $DB->table = $DB->pre . "driver_management";
        $DB->data  = array(
            "fromTime"         => $fromTime,
            "toTime"           => $toTime,
            "expenseAmt"       => $expense,
            "overtimeHrs"      => $calc["overtimeHrs"],
            "totalOvertimePay" => intval($calc["totalOvertimePay"]),
            "dinnerAllowance"  => intval($calc["dinnerAllowance"]),
            "taxiAllowance"    => intval($calc["taxiAllowance"]),
            "sunAllowance"     => intval($calc["sunAllowance"]),
            "totalPay"         => intval($calc["totalPay"]),
        );
        if (!$DB->dbUpdate("driverManagementID=?", "i", array($driverManagementID))) {
            return array("err" => 1, "msg" => "Failed to save overtime record.");
        }

        dvLogOvertime($driverManagementID, $userID, $fromTime, $toTime, $calc);
        return array_merge(array("err" => 0, "msg" => "Overtime saved."), $calc);
    }

    /**
     * Append-only audit trail of every pay computation — useful if a driver ever queries a
     * payment. Best-effort: a logging failure must never block the save.
     */
    function dvLogOvertime($dmID, $userID, $fromTime, $toTime, $calc)
    {
        $dir = defined('ROOTPATH') ? ROOTPATH . '/logs' : dirname(__DIR__) . '/logs';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents(
            $dir . '/driver-overtime-' . date('Y-m') . '.log',
            sprintf("[%s] dmID=%d uid=%d %s -> %s | OT %.2fh Rs%d | dinner %d taxi %d sun %d | TOTAL Rs%d\n",
                date('Y-m-d H:i:s'), $dmID, $userID, $fromTime, $toTime,
                $calc['overtimeHrs'], intval($calc['totalOvertimePay']),
                intval($calc['dinnerAllowance']), intval($calc['taxiAllowance']),
                intval($calc['sunAllowance']), intval($calc['totalPay'])),
            FILE_APPEND
        );
    }
}

/* ===========================================================================
 * OVERTIME MARK IN / MARK OUT — shared decision + action layer.
 *
 * Used by BOTH the driver portal and the WhatsApp bot so the rules exist once.
 * These record OVERTIME ONLY — they never touch attendance, and the nightly
 * autoMarkin() baseline row (which represents the normal shift) is unaffected.
 *
 *   Mark In  = EARLY overtime  (arrived before shift start)
 *   Mark Out = LATE  overtime  (worked past shift end)
 * ======================================================================== */

if (!function_exists('dvGetDriverByPhone')) {

    /** Find an active driver by mobile number (handles +91 / 91 / bare 10-digit). */
    function dvGetDriverByPhone($phone = "")
    {
        global $DB;
        $p = preg_replace('/[^0-9]/', '', (string)$phone);
        if ($p === "") return null;
        $bare = (strlen($p) > 10 && substr($p, 0, 2) === '91') ? substr($p, 2) : $p;

        $DB->vals  = array(1, $p, $bare);
        $DB->types = "iss";
        $DB->sql   = "SELECT userID, userName, userMobileNo, userFromTime, userToTime
                      FROM `" . $DB->pre . "user`
                      WHERE status=? AND (userMobileNo=? OR userMobileNo=?) LIMIT 1";
        $r = $DB->dbRow();
        return ($DB->numRows > 0) ? $r : null;
    }

    /**
     * Time windows for a driver right now. Mirrors the portal's logic exactly:
     * before 6 AM still belongs to the PREVIOUS day's late-overtime window.
     */
    function dvGetWindows($userID, $now = null)
    {
        global $DB;
        $now   = $now ?: time();
        $rates = dvGetDriverRates($userID);
        if (!$rates) return null;

        $shiftStart = substr($rates['shiftStart'], 0, 5);
        $shiftEnd   = substr($rates['shiftEnd'], 0, 5);
        $hour       = (int)date('H', $now);
        $hhmm       = date('H:i', $now);
        $relevantDate = ($hour < 6) ? date('Y-m-d', strtotime('-1 day', $now)) : date('Y-m-d', $now);

        return array(
            'shiftStart'   => $shiftStart,
            'shiftEnd'     => $shiftEnd,
            'relevantDate' => $relevantDate,
            'isOffDay'     => dvIsOffDay($userID, $relevantDate),
            'isEarly'      => ($hour >= 6 && $hhmm < $shiftStart),
            'isLate'       => ($hhmm >= $shiftEnd || $hour < 6),
            'beforeSix'    => ($hour < 6),
        );
    }

    /** The open (un-marked-out) record for the relevant date, if any. */
    function dvGetOpenRecord($userID, $relevantDate)
    {
        global $DB;
        $DB->vals  = array(1, $userID, $relevantDate);
        $DB->types = "iis";
        $DB->sql   = "SELECT driverManagementID, dmDate, fromTime, toTime, recordType, expenseAmt
                      FROM `" . $DB->pre . "driver_management`
                      WHERE status=? AND userID=? AND dmDate=?
                        AND (toTime IS NULL OR toTime='' OR toTime='0000-00-00 00:00:00' OR toTime<=fromTime)
                      ORDER BY driverManagementID DESC LIMIT 1";
        $r = $DB->dbRow();
        return ($DB->numRows > 0) ? $r : null;
    }

    /** Any record (open or closed) for this driver+date — used to prevent duplicate rows. */
    function dvGetRecordForDate($userID, $date)
    {
        global $DB;
        $DB->vals  = array(1, $userID, $date);
        $DB->types = "iis";
        $DB->sql   = "SELECT driverManagementID, dmDate, fromTime, toTime, recordType, expenseAmt
                      FROM `" . $DB->pre . "driver_management`
                      WHERE status=? AND userID=? AND dmDate=?
                      ORDER BY driverManagementID DESC LIMIT 1";
        $r = $DB->dbRow();
        return ($DB->numRows > 0) ? $r : null;
    }

    /** May this driver mark IN right now? */
    function dvCanMarkIn($userID, $now = null)
    {
        global $DB;
        $now = $now ?: time();
        $w   = dvGetWindows($userID, $now);
        if (!$w) return array('allowed' => false, 'msg' => "Driver settings not found.");

        if ($w['beforeSix']) {
            return array('allowed' => false, 'msg' => "Mark In is not available before 6 AM. Please mark out yesterday's overtime first.");
        }
        // Early-overtime only on a working day; an off day is overtime all day.
        if (!$w['isOffDay'] && !$w['isEarly']) {
            return array('allowed' => false, 'msg' => "Mark In is only for EARLY overtime — before " . date('g:i A', strtotime($w['shiftStart'])) . ".");
        }

        $today = date('Y-m-d', $now);
        $DB->vals  = array(1, $userID, $today);
        $DB->types = "iis";
        $DB->sql   = "SELECT driverManagementID, recordType, toTime FROM `" . $DB->pre . "driver_management`
                      WHERE status=? AND userID=? AND dmDate=? ORDER BY driverManagementID DESC LIMIT 1";
        $rec = $DB->dbRow();
        if ($DB->numRows > 0) {
            $closed = !empty($rec['toTime']) && $rec['toTime'] !== '0000-00-00 00:00:00';
            if ((int)$rec['recordType'] === 2)  return array('allowed' => false, 'msg' => "You have already marked in today.");
            if ($closed)                        return array('allowed' => false, 'msg' => "Today's overtime is already completed.");
            // A cron baseline row exists — mark-in will update it rather than duplicate.
            return array('allowed' => true, 'msg' => '', 'dmID' => (int)$rec['driverManagementID']);
        }
        return array('allowed' => true, 'msg' => '', 'dmID' => 0);
    }

    /** May this driver mark OUT right now? */
    function dvCanMarkOut($userID, $now = null)
    {
        $now = $now ?: time();
        $w   = dvGetWindows($userID, $now);
        if (!$w) return array('allowed' => false, 'msg' => "Driver settings not found.");

        // Late-overtime only on a working day (this is the guard that stops a mid-shift mark-out).
        if (!$w['isOffDay'] && !$w['isLate']) {
            return array('allowed' => false, 'msg' => "Mark Out is only for LATE overtime — available after " . date('g:i A', strtotime($w['shiftEnd'])) . ".");
        }

        $open = dvGetOpenRecord($userID, $w['relevantDate']);
        if ($open) {
            $mins = (strtotime(date('Y-m-d H:i:s', $now)) - strtotime($open['fromTime'])) / 60;
            if ($mins < 30) {
                return array('allowed' => false, 'msg' => "Please wait at least 30 minutes after Mark In. (" . ceil(30 - $mins) . " min remaining)");
            }
            return array('allowed' => true, 'msg' => '', 'dmID' => (int)$open['driverManagementID'], 'relevantDate' => $w['relevantDate']);
        }

        /**
         * No OPEN record — but a CLOSED one may exist for this date, and we must never
         * insert a second row for the same driver+date.
         *
         * autoMarkout() runs hourly and closes the previous day's open record at shift end.
         * A driver working past midnight can still legitimately mark out for that date
         * (the late window includes the hours before 6 AM), so the cron may have closed the
         * record minutes earlier. An auto-markout is recognisable because toTime lands
         * exactly on shift end — that one may be OVERRIDDEN with the real time. A genuine
         * driver mark-out must not be.
         */
        $rec = dvGetRecordForDate($userID, $w['relevantDate']);
        if ($rec) {
            $isAutoMarkout = (!empty($rec['toTime']) && date('H:i', strtotime($rec['toTime'])) === $w['shiftEnd']);
            if ($isAutoMarkout) {
                return array('allowed' => true, 'msg' => '', 'dmID' => (int)$rec['driverManagementID'],
                             'relevantDate' => $w['relevantDate'], 'override' => true);
            }
            return array('allowed' => false, 'msg' => "You have already marked out for " . date('d M', strtotime($w['relevantDate'])) . ".");
        }

        // Genuinely no record for the date: a fresh late-overtime entry starting at shift end.
        return array('allowed' => true, 'msg' => '', 'dmID' => 0, 'relevantDate' => $w['relevantDate']);
    }

    /** Record EARLY overtime start. */
    function dvDoMarkIn($userID, $now = null)
    {
        global $DB;
        $now = $now ?: time();
        $chk = dvCanMarkIn($userID, $now);
        if (empty($chk['allowed'])) return array('err' => 1, 'msg' => $chk['msg']);

        $stamp = date('Y-m-d H:i:s', $now);
        $DB->table = $DB->pre . "driver_management";
        if (!empty($chk['dmID'])) {
            $DB->data = array("fromTime" => $stamp, "recordType" => 2, "isVerify" => 0);
            $ok = $DB->dbUpdate("driverManagementID=?", "i", array($chk['dmID']));
            $dmID = $chk['dmID'];
        } else {
            $DB->data = array("dmDate" => date('Y-m-d', $now), "fromTime" => $stamp,
                              "recordType" => 2, "isVerify" => 0, "userID" => $userID);
            $ok   = $DB->dbInsert();
            $dmID = $DB->insertID;
        }
        if (!$ok) return array('err' => 1, 'msg' => "Could not record Mark In. Please try again.");
        return array('err' => 0, 'msg' => "Mark In recorded", 'dmID' => $dmID, 'time' => $stamp);
    }

    /** Record LATE overtime end and compute pay (via dvSaveOvertime). */
    function dvDoMarkOut($userID, $now = null)
    {
        global $DB;
        $now = $now ?: time();
        $chk = dvCanMarkOut($userID, $now);
        if (empty($chk['allowed'])) return array('err' => 1, 'msg' => $chk['msg']);

        $stamp  = date('Y-m-d H:i:s', $now);
        $rates  = dvGetDriverRates($userID);
        $dmDate = $chk['relevantDate'];
        $dmID   = (int)$chk['dmID'];

        if (!$dmID) {
            // Late overtime with no prior record: overtime starts at shift end.
            $DB->table = $DB->pre . "driver_management";
            $DB->data  = array("dmDate" => $dmDate,
                               "fromTime" => $dmDate . " " . substr($rates['shiftEnd'], 0, 5) . ":00",
                               "recordType" => 1, "isVerify" => 0, "userID" => $userID);
            if (!$DB->dbInsert()) return array('err' => 1, 'msg' => "Could not create overtime record.");
            $dmID = $DB->insertID;
        }
        $res = dvSaveOvertime($dmID, array("toTime" => $stamp));
        if (!empty($res['err'])) return array('err' => 1, 'msg' => $res['msg'] ?? "Could not save overtime.");
        return array_merge(array('err' => 0, 'msg' => "Mark Out recorded", 'dmID' => $dmID, 'time' => $stamp), $res);
    }

    /** Read-only: this month's overtime summary. */
    function dvGetMonthSummary($userID, $ym = null)
    {
        global $DB;
        $ym = $ym ?: date('Y-m');
        $DB->vals  = array(1, $userID, $ym);
        $DB->types = "iis";
        $DB->sql   = "SELECT COUNT(*) recs, COALESCE(SUM(overtimeHrs),0) hrs,
                             COALESCE(SUM(totalOvertimePay),0) otPay,
                             COALESCE(SUM(dinnerAllowance),0) dinner,
                             COALESCE(SUM(taxiAllowance),0) taxi,
                             COALESCE(SUM(sunAllowance),0) sun,
                             COALESCE(SUM(totalPay),0) total
                      FROM `" . $DB->pre . "driver_management`
                      WHERE status=? AND userID=? AND DATE_FORMAT(dmDate,'%Y-%m')=? AND totalPay>0";
        return $DB->dbRow() ?: array();
    }

    /** Read-only: unsettled amount — same rule the admin settle screen uses. */
    function dvGetOutstanding($userID)
    {
        global $DB;
        $DB->vals  = array(1, $userID);
        $DB->types = "ii";
        $DB->sql   = "SELECT COUNT(*) recs, COALESCE(SUM(totalPay),0) amount
                      FROM `" . $DB->pre . "driver_management`
                      WHERE status=? AND userID=? AND isSettled=0 AND totalPay>0";
        return $DB->dbRow() ?: array('recs' => 0, 'amount' => 0);
    }
}

/* ===========================================================================
 * GEOFENCE — the driver does not take the vehicle home, so his shift ends where
 * the vehicle is parked. A mark-in/out far from that point is recorded but FLAGGED
 * for verification. Deliberately not a hard block: GPS drift in dense South Mumbai
 * is normal, and a legitimate late mark-out should never be refused outright.
 *
 * NOTE ON TRUST: WhatsApp's "share location" lets the sender drag the pin anywhere,
 * so this is a deterrent plus an audit trail — not proof. The owner confirmation
 * prompt is the control that actually verifies WHEN the work ended.
 * ======================================================================== */

if (!function_exists('dvDistanceMeters')) {

    /** Haversine distance in metres. */
    function dvDistanceMeters($lat1, $lng1, $lat2, $lng2)
    {
        $R = 6371000.0;
        $p1 = deg2rad((float)$lat1); $p2 = deg2rad((float)$lat2);
        $dp = $p2 - $p1;
        $dl = deg2rad((float)$lng2 - (float)$lng1);
        $a = sin($dp / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;
        return (int)round($R * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    /** Distance from the vehicle parking point + whether it is inside the radius. */
    function dvCheckGeofence($lat, $lng)
    {
        if (!defined('DRIVER_PARK_LAT') || $lat === null || $lng === null || $lat === '' || $lng === '') {
            return array('distM' => null, 'inside' => null);
        }
        $d = dvDistanceMeters($lat, $lng, DRIVER_PARK_LAT, DRIVER_PARK_LNG);
        return array('distM' => $d, 'inside' => ($d <= DRIVER_PARK_RADIUS_M));
    }

    /** Persist a shared location against a record. $which = 'in' | 'out'. */
    function dvSaveLocation($driverManagementID, $which, $lat, $lng)
    {
        global $DB;
        $driverManagementID = intval($driverManagementID);
        if (!$driverManagementID) return array('distM' => null, 'inside' => null);

        $geo = dvCheckGeofence($lat, $lng);
        $col = ($which === 'in')
            ? array('markInLat', 'markInLng', 'markInDistM')
            : array('markOutLat', 'markOutLng', 'markOutDistM');

        $DB->table = $DB->pre . "driver_management";
        $DB->data  = array(
            $col[0] => $lat,
            $col[1] => $lng,
            $col[2] => $geo['distM'],
        );
        if ($geo['inside'] === false) $DB->data['locFlagged'] = 1;
        $DB->dbUpdate("driverManagementID=?", "i", array($driverManagementID));
        return $geo;
    }

    /** Google Maps link for a coordinate — used in the admin screen and owner prompt. */
    function dvMapLink($lat, $lng)
    {
        return "https://maps.google.com/?q=" . urlencode($lat . "," . $lng);
    }
}
