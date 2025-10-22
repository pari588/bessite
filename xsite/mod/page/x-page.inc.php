<?php
// Start: To save contact us form data.
function saveContactUsInfo()
{
    global $DB;
    $data = array();
    $data['err'] = 1;
    $data['msg'] = "Someting went wrong";
    if ($_POST["userName"] != "" && $_POST["userLastName"] != "" && $_POST["userEmail"] != "" && $_POST["userSubject"] != "" && $_POST["userMessage"] != "") {
        $DB->table = $DB->pre . "contact_us";
        $DB->data = $_POST;
        if ($DB->dbInsert()) {
            $data['err'] = 0;
            $data['msg'] = "Thank you for contacting us!";
        }
    }
    return $data;
}
// End.

if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    $MXRES = mxCheckRequest(false, false);
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "saveContactUsInfo":
                $MXRES = saveContactUsInfo($_POST);
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "contact_us", "PK" => "userID"));
}
