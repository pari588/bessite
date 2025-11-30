# WhatsApp Link Preview Implementation Guide

**Date Created:** November 8, 2025
**Status:** Planning - Ready for Implementation
**Priority:** High (Improves social sharing & user engagement)

---

## Executive Summary

Enable product-specific WhatsApp link previews for pump pages. Currently, all pages show generic company information when shared on WhatsApp. After implementation, each pump product will display with its own image, title, and description.

**Example:**
```
Current: Generic "Bombay Engineering Syndicate" preview
After:   Product-specific "ULTIMO II - ₹4,400" preview with product image
```

---

## Current Situation Analysis

### Files Involved
- **Header File:** `/home/bombayengg/public_html/xsite/mod/header.php` (lines 96-115)
- **Pump Detail Page:** `/home/bombayengg/public_html/xsite/mod/pumps/x-detail.php`
- **Database Tables:** `mx_pump`, `mx_pump_detail`

### Current Meta Tags (Global - Same for ALL Pages)

Location: `/xsite/mod/header.php` lines 96-108

```html
<!-- Open Graph Tags -->
<meta property="og:title" content="Bombay Engineering Syndicate - Industrial Motors & Pumps Supplier" />
<meta property="og:description" content="Energy-efficient motors, submersible pumps & industrial solutions. Trusted supplier since 1957. Locations: Mumbai & Ahmedabad. Free Enquiry Form." />
<meta property="og:url" content="<?php echo SITEURL; ?>" />
<meta property="og:image" content="<?php echo SITEURL; ?>/images/moters.jpeg" />
<meta property="og:image:secure_url" content="<?php echo SITEURL; ?>/images/moters.jpeg" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:type" content="image/jpeg" />
<meta property="og:image:alt" content="Bombay Engineering Syndicate - Industrial Motors and Pumps" />
<meta property="og:type" content="website" />
<meta property="og:locale" content="en_IN" />
<meta property="og:site_name" content="Bombay Engineering Syndicate" />

<!-- Twitter Card Tags -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="Bombay Engineering Syndicate - Motors & Pumps Supplier in Mumbai" />
<meta name="twitter:description" content="Leading supplier of energy-efficient industrial motors and pumps. Serving Mumbai & Ahmedabad since 1957." />
<meta name="twitter:image" content="<?php echo SITEURL; ?>/images/moters.jpeg" />
<meta name="twitter:creator" content="@BombayEngg" />
```

---

## Data Available in Database

### mx_pump Table
```sql
SELECT pumpID, pumpTitle, pumpImage, pumpFeatures FROM mx_pump
WHERE status = 1
```

**Relevant Fields:**
- `pumpTitle` - Product name (e.g., "ULTIMO II")
- `pumpImage` - Image filename (e.g., "ultimo-ii.webp")
- `pumpFeatures` - Product description (70-100 words, HTML formatted)

**Image Full Path:**
```
https://www.bombayengg.net/uploads/pump/530_530_crop_100/{pumpImage}
```

### mx_pump_detail Table
```sql
SELECT pumpID, mrp FROM mx_pump_detail
WHERE pumpID = ? AND status = 1 LIMIT 1
```

**Relevant Fields:**
- `mrp` - Price (e.g., "₹4,400.00")

---

## Site Structure Understanding

### Page Routing
```
All pump pages follow this pattern:
/pump/{category}/{subcategory}/{pump-seo-uri}/

Example:
https://www.bombayengg.net/pump/residential-pumps/mini-pumps/ultimo-ii/
```

### File Structure
```
/xsite/
  ├── mod/
  │   ├── header.php                  (Global headers - ALL pages)
  │   ├── pumps/
  │   │   ├── x-detail.php            (Product detail pages)
  │   │   ├── x-pumps.php             (Category listing pages)
  │   │   └── x-pumps.inc.php         (Functions)
  │   └── ...other modules
  ├── core-site/
  │   ├── pump-schema.inc.php         (Schema functions)
  │   └── common.inc.php
  └── index.php                       (Main entry point)
```

### How Pages Load
1. User visits: `/pump/residential-pumps/mini-pumps/ultimo-ii/`
2. `index.php` routes to `x-detail.php` with pump data
3. `header.php` is included (contains meta tags)
4. Pump-specific data loaded via `getPDetail()` function
5. Schema markup added via `echoProductSchema()` (added Nov 8, 2025)

