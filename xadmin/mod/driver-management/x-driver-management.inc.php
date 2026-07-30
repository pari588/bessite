<?php
//error_reporting(E_ALL);
/*

addDriverMangement = To save driver management data.
updateDriverMangement = To update driver management data.
addUpdateMoterDetail = To add and update driver management's detail data.
*/

/*====== START: DEFINE DEFAULT VALUES.======*/

//Define from time to time.
$frmTime = new DateTime("10:00:00");
$toTime = new DateTime("20:00:00");
$FROMTIME = $frmTime->format('H:i:s'); // Added in user table
$TOTIME = $toTime->format('H:i:s'); // Added in user table
//End.

//After Overtime after 8 pm is Rs 75/hr
$OVERTIMEALLOW = "75"; // Added in user table

// After 10 pm add a fixed dinner cost of Rs.150
$dinerTime = new DateTime("22:00:00");
$DATIME = $dinerTime->format('H:i:s'); // Added in user table
$DINNERALLOW = "150"; // Added in user table

// After 12 pm Fixed Taxi Cost of Rs.100
$taxiTime = new DateTime("23:59:00");
$TAXIALLOTIME = $taxiTime->format('H:i:s'); // Added in user table
$TAXIALLOW = "100"; // Added in user table

// Sundays: Rs. 450 for 4 hours and Rs. 600 above 4 hours.
$SUNFOURHRSALLOW = "450";
$ABVFOURHRSALLOW = "600"; // Added in user table


function isWeekend($date)
{
    global $DB;
    $dmDate = date('N', strtotime($date));
    $arrWhere = array("sql" => "status = ? AND userID=?", "types" => "ii", "vals" => array(1, $_POST['userID']));
    $params = ["table" => $DB->pre . "user_off_days", "key" => "userOffDayID", "val" => "weekdayNo", "where" => $arrWhere, "lang" => false];
    $arrWeekdayNo  = getDataArray($params);
    if (in_array($dmDate, $arrWeekdayNo['data'])) {
        return true;
    } else {
        return false;
    }
}
$WORKINGHRS = 10;

function getUserSetting($userID = 0)
{
    global $DB, $TOTIME, $OVERTIMEALLOW, $DATIME, $TAXIALLOTIME, $DINNERALLOW, $TAXIALLOW, $SUNFOURHRSALLOW, $ABVFOURHRSALLOW, $WORKINGHRS;
    $DB->vals = array(1, $userID);
    $DB->types = "ii";
    $DB->sql = "SELECT offDayPriceBelow4Hr,offDayPriceAbove4Hr,userID,overtimeAllowance,userFromTime,userToTime,dinnerTime,dinnerAllowance,taxiAllowanceTime,taxiAllowance,workingHrs FROM " . $DB->pre . "user WHERE status=? AND userID=?";
    $driverData = $DB->dbRow();
    $FROMTIME = $driverData['userFromTime'];
    $TOTIME = $driverData['userToTime'];
    $OVERTIMEALLOW = $driverData['overtimeAllowance'];
    $DATIME = $driverData['dinnerTime'];
    $DINNERALLOW = $driverData['dinnerAllowance'];
    $TAXIALLOTIME = $driverData['taxiAllowanceTime'];
    $TAXIALLOW = $driverData['taxiAllowance'];
    $SUNFOURHRSALLOW = $driverData['offDayPriceBelow4Hr'];
    $ABVFOURHRSALLOW = $driverData['offDayPriceAbove4Hr'];
    $WORKINGHRS = $driverData['workingHrs'];
}

/*====== END: DEFINE DEFAULT VALUES.======*/

//Start: To save driver management data.
/**
 * Reject a negative expense amount.
 *
 * `expenseAmt` is a REIMBURSEMENT field — money the driver spent that the company pays
 * back, so it adds to totalPay. Five 2025 records used it in reverse, as a deduction, to
 * note company cash the driver was holding ("balance with Dilkhush"). That drove totalPay
 * negative, and because the list page disables the settle checkbox whenever
 * totalPay <= 0, those rows could never be settled — they sat open indefinitely while
 * being invisible in the outstanding figure (which only counts totalPay > 0).
 *
 * Deductions need their own mechanism, not this field.
 */
function validateExpenseAmt()
{
    if (isset($_POST["expenseAmt"]) && $_POST["expenseAmt"] !== "" && (float)$_POST["expenseAmt"] < 0) {
        $msg = "Amount cannot be negative. This field is for expenses reimbursed TO the driver. "
             . "A negative value makes the record impossible to settle. Record money held by the driver separately.";
        setResponse(array("err" => 1, "msg" => $msg, "alert" => $msg));
        return false;
    }
    return true;
}

