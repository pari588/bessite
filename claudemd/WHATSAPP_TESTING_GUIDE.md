# WhatsApp Link Preview - Quick Testing Guide

**Last Updated:** November 9, 2025

---

## Quick Start: Test in 5 Minutes

### Step 1: Pick a Pump Product URL
Use any pump detail page:
```
https://www.bombayengg.net/pump/residential-pumps/mini-pumps/ultimo-ii/
https://www.bombayengg.net/pump/residential-pumps/mini-pumps/flomax-plus-ii/
https://www.bombayengg.net/pump/agricultural-pumps/borewell-submersibles/100w-v/
```

### Step 2: View Source to Verify Meta Tags
1. Visit pump URL in browser
2. Press `Ctrl+U` (or right-click → View Page Source)
3. Search for `og:title`
4. You should see something like:
   ```html
   <meta property="og:title" content="ULTIMO II - ₹4400.00" />
   ```

### Step 3: Test on WhatsApp Web
1. Open WhatsApp Web (https://web.whatsapp.com)
2. Click on any chat
3. Paste pump URL in message box
4. Wait for preview to load (1-2 seconds)
5. Should show:
   - Product image
   - Product name with price
   - Product description

### Step 4: Test on Other Platforms

#### Facebook
1. Go to Facebook Link Debugger: https://developers.facebook.com/tools/debug/sharing/
2. Paste pump URL
3. Click "Scrape Again" to refresh
4. Check preview shows product image + title

#### Twitter
1. Go to Twitter Card Validator: https://cards-dev.twitter.com/validator
2. Paste pump URL
3. Check preview shows product image + title

#### LinkedIn
1. Open LinkedIn (https://www.linkedin.com)
2. Share pump URL
3. Check preview

---

## Expected Results

### ✅ What You Should See

**Page Source (`Ctrl+U`):**
```html
<meta property="og:title" content="ULTIMO II - ₹4400.00" />
<meta property="og:description" content="Premium Crompton mini pump for residential water pressure boosting and domestic applications." />
<meta property="og:image" content="https://www.bombayengg.net/uploads/pump/530_530_crop_100/ultimo-ii.webp" />
<meta property="og:type" content="product" />
```

**WhatsApp Preview:**
```
┌─────────────────────────────────┐
| [Product Image - 530x530 WebP]  |
|                                 |
| ULTIMO II - ₹4400.00            |
| Premium Crompton mini pump...   |
| www.bombayengg.net              |
└─────────────────────────────────┘
```

**Facebook/Twitter/LinkedIn Preview:**
```
┌─────────────────────────────────┐
| [Product Image]                 |
| ULTIMO II - ₹4400.00            |
| Premium Crompton mini pump...   |
└─────────────────────────────────┘
```

---

## Testing Checklist

### Critical Tests (Must Pass)
- [ ] **OG Title:** Shows product name + price (e.g., "ULTIMO II - ₹4400.00")
- [ ] **OG Image:** Shows product image (530x530 WebP format)
- [ ] **OG Description:** Shows first 160 chars of product description
- [ ] **OG Type:** Set to "product" (not "website")
- [ ] **Fallback:** Non-pump pages still show generic company info

### Platform Tests
- [ ] **WhatsApp Web:** Shows product preview correctly
- [ ] **Facebook:** Shows product preview (use debugger)
- [ ] **Twitter:** Shows product preview
- [ ] **LinkedIn:** Shows product preview
- [ ] **Pinterest:** Shows product preview (if shared)

### Additional Tests
- [ ] **Multiple Products:** Test with 3+ different pump products
- [ ] **Different Categories:** Test pumps from different categories
- [ ] **Missing Data:** Test pump without price (should show name only)
- [ ] **Broken Images:** Verify fallback image works if product image missing
- [ ] **Home Page:** Verify still shows company info (not affected)
- [ ] **Category Pages:** Verify still show company info

---

## Troubleshooting

### Issue: Meta tags not showing product info
**Solution:** Clear browser cache and try again
- Ctrl+Shift+Delete → Clear browsing data
- Hard refresh: Ctrl+F5

### Issue: WhatsApp shows old preview
**Solution:** Social platforms cache metadata. Force refresh by:
- Sending URL again in a new chat (may cache fresh)
- Or wait 24-48 hours for automatic cache expiry

### Issue: Image not displaying
**Solution:** Check if file exists
```bash
ls -lh /home/bombayengg/public_html/uploads/pump/530_530_crop_100/ultimo-ii.webp
```
If missing, fallback generic image will be used.

### Issue: Price not showing in title
**Solution:** Check if MRP field is populated in database
```sql
SELECT pumpID, pumpTitle, mrp FROM mx_pump_detail
WHERE status = 1 LIMIT 5;
```

---

## Sample Test Products

### Test Case 1: Mini Pump with Full Data
- **URL:** `/pump/residential-pumps/mini-pumps/ultimo-ii/`
- **Expected Title:** "ULTIMO II - ₹[price]"
- **Expected Image:** ultimo-ii.webp

### Test Case 2: Borewell Submersible
- **URL:** `/pump/agricultural-pumps/borewell-submersibles/100w-v/`
- **Expected Title:** Product name with price
- **Expected Image:** Product image

### Test Case 3: Shallow Well Pump
- **URL:** `/pump/agricultural-pumps/shallow-well-pumps/swj50a-30-plus/`
- **Expected Title:** Product name with price
- **Expected Image:** Product image

### Test Case 4: Non-Pump Page (Should Show Generic Info)
- **URL:** `/` (home page)
- **Expected Title:** "Bombay Engineering Syndicate - Industrial Motors & Pumps Supplier"
- **Expected Image:** Generic company image

---

## Detailed Testing Procedure

### Test 1: Page Source Inspection

**Procedure:**
1. Visit pump URL: `https://www.bombayengg.net/pump/residential-pumps/mini-pumps/ultimo-ii/`
2. Right-click → View Page Source (or Ctrl+U)
3. Search for: `og:title` (Ctrl+F)
4. Verify you see:
   ```html
   <meta property="og:title" content="ULTIMO II - ₹4400.00" />
   ```

**Expected Result:** ✅ PASS

---

### Test 2: WhatsApp Web Preview

**Procedure:**
1. Open WhatsApp Web: https://web.whatsapp.com
2. Click any chat or group
3. Copy pump URL: `https://www.bombayengg.net/pump/residential-pumps/mini-pumps/ultimo-ii/`
4. Paste in message box
5. Wait 2-3 seconds for preview

**Expected Result:** ✅ PASS
- Image displays (product image, 530x530)
- Title shows: "ULTIMO II - ₹4400.00"
- Description shows: "Premium Crompton mini pump..."

**If preview is old:**
- Right-click preview → Copy and re-paste URL in new chat
- Or manually add `?nocache=[timestamp]` to URL (if needed)

---

### Test 3: Facebook Link Debugger

**Procedure:**
1. Go to: https://developers.facebook.com/tools/debug/sharing/
2. Paste pump URL: `https://www.bombayengg.net/pump/residential-pumps/mini-pumps/ultimo-ii/`
3. Click "Scrape Again" button
4. Check preview section

**Expected Result:** ✅ PASS
- Title: Product name with price
- Image: Product image
- Description: Product features (first 160 chars)

**If showing old data:**
- Click "Scrape Again" button
- Wait 10 seconds for fresh scrape

---

### Test 4: Twitter Card Validator

**Procedure:**
1. Go to: https://cards-dev.twitter.com/validator
2. Paste pump URL
3. Check preview

**Expected Result:** ✅ PASS
- Title shows product name + price
- Image displays correctly
- Type shows: "summary_large_image"

---

### Test 5: Multi-Product Test

**Test these 5 pump products:**

```bash
1. https://www.bombayengg.net/pump/residential-pumps/mini-pumps/ultimo-ii/
   Expected: "ULTIMO II - ₹[price]"

2. https://www.bombayengg.net/pump/residential-pumps/mini-pumps/flomax-plus-ii/
   Expected: "FLOMAX PLUS II - ₹[price]"

3. https://www.bombayengg.net/pump/agricultural-pumps/borewell-submersibles/100w-v/
   Expected: "100W V - ₹[price]"

4. https://www.bombayengg.net/pump/residential-pumps/openwell-pumps/horizontal-openwell-/
   Expected: Product name with price

5. https://www.bombayengg.net/pump/pressure-booster-pumps/
   Expected: Shows page-level content (may vary)
```

**For each:** Check WhatsApp preview + page source

**Expected Result:** ✅ All show product-specific info

---

## Sign-Off Checklist

After testing, verify all checkboxes:

```
✅ Product title shows in og:title
✅ Product price shows in og:title
✅ Product image shows in og:image
✅ Product description shows in og:description
✅ og:type is set to "product"
✅ WhatsApp preview shows product info
✅ Facebook preview shows product info
✅ Twitter preview shows product info
✅ LinkedIn preview shows product info
✅ Non-pump pages still show generic info
✅ Home page unaffected
✅ No errors in browser console
✅ Page loads normally (no performance issues)
```

---

## Performance Verification

### Load Time Check
```bash
# Test page load time with curl
curl -w "Time taken: %{time_total}s\n" -o /dev/null -s \
  https://www.bombayengg.net/pump/residential-pumps/mini-pumps/ultimo-ii/

# Expected: < 1.5 seconds
```

### Memory Check
Monitor PHP memory usage doesn't increase:
- Should remain < 2MB additional (constants only)

---

## Rollback Instructions

If any issue occurs, restore backups:

```bash
# Navigate to site root
cd /home/bombayengg/public_html

# Restore header.php
cp xsite/mod/header.php.backup.whatsapp.20251109_113525 xsite/mod/header.php

# Restore x-detail.php
cp xsite/mod/pumps/x-detail.php.backup.whatsapp.20251109_113525 xsite/mod/pumps/x-detail.php

# Clear cache if applicable
php clear_cache.php

echo "Rollback complete!"
```

---

## Verification Commands

### Check if files are modified:
```bash
git diff xsite/mod/header.php | head -20
git diff xsite/mod/pumps/x-detail.php | head -20
```

### Check if constants are being defined:
```bash
grep -n "WHATSAPP_OG" xsite/mod/pumps/x-detail.php
```

### Check if constants are being used:
```bash
grep -n "WHATSAPP_OG" xsite/mod/header.php
```

---

## Success Criteria

### Implementation is successful if:
✅ All 5 test products show product-specific previews on WhatsApp
✅ All 4 platforms (WhatsApp, Facebook, Twitter, LinkedIn) work correctly
✅ Non-pump pages show generic company information
✅ No errors in browser console
✅ Page load time is unchanged
✅ Database queries are unchanged
✅ Existing functionality is preserved

---

## Testing Report Template

Use this to document your testing:

```
Date: _______________
Tester: _______________

Test Results:
[ ] Page source shows correct og:title
[ ] Page source shows correct og:image
[ ] Page source shows correct og:description
[ ] WhatsApp preview works
[ ] Facebook preview works
[ ] Twitter preview works
[ ] LinkedIn preview works
[ ] Multi-product test passed
[ ] Non-pump pages unaffected
[ ] Performance is normal

Issues Found:
_______________________________________________
_______________________________________________

Sign-off: _________________ Date: _____________
```

---

## Contact & Support

If you encounter any issues during testing, refer to:
1. **Main Report:** `WHATSAPP_IMPLEMENTATION_REPORT.md`
2. **Troubleshooting:** See section above
3. **Rollback:** Use instructions above to restore backups

---

**Implementation Status:** ✅ Ready for Testing
**Test Duration:** ~15 minutes
**Difficulty:** Easy (no technical knowledge required)

Good luck with testing! 🚀
