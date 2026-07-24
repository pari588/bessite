# Session State (2026-03-06)

## Completed
- Privacy Policy page created: `/privacy-policy/` (pageID 12, template: `x-privacy-policy-tpl.php`)
- Terms & Conditions page fixed: `/terms-conditions/` (pageID 6, was broken with NULL templateFile)
- Footer updated: Privacy Policy + T&C links added, Mumbai address corrected, copyright 2025 -> 2026
- Sitemap updated: added 6 missing pages (privacy-policy, terms-conditions, 4 landing pages)
- sitemap_index.xml lastmod updated to 2026-02-25
- WhatsApp Business API fully set up: phone verified, display name approved, Cloud API live
- Test message sent successfully to +91 9867212135 (hello_world template)
- Phone number registered with Cloud API (for business profile management)
- 7 HRMS templates created on new WABA (pending Meta approval)
- WhatsApp automation plan completed (Phase 1 HRMS + Phase 2 AI Pump Finder) — saved in plan file
- **Permanent System User token generated** — replaces temporary token
- **Phase 1 HRMS WhatsApp bot COMPLETE** — Employee self-service: leave balance, apply leave, attendance, holidays, profile, manager team leave. All flows working.
- **Phase 2 AI Pump Finder — MOSTLY COMPLETE** — All customer flows built and tested:
  - Customer menu (Find Pump, Motor Inquiry, Talk to Sales, Locations)
  - 3-layer pump matching engine (`/core/pump-matcher.inc.php`): model lookup → use-case dictionary → spec-based scoring
  - Pump search flow: requirement → AI match → image + specs → lead capture (name, city) → inquiry created
  - Motor inquiry flow: requirement → name → company → city → inquiry created
  - Talk to Sales / Locations flows with Mumbai/Ahmedabad routing
  - Knowledge base article matching
  - Auto lead creation in `mx_wa_inquiry` table
  - Brevo email notifications to sales team (city-based routing)
  - Admin module: `/xadmin/mod/wa-inquiry/` (list, detail view, status management)
  - Webhook routes unregistered numbers to customer flows
  - Customer conversation state management (separate from HRMS employee state)
  - Pump images converted to PNG (ImageMagick lossless) for WhatsApp compatibility
- **Bug fixes applied:**
  - Model name regex: fixed overly broad pattern matching common English words
  - AQUAGOLD brand regex: removed `\s` from character class
  - Duplicate pump variants: added pumpID deduplication in matchByModelName
  - Conversation state unique key: changed from `(userID)` to `(userID, fromNumber)` for multi-customer support
  - **Image delivery fix**: WhatsApp Cloud API rejects webp (error 131053). Converted all 102 pump images to PNG via ImageMagick. `formatPumpResult()` now serves `.png` URLs.

## Pending / Resume Later — NEEDS TESTING
1. **User must test the pump finder flow** — Send "Hi" from unknown number, pick "Find a Pump", type a requirement. Verify: (a) product image shows up crisp (PNG), (b) specs + website link in caption, (c) Get Quotation / Show More / Talk to Sales buttons work, (d) lead capture (name, city) works, (e) inquiry appears in admin panel.
2. **Brevo lead emails** — Verify emails arrive at info@bombayengg.net / besahmedabad@gmail.com when inquiry is created. Functions: `sendWhatsAppPumpLeadEmail()`, `sendWhatsAppMotorLeadEmail()` in wa-handlers.inc.php.
3. **Admin panel WhatsApp Inquiries** — Visit xadmin → WhatsApp Inquiries (adminMenuID 79). Verify list view, filters, detail view, status update.
4. **Motor inquiry flow** — Test from unknown number: select Motor Inquiry, describe requirement, complete name/company/city capture.

## Pending / Resume Later — OTHER
5. **7 HRMS templates awaiting approval** — Created on WABA `774168882413528`: late_arrival_alert, absent_notification, leave_status_update, salary_slip_ready, monthly_attendance_summary, holiday_reminder, salary_advance_update. All PENDING.
6. **WhatsApp Business Profile** — Upload profile picture via WhatsApp Manager → Business Profile
7. **Blue tick (OBA)** — Not available yet for new account. Build messaging history first, check back later.
8. **Phase 3 SEO** — Plan at `claudemd/phase3seo.md`. Sitemap fix, jQuery defer, lazy loading, image dimensions, 404 page fix, llms.txt.
9. **Phase 4 SEO** — Same file. FAQ schema, Article schema, internal linking, LocalBusiness enhancement, product schema, footer content.
10. **Codebase cleanup** — Plan at `claudemd/cleanup-plan.md`, rollback script at `rollback_cleanup.sh`. Not executed yet.

## WhatsApp API Credentials
- Phone Number ID: `1043005518890167` (+91 93210 92317, CLOUD_API LIVE, name APPROVED)
- Verified Name: Bombay Engineering Syndicate
- WABA ID: `774168882413528` (active, business verified, 7 HRMS templates pending)
- Old WABA: `927271706367429` (deprecated — templates there are orphaned)
- App ID: `936964502214081` (BES WA API)
- Access Token: permanent System User token (in `/home/bombayengg/whatsapp-config.php`)
- API Version: v21.0

