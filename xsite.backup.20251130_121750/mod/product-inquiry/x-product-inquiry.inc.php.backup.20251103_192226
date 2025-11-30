<?php
// Start: To save product inquiry data.
function saveProductInquiry()
{
    global $DB;
    $data = array();
    $data['err'] = 1;
    $data['msg'] = "Someting went wrong";
    if ($_POST["companyName"] != "" && $_POST["userName"] != "" && $_POST["userEmail"] != "" && $_POST["userMobile"] != "") {
        $_POST["offerRequirementIs"] = implode(",", $_POST["offerRequirementIs"] ?? array());
        $_POST["uploadFile"]  = mxGetFileName("uploadFile");
        $_POST["uploadFileD"]  = mxGetFileName("uploadFileD");
        $DB->table = $DB->pre . "product_inquiry";
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
            case "saveProductInquiry":
                $MXRES = saveProductInquiry($_POST);
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "product_inquiry", "PK" => "productInquiryID", "UDIR" => array("uploadFile" => "product-inquiry", "uploadFileD" => "product-inquiry")));
}