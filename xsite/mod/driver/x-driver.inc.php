<?php

// Include Brevo email service for overtime notifications
require_once dirname(__FILE__) . "/../../../core/brevo.inc.php";

function driverLogin()
{
    global $DB;
    $response['err'] = 1;
    $response['msg'] = "Please Enter Valid PIN";

    $loginOtp1 = mysqli_real_escape_string($DB->con, $_POST['loginOtp1']);
    $loginOtp2 = mysqli_real_escape_string($DB->con, $_POST['loginOtp2']);
    $loginOtp3 = mysqli_real_escape_string($DB->con, $_POST['loginOtp3']);
    $loginOtp4 = mysqli_real_escape_string($DB->con, $_POST['loginOtp4']);
    $loginOtp = $loginOtp1 . $loginOtp2 . $loginOtp3 . $loginOtp4;

    if ($loginOtp != "") {
        $DB->vals = array(1, $loginOtp);
        $DB->types = "is";
        $DB->sql = "SELECT * FROM `" . $DB->pre . "user` WHERE status=?  AND userLoginOTP=?";
        $driverData = $DB->dbRow();

        //if(DRIVERLOGINOTP == $loginOtp) {
        if ($DB->numRows > 0) {
            $_SESSION['DRIVER_LOGIN_OTP'] = $loginOtp;
            $_SESSION['USER_ID'] = $driverData['userID'];
            $_SESSION['USER_NAME'] = $driverData['userName'];
            $response['err'] = 0;
            $response['msg'] = "Login Successfully";
        }
    }
    return $response;
}


function markIn()
{
    global $DB;
    $response['err'] = 1;
    $response['msg'] = "Markin Failed.";

    $userID = $_SESSION['USER_ID'] ?? 0;
    $userName = $_SESSION['USER_NAME'] ?? "";

    // Get driver's shift start time from user settings
    $DB->vals = array(1, $userID);
    $DB->types = "ii";
    $DB->sql = "SELECT userFromTime FROM `" . $DB->pre . "user` WHERE status=? AND userID=?";
    $userSettings = $DB->dbRow();
    $driverShiftStart = isset($userSettings['userFromTime']) ? substr($userSettings['userFromTime'], 0, 5) : '10:00';

    // Check if today is driver's weekly off day
    $todayDayOfWeek = date('N'); // 1=Monday, 7=Sunday
    $DB->vals = array(1, $userID, $todayDayOfWeek);
    $DB->types = "iii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "user_off_days` WHERE status=? AND userID=? AND weekdayNo=?";
    $DB->dbRow();
    $isOffDay = ($DB->numRows > 0);

    // Prevent mark-in before 6 AM (driver should mark out previous day's shift first)
    $currentHour = (int)date('H');
    if ($currentHour < 6) {
        $response['msg'] = "Mark In not available before 6 AM. Please mark out previous shift first.";
        return $response;
    }

    // On normal days, Mark In is only for early overtime (before driver's shift start time)
    // On off days, Mark In is allowed anytime after 6 AM
    if (!$isOffDay && date('H:i') >= $driverShiftStart) {
        $response['msg'] = "Mark In is only available before " . date('g:i A', strtotime($driverShiftStart)) . " for early overtime.";
        return $response;
    }

    $currDate = date("Y-m-d");
    $DB->vals = array(1, $currDate, $userID);
    $DB->types = "isi";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "driver_management` WHERE status=?  AND dmDate=? AND userID=?";
    $driverManagement = $DB->dbRow();

    if ($DB->numRows == 0) {
        // No record exists - create new one
        $arrIn = array(
            "dmDate" => date("Y-m-d"),
            "fromTime" => date("Y-m-d H:i:s"),
            "recordType" => 2,
            "isVerify" => 0,
            "userID" => $userID
        );
        $DB->table = $DB->pre . "driver_management";
        $DB->data = $arrIn;
        if ($DB->dbInsert()) {
            // Send overtime notification via Brevo
            $overtimeData = array(
                'driverName' => $userName,
                'overtimeType' => 'early_checkin',
                'checkInTime' => date("Y-m-d H:i:s"),
                'checkOutTime' => null,
                'date' => date("Y-m-d")
            );
            sendDriverOvertimeNotification($overtimeData);

            $response['err'] = 0;
            $response['msg'] = "Markin Successfully";
        }
    } elseif ($driverManagement['recordType'] == 1 && ($driverManagement['toTime'] == '' || $driverManagement['toTime'] == NULL || $driverManagement['toTime'] == '0000-00-00 00:00:00')) {
        // Cron-created record exists (recordType=1) with no toTime - UPDATE it with early check-in time
        $driverManagementID = $driverManagement['driverManagementID'];
        $DB->table = $DB->pre . "driver_management";
        $DB->data = array(
            "fromTime" => date("Y-m-d H:i:s"),
            "recordType" => 2,
            "isVerify" => 0
        );
        if ($DB->dbUpdate("driverManagementID=?", "i", array($driverManagementID))) {
            // Send overtime notification via Brevo
            $overtimeData = array(
                'driverName' => $userName,
                'overtimeType' => 'early_checkin',
                'checkInTime' => date("Y-m-d H:i:s"),
                'checkOutTime' => null,
                'date' => date("Y-m-d")
            );
            sendDriverOvertimeNotification($overtimeData);

            $response['err'] = 0;
            $response['msg'] = "Markin Successfully (early overtime recorded)";
        }
    } elseif ($driverManagement['recordType'] == 2) {
        // Already manually marked in
        $response['msg'] = "Markin already done.";
    } elseif ($driverManagement['toTime'] != '' && $driverManagement['toTime'] != NULL && $driverManagement['toTime'] != '0000-00-00 00:00:00') {
        // Already marked out
        $response['msg'] = "Today's overtime already completed.";
    } else {
        $response['msg'] = "Markin already done.";
    }
    return $response;
}


