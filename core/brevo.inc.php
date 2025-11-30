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
                    <td>{{date("Y-m-d H:i:s")}}</td>
                </tr>
                <tr>
                    <td class="label">Application Type:</td>
                    <td>{{{$data['applicationTypeID'] ?? 'N/A'}}}</td>
                </tr>
                <tr>
                    <td class="label">Installation Type:</td>
                    <td>{{{$data['installationTypeID'] ?? 'N/A'}}}</td>
                </tr>
                <tr>
                    <td class="label">Preferred Contact:</td>
                    <td>{{{$data['preferredContactTime'] ?? 'Any time'}}}</td>
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
                    <tr><td class="label">Name:</td><td>{{{$data['fullName'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Company:</td><td>{{{$data['companyName'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Email:</td><td>{{{$data['userEmail'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Mobile:</td><td>{{{$data['userMobile'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">City:</td><td>{{{$data['city'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Contact Time:</td><td>{{{$data['preferredContactTime'] ?? 'Any time'}}}</td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">APPLICATION DETAILS</div>
                <table>
                    <tr><td class="label">Application Type:</td><td>{{{$data['applicationTypeID'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Installation Type:</td><td>{{{$data['installationTypeID'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Operating Medium:</td><td>{{{$data['operatingMediumID'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Water Source:</td><td>{{{$data['waterSourceID'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Power Supply:</td><td>{{{$data['powerSupplyID'] ?? 'N/A'}}}</td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">TECHNICAL REQUIREMENTS</div>
                <table>
                    <tr><td class="label">Required Head:</td><td>{{{$data['requiredHead'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Required Discharge:</td><td>{{{$data['requiredDischarge'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Pumping Distance:</td><td>{{{$data['pumpingDistance'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Operating Hours:</td><td>{{{$data['operatingHours'] ?? 'N/A'}}}</td></tr>
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
                    <tr><td class="label">Name:</td><td>{{{$data['userName'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Company:</td><td>{{{$data['companyName'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Email:</td><td>{{{$data['userEmail'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Mobile:</td><td>{{{$data['userMobile'] ?? 'N/A'}}}</td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">MOTOR SPECIFICATIONS</div>
                <table>
                    <tr><td class="label">Make:</td><td>{{{$data['makeOfMotor'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">KW/HP:</td><td>{{{$data['kw'] ?? 'N/A'}}} KW / {{{$data['hp'] ?? 'N/A'}}} HP</td></tr>
                    <tr><td class="label">Type:</td><td>{{{$data['typeOfMotorID'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Voltage:</td><td>{{{$data['voltageID'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Mounting:</td><td>{{{$data['mountingID'] ?? 'N/A'}}}</td></tr>
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
    $senderName = isset($data['userName']) ? htmlspecialchars($data['userName']) . ' ' . htmlspecialchars($data['userLastName']) : 'Unknown';
    $senderEmail = isset($data['userEmail']) ? htmlspecialchars($data['userEmail']) : 'N/A';
    $senderMessage = isset($data['userMessage']) ? nl2br(htmlspecialchars($data['userMessage'])) : 'N/A';

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
                    <tr><td class="label">First Name:</td><td>{{{$data['userName'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Last Name:</td><td>{{{$data['userLastName'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Email:</td><td>{{{$data['userEmail'] ?? 'N/A'}}}</td></tr>
                    <tr><td class="label">Phone:</td><td>{{{$data['userMobile'] ?? 'N/A'}}}</td></tr>
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

?>
