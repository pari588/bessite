# Brevo Email Integration - Quick Start Guide

## What Was Implemented

The website now has automatic email notifications for three forms:
- ✓ Pump Inquiry Form
- ✓ Product (Motor) Inquiry Form
- ✓ Contact Us Form

When someone submits a form, they get a confirmation email and the admin gets a notification email.

---

## Quick Setup (5 Minutes)

### Step 1: Get Brevo API Key (2 minutes)
1. Go to https://app.brevo.com and create account
2. Go to **Settings** → **Keys & Tokens** → **API Keys**
3. Click **Create a new API key**
4. Copy the API key (starts with `xkeysib_`)

### Step 2: Add API Key to Website (2 minutes)
1. Open `/home/bombayengg/public_html/config.inc.php`
2. Find this section:
```php
define("BREVO_API_KEY", "");
```
3. Paste your API key:
```php
define("BREVO_API_KEY", "xkeysib_your_key_here");
```
4. Save the file

### Step 3: Verify Sender Email (1 minute)
1. In Brevo dashboard: **Senders & Lists** → **Add Sender**
2. Use: `noreply@bombayengg.net` (or any verified domain email)
3. Click verification link in your email
4. Update `config.inc.php` if you used different email:
```php
define("BREVO_SENDER_EMAIL", "noreply@bombayengg.net");
```

**Done!** Emails will now send automatically.

---

## Test It

Fill out any form on the site and check your email inbox for confirmation.

---

## Configuration Options

### Basic Setup
```php
define("BREVO_API_KEY", "xkeysib_your_key_here");
define("BREVO_SENDER_EMAIL", "noreply@bombayengg.net");
define("BREVO_SENDER_NAME", "Bombay Engineering Syndicate");
```

### Optional: Send Admin Emails to Different Address
```php
define("ADMIN_NOTIFICATION_EMAIL", "admin@example.com");
```

If not set, admin emails go to `BREVO_SENDER_EMAIL`.

---

## Email Templates

All email templates are in: `/home/bombayengg/public_html/core/brevo.inc.php`

### Customizing Emails

Find the template function:
- `buildPumpInquiryConfirmationEmail()` - Pump form customer email
- `buildPumpInquiryAdminEmail()` - Pump form admin email
- `buildProductInquiryConfirmationEmail()` - Motor form customer email
- `buildProductInquiryAdminEmail()` - Motor form admin email
- `buildContactUsConfirmationEmail()` - Contact form customer email
- `buildContactUsAdminEmail()` - Contact form admin email

Edit the HTML inside to customize colors, text, branding, etc.

---

## Monitoring

### Check Email Status
1. Log into Brevo: https://app.brevo.com
2. Go to **Email Logs** → **Transactional Emails**
3. See all sent emails, delivery status, bounces

### View Errors
1. Check PHP error logs:
   - Usually: `/var/log/php-fpm/www-error.log` or similar
   - Search for "Brevo" entries
2. All errors are logged automatically

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Emails not sending | Check API key is correct in `config.inc.php` |
| "Invalid sender" error | Verify email in Brevo (Senders & Lists) |
| Emails in spam | Check your spam folder, whitelist sender |
| No admin emails | Set `ADMIN_NOTIFICATION_EMAIL` in config |

---

## File Changes

These files were modified:
1. `config.inc.php` - Added API key configuration
2. `core/brevo.inc.php` - New file with email service
3. `xsite/mod/pump-inquiry/x-pump-inquiry-inc.php` - Added email sending
4. `xsite/mod/product-inquiry/x-product-inquiry.inc.php` - Added email sending
5. `xsite/mod/page/x-page.inc.php` - Added email sending

---

## For Developers

### Function Reference

```php
// Send pump inquiry email
sendPumpInquiryEmail($inquiryData);

// Send product inquiry email
sendProductInquiryEmail($inquiryData);

// Send contact form email
sendContactUsEmail($contactData);

// Get Brevo service instance
$brevo = getBrevoService();

// Check if configured
if ($brevo->isConfigured()) {
    // Can send emails
}
```

### Email Parameters

```php
$params = array(
    'to' => array(
        array('email' => 'user@example.com', 'name' => 'John Doe')
    ),
    'subject' => 'Email Subject',
    'htmlContent' => '<h1>HTML content</h1>',
    'tags' => array('tag1', 'tag2'), // Optional
    'replyTo' => array('email' => 'reply@example.com'), // Optional
    'cc' => array(...), // Optional
    'bcc' => array(...) // Optional
);

$result = $brevo->sendEmail($params);
// Returns: array('success' => true/false, 'messageId' => '...', 'error' => '...')
```

---

## Support

- **Brevo API Docs:** https://developers.brevo.com
- **Brevo Help:** https://app.brevo.com/support
- **Status Page:** https://status.brevo.com

---

## Next Steps

1. Follow "Quick Setup" above
2. Test by submitting a form
3. Customize email templates if desired
4. Monitor emails in Brevo dashboard
5. Set up alerts/monitoring as needed

That's it! Emails are now live on your website.
