<?php
/*
addMotor = To save motor data.
updateMotor = To update motor data.
addUpdateMoterDetail = To add and update motor's detail data. 
*/
//Start: To save motor data.
function addMotor()
{
    global $DB;
    if (isset($_POST["motorTitle"]))
        $_POST["motorTitle"] = cleanTitle($_POST["motorTitle"]);
    if (isset($_POST["motorSubTitle"]))
        $_POST["motorSubTitle"] = cleanTitle($_POST["motorSubTitle"]);
    if (isset($_POST["categoryMID"]))
        $_POST["categoryMID"] = intval($_POST["categoryMID"]);
    if (isset($_POST["motorDesc"]))
        $_POST["motorDesc"] = trim($_POST["motorDesc"]);
    $_POST["motorImage"] = mxGetFileName("motorImage");
    $_POST["seoUri"] = makeSeoUri($_POST["motorTitle"]);

    $DB->table = $DB->pre . "motor";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $motorID = $DB->insertID;
        if ($motorID) {
            addUpdateMoterDetail($motorID);
            setResponse(array("err" => 0, "param" => "id=$motorID"));
        }
    } else {
        setResponse(array("err" => 1));
    }
}
//End.
//Start: To update motor data.
function  updateMotor()
{
    global $DB;
    $motorID = intval($_POST["motorID"]);
    if (isset($_POST["motorTitle"]))
        $_POST["motorTitle"] = cleanTitle($_POST["motorTitle"]);
    if (isset($_POST["categoryMID"]))
        $_POST["categoryMID"] = intval($_POST["categoryMID"]);
    if (isset($_POST["motorSubTitle"]))
        $_POST["motorSubTitle"] = cleanTitle($_POST["motorSubTitle"]);
    if (isset($_POST["motorDesc"]))
        $_POST["motorDesc"] = trim($_POST["motorDesc"]);
    $_POST["motorImage"] = mxGetFileName("motorImage");
    $_POST["seoUri"] = makeSeoUri($_POST["motorTitle"]);

    $DB->table = $DB->pre . "motor";
    $DB->data = $_POST;
    if ($DB->dbUpdate("motorID=?", "i", array($motorID))) {
        if ($motorID) {
            $DB->vals = array($motorID);
            $DB->types = "i";
            $DB->sql = "DELETE FROM " . $DB->pre . "motor_detail WHERE motorID=?";
            $DB->dbQuery();
            addUpdateMoterDetail($motorID);
            updateContactUsProduct($_POST["oldProductTitle"], $_POST["motorTitle"], 1);
            setResponse(array("err" => 0, "param" => "id=$motorID"));
        }
    } else {
        setResponse(array("err" => 1));
    }
}
//End.
//Start: To add and update motor's detail information.
function addUpdateMoterDetail($motorID = 0)
{
    global $DB, $TPL;
    if (intval($motorID) > 0) {
        if ($_POST["descriptionTitle"]["0"] != "" || $_POST["descriptionOutput"]["0"] != "" || $_POST["descriptionFrameSize"]["0"] != "" || $_POST["descriptionStandard"]["0"] != "") {
            if (isset($_POST["motorDID"]) && count($_POST["motorDID"]) > 0) {
                for ($i = 0; $i < count($_POST["motorDID"]); $i++) {
                    $arrIn = array(
                        "motorID" => $motorID,
                        "descriptionTitle" => $_POST["descriptionTitle"][$i],
                        "descriptionOutput" => $_POST["descriptionOutput"][$i],
                        "descriptionVoltage" => $_POST["descriptionVoltage"][$i],
                        "descriptionFrameSize" => $_POST["descriptionFrameSize"][$i],
                        "descriptionStandard" => $_POST["descriptionStandard"][$i]
                    );
                    $DB->table = $DB->pre . "motor_detail";
                    $DB->data = $arrIn;
                    $DB->dbInsert();
                }
            }
        }
    }
}
//End.

if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    require_once("../../inc/site.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addMotor();
                break;
            case "UPDATE":
                updateMotor();
                break;
            case "mxDelFile":
                $param = array("dir" => "motor", "tbl" => "motor", "pk" => "motorID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "motor", "PK" => "motorID", "UDIR" => array("motorImage" => "motor")));
}