function addDriverMangement()
{
    global $DB;
    if (!validateExpenseAmt()) return;
    if (isset($_POST["dmDate"]))
        $_POST["dmDate"] = $_POST["dmDate"];
    if (isset($_POST["fromTime"]))
        $_POST["fromTime"] = $_POST["fromTime"];
    if (isset($_POST["toTime"]))
        $_POST["toTime"] = $_POST["toTime"];
    if (isset($_POST["otherExpense"]))
        $_POST["otherExpense"] = $_POST["otherExpense"];
    if (isset($_POST["expenseAmt"]))
        $_POST["expenseAmt"] = $_POST["expenseAmt"];
    $_POST["supportingDoc"] = mxGetFileName("supportingDoc");
    getUserSetting($_POST['userID'] ?? 0);
    $result = overtimeManagement();

    if (sizeof($result) > 0) {
        $_POST["overtimeHrs"] = $result["overtimeHrs"];
        $_POST["totalOvertimePay"] = intval($result["totalOvertimePay"]);
        $_POST["dinnerAllowance"] = intval($result["dinnerAllowance"]);
        $_POST["taxiAllowance"] = intval($result["taxiAllowance"]);
        $_POST["sunAllowance"] = intval($result["sunAllowance"]);
        $_POST["totalPay"] = intval($result["totalPay"]);
    }

    $_POST['isVerify'] = 1;

    $DB->table = $DB->pre . "driver_management";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $driverManagementID = $DB->insertID;
        setResponse(array("err" => 0, "param" => "id=$driverManagementID"));
    } else {
        setResponse(array("err" => 1));
    }
}
//End.
//Start: To update driver management data.
/**
 * Thin wrapper over the shared engine in core/driver-overtime.inc.php.
 *
 * Previously this did `$DB->data = $_POST; dbUpdate(...)`, so any column missing from the
 * request was blanked — a driver mark-out (which posts only the times) silently wiped
 * supportingDoc on records that had a document attached. dvSaveOvertime() writes an explicit
 * column list instead, so untouched fields stay untouched.
 *
 * Admin-form extras that the engine does not own (supportingDoc upload, otherExpense) are
 * still handled here, and only when they were actually submitted.
 */
