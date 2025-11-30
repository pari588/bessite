<?php

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

    $currDate = date("Y-m-d");
    $DB->vals = array(1, $currDate, $userID);
    $DB->types = "isi";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "driver_management` WHERE status=?  AND dmDate=? AND userID=?";
    $driverManagement = $DB->dbRow();
    if ($DB->numRows == 0) {
        $arrIn = array(
            "dmDate" => date("Y-m-d"),
            "fromTime" => date("Y-m-d H:i:s"),
            "recordType" => 2,
            "isVerify" => 1,
            "userID" => $userID
        );
        $DB->table = $DB->pre . "driver_management";
        $DB->data = $arrIn;
        if ($DB->dbInsert()) {
            if (date("Y-m-d H:i") < date("Y-m-d 10:00")) {
                $subject = "Quick Verification: Driver " . $userName . "'s Overtime";
                $body = 'Hi Akash, 
            Noticed Dilkush checked in before 10 AM and checked out after 8 PM on ' . date("Y-m-d") . '. If accurate, please confirm by clicking the link below:
            ' . SITEURL . '/xadmin/driver-management-list/ 
            Thanks for your prompt attention.
            Best';
                // sendEmail($mailData);
                mail("akash.tdf@gmail.com", $subject, $body);
            }

            $response['err'] = 0;
            $response['msg'] = "Markin Successfully";
        }
    } elseif ($driverManagement['fromTime'] != '') {
        $response['msg'] = "Markin already done.";
    } elseif ($driverManagement['fromTime'] != '' && $driverManagement['toTime'] != '' && $driverManagement['dmDate'] != '') {
        $response['msg'] = "Markout Alredy Done";
    } else {
        $response['msg'] = "Markin already done.";
    }
    return $response;
}


function markOut()
{
    global $DB;
    $response['err'] = 1;
    error_reporting(0);
    $response['msg'] = "Markout Failed.";
    $driverManagementID = intval($_POST['driverManagementID']);
    $toTime = date("Y-m-d H:i:s");
    $userName = $_SESSION['USER_NAME'] ?? "";

    $DB->vals = array(1, $driverManagementID);
    $DB->types = "is";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "driver_management` WHERE status=?  AND driverManagementID=?";
    $driverManagement = $DB->dbRow();

    $markTime = $driverManagement['fromTime'];
    $currentTime = date('H:i:s'); // Get the current time in "H:i:s" format

    $timestamp1 = strtotime($markTime);
    $timestamp2 = strtotime($currentTime);
    // Calculate the difference between the two timestamps
    $elapsedTime = abs($timestamp2 - $timestamp1);

    if ($elapsedTime < (1 * 60)) { // 60 minutes * 60 seconds
        $response['status'] = false;
        $response['msg'] = "Mark out Not allowed.";
    } else {
        $ch = curl_init();
        $requestParam = "xAction=UPDATE&driverManagementID=" . $driverManagementID . "&toTime=" . $toTime . "&fromTime=" . $driverManagement['fromTime'] . "&expenseAmt=" . $driverManagement['expenseAmt'] . "&dmDate=" . $driverManagement['dmDate'];
        curl_setopt($ch, CURLOPT_URL, SITEURL . "/xadmin/mod/driver-management/x-driver-management.inc.php");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestParam);
        $result = curl_exec($ch);

        $resultArr = json_decode($result, true);

        if ($resultArr['err'] == 0) {
            if (date("Y-m-d H:i") > date("Y-m-d 20:00")) {
                $subject = "Quick Verification: Driver " . $userName . "'s Overtime";
                $body = 'Hi Akash, 
            Noticed Dilkush checked in before 10 AM and checked out after 8 PM on ' . date("Y-m-d") . '. If accurate, please confirm by clicking the link below:
            ' . SITEURL . '/xadmin/driver-management-list/ 
            Thanks for your prompt attention.
            Best';
                // sendEmail($mailData);
                mail("akash.tdf@gmail.com", $subject, $body);
            }
            $response['err'] = 0;
            $response['msg'] = "Markout Successfully";
        }
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
        }
    }
    echo json_encode($MXRES);
}
