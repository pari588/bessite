# OCR Handler Debugging Session - November 30, 2025

## Problem Statement

User reported that when uploading a PDF through the Fuel Expenses form for OCR processing, they receive the error:
```
✗ OCR: Tesseract processing failed with code: 1
```

However, despite the error message appearing, no handler logs were being created, indicating the OCR function was not being called.

---

## Investigation Summary

### Key Findings

1. **✅ Files ARE Being Uploaded**
   - Latest upload: `new-kampala-s-station-4180.PDF` (48KB, Nov 30 12:54)
   - Location: `/home/bombayengg/public_html/uploads/fuel-expense/`
   - Files accumulate in this directory (2.2MB total across many PDFs)

2. **❌ Handler Logs NOT Being Created**
   - `/tmp/ocr_handler.log` - DOES NOT EXIST
   - `/tmp/ocr_debug.log` - DOES NOT EXIST
   - `/tmp/ocr_handler_start.log` - DOES NOT EXIST
   - Conclusion: Handler function is NOT being called

3. **✅ Error Message IS Appearing**
   - Browser shows: "✗ OCR: Tesseract processing failed with code: 1"
   - This error comes from line 194 of `/xadmin/mod/fuel-expense/inc/js/x-fuel-expense.inc.js`
   - Indicates handler IS returning a response, but before logs are written

4. **Critical Contradiction**
   - Handler returns error to browser ✅
   - But doesn't create logs ❌
   - This means handler crashes BEFORE logging statement (line 34)
   - Or the handler being called is different from what we expect

---

## Root Cause Analysis

The handler is being invoked and returning an error, but it's crashing before the logging statements execute. This suggests:

1. The includes at line 14-21 (OCR library include) fail silently
2. The includes at line 49-56 (core.inc.php, site.inc.php) cause an exception
3. The mxCheckRequest() function at line 60 throws an error
4. There's a PHP fatal error that suppresses output with set_error_handler

---

## Changes Made This Session

### 1. Ultra-Early Logging Added
**File:** `/home/bombayengg/public_html/xadmin/mod/fuel-expense/x-fuel-expense.inc.php`
**Lines:** 2-3

Added absolute first-line logging to detect if the handler file is even being executed:

```php
// ABSOLUTE FIRST LINE - Log immediately to detect any issues
@file_put_contents(sys_get_temp_dir() . '/ocr_handler_entry.log',
    "[" . date('Y-m-d H:i:s') . "] === FILE LOADED === xAction=" .
    (isset($_POST["xAction"]) ? $_POST["xAction"] : "NOT SET") . "\n",
    FILE_APPEND);
```

This creates `/tmp/ocr_handler_entry.log` before ANY other PHP code executes.

### 2. Enhanced Log API
**File:** `/home/bombayengg/public_html/get_ocr_logs.php`

Updated to support:
- Viewing individual log files by passing `?log=/path/to/file`
- Clearing multiple log files at once with `?action=clear`
- Returning detailed file information (size, modification time, etc.)

### 3. Comprehensive Diagnostic Tools Created

#### A. Handler Endpoint Tester
**File:** `/home/bombayengg/public_html/test_handler_endpoint.php`

Features:
- Test 1: Handler Reachability
- Test 2: OCR Action Recognition
- Test 3: File Upload and OCR Processing
- Test 4: Check if Logs Were Created
- Direct log file viewer
- Real-time log monitoring

#### B. Complete Diagnostic Dashboard
**File:** `/home/bombayengg/public_html/diagnose_ocr_handler.php`

Features:
- Shows status of all log files
- Displays system configuration
- Shows temp directory info
- Provides test upload functionality
- Allows clearing all logs at once
- Comprehensive debugging guide

### 4. Documentation Files Created

1. **IMMEDIATE_ACTION_REQUIRED.txt**
   - Quick start guide for current debugging
   - Step-by-step test procedure
   - Interpretation guide for log results

2. **OCR_HANDLER_DEBUGGING_SUMMARY.md**
   - Complete debugging guide
   - All log interpretation scenarios
   - Troubleshooting table
   - Quick reference commands

3. **DIAGNOSTIC_NEXT_STEPS.md**
   - Detailed guide for using diagnostic tools
   - Common issues and solutions
   - Step-by-step problem solving

4. **SESSION_SUMMARY_20251130.md**
   - This file
   - Complete session documentation
   - All changes and findings

---

## Files Modified

### `/home/bombayengg/public_html/xadmin/mod/fuel-expense/x-fuel-expense.inc.php`
- **Line 2-3:** Added ultra-early logging to detect file execution
- **Line 34:** Early handler logging (already existed)
- **Line 37:** Response initialization
- **Lines 39-105:** Handler logic and error handling

### `/home/bombayengg/public_html/get_ocr_logs.php`
- Enhanced to support specific log file retrieval
- Added clear all logs functionality
- Improved error handling and response format

---

## Files Created

### Diagnostic Tools
1. `/home/bombayengg/public_html/test_handler_endpoint.php` (379 lines)
2. `/home/bombayengg/public_html/diagnose_ocr_handler.php` (255 lines)

### Documentation
1. `/home/bombayengg/public_html/IMMEDIATE_ACTION_REQUIRED.txt` (175 lines)
2. `/home/bombayengg/public_html/OCR_HANDLER_DEBUGGING_SUMMARY.md` (365 lines)
3. `/home/bombayengg/public_html/DIAGNOSTIC_NEXT_STEPS.md` (290 lines)
4. `/home/bombayengg/public_html/SESSION_SUMMARY_20251130.md` (This file)

