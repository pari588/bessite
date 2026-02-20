<?php
/**
 * Brevo Email Service Integration
 * Handles email notifications for form submissions
 * Uses Brevo (formerly Sendinblue) REST API v3
 */

class BrevoEmailService
{
    private $apiKey;
    private $apiUrl = "https://api.brevo.com/v3";
    private $senderEmail;
    private $senderName;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiKey = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
        $this->senderEmail = defined('BREVO_SENDER_EMAIL') ? BREVO_SENDER_EMAIL : 'noreply@bombayengg.net';
        $this->senderName = defined('BREVO_SENDER_NAME') ? BREVO_SENDER_NAME : 'Bombay Engineering Syndicate';
    }

    /**
     * Check if Brevo is configured
     */
    public function isConfigured()
    {
        return !empty($this->apiKey);
    }

    /**
     * Send email via Brevo API
     *
     * @param array $params - Email parameters
     *   - to: array of recipient emails ['email' => 'email@example.com', 'name' => 'John Doe']
     *   - subject: Email subject
     *   - htmlContent: HTML email body
     *   - textContent: Plain text email body (optional)
     *   - replyTo: Reply-to email (optional)
     *   - cc: CC recipients (optional)
     *   - bcc: BCC recipients (optional)
     *   - tags: Email tags for tracking (optional)
     *   - attachment: array of attachments ['content' => base64, 'name' => filename] (optional)
     *
     * @return array - Response array with 'success' => true/false and 'messageId'
     */
    public function sendEmail($params)
    {
        $response = array(
            'success' => false,
            'messageId' => null,
            'error' => 'API not configured'
        );

        // Check if API is configured
        if (!$this->isConfigured()) {
            error_log("Brevo: API key not configured");
            return $response;
        }

        try {
            // Validate required parameters
            if (empty($params['to']) || empty($params['subject']) || empty($params['htmlContent'])) {
                $response['error'] = 'Missing required parameters: to, subject, htmlContent';
                error_log("Brevo: " . $response['error']);
                return $response;
            }

            // Build request body
            $emailData = array(
                'sender' => array(
                    'email' => $this->senderEmail,
                    'name' => $this->senderName
                ),
                'to' => $params['to'],
                'subject' => $params['subject'],
                'htmlContent' => $params['htmlContent']
            );

            // Add optional fields
            if (!empty($params['textContent'])) {
                $emailData['textContent'] = $params['textContent'];
            }

            if (!empty($params['replyTo'])) {
                $emailData['replyTo'] = $params['replyTo'];
            }

            if (!empty($params['cc'])) {
                $emailData['cc'] = $params['cc'];
            }

            if (!empty($params['bcc'])) {
                $emailData['bcc'] = $params['bcc'];
            }

            if (!empty($params['tags'])) {
                $emailData['tags'] = $params['tags'];
            }

            // Add attachments if provided
            if (!empty($params['attachment'])) {
                $emailData['attachment'] = $params['attachment'];
            }

            // Make API request
            $curlResponse = $this->makeApiRequest('/smtp/email', 'POST', $emailData);

            if ($curlResponse['success']) {
                $response['success'] = true;
                $response['messageId'] = isset($curlResponse['data']['messageId']) ? $curlResponse['data']['messageId'] : null;
                error_log("Brevo: Email sent successfully. MessageId: " . $response['messageId']);
            } else {
                $response['error'] = isset($curlResponse['error']) ? $curlResponse['error'] : 'Unknown error';
                error_log("Brevo: Email send failed - " . $response['error']);
            }
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            error_log("Brevo: Exception - " . $e->getMessage());
        }

        return $response;
    }

    /**
     * Make HTTP request to Brevo API
     */
    private function makeApiRequest($endpoint, $method = 'GET', $data = null)
    {
        $response = array(
            'success' => false,
            'data' => null,
            'error' => null
        );

        try {
            $url = $this->apiUrl . $endpoint;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            // Set request method
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } elseif ($method === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } elseif ($method === 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            }

            // Set headers
            $headers = array(
                'Content-Type: application/json',
                'api-key: ' . $this->apiKey
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // Execute request
            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Check for cURL errors
            if ($curlError) {
                $response['error'] = "cURL error: " . $curlError;
                error_log("Brevo API cURL error: " . $curlError);
                return $response;
            }

            // Parse response
            $responseData = json_decode($responseBody, true);

            // Check HTTP status code
            if ($httpCode >= 200 && $httpCode < 300) {
                $response['success'] = true;
                $response['data'] = $responseData;
            } else {
                $response['error'] = isset($responseData['message']) ? $responseData['message'] : "HTTP Error: " . $httpCode;
                error_log("Brevo API HTTP " . $httpCode . ": " . $response['error']);
            }
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            error_log("Brevo API Exception: " . $e->getMessage());
        }

        return $response;
    }
}

/**
 * Helper function to get Brevo service instance
 */
function getBrevoService()
{
    static $brevoService = null;
    if ($brevoService === null) {
        $brevoService = new BrevoEmailService();
    }
    return $brevoService;
}

/**
 * Send pump inquiry notification to admin only (no customer confirmation)
 */
function sendPumpInquiryEmail($inquiryData)
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        error_log("Brevo not configured - skipping pump inquiry email");
        return true; // Don't block the form submission
    }

    try {
        // Send admin notification only
        sendPumpInquiryAdminEmail($inquiryData);
        return true;
    } catch (Exception $e) {
        error_log("Pump Inquiry Email Error: " . $e->getMessage());
        return true; // Don't block form submission
    }
}

/**
 * Send pump inquiry notification to admin
 */
function sendPumpInquiryAdminEmail($inquiryData)
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        return true;
    }

    try {
        // Get admin email from settings (fallback to sender email)
        $adminEmail = defined('ADMIN_NOTIFICATION_EMAIL') ? ADMIN_NOTIFICATION_EMAIL : BREVO_SENDER_EMAIL;

        $htmlContent = buildPumpInquiryAdminEmail($inquiryData);

        $emailParams = array(
            'to' => array(
                array(
                    'email' => $adminEmail,
                    'name' => 'Admin'
                )
            ),
            'subject' => 'New Pump Inquiry Submission - ' . $inquiryData['fullName'],
            'htmlContent' => $htmlContent,
            'tags' => array('pump-inquiry', 'admin-notification')
        );

        return $brevo->sendEmail($emailParams)['success'];
    } catch (Exception $e) {
        error_log("Pump Inquiry Admin Email Error: " . $e->getMessage());
        return true;
    }
}

/**
 * Send product inquiry notification to admin only (no customer confirmation)
 */
function sendProductInquiryEmail($inquiryData)
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        error_log("Brevo not configured - skipping product inquiry email");
        return true;
    }

    try {
        // Send admin notification only
        sendProductInquiryAdminEmail($inquiryData);
        return true;
    } catch (Exception $e) {
        error_log("Product Inquiry Email Error: " . $e->getMessage());
        return true;
    }
}

/**
 * Send product inquiry notification to admin
 */
function sendProductInquiryAdminEmail($inquiryData)
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        return true;
    }

    try {
        $htmlContent = buildProductInquiryAdminEmail($inquiryData);

        $emailParams = array(
            'to' => array(
                array(
                    'email' => 'info@bombayengg.net',
                    'name' => 'Bombay Engineering'
                ),
                array(
                    'email' => 'manishbeskkc@gmail.com',
                    'name' => 'Manish'
                )
            ),
            'subject' => 'New Motor Inquiry - ' . $inquiryData['userName'],
            'htmlContent' => $htmlContent,
            'tags' => array('product-inquiry', 'admin-notification')
        );

        return $brevo->sendEmail($emailParams)['success'];
    } catch (Exception $e) {
        error_log("Product Inquiry Admin Email Error: " . $e->getMessage());
        return true;
    }
}

/**
 * Send contact us notification to admin only (no customer confirmation)
 */
function sendContactUsEmail($contactData)
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        error_log("Brevo not configured - skipping contact us email");
        return true;
    }

    try {
        // Send admin notification only
        sendContactUsAdminEmail($contactData);
        return true;
    } catch (Exception $e) {
        error_log("Contact Us Email Error: " . $e->getMessage());
        return true;
    }
}

/**
 * Send contact us notification to admin
 */
function sendContactUsAdminEmail($contactData)
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        return true;
    }

    try {
        $adminEmail = defined('ADMIN_NOTIFICATION_EMAIL') ? ADMIN_NOTIFICATION_EMAIL : BREVO_SENDER_EMAIL;
        $senderName = isset($contactData['userName']) ? $contactData['userName'] . ' ' . $contactData['userLastName'] : 'Contact Form';

        $htmlContent = buildContactUsAdminEmail($contactData);

        $emailParams = array(
            'to' => array(
                array(
                    'email' => $adminEmail,
                    'name' => 'Admin'
                )
            ),
            'subject' => 'New Contact Form Submission - ' . $senderName,
            'htmlContent' => $htmlContent,
            'tags' => array('contact-us', 'admin-notification')
        );

        return $brevo->sendEmail($emailParams)['success'];
    } catch (Exception $e) {
        error_log("Contact Us Admin Email Error: " . $e->getMessage());
        return true;
    }
}

