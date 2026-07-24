<?php
/**
 * HR Email Settings Module Controller
 * Manages email recipients and HRMS settings
 */

// Add email recipient
function addRecipient()
{
    global $DB;

    $_POST["recipientName"] = cleanTitle($_POST["recipientName"]);
    $_POST["recipientEmail"] = cleanTitle($_POST["recipientEmail"]);
    if (isset($_POST["emailTypes"])) {
        $_POST["emailTypes"] = implode(',', $_POST["emailTypes"]);
    }

    $DB->table = $DB->pre . "hr_email_recipients";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $recipientID = $DB->insertID;
        setResponse(array("err" => 0, "param" => "id=$recipientID"));
    } else {
        setResponse(array("err" => 1));
    }
}

// Update email recipient
function updateRecipient()
{
    global $DB;

    $recipientID = intval($_POST["recipientID"]);

    if (isset($_POST["recipientName"])) $_POST["recipientName"] = cleanTitle($_POST["recipientName"]);
    if (isset($_POST["recipientEmail"])) $_POST["recipientEmail"] = cleanTitle($_POST["recipientEmail"]);
    if (isset($_POST["emailTypes"])) {
        $_POST["emailTypes"] = implode(',', $_POST["emailTypes"]);
    }

    $DB->table = $DB->pre . "hr_email_recipients";
    $DB->data = $_POST;
    if ($DB->dbUpdate("recipientID=?", "i", array($recipientID))) {
        setResponse(array("err" => 0, "param" => "id=$recipientID"));
    } else {
        setResponse(array("err" => 1));
    }
}

// Update HRMS settings
function updateSettings()
{
    global $DB;

    $settings = $_POST["settings"] ?? array();

    foreach ($settings as $key => $value) {
        $DB->table = $DB->pre . "hrms_settings";
        $DB->data = array("settingValue" => cleanTitle($value));
        $DB->dbUpdate("settingKey=?", "s", array($key));
    }

    setResponse(array("err" => 0, "alert" => "Settings updated successfully"));
}

// Router
if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    require_once("../../inc/site.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addRecipient();
                break;
            case "UPDATE":
                updateRecipient();
                break;
            case "updateSettings":
                updateSettings();
                break;
            case "mxDelFile":
                $param = array("dir" => "hr-email-settings", "tbl" => "hr_email_recipients", "pk" => "recipientID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "hr_email_recipients", "PK" => "recipientID", "UDIR" => array()));
}