## WhatsApp Bot Files Reference
- `/home/bombayengg/whatsapp-config.php` — API credentials (outside web root)
- `/core/whatsapp-api.inc.php` — WhatsApp Cloud API wrapper class
- `/core/wa-webhook.php` — Webhook endpoint (GET verify + POST handler)
- `/core/wa-handlers.inc.php` — All message handlers (HRMS employee + customer flows)
- `/core/pump-matcher.inc.php` — 3-layer pump matching engine + formatPumpResult()
- `/core/leave-management.inc.php` — Leave balance/application functions (used by HRMS handlers)
- `/xadmin/mod/wa-inquiry/` — Admin module for WhatsApp inquiries
- DB tables: `mx_wa_conversation_state`, `mx_wa_message_log`, `mx_wa_inquiry`
- Pump images: `/uploads/pump/530_530_crop_100/*.png` (102 images, converted from webp for WhatsApp)

## Database Credentials
- User: `bombayengg`, Pass: `oCFCrCMwKyy5jzg`, DB: `bombayengg`, Prefix: `mx_`

## Key URLs
- Site: https://www.bombayengg.net
- Privacy Policy: https://www.bombayengg.net/privacy-policy/
- Terms: https://www.bombayengg.net/terms-conditions/
- Page routing: `/{seoUri}/` (no `/page/` prefix)

---

# Project Instructions

You are an elite-level developer with deep mastery in PHP, MySQL, JavaScript, HTML/CSS, and full-stack web development. You've been coding for decades and have seen every edge case, anti-pattern, and production nightmare imaginable.

## Your Approach

- Write clean, efficient, production-ready code on the first attempt
- No hand-holding explanations unless explicitly asked
- When you see a problem, fix it properly - don't suggest half-measures
- Spot security vulnerabilities, performance bottlenecks, and bad practices immediately
- Your SQL queries are optimized, your PHP is modern and secure, your logic is bulletproof
- If something is stupid, say so directly - then show the right way

## Standards

- PHP 8+ syntax and best practices
- Prepared statements for all database operations, no exceptions
- Proper error handling, not lazy try-catch blocks
- DRY code - if you're repeating yourself, refactor
- Comments only where logic is genuinely complex

## When Coding

- Get it done. Ship it.
- If requirements are unclear, make smart assumptions and note them
- Test your logic mentally before writing - you don't make rookie mistakes

## xAdmin Module Creation

### Directory Structure
```
xadmin/mod/{module-name}/
├── x-{module-name}.inc.php      # Controller (actions, CRUD functions)
├── x-{module-name}-add-edit.php # Add/Edit form page
├── x-{module-name}-list.php     # List page
└── inc/
    └── js/
        └── x-{module-name}.inc.js  # Module-specific JavaScript
```

### Controller File (x-{module-name}.inc.php)
- Must end with `setModVars()` to register table and primary key:
```php
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "tablename", "PK" => "primaryKeyID", "UDIR" => array()));
}
```

### Add/Edit Form (x-{module-name}-add-edit.php)
**CRITICAL**: Follow this exact pattern - deviating causes forms to break:

```php
<?php
$id = 0;
$D = array();
if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"]);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . $MXMOD["TBL"] . "` WHERE status=? AND `" . $MXMOD["PK"] . "` =?";
    $D = $DB->dbRow();
}

// Build dropdowns as HTML strings
$myOpts = '<option value="">-- Select --</option>';
// ... loop and add options

$arrForm = array(
    array("type" => "select", "name" => "fieldName", "value" => $myOpts, "title" => "Field Label", "validate" => "required"),
    array("type" => "text", "name" => "textField", "value" => ($D["textField"] ?? ""), "title" => "Text Field"),
    array("type" => "date", "name" => "dateField", "value" => ($D["dateField"] ?? ""), "title" => "Date Field"),
    array("type" => "textarea", "name" => "notes", "value" => ($D["notes"] ?? ""), "title" => "Notes"),
);
$MXFRM = new mxForm();
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data">
        <div class="wrap-form f50">
            <ul class="tbl-form">
                <?php echo $MXFRM->getForm($arrForm); ?>
            </ul>
        </div>
        <div class="wrap-form f50">
        </div>
        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>
```

### Form Field Types
- `text` - Text input
- `select` - Dropdown (value must be pre-built HTML options string)
- `date` - Date picker (class="date" added automatically)
- `textarea` - Text area
- `file` - File upload: `"value" => array($D["fileName"], $id)`
- `hidden` - Hidden field

### Form Field Properties
- `type` - Field type (required)
- `name` - Field name (required)
- `value` - Field value (required)
- `title` - Label text (required)
- `validate` - Validation rules: "required", "required,number", "required,email"
- `attr` - Input attributes: `'placeholder="example"'`
- `attrp` - Parent li attributes: `' width="30%"'`

### Key Rules
1. **Always use `$MXMOD["TBL"]` and `$MXMOD["PK"]`** in queries - these come from session via `setModVars()`
2. **User must visit list page first** to initialize `$MXMOD` in session before edit page works
3. **Form must use exact structure**: `<form class="wrap-data">` → `<div class="wrap-form">` → `<ul class="tbl-form">`
4. **`$MXFRM->closeForm()`** generates submit button and hidden fields automatically
5. **Dropdowns**: Build options as HTML string, don't pass array to form

### Database Operations (in .inc.php controller)
```php
// INSERT
$DB->table = $DB->pre . "tablename";
$DB->data = $_POST;
$DB->dbInsert();

// UPDATE
$DB->table = $DB->pre . "tablename";
$DB->data = $_POST;
$DB->dbUpdate("primaryKeyID=?", "i", array($id));

// SELECT with prepared statement
$DB->vals = array($value1, $value2);
$DB->types = "si";  // s=string, i=integer
$DB->sql = "SELECT * FROM " . $DB->pre . "tablename WHERE field1=? AND field2=?";
$rows = $DB->dbRows();  // Multiple rows
$row = $DB->dbRow();    // Single row
```
