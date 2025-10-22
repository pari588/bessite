<?php
/*
addPump = To save Pump data.
updatePump = To update Pump data.
addUpdatePumpDetail = To add and update Pump's detail data. 
*/

//Start: To save Pump data.
function addPump()
{
    global $DB;

    if (isset($_POST["pumpTitle"]))
        $_POST["pumpTitle"] = cleanTitle($_POST["pumpTitle"]);
    if (isset($_POST["categoryPID"]))
        $_POST["categoryPID"] = intval($_POST["categoryPID"]);
    if (isset($_POST["pumpFeatures"]))
        $_POST["pumpFeatures"] = cleanHtml($_POST["pumpFeatures"]);
    if (isset($_POST["kwhp"]))
        $_POST["kwhp"] = cleanTitle($_POST["kwhp"]);
    if (isset($_POST["supplyPhase"]))
        $_POST["supplyPhase"] = cleanTitle($_POST["supplyPhase"]);
    if (isset($_POST["deliveryPipe"]))
        $_POST["deliveryPipe"] = cleanTitle($_POST["deliveryPipe"]);
    if (isset($_POST["noOfStage"]))
        $_POST["noOfStage"] = cleanTitle($_POST["noOfStage"]);
    if (isset($_POST["isi"]))
        $_POST["isi"] = cleanTitle($_POST["isi"]);
    if (isset($_POST["mnre"]))
        $_POST["mnre"] = cleanTitle($_POST["mnre"]);
    if (isset($_POST["pumpType"]))
        $_POST["pumpType"] = cleanTitle($_POST["pumpType"]);
    $_POST["pumpImage"] = mxGetFileName("pumpImage");
    $_POST["seoUri"] = makeSeoUri($_POST["pumpTitle"]);

    $DB->table = $DB->pre . "pump";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $pumpID = $DB->insertID;
        if ($pumpID) {
            addUpdatePumpDetail($pumpID);
            setResponse(array("err" => 0, "param" => "id=$pumpID"));
        }
    } else {
        setResponse(array("err" => 1));
    }
}
//End.

//Start: To update Pump data.
function  updatePump()
{
    global $DB;
    $pumpID = intval($_POST["pumpID"]);
    if (isset($_POST["pumpTitle"]))
        $_POST["pumpTitle"] = cleanTitle($_POST["pumpTitle"]);
    if (isset($_POST["categoryPID"]))
        $_POST["categoryPID"] = intval($_POST["categoryPID"]);
    if (isset($_POST["pumpFeatures"]))
        $_POST["pumpFeatures"] = cleanHtml($_POST["pumpFeatures"]);
    if (isset($_POST["kwhp"]))
        $_POST["kwhp"] = cleanTitle($_POST["kwhp"]);
    if (isset($_POST["supplyPhase"]))
        $_POST["supplyPhase"] = cleanTitle($_POST["supplyPhase"]);
    if (isset($_POST["deliveryPipe"]))
        $_POST["deliveryPipe"] = cleanTitle($_POST["deliveryPipe"]);
    if (isset($_POST["noOfStage"]))
        $_POST["noOfStage"] = trim($_POST["noOfStage"]);
    if (isset($_POST["isi"]))
        $_POST["isi"] = cleanTitle($_POST["isi"]);
    if (isset($_POST["mnre"]))
        $_POST["mnre"] = cleanTitle($_POST["mnre"]);
    if (isset($_POST["pumpType"]))
        $_POST["pumpType"] = cleanTitle($_POST["pumpType"]);
    $_POST["pumpImage"] = mxGetFileName("pumpImage");
    $_POST["seoUri"] = makeSeoUri($_POST["pumpTitle"]);

    $DB->table = $DB->pre . "pump";
    $DB->data = $_POST;
    if ($DB->dbUpdate("pumpID=?", "i", array($pumpID))) {
        if ($pumpID) {
            $DB->vals = array($pumpID);
            $DB->types = "i";
            $DB->sql = "DELETE FROM " . $DB->pre . "pump_detail WHERE pumpID=?";
            $DB->dbQuery();
            addUpdatePumpDetail($pumpID);
            updateContactUsProduct($_POST["oldProductTitle"], $_POST["pumpTitle"], 2);
            setResponse(array("err" => 0, "param" => "id=$pumpID"));
        }
    } else {
        setResponse(array("err" => 1));
    }
}
//End.
//Start: To  Add and Update Pump Details data.
function addUpdatePumpDetail($pumpID = 0)
{
    global $DB;
    if (intval($pumpID) > 0) {
        if ($_POST["categoryref"]["0"] != "" || $_POST["powerKw"]["0"] != "" || $_POST["powerHp"]["0"] != "" || $_POST["supplyPhaseD"]["0"] != "" || $_POST["pipePhase"]["0"] != "" || $_POST["noOfStageD"]["0"] != "" || $_POST["headRange"]["0"] != "" || $_POST["dischargeRange"]["0"] != "" || $_POST["mrp"]["0"] != "" || $_POST["warrenty"]["0"] != "") {
            if (isset($_POST["pumpDID"]) && count($_POST["pumpDID"]) > 0) {
                for ($i = 0; $i < count($_POST["pumpDID"]); $i++) {
                    $arrIn = array(
                        "pumpID" => $pumpID,
                        "categoryref" => $_POST["categoryref"][$i],
                        "powerKw" => $_POST["powerKw"][$i],
                        "powerHp" => $_POST["powerHp"][$i],
                        "supplyPhaseD" => $_POST["supplyPhaseD"][$i],
                        "pipePhase" => $_POST["pipePhase"][$i],
                        "noOfStageD" => $_POST["noOfStageD"][$i],
                        "headRange" => $_POST["headRange"][$i],
                        "dischargeRange" => $_POST["dischargeRange"][$i],
                        "mrp" => $_POST["mrp"][$i],
                        "warrenty" => $_POST["warrenty"][$i],
                    );
                    $DB->table = $DB->pre . "pump_detail";
                    $DB->data = $arrIn;
                    $DB->dbInsert();
                }
            }
        }
    }
}
//End
if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    require_once("../../inc/site.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addPump();
                break;
            case "UPDATE":
                updatePump();
                break;
            case "mxDelFile":
                $param = array("dir" => "pump", "tbl" => "pump", "pk" => "pumpID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "pump", "PK" => "pumpID", "UDIR" => array("pumpImage" => "pump")));
}