function  updateDriverMangement()
{
    global $DB;
    if (!validateExpenseAmt()) return;
    $driverManagementID = intval($_POST["driverManagementID"] ?? 0);

    $res = dvSaveOvertime($driverManagementID, array(
        "fromTime"   => $_POST["fromTime"]   ?? "",
        "toTime"     => $_POST["toTime"]     ?? "",
        "dmDate"     => $_POST["dmDate"]     ?? "",
        "expenseAmt" => $_POST["expenseAmt"] ?? "",
    ));

    if (!empty($res["err"])) {
        setResponse(array("err" => 1, "msg" => $res["msg"] ?? "Update failed", "alert" => $res["msg"] ?? ""));
        return;
    }

    // Admin-form-only fields, applied separately so the driver/cron paths never clear them.
    $extra = array();
    $doc = mxGetFileName("supportingDoc");
    if (!empty($doc))                        $extra["supportingDoc"] = $doc;
    if (isset($_POST["otherExpense"]))       $extra["otherExpense"]  = $_POST["otherExpense"];
    if (!empty($extra)) {
        $DB->table = $DB->pre . "driver_management";
        $DB->data  = $extra;
        $DB->dbUpdate("driverManagementID=?", "i", array($driverManagementID));
    }

    setResponse(array("err" => 0, "param" => "id=$driverManagementID"));
}
// End.
// Start: Driver Overtime salary management.
function overtimeManagement()
{
    global $TOTIME, $OVERTIMEALLOW, $DATIME, $TAXIALLOTIME, $DINNERALLOW, $TAXIALLOW, $SUNFOURHRSALLOW, $ABVFOURHRSALLOW, $WORKINGHRS;
    $data = array("overtimeHrs" => 0, "expenseAmt" => 0.00, "totalOvertimePay" => 0.00, "totalPay" => 0.00, "dinnerAllowance" => 0.00, "taxiAllowance" => 0.00, "sunAllowance" => 0.00);

    $toTime = new DateTime($_POST["toTime"]);
    $outgoingTime = $toTime->format('H:i:s');
    $outgoingDate = $toTime->format('Y-m-d');

    $fromTime = new DateTime($_POST["fromTime"]);
    $arrivalTime = $fromTime->format('H:i:s');
    $arrivalDate = $fromTime->format('Y-m-d');

    $taxiAllowanceDate = $arrivalDate;
    if ($TAXIALLOTIME === "00:00:00") {
        $taxiAllowanceDate = date("Y-m-d", strtotime($arrivalDate . " +1 day"));
    }


    $data["expenseAmt"] = $_POST["expenseAmt"];
    $isOffDay = isWeekend($_POST["dmDate"]);

    if (!$isOffDay) {
        // NORMAL WORKING DAY
        // Overtime = time worked BEFORE shift start (early) + time worked AFTER shift end (late).
        //
        // The previous formula was (totalWorkedHrs - WORKINGHRS). That agrees with this one
        // whenever fromTime is at/before shift start, but breaks when a record's fromTime is
        // the shift END — which is exactly what markOut() Case 2 creates for a late-overtime-only
        // record (e.g. no baseline row because autoMarkin didn't run). 20:00->22:00 then gave
        // 2 - 10 = -8 hrs, so two genuine overtime hours were paid as ZERO.
        $tsFrom      = strtotime($_POST["fromTime"]);
        $tsTo        = strtotime($_POST["toTime"]);
        $tsShiftEnd  = strtotime($arrivalDate . ' ' . $TOTIME);
        // Shift start is derived from shift end minus the contracted working hours, so it
        // stays correct for any driver's configured shift without needing $FROMTIME.
        $tsShiftStart = $tsShiftEnd - ($WORKINGHRS * 3600);

        $earlyHrs = max(0, ($tsShiftStart - $tsFrom) / 3600);
        $lateHrs  = max(0, ($tsTo - $tsShiftEnd) / 3600);
        $data["overtimeHrs"] = round($earlyHrs + $lateHrs, 2);
        if ($data["overtimeHrs"] > 0) {
            $data["totalOvertimePay"] = $OVERTIMEALLOW * $data["overtimeHrs"];
        }
        if (date($outgoingDate . ' ' . $outgoingTime) >= date($arrivalDate . ' ' . $DATIME)) {
            $data["dinnerAllowance"] = $DINNERALLOW;
        }

        if (date($outgoingDate . ' ' . $outgoingTime) >= date($taxiAllowanceDate . ' ' . $TAXIALLOTIME)) {
            $data["taxiAllowance"] = $TAXIALLOW;
        }
    } else {
        // WEEKLY OFF DAY - Flat rate + overtime if crosses shift end
        $timestamp1 = strtotime($_POST["fromTime"]);
        $timestamp2 = strtotime($_POST["toTime"]);
        $totalWorkingHrs = abs($timestamp2 - $timestamp1) / (60 * 60);

        // Flat rate based on hours worked on off-day
        // offDayPriceBelow4Hr (Rs. 450) for ≤4 hours
        // offDayPriceAbove4Hr (Rs. 600) for >4 hours
        if ($totalWorkingHrs > 0) {
            if ($totalWorkingHrs <= 4) {
                $data["sunAllowance"] = $SUNFOURHRSALLOW;  // Rs. 450 for up to 4 hours
            } else {
                $data["sunAllowance"] = $ABVFOURHRSALLOW;  // Rs. 600 for more than 4 hours
            }
        }

        // PLUS overtime if work extends past shift end time.
        // Overtime is counted from whichever is LATER: shift end, or when the driver actually
        // arrived. Previously it always counted from shift end, so an off-day stint that began
        // after shift end was massively over-paid — e.g. 22:00->23:00 (1 hr worked) was paid as
        // 3 hrs (23:00 minus 20:00).
        if (date($outgoingDate . ' ' . $outgoingTime) >= date($arrivalDate . ' ' . $TOTIME)) {
            $shiftEndTimestamp = strtotime($arrivalDate . ' ' . $TOTIME);
            $overtimeStart   = max(strtotime($_POST["fromTime"]), $shiftEndTimestamp);
            $overtimeSeconds = strtotime($_POST["toTime"]) - $overtimeStart;
            if ($overtimeSeconds > 0) {
                $data["overtimeHrs"] = round($overtimeSeconds / 3600, 2);
                $data["totalOvertimePay"] = $OVERTIMEALLOW * $data["overtimeHrs"];
            }
        }

        // Dinner and taxi allowance also apply on off days if working late
        if (date($outgoingDate . ' ' . $outgoingTime) >= date($arrivalDate . ' ' . $DATIME)) {
            $data["dinnerAllowance"] = $DINNERALLOW;
        }

        if (date($outgoingDate . ' ' . $outgoingTime) >= date($taxiAllowanceDate . ' ' . $TAXIALLOTIME)) {
            $data["taxiAllowance"] = $TAXIALLOW;
        }
    }
    $data["totalPay"] = $data["expenseAmt"] + $data["totalOvertimePay"] + $data["dinnerAllowance"] + $data["taxiAllowance"] + $data["sunAllowance"];
    return $data;
}
// End.
// Start: Get driver overtime management report into 
function driverReport()
{
    global $DB;
    $weekDate = [];
    $arrUserData = array();
    $weekDate[0] = 01;
    $weekDate[0] = 01;
    $filename = "Driver-Report";
    $year = intval($_REQUEST['year']);
    $month = intval($_REQUEST['month']);
    $week = $_GET['week'];
    $weekDate = explode(',', $week);
    if ($week == '') {
        $weekDate[0] = 01;
        $weekDate[1] = 31;
    }

    if ($month > 9 || $weekDate[0] > 9) {
        $fromDate = date($year . '-' . $month . '-' . $weekDate[0]);
    } else {
        $fromDate = date($year . '-0' . $month . '-0' . $weekDate[0]);
    }
    if ($month > 9 || $weekDate[1] > 9) {
        $toDate = date($year . '-' . $month . '-' . $weekDate[1]);
    } else {
        $toDate = date($year . '-0' . $month . '-0' . $weekDate[1]);
    }

    if ($year != "") {
        $DB->vals = array(1, $year, $month, date($fromDate), date($toDate));
        $DB->types = "iiiss";
        $DB->sql = "SELECT DM.driverManagementID,U.userName AS driverName,DM.dmDate,DM.fromTime,DM.toTime,DM.otherExpense,DM.expenseAmt,DM.overtimeHrs,DM.totalOvertimePay,DM.dinnerAllowance,DM.taxiAllowance,DM.sunAllowance,DM.totalPay,DM.supportingDoc,DM.recordType,DM.isVerify,DM.isSettled,DM.status FROM " . $DB->pre . "driver_management AS DM 
        LEFT JOIN " . $DB->pre . "user AS U ON U.userID=DM.userID
        WHERE DM.status=? AND YEAR(DM.dmDate)=? AND MONTH(DM.dmDate)=? AND DM.dmDate BETWEEN ? AND ?";
        $arrUserData = $DB->dbRows();
    }
    generateExcel($arrUserData, $filename);
}
function generateExcel($records = array(), $filename = "Excel")
{
    ini_set('memory_limit', '-1');
    $filename = $filename . "-list-" . date('m-d-Y') . ".csv";
    header('Content-type: application/ms-excel');
    header('Content-Disposition: attachment; filename=' . $filename);
    $fh = fopen('php://output', 'w');
    $is_coloumn = true;
    if (!empty($records)) {
        foreach ($records as $key => $record) {
            if ($is_coloumn) {
                fputcsv($fh, array_keys($record));
                $is_coloumn = false;
            }
            fputcsv($fh, array_values($record));
        }
        fclose($fh);
    }
    exit;
}

