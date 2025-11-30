# Brevo Email Service - Technical Reference

## Architecture Overview

The Brevo email integration uses a REST API-based approach with a dedicated email service class that abstracts API communication.

```
User Form Submission
        ↓
Form Handler (x-pump-inquiry-inc.php, etc.)
        ↓
Database Insert (if validation passes)
        ↓
sendXxxEmail() function call
        ↓
getBrevoService() → BrevoEmailService class
        ↓
makeApiRequest() → Brevo API v3
        ↓
HTTP Response
        ↓
Email sent to customer & admin
```

---

## File Structure

### Core Files

#### 1. `/config.inc.php`
Contains configuration constants:

```php
define("BREVO_API_KEY", "xkeysib_...");           // API authentication
define("BREVO_API_URL", "https://api.brevo.com/v3"); // API endpoint
define("BREVO_SENDER_NAME", "Company Name");      // From name
define("BREVO_SENDER_EMAIL", "email@domain.com"); // From email
define("ADMIN_NOTIFICATION_EMAIL", "admin@...");  // Admin recipient
```

#### 2. `/core/brevo.inc.php`
Main email service implementation (670+ lines):

**Classes:**
- `BrevoEmailService` - Core service class

**Helper Functions:**
- `getBrevoService()` - Singleton instance getter
- `sendPumpInquiryEmail($data)` - Pump form emails
- `sendProductInquiryEmail($data)` - Motor form emails
- `sendContactUsEmail($data)` - Contact form emails

**Admin Email Functions:**
- `sendPumpInquiryAdminEmail($data)`
- `sendProductInquiryAdminEmail($data)`
- `sendContactUsAdminEmail($data)`

**Template Builders:**
- `buildPumpInquiryConfirmationEmail($data)`
- `buildPumpInquiryAdminEmail($data)`
- `buildProductInquiryConfirmationEmail($data)`
- `buildProductInquiryAdminEmail($data)`
- `buildContactUsConfirmationEmail($data)`
- `buildContactUsAdminEmail($data)`

### Modified Form Handlers

#### 1. `/xsite/mod/pump-inquiry/x-pump-inquiry-inc.php`
Lines 413-426: Added email sending after database insert

```php
if ($DB->dbInsert()) {
    $data['err'] = 0;
    $data['msg'] = "Success message...";

    // Load Brevo service
    if (!function_exists('sendPumpInquiryEmail')) {
        require_once(ROOTPATH . "/core/brevo.inc.php");
    }

    // Send emails
    $emailSent = sendPumpInquiryEmail($_POST);
    error_log("Email sent: " . ($emailSent ? "Yes" : "No"));
}
```

#### 2. `/xsite/mod/product-inquiry/x-product-inquiry.inc.php`
Lines 121-133: Added email sending after database insert

#### 3. `/xsite/mod/page/x-page.inc.php`
Lines 12-24: Added email sending after database insert

---

## BrevoEmailService Class

### Constructor
```php
public function __construct()
```
Initializes service with configuration from constants.

### Public Methods

#### `isConfigured()`
```php
public function isConfigured() : bool
```
Returns true if API key is configured.

**Returns:** `bool` - API key is set and non-empty

**Example:**
```php
$brevo = getBrevoService();
if ($brevo->isConfigured()) {
    echo "Brevo is ready to send emails";
}
```

#### `sendEmail($params)`
```php
public function sendEmail($params) : array
```
Send an email via Brevo API.

**Parameters:**
```php
$params = array(
    'to' => array(
        array(
            'email' => 'recipient@example.com',
            'name' => 'Recipient Name' // optional
        ),
        // Can have multiple recipients
    ),
    'subject' => 'Email Subject',
    'htmlContent' => '<html>...</html>',
    'textContent' => 'Plain text content', // optional
    'replyTo' => array(              // optional
        'email' => 'reply@example.com',
        'name' => 'Reply Name'
    ),
    'cc' => array(                   // optional
        array('email' => 'cc@example.com')
    ),
    'bcc' => array(                  // optional
        array('email' => 'bcc@example.com')
    ),
    'tags' => array(                 // optional
        'tag1', 'tag2'
    )
);
```

**Returns:**
```php
array(
    'success' => true,                    // Email sent successfully
    'messageId' => '12345...',            // Brevo message ID
    'error' => null                       // Error message if failed
)
```

**Example:**
```php
$result = $brevo->sendEmail(array(
    'to' => array(array('email' => 'user@example.com')),
    'subject' => 'Hello',
    'htmlContent' => '<p>Hello World</p>'
));

if ($result['success']) {
    error_log("Email sent, ID: " . $result['messageId']);
} else {
    error_log("Email failed: " . $result['error']);
}
```

### Private Methods

#### `makeApiRequest($endpoint, $method, $data)`
```php
private function makeApiRequest(
    $endpoint,      // API endpoint: /smtp/email
    $method = 'GET',// GET, POST, PUT, DELETE
    $data = null    // Request body data
) : array
```
Internal method for making HTTP requests to Brevo API.

