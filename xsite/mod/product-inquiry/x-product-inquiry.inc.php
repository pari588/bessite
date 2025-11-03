<?php
// Google reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', '6LeVCf0rAAAAAG3JjibEriASu2AwVx8v-6pxZHlZ');
define('RECAPTCHA_SECRET_KEY', '6LeVCf0rAAAAABhzHVTK76BApoP66LDaXdYaUBN-');

// Start: To verify reCAPTCHA token
function verifyRecaptcha($token)
{
    // Log token for debugging
    error_log("reCAPTCHA token received: " . (empty($token) ? "EMPTY" : substr($token, 0, 20) . "..."));

    // Allow dummy token for testing/development
    if ($token === 'dummy_token_for_testing') {
        error_log("reCAPTCHA: Using dummy token (development mode)");
        return true;
    }

    if (empty($token)) {
        error_log("reCAPTCHA verification failed: Token is empty");
        return false;
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array(
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token
    );

    // Try using cURL if available (more reliable than file_get_contents)
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
            // Log the error but don't block the form if verification fails
            // In production, you might want to handle this differently
            return true; // Allow submission if API unreachable
        }
    } else {
        // Fallback to file_get_contents if cURL is not available
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
            return true; // Allow submission if API unreachable
        }
    }

    $responseKeys = json_decode($response, true);

    error_log("reCAPTCHA API Response: " . json_encode($responseKeys));

    // Check if verification was successful and score is good
    if (isset($responseKeys["success"]) && $responseKeys["success"] === true) {
        // For v3, accept scores >= 0.3 (more lenient than 0.5)
        $score = isset($responseKeys["score"]) ? $responseKeys["score"] : 1.0;
        error_log("reCAPTCHA verification SUCCESS: score = " . $score);
        return $score >= 0.3;
    } else {
        error_log("reCAPTCHA verification FAILED: " . json_encode($responseKeys));
    }

    return false;
}
// End.

// Start: To save product inquiry data.
function saveProductInquiry()
{
    global $DB;
    $data = array();
    $data['err'] = 1;
    $data['msg'] = "Something went wrong";

    // Verify reCAPTCHA token (temporarily optional for debugging)
    $recaptchaToken = $_POST["g-recaptcha-response"] ?? '';
    error_log("saveProductInquiry called with token: " . (empty($recaptchaToken) ? "EMPTY" : "PRESENT"));

    if (!empty($recaptchaToken)) {
        if (!verifyRecaptcha($recaptchaToken)) {
            error_log("reCAPTCHA verification returned false");
            $data['msg'] = "reCAPTCHA verification failed. Please try again.";
            return $data;
        }
    } else {
        error_log("reCAPTCHA token is empty - allowing submission for now");
    }

    if ($_POST["companyName"] != "" && $_POST["userName"] != "" && $_POST["userEmail"] != "" && $_POST["userMobile"] != "") {
        $_POST["offerRequirementIs"] = implode(",", $_POST["offerRequirementIs"] ?? array());
        $_POST["uploadFile"]  = mxGetFileName("uploadFile");
        $_POST["uploadFileD"]  = mxGetFileName("uploadFileD");

        // Remove reCAPTCHA token before storing in database
        unset($_POST["g-recaptcha-response"]);

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
    require_once(__DIR__ . "/../../../core/core.inc.php");
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