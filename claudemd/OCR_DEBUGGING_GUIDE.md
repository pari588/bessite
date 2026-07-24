# OCR PDF Upload Debugging Guide

**Issue**: Web form uploads of scanned PDF fuel bills fail with "Tesseract processing failed with code: 1"
**Status**: Under investigation - CLI tests pass, web form fails
**Date**: November 30, 2025

## What Works ✅

- Direct PHP CLI calls to `processBillOCR()` - **100% success**
- pdftoppm PDF→PNG conversion - **returns code 0**
- Tesseract OCR on PNG files - **returns code 0**
- Date/amount extraction - **72-92% confidence**
- Direct file write to /tmp - **works fine**

## What Fails ❌

- Web form PDF uploads - **still returning "Tesseract processing failed with code: 1"**
- This is despite identical code working in CLI

## Debugging Tools Available

### 1. Real-Time Log Viewer (Browser)
```
http://your-domain/check_ocr_logs.php
```
Opens a live dashboard showing:
- `/tmp/ocr_debug.log` - Core OCR function logs
- `/tmp/ocr_handler.log` - Web handler logs
- File statistics (size, last modified, permissions)
- Auto-refreshes every 2 seconds

### 2. Log Endpoints (for Curl/API)
```bash
curl http://your-domain/get_ocr_logs.php
```
Returns JSON with:
- Complete log contents (last 100 lines)
- File information and metadata
- Timestamps

### 3. Manual Log Checking
```bash
# View OCR core function logs
cat /tmp/ocr_debug.log | tail -50

# View web handler logs
cat /tmp/ocr_handler.log | tail -20

# Clear all logs
rm /tmp/ocr_debug.log /tmp/ocr_handler.log
```

## What to Check When Uploading

1. **Upload a PDF through the web form**
2. **Wait a few seconds** (OCR takes 1-2 seconds)
3. **Check the browser error message**
4. **Check the logs using one of the tools above**

### Critical Log Entry Points

#### If Upload Fails at File Move:
```
[2025-11-30 12:00:00] Calling processBillOCR(/path/to/file.pdf)
```
(This line will NOT appear if move_uploaded_file fails)

#### If OCR Function is Called:
```
[2025-11-30 12:00:00] processBillOCR called with: /path/to/file.pdf
[2025-11-30 12:00:00] File exists: /path/to/file.pdf, size: XXXXX
[2025-11-30 12:00:00] pdftoppm path: /bin/pdftoppm
[2025-11-30 12:00:00] pdftoppm return code: X
```

#### If Tesseract is Called:
```
[2025-11-30 12:00:00] Running Tesseract: /usr/bin/tesseract '...'
[2025-11-30 12:00:00] Input file: ... (exists: yes)
[2025-11-30 12:00:00] Process user: 1003
[2025-11-30 12:00:00] Temp dir: /tmp (writable: yes)
[2025-11-30 12:00:00] Tesseract return code: X
[2025-11-30 12:00:00] Tesseract passthru output: ...
```

## Possible Failure Scenarios

### Scenario 1: File Upload Fails
**Symptoms**: `ocr_handler.log` shows NO "Calling processBillOCR" entry
**Cause**: File never reaches OCR function
**Check**:
- Does `/home/bombayengg/public_html/uploads/fuel-expense/` directory exist?
- Is it writable? (`ls -la | grep fuel-expense`)
- Is file size limit set correctly? (max 5MB)

### Scenario 2: pdftoppm Fails
**Symptoms**: Log shows "pdftoppm return code: non-zero"
**Cause**: PDF→PNG conversion failed
**Check**:
- Is pdftoppm installed? (`which pdftoppm`)
- Can it read the PDF? (`pdftoppm -singlefile -png test.pdf test 2>&1`)
- PDF file corruption?

### Scenario 3: Tesseract Fails
**Symptoms**: Log shows "Tesseract return code: 1"
**Cause**: Most likely - Tesseract can't read the PNG or has no output
**Check**:
- Is PNG file readable? Check ocr_debug.log for "Input file: ... (exists: yes)"
- Is output file being created? Check "Expected output: ... (exists: yes)"
- What does passthru output show? (errors will be logged here)

### Scenario 4: PHP-FPM User Permissions
**Symptoms**: Everything works in CLI, fails in web
**Cause**: PHP-FPM runs as different user with different permissions
**Check**:
- CLI user: `whoami` → shows your user
- Web user: Check "Process user:" in ocr_debug.log → shows UID
- Are temp files writable by both users?

## Files Modified for Debugging

### Core OCR Library
**File**: `/home/bombayengg/public_html/core/ocr.inc.php`

Enhanced with:
- Custom logging function writing to `/tmp/ocr_debug.log`
- Detailed pdftoppm execution logging
- Tesseract passthru output capture (all stderr + stdout)
- Temp directory file listing on failure
- Process user and permissions logging

### Web Handler
**File**: `/home/bombayengg/public_html/xadmin/mod/fuel-expense/x-fuel-expense.inc.php`

Enhanced with:
- Handler-level logging to `/tmp/ocr_handler.log`
- Tracks processBillOCR entry and exit points
- Logs return status from OCR function

### Log Viewer Pages
- `/check_ocr_logs.php` - Browser dashboard
- `/get_ocr_logs.php` - JSON API endpoint

## Fix Attempts So Far

1. **Extended year validation** (Nov 30, 12:30)
   - Allows dates up to 2 years in future
   - Fixed "future date rejection" issue
   - ✅ Working in CLI tests

2. **Enhanced date pattern regex** (Nov 30, 12:35)
   - Handles OCR artifacts like `{7-11-2026`
   - ✅ Working in CLI tests

3. **pdftoppm flag change** (Nov 29)
   - Changed from `-ppm` to `-png`
   - ✅ Working in CLI tests

4. **Comprehensive logging system** (Nov 30, 12:40)
   - Custom log file for OCR function
   - Handler-level logging
   - Log viewer dashboard
   - 🔄 Waiting for web upload to test

## Next Steps

1. **Upload a PDF** through the Fuel Expenses module web form
2. **Note the error message** shown in browser
3. **Open** `http://your-domain/check_ocr_logs.php`
4. **Copy the logs** and send them to me
5. **Identify the failure point**:
   - Is handler being called? (check ocr_handler.log)
   - Is OCR function being called? (check ocr_debug.log)
   - What's the return code?
   - What's the full error output?

Based on the logs, we can then:
- Fix file permissions issues
- Adjust PHP-FPM configuration
- Work around system limitations
- Or identify the true root cause

## Testing Locally (No Web Form)

If you want to simulate a web upload without the browser:

```bash
# Create test file
php /home/bombayengg/public_html/test_ocr_quick.php

# Check logs
cat /tmp/ocr_debug.log | grep -A 2 "Tesseract"
```

## Commands Reference

```bash
# Clear all logs
rm /tmp/ocr_debug.log /tmp/ocr_handler.log

# Check OCR debug log
cat /tmp/ocr_debug.log | tail -50

# Check handler log
cat /tmp/ocr_handler.log

# Monitor logs in real-time
tail -f /tmp/ocr_debug.log
tail -f /tmp/ocr_handler.log

# Search for errors
grep -i error /tmp/ocr_debug.log
grep -i failed /tmp/ocr_debug.log
grep "return code: 1" /tmp/ocr_debug.log
```

## Contact Support

When reporting the issue, please include:
1. Full content of `/tmp/ocr_debug.log`
2. Full content of `/tmp/ocr_handler.log`
3. Error message from browser
4. PDF filename that was uploaded
5. Size of the PDF file
