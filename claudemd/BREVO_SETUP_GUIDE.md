# Brevo Email Notifications Setup Guide

## Overview
This document provides step-by-step instructions to configure Brevo (formerly Sendinblue) email notifications for the Bombay Engineering Syndicate website. The system will automatically send email confirmations and admin notifications when users submit:
1. Pump Inquiry Forms
2. Product (Motor) Inquiry Forms
3. Contact Us Forms

---

## Prerequisites
- Active Brevo account (Sign up at https://app.brevo.com)
- Brevo API key
- Valid sender email address verified in Brevo

---

## Step 1: Create a Brevo Account & Get API Key

### 1.1 Sign Up for Brevo
1. Visit https://app.brevo.com
2. Click "Create free account"
3. Fill in your details and verify your email

### 1.2 Generate API Key
1. Log in to Brevo dashboard
2. Go to **Settings** → **Keys & Tokens** → **API Keys**
3. Click **Create a new API key**
4. Give it a name (e.g., "Bombay Engineering Website")
5. Select **Full access** permissions
6. Click **Generate**
7. **Copy the API key** (you'll need this in the next step)

### 1.3 Verify Sender Email
1. In Brevo dashboard, go to **Senders & list**
2. Click **Add a sender**
3. Enter your sender email (e.g., `noreply@bombayengg.net`)
4. Verify the email by clicking the link sent to that address

---

## Step 2: Configure API Credentials

### 2.1 Update config.inc.php

Open `/home/bombayengg/public_html/config.inc.php` and find the Brevo configuration section:

```php
// ===== BREVO EMAIL CONFIGURATION =====
define("BREVO_API_KEY", "");  // Add your API key here
define("BREVO_API_URL", "https://api.brevo.com/v3");
define("BREVO_SENDER_NAME", "Bombay Engineering Syndicate");
define("BREVO_SENDER_EMAIL", "noreply@bombayengg.net");  // Update with your verified sender email
```

### 2.2 Add Your Values

Replace with your actual values:

```php
// ===== BREVO EMAIL CONFIGURATION =====
define("BREVO_API_KEY", "xkeysib_your_actual_api_key_here");
define("BREVO_API_URL", "https://api.brevo.com/v3");
define("BREVO_SENDER_NAME", "Bombay Engineering Syndicate");
define("BREVO_SENDER_EMAIL", "noreply@bombayengg.net");
```

### 2.3 (Optional) Add Admin Notification Email

If you want admin notifications sent to a specific email address, add this to `config.inc.php`:

```php
define("ADMIN_NOTIFICATION_EMAIL", "admin@bombayengg.net");
```

If not defined, admin notifications will be sent to the sender email.

---

## Step 3: Email Templates & Customization

### 3.1 Email Template Locations

All email templates are defined in `/home/bombayengg/public_html/core/brevo.inc.php`:

**Customer Confirmation Emails:**
- `buildPumpInquiryConfirmationEmail()` - Pump inquiry confirmation
- `buildProductInquiryConfirmationEmail()` - Motor inquiry confirmation
- `buildContactUsConfirmationEmail()` - Contact form confirmation

**Admin Notification Emails:**
- `buildPumpInquiryAdminEmail()` - Pump inquiry details for admin
- `buildProductInquiryAdminEmail()` - Motor inquiry details for admin
- `buildContactUsAdminEmail()` - Contact details for admin

### 3.2 Customizing Email Templates

To customize email templates:

1. Open `/home/bombayengg/public_html/core/brevo.inc.php`
2. Find the template function you want to modify
3. Edit the HTML content inside the function
4. The templates use standard HTML and CSS for styling

**Example: Customizing Pump Inquiry Confirmation Email**

```php
function buildPumpInquiryConfirmationEmail($data)
{
    // Your custom email HTML here
    return $html;
}
```

Key variables available in templates:
- `$data['fullName']` - Customer name
- `$data['userEmail']` - Customer email
- `$data['applicationTypeID']` - Application type
- All other form fields are available in `$data` array

---

## Step 4: Email Sending Flow

### 4.1 How It Works

When a user submits a form:

1. **Form is submitted** → Form validation occurs
2. **Data is saved to database** → Inquiry is recorded
3. **Brevo API is called** → Two emails are sent:
   - **Confirmation email** to the customer
   - **Admin notification** to admin email address
4. **Response returned** to user

### 4.2 Error Handling

- If Brevo is not configured, emails are skipped but form submission succeeds
- Email sending failures do NOT block form submissions
- All email activities are logged to PHP error log
- Failed emails can be tracked in Brevo dashboard

---

## Step 5: Testing Email Configuration

### 5.1 Manual Testing

You can test email sending with this simple test script:

**Create file: `/home/bombayengg/public_html/test_brevo.php`**

```php
<?php
// Test Brevo Email Configuration
require_once("config.inc.php");
require_once("core/brevo.inc.php");

$brevo = getBrevoService();

if (!$brevo->isConfigured()) {
    echo "❌ Brevo is NOT configured. Check your API key in config.inc.php\n";
    exit;
}

echo "✓ Brevo API Key is configured\n";

// Test sending an email
$testData = array(
    'to' => array(
        array(
            'email' => 'your-test-email@gmail.com',
            'name' => 'Test User'
        )
    ),
    'subject' => 'Test Email from Bombay Engineering',
    'htmlContent' => '<h1>Test Email</h1><p>This is a test email from Brevo integration.</p>',
    'tags' => array('test')
);

$result = $brevo->sendEmail($testData);

if ($result['success']) {
    echo "✓ Test email sent successfully!\n";
    echo "Message ID: " . $result['messageId'] . "\n";
} else {
    echo "❌ Test email failed: " . $result['error'] . "\n";
}
?>
```

### 5.2 Form Submission Testing

1. Go to: `https://www.bombayengg.net/pump-inquiry` (or any form page)
2. Fill in the form with test data
3. Submit the form
4. Check your test email inbox for confirmation
5. Check admin email for notification

### 5.3 Monitoring Email Delivery

1. Log in to Brevo dashboard
2. Go to **Email Logs** → **Transactional Emails**
3. Search for emails from your campaign
4. View delivery status and bounce information

---

## Step 6: Production Deployment Checklist

- [ ] API key is configured in `config.inc.php`
- [ ] Sender email is verified in Brevo
- [ ] Test email sends successfully
- [ ] Pump inquiry form sends email
- [ ] Product inquiry form sends email
- [ ] Contact form sends email
- [ ] Admin notification email is configured
- [ ] Email templates have been reviewed and customized
- [ ] Brevo account is on appropriate pricing plan
- [ ] Error logs are being monitored

---

## Troubleshooting

### Issue: "Brevo API not configured"
**Solution:**
- Check `config.inc.php` for BREVO_API_KEY
- Ensure the API key value is not empty
- Verify API key is correct from Brevo dashboard

### Issue: "Email send failed - Invalid sender"
**Solution:**
- Ensure sender email in config.inc.php is verified in Brevo
- Go to Brevo → Senders & Lists → Verify the email

### Issue: "Email send failed - Authentication failed"
**Solution:**
- Check API key is correct
- Regenerate API key in Brevo if needed
- Copy the full key including the prefix (xkeysib_)

### Issue: Emails not being received
**Solution:**
- Check Brevo Email Logs for bounce/error messages
- Verify recipient email address is valid
- Check spam/junk folder
- Ensure sending email is properly authenticated (SPF, DKIM)

### Issue: Customer confirmation emails not sent but admin emails work
**Solution:**
- Check customer email address is valid
- Ensure email format is correct (no spaces, valid domain)
- Customer emails might be in spam - check spam folder

---

## Email Configuration Details

### Email Types

**1. Pump Inquiry Notifications**
- **Customer Email:** Confirmation of submission with inquiry summary
- **Admin Email:** Complete inquiry details with contact information
- **Tags:** `pump-inquiry`, `customer-confirmation` / `admin-notification`

**2. Product (Motor) Inquiry Notifications**
- **Customer Email:** Motor inquiry confirmation
- **Admin Email:** Motor specifications and customer details
- **Tags:** `product-inquiry`, `customer-confirmation` / `admin-notification`

**3. Contact Form Notifications**
- **Customer Email:** Acknowledgment of message received
- **Admin Email:** Full message with contact details
- **Tags:** `contact-us`, `customer-confirmation` / `admin-notification`

---

## API Rate Limits

Brevo has the following API rate limits:
- **Free Plan:** 300 emails/day
- **Starter Plan:** 20,000 emails/month
- **Premium Plan:** Unlimited

For details, visit: https://help.brevo.com/en/articles/2068518-api-overview

---

## Security Best Practices

1. **Never commit API keys to version control**
   - API key is sensitive - keep it in config.inc.php only
   - Add config.inc.php to .gitignore if using Git

2. **Use environment-specific configuration**
   - Development: Test API key for testing
   - Production: Production API key for live emails

3. **Monitor email logs**
   - Check Brevo Email Logs regularly
   - Set up Brevo alerts for failures

4. **Verify sender email**
   - Only verified emails can send
   - Prevent email spoofing

---

## File Modifications Summary

The following files were modified for Brevo integration:

1. **`/config.inc.php`**
   - Added Brevo configuration constants

2. **`/core/brevo.inc.php`** (New file)
   - Created Brevo email service class
   - Implements REST API integration
   - Contains all email templates

3. **`/xsite/mod/pump-inquiry/x-pump-inquiry-inc.php`**
   - Added call to `sendPumpInquiryEmail()` after form submission

4. **`/xsite/mod/product-inquiry/x-product-inquiry.inc.php`**
   - Added call to `sendProductInquiryEmail()` after form submission

5. **`/xsite/mod/page/x-page.inc.php`**
   - Added call to `sendContactUsEmail()` after form submission

---

## Support & Resources

- **Brevo Documentation:** https://developers.brevo.com
- **Brevo Email API:** https://developers.brevo.com/reference/sendtransacemail
- **Brevo Status Page:** https://status.brevo.com
- **Contact Brevo Support:** https://app.brevo.com/support

---

## Version Information

- **Brevo API Version:** v3
- **Integration Date:** 2024
- **PHP Version Required:** 7.0+
- **Curl Extension Required:** Yes

---

## Changelog

### v1.0 - Initial Release
- Pump inquiry email notifications
- Product inquiry email notifications
- Contact form email notifications
- Admin notification system
- Email templates with customization support
- Error handling and logging

---

## Next Steps

1. Complete all steps in this guide
2. Test the configuration
3. Customize email templates if needed
4. Monitor deliverability in Brevo dashboard
5. Set up webhooks (optional) for advanced tracking

---

**Questions or Issues?** Check the Troubleshooting section or contact your system administrator.
