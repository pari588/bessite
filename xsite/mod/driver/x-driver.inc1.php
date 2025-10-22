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
        if (DRIVERLOGINOTP == $loginOtp) {
            $_SESSION['DRIVER_LOGIN_OTP'] = $loginOtp;
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

    $arrIn = array(
        "dmDate" => date("Y-m-d"),
        "fromTime" => date("Y-m-d H:i:s")
    );
    $DB->table = $DB->pre . "driver_management";
    $DB->data = $arrIn;
    if ($DB->dbInsert()) {
        $response['err'] = 0;
        $response['msg'] = "Markin Successfully";
    }
    return $response;
}

function markOut()
{
    global $DB;
    $response['err'] = 1;
    $response['msg'] = "Markout Failed.";
    $driverManagementID = intval($_POST['driverManagementID']);

    $arrIn = array(
        "toTime" => date("Y-m-d H:i:s")
    );
    $DB->table = $DB->pre . "driver_management";
    $DB->data = $arrIn;
    if ($DB->dbUpdate("driverManagementID=?", "i", array($driverManagementID))) {
        $response['err'] = 0;
        $response['msg'] = "Markout Successfully";
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
