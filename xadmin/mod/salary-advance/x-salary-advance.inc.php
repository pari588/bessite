<?php
/**
 * Salary Advance Module Controller
 * Manages employee advance requests and EMI deductions
 */

function addAdvance()
{
    global $DB;

    $userID = intval($_POST["userID"]);
    $_POST["userID"] = $userID;
    $_POST["advanceAmount"] = floatval($_POST["advanceAmount"]);
    $_POST["advanceDate"] = cleanTitle($_POST["advanceDate"]);
    if (isset($_POST["reason"])) $_POST["reason"] = cleanTitle($_POST["reason"]);
    if (isset($_POST["deductFromMonth"])) $_POST["deductFromMonth"] = intval($_POST["deductFromMonth"]);
    if (isset($_POST["deductFromYear"])) $_POST["deductFromYear"] = intval($_POST["deductFromYear"]);
    if (isset($_POST["monthlyDeduction"])) $_POST["monthlyDeduction"] = floatval($_POST["monthlyDeduction"]);

    $_POST["remainingAmount"] = $_POST["advanceAmount"];
    $_POST["totalDeducted"] = 0;
    $_POST["advanceStatus"] = "pending";

    $DB->table = $DB->pre . "salary_advance";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $advanceID = $DB->insertID;
        setResponse(array("err" => 0, "param" => "id=$advanceID"));
    } else {
        setResponse(array("err" => 1));
    }
}

function updateAdvance()
{
    global $DB;

    $advanceID = intval($_POST["advanceID"]);

    if (isset($_POST["advanceAmount"])) $_POST["advanceAmount"] = floatval($_POST["advanceAmount"]);
    if (isset($_POST["advanceDate"])) $_POST["advanceDate"] = cleanTitle($_POST["advanceDate"]);
    if (isset($_POST["reason"])) $_POST["reason"] = cleanTitle($_POST["reason"]);
    if (isset($_POST["deductFromMonth"])) $_POST["deductFromMonth"] = intval($_POST["deductFromMonth"]);
    if (isset($_POST["deductFromYear"])) $_POST["deductFromYear"] = intval($_POST["deductFromYear"]);
    if (isset($_POST["monthlyDeduction"])) $_POST["monthlyDeduction"] = floatval($_POST["monthlyDeduction"]);

    $DB->table = $DB->pre . "salary_advance";
    $DB->data = $_POST;
    if ($DB->dbUpdate("advanceID=?", "i", array($advanceID))) {
        setResponse(array("err" => 0, "param" => "id=$advanceID"));
    } else {
        setResponse(array("err" => 1));
    }
}

// Approve advance request
function approveAdvance()
{
    global $DB;

    $advanceID = intval($_POST["advanceID"]);

    $DB->table = $DB->pre . "salary_advance";
    $DB->data = array(
        "advanceStatus" => "approved",
        "approvedBy" => $_SESSION[SITEURL]["MXID"],
        "approvedAt" => date('Y-m-d H:i:s')
    );

    if ($DB->dbUpdate("advanceID=?", "i", array($advanceID))) {
        setResponse(array("err" => 0, "alert" => "Advance approved successfully"));
    } else {
        setResponse(array("err" => 1));
    }
}

// Reject advance request
function rejectAdvance()
{
    global $DB;

    $advanceID = intval($_POST["advanceID"]);

    $DB->table = $DB->pre . "salary_advance";
    $DB->data = array(
        "advanceStatus" => "rejected",
        "approvedBy" => $_SESSION[SITEURL]["MXID"],
        "approvedAt" => date('Y-m-d H:i:s')
    );

    if ($DB->dbUpdate("advanceID=?", "i", array($advanceID))) {
        setResponse(array("err" => 0, "alert" => "Advance rejected"));
    } else {
        setResponse(array("err" => 1));
    }
}

// Router
if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    require_once("../../inc/site.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addAdvance();
                break;
            case "UPDATE":
                updateAdvance();
                break;
            case "approve":
                approveAdvance();
                break;
            case "reject":
                rejectAdvance();
                break;
            case "mxDelFile":
                $param = array("dir" => "salary-advance", "tbl" => "salary_advance", "pk" => "advanceID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "salary_advance", "PK" => "advanceID", "UDIR" => array()));
}
