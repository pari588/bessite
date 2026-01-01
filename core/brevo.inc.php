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
        $adminEmail = defined('ADMIN_NOTIFICATION_EMAIL') ? ADMIN_NOTIFICATION_EMAIL : BREVO_SENDER_EMAIL;

        $htmlContent = buildProductInquiryAdminEmail($inquiryData);

        $emailParams = array(
            'to' => array(
                array(
                    'email' => $adminEmail,
                    'name' => 'Admin'
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
    $pumpingDistance = isset($data['pumpingDistance']) ? htmlspecialchars($data['pumpingDistance']) : 'N/A';
    $operatingHours = isset($data['operatingHours']) ? htmlspecialchars($data['operatingHours']) : 'N/A';

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
                    <tr><td class="label">Pumping Distance:</td><td>{$pumpingDistance}</td></tr>
                    <tr><td class="label">Operating Hours:</td><td>{$operatingHours}</td></tr>
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

    // Pre-assign all variables to avoid ?? operator issues in heredoc
    $userName = isset($data['userName']) ? htmlspecialchars($data['userName']) : 'N/A';
    $companyName = isset($data['companyName']) ? htmlspecialchars($data['companyName']) : 'N/A';
    $userEmail = isset($data['userEmail']) ? htmlspecialchars($data['userEmail']) : 'N/A';
    $userMobile = isset($data['userMobile']) ? htmlspecialchars($data['userMobile']) : 'N/A';
    $makeOfMotor = isset($data['makeOfMotor']) ? htmlspecialchars($data['makeOfMotor']) : 'N/A';
    $kw = isset($data['kw']) ? htmlspecialchars($data['kw']) : 'N/A';
    $hp = isset($data['hp']) ? htmlspecialchars($data['hp']) : 'N/A';
    $typeOfMotorID = isset($data['typeOfMotorID']) ? htmlspecialchars($data['typeOfMotorID']) : 'N/A';
    $voltageID = isset($data['voltageID']) ? htmlspecialchars($data['voltageID']) : 'N/A';
    $mountingID = isset($data['mountingID']) ? htmlspecialchars($data['mountingID']) : 'N/A';

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
                    <tr><td class="label">Make:</td><td>{$makeOfMotor}</td></tr>
                    <tr><td class="label">KW/HP:</td><td>{$kw} KW / {$hp} HP</td></tr>
                    <tr><td class="label">Type:</td><td>{$typeOfMotorID}</td></tr>
                    <tr><td class="label">Voltage:</td><td>{$voltageID}</td></tr>
                    <tr><td class="label">Mounting:</td><td>{$mountingID}</td></tr>
                </table>
            </div>

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

?>
