# OCR Handler Debugging - Complete Summary

## Problem Summary

When uploading a PDF through the web form for OCR processing, you receive the error:
```
✗ OCR: Tesseract processing failed with code: 1
```

However, the handler logs are NOT being created, which indicates the handler function is not being called at all.

---

## What We've Added

### 1. Early Handler Logging (Line 33-34 in x-fuel-expense.inc.php)
Added logging at the very beginning of the handler to track if it's being called:
```php
$handlerStartLog = sys_get_temp_dir() . '/ocr_handler_start.log';
@file_put_contents($handlerStartLog, "[" . date('Y-m-d H:i:s') . "] Handler called...");
```

This creates a file `/tmp/ocr_handler_start.log` immediately when the handler is invoked.

### 2. Diagnostic Tools (New Pages)

#### A. Complete Diagnostic Dashboard
**URL:** `http://your-domain/diagnose_ocr_handler.php`

Features:
- ✅ Shows status of all log files
- ✅ Displays system configuration
- ✅ View logs in real-time
- ✅ Test upload functionality
- ✅ Clear all logs at once

#### B. Handler Endpoint Tester
**URL:** `http://your-domain/test_handler_endpoint.php`

Features:
- ✅ Test 1: Handler reachability
- ✅ Test 2: OCR action recognition
- ✅ Test 3: File upload and OCR
- ✅ Test 4: Check if logs were created
- ✅ View all logs

### 3. Enhanced Log API (Updated get_ocr_logs.php)
Now supports:
- Viewing individual log files
- Clearing all logs at once
- Checking specific log status

---

## How to Use - Step by Step

### Step 1: Clear All Logs (Fresh Start)
Open your terminal and run:
```bash
rm -f /tmp/ocr_handler_start.log /tmp/ocr_handler.log /tmp/ocr_debug.log
```

Or use the diagnostic tool's "Clear All Logs" button.

### Step 2: Test the Handler

Open in your browser:
```
http://your-domain/test_handler_endpoint.php
```

This is the best starting point because it:
- Tests handler reachability
- Tests with minimal parameters
- Checks if logs are created
- Shows raw responses

Click these buttons in order:
1. **Test Handler Reachability** - Should show handler is responding
2. **Test OCR Action** - Should show handler recognizes the OCR action
3. **Test OCR Action (with file upload)** - Upload a PDF to test full flow
4. **Check If Logs Were Created** - Shows which log files exist

### Step 3: Interpret Results

#### All Tests Pass
✅ If tests 1-3 all show success and logs are created in test 4:
- Handler is working correctly
- Issue is likely in OCR processing itself
- Check the `/tmp/ocr_debug.log` for Tesseract details

#### Tests 1-2 Pass But Test 3 Fails
🟡 If handler reachability works but file upload fails:
- File upload validation failing
- Check the error message in test 3 response
- Review `/tmp/ocr_handler.log` for details

#### Test 1 or 2 Fails
🔴 If handler is not responding:
- Handler file path is incorrect
- Handler file has PHP errors
- Web server not routing request correctly
- Check browser console (F12) for network errors

#### No Logs Created After Tests
🔴 Handler is being called but logs not written:
- Check PHP user has write permission to `/tmp`
- Check if error happens before log statement
- Check server error logs

---

## View All Logs

Open the diagnostic dashboard:
```
http://your-domain/diagnose_ocr_handler.php
```

Scroll to section **"3. View Recent Logs"** and click:
- **View Handler Start Log** - Shows when handler was called
- **View Handler Log** - Shows processBillImageOCR() execution
- **View OCR Debug Log** - Shows OCR/Tesseract processing details

Or use terminal:
```bash
# Handler start log (new!)
cat /tmp/ocr_handler_start.log

# Handler function log
cat /tmp/ocr_handler.log

# OCR debug log
cat /tmp/ocr_debug.log
```

---

## Log Interpretation Guide