/**
 * Build HTML email template for pump inquiry confirmation
 */
function buildPumpInquiryConfirmationEmail($data)
{
    $recipientName = isset($data['fullName']) ? htmlspecialchars($data['fullName']) : 'Valued Customer';
    $applicationTypeID = isset($data['applicationTypeID']) ? htmlspecialchars($data['applicationTypeID']) : 'N/A';
    $installationTypeID = isset($data['installationTypeID']) ? htmlspecialchars($data['installationTypeID']) : 'N/A';
    $preferredContactTime = isset($data['preferredContactTime']) ? htmlspecialchars($data['preferredContactTime']) : 'Any time';
    $currentDate = date("Y-m-d H:i:s");

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #157bba; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
        .highlight { background-color: #fff3cd; padding: 10px; border-left: 4px solid #157bba; margin: 15px 0; }
        table { width: 100%; margin: 15px 0; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; width: 30%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Pump Inquiry Confirmation</h2>
        </div>
        <div class="content">
            <p>Dear {$recipientName},</p>

            <p>Thank you for submitting your pump inquiry to Bombay Engineering Syndicate. We have successfully received your submission and our team will review your requirements shortly.</p>

            <div class="highlight">
                <strong>Your inquiry has been recorded with the following details:</strong>
            </div>

            <table>
                <tr>
                    <td class="label">Inquiry ID:</td>
                    <td>#INQUIRY_ID</td>
                </tr>
                <tr>
                    <td class="label">Submitted on:</td>
                    <td>{$currentDate}</td>
                </tr>
                <tr>
                    <td class="label">Application Type:</td>
                    <td>{$applicationTypeID}</td>
                </tr>
                <tr>
                    <td class="label">Installation Type:</td>
                    <td>{$installationTypeID}</td>
                </tr>
                <tr>
                    <td class="label">Preferred Contact:</td>
                    <td>{$preferredContactTime}</td>
                </tr>
            </table>

            <p><strong>What happens next?</strong></p>
            <ul>
                <li>Our technical team will review your requirements</li>
                <li>We will contact you at the provided phone number or email</li>
                <li>We will suggest the most suitable pump solution for your needs</li>
                <li>Timeline: Usually within 24-48 business hours</li>
            </ul>

            <p>If you have any urgent questions, please feel free to call us directly or reply to this email.</p>

            <p>Best regards,<br>
            <strong>Bombay Engineering Syndicate Team</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated confirmation email. Please do not reply directly to this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Build HTML email template for pump inquiry admin notification
 */
function buildPumpInquiryAdminEmail($data)
{
    $customerName = isset($data['fullName']) ? htmlspecialchars($data['fullName']) : 'Unknown';
    $customerEmail = isset($data['userEmail']) ? htmlspecialchars($data['userEmail']) : 'N/A';
    $customerMobile = isset($data['userMobile']) ? htmlspecialchars($data['userMobile']) : 'N/A';

    // Pre-assign all variables to avoid ?? operator issues in heredoc
    $fullName = isset($data['fullName']) ? htmlspecialchars($data['fullName']) : 'N/A';
    $companyName = isset($data['companyName']) ? htmlspecialchars($data['companyName']) : 'N/A';
    $userEmail = isset($data['userEmail']) ? htmlspecialchars($data['userEmail']) : 'N/A';
    $userMobile = isset($data['userMobile']) ? htmlspecialchars($data['userMobile']) : 'N/A';
    $city = isset($data['city']) ? htmlspecialchars($data['city']) : 'N/A';
    $preferredContactTime = isset($data['preferredContactTime']) ? htmlspecialchars($data['preferredContactTime']) : 'Any time';
    $applicationTypeID = isset($data['applicationTypeID']) ? htmlspecialchars($data['applicationTypeID']) : 'N/A';
    $installationTypeID = isset($data['installationTypeID']) ? htmlspecialchars($data['installationTypeID']) : 'N/A';
    $operatingMediumID = isset($data['operatingMediumID']) ? htmlspecialchars($data['operatingMediumID']) : 'N/A';
    $waterSourceID = isset($data['waterSourceID']) ? htmlspecialchars($data['waterSourceID']) : 'N/A';
    $powerSupplyID = isset($data['powerSupplyID']) ? htmlspecialchars($data['powerSupplyID']) : 'N/A';
    $requiredHead = isset($data['requiredHead']) ? htmlspecialchars($data['requiredHead']) : 'N/A';
    $requiredDischarge = isset($data['requiredDischarge']) ? htmlspecialchars($data['requiredDischarge']) : 'N/A';

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        .header { background-color: #157bba; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .section { margin: 20px 0; padding: 15px; background-color: white; border-left: 4px solid #157bba; }
        .section-title { font-weight: bold; color: #157bba; margin-bottom: 10px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; width: 25%; color: #157bba; }
        .important { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ff9800; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Pump Inquiry Received</h2>
        </div>
        <div class="content">
            <div class="important">
                <strong>New inquiry from: {$customerName}</strong><br>
                Email: {$customerEmail} | Phone: {$customerMobile}
            </div>

            <div class="section">
                <div class="section-title">CUSTOMER INFORMATION</div>
                <table>
                    <tr><td class="label">Name:</td><td>{$fullName}</td></tr>
                    <tr><td class="label">Company:</td><td>{$companyName}</td></tr>
                    <tr><td class="label">Email:</td><td>{$userEmail}</td></tr>
                    <tr><td class="label">Mobile:</td><td>{$userMobile}</td></tr>
                    <tr><td class="label">City:</td><td>{$city}</td></tr>
                    <tr><td class="label">Contact Time:</td><td>{$preferredContactTime}</td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">APPLICATION DETAILS</div>
                <table>
                    <tr><td class="label">Application Type:</td><td>{$applicationTypeID}</td></tr>
                    <tr><td class="label">Installation Type:</td><td>{$installationTypeID}</td></tr>
                    <tr><td class="label">Operating Medium:</td><td>{$operatingMediumID}</td></tr>
                    <tr><td class="label">Water Source:</td><td>{$waterSourceID}</td></tr>
                    <tr><td class="label">Power Supply:</td><td>{$powerSupplyID}</td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">TECHNICAL REQUIREMENTS</div>
                <table>
                    <tr><td class="label">Required Head:</td><td>{$requiredHead}</td></tr>
                    <tr><td class="label">Required Discharge:</td><td>{$requiredDischarge}</td></tr>
                </table>
            </div>

            <p style="margin-top: 20px;"><strong>Action Required:</strong> Review the inquiry details and contact the customer to discuss the pump solution.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Build HTML email template for product inquiry confirmation
 */
function buildProductInquiryConfirmationEmail($data)
{
    $recipientName = isset($data['userName']) ? htmlspecialchars($data['userName']) : 'Valued Customer';

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #157bba; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
        .highlight { background-color: #fff3cd; padding: 10px; border-left: 4px solid #157bba; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Motor Inquiry Confirmation</h2>
        </div>
        <div class="content">
            <p>Dear {$recipientName},</p>

            <p>Thank you for submitting your motor inquiry to Bombay Engineering Syndicate. We have received your request and our technical team will get back to you shortly with relevant solutions.</p>

            <div class="highlight">
                <strong>Your inquiry has been recorded and will be processed shortly.</strong>
            </div>

            <p><strong>Next Steps:</strong></p>
            <ul>
                <li>Our team will review your motor requirements</li>
                <li>We will provide technical specifications and pricing</li>
                <li>You will receive a response within 24-48 hours</li>
            </ul>

            <p>Thank you for choosing Bombay Engineering Syndicate for your motor needs.</p>

            <p>Best regards,<br>
            <strong>Bombay Engineering Syndicate Team</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated confirmation email. Please do not reply directly to this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Build HTML email template for product inquiry admin notification
 */
function buildProductInquiryAdminEmail($data)
{
    $customerName = isset($data['userName']) ? htmlspecialchars($data['userName']) : 'Unknown';
    $customerEmail = isset($data['userEmail']) ? htmlspecialchars($data['userEmail']) : 'N/A';

    // Pre-assign all variables
    $userName = isset($data['userName']) ? htmlspecialchars($data['userName']) : 'N/A';
    $companyName = isset($data['companyName']) ? htmlspecialchars($data['companyName']) : 'N/A';
    $userEmail = isset($data['userEmail']) ? htmlspecialchars($data['userEmail']) : 'N/A';
    $userMobile = isset($data['userMobile']) ? htmlspecialchars($data['userMobile']) : 'N/A';
    $makeOfMotor = isset($data['makeOfMotor']) ? htmlspecialchars($data['makeOfMotor']) : 'N/A';
    $kw = isset($data['kw']) ? htmlspecialchars($data['kw']) : 'N/A';
    $hp = isset($data['hp']) ? htmlspecialchars($data['hp']) : 'N/A';
    $rpm = isset($data['rpm']) ? htmlspecialchars($data['rpm']) : 'N/A';
    $otherSpec = isset($data['otherSpec']) && $data['otherSpec'] != '' ? htmlspecialchars($data['otherSpec']) : '';

    // Resolve IDs to human-readable labels
    $dutyArr = array("1" => "S1", "2" => "S2", "3" => "S3", "4" => "S4", "5" => "Other");
    $typeOfMotorArr = array("1" => "TEFC - SAFE AREA STANDARD", "2" => "FLAME PROOF - GAS GROUP IIA/IIB", "3" => "FLAME PROOF - GAS GROUP IIC", "4" => "INCREASED SAFETY - Ex'e'", "5" => "NON SPARKING - Ex'n'", "6" => "Other");
    $rotorTypeArr = array("1" => "SQUIRREL CAGE", "2" => "SLIP RING");
    $voltageArr = array("1" => "415", "2" => "380", "3" => "440", "4" => "460", "5" => "480", "6" => "Other");
    $frequencyArr = array("1" => "50 Hz", "2" => "60 Hz");
    $efficiencyArr = array("1" => "IE2", "2" => "IE3", "3" => "IE4");
    $MountingArr = array("1" => "B3 - FOOT", "2" => "B5 - FLANGE", "3" => "B35 - FOOT CUM FLANGE", "4" => "V1 - VERTICAL FLANGE", "5" => "B14 - FACE MOUNTED", "6" => "Other");
    $shaftExtensionArr = array("1" => "SINGLE", "2" => "DOUBLE", "3" => "Other");
    $expectedDeliveryTimeArr = array("1" => "EX.STOCK", "2" => "1-4 WEEKS", "3" => "4-8 WEEKS", "4" => "MORE THAN 8 WEEKS", "5" => "Other");
    $offerRequirementIsArr = array("1" => "Estimated", "2" => "Firm");
    $requirementForRplcArr = array("1" => "Yes", "2" => "No");
    $poleArr = array("1" => "2", "2" => "4", "3" => "6", "4" => "8");

    $duty = isset($data['dutyID']) && $data['dutyID'] ? ($dutyArr[$data['dutyID']] ?? 'N/A') : 'N/A';
    $dutyOther = isset($data['dutyOther']) && $data['dutyOther'] != '' ? ' (' . htmlspecialchars($data['dutyOther']) . ')' : '';
    $typeOfMotor = isset($data['typeOfMotorID']) && $data['typeOfMotorID'] ? ($typeOfMotorArr[$data['typeOfMotorID']] ?? 'N/A') : 'N/A';
    $typeOfMotorOther = isset($data['typeOfMotorOther']) && $data['typeOfMotorOther'] != '' ? ' (' . htmlspecialchars($data['typeOfMotorOther']) . ')' : '';
    $rotorType = isset($data['rotorTypeID']) && $data['rotorTypeID'] ? ($rotorTypeArr[$data['rotorTypeID']] ?? 'N/A') : 'N/A';
    $voltage = isset($data['voltageID']) && $data['voltageID'] ? ($voltageArr[$data['voltageID']] ?? 'N/A') : 'N/A';
    $voltageOther = isset($data['voltageOther']) && $data['voltageOther'] != '' ? ' (' . htmlspecialchars($data['voltageOther']) . ')' : '';
    $frequency = isset($data['frequencyID']) && $data['frequencyID'] ? ($frequencyArr[$data['frequencyID']] ?? 'N/A') : 'N/A';
    $efficiency = isset($data['efficiencyID']) && $data['efficiencyID'] ? ($efficiencyArr[$data['efficiencyID']] ?? 'N/A') : 'N/A';
    $mounting = isset($data['mountingID']) && $data['mountingID'] ? ($MountingArr[$data['mountingID']] ?? 'N/A') : 'N/A';
    $mountingOther = isset($data['mountingOther']) && $data['mountingOther'] != '' ? ' (' . htmlspecialchars($data['mountingOther']) . ')' : '';
    $shaftExtension = isset($data['shaftExtensionID']) && $data['shaftExtensionID'] ? ($shaftExtensionArr[$data['shaftExtensionID']] ?? 'N/A') : 'N/A';
    $shaftExtensionOther = isset($data['shaftExtensionOther']) && $data['shaftExtensionOther'] != '' ? ' (' . htmlspecialchars($data['shaftExtensionOther']) . ')' : '';
    $expectedDeliveryTime = isset($data['expectedDeliveryTimeID']) && $data['expectedDeliveryTimeID'] ? ($expectedDeliveryTimeArr[$data['expectedDeliveryTimeID']] ?? 'N/A') : 'N/A';
    $expectedDeliveryTimeOther = isset($data['expectedDeliveryTimeOther']) && $data['expectedDeliveryTimeOther'] != '' ? ' (' . htmlspecialchars($data['expectedDeliveryTimeOther']) . ')' : '';

    // Offer requirement
    $offerReq = 'N/A';
    if (isset($data['offerRequirementIs']) && $data['offerRequirementIs'] != '') {
        $offerParts = explode(',', $data['offerRequirementIs']);
        $offerLabels = array();
        foreach ($offerParts as $p) {
            $p = trim($p);
            if (isset($offerRequirementIsArr[$p])) $offerLabels[] = $offerRequirementIsArr[$p];
        }
        $offerReq = !empty($offerLabels) ? implode(', ', $offerLabels) : 'N/A';
    }

    // Replacement details
    $isReplacement = isset($data['requirementIsForRplc']) && $data['requirementIsForRplc'] == '1';
    $replacementLabel = $isReplacement ? 'Yes' : 'No';
    $replacementHtml = '';
    if ($isReplacement) {
        $makeOfMotorD = isset($data['makeOfMotorD']) && $data['makeOfMotorD'] != '' ? htmlspecialchars($data['makeOfMotorD']) : 'N/A';
        $kwD = isset($data['kwD']) && $data['kwD'] != '' ? htmlspecialchars($data['kwD']) : 'N/A';
        $hpD = isset($data['hpD']) && $data['hpD'] != '' ? htmlspecialchars($data['hpD']) : 'N/A';
        $mountingD = isset($data['mounting']) && $data['mounting'] != '' ? htmlspecialchars($data['mounting']) : 'N/A';
        $pole = isset($data['poleID']) && $data['poleID'] ? ($poleArr[$data['poleID']] ?? 'N/A') : 'N/A';
        $application = isset($data['application']) && $data['application'] != '' ? htmlspecialchars($data['application']) : 'N/A';

        $replacementHtml = '
            <div class="section">
                <div class="section-title">EXISTING MOTOR DETAILS (REPLACEMENT)</div>
                <table>
                    <tr><td class="label">Make:</td><td>' . $makeOfMotorD . '</td></tr>
                    <tr><td class="label">KW/HP:</td><td>' . $kwD . ' KW / ' . $hpD . ' HP</td></tr>
                    <tr><td class="label">Mounting:</td><td>' . $mountingD . '</td></tr>
                    <tr><td class="label">Pole:</td><td>' . $pole . '</td></tr>
                    <tr><td class="label">Application:</td><td>' . $application . '</td></tr>
                </table>
            </div>';
    }

    // Other specification section
    $otherSpecHtml = '';
    if ($otherSpec != '') {
        $otherSpecHtml = '
            <div class="section">
                <div class="section-title">OTHER SPECIFICATION</div>
                <p style="margin: 0; padding: 5px 0;">' . $otherSpec . '</p>
            </div>';
    }

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        .header { background-color: #157bba; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .section { margin: 20px 0; padding: 15px; background-color: white; border-left: 4px solid #157bba; }
        .section-title { font-weight: bold; color: #157bba; margin-bottom: 10px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; width: 30%; color: #157bba; }
        .important { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ff9800; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Motor Inquiry Received</h2>
        </div>
        <div class="content">
            <div class="important">
                <strong>New inquiry from: {$customerName}</strong><br>
                Email: {$customerEmail}
            </div>

            <div class="section">
                <div class="section-title">CUSTOMER INFORMATION</div>
                <table>
                    <tr><td class="label">Name:</td><td>{$userName}</td></tr>
                    <tr><td class="label">Company:</td><td>{$companyName}</td></tr>
                    <tr><td class="label">Email:</td><td>{$userEmail}</td></tr>
                    <tr><td class="label">Mobile:</td><td>{$userMobile}</td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">MOTOR SPECIFICATIONS</div>
                <table>
                    <tr><td class="label">Make of Motor:</td><td>{$makeOfMotor}</td></tr>
                    <tr><td class="label">KW / HP:</td><td>{$kw} KW / {$hp} HP</td></tr>
                    <tr><td class="label">RPM:</td><td>{$rpm}</td></tr>
                    <tr><td class="label">Duty:</td><td>{$duty}{$dutyOther}</td></tr>
                    <tr><td class="label">Type of Motor:</td><td>{$typeOfMotor}{$typeOfMotorOther}</td></tr>
                    <tr><td class="label">Rotor Type:</td><td>{$rotorType}</td></tr>
                    <tr><td class="label">Voltage:</td><td>{$voltage}{$voltageOther}</td></tr>
                    <tr><td class="label">Frequency:</td><td>{$frequency}</td></tr>
                    <tr><td class="label">Efficiency:</td><td>{$efficiency}</td></tr>
                    <tr><td class="label">Mounting:</td><td>{$mounting}{$mountingOther}</td></tr>
                    <tr><td class="label">Shaft Extension:</td><td>{$shaftExtension}{$shaftExtensionOther}</td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">COMMERCIAL</div>
                <table>
                    <tr><td class="label">Expected Delivery:</td><td>{$expectedDeliveryTime}{$expectedDeliveryTimeOther}</td></tr>
                    <tr><td class="label">Offer Requirement:</td><td>{$offerReq}</td></tr>
                    <tr><td class="label">For Replacement:</td><td>{$replacementLabel}</td></tr>
                </table>
            </div>

            {$replacementHtml}

            {$otherSpecHtml}

            <p style="margin-top: 20px;"><strong>Action Required:</strong> Review the motor specification inquiry and contact the customer with a quotation.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Build HTML email template for contact us confirmation
 */
function buildContactUsConfirmationEmail($data)
{
    $recipientName = isset($data['userName']) ? htmlspecialchars($data['userName']) : 'Valued Customer';

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #157bba; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
        .highlight { background-color: #fff3cd; padding: 10px; border-left: 4px solid #157bba; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>We Received Your Message</h2>
        </div>
        <div class="content">
            <p>Dear {$recipientName},</p>

            <p>Thank you for reaching out to Bombay Engineering Syndicate. We have received your message and appreciate you taking the time to contact us.</p>

            <div class="highlight">
                <strong>Our team will review your message and get back to you soon.</strong>
            </div>

            <p><strong>Response Time:</strong> We typically respond to inquiries within 24 business hours.</p>

            <p>If your matter is urgent, please feel free to call us directly.</p>

            <p>Thank you for your interest in our products and services.</p>

            <p>Best regards,<br>
            <strong>Bombay Engineering Syndicate Team</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated confirmation email. Please do not reply directly to this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Build HTML email template for contact us admin notification
 */
function buildContactUsAdminEmail($data)
{
    $senderName = isset($data['userName']) ? htmlspecialchars($data['userName']) . ' ' . htmlspecialchars($data['userLastName'] ?? '') : 'Unknown';
    $senderEmail = isset($data['userEmail']) ? htmlspecialchars($data['userEmail']) : 'N/A';
    $senderMessage = isset($data['userMessage']) ? nl2br(htmlspecialchars($data['userMessage'])) : 'N/A';

    // Pre-assign all variables to avoid ?? operator issues in heredoc
    $userName = isset($data['userName']) ? htmlspecialchars($data['userName']) : 'N/A';
    $userLastName = isset($data['userLastName']) ? htmlspecialchars($data['userLastName']) : 'N/A';
    $userEmail = isset($data['userEmail']) ? htmlspecialchars($data['userEmail']) : 'N/A';
    $userMobile = isset($data['userMobile']) ? htmlspecialchars($data['userMobile']) : 'N/A';

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        .header { background-color: #157bba; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .section { margin: 20px 0; padding: 15px; background-color: white; border-left: 4px solid #157bba; }
        .section-title { font-weight: bold; color: #157bba; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; width: 25%; color: #157bba; }
        .message-box { background-color: #f0f0f0; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .important { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ff9800; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Contact Form Submission</h2>
        </div>
        <div class="content">
            <div class="important">
                <strong>From: {$senderName}</strong><br>
                Email: {$senderEmail}
            </div>

            <div class="section">
                <div class="section-title">CONTACT INFORMATION</div>
                <table>
                    <tr><td class="label">First Name:</td><td>{$userName}</td></tr>
                    <tr><td class="label">Last Name:</td><td>{$userLastName}</td></tr>
                    <tr><td class="label">Email:</td><td>{$userEmail}</td></tr>
                    <tr><td class="label">Phone:</td><td>{$userMobile}</td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">MESSAGE</div>
                <div class="message-box">
                    {$senderMessage}
                </div>
            </div>

            <p style="margin-top: 20px;"><strong>Action Required:</strong> Please respond to this inquiry at your earliest convenience.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

// ============================================================================
// FUEL EXPENSE EMAIL NOTIFICATION
// ============================================================================

/**
 * Send fuel expense invoice notification email with PDF attachment
 *
 * @param array $expenseData - Fuel expense data
 * @param string $pdfPath - Full path to the PDF/invoice file
 * @return bool - Success status
 */
function sendFuelExpenseNotification($expenseData, $pdfPath = null)
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        error_log("Brevo not configured - skipping fuel expense notification");
        return true; // Don't block the operation
    }

    try {
        // TEST MODE: Send to test email instead of production
        // Change this to 'reenakkc@gmail.com' for production
        $recipientEmail = 'paritosh.ajmera@gmail.com';
        $recipientName = 'Accounts Team';

        $htmlContent = buildFuelExpenseNotificationEmail($expenseData);

        $emailParams = array(
            'to' => array(
                array(
                    'email' => $recipientEmail,
                    'name' => $recipientName
                )
            ),
            'subject' => 'New Fuel Invoice Uploaded - ' . htmlspecialchars($expenseData['vehicleName'] ?? 'Vehicle') . ' - ₹' . number_format($expenseData['expenseAmount'], 2),
            'htmlContent' => $htmlContent,
            'tags' => array('fuel-expense', 'invoice-notification')
        );

        // Add PDF attachment if file exists
        if ($pdfPath && file_exists($pdfPath)) {
            $fileContent = file_get_contents($pdfPath);
            if ($fileContent !== false) {
                $fileName = basename($pdfPath);
                $emailParams['attachment'] = array(
                    array(
                        'content' => base64_encode($fileContent),
                        'name' => $fileName
                    )
                );
                error_log("Brevo: Attaching fuel invoice: " . $fileName);
            }
        }

        $result = $brevo->sendEmail($emailParams);

        if ($result['success']) {
            error_log("Brevo: Fuel expense notification sent successfully to " . $recipientEmail);
        } else {
            error_log("Brevo: Failed to send fuel expense notification - " . ($result['error'] ?? 'Unknown error'));
        }

        return $result['success'];
    } catch (Exception $e) {
        error_log("Fuel Expense Notification Error: " . $e->getMessage());
        return true; // Don't block the operation
    }
}

/**
 * Build beautiful HTML email template for fuel expense notification
 * Design: Modern, clean with subtle industrial aesthetic
 */
function buildFuelExpenseNotificationEmail($data)
{
    $vehicleName = htmlspecialchars($data['vehicleName'] ?? 'N/A');
    $vehicleNumber = htmlspecialchars($data['vehicleNumber'] ?? 'N/A');
    $billDate = htmlspecialchars($data['billDate'] ?? date('Y-m-d'));
    $expenseAmount = number_format(floatval($data['expenseAmount'] ?? 0), 2);
    $remarks = htmlspecialchars($data['remarks'] ?? '-');
    $uploadedBy = htmlspecialchars($data['uploadedBy'] ?? 'System');
    $uploadTime = date('d M Y, h:i A');

    // Format bill date nicely
    $billDateFormatted = date('d M Y', strtotime($billDate));

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Invoice Notification</title>
    <!--[if mso]>
    <style type="text/css">
        table { border-collapse: collapse; }
        td { padding: 0; }
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">

    <!-- Outer Container -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa;">
        <tr>
            <td align="center" style="padding: 40px 20px;">

                <!-- Email Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    <!-- Header with Gradient -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); border-radius: 16px 16px 0 0; padding: 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding: 35px 40px 30px;">
                                        <!-- Logo/Brand -->
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>
                                                    <span style="display: inline-block; background: rgba(255,255,255,0.15); padding: 8px 16px; border-radius: 8px; font-size: 12px; color: #94a3b8; letter-spacing: 2px; text-transform: uppercase; font-weight: 600;">Fuel Management</span>
                                                </td>
                                                <td align="right">
                                                    <span style="display: inline-block; background: #22c55e; color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; letter-spacing: 0.5px;">NEW INVOICE</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0 40px 40px;">
                                        <h1 style="margin: 0 0 12px; font-size: 28px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">Fuel Invoice Uploaded</h1>
                                        <p style="margin: 0; font-size: 15px; color: #94a3b8; line-height: 1.5;">A new fuel expense has been recorded in the system</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Amount Highlight Card -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 0 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: -20px;">
                                <tr>
                                    <td style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 12px; padding: 24px 28px; box-shadow: 0 10px 40px rgba(245, 158, 11, 0.3);">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>
                                                    <p style="margin: 0 0 4px; font-size: 12px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Total Amount</p>
                                                    <p style="margin: 0; font-size: 36px; font-weight: 800; color: #ffffff; letter-spacing: -1px;">₹{$expenseAmount}</p>
                                                </td>
                                                <td align="right" valign="middle">
                                                    <div style="width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 50%; display: inline-block; text-align: center; line-height: 56px;">
                                                        <span style="font-size: 28px;">⛽</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 35px 40px 40px;">

                            <!-- Vehicle Details Section -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 2px solid #f1f5f9;">
                                        <p style="margin: 0; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">Vehicle Details</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                                <tr>
                                    <td width="50%" style="padding: 12px 0; vertical-align: top;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Vehicle Name</p>
                                        <p style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 600;">{$vehicleName}</p>
                                    </td>
                                    <td width="50%" style="padding: 12px 0; vertical-align: top;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Registration No.</p>
                                        <p style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 600;">{$vehicleNumber}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Invoice Details Section -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td style="padding-bottom: 16px; border-bottom: 2px solid #f1f5f9;">
                                        <p style="margin: 0; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">Invoice Details</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f8fafc; border-radius: 10px; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td width="40%"><p style="margin: 0; font-size: 13px; color: #64748b;">Bill Date</p></td>
                                                            <td width="60%"><p style="margin: 0; font-size: 14px; color: #1e293b; font-weight: 600;">{$billDateFormatted}</p></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td width="40%"><p style="margin: 0; font-size: 13px; color: #64748b;">Amount</p></td>
                                                            <td width="60%"><p style="margin: 0; font-size: 14px; color: #1e293b; font-weight: 600;">₹{$expenseAmount}</p></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td width="40%"><p style="margin: 0; font-size: 13px; color: #64748b;">Uploaded By</p></td>
                                                            <td width="60%"><p style="margin: 0; font-size: 14px; color: #1e293b; font-weight: 600;">{$uploadedBy}</p></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td width="40%"><p style="margin: 0; font-size: 13px; color: #64748b;">Remarks</p></td>
                                                            <td width="60%"><p style="margin: 0; font-size: 14px; color: #1e293b; font-weight: 500;">{$remarks}</p></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Attachment Notice -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 10px; border-left: 4px solid #3b82f6;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="40" valign="top">
                                                    <span style="font-size: 22px;">📎</span>
                                                </td>
                                                <td>
                                                    <p style="margin: 0 0 4px; font-size: 14px; color: #1e40af; font-weight: 600;">Invoice Attached</p>
                                                    <p style="margin: 0; font-size: 13px; color: #3b82f6;">The fuel bill/invoice has been attached to this email for your records.</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1e293b; border-radius: 0 0 16px 16px; padding: 30px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 8px; font-size: 15px; color: #ffffff; font-weight: 600;">Bombay Engineering Syndicate</p>
                                        <p style="margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.6;">Fuel Management System<br>Automated Notification</p>
                                    </td>
                                    <td align="right" valign="bottom">
                                        <p style="margin: 0; font-size: 11px; color: #64748b;">{$uploadTime}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Bottom Spacing -->
                    <tr>
                        <td style="padding: 25px 0; text-align: center;">
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">This is an automated notification from the Fuel Management System</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
HTML;
}

// ============================================================================
// DRIVER OVERTIME EMAIL NOTIFICATION
// ============================================================================

/**
 * Send driver overtime notification email
 *
 * @param array $overtimeData - Driver overtime data
 *   - driverName: Name of the driver
 *   - overtimeType: 'early_checkin' or 'late_checkout'
 *   - checkInTime: Time of check-in (if applicable)
 *   - checkOutTime: Time of check-out (if applicable)
 *   - date: Date of the overtime
 * @return bool - Success status
 */
function sendDriverOvertimeNotification($overtimeData)
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        error_log("Brevo not configured - skipping driver overtime notification");
        return true; // Don't block the operation
    }

    try {
        // Send to Paritosh for verification
        $recipientEmail = 'paritosh.ajmera@gmail.com';
        $recipientName = 'Paritosh';

        $htmlContent = buildDriverOvertimeEmail($overtimeData);

        // Create subject based on overtime type
        $overtimeType = $overtimeData['overtimeType'] == 'early_checkin' ? 'Early Check-In' : 'Late Check-Out';
        $driverName = htmlspecialchars($overtimeData['driverName'] ?? 'Driver');

        $emailParams = array(
            'to' => array(
                array(
                    'email' => $recipientEmail,
                    'name' => $recipientName
                )
            ),
            'subject' => '🚗 Driver Overtime Alert: ' . $driverName . ' - ' . $overtimeType,
            'htmlContent' => $htmlContent,
            'tags' => array('driver-overtime', 'verification-required')
        );

        $result = $brevo->sendEmail($emailParams);

        if ($result['success']) {
            error_log("Brevo: Driver overtime notification sent successfully to " . $recipientEmail . " for driver: " . $driverName);
        } else {
            error_log("Brevo: Failed to send driver overtime notification - " . ($result['error'] ?? 'Unknown error'));
        }

        return $result['success'];
    } catch (Exception $e) {
        error_log("Driver Overtime Notification Error: " . $e->getMessage());
        return true; // Don't block the operation
    }
}

/**
 * Build HTML email template for driver overtime notification
 * Design: Clean, email-client compatible template with BES branding
 * Optimized for Gmail, Outlook, Apple Mail compatibility
 */
function buildDriverOvertimeEmail($data)
{
    $driverName = htmlspecialchars($data['driverName'] ?? 'Unknown Driver');
    $overtimeType = $data['overtimeType'] ?? 'late_checkout';
    $checkInTime = isset($data['checkInTime']) ? date('h:i A', strtotime($data['checkInTime'])) : '-';
    $checkOutTime = isset($data['checkOutTime']) ? date('h:i A', strtotime($data['checkOutTime'])) : '-';
    $overtimeDate = isset($data['date']) ? date('l, d M Y', strtotime($data['date'])) : date('l, d M Y');
    $verifyLink = defined('SITEURL') ? SITEURL . '/xadmin/driver-management-list/' : '#';
    $currentTime = date('d M Y, h:i A');

    // Set alert details based on overtime type
    if ($overtimeType == 'early_checkin') {
        $alertTitle = 'Early Check-In Detected';
        $alertSubtitle = 'Driver checked in before 10:00 AM';
        $highlightTime = $checkInTime;
        $highlightLabel = 'Check-In Time';
        $standardTime = '10:00 AM';
        $alertBgColor = '#0284c7';
        $alertIconText = 'EARLY';
    } else {
        $alertTitle = 'Late Check-Out Detected';
        $alertSubtitle = 'Driver checked out after 8:00 PM';
        $highlightTime = $checkOutTime;
        $highlightLabel = 'Check-Out Time';
        $standardTime = '8:00 PM';
        $alertBgColor = '#7c3aed';
        $alertIconText = 'LATE';
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>Driver Overtime Notification</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style>
        table { border-collapse: collapse; }
        td, th { mso-line-height-rule: exactly; }
    </style>
    <![endif]-->
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { text-decoration: none; }
        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; padding: 10px !important; }
            .mobile-padding { padding: 20px !important; }
            .mobile-center { text-align: center !important; }
            .time-box { display: block !important; width: 100% !important; margin-bottom: 10px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; -webkit-font-smoothing: antialiased;">

    <!-- Preheader Text -->
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        {$alertTitle} - {$driverName} | {$overtimeDate}
    </div>

    <!-- Email Wrapper -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 30px 15px;">

                <!-- Main Container -->
                <table role="presentation" class="container" width="580" cellpadding="0" cellspacing="0" style="max-width: 580px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="background-color: #157bba; padding: 28px 35px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.3px;">BES Driver Portal</p>
                                        <p style="margin: 5px 0 0; font-size: 13px; color: rgba(255,255,255,0.85);">Overtime Notification</p>
                                    </td>
                                    <td align="right" valign="middle">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="background-color: #dc2626; padding: 8px 14px; border-radius: 6px;">
                                                    <p style="margin: 0; font-size: 11px; font-weight: 700; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px;">Action Required</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Alert Banner -->
                    <tr>
                        <td style="background-color: {$alertBgColor}; padding: 22px 35px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="55" valign="top">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="background-color: rgba(255,255,255,0.2); width: 48px; height: 48px; border-radius: 50%; text-align: center; vertical-align: middle;">
                                                    <p style="margin: 0; font-size: 11px; font-weight: 800; color: #ffffff; line-height: 48px;">{$alertIconText}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle" style="padding-left: 12px;">
                                        <p style="margin: 0 0 4px; font-size: 20px; font-weight: 700; color: #ffffff;">{$alertTitle}</p>
                                        <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.9);">{$alertSubtitle}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Time Highlight Box -->
                    <tr>
                        <td style="padding: 25px 35px 20px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                                <tr>
                                    <td class="time-box" width="50%" style="padding: 20px; text-align: center; border-right: 1px solid #e2e8f0;">
                                        <p style="margin: 0 0 6px; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">{$highlightLabel}</p>
                                        <p style="margin: 0; font-size: 28px; font-weight: 800; color: #0f172a;">{$highlightTime}</p>
                                    </td>
                                    <td class="time-box" width="50%" style="padding: 20px; text-align: center;">
                                        <p style="margin: 0 0 6px; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Standard Time</p>
                                        <p style="margin: 0; font-size: 28px; font-weight: 800; color: #64748b;">{$standardTime}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Driver Details -->
                    <tr>
                        <td style="padding: 5px 35px 25px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <!-- Section Header -->
                                <tr>
                                    <td style="padding-bottom: 15px; border-bottom: 2px solid #e2e8f0;">
                                        <p style="margin: 0; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">Driver Details</p>
                                    </td>
                                </tr>
                                <!-- Details Table -->
                                <tr>
                                    <td style="padding-top: 15px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td width="35%" style="color: #64748b; font-size: 14px;">Driver Name</td>
                                                            <td style="color: #0f172a; font-size: 14px; font-weight: 600;">{$driverName}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td width="35%" style="color: #64748b; font-size: 14px;">Date</td>
                                                            <td style="color: #0f172a; font-size: 14px; font-weight: 600;">{$overtimeDate}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td width="35%" style="color: #64748b; font-size: 14px;">Check-In</td>
                                                            <td style="color: #059669; font-size: 14px; font-weight: 600;">{$checkInTime}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0;">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td width="35%" style="color: #64748b; font-size: 14px;">Check-Out</td>
                                                            <td style="color: #dc2626; font-size: 14px; font-weight: 600;">{$checkOutTime}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- CTA Button -->
                    <tr>
                        <td align="center" style="padding: 10px 35px 30px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background-color: #157bba; border-radius: 8px;">
                                        <a href="{$verifyLink}" target="_blank" style="display: inline-block; padding: 16px 40px; font-size: 15px; font-weight: 700; color: #ffffff; text-decoration: none;">
                                            Verify Overtime Now
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Info Note -->
                    <tr>
                        <td style="padding: 0 35px 30px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #eff6ff; border-left: 4px solid #157bba; border-radius: 0 8px 8px 0;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <p style="margin: 0 0 4px; font-size: 14px; color: #1e40af; font-weight: 600;">What to do?</p>
                                        <p style="margin: 0; font-size: 13px; color: #3b82f6; line-height: 1.5;">Click the button above to review and verify this overtime entry in the admin panel.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 25px 35px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 4px; font-size: 15px; color: #ffffff; font-weight: 600;">Bombay Engineering Syndicate</p>
                                        <p style="margin: 0; font-size: 12px; color: #94a3b8;">Driver Attendance System</p>
                                    </td>
                                    <td align="right" valign="bottom">
                                        <p style="margin: 0; font-size: 11px; color: #64748b;">{$currentTime}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <!-- Bottom Note -->
                <table role="presentation" width="580" cellpadding="0" cellspacing="0" style="max-width: 580px; width: 100%;">
                    <tr>
                        <td style="padding: 20px 0; text-align: center;">
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">This is an automated notification from the BES Driver Portal</p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>
HTML;
}

// ============================================================================
// LEAVE APPLICATION EMAIL NOTIFICATION
// ============================================================================

/**
 * Send leave application notification email to approvers
 *
 * @param array $leaveData - Leave application data
 *   - leaveID: Leave request ID
 *   - employeeName: Name of employee applying for leave
 *   - employeeEmail: Employee's email
 *   - leaveType: Type of leave (CL, SL, EL, etc.)
 *   - fromDate: Start date
 *   - toDate: End date
 *   - reason: Reason for leave
 *   - totalDays: Number of days
 * @param array $approverEmails - Array of approver email addresses
 * @return bool - Success status
 */
function sendLeaveApplicationNotification($leaveData, $approverEmails = array())
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        error_log("Brevo not configured - skipping leave application notification");
        return true;
    }

    // Default approvers if none provided
    if (empty($approverEmails)) {
        $approverEmails = array(
            array('email' => 'paritosh.ajmera@gmail.com', 'name' => 'Paritosh'),
            array('email' => 'manishbeskkc@gmail.com', 'name' => 'Manish')
        );
    }

    try {
        $htmlContent = buildLeaveApplicationEmail($leaveData);
        $employeeName = htmlspecialchars($leaveData['employeeName'] ?? 'Employee');
        $fromDate = date('d M Y', strtotime($leaveData['fromDate']));
        $toDate = date('d M Y', strtotime($leaveData['toDate']));

        $emailParams = array(
            'to' => $approverEmails,
            'subject' => 'Leave Application: ' . $employeeName . ' (' . $fromDate . ' - ' . $toDate . ')',
            'htmlContent' => $htmlContent,
            'tags' => array('leave-application', 'approval-required')
        );

        error_log("Brevo Leave: Sending to " . json_encode($approverEmails) . " for employee: " . $employeeName);
        $result = $brevo->sendEmail($emailParams);

        if ($result['success']) {
            error_log("Brevo: Leave application notification sent for " . $employeeName . " - messageId: " . ($result['messageId'] ?? 'N/A'));
        } else {
            error_log("Brevo: Failed to send leave notification for " . $employeeName . " - Error: " . ($result['error'] ?? 'Unknown error'));
        }

        return $result['success'];
    } catch (Exception $e) {
        error_log("Leave Application Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Build HTML email template for leave application notification
 */
function buildLeaveApplicationEmail($data)
{
    $employeeName = htmlspecialchars($data['employeeName'] ?? 'Employee');
    $employeeEmail = htmlspecialchars($data['employeeEmail'] ?? 'N/A');
    $leaveType = htmlspecialchars($data['leaveTypeName'] ?? $data['leaveType'] ?? 'Leave');
    $fromDate = date('l, d M Y', strtotime($data['fromDate']));
    $toDate = date('l, d M Y', strtotime($data['toDate']));
    $reason = htmlspecialchars($data['reason'] ?? 'No reason provided');
    $totalDays = $data['totalDays'] ?? 1;
    $leaveID = $data['leaveID'] ?? 'N/A';
    $appliedOn = date('d M Y, h:i A');

    // Balance warning info
    $balanceWarning = $data['balanceWarning'] ?? null;
    $warningHtml = '';

    if ($balanceWarning) {
        $warningType = $balanceWarning['type'] === 'over_limit' ? 'LEAVE QUOTA ALREADY EXCEEDED' : 'LEAVE QUOTA WILL BE EXCEEDED';
        $warningMessage = htmlspecialchars($balanceWarning['message'] ?? '');
        $warningLeaveType = htmlspecialchars($balanceWarning['leaveTypeName'] ?? $leaveType);
        $warningAllowed = $balanceWarning['allowed'] ?? 0;
        $warningUsed = $balanceWarning['used'] ?? 0;
        $warningCurrent = $balanceWarning['currentBalance'] ?? 0;
        $warningAfter = $balanceWarning['afterBalance'] ?? 0;

        $warningHtml = <<<WARNING

                            <!-- Balance Warning Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 2px solid #ef4444; border-radius: 10px; margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <span style="display: inline-block; background: #ef4444; color: #fff; padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: 800; letter-spacing: 1px;">⚠ {$warningType}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 15px;">
                                                    <p style="margin: 0; font-size: 14px; color: #991b1b; line-height: 1.5;">{$warningMessage}</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 6px;">
                                                        <tr>
                                                            <td style="padding: 12px 15px; border-bottom: 1px solid #fecaca;">
                                                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                        <td style="font-size: 13px; color: #7f1d1d;">Leave Type:</td>
                                                                        <td align="right" style="font-size: 13px; color: #7f1d1d; font-weight: 700;">{$warningLeaveType}</td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 12px 15px; border-bottom: 1px solid #fecaca;">
                                                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                        <td style="font-size: 13px; color: #7f1d1d;">Allocated:</td>
                                                                        <td align="right" style="font-size: 13px; color: #7f1d1d; font-weight: 700;">{$warningAllowed} days</td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 12px 15px; border-bottom: 1px solid #fecaca;">
                                                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                        <td style="font-size: 13px; color: #7f1d1d;">Already Used:</td>
                                                                        <td align="right" style="font-size: 13px; color: #7f1d1d; font-weight: 700;">{$warningUsed} days</td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 12px 15px; border-bottom: 1px solid #fecaca;">
                                                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                        <td style="font-size: 13px; color: #7f1d1d;">Current Balance:</td>
                                                                        <td align="right" style="font-size: 13px; color: #dc2626; font-weight: 700;">{$warningCurrent} days</td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 12px 15px;">
                                                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                        <td style="font-size: 13px; color: #7f1d1d;">After This Leave:</td>
                                                                        <td align="right" style="font-size: 14px; color: #dc2626; font-weight: 800;">{$warningAfter} days</td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
WARNING;
    }

    // Generate secure action URLs
    $secret = defined('BREVO_API_KEY') ? substr(BREVO_API_KEY, 0, 16) : 'besyndicate2024';
    $token = md5($leaveID . $secret);
    $baseUrl = defined('SITEURL') ? SITEURL : 'https://www.bombayengg.net';
    $approveUrl = $baseUrl . '/xadmin/mod/employee-leave/x-leave-action.php?action=approve&id=' . $leaveID . '&token=' . $token;
    $rejectUrl = $baseUrl . '/xadmin/mod/employee-leave/x-leave-action.php?action=reject&id=' . $leaveID . '&token=' . $token;

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Application</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 20px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #157bba 0%, #0f5a8a 100%); border-radius: 12px 12px 0 0; padding: 30px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 8px; font-size: 12px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 2px;">HRMS Notification</p>
                                        <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #ffffff;">Leave Application</h1>
                                    </td>
                                    <td align="right">
                                        <span style="display: inline-block; background: #f59e0b; color: #fff; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 700;">PENDING APPROVAL</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 35px 40px;">

                            <!-- Employee Info -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f8fafc; border-radius: 10px; margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="60" valign="top">
                                                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #157bba, #0f5a8a); border-radius: 50%; text-align: center; line-height: 50px; color: #fff; font-size: 20px; font-weight: 700;">
                                                        {$employeeName[0]}
                                                    </div>
                                                </td>
                                                <td valign="middle">
                                                    <p style="margin: 0 0 4px; font-size: 18px; font-weight: 700; color: #1e293b;">{$employeeName}</p>
                                                    <p style="margin: 0; font-size: 14px; color: #64748b;">{$employeeEmail}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Leave Details -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
                                <tr>
                                    <td style="padding-bottom: 15px; border-bottom: 2px solid #e2e8f0;">
                                        <p style="margin: 0; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">Leave Details</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
                                <tr>
                                    <td width="50%" style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Leave Type</p>
                                        <p style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 600;">{$leaveType}</p>
                                    </td>
                                    <td width="50%" style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Total Days</p>
                                        <p style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 600;">{$totalDays} Day(s)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">From Date</p>
                                        <p style="margin: 0; font-size: 15px; color: #1e293b; font-weight: 600;">{$fromDate}</p>
                                    </td>
                                    <td style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">To Date</p>
                                        <p style="margin: 0; font-size: 15px; color: #1e293b; font-weight: 600;">{$toDate}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Reason Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #fefce8; border-left: 4px solid #eab308; border-radius: 0 8px 8px 0; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="margin: 0 0 8px; font-size: 12px; color: #a16207; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">Reason for Leave</p>
                                        <p style="margin: 0; font-size: 15px; color: #713f12; line-height: 1.6;">{$reason}</p>
                                    </td>
                                </tr>
                            </table>
{$warningHtml}
                            <!-- Action Buttons -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 10px 0;">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding-right: 10px;">
                                                    <a href="{$approveUrl}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; padding: 16px 36px; font-size: 15px; font-weight: 700; text-decoration: none; border-radius: 8px; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);">
                                                        ✓ APPROVE
                                                    </a>
                                                </td>
                                                <td style="padding-left: 10px;">
                                                    <a href="{$rejectUrl}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #ffffff; padding: 16px 36px; font-size: 15px; font-weight: 700; text-decoration: none; border-radius: 8px; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3);">
                                                        ✗ REJECT
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1e293b; border-radius: 0 0 12px 12px; padding: 25px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 4px; font-size: 15px; color: #ffffff; font-weight: 600;">Bombay Engineering Syndicate</p>
                                        <p style="margin: 0; font-size: 12px; color: #94a3b8;">HRMS - Leave Management</p>
                                    </td>
                                    <td align="right">
                                        <p style="margin: 0; font-size: 11px; color: #64748b;">Applied: {$appliedOn}</p>
                                        <p style="margin: 4px 0 0; font-size: 11px; color: #64748b;">Ref: #{$leaveID}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <p style="margin: 20px 0 0; font-size: 11px; color: #94a3b8; text-align: center;">This is an automated notification from the HRMS system</p>

            </td>
        </tr>
    </table>

</body>
</html>
HTML;
}

// ============================================================================
// LEAVE REMINDER EMAIL NOTIFICATION
// ============================================================================

/**
 * Send leave reminder notification email to admins
 * Sent 2 days before an approved leave starts
 *
 * @param array $leaveData - Leave data
 *   - leaveID: Leave request ID
 *   - employeeName: Name of employee on leave
 *   - employeeEmail: Employee's email
 *   - leaveType: Type of leave
 *   - fromDate: Start date
 *   - toDate: End date
 *   - totalDays: Number of days
 * @return bool - Success status
 */
function sendLeaveReminderNotification($leaveData)
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        error_log("Brevo not configured - skipping leave reminder notification");
        return true;
    }

    // Send to HR/Admin approvers (both emails)
    $approverEmails = array(
        array('email' => 'paritosh.ajmera@gmail.com', 'name' => 'Paritosh'),
        array('email' => 'manishbeskkc@gmail.com', 'name' => 'Manish')
    );

    try {
        $htmlContent = buildLeaveReminderEmail($leaveData);
        $employeeName = htmlspecialchars($leaveData['employeeName'] ?? 'Employee');
        $fromDate = date('d M Y', strtotime($leaveData['fromDate']));
        $toDate = date('d M Y', strtotime($leaveData['toDate']));

        $emailParams = array(
            'to' => $approverEmails,
            'subject' => 'Reminder: ' . $employeeName . ' on leave from ' . $fromDate,
            'htmlContent' => $htmlContent,
            'tags' => array('leave-reminder', 'upcoming-leave')
        );

        $result = $brevo->sendEmail($emailParams);

        if ($result['success']) {
            error_log("Brevo: Leave reminder sent for " . $employeeName . " (Leave ID: " . $leaveData['leaveID'] . ")");
        } else {
            error_log("Brevo: Failed to send leave reminder - " . ($result['error'] ?? 'Unknown error'));
        }

        return $result['success'];
    } catch (Exception $e) {
        error_log("Leave Reminder Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Build HTML email template for leave reminder notification
 */
function buildLeaveReminderEmail($data)
{
    $employeeName = htmlspecialchars($data['employeeName'] ?? 'Employee');
    $employeeEmail = htmlspecialchars($data['employeeEmail'] ?? 'N/A');
    $leaveType = htmlspecialchars($data['leaveTypeName'] ?? $data['leaveType'] ?? 'Leave');
    $fromDate = date('l, d M Y', strtotime($data['fromDate']));
    $toDate = date('l, d M Y', strtotime($data['toDate']));
    $totalDays = $data['totalDays'] ?? 1;
    $leaveID = $data['leaveID'] ?? 'N/A';
    $sentOn = date('d M Y, h:i A');

    // Calculate days until leave starts
    $today = new DateTime();
    $startDate = new DateTime($data['fromDate']);
    $daysUntil = $today->diff($startDate)->days;
    $daysText = $daysUntil == 1 ? 'tomorrow' : 'in ' . $daysUntil . ' days';

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Reminder</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 20px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); border-radius: 12px 12px 0 0; padding: 30px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 8px; font-size: 12px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 2px;">HRMS Reminder</p>
                                        <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #ffffff;">Upcoming Leave</h1>
                                    </td>
                                    <td align="right">
                                        <span style="display: inline-block; background: #fbbf24; color: #78350f; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 700;">STARTS {$daysText}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Alert Banner -->
                    <tr>
                        <td style="background-color: #fef3c7; padding: 20px 40px; border-bottom: 3px solid #f59e0b;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="50" valign="middle">
                                        <span style="font-size: 32px;">🔔</span>
                                    </td>
                                    <td>
                                        <p style="margin: 0; font-size: 16px; color: #92400e; font-weight: 600;">
                                            <strong>{$employeeName}</strong> will be on leave starting {$daysText}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 35px 40px;">

                            <!-- Employee Info -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f8fafc; border-radius: 10px; margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="60" valign="top">
                                                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #8b5cf6, #6d28d9); border-radius: 50%; text-align: center; line-height: 50px; color: #fff; font-size: 20px; font-weight: 700;">
                                                        {$employeeName[0]}
                                                    </div>
                                                </td>
                                                <td valign="middle">
                                                    <p style="margin: 0 0 4px; font-size: 18px; font-weight: 700; color: #1e293b;">{$employeeName}</p>
                                                    <p style="margin: 0; font-size: 14px; color: #64748b;">{$employeeEmail}</p>
                                                </td>
                                                <td align="right" valign="middle">
                                                    <span style="display: inline-block; background: #10b981; color: #fff; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600;">APPROVED</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Leave Details -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
                                <tr>
                                    <td style="padding-bottom: 15px; border-bottom: 2px solid #e2e8f0;">
                                        <p style="margin: 0; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">Leave Details</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
                                <tr>
                                    <td width="50%" style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Leave Type</p>
                                        <p style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 600;">{$leaveType}</p>
                                    </td>
                                    <td width="50%" style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Total Days</p>
                                        <p style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 600;">{$totalDays} Day(s)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">From Date</p>
                                        <p style="margin: 0; font-size: 15px; color: #1e293b; font-weight: 600;">{$fromDate}</p>
                                    </td>
                                    <td style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">To Date</p>
                                        <p style="margin: 0; font-size: 15px; color: #1e293b; font-weight: 600;">{$toDate}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Info Note -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 0 8px 8px 0;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="margin: 0 0 4px; font-size: 14px; color: #1e40af; font-weight: 600;">Please Note</p>
                                        <p style="margin: 0; font-size: 13px; color: #3b82f6; line-height: 1.5;">This is an automated reminder that the above employee will be on approved leave. Please ensure proper handover and coverage arrangements are in place.</p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1e293b; border-radius: 0 0 12px 12px; padding: 25px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 4px; font-size: 15px; color: #ffffff; font-weight: 600;">Bombay Engineering Syndicate</p>
                                        <p style="margin: 0; font-size: 12px; color: #94a3b8;">HRMS - Leave Management</p>
                                    </td>
                                    <td align="right">
                                        <p style="margin: 0; font-size: 11px; color: #64748b;">Sent: {$sentOn}</p>
                                        <p style="margin: 4px 0 0; font-size: 11px; color: #64748b;">Ref: #{$leaveID}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <p style="margin: 20px 0 0; font-size: 11px; color: #94a3b8; text-align: center;">This is an automated reminder from the HRMS system</p>

            </td>
        </tr>
    </table>

</body>
</html>
HTML;
}

/**
 * Send leave status update notification to employee
 * Called when leave is approved or rejected
 *
 * @param array $leaveData - Leave details including:
 *   - employeeName: Name of employee
 *   - employeeEmail: Employee's email address
 *   - leaveType: Type of leave
 *   - fromDate: Start date
 *   - toDate: End date
 *   - status: 'Approved' or 'Disapproved'
 *   - remarks: Optional remarks/reason for rejection
 * @return bool - Success status
 */
function sendLeaveStatusNotification($leaveData)
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        error_log("Brevo not configured - skipping leave status notification");
        return true;
    }

    $employeeEmail = $leaveData['employeeEmail'] ?? '';
    $employeeName = $leaveData['employeeName'] ?? 'Employee';

    if (empty($employeeEmail)) {
        error_log("Brevo: No employee email provided for leave status notification");
        return false;
    }

    try {
        $htmlContent = buildLeaveStatusEmail($leaveData);
        $status = $leaveData['status'] ?? 'Updated';
        $fromDate = date('d M Y', strtotime($leaveData['fromDate']));
        $toDate = date('d M Y', strtotime($leaveData['toDate']));

        $emailParams = array(
            'to' => array(
                array('email' => $employeeEmail, 'name' => $employeeName)
            ),
            'subject' => 'Leave ' . $status . ': ' . $fromDate . ' - ' . $toDate,
            'htmlContent' => $htmlContent,
            'tags' => array('leave-status', strtolower($status))
        );

        error_log("Brevo Leave Status: Sending to $employeeEmail for $employeeName - Status: $status");
        $result = $brevo->sendEmail($emailParams);

        if ($result['success']) {
            error_log("Brevo: Leave status notification sent to $employeeName ($employeeEmail) - Status: $status");
        } else {
            error_log("Brevo: Failed to send leave status notification - " . ($result['error'] ?? 'Unknown error'));
        }

        return $result['success'];
    } catch (Exception $e) {
        error_log("Leave Status Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Build HTML email template for leave status notification
 * Matches the design of buildLeaveApplicationEmail for consistency
 */
function buildLeaveStatusEmail($data)
{
    $employeeName = htmlspecialchars($data['employeeName'] ?? 'Employee');
    $leaveType = htmlspecialchars($data['leaveType'] ?? 'Leave');
    $fromDate = date('l, d M Y', strtotime($data['fromDate']));
    $toDate = date('l, d M Y', strtotime($data['toDate']));
    $status = $data['status'] ?? 'Updated';
    $remarks = htmlspecialchars($data['remarks'] ?? '');
    $processedOn = date('d M Y, h:i A');

    $isApproved = ($status === 'Approved');

    // Colors matching the existing template design system
    $headerGradient = $isApproved
        ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)'
        : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
    $statusBadgeBg = $isApproved ? '#10b981' : '#ef4444';
    $statusIcon = $isApproved ? '✓' : '✗';
    $statusText = $isApproved ? 'APPROVED' : 'REJECTED';
    $statusTextLower = $isApproved ? 'Approved' : 'Rejected';

    $message = $isApproved
        ? 'Great news! Your leave request has been approved. Please ensure proper handover of your responsibilities before going on leave.'
        : 'We regret to inform you that your leave request has been rejected. Please contact your manager or HR for more details.';

    // Build remarks section if provided
    $remarksHtml = '';
    if (!empty($remarks)) {
        $remarksBg = $isApproved ? '#f0fdf4' : '#fef2f2';
        $remarksBorder = $isApproved ? '#10b981' : '#ef4444';
        $remarksTextColor = $isApproved ? '#166534' : '#991b1b';
        $remarksLabelColor = $isApproved ? '#15803d' : '#b91c1c';

        $remarksHtml = <<<HTML
                            <!-- Remarks Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: {$remarksBg}; border-left: 4px solid {$remarksBorder}; border-radius: 0 8px 8px 0; margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="margin: 0 0 8px; font-size: 12px; color: {$remarksLabelColor}; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">Remarks</p>
                                        <p style="margin: 0; font-size: 15px; color: {$remarksTextColor}; line-height: 1.6;">{$remarks}</p>
                                    </td>
                                </tr>
                            </table>
HTML;
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave {$statusTextLower}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 20px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    <!-- Header -->
                    <tr>
                        <td style="background: {$headerGradient}; border-radius: 12px 12px 0 0; padding: 30px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 8px; font-size: 12px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 2px;">HRMS Notification</p>
                                        <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #ffffff;">Leave {$statusTextLower}</h1>
                                    </td>
                                    <td align="right">
                                        <div style="width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 50%; text-align: center; line-height: 56px;">
                                            <span style="font-size: 28px; color: #ffffff;">{$statusIcon}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 35px 40px;">

                            <!-- Greeting -->
                            <p style="margin: 0 0 20px; font-size: 16px; color: #1e293b; line-height: 1.6;">
                                Dear <strong>{$employeeName}</strong>,
                            </p>

                            <!-- Message -->
                            <p style="margin: 0 0 30px; font-size: 15px; color: #64748b; line-height: 1.7;">
                                {$message}
                            </p>

                            <!-- Leave Details Section Header -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
                                <tr>
                                    <td style="padding-bottom: 15px; border-bottom: 2px solid #e2e8f0;">
                                        <p style="margin: 0; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">Leave Details</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Leave Details Grid -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
                                <tr>
                                    <td width="50%" style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Leave Type</p>
                                        <p style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 600;">{$leaveType}</p>
                                    </td>
                                    <td width="50%" style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Status</p>
                                        <span style="display: inline-block; background: {$statusBadgeBg}; color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;">{$statusText}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">From Date</p>
                                        <p style="margin: 0; font-size: 15px; color: #1e293b; font-weight: 600;">{$fromDate}</p>
                                    </td>
                                    <td style="padding: 12px 0;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase;">To Date</p>
                                        <p style="margin: 0; font-size: 15px; color: #1e293b; font-weight: 600;">{$toDate}</p>
                                    </td>
                                </tr>
                            </table>

                            {$remarksHtml}

                            <!-- Help Text -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f8fafc; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.6;">
                                            <strong style="color: #475569;">Need help?</strong> You can view your leave history and balances on the <a href="https://www.bombayengg.net/hrms/leave/" style="color: #157bba; text-decoration: none; font-weight: 600;">HRMS Portal</a>.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1e293b; border-radius: 0 0 12px 12px; padding: 25px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 4px; font-size: 15px; color: #ffffff; font-weight: 600;">Bombay Engineering Syndicate</p>
                                        <p style="margin: 0; font-size: 12px; color: #94a3b8;">HRMS - Leave Management</p>
                                    </td>
                                    <td align="right">
                                        <p style="margin: 0; font-size: 11px; color: #64748b;">Processed: {$processedOn}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <p style="margin: 20px 0 0; font-size: 11px; color: #94a3b8; text-align: center;">This is an automated notification from the HRMS system</p>

            </td>
        </tr>
    </table>

</body>
</html>
HTML;
}

?>