**Returns:**
```php
array(
    'success' => true,
    'data' => array(...), // API response
    'error' => null
)
```

---

## Helper Functions

### `getBrevoService()`
```php
function getBrevoService() : BrevoEmailService
```
Returns singleton instance of BrevoEmailService.

Uses static variable to ensure only one instance per request.

**Example:**
```php
$brevo = getBrevoService();
$brevo->sendEmail(...);
```

### `sendPumpInquiryEmail($inquiryData)`
```php
function sendPumpInquiryEmail($inquiryData) : bool
```
Sends pump inquiry confirmation and admin notification.

**Parameters:**
- `$inquiryData` - Array with keys:
  - `userEmail` - Customer email
  - `fullName` - Customer name
  - `applicationTypeID`, `installationTypeID`, etc. - Form fields

**Returns:** `bool` - true if successful (even if just attempted)

**Notes:**
- Sends two emails: one to customer, one to admin
- Does not block form submission on email failure
- Logs all activity to PHP error_log

**Example:**
```php
if ($DB->dbInsert()) {
    sendPumpInquiryEmail($_POST);
}
```

### `sendProductInquiryEmail($inquiryData)`
```php
function sendProductInquiryEmail($inquiryData) : bool
```
Similar to pump inquiry but for product/motor inquiries.

**Required fields in `$inquiryData`:**
- `userEmail`
- `userName`
- `companyName`
- `userMobile`

### `sendContactUsEmail($contactData)`
```php
function sendContactUsEmail($contactData) : bool
```
Sends contact form confirmation and admin notification.

**Required fields in `$contactData`:**
- `userEmail`
- `userName`
- `userLastName`
- `userMessage`

---

## Admin Email Functions

### `sendPumpInquiryAdminEmail($inquiryData)`
```php
function sendPumpInquiryAdminEmail($inquiryData) : bool
```
Internal function called by `sendPumpInquiryEmail()`.

Sends admin notification with complete inquiry details.

**Recipient:** `ADMIN_NOTIFICATION_EMAIL` or `BREVO_SENDER_EMAIL`

**Tags:** `pump-inquiry`, `admin-notification`

### `sendProductInquiryAdminEmail($inquiryData)`
```php
function sendProductInquiryAdminEmail($inquiryData) : bool
```
Sends motor/product inquiry details to admin.

### `sendContactUsAdminEmail($contactData)`
```php
function sendContactUsAdminEmail($contactData) : bool
```
Sends contact message details to admin.

---

## Email Template Builders

All template functions return HTML string for email body.

### `buildPumpInquiryConfirmationEmail($data)`
Customer confirmation for pump inquiry.

**Available variables:**
```php
$data['fullName']              // Customer name
$data['userEmail']             // Customer email
$data['applicationTypeID']     // Application type
$data['installationTypeID']    // Installation type
$data['preferredContactTime']  // Preferred contact time
// All other form fields...
```

### `buildPumpInquiryAdminEmail($data)`
Admin notification with full inquiry details.

Includes customer info, application details, and technical requirements.

### `buildProductInquiryConfirmationEmail($data)`
Motor inquiry confirmation for customer.

### `buildProductInquiryAdminEmail($data)`
Motor inquiry details for admin.

Includes customer info and motor specifications.

### `buildContactUsConfirmationEmail($data)`
Contact form confirmation.

### `buildContactUsAdminEmail($data)`
Contact message for admin.

---

## Error Handling

### API Errors

All API errors are caught and logged:

```php
// Error logging locations:
error_log("Brevo: API Error Message");  // PHP error_log
```

**Common Errors:**
- `API key not configured` - BREVO_API_KEY is empty
- `Missing required parameters` - to, subject, htmlContent missing
- `Invalid sender` - Sender email not verified in Brevo
- `cURL error` - Network/connectivity issue
- `HTTP 401` - Invalid/expired API key
- `HTTP 403` - Insufficient permissions
- `HTTP 429` - Rate limited
- `HTTP 500` - Brevo server error

### Graceful Degradation

**Important:** Email failures do NOT block form submission.

```php
// Form submission succeeds even if email fails
if ($DB->dbInsert()) {
    $data['err'] = 0;
    $data['msg'] = "Thank you for your submission!";

    // Email sending is optional, won't block on failure
    sendPumpInquiryEmail($_POST);
}
```

This ensures user data is always saved even if email service is down.

---

## Logging

### PHP Error Log

All email activities are logged to PHP error_log:

```php
error_log("Brevo: Email sent successfully. MessageId: 12345");
error_log("Brevo: Email send failed - Invalid sender");
error_log("Brevo API HTTP 401: Invalid API key");
error_log("Brevo Exception: curl error");
```

### Database Logging

Form submission data is always logged to database even if email fails:

- `bombay_pump_inquiry` - Pump inquiry data
- `mx_product_inquiry` - Motor inquiry data
- `mx_contact_us` - Contact form data

### Brevo Dashboard Logging

