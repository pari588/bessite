<?php
/**
 * Salary Structure Module Controller
 * Manages employee salary components
 */

function addSalaryStructure()
{
    global $DB;

    $userID = intval($_POST["userID"]);
    $effectiveFrom = cleanTitle($_POST["effectiveFrom"]);

    // Close previous active structure
    $DB->vals = array($userID, $effectiveFrom, 1);
    $DB->types = "isi";
    $DB->sql = "SELECT structureID FROM " . $DB->pre . "salary_structure
                WHERE userID=? AND effectiveTo IS NULL AND effectiveFrom < ? AND status=?";
    $previous = $DB->dbRow();

    if ($previous) {
        // Close previous structure
        $prevEndDate = date('Y-m-d', strtotime($effectiveFrom . ' -1 day'));
        $DB->table = $DB->pre . "salary_structure";
        $DB->data = array("effectiveTo" => $prevEndDate);
        $DB->dbUpdate("structureID=?", "i", array($previous['structureID']));
    }

    // Sanitize and calculate
    $_POST["userID"] = $userID;
    $_POST["effectiveFrom"] = $effectiveFrom;
    $_POST["basicSalary"] = floatval($_POST["basicSalary"] ?? 0);
    $_POST["hra"] = floatval($_POST["hra"] ?? 0);
    $_POST["conveyanceAllowance"] = floatval($_POST["conveyanceAllowance"] ?? 0);
    $_POST["medicalAllowance"] = floatval($_POST["medicalAllowance"] ?? 0);
    $_POST["specialAllowance"] = floatval($_POST["specialAllowance"] ?? 0);
    $_POST["otherAllowance"] = floatval($_POST["otherAllowance"] ?? 0);
    if (isset($_POST["remarks"])) $_POST["remarks"] = cleanTitle($_POST["remarks"]);

    // Calculate gross salary
    $_POST["grossSalary"] = $_POST["basicSalary"] + $_POST["hra"] + $_POST["conveyanceAllowance"] +
        $_POST["medicalAllowance"] + $_POST["specialAllowance"] + $_POST["otherAllowance"];

    $_POST["createdBy"] = $_SESSION[SITEURL]["MXID"];

    $DB->table = $DB->pre . "salary_structure";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $structureID = $DB->insertID;
        setResponse(array("err" => 0, "param" => "id=$structureID", "alert" => "Salary structure saved successfully"));
    } else {
        setResponse(array("err" => 1));
    }
}

function updateSalaryStructure()
{
    global $DB;

    $structureID = intval($_POST["structureID"]);

    // Sanitize and calculate
    $_POST["basicSalary"] = floatval($_POST["basicSalary"] ?? 0);
    $_POST["hra"] = floatval($_POST["hra"] ?? 0);
    $_POST["conveyanceAllowance"] = floatval($_POST["conveyanceAllowance"] ?? 0);
    $_POST["medicalAllowance"] = floatval($_POST["medicalAllowance"] ?? 0);
    $_POST["specialAllowance"] = floatval($_POST["specialAllowance"] ?? 0);
    $_POST["otherAllowance"] = floatval($_POST["otherAllowance"] ?? 0);
    if (isset($_POST["remarks"])) $_POST["remarks"] = cleanTitle($_POST["remarks"]);
    if (isset($_POST["effectiveFrom"])) $_POST["effectiveFrom"] = cleanTitle($_POST["effectiveFrom"]);
    if (isset($_POST["effectiveTo"])) $_POST["effectiveTo"] = cleanTitle($_POST["effectiveTo"]);

    // Calculate gross salary
    $_POST["grossSalary"] = $_POST["basicSalary"] + $_POST["hra"] + $_POST["conveyanceAllowance"] +
        $_POST["medicalAllowance"] + $_POST["specialAllowance"] + $_POST["otherAllowance"];

    $DB->table = $DB->pre . "salary_structure";
    $DB->data = $_POST;
    if ($DB->dbUpdate("structureID=?", "i", array($structureID))) {
        setResponse(array("err" => 0, "param" => "id=$structureID", "alert" => "Salary structure updated"));
    } else {
        setResponse(array("err" => 1));
    }
}

// Get current salary structure for a user
function getCurrentStructure()
{
    global $DB;

    $userID = intval($_POST["userID"]);
    $date = isset($_POST["date"]) ? cleanTitle($_POST["date"]) : date('Y-m-d');

    $DB->vals = array($userID, $date, $date, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT * FROM " . $DB->pre . "salary_structure
                WHERE userID=? AND effectiveFrom <= ?
                AND (effectiveTo IS NULL OR effectiveTo >= ?) AND status=?
                ORDER BY effectiveFrom DESC LIMIT 1";
    $structure = $DB->dbRow();

    return array("err" => 0, "data" => $structure);
}

// Router
if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    require_once("../../inc/site.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addSalaryStructure();
                break;
            case "UPDATE":
                updateSalaryStructure();
                break;
            case "getCurrentStructure":
                $MXRES = getCurrentStructure();
                break;
            case "mxDelFile":
                $param = array("dir" => "salary-structure", "tbl" => "salary_structure", "pk" => "structureID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "salary_structure", "PK" => "structureID", "UDIR" => array()));
}
