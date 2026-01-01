<?php
/**
 * Employee Document Module Controller
 * Manages employee documents like ID proofs, letters, certificates
 */

function addDocument()
{
    global $DB;

    $userID = intval($_POST["userID"]);
    $_POST["userID"] = $userID;
    $_POST["documentType"] = cleanTitle($_POST["documentType"]);
    $_POST["documentName"] = cleanTitle($_POST["documentName"]);
    $_POST["fileName"] = mxGetFileName("fileName");
    if (isset($_POST["remarks"])) $_POST["remarks"] = cleanTitle($_POST["remarks"]);
    if (isset($_POST["validUpto"])) $_POST["validUpto"] = cleanTitle($_POST["validUpto"]);
    $_POST["uploadedBy"] = $_SESSION[SITEURL]["MXID"];

    // Get file size
    if ($_POST["fileName"] && isset($_FILES["fileName"]["size"])) {
        $_POST["fileSize"] = $_FILES["fileName"]["size"];
    }

    $DB->table = $DB->pre . "employee_document";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $documentID = $DB->insertID;
        setResponse(array("err" => 0, "param" => "id=$documentID"));
    } else {
        setResponse(array("err" => 1));
    }
}

function updateDocument()
{
    global $DB;

    $documentID = intval($_POST["documentID"]);

    $_POST["fileName"] = mxGetFileName("fileName");
    if (isset($_POST["documentType"])) $_POST["documentType"] = cleanTitle($_POST["documentType"]);
    if (isset($_POST["documentName"])) $_POST["documentName"] = cleanTitle($_POST["documentName"]);
    if (isset($_POST["remarks"])) $_POST["remarks"] = cleanTitle($_POST["remarks"]);
    if (isset($_POST["validUpto"])) $_POST["validUpto"] = cleanTitle($_POST["validUpto"]);

    $DB->table = $DB->pre . "employee_document";
    $DB->data = $_POST;
    if ($DB->dbUpdate("documentID=?", "i", array($documentID))) {
        setResponse(array("err" => 0, "param" => "id=$documentID"));
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
                addDocument();
                break;
            case "UPDATE":
                updateDocument();
                break;
            case "mxDelFile":
                $param = array("dir" => "employee_document", "tbl" => "employee_document", "pk" => "documentID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "employee_document", "PK" => "documentID", "UDIR" => array("fileName" => "employee_document")));
}