Log into Brevo and check:
- **Email Logs** → View all sent emails
- **Transactional Emails** → Delivery status
- **Email Statistics** → Open rates, bounces

---

## API Specifications

### Endpoint Used

**POST** `/smtp/email`

Brevo API v3 endpoint for sending transactional emails.

### Request Format

```json
{
  "sender": {
    "email": "from@example.com",
    "name": "Sender Name"
  },
  "to": [
    {
      "email": "to@example.com",
      "name": "Recipient Name"
    }
  ],
  "subject": "Email Subject",
  "htmlContent": "<html>...</html>",
  "tags": ["tag1", "tag2"]
}
```

### Response Format

**Success (200-299):**
```json
{
  "messageId": "< id of the sent transactional email message >"
}
```

**Error:**
```json
{
  "code": "invalid_parameter",
  "message": "Invalid email"
}
```

---

## Performance Considerations

### API Calls

Each email send = 1 API call minimum

**Example:** Contact form submission = 2 API calls
- 1 call for customer confirmation
- 1 call for admin notification

### Rate Limits

Brevo rate limits depend on plan:
- Free: 300 emails/day
- Starter: 20,000 emails/month
- Premium: Unlimited

Monitor API calls in Brevo dashboard.

### Timeout

Default timeout: 10 seconds per API call

```php
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
```

---

## Security Considerations

### API Key Protection

```php
// ✓ Safe - in config file (not in version control)
define("BREVO_API_KEY", "xkeysib_...");

// ✗ Unsafe - hardcoded in function
$apiKey = "xkeysib_..."; // Don't do this
```

**Best Practice:** Use environment variables in production
```php
define("BREVO_API_KEY", getenv('BREVO_API_KEY'));
```

### SSL/TLS

All API requests use HTTPS with certificate verification:

```php
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
```

### Input Sanitization

Email addresses are validated but not sanitized by service.

Use standard PHP validation:
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Invalid email
}
```

### Email Content

HTML content from user input should be escaped:

```php
// In templates:
htmlspecialchars($userData);  // ✓ Safe
$userData;                     // ✗ Unsafe
```

---

## Customization Guide

### Adding New Email Type

To add a new email type:

1. Create email functions in `/core/brevo.inc.php`:
```php
function sendMyNewEmail($data) {
    $brevo = getBrevoService();

    $emailParams = array(
        'to' => array(array('email' => $data['email'])),
        'subject' => 'My Subject',
        'htmlContent' => buildMyNewEmail($data),
        'tags' => array('my-tag')
    );

    return $brevo->sendEmail($emailParams)['success'];
}

function buildMyNewEmail($data) {
    return '<html>...</html>';
}
```

2. Call from form handler:
```php
if ($DB->dbInsert()) {
    require_once(ROOTPATH . "/core/brevo.inc.php");
    sendMyNewEmail($_POST);
}
```

### Modifying Templates

Edit HTML templates in `buildXxxEmail()` functions:

```php
function buildMyEmail($data) {
    return <<<HTML
    <html>
        <body style="font-family: Arial;">
            <h1>Hello {$data['name']}</h1>
            <p>Your data: {$data['value']}</p>
        </body>
    </html>
    HTML;
}
```

### Adding Custom Fields

Add any field from `$data` to templates:

```php
// Available from form POST data:
$data['fieldName']  // Any form field
$data['userEmail']  // All standard fields
$data['userName']
// etc.
```

---

## Testing Guide

### Unit Test Example

```php
<?php
require_once("config.inc.php");
require_once("core/brevo.inc.php");

$brevo = getBrevoService();

// Test 1: Configuration check
assert($brevo->isConfigured(), "Brevo not configured");

// Test 2: Email sending
$result = $brevo->sendEmail(array(
    'to' => array(array('email' => 'test@example.com')),
    'subject' => 'Test',
    'htmlContent' => '<p>Test</p>'
));

assert($result['success'], "Email send failed: " . $result['error']);
assert(!empty($result['messageId']), "No message ID returned");

echo "All tests passed!\n";
?>
```

### Integration Test

1. Submit pump inquiry form
2. Check database for record
3. Check email inbox for customer confirmation
4. Check admin email for notification
5. Check Brevo dashboard for delivery status

---

## References

- **Brevo API Documentation:** https://developers.brevo.com/docs
- **Send Email Endpoint:** https://developers.brevo.com/reference/sendtransacemail
- **Email Logs API:** https://developers.brevo.com/reference/gettransacemail
- **Rate Limits:** https://developers.brevo.com/reference/http-limits

---

## Version History

### v1.0 (Initial Release)
- Complete Brevo API integration
- Support for 3 email types
- Customer confirmation emails
- Admin notification emails
- Error handling and logging
- Template customization support

---

## Support

For issues or questions:
1. Check error logs in PHP error_log
2. Check Brevo Email Logs dashboard
3. Review BREVO_SETUP_GUIDE.md for troubleshooting
4. Contact Brevo support: https://app.brevo.com/support
