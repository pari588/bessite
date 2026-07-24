<?php

function addServiceManufacturer()
{
    global $DB;
    $_POST["logo"] = mxGetFileName("logo");
    $_POST["dateAdded"] = date("Y-m-d H:i:s");
    $DB->table = $DB->pre . "service_manufacturer";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $manufacturerID = $DB->insertID;
        if ($manufacturerID) {
            setResponse(array("err" => 0, "param" => "id=$manufacturerID"));
        }
    } else {
        setResponse(array("err" => 1));
    }
}

function updateServiceManufacturer()
{
    global $DB;
    $manufacturerID = intval($_POST["manufacturerID"]);
    $_POST["logo"] = mxGetFileName("logo");
    $_POST["dateModified"] = date("Y-m-d H:i:s");
    $DB->table = $DB->pre . "service_manufacturer";
    $DB->data = $_POST;
    if ($DB->dbUpdate("manufacturerID=?", "i", array($manufacturerID))) {
        setResponse(array("err" => 0, "param" => "id=$manufacturerID"));
    } else {
        setResponse(array("err" => 1));
    }
}

if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addServiceManufacturer();
                break;
            case "UPDATE":
                updateServiceManufacturer();
                break;
            case "mxDelFile":
                $param = array("dir" => "service-manufacturer", "tbl" => "service_manufacturer", "pk" => "manufacturerID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    setModVars(array("TBL" => "service_manufacturer", "PK" => "manufacturerID", "UDIR" => array("logo" => "service-manufacturer")));
}