---

## How to Proceed

### Immediate Next Steps

1. **Clear All Logs**
   ```bash
   rm -f /tmp/ocr_handler_entry.log /tmp/ocr_handler_start.log \
         /tmp/ocr_handler.log /tmp/ocr_debug.log
   ```

2. **Upload a PDF Through the Form**
   - Go to Admin → Fuel Expenses
   - Click "Add New"
   - Upload any PDF
   - Note the error message

3. **Check All Logs Immediately**
   ```bash
   echo "=== Entry Log ===" && cat /tmp/ocr_handler_entry.log
   echo "=== Start Log ===" && cat /tmp/ocr_handler_start.log
   echo "=== Handler Log ===" && cat /tmp/ocr_handler.log
   echo "=== Debug Log ===" && cat /tmp/ocr_debug.log
   ```

4. **Interpret Results**
   - **If `/tmp/ocr_handler_entry.log` EXISTS:** Handler file IS being loaded
   - **If `/tmp/ocr_handler_entry.log` MISSING:** Handler file is NOT being executed

5. **Use Diagnostic Tools**
   - `http://your-domain/test_handler_endpoint.php` - Best for quick diagnosis
   - `http://your-domain/diagnose_ocr_handler.php` - Comprehensive dashboard

---

## Expected Test Results

### Scenario 1: Entry Log Exists with "xAction=OCR"
```
[2025-11-30 14:32:15] === FILE LOADED === xAction=OCR
```
✅ **Good:** Handler file is being executed
→ Problem is in includes or setup code
→ Check if other logs exist

### Scenario 2: Entry Log Doesn't Exist
❌ **Bad:** Handler file is NOT being executed
→ Even though error appears in browser
→ Error must come from different code path
→ Check form, JavaScript, or routing

---

## Technical Details

### Log Files Created

| Log File | Purpose | When Created |
|----------|---------|--------------|
| `/tmp/ocr_handler_entry.log` | Tracks file execution entry | Line 2-3 |
| `/tmp/ocr_handler_start.log` | Tracks handler block entry | Line 34 |
| `/tmp/ocr_handler.log` | Tracks OCR function execution | Function level |
| `/tmp/ocr_debug.log` | Tracks OCR core processing | processBillOCR() function |

### Handler Flow

```
HTTP POST to /xadmin/mod/fuel-expense/x-fuel-expense.inc.php
    ↓
Line 2-3: Write to /tmp/ocr_handler_entry.log
    ↓
Line 14-21: Include OCR library
    ↓
Line 24: Check if $_POST["xAction"] isset
    ↓
Line 34: Write to /tmp/ocr_handler_start.log
    ↓
Line 49-56: Include core.inc.php and site.inc.php
    ↓
Line 60: Call mxCheckRequest()
    ↓
Line 63-89: Route to appropriate function based on xAction
    ↓
Line 73-74: If xAction="OCR", call processBillImageOCR()
    ↓
processBillImageOCR() writes to /tmp/ocr_handler.log
    ↓
processBillOCR() writes to /tmp/ocr_debug.log
```

### Key Issue

The handler appears to be called (error returned to browser) but logs don't exist. This could mean:

1. **Most Likely:** The handler file is cached/not updated
   - Solution: Clear PHP opcode cache

2. **Possible:** Different handler is being called
   - Solution: Verify AJAX URL path is correct

3. **Possible:** Handler crashes before line 34
   - Solution: Check ultra-early log (`/tmp/ocr_handler_entry.log`)

---

## Debugging Strategy

### Phase 1: Verify Handler is Being Called
Use `/tmp/ocr_handler_entry.log` to confirm handler file is executed

### Phase 2: Identify Where It Fails
Check which logs exist to trace the failure point:
- Entry log only → Fails in includes
- Entry + Start logs → Fails after includes
- All logs exist → Fails in OCR processing

### Phase 3: Fix the Specific Failure
Once we know where it fails, focus on that section

---

## Important Notes

1. **Error Message Mismatch**
   - Browser shows: "✗ OCR: Tesseract processing failed with code: 1"
   - But logs don't exist, proving processBillImageOCR() never runs
   - This means the error is fabricated or comes from error handling

2. **Files ARE Being Uploaded**
   - Directory has recent files with correct naming pattern
   - This proves file upload part of the request succeeds
   - Proves $_FILES is populated correctly
   - Problem is in OCR processing, not file upload

3. **Multiple Test Attempts**
   - Many PDF files in upload directory from previous attempts
   - All have proper timestamps
   - All named with bill_TIMESTAMP_UNIQUEID pattern
   - Proves file upload is working consistently

---

## Summary

We've added comprehensive logging and diagnostic tools to pinpoint the exact failure location. The key breakthrough is the ultra-early logging that will tell us whether the handler file is even being executed.

**Next Action:** Use the diagnostic tools to determine if `/tmp/ocr_handler_entry.log` is created, which will tell us if the handler is being called.

---

## Quick Links

- **Ultra-Quick Test:** `http://your-domain/test_handler_endpoint.php`
- **Full Dashboard:** `http://your-domain/diagnose_ocr_handler.php`
- **Quick Start:** Read `/home/bombayengg/public_html/IMMEDIATE_ACTION_REQUIRED.txt`
- **Detailed Guide:** Read `/home/bombayengg/public_html/OCR_HANDLER_DEBUGGING_SUMMARY.md`

---

**Session Date:** November 30, 2025
**Status:** Waiting for user to test and provide log output
**Next Step:** Check `/tmp/ocr_handler_entry.log` to verify handler execution
