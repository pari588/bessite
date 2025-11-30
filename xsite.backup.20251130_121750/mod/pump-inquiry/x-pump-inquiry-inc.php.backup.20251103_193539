<?php
// Start: To save pump inquiry data.
function savePumpInquiry()
{
    global $DB;
    $data = array();
    $data['err'] = 1;
    $data['msg'] = "Something went wrong";

    // Validate all required fields
    if ($_POST["userName"] == "" || $_POST["userEmail"] == "" || $_POST["userMobile"] == "" || $_POST["enquiryText"] == "") {
        $data['msg'] = "All fields are required";
        return $data;
    }

    // Validate name (company or personal)
    if (strlen($_POST["userName"]) < 3 || !preg_match("/^[a-zA-Z\s.,'&()\-]{3,100}$/", $_POST["userName"])) {
        $data['msg'] = "Please enter a valid name";
        return $data;
    }

    // Validate email
    if (!filter_var($_POST["userEmail"], FILTER_VALIDATE_EMAIL)) {
        $data['msg'] = "Please enter a valid email address";
        return $data;
    }

    // Validate Indian mobile number
    $mobile = preg_replace('/[\s\-\(\)]/', '', $_POST["userMobile"]);

    // Remove country code if present
    if (substr($mobile, 0, 3) == "+91") {
        $mobile = substr($mobile, 3);
    } else if (substr($mobile, 0, 4) == "0091") {
        $mobile = substr($mobile, 4);
    } else if (substr($mobile, 0, 2) == "91" && strlen($mobile) > 10) {
        $mobile = substr($mobile, 2);
    }

    // Validate the 10-digit number (Indian mobile starts with 6,7,8,9)
    if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
        $data['msg'] = "Please enter a valid Indian mobile number";
        return $data;
    }

    // Store the cleaned mobile number
    $_POST["userMobile"] = $mobile;

    // All validations passed, save to database
    $DB->table = $DB->pre . "pump_inquiry";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $data['err'] = 0;
        $data['msg'] = "Thank you for contacting us! We will get back to you shortly regarding your pump inquiry.";
    }

    return $data;
}
// End.

if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    $MXRES = mxCheckRequest(false, false);
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "savePumpInquiry":
                $MXRES = savePumpInquiry($_POST);
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "pump_inquiry", "PK" => "pumpInquiryID"));
}