---

## Implementation Approach

### RECOMMENDED: Enhanced Implementation (Better UX - 1-2 hours)

**Location:** Modify `/xsite/mod/header.php` with dynamic tag generation

**What to Add:**
1. Detect if current page is pump detail page
2. Fetch pump data conditionally
3. Generate all OG tags dynamically
4. Support both static and pump pages
5. Add better semantic markup (`og:type="product"`)

**Benefits:**
- ✅ Better WhatsApp preview with product image
- ✅ Better Facebook preview
- ✅ Better LinkedIn preview
- ✅ Better Pinterest preview
- ✅ Better Twitter preview
- ✅ All social platforms benefit equally
- ✅ Cleaner code (single location for all OG tags)
- ⏱️ Dev Time: 1-2 hours

---

## Alternative: Minimal Implementation (Quick Option - 30 minutes)

**Location:** `/xsite/mod/pumps/x-detail.php` (after line 4, before HTML output)

**Note:** This approach is faster but only optimizes pump detail pages. For a comprehensive solution, Option 2 (Enhanced) is recommended.

**What to Add:**
1. Extract pump title from `$TPL->data['pumpTitle']`
2. Extract product image path from `$TPL->data['pumpImage']`
3. Extract description from `$TPL->data['pumpFeatures']`
4. Get MRP from first detail record
5. Output dynamic OG meta tags (only on pump detail pages)

**Meta Tags to Override:**
```html
<!-- ONLY on pump detail pages, override global og:title, og:description, og:image -->
<meta property="og:title" content="ULTIMO II - ₹4,400 | Bombay Engineering" />
<meta property="og:description" content="Premium Crompton mini pump for residential water pressure..." />
<meta property="og:image" content="https://www.bombayengg.net/uploads/pump/530_530_crop_100/ultimo-ii.webp" />
<meta property="og:type" content="product" />
```

**Code Pattern:**
```php
// In x-detail.php, after getPDetail() call
if (!empty($TPL->data)) {
    $pump_title = $TPL->data['pumpTitle'];
    $pump_image = !empty($TPL->data['pumpImage']) ?
                  UPLOADURL . '/pump/530_530_crop_100/' . $TPL->data['pumpImage'] :
                  SITEURL . '/images/moters.jpeg';
    $pump_desc = substr(strip_tags($TPL->data['pumpFeatures']), 0, 160);

    // Get price from detail record if available
    $price = !empty($pumsDetailArr[0]['mrp']) ?
             str_replace(['₹', ','], '', $pumsDetailArr[0]['mrp']) :
             'Contact';

    // These variables will override global OG tags in header
    // Output dynamic meta tags here
}
```

**Result After Minimal Implementation:**
- ✅ WhatsApp shows product image
- ✅ WhatsApp shows product name
- ✅ WhatsApp shows product description
- ✅ Price visible in title
- ⏱️ Dev Time: 30 minutes

---

## Implementation Checklist

### RECOMMENDED: Enhanced Implementation in header.php
- [ ] Create function to detect if current page is pump detail page
- [ ] Query database for pump data conditionally (only on pump pages)
- [ ] Extract pump title, image, description, and price
- [ ] Generate dynamic OG meta tags with product data
- [ ] Override global tags for pump pages only
- [ ] Add `og:type="product"` for semantic markup
- [ ] Optimize Twitter card tags for social platforms
- [ ] Move logic to separate function file (e.g., `pump-meta.inc.php`) for reusability
- [ ] Test with 3-5 pump products on WhatsApp/Facebook/Twitter
- [ ] Verify image quality and display in all platform previews
- [ ] Check that title, description, and price display correctly
- [ ] Test on non-pump pages to ensure they still show global metadata
- [ ] Monitor social sharing metrics after deployment

### Quick Alternative: Minimal Implementation in x-detail.php
- [ ] Create function to detect pump detail page
- [ ] Extract pump data from `$TPL->data` array
- [ ] Extract detail data (MRP) from database
- [ ] Build dynamic OG meta tag strings
- [ ] Output tags before closing `</head>` tag
- [ ] Test with 3 pump products on WhatsApp/Facebook
- [ ] Verify image quality in previews
- [ ] Check that title and description display correctly

