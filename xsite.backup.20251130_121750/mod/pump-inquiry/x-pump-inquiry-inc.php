<?php
// ============================================================================
// Extended Pump Inquiry Form - Backend Handler
// ============================================================================

// Google reCAPTCHA Configuration (Same as Product Inquiry)
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

/**
 * Convert dropdown IDs to their text labels
 * Maps numeric values back to human-readable strings
 */
function convertDropdownIDsToLabels(&$postData)
{
    // Define all dropdown options (must match the form)
    $dropdownMappings = array(
        'city' => array(
            "1" => "Mumbai",
            "2" => "Pune",
            "3" => "Ahmedabad",
            "4" => "Other"
        ),
        'preferredContactTime' => array(
            "1" => "Morning (6 AM - 12 PM)",
            "2" => "Afternoon (12 PM - 5 PM)",
            "3" => "Evening (5 PM - 10 PM)"
        ),
        'applicationTypeID' => array(
            "1" => "Domestic",
            "2" => "Industrial",
            "3" => "Agricultural",
            "4" => "Commercial",
            "5" => "Sewage",
            "6" => "HVAC",
            "7" => "Firefighting",
            "8" => "Other"
        ),
        'installationTypeID' => array(
            "1" => "Surface",
            "2" => "Submersible",
            "3" => "Booster",
            "4" => "Dewatering",
            "5" => "Openwell",
            "6" => "Borewell"
        ),
        'operatingMediumID' => array(
            "1" => "Clean water",
            "2" => "Muddy water",
            "3" => "Sewage",
            "4" => "Chemical",
            "5" => "Hot water",
            "6" => "Other"
        ),
        'waterSourceID' => array(
            "1" => "Overhead tank",
            "2" => "Underground tank",
            "3" => "Borewell",
            "4" => "River",
            "5" => "Sump",
            "6" => "Other"
        ),
        'powerSupplyID' => array(
            "1" => "Single Phase",
            "2" => "Three Phase"
        ),
        'automationNeeded' => array(
            "1" => "Yes",
            "2" => "No"
        ),
        'preferredBrand' => array(
            "1" => "Crompton",
            "2" => "CG Power",
            "3" => "Kirloskar",
            "4" => "Open to suggestion"
        ),
        'materialPreference' => array(
            "1" => "Cast Iron",
            "2" => "Stainless Steel",
            "3" => "Bronze",
            "4" => "Plastic",
            "5" => "Open to suggestion"
        ),
        'pumpTypesInterested' => array(
            "1" => "Centrifugal",
            "2" => "Jet",
            "3" => "Submersible",
            "4" => "Monoblock",
            "5" => "Borewell",
            "6" => "Booster",
            "7" => "Self-Priming",
            "8" => "Others"
        )
    );

    // Convert each dropdown field
    foreach ($dropdownMappings as $fieldName => $options) {
        if (isset($postData[$fieldName]) && !empty($postData[$fieldName])) {
            $value = $postData[$fieldName];

            // Special handling for pumpTypesInterested (checkbox - comma-separated values)
            if ($fieldName === 'pumpTypesInterested' && is_string($value)) {
                $ids = explode(",", $value);
                $labels = array();
                foreach ($ids as $id) {
                    $id = trim($id);
                    if (isset($options[$id])) {
                        $labels[] = $options[$id];
                    }
                }
                if (!empty($labels)) {
                    $postData[$fieldName] = implode(", ", $labels);
                }
            } else {
                // Standard single-value dropdown
                if (isset($options[$value])) {
                    $postData[$fieldName] = $options[$value];
                }
            }
        }
    }
}

/**
 * Save pump inquiry data
 * Validates all fields and saves to database
 */