### Handler Start Log (`/tmp/ocr_handler_start.log`)
**Purpose:** Proves the handler file is being executed

**Expected output:**
```
[2025-11-30 14:32:15] Handler called with xAction=OCR, POST size=2, FILES size=1
```

**If missing:**
- Handler file is not being called
- Check if AJAX URL is correct: `/xadmin/mod/fuel-expense/x-fuel-expense.inc.php`
- Check browser console for network errors

### Handler Function Log (`/tmp/ocr_handler.log`)
**Purpose:** Shows processBillImageOCR() execution steps

**Expected output:**
```
[2025-11-30 14:32:15] processBillImageOCR() called
[2025-11-30 14:32:15] File uploaded: test.pdf
[2025-11-30 14:32:15] File moved successfully to: /path/to/file (size: 12345)
[2025-11-30 14:32:15] File verified as readable
[2025-11-30 14:32:15] Calling processBillOCR(/path/to/file)
[2025-11-30 14:32:20] processBillOCR returned status=success
```

**If missing:**
- Handler was called but processBillImageOCR() was not executed
- Check if xAction parameter is exactly "OCR"
- Check for PHP errors in server logs

### OCR Debug Log (`/tmp/ocr_debug.log`)
**Purpose:** Shows OCR processing in detail

**Expected output:**
```
[OCR] Processing file: /path/to/bill_xxx.pdf (size: 12345)
[OCR] Extracting PDF pages...
[OCR] pdftoppm succeeded, using: /tmp/ocr_xxx.png
[OCR] Running Tesseract...
[OCR] Tesseract succeeded, output file exists: YES
[OCR] Extracted text: "..."
[OCR] Extracted date: 2025-11-30
[OCR] Extracted amount: 1500.00
```

**If shows error:**
```
[OCR] pdftoppm return code: 1
```
- PDF conversion failed
- Check if pdftoppm is installed
- Check if file permissions allow conversion

```
[OCR] Tesseract return code: 1
```
- Tesseract command failed
- Check if Tesseract is installed
- Check if PNG file from pdftoppm is readable
- Check if file path has spaces (needs escaping)

---

## Quick Diagnosis Flow

1. **Clear logs**
   ```bash
   rm -f /tmp/ocr_handler*.log /tmp/ocr_debug.log
   ```

2. **Upload PDF via web form or test page**
   - Web form: Admin → Fuel Expenses → Add New → Upload PDF
   - Test page: http://your-domain/test_handler_endpoint.php → Test File Upload

3. **Check if handler start log exists**
   ```bash
   cat /tmp/ocr_handler_start.log
   ```
   - **If exists:** Handler IS being called → Go to step 4
   - **If missing:** Handler NOT being called → Debug network request

4. **Check if handler function log exists**
   ```bash
   cat /tmp/ocr_handler.log
   ```
   - **If exists:** processBillImageOCR() is running → Go to step 5
   - **If missing:** Function not called → Check xAction parameter

5. **Check OCR debug log**
   ```bash
   cat /tmp/ocr_debug.log
   ```
   - Look for error messages
   - Check pdftoppm return code
   - Check Tesseract return code

---

## Troubleshooting Table

| Symptom | Cause | Solution |
|---------|-------|----------|
| No logs exist after upload | Handler not called | Check AJAX URL, browser network tab, server error logs |
| Handler start log only | xAction not "OCR" or not reaching processBillImageOCR | Verify JavaScript sends xAction=OCR, check _POST parsing |
| Handler log but no OCR debug log | OCR function not called | Verify file upload validation passes, check for early errors |
| "pdftoppm return code: 1" | PDF conversion failed | Check pdftoppm installed, test with: `pdftoppm -singlefile -png test.pdf /tmp/test` |
| "Tesseract return code: 1" | OCR failed | Check Tesseract installed, verify PNG is readable, test with: `tesseract /tmp/test.png /tmp/output` |
| "Unable to extract date" | Date not found in OCR text | PDF may not be scanned correctly, try manual entry |