function verifyMarkin()
{
    global $DB;
    $response['err'] = 1;
    $response['msg'] = "Markin Verified Failed";
    $driverManagementID = intval($_POST["driverManagementID"]);
    $DB->table = $DB->pre . "driver_management";
    $DB->data = array("isVerify" => 1, "recordType" => 3);
    if ($DB->dbUpdate("driverManagementID=?", "i", array($driverManagementID))) {
        $response['err'] = 0;
        $response['msg'] = "Markin Verified Successfully.";
    }
    return $response;
}

function settlePayment($settleArr = [])
{
    global $DB;
    $response = array("err" => 1, "param" => "", "msg" => "Something went wrong!");
    if (isset($settleArr) && count($settleArr) > 0 && isset($settleArr['totalWelfareAmount']) && $settleArr['totalWelfareAmount'] > 0 && isset($settleArr['driverArr']) && count($settleArr['driverArr']) > 0) {

        // Fetch overtime details for narration
        $driverIDs = implode(',', array_map('intval', $settleArr['driverArr']));
        $DB->sql = "SELECT DM.*, U.userName FROM `" . $DB->pre . "driver_management` AS DM
                    LEFT JOIN `" . $DB->pre . "user` AS U ON U.userID = DM.userID
                    WHERE DM.driverManagementID IN (" . $driverIDs . ") ORDER BY DM.dmDate ASC";
        $overtimeRecords = $DB->dbRows();

        // Build detailed narration
        $narrationLines = [];
        $driverNames = [];
        foreach ($DB->rows as $record) {
            $date = date('d-M-Y', strtotime($record['dmDate']));
            $fromTime = date('h:i A', strtotime($record['fromTime']));
            $toTime = date('h:i A', strtotime($record['toTime']));
            $totalPay = number_format($record['totalPay'], 2);
            $otHrs = $record['overtimeHrs'];

            $narrationLines[] = "{$date}: {$fromTime} - {$toTime} | OT: {$otHrs} hrs | Rs. {$totalPay}";

            if (!in_array($record['userName'], $driverNames)) {
                $driverNames[] = $record['userName'];
            }
        }
        $voucherDesc = implode("\n", $narrationLines);
        $driverNamesStr = implode(', ', $driverNames);

        //for add credit note
        $creditData['amount'] = $debitData['amount'] =  $settleArr['totalWelfareAmount'];
        $creditData['transactionType'] = 1;
        $creditData['pettyCashNote'] = "Added balance for driver's welfare ({$driverNamesStr})";
        $creditData['paymentMode'] = 'Cash';
        $creditData['transactionDate'] = $debitData['transactionDate'] = date('Y-m-d');
        $response = addPettyCashBook($creditData);

        if (isset($response) && $response['err'] == 0) {
            //for add debit note
            $debitData['transactionType'] = 2;
            $debitData['pettyCashNote'] = "Settle driver's overtime ({$driverNamesStr})";
            $debitData['pettyCashCatID'] = 15;
            $response = addPettyCashBook($debitData);
            if (isset($response) && $response['err'] == 0) {
                $voucherData = [];
                $param = array("tbl" => "voucher", "noCol" => "voucherNo", "dtCol" => "voucherDate", "prefix" => "V");
                $voucherData['voucherNo'] = getNextNo($param);
                $voucherData['voucherDate'] = date('Y-m-d');
                $voucherData['voucherDebitTo'] = $driverNamesStr;
                $voucherData['voucherAmt'] = $settleArr['totalWelfareAmount'];
                $voucherData['voucherTitle'] = "Driver Overtime Settlement";
                $voucherData['voucherDesc'] = $voucherDesc;
                $voucherData['pettyCashCatID'] = '3';  // staff welfare
                $voucherData['voucherRef'] = "Overtime settlement for " . count($settleArr['driverArr']) . " record(s)";
                $response = addVoucherData($voucherData);
                if (isset($response) && $response['err'] == 0) {
                    $dData['isSettled'] = 1;
                    $DB->table = $DB->pre . "driver_management";
                    $DB->data = $dData;
                    if ($DB->dbUpdate("driverManagementID IN (" . $driverIDs . ")")) {
                        $response = array("err" => 0, "param" => "", "msg" => "Driver welfare successfully settled.");
                    } else {
                        $response = array("err" => 1, "msg" => "Error occurred while updating status isSettled");
                    }
                }
            }
        }
    }
    return $response;
}