function savePumpInquiry()
{
    global $DB;
    $data = array();
    $data['err'] = 1;
    $data['msg'] = "Something went wrong";

    // Debug logging
    file_put_contents("/tmp/pump_inquiry_debug.log", date("Y-m-d H:i:s") . " - savePumpInquiry() called\n", FILE_APPEND);
    file_put_contents("/tmp/pump_inquiry_debug.log", "POST data: " . json_encode($_POST) . "\n", FILE_APPEND);
    file_put_contents("/tmp/pump_inquiry_debug.log", "POST keys: " . implode(", ", array_keys($_POST)) . "\n\n", FILE_APPEND);

    // ========== VERIFY RECAPTCHA TOKEN ==========
    $recaptchaToken = $_POST["g-recaptcha-response"] ?? '';
    error_log("savePumpInquiry called with reCAPTCHA token: " . (empty($recaptchaToken) ? "EMPTY" : "PRESENT"));

    if (!empty($recaptchaToken)) {
        if (!verifyRecaptcha($recaptchaToken)) {
            error_log("reCAPTCHA verification returned false");
            $data['msg'] = "reCAPTCHA verification failed. Please try again.";
            return $data;
        }
    } else {
        error_log("reCAPTCHA token is empty - allowing submission for now");
    }

    // Remove reCAPTCHA token before storing in database
    unset($_POST["g-recaptcha-response"]);

    // ========== VALIDATE REQUIRED FIELDS ==========
    $requiredFields = array('fullName', 'userEmail', 'userMobile', 'city', 'applicationTypeID', 'installationTypeID', 'operatingMediumID', 'waterSourceID', 'powerSupplyID', 'consentGiven');

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field]) || $_POST[$field] == "") {
            $data['msg'] = "All required fields (*) must be filled";
            return $data;
        }
    }

    // ========== VALIDATE FULL NAME ==========
    $fullName = trim($_POST["fullName"]);
    if (strlen($fullName) < 3) {
        $data['msg'] = "Full name must be at least 3 characters long";
        return $data;
    }
    if (!preg_match("/^[a-zA-Z\s.,'&()\-]{3,100}$/", $fullName)) {
        $data['msg'] = "Full name contains invalid characters";
        return $data;
    }

    // ========== VALIDATE EMAIL ==========
    $email = trim($_POST["userEmail"]);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $data['msg'] = "Please enter a valid email address";
        return $data;
    }

    // ========== VALIDATE INDIAN MOBILE NUMBER ==========
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
        $data['msg'] = "Please enter a valid Indian mobile number (10 digits starting with 6, 7, 8, or 9)";
        return $data;
    }

    // Store the cleaned mobile number
    $_POST["userMobile"] = $mobile;

    // ========== VALIDATE PIN CODE (IF PROVIDED) ==========
    if (!empty($_POST["pinCode"])) {
        $pinCode = trim($_POST["pinCode"]);
        if (!preg_match('/^[0-9]{6}$/', $pinCode)) {
            $data['msg'] = "Pin code must be 6 digits";
            return $data;
        }
    }

    // ========== VALIDATE NUMERIC FIELDS (IF PROVIDED) ==========
    if (!empty($_POST["requiredHead"])) {
        if (!is_numeric($_POST["requiredHead"]) || $_POST["requiredHead"] < 0) {
            $data['msg'] = "Required head must be a positive number";
            return $data;
        }
    }

    if (!empty($_POST["pumpingDistance"])) {
        if (!is_numeric($_POST["pumpingDistance"]) || $_POST["pumpingDistance"] < 0) {
            $data['msg'] = "Pumping distance must be a positive number";
            return $data;
        }
    }

    if (!empty($_POST["heightDifference"])) {
        if (!is_numeric($_POST["heightDifference"]) || $_POST["heightDifference"] < 0) {
            $data['msg'] = "Height difference must be a positive number";
            return $data;
        }
    }

    if (!empty($_POST["operatingHours"])) {
        if (!is_numeric($_POST["operatingHours"]) || $_POST["operatingHours"] < 0 || $_POST["operatingHours"] > 24) {
            $data['msg'] = "Operating hours must be between 0 and 24";
            return $data;
        }
    }

    if (!empty($_POST["quantityRequired"])) {
        if (!is_numeric($_POST["quantityRequired"]) || $_POST["quantityRequired"] < 1) {
            $data['msg'] = "Quantity required must be at least 1";
            return $data;
        }
    }

    // ========== HANDLE FILE UPLOAD ==========
    if (isset($_FILES["uploadedFile"]) && $_FILES["uploadedFile"]["error"] == 0) {
        $uploadDir = UPLOADPATH . "/pump-inquiry/";

        // Create directory if not exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = $_FILES["uploadedFile"]["name"];
        $fileTmp = $_FILES["uploadedFile"]["tmp_name"];
        $fileSize = $_FILES["uploadedFile"]["size"];

        // Validate file type
        $allowedTypes = array('image/jpeg', 'image/png', 'application/pdf');
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $fileTmp);
        finfo_close($fileInfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $data['msg'] = "Only JPG, PNG, and PDF files are allowed";
            return $data;
        }

        // Validate file size (5MB max)
        if ($fileSize > 5 * 1024 * 1024) {
            $data['msg'] = "File size must not exceed 5MB";
            return $data;
        }

        // Generate unique filename
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = "pump_inquiry_" . time() . "_" . uniqid() . "." . $fileExt;
        $uploadPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmp, $uploadPath)) {
            $_POST["uploadedFile"] = "/uploads/pump-inquiry/" . $newFileName;
        } else {
            $data['msg'] = "Failed to upload file";
            return $data;
        }
    } else {
        // Remove file key if no file uploaded
        unset($_POST["uploadedFile"]);
    }

    // ========== SANITIZE TEXT FIELDS ==========
    $textFields = array('companyName', 'address', 'purposeOfPump', 'requiredDischarge', 'pipeSize', 'existingPumpModel', 'motorRating');
    foreach ($textFields as $field) {
        if (isset($_POST[$field])) {
            $_POST[$field] = trim($_POST[$field]);
        }
    }

    // ========== HANDLE PUMP TYPES (CHECKBOX ARRAY) ==========
    if (isset($_POST["pumpTypesInterested"]) && is_array($_POST["pumpTypesInterested"])) {
        $_POST["pumpTypesInterested"] = implode(", ", $_POST["pumpTypesInterested"]);
    } else {
        unset($_POST["pumpTypesInterested"]);
    }

    // ========== VALIDATE CONSENT ==========
    if (empty($_POST["consentGiven"]) || $_POST["consentGiven"] != "1") {
        $data['msg'] = "You must give consent to proceed";
        return $data;
    }

    // ========== CONVERT DROPDOWN IDs TO LABELS ==========
    $postData = $_POST;
    convertDropdownIDsToLabels($postData);

    // ========== SAVE TO DATABASE ==========
    try {
        // Use bombay_pump_inquiry table directly (custom naming, no mx_ prefix)
        $DB->table = "bombay_pump_inquiry";
        $DB->data = $postData;

        file_put_contents("/tmp/pump_inquiry_debug.log", "Attempting to insert with: " . json_encode($DB->data) . "\n", FILE_APPEND);

        if ($DB->dbInsert()) {
            $data['err'] = 0;
            $data['msg'] = "Thank you for submitting your pump inquiry! Our team will review your information and contact you shortly to discuss your requirements.";
            file_put_contents("/tmp/pump_inquiry_debug.log", "INSERT SUCCESS - ID: " . $DB->lastID . "\n\n", FILE_APPEND);

            // ========== SEND EMAIL NOTIFICATIONS ==========
            // Load Brevo email service
            if (!function_exists('sendPumpInquiryEmail')) {
                require_once(ROOTPATH . "/core/brevo.inc.php");
            }

            // Send confirmation email to customer and notification to admin
            $emailSent = sendPumpInquiryEmail($_POST);
            error_log("Pump Inquiry - Email notification sent: " . ($emailSent ? "Yes" : "No"));
        } else {
            $data['msg'] = "Failed to save inquiry. Please try again later.";
            file_put_contents("/tmp/pump_inquiry_debug.log", "INSERT FAILED - Error: " . $DB->error . "\n\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        $data['msg'] = "An error occurred while processing your inquiry";
        error_log("Pump Inquiry Error: " . $e->getMessage());
        file_put_contents("/tmp/pump_inquiry_debug.log", "EXCEPTION: " . $e->getMessage() . "\n\n", FILE_APPEND);
    }

    return $data;
}
// End savePumpInquiry()

// ============================================================================
// REQUEST HANDLING
// ============================================================================

if (isset($_POST["xAction"])) {
    // This is an AJAX POST request
    // Load core if not already loaded
    if (!function_exists("mxGetFileName")) {
        require_once("../../../core/core.inc.php");
        // Load site-specific common if not already loaded
        if (!function_exists("setModVars")) {
            require_once("../core-site/common.inc.php");
        }
    }

    $MXRES = mxCheckRequest(false, false);

    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "savePumpInquiry":
                $MXRES = savePumpInquiry();
                break;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($MXRES);
} else {
    // This is a page load request
    if (function_exists("setModVars")) {
        setModVars(array("TBL" => "pump_inquiry", "PK" => "pumpInquiryID"));
    }
}
?>