---

## Key Variables & Data Points

### From $TPL->data (Main Pump Record)
```php
$TPL->data['pumpID']          // Pump ID
$TPL->data['pumpTitle']       // "ULTIMO II"
$TPL->data['pumpImage']       // "ultimo-ii.webp"
$TPL->data['pumpFeatures']    // "The ULTIMO II is a premium..."
$TPL->data['kwhp']            // "0.5HP"
$TPL->data['pumpType']        // "Mini Pump"
```

### From Detail Record (Optional)
```php
$pumsDetailArr[0]['mrp']      // "₹4,400.00"
$pumsDetailArr[0]['powerKw']  // "0.37"
$pumsDetailArr[0]['powerHp']  // "0.5"
```

### Generated Values
```php
$product_image_url = UPLOADURL . '/pump/530_530_crop_100/' . $TPL->data['pumpImage'];
// Result: https://www.bombayengg.net/uploads/pump/530_530_crop_100/ultimo-ii.webp

$product_title = $TPL->data['pumpTitle'] . ' - ₹' . $price;
// Result: "ULTIMO II - ₹4,400"

$product_description = substr(strip_tags($TPL->data['pumpFeatures']), 0, 160);
// Result: "The ULTIMO II is a premium Crompton mini pump for residential water pressure boosting and domestic applications. With 0.5 HP..."
```

---

## Testing & Verification

### Test URLs
1. **Detail Page:** `https://www.bombayengg.net/pump/residential-pumps/mini-pumps/ultimo-ii/`
2. **Category Page:** `https://www.bombayengg.net/pump/residential-pumps/mini-pumps/`
3. **Home Page:** `https://www.bombayengg.net/`

### Verification Methods
1. **WhatsApp Web**
   - Paste URL in chat
   - Check preview shows product image, title, description

2. **Facebook Link Debugger**
   - URL: https://developers.facebook.com/tools/debug/sharing/
   - Paste URL and check preview

3. **Twitter Card Validator**
   - URL: https://cards-dev.twitter.com/validator
   - Verify Twitter preview

4. **Page Source Check**
   - Ctrl+U on page
   - Search for `og:title`, `og:image`, `og:description`
   - Verify correct values

---

## Expected Results

### Before Implementation
```
When shared on WhatsApp:
┌─────────────────────────────────┐
| [Generic Company Image]         |
|                                 |
| Bombay Engineering Syndicate -...|
| Leading supplier of energy-...  |
| www.bombayengg.net              |
└─────────────────────────────────┘
```

### After Option 1 (Minimal)
```
When shared on WhatsApp:
┌─────────────────────────────────┐
| [ULTIMO II Product Image]       |
|                                 |
| ULTIMO II - ₹4,400              |
| The ULTIMO II is a premium...   |
| www.bombayengg.net              |
└─────────────────────────────────┘
```

### After Option 2 (Enhanced)
```
When shared on WhatsApp/Facebook:
┌─────────────────────────────────┐
| [High-Quality Product Image]    |
|                                 |
| ULTIMO II - ₹4,400 | Crompton   |
| Premium mini pump for residential|
| water pressure boosting...       |
| www.bombayengg.net              |
└─────────────────────────────────┘
```

---

## Performance & SEO Impact

### Performance Impact
- **Database Queries:** 0 additional (data already loaded)
- **Processing Time:** < 5ms per page
- **Memory Usage:** Minimal (meta tags only)
- **Caching:** Meta tags cached with page

### SEO Impact
- **Positive:** Better social signals, improved CTR from social shares
- **Neutral:** No negative impact on rankings
- **Bonus:** `og:type="product"` helps Google understand content

---

## Related Implementation Notes

### Already Implemented (Nov 8, 2025)
- ✅ Product Schema (JSON-LD) on all pump detail pages
- ✅ BreadcrumbList Schema on all pump pages
- ✅ Global Open Graph tags for all pages
- ✅ Global Twitter Card tags for all pages

### This Implementation Complements
- Existing Product Schema markup
- Existing BreadcrumbList markup
- Global OG tags (will be overridden for pump pages)

### Does NOT Affect
- SEO schema markup (separate from OG tags)
- Site structure or routing
- Database schema
- Existing functionality

