# OCR Handler Debugging - Next Steps

## Current Status

The OCR PDF upload is failing with error **"✗ OCR: Tesseract processing failed with code: 1"** but the handler logs are NOT being created. This indicates:

1. ✅ Files ARE being uploaded to the directory
2. ❌ Handler function logs are NOT being created
3. ❌ OCR processing is NOT completing

### Critical Finding
The **absence of log files** proves that:
- Either the handler is not being called
- OR the handler is being called but the error happens before the log is written
- OR there's a PHP error preventing execution

---

## Step 1: Access the Diagnostic Tool

Open in your browser:
```
http://your-domain/diagnose_ocr_handler.php
```

This page shows:
- ✅ Whether log files exist
- ✅ System configuration
- ✅ Ability to view logs in real-time
- ✅ Test upload functionality

---

## Step 2: Clear All Logs

Before testing, clear all existing logs to get a clean slate:

```bash
rm -f /tmp/ocr_handler_start.log /tmp/ocr_handler.log /tmp/ocr_debug.log
```

Or use the diagnostic tool's "Clear All Logs" button.

---

## Step 3: Test Upload

### Option A: Via Diagnostic Tool (Recommended)
1. Go to http://your-domain/diagnose_ocr_handler.php
2. Scroll to "4. Test OCR Upload"
3. Select a test PDF
4. Click "Test Upload"
5. Check the result

### Option B: Via Web Form
1. Go to Admin Panel → Fuel Expenses
2. Click "Add New"
3. Upload a PDF to the "Bill Image" field
4. Check for the error message

### Option C: Via Test Page
1. Go to http://your-domain/test_ocr_web_simulation.php
2. Upload a PDF
3. Check the response

---

## Step 4: Check the Logs

After uploading, immediately check these logs:

### Handler Start Log (New!)
```bash
cat /tmp/ocr_handler_start.log
```
This shows if the handler file was even loaded.

**Expected Output:**
```
[2025-11-30 14:32:15] Handler called with xAction=OCR, POST size=2, FILES size=1
```

**If this is empty/missing:** The handler was not called at all

### Handler Function Log
```bash
cat /tmp/ocr_handler.log
```
This shows what the handler function is doing.

### OCR Core Debug Log
```bash
cat /tmp/ocr_debug.log
```
This shows detailed OCR processing steps.

---

## Step 5: Interpret the Results

### Scenario 1: All Logs Exist
✅ **Good!** The handler IS being called.
- Check the logs for the actual error
- Look for Tesseract command execution details
- Check pdftoppm conversion success

### Scenario 2: Only Handler Start Log Exists
🟡 **Partially working** - Handler file loaded but handler function not called
- Check the xAction parameter (should be "OCR")
- Check if $_POST is being properly parsed
- Verify JSON response headers are set

### Scenario 3: No Logs Exist
🔴 **Handler not being called**
- The AJAX request is not reaching the handler
- OR there's a PHP error before logging starts
- Check browser console (F12) for errors
- Check Apache/Nginx error log:
  ```bash
  tail -50 /var/log/apache2/error.log
  tail -50 /var/log/nginx/error.log
  ```

### Scenario 4: Logs Show Handler Called But No OCR Logs
🟡 **Handler called but OCR not running**
- Check if processBillImageOCR() is being called
- Look for file upload validation errors
- Verify file moved successfully to upload directory

---

## Step 6: Manual Testing

If logs don't appear, test the handler directly:

### Test 1: Verify Handler is Reachable
```bash
curl -X POST \
  -F "xAction=OCR" \
  -F "billImage=@test.pdf" \
  http://your-domain/xadmin/mod/fuel-expense/x-fuel-expense.inc.php
```

### Test 2: Check Server Error Logs
```bash
# Apache
tail -100 /var/log/apache2/error.log | grep -i ocr

# Nginx + PHP-FPM
tail -100 /var/log/php-fpm/error.log | grep -i ocr
```

### Test 3: Test OCR Function Directly
A CLI test script exists at:
```bash
php /home/bombayengg/public_html/test_ocr_quick.php
```

This tests OCR directly without web handler - if it works, the issue is in the web request path.

---

## Step 7: What to Share for Debugging

After running the diagnostic, collect and share:

1. **Handler Start Log**
   ```bash
   cat /tmp/ocr_handler_start.log
   ```

2. **Handler Function Log**
   ```bash
   cat /tmp/ocr_handler.log
   ```

3. **OCR Debug Log**
   ```bash
   cat /tmp/ocr_debug.log
   ```

4. **Browser Console Error (F12)**
   - Open Developer Tools (F12)
   - Go to Console tab
   - Look for [OCR] prefixed messages
   - Copy any red error messages

5. **Server Error Log**
   ```bash
   tail -100 /var/log/apache2/error.log
   # or
   tail -100 /var/log/php-fpm/error.log
   ```

6. **Test File Details**
   - PDF filename
   - PDF file size
   - Whether it's scanned (image-based) or digital

---

## Key Log Locations

| Log | Path | Created By |
|-----|------|------------|
| Handler Start | `/tmp/ocr_handler_start.log` | Handler initialization |
| Handler Function | `/tmp/ocr_handler.log` | processBillImageOCR() |
| OCR Core Debug | `/tmp/ocr_debug.log` | processBillOCR() |

---

## Common Issues & Fixes

### Issue 1: Handler Start Log Doesn't Exist
**Problem:** The handler file isn't being executed
**Solution:**
- Verify handler path is correct: `/xadmin/mod/fuel-expense/x-fuel-expense.inc.php`
- Check if file has PHP syntax errors: `php -l x-fuel-expense.inc.php`
- Verify the AJAX request is sending to correct URL

### Issue 2: Handler Log Empty But Files Uploaded
**Problem:** Handler called but processBillImageOCR() not running
**Solution:**
- Check if xAction parameter is exactly "OCR"
- Verify file upload validation passes
- Check if error happens before logging starts

### Issue 3: OCR Log Shows "Tesseract return code: 1"
**Problem:** Tesseract command failed
**Solution:**
- Check if pdftoppm succeeded (output PNG file created)
- Verify PNG file is readable by Tesseract user
- Test Tesseract manually: `tesseract /tmp/test.png /tmp/output`

---

## Quick Command Summary

```bash
# Clear all logs
rm -f /tmp/ocr_handler_start.log /tmp/ocr_handler.log /tmp/ocr_debug.log

# View handler start log
tail -20 /tmp/ocr_handler_start.log

# View handler function log
tail -50 /tmp/ocr_handler.log

# View OCR debug log
tail -100 /tmp/ocr_debug.log

# Test OCR directly
php /home/bombayengg/public_html/test_ocr_quick.php

# Check Tesseract
which tesseract
tesseract --version

# Check pdftoppm
which pdftoppm
pdftoppm --version
```

---

## Next Actions

1. **Clear logs** - Fresh start
2. **Upload test PDF** - Via diagnostic tool or web form
3. **Check handler start log** - See if handler was called
4. **Check other logs** - Follow the error chain
5. **Share results** - Include all logs for debugging

The diagnostic tool at `/diagnose_ocr_handler.php` makes this process much easier!
