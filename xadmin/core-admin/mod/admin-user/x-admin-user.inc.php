<?php
function addUser()
{
    global $DB, $MXRES;
    $_POST["seoUri"] = makeSeoUri($_POST["displayName"]);
    $_POST["imageName"] = mxGetFileName("imageName");
    $_POST["userPass"] = md5($_POST["userPass"]);

    // Checkbox handling
    $_POST['techIlliterate'] = (!isset($_POST['techIlliterate']) || $_POST['techIlliterate'] <= 0) ? 0 : 1;
    $_POST['isLeaveManager'] = (!isset($_POST['isLeaveManager']) || $_POST['isLeaveManager'] <= 0) ? 0 : 1;

    // Sanitize HR fields
    if (isset($_POST["employeeCode"])) $_POST["employeeCode"] = cleanTitle($_POST["employeeCode"]);
    if (isset($_POST["designation"])) $_POST["designation"] = cleanTitle($_POST["designation"]);
    if (isset($_POST["department"])) $_POST["department"] = cleanTitle($_POST["department"]);
    if (isset($_POST["bankName"])) $_POST["bankName"] = cleanTitle($_POST["bankName"]);
    if (isset($_POST["bankAccountNo"])) $_POST["bankAccountNo"] = cleanTitle($_POST["bankAccountNo"]);
    if (isset($_POST["bankIFSC"])) $_POST["bankIFSC"] = strtoupper(cleanTitle($_POST["bankIFSC"]));
    if (isset($_POST["panNo"])) $_POST["panNo"] = strtoupper(cleanTitle($_POST["panNo"]));
    if (isset($_POST["aadhaarNo"])) $_POST["aadhaarNo"] = cleanTitle($_POST["aadhaarNo"]);
    if (isset($_POST["emergencyContactName"])) $_POST["emergencyContactName"] = cleanTitle($_POST["emergencyContactName"]);
    if (isset($_POST["emergencyContact"])) $_POST["emergencyContact"] = cleanTitle($_POST["emergencyContact"]);
    if (isset($_POST["biometricID"])) $_POST["biometricID"] = cleanTitle($_POST["biometricID"]);

    // Handle empty managerID
    if (empty($_POST['managerID'])) $_POST['managerID'] = null;

    // Handle empty dates
    if (empty($_POST['dateOfBirth'])) $_POST['dateOfBirth'] = null;
    if (empty($_POST['dateOfJoining'])) $_POST['dateOfJoining'] = null;
    if (empty($_POST['dateOfExit'])) $_POST['dateOfExit'] = null;

    $DB->table = $DB->pre . "x_admin_user";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $userID = $DB->insertID;
        setResponse(["err" => 0, "param" => "id=$userID"]);
    } else {
        setResponse(["err" => 1]);
    }
}

function updateUser()
{
    global $DB, $MXRES;
    $_POST["imageName"] = mxGetFileName("imageName");
    if ($_POST["userPass"])
        $_POST["userPass"] = md5($_POST["userPass"]);
    else
        unset($_POST["userPass"]);
    $userID = intval($_POST["userID"]);

    // Checkbox handling
    $_POST['techIlliterate'] = (!isset($_POST['techIlliterate']) || $_POST['techIlliterate'] <= 0) ? 0 : 1;
    $_POST['isLeaveManager'] = (!isset($_POST['isLeaveManager']) || $_POST['isLeaveManager'] <= 0) ? 0 : 1;

    // Sanitize HR fields
    if (isset($_POST["employeeCode"])) $_POST["employeeCode"] = cleanTitle($_POST["employeeCode"]);
    if (isset($_POST["designation"])) $_POST["designation"] = cleanTitle($_POST["designation"]);
    if (isset($_POST["department"])) $_POST["department"] = cleanTitle($_POST["department"]);
    if (isset($_POST["bankName"])) $_POST["bankName"] = cleanTitle($_POST["bankName"]);
    if (isset($_POST["bankAccountNo"])) $_POST["bankAccountNo"] = cleanTitle($_POST["bankAccountNo"]);
    if (isset($_POST["bankIFSC"])) $_POST["bankIFSC"] = strtoupper(cleanTitle($_POST["bankIFSC"]));
    if (isset($_POST["panNo"])) $_POST["panNo"] = strtoupper(cleanTitle($_POST["panNo"]));
    if (isset($_POST["aadhaarNo"])) $_POST["aadhaarNo"] = cleanTitle($_POST["aadhaarNo"]);
    if (isset($_POST["emergencyContactName"])) $_POST["emergencyContactName"] = cleanTitle($_POST["emergencyContactName"]);
    if (isset($_POST["emergencyContact"])) $_POST["emergencyContact"] = cleanTitle($_POST["emergencyContact"]);
    if (isset($_POST["biometricID"])) $_POST["biometricID"] = cleanTitle($_POST["biometricID"]);

    // Handle empty managerID
    if (empty($_POST['managerID'])) $_POST['managerID'] = null;

    // Handle empty dates
    if (empty($_POST['dateOfBirth'])) $_POST['dateOfBirth'] = null;
    if (empty($_POST['dateOfJoining'])) $_POST['dateOfJoining'] = null;
    if (empty($_POST['dateOfExit'])) $_POST['dateOfExit'] = null;

    $DB->table = $DB->pre . "x_admin_user";
    $DB->data = $_POST;
    if ($DB->dbUpdate("userID='$userID'")) {
        setResponse(["err" => 0, "param" => "id=$userID"]);
    } else {
        setResponse(["err" => 1]);
    }
}

function resetUnauthorizedLeavesCnt($userID = 0){
    $data = array("err" => 1, "msg" => "");
    global $DB;
    $lData['unauthorized'] = 0;
    $DB->table = $DB->pre . "x_admin_user";
    $DB->data = $lData;
    if ($DB->dbUpdate("userID='$userID'")) {
        $data = array("err" => 0, "msg" => "Count reset successfully.");
    }
    return $data;
}

function validateUserPin($userData = []){
    global $DB,$MXMOD; 
    $data = array('err'=>1,'msg'=>"Something Went Wrong");
    $userID = $userData['userID'] ?? 0;
    $userPin = $userData['userPin'] ?? '';
    if(isset($userPin) && $userPin !=''){
        $data = array('err'=>0,'msg'=>"User Pin saved");
        $DB->vals = array($userID,$userPin,1);
        $DB->types = "iii";
        $DB->sql = "SELECT userPin FROM `" . $DB->pre ."x_admin_user` 
                    WHERE userID !=? AND userPin = ? AND status= ?";
        $DB->dbRows();
        if($DB->numRows > 0){
            $data = array('err'=>1,'msg'=>"User pin is already being used by another user.");
        }
    }
    return $data;
}

if (isset($_POST["xAction"])) {
    require_once("../../../../core/core.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addUser();
                break;
            case "UPDATE":
                updateUser();
                break;
            case "validateUserPin":
                $MXRES = validateUserPin($_POST);
                break;
            case "resetUnauthorizedLeaves":
                $MXRES = resetUnauthorizedLeavesCnt($_POST['userID']);
                break;
            case "mxDelFile":
                $param = array("dir" => "x_admin_user", "tbl" => "x_admin_user", "pk" => "userID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "x_admin_user", "PK" => "userID", "UDIR" => array("imageName" => "admin_user")));
}