---

## Code Examples

### Simple Approach (Best for Getting Started)

```php
// Add to x-detail.php after line 8 (after getPDetail call)

// Generate dynamic OG meta tags for pump pages
if (!empty($TPL->data['pumpTitle'])) {
    $og_title = $TPL->data['pumpTitle'];

    // Add price if available
    if (!empty($pumsDetailArr) && !empty($pumsDetailArr[0]['mrp'])) {
        $mrp = str_replace(['₹', ','], '', $pumsDetailArr[0]['mrp']);
        $og_title .= ' - ₹' . $mrp;
    }

    $og_image = !empty($TPL->data['pumpImage']) ?
                UPLOADURL . '/pump/530_530_crop_100/' . $TPL->data['pumpImage'] :
                SITEURL . '/images/moters.jpeg';

    $og_description = substr(strip_tags($TPL->data['pumpFeatures']), 0, 160);

    // Store in a variable that can be used in header
    define('DYNAMIC_OG_TITLE', $og_title);
    define('DYNAMIC_OG_IMAGE', $og_image);
    define('DYNAMIC_OG_DESCRIPTION', $og_description);
}
```

Then in `header.php`, replace og:title, og:image, og:description with:
```php
<meta property="og:title" content="<?php echo defined('DYNAMIC_OG_TITLE') ? DYNAMIC_OG_TITLE : 'Bombay Engineering...'; ?>" />
<meta property="og:image" content="<?php echo defined('DYNAMIC_OG_IMAGE') ? DYNAMIC_OG_IMAGE : SITEURL . '/images/moters.jpeg'; ?>" />
<meta property="og:description" content="<?php echo defined('DYNAMIC_OG_DESCRIPTION') ? DYNAMIC_OG_DESCRIPTION : 'Energy-efficient...'; ?>" />
```

---

## Deployment Notes

### Files to Modify
1. `/xsite/mod/pumps/x-detail.php` - Add dynamic OG generation
2. `/xsite/mod/header.php` - Use dynamic OG values (if using header approach)

### Testing Before Going Live
1. Test on localhost with local WhatsApp Web
2. Share test URLs on WhatsApp
3. Verify preview shows correctly
4. Check database values are correct
5. Test with 3-5 different pump products

### Rollback Plan
- Simply comment out new code in x-detail.php
- Default to global OG tags in header.php
- No data loss or site issues

---

## Future Enhancements

### Related Improvements to Consider
1. **Dynamic Meta Tags for Pump Categories**
   - Show category image and description in social previews

2. **Structured Data Enhancement**
   - Add price schema.org support
   - Add availability markup

3. **Image Optimization**
   - Use 1200x630px images instead of 530x530 for better social display
   - Add more vibrant product photography

4. **Analytics Tracking**
   - Monitor social share performance
   - Track CTR improvements from social preview changes

---

## Contact Points & Questions

**When Implementing, Verify:**
1. Are `$TPL->data` values available in `x-detail.php`?
2. Is `UPLOADURL` constant defined?
3. Are detail records loaded in `$pumsDetailArr`?
4. What's the best approach: define() constants or array variables?
5. Should this also apply to category pages (x-pumps.php)?

---

## Summary for Quick Reference

| Aspect | Details |
|--------|---------|
| **Objective** | Add product-specific previews (WhatsApp, Facebook, LinkedIn, Pinterest, Twitter) |
| **Current Issue** | All pages show generic company preview |
| **Recommended Solution** | Dynamic OG meta tags in header.php (Enhanced Implementation) |
| **Implementation Time** | 1-2 hours (Recommended Option 2) or 30 mins (Quick Option 1) |
| **Files to Modify** | header.php (+ optional pump-meta.inc.php for reusability) |
| **Database Queries** | 0 additional (use existing data) |
| **Performance Impact** | Negligible |
| **Testing** | WhatsApp, Facebook, LinkedIn, Pinterest, Twitter |
| **Priority** | High (improves user engagement on all platforms) |
| **Status** | Ready for implementation |
| **Recommended Approach** | **OPTION 2 - Enhanced Implementation** ✅ |

---

**Last Updated:** November 8, 2025
**Ready to Implement:** YES ✅
**Complexity:** LOW ✅
**Recommended:** YES - HIGH ROI ✅