---

## Testing Tools Available

### 1. Diagnostic Dashboard
**URL:** http://your-domain/diagnose_ocr_handler.php
- Best for comprehensive testing
- View all log statuses
- Test upload
- View logs in real-time

### 2. Handler Endpoint Tester
**URL:** http://your-domain/test_handler_endpoint.php
- Best for quick diagnosis
- Tests handler reachability
- Shows raw responses
- Checks which logs were created

### 3. CLI Test Script
**Command:** `php /home/bombayengg/public_html/test_ocr_quick.php`
- Tests OCR function directly
- Useful if web handler fails but CLI works

### 4. Web Simulation Test
**URL:** http://your-domain/test_ocr_web_simulation.php
- Simulates actual form upload
- Good for testing complete flow

---

## Server Error Log Locations

If handler is not responding, check:

```bash
# Apache errors
tail -100 /var/log/apache2/error.log

# Nginx errors
tail -100 /var/log/nginx/error.log

# PHP-FPM errors
tail -100 /var/log/php-fpm/error.log

# System errors
tail -100 /var/log/syslog
```

Search for errors related to the handler file:
```bash
grep -i "x-fuel-expense" /var/log/apache2/error.log
```

---

## Files Modified/Created

### Modified Files
- `/home/bombayengg/public_html/xadmin/mod/fuel-expense/x-fuel-expense.inc.php`
  - Added early handler logging at lines 33-34

- `/home/bombayengg/public_html/get_ocr_logs.php`
  - Enhanced to support viewing individual log files
  - Added clear all logs functionality

### New Files Created
- `/home/bombayengg/public_html/diagnose_ocr_handler.php` - Diagnostic dashboard
- `/home/bombayengg/public_html/test_handler_endpoint.php` - Endpoint tester
- `/home/bombayengg/public_html/DIAGNOSTIC_NEXT_STEPS.md` - Detailed guide
- `/home/bombayengg/public_html/OCR_HANDLER_DEBUGGING_SUMMARY.md` - This file

---

## What's the Next Step?

1. **Open the endpoint tester:**
   ```
   http://your-domain/test_handler_endpoint.php
   ```

2. **Follow the tests in order:**
   - Test 1: Handler reachability
   - Test 2: OCR action
   - Test 3: File upload (upload a PDF)
   - Test 4: Check if logs exist

3. **Check the logs:**
   - If Handler Start Log exists → Handler IS being called
   - If Handler Function Log exists → processBillImageOCR() IS running
   - If OCR Debug Log exists → OCR processing IS happening

4. **Share results:**
   - Screenshots of test results
   - Content of all three log files
   - Browser console errors (F12)
   - PDF filename and size

This will tell us exactly where the process is failing!

---

## Quick Reference Commands

```bash
# Clear all logs
rm -f /tmp/ocr_handler*.log /tmp/ocr_debug.log

# View handler start log
tail -f /tmp/ocr_handler_start.log

# View handler function log
tail -f /tmp/ocr_handler.log

# View OCR debug log
tail -f /tmp/ocr_debug.log

# Test pdftoppm
pdftoppm -singlefile -png /tmp/test.pdf /tmp/test

# Test Tesseract
tesseract /tmp/test.png /tmp/output

# Check if tools installed
which pdftoppm tesseract

# Test CLI OCR directly
php /home/bombayengg/public_html/test_ocr_quick.php
```

---

## Summary

We've added comprehensive logging and diagnostic tools to help identify exactly where the OCR process is failing. The key is checking the logs in order:

1. **Handler Start Log** → Is handler being called?
2. **Handler Function Log** → Is OCR function running?
3. **OCR Debug Log** → What's the actual OCR error?

Start with the endpoint tester: http://your-domain/test_handler_endpoint.php
