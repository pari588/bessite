# SEO & Crawlability Fixes - Google PageSpeed Issues

**Date:** November 21, 2025
**Issue:** Uncrawlable links reported by Google PageSpeed
**Severity:** Medium (affects crawlability, not functionality)

---

## 🔍 Problem Identified

Google PageSpeed reported **uncrawlable links** using `javascript:void(0)` pattern. These links are:
- Not detected as real URLs by search engine crawlers
- Not crawlable (no actual navigation target)
- Common in JavaScript-heavy sites but SEO unfriendly

### Example Uncrawlable Links Found:
```html
<!-- Buttons with JavaScript handlers -->
<a href="javascript:void(0)" class="fa-save button thm-btn" rel="frmPopupEnquiry">
  Send a message
</a>

<!-- Time picker selectors -->
<a class="ptTimeSelectHr" href="javascript: void(0);">1</a>
<a class="ptTimeSelectHr" href="javascript: void(0);">2</a>

<!-- Other form triggers -->
<a href="javascript:void(0)" rel="contactUsForm">Send a message</a>
<a href="javascript:void(0)" rel="pumpInquiryForm">Submit Inquiry</a>
```

### Files Affected:
```
xsite/mod/footer.php                    (Send message button)
xsite/mod/header-webapp.php             (Logout button)
xsite/mod/driver/x-home.php             (Mark In/Out buttons)
xsite/mod/driver/x-login.php            (Login button)
xsite/mod/lead/x-lead.php               (Form buttons)
xsite/mod/leave/x-apply.php             (Form buttons)
xsite/mod/leave/x-leave.php             (Form buttons)
xsite/mod/page/x-contact-us-tpl.php     (Contact form button)
xsite/mod/product-inquiry/              (Inquiry form button)
xsite/mod/pump-inquiry/                 (Inquiry form button)
xsite/vendors/timepicker/timePicker.js  (Time picker library)
```

---

## 💡 Solution Overview

### Approach 1: Use Actual URLs + JavaScript Prevention (RECOMMENDED)
Instead of `javascript:void(0)`, use real URLs that gracefully degrade:

```html
<!-- BEFORE (Bad for SEO) -->
<a href="javascript:void(0)" class="fa-save button thm-btn" rel="frmPopupEnquiry">
  Send a message
</a>

<!-- AFTER (Good for SEO) -->
<a href="#popup-enquiry" class="fa-save button thm-btn" data-form-id="frmPopupEnquiry">
  Send a message
</a>
```

**Benefits:**
- ✅ Crawlable by search engines
- ✅ Accessible without JavaScript
- ✅ Proper anchor semantics
- ✅ Works with keyboard navigation

### Approach 2: Use `button` Element
For action buttons (not actual links):

```html
<!-- BEFORE (Not semantic) -->
<a href="javascript:void(0)" class="button">Click Me</a>

<!-- AFTER (Semantic) -->
<button type="button" class="button">Click Me</button>
```

**Benefits:**
- ✅ Semantic HTML
- ✅ Better accessibility
- ✅ Clearer intent

### Approach 3: Progressive Enhancement
Use real href with fallback:

```html
<a href="/inquiry/form/" class="button" data-modal="true">
  Send Message
</a>
```

---

## 🛠️ Implementation Plan

### Phase 1: Inquiry Form Buttons (High Priority)
These appear on main customer-facing pages:

**Files to Update:**
1. `xsite/mod/footer.php` - Send message button
2. `xsite/mod/page/x-contact-us-tpl.php` - Contact form button
3. `xsite/mod/pump-inquiry/x-pump-inquiry.php` - Pump inquiry button
4. `xsite/mod/product-inquiry/x-product-inquiry.php` - Product inquiry button

**Current Code:**
```html
<a href="javascript:void(0)" class="fa-save button thm-btn" rel="frmPopupEnquiry">
  Send a message
</a>
```

**Updated Code:**
```html
<a href="#pump-inquiry" class="fa-save button thm-btn" data-form-id="frmPopupEnquiry" onclick="event.preventDefault(); volid(0); return false;">
  Send a message
</a>
```

### Phase 2: Authentication Buttons (Medium Priority)
These are not visible to main search engine crawlers but important for UX:

**Files to Update:**
1. `xsite/mod/header-webapp.php` - Logout button
2. `xsite/mod/driver/x-login.php` - Login button
3. `xsite/mod/lead/x-lead.php` - Login/Save buttons
4. `xsite/mod/leave/x-leave.php` - Login/Save buttons

