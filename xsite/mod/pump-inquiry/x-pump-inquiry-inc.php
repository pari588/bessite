<?php
// Google reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', '6LeVCf0rAAAAAG3JjibEriASu2AwVx8v-6pxZHlZ');
define('RECAPTCHA_SECRET_KEY', '6LeVCf0rAAAAABhzHVTK76BApoP66LDaXdYaUBN-');

// Start: To verify reCAPTCHA token
function verifyRecaptcha($token)
{
    if (empty($token)) {
        error_log("reCAPTCHA token is empty");
        return false;
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array(
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token
    );

    // Try using cURL if available
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return true; // Allow if API unreachable
        }
    } else {
        // Fallback to file_get_contents
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => 5
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        );

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return true; // Allow if API unreachable
        }
    }

    $responseKeys = json_decode($response, true);

    if (isset($responseKeys["success"]) && $responseKeys["success"] === true) {
        $score = isset($responseKeys["score"]) ? $responseKeys["score"] : 1.0;
        return $score >= 0.3;
    }

    return false;
}
// End.

// Start: To save pump inquiry data.
function savePumpInquiry()
{
    global $DB;
    $data = array();
    $data['err'] = 1;
    $data['msg'] = "Something went wrong";

    // Verify reCAPTCHA token
    $recaptchaToken = $_POST["g-recaptcha-response"] ?? '';
    if (!empty($recaptchaToken)) {
        if (!verifyRecaptcha($recaptchaToken)) {
            $data['msg'] = "reCAPTCHA verification failed. Please try again.";
            return $data;
        }
    }

    // Validate all required fields
    $requiredFields = array('fullName', 'userEmail', 'userMobile', 'applicationTypeID', 'installationTypeID', 'operatingMediumID', 'waterSourceID', 'powerSupplyID', 'city', 'consentGiven');

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $data['msg'] = "Please fill in all required fields";
            return $data;
        }
    }

    // Validate full name
    if (strlen($_POST["fullName"]) < 2 || strlen($_POST["fullName"]) > 100) {
        $data['msg'] = "Please enter a valid full name";
        return $data;
    }

    // Validate email
    if (!filter_var($_POST["userEmail"], FILTER_VALIDATE_EMAIL)) {
        $data['msg'] = "Please enter a valid email address";
        return $data;
    }

    // Validate Indian mobile number
    $mobile = preg_replace('/[\s\-\(\)\+]/', '', $_POST["userMobile"]);

    // Remove country code if present
    if (strpos($mobile, '91') === 0 && strlen($mobile) > 10) {
        $mobile = substr($mobile, 2);
    }

    if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
        $data['msg'] = "Please enter a valid Indian mobile number";
        return $data;
    }

    $_POST["userMobile"] = $mobile;

    // Validate pin code if provided
    if (!empty($_POST["pinCode"]) && (!is_numeric($_POST["pinCode"]) || strlen($_POST["pinCode"]) != 6)) {
        $data['msg'] = "Please enter a valid 6-digit pin code";
        return $data;
    }

    // Validate numeric fields
    if (!empty($_POST["requiredHead"]) && !is_numeric($_POST["requiredHead"])) {
        $data['msg'] = "Required Head must be a number";
        return $data;
    }

    if (!empty($_POST["pumpingDistance"]) && !is_numeric($_POST["pumpingDistance"])) {
        $data['msg'] = "Pumping Distance must be a number";
        return $data;
    }

    if (!empty($_POST["heightDifference"]) && !is_numeric($_POST["heightDifference"])) {
        $data['msg'] = "Height Difference must be a number";
        return $data;
    }

    if (!empty($_POST["operatingHours"]) && (!is_numeric($_POST["operatingHours"]) || $_POST["operatingHours"] > 24)) {
        $data['msg'] = "Operating hours must be between 0 and 24";
        return $data;
    }

    if (!empty($_POST["quantityRequired"]) && (!is_numeric($_POST["quantityRequired"]) || $_POST["quantityRequired"] < 1)) {
        $data['msg'] = "Quantity must be a positive number";
        return $data;
    }

    // Handle file upload if present
    if (isset($_FILES['uploadedFile']) && $_FILES['uploadedFile']['size'] > 0) {
        $uploadFile = mxGetFileName("uploadedFile");
        if (!$uploadFile) {
            $data['msg'] = "File upload failed. Please try again.";
            return $data;
        }
        $_POST["uploadedFile"] = $uploadFile;
    } else {
        $_POST["uploadedFile"] = '';
    }

    // Handle pump types array
    if (!empty($_POST["pumpTypesInterested"]) && is_array($_POST["pumpTypesInterested"])) {
        $_POST["pumpTypesInterested"] = implode(",", $_POST["pumpTypesInterested"]);
    } else {
        $_POST["pumpTypesInterested"] = '';
    }

    // Store reCAPTCHA score if available
    if (!empty($recaptchaToken)) {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $postData = array('secret' => RECAPTCHA_SECRET_KEY, 'response' => $recaptchaToken);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create(array('http' => array('method' => 'POST', 'content' => http_build_query($postData))));
            $response = @file_get_contents($url, false, $context);
        }

        if ($response) {
            $result = json_decode($response, true);
            $_POST["gRecaptchaScore"] = isset($result["score"]) ? $result["score"] : 0;
        }
    }

    // Remove reCAPTCHA token before storing
    unset($_POST["g-recaptcha-response"]);

    // Save to database
    $DB->table = $DB->pre . "pump_inquiry";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $data['err'] = 0;
        $data['msg'] = "Thank you for your inquiry! Our team will contact you shortly with personalized pump solutions.";
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
