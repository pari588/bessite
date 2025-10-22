<?php
// Start: To save product inquiry data.
function verifyUserPin()
{
    global $DB;
    $response['err'] = 1;
    $response['msg'] = "";
    $result = array();

    $userPinArr = array();
    for ($n = 1; $n <= 4; $n++) {
        $pin = $_POST["pin_" . $n];
        array_push($userPinArr, $pin);
    }
    $userPIN = implode("", $userPinArr);
    if ($userPIN != "") {
        $DB->vals = array(1, trim($userPIN));
        $DB->types = "is";
        $DB->sql = "SELECT userID,userPin,displayName  FROM `" . $DB->pre . "x_admin_user` WHERE status=? AND userPin=?";
        $result = $DB->dbRow();
        if (is_array($result) && count($result) > 0) {
            if ($result["userPin"] != "") {
                $response['err'] = 0;
                $_SESSION['LEADUSERID'] = $result["userID"];
                $_SESSION['LEADUSERNAME'] = $result["displayName"];
                $response['msg'] = "User Pin is Available!";
            }
        } else {
            $response['err'] = 1;
            $response['msg'] = "Please Enter a Valid User Pin!";
        }
    } else {
        $response['msg'] = "Enter 4 Digit PIN Code!";
    }
    return $response;
}
// End.
// Start: To save new lead user data.
function addLeadUser()
{
    global $DB;
    $data = array();
    $data['err'] = 1;
    $data['msg'] = "Someting went wrong";
    $_POST["referenceDocument"]  = mxGetFileName("referenceDocument");
    $_POST["visitingCard"]  = mxGetFileName("visitingCard");
    $_POST["cameraUpload"]  = trim($_POST["cameraUpload"]);
    $_POST["addedByLead"] = $_SESSION['LEADUSERID'] ?? 0;
    $_POST["transferByLead"] = $_SESSION['LEADUSERID'] ?? 0;
    $_POST["currentLead"] = $_SESSION['LEADUSERID'] ?? 0;
    $DB->table = $DB->pre . "lead";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $data['err'] = 0;
        $data['msg'] = "New lead added successfully!";
    }

    return $data;
}
// End.
// Start: To get geolocation using longitude and latitude.
function getLocation()
{
    global $DB;
    $response['data'] = array();
    $response['count'] = 0;
    $response['msg'] = "Somthing went wrong!!";

    if (isset($_POST['latitude'])) {
        $latitude = mysqli_real_escape_string($DB->con, $_POST['latitude']);
    }

    if (isset($_POST['longitude'])) {
        $longitude = mysqli_real_escape_string($DB->con, $_POST['longitude']);
    }

    if ($latitude != "" && $longitude != "") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.opencagedata.com/geocode/v1/json?q=' . $latitude . '%2C%20' . $longitude . '&key=1d718062cc2f4abba0f7ea7b59fcd700&language=en&pretty=1');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if (is_array($result->results)) {
            if (count($result->results) > 0) {
                $streetAddress = $result->results[0]->formatted;
                $response['count'] = 1;
                $response['msg'] = "Location fetched successfully.";
                $response['streetAddress'] =  $streetAddress;
            }
        }
    }
    return $response;
}
// End.

function uploadLocationImg()
{
    global $DB;
    $response['err'] = 1;
    $response['msg'] = "Something went wrong";

    if (isset($_FILES['webcam'])) {
        $filename = 'pres_' . date('YmdHis') . '.jpeg';
        // $url = '';
        if (move_uploaded_file($_FILES['webcam']['tmp_name'], UPLOADPATH . '/camera-upload/' . $filename)) {
            $response['err'] = 0;
            $response['filename'] = $filename;
            $response['msg'] = "SUCCESS";
        }
    }
    return $response;
}
function is_android() {
    $is_android = 0;
    if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false) {
        $is_android = 1;
    }
    // echo $is_android;
    return $is_android;
}

if (isset($_REQUEST["xAction"])) {
    require_once("../../../core/core.inc.php");
    $xAction = $_REQUEST["xAction"];
    $ignoreToken = $xAction == "uploadLocationImg" ? true : false;
    $MXRES = mxCheckRequest(false, $ignoreToken);
    if ($MXRES["err"] == 0) {
        switch ($xAction) {
            case "verifyUserPin":
                $MXRES = verifyUserPin($_POST);
                break;
            case "addLeadUser":
                $MXRES = addLeadUser($_POST);
                break;
            case "getLocation":
                $MXRES = getLocation($_POST);
                break;
            case "uploadLocationImg":
                $MXRES = uploadLocationImg($_POST);
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(
        array("TBL" => "lead", "PK" => "leadID", "UDIR" => array(
            "referenceDocument" => "lead-ref-document",
            "visitingCard" => "visiting-card",
            "cameraUpload" => "camera-upload"
        ))
    );
}
