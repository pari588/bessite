<?php
// Start: To save contact us form data.
function saveContactUsInfo()
{
    global $DB;
    $data = array();
    $data['err'] = 1;
    $data['msg'] = "Someting went wrong";
    if ($_POST["userName"] != "" && $_POST["userLastName"] != "" && $_POST["userEmail"] != "" && $_POST["userMessage"] != "") {
        $DB->table = $DB->pre . "contact_us";
        $DB->data = $_POST;
        if ($DB->dbInsert()) {
            $data['err'] = 0;
            $data['msg'] = "Thank you for contacting us!";

            // ========== SEND EMAIL NOTIFICATIONS ==========
            // Load Brevo email service
            if (!function_exists('sendContactUsEmail')) {
                require_once(ROOTPATH . "/core/brevo.inc.php");
            }

            // Send confirmation email to customer and notification to admin
            $emailSent = sendContactUsEmail($_POST);
            error_log("Contact Us - Email notification sent: " . ($emailSent ? "Yes" : "No"));
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