**Updated to:**
```html
<button type="button" class="btn1" id="mark-out" data-driver-id="<?php echo $driverManagementID; ?>">
  Mark Out
</button>
```

### Phase 3: Time Picker Library (Lower Priority)
The timePicker.js library generates these links dynamically. Options:
1. Update library (if newer version available)
2. Suppress from crawlers (robots.txt)
3. Convert to buttons via JavaScript

---

## 📋 Detailed Fix for Each File

### 1. xsite/mod/footer.php
**Issue:** Send message button using `javascript:void(0)`

```html
<!-- BEFORE -->
<a href="javascript:void(0)" class="fa-save button thm-btn" rel="frmPopupEnquiry">
    Send a message
</a>

<!-- AFTER -->
<a href="#contact-form" class="fa-save button thm-btn"
   data-form-id="frmPopupEnquiry"
   onclick="event.preventDefault(); volid(0); return false;">
    Send a message
</a>
```

### 2. xsite/mod/page/x-contact-us-tpl.php
**Issue:** Contact form submit button

```html
<!-- BEFORE -->
<a href="javascript:void(0)" class="fa-save button thm-btn" rel="contactUsForm">
    Send a message
</a>

<!-- AFTER -->
<a href="#contact-form" class="fa-save button thm-btn"
   data-form-id="contactUsForm"
   onclick="event.preventDefault(); submitForm('contactUsForm'); return false;">
    Send a message
</a>
```

### 3. xsite/mod/pump-inquiry/x-pump-inquiry.php
**Issue:** Pump inquiry form button

```html
<!-- BEFORE -->
<a href="javascript:void(0)" class="fa-save button thm-btn" rel="pumpInquiryForm">
    Submit Inquiry
</a>

<!-- AFTER -->
<a href="#pump-inquiry-form" class="fa-save button thm-btn"
   data-form-id="pumpInquiryForm"
   onclick="event.preventDefault(); submitPumpInquiry(); return false;">
    Submit Inquiry
</a>
```

### 4. Driver/Auth Buttons - Convert to `<button>`

```html
<!-- BEFORE -->
<a href="javascript:void(0);" class="btn1" id="mark-out" rel="<?php echo $id; ?>">
    Mark Out
</a>

<!-- AFTER -->
<button type="button" class="btn1" id="mark-out" data-id="<?php echo $id; ?>">
    Mark Out
</button>
```

---

## 📝 Time Picker Fix (jquery.ptTimeSelect.js)

The time picker generates many `href="javascript:void(0)"` links dynamically.

### Option A: Update Robots.txt (Quick Fix)
Prevent crawling of time picker UI:

```
# In xsite/robots.txt
Disallow: */jquery.ptTimeSelect*
```

### Option B: Fix Library (Proper Fix)
Edit `/xsite/vendors/timepicker/timePicker.js`:

Find:
```javascript
return '<a class="ptTimeSelectHr ui-state-default" href="javascript: void(0);">' + hour + '</a>';
```

Replace with:
```javascript
return '<a class="ptTimeSelectHr ui-state-default" href="#" onclick="return false;">' + hour + '</a>';
```

This makes links non-navigable but still crawlable (returns false prevents action).

---

## 🔧 JavaScript Implementation

### Update Event Handlers

Instead of relying on `href="javascript:..."`, use proper event handlers:

```javascript
// File: xsite/inc/js/mellis.js or main JS file

// Setup delegation for inquiry buttons
$(document).on('click', '[data-form-id]', function(e) {
    e.preventDefault();
    var formId = $(this).data('form-id');

    // Open inquiry form
    volid(0);

    // Focus on form if needed
    if(formId) {
        setTimeout(function() {
            $('#' + formId).focus();
        }, 300);
    }

    return false;
});

// Setup button handlers
$(document).on('click', 'button[id="mark-out"]', function(e) {
    e.preventDefault();
    var driverId = $(this).data('id');
    // Handle mark out logic
});
```

---

## 🧪 Testing Checklist

### Before Changes
- [ ] Test all inquiry forms (pump, product, contact)
- [ ] Test driver portal (mark in/out)
- [ ] Test auth pages (login, logout)
- [ ] Check PageSpeed report

### During Changes
- [ ] Update one file at a time
- [ ] Test functionality after each update
- [ ] Verify no JavaScript errors in console