function driverLogout()
{
    $response['err'] = 0;
    $response['msg'] = "Logout Successfully";

    // Clear driver session variables
    unset($_SESSION['DRIVER_LOGIN_OTP']);
    unset($_SESSION['USER_ID']);
    unset($_SESSION['USER_NAME']);

    return $response;
}

function markOut()
{
    global $DB;
    $response['err'] = 1;
    error_reporting(0);
    $response['msg'] = "Markout Failed.";
    $driverManagementID = intval($_POST['driverManagementID'] ?? 0);
    $toTime = date("Y-m-d H:i:s");
    $userID = $_SESSION['USER_ID'] ?? 0;
    $userName = $_SESSION['USER_NAME'] ?? "";

    // Get driver's shift end time from user settings
    $DB->vals = array(1, $userID);
    $DB->types = "ii";
    $DB->sql = "SELECT userToTime FROM `" . $DB->pre . "user` WHERE status=? AND userID=?";
    $userSettings = $DB->dbRow();
    $driverShiftEnd = isset($userSettings['userToTime']) ? substr($userSettings['userToTime'], 0, 5) : '20:00';

    // Determine the date for this overtime record
    // If it's before 6 AM, this is for yesterday's shift
    $currentHour = (int)date('H');
    $currentTime = date('H:i');
    $dmDate = ($currentHour < 6) ? date('Y-m-d', strtotime('-1 day')) : date('Y-m-d');

    // Check if today/relevant date is driver's weekly off day
    $checkDayOfWeek = date('N', strtotime($dmDate)); // 1=Monday, 7=Sunday
    $DB->vals = array(1, $userID, $checkDayOfWeek);
    $DB->types = "iii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "user_off_days` WHERE status=? AND userID=? AND weekdayNo=?";
    $DB->dbRow();
    $isOffDay = ($DB->numRows > 0);

    // VALIDATION: On normal days, Mark Out should only be allowed in late overtime window (after shift end or before 6 AM)
    // On off days, Mark Out is allowed anytime (if driver has an open shift)
    $isLateOvertimeWindow = ($currentTime >= $driverShiftEnd || $currentHour < 6);
    if (!$isOffDay && !$isLateOvertimeWindow && $driverManagementID == 0) {
        // If trying to mark out before shift end without an existing open shift,
        // this is not allowed - Mark Out is only for late overtime
        $response['msg'] = "Mark Out is only available after " . date('g:i A', strtotime($driverShiftEnd)) . " for late overtime.";
        return $response;
    }

    // Case 1: Existing record (driver marked in earlier)
    if ($driverManagementID > 0) {
        $DB->vals = array(1, $driverManagementID);
        $DB->types = "ii";
        $DB->sql = "SELECT * FROM `" . $DB->pre . "driver_management` WHERE status=? AND driverManagementID=?";
        $driverManagement = $DB->dbRow();

        if ($DB->numRows > 0) {
            $fromTime = $driverManagement['fromTime'];
            $dmDate = $driverManagement['dmDate'];
            $expenseAmt = $driverManagement['expenseAmt'];

            // Validate that toTime is after fromTime
            if (strtotime($toTime) < strtotime($fromTime)) {
                $response['msg'] = "Error: Checkout time cannot be before check-in time. Please contact admin.";
                return $response;
            }
        } else {
            $response['msg'] = "Record not found.";
            return $response;
        }
    }
    // Case 2: No existing record - late overtime only (driver didn't mark in)
    else {
        // Check if there's already a record for today/yesterday
        $DB->vals = array(1, $userID, $dmDate);
        $DB->types = "iis";
        $DB->sql = "SELECT * FROM `" . $DB->pre . "driver_management` WHERE status=? AND userID=? AND dmDate=?";
        $existingRecord = $DB->dbRow();

        if ($DB->numRows > 0) {
            // Use existing record
            $driverManagementID = $existingRecord['driverManagementID'];
            $fromTime = $existingRecord['fromTime'];
            $expenseAmt = $existingRecord['expenseAmt'];

            // Validate that toTime is after fromTime
            if (strtotime($toTime) < strtotime($fromTime)) {
                $response['msg'] = "Error: Checkout time cannot be before check-in time. Please contact admin.";
                return $response;
            }
        } else {
            // Create new record with fromTime = driver's shift end time (for late overtime)
            // This ensures logical flow: they worked until now, overtime starts from shift end
            $fromTime = $dmDate . " " . $driverShiftEnd . ":00";
            $expenseAmt = 0;

            $arrIn = array(
                "dmDate" => $dmDate,
                "fromTime" => $fromTime,
                "recordType" => 1,
                "isVerify" => 0,
                "userID" => $userID
            );
            $DB->table = $DB->pre . "driver_management";
            $DB->data = $arrIn;
            if ($DB->dbInsert()) {
                $driverManagementID = $DB->insertID;
            } else {
                $response['msg'] = "Failed to create overtime record.";
                return $response;
            }
        }
    }

    // Now update the record with toTime
    $ch = curl_init();
    $requestParam = "xAction=UPDATE&driverManagementID=" . $driverManagementID . "&toTime=" . urlencode($toTime) . "&fromTime=" . urlencode($fromTime) . "&expenseAmt=" . $expenseAmt . "&dmDate=" . $dmDate . "&userID=" . $userID;
    curl_setopt($ch, CURLOPT_URL, SITEURL . "/xadmin/mod/driver-management/x-driver-management.inc.php");
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestParam);
    $result = curl_exec($ch);
    curl_close($ch);

    $resultArr = json_decode($result, true);

    if (isset($resultArr['err']) && $resultArr['err'] == 0) {
        // Send overtime notification via Brevo
        $overtimeData = array(
            'driverName' => $userName,
            'overtimeType' => 'late_checkout',
            'checkInTime' => $fromTime,
            'checkOutTime' => $toTime,
            'date' => $dmDate
        );
        sendDriverOvertimeNotification($overtimeData);

        $response['err'] = 0;
        $response['msg'] = "Markout Successfully";
    } else {
        $response['msg'] = $resultArr['msg'] ?? "Failed to update record.";
    }

    return $response;
}




if (isset($_POST["xAction"])) {
    require_once "../../../core/core.inc.php";
    $MXRES = mxCheckRequest(false, true);
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "driverLogin":
                $MXRES = driverLogin();
                break;
            case "markIn":
                $MXRES = markIn();
                break;
            case "markOut":
                $MXRES = markOut();
                break;
            case "driverLogout":
                $MXRES = driverLogout();
                break;
        }
    }
    echo json_encode($MXRES);
}