$_POST = array_merge($_POST, $_GET);
if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    require_once("../../inc/site.inc.php");
    require_once(dirname(__FILE__) . "/../../../core/driver-overtime.inc.php");

    /**
     * SECURITY: admin login is required for every action here.
     *
     * This endpoint previously ran mxCheckRequest(false, true) — no login AND no token —
     * purely so the driver portal's markOut() and the autoMarkout cron could POST to it
     * over cURL without a session. That left ADD / UPDATE / verifyMarkin / settlePayment /
     * mxDelFile callable by anyone on the internet (an unauthenticated verifyMarkin
     * returned {"err":0,...}). Both of those callers now use core/driver-overtime.inc.php
     * in process, so no internal HTTP call exists and the exemption is gone.
     */
    $MXRES = mxCheckRequest(true, true);   // admin login required

    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addDriverMangement();
                break;
            case "UPDATE":
                updateDriverMangement();
                break;
            case "driverReport":
                $MXRES = driverReport();
                break;
            case "overtimeManagement":
                $MXRES = overtimeManagement();
                break;
            case "verifyMarkin":
                $MXRES = verifyMarkin();
                break;
            case "settlePayment":
                $MXRES = settlePayment($_POST);
                break;
            case "mxDelFile":
                $param = array("dir" => "driver-management", "tbl" => "driver_management", "pk" => "driverManagementID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "driver_management", "PK" => "driverManagementID", "UDIR" => array("supportingDoc" => "driver-management")));
}