### After Changes
- [ ] Test all forms still work
- [ ] Test buttons still functional
- [ ] Run Google PageSpeed test again
- [ ] Check Search Console (crawlability improvement)
- [ ] Monitor for 404 errors in logs

---

## 📊 Expected Impact

### SEO Improvements
- ✅ Reduced crawl errors
- ✅ Better page crawlability
- ✅ Improved accessibility score
- ✅ Better mobile usability score

### Metrics
```
Before:  X uncrawlable links reported
After:   0 uncrawlable links reported
Impact:  100% improvement in crawlability
```

### Timeline
- Phase 1 (Inquiry buttons): Immediate (high visibility)
- Phase 2 (Auth buttons): This week (medium priority)
- Phase 3 (Time picker): This month (can be deferred)

---

## 🚀 Implementation Priority

### HIGH PRIORITY (Do First)
1. Footer inquiry button
2. Contact us form button
3. Pump inquiry form button
4. Product inquiry form button

**Why:** These are on main customer-facing pages and affect crawlability of important conversions.

### MEDIUM PRIORITY (Do Second)
1. Driver portal buttons
2. Authentication page buttons

**Why:** Less visible to crawlers but important for user functionality.

### LOW PRIORITY (Can Be Deferred)
1. Time picker library
2. Internal form buttons

**Why:** Can be handled via robots.txt or library update later.

---

## 📚 References & Best Practices

### Google Guidelines
- [Make Links Crawlable](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls#use-crawlable-links)
- [Accessibility Guidelines](https://www.w3.org/WAI/WCAG21/Understanding/link-purpose.html)

### HTML Standards
- Use `<a>` for navigation
- Use `<button>` for actions
- Provide meaningful href values

### Progressive Enhancement
1. Make links work without JavaScript
2. Enhance with JavaScript if available
3. Provide fallback for no-JS users

---

## 📋 Implementation Code Examples

### Example 1: Inquiry Form Button (Best Practice)

```html
<!-- HTML -->
<a href="/inquiry/?type=pump" class="fa-save button thm-btn"
   data-modal="true" title="Submit pump inquiry">
    Submit Inquiry
</a>

<!-- JavaScript -->
<script>
$(document).on('click', 'a[data-modal="true"]', function(e) {
    // If JavaScript is available, use modal
    e.preventDefault();
    var type = this.href.split('type=')[1];
    volid(0); // Open modal form
    return false;
});
</script>
```

This way:
- URL is real (`/inquiry/?type=pump`) - crawlable
- JavaScript enhances it to modal - better UX
- Works without JS - degradable

### Example 2: Convert Button to Semantic HTML

```html
<!-- BEFORE: Looks like link but acts like button -->
<a href="javascript:void(0)" class="button">Submit</a>

<!-- AFTER: Actual button -->
<button type="button" class="button">Submit</button>

<!-- OR: Link that acts like navigation -->
<a href="/submit/" class="button" data-ajax="true">Submit</a>
```

---

## 💾 Backup Plan

Before making changes:
```bash
# Create backups of files being modified
cp xsite/mod/footer.php xsite/mod/footer.php.backup.20251121
cp xsite/mod/page/x-contact-us-tpl.php xsite/mod/page/x-contact-us-tpl.php.backup.20251121
# etc...
```

If issues occur, rollback:
```bash
# Restore from backup
cp xsite/mod/footer.php.backup.20251121 xsite/mod/footer.php
```

---

## ✅ Verification Steps

### Step 1: Identify All Issues
```bash
grep -r "javascript:void" /home/bombayengg/public_html/xsite --include="*.php"
```

### Step 2: Update Files
Make changes according to implementation plan above.

### Step 3: Test Locally
- Click all buttons
- Verify forms open
- Check console for errors

### Step 4: Run PageSpeed Test
- Go to https://pagespeed.web.dev/
- Enter URL: https://www.bombayengg.com
- Check for "Uncrawlable links" warning

### Step 5: Submit to Search Console
- Go to Google Search Console
- Request indexing
- Monitor crawl stats

---

## 📞 Summary

**Issue:** Google PageSpeed reports uncrawlable links using `javascript:void(0)`

**Solution:** Replace with semantic HTML and proper event handlers

**Priority:** High (affects SEO)

**Estimated Time:** 1-2 hours for Phase 1

**Expected Benefit:**
- Better crawlability
- Improved accessibility
- Better PageSpeed score
- Potential SEO ranking improvement

---

**Document Created:** November 21, 2025
**Status:** Recommended for implementation
**Version:** 1.0

