<?php
/**
 * Driver Leave — controller
 *
 * Drivers (mx_user) do not mark themselves in/out: there is no device on site and
 * the phone portal is unused. autoMarkin() in xsite/mx-crons.php therefore creates a
 * baseline duty row each working day, purely so overtime can be measured against it.
 * A leave record here suppresses that baseline (see isDriverOnLeave()) and drives the
 * availability badge on the driver list.
 *
 * toDate is an EXPECTED return date, not a hard end. Leave stays active until the
 * office ticks "Has returned", so the system never silently declares a driver back.
 */

function addDriverLeave()
{
    global $DB;
    if (!validateDriverLeave()) return;

    $DB->table = $DB->pre . "driver_leave";
    $DB->data  = $_POST;
    if ($DB->dbInsert()) {
        setResponse(["err" => 0, "param" => "id=" . $DB->insertID]);
    } else {
        setResponse(["err" => 1]);
    }
}

function updateDriverLeave()
{
    global $DB;
    $driverLeaveID = intval($_POST["driverLeaveID"]);
    if (!validateDriverLeave($driverLeaveID)) return;

    $DB->table = $DB->pre . "driver_leave";
    $DB->data  = $_POST;
    if ($DB->dbUpdate("driverLeaveID=?", "i", array($driverLeaveID))) {
        setResponse(["err" => 0, "param" => "id=$driverLeaveID"]);
    } else {
        setResponse(["err" => 1]);
    }
}

/**
 * Reject a to-date before the from-date, and overlapping leave for the same driver.
 */
function validateDriverLeave($excludeID = 0)
{
    global $DB;
    $userID   = intval($_POST["userID"] ?? 0);
    $fromDate = $_POST["fromDate"] ?? "";
    $toDate   = $_POST["toDate"] ?? "";

    if (!$userID || $fromDate == "") {
        setResponse(["err" => 1, "msg" => "Driver and From Date are required", "alert" => "Driver and From Date are required"]);
        return false;
    }
    if ($toDate != "" && strtotime($toDate) < strtotime($fromDate)) {
        setResponse(["err" => 1, "msg" => "Expected return date cannot be before the from date", "alert" => "Expected return date cannot be before the from date"]);
        return false;
    }

    // Overlap check — two open leaves for one driver would make availability ambiguous.
    $sql = "SELECT driverLeaveID FROM `" . $DB->pre . "driver_leave`
            WHERE userID=? AND status=1
              AND (? <= IFNULL(toDate,'9999-12-31'))
              AND (IFNULL(?, '9999-12-31') >= fromDate)";
    $vals  = array($userID, $fromDate, ($toDate != "" ? $toDate : null));
    $types = "iss";
    if ($excludeID) { $sql .= " AND driverLeaveID <> ?"; $vals[] = $excludeID; $types .= "i"; }

    $DB->vals = $vals; $DB->types = $types; $DB->sql = $sql . " LIMIT 1";
    $DB->dbRow();
    if ($DB->numRows > 0) {
        setResponse(["err" => 1, "msg" => "This driver already has leave recorded that overlaps these dates", "alert" => "Overlapping leave already exists for this driver"]);
        return false;
    }
    return true;
}

if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addDriverLeave();
                break;
            case "UPDATE":
                updateDriverLeave();
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "driver_leave", "PK" => "driverLeaveID", "UDIR" => array()));
}
