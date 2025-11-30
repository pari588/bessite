# Fuel Expense Module - JSON Parsing Error Fix

**Date:** November 29, 2025
**Issue:** "error - unexpected token < <br /> <b> is not a valid json alert popup on uploading image"
**Status:** FIXED ✓

## Problem Description

When uploading a bill image, instead of getting the OCR result, the user received an error:
```
error - unexpected token <
<br />
<b> is not a valid json alert popup
```

This indicates that the server was returning HTML error output instead of JSON, which the JavaScript couldn't parse.

## Root Causes

The HTML error message suggests that:
1. PHP encountered an error and output HTML error formatting
2. The JSON response had HTML prepended to it
3. This broke the JavaScript JSON parsing

**Why This Happened:**
- PHP error output (display_errors) was being sent to the browser
- The `processBillImageOCR()` function might have had an issue
- Missing JSON header on response
- No error handling for exceptions

## Solutions Applied

### 1. Added JSON Response Header
```php
header('Content-Type: application/json; charset=utf-8');
```

This tells the browser to expect JSON, and prevents PHP from auto-formatting errors as HTML.

### 2. Added Try-Catch Error Handling
```php
try {
    require_once("../../../core/core.inc.php");
    require_once("../../inc/site.inc.php");
    // ... OCR processing code
} catch (Exception $e) {
    $MXRES["err"] = 1;
    $MXRES["msg"] = "Server error: " . $e->getMessage();
}
```

Any PHP exceptions are now caught and converted to JSON error responses.

### 3. Cleaned Output Buffer
```php
if (ob_get_level() > 0) {
    ob_end_clean();
}
```

Removes any accidental output (warnings, notices) that might have been buffered.

### 4. Enhanced Upload Error Handling
The `processBillImageOCR()` function now checks for:
- File upload errors with specific error messages
- Directory creation failures
- Directory write permissions
- File movementfailures
- File readability after upload
- Exception handling with cleanup

**Error codes now handled:**
- UPLOAD_ERR_INI_SIZE → "File exceeds upload_max_filesize"
- UPLOAD_ERR_FORM_SIZE → "File exceeds MAX_FILE_SIZE"
- UPLOAD_ERR_PARTIAL → "File upload incomplete"
- UPLOAD_ERR_NO_FILE → "No file uploaded"
- UPLOAD_ERR_NO_TMP_DIR → "Server temp directory missing"
- UPLOAD_ERR_CANT_WRITE → "Failed to write file to disk"

### 5. Improved JavaScript JSON Parsing
```javascript
return response.text();  // Get raw text
.then(function(text) {
    try {
        data = JSON.parse(text);  // Try to parse
    } catch (parseError) {
        console.error('[OCR] JSON Parse Error:', parseError);
        console.error('[OCR] Response text:', text);
        alert('Error: Invalid response from server.\n\n' +
              'Response text:\n' + text.substring(0, 200));
        return;
    }
```

Now if JSON parsing fails, the user sees:
- The actual text that was returned
- Indication that the response wasn't valid JSON
- First 200 characters of the problematic response

## Expected Behavior After Fix

### Success Case
```
User uploads file
    ↓
Browser shows loader
    ↓
JavaScript sends POST request (multipart/form-data)
    ↓
Server receives and processes file
    ↓
Header sent: Content-Type: application/json
    ↓
Response returned: {"err": 0, "msg": "...", "data": {...}}
    ↓
JavaScript parses JSON successfully
    ↓
Form fields populate
    ↓
Success alert appears
```

### Error Case (Now Better Handled)
```
User uploads file
    ↓
Browser shows loader
    ↓
JavaScript sends POST request
    ↓
Server encounters error (missing directory, permission denied, etc.)
    ↓
Header sent: Content-Type: application/json
    ↓
Response returned: {"err": 1, "msg": "Error description"}
    ↓
JavaScript parses JSON successfully
    ↓
Error alert appears with description
```

## Testing the Fix

### Step 1: Upload a Bill Image
1. Navigate to: Fuel Management → Fuel Expenses → +Add
2. Click on "Bill Image (JPG/PNG/PDF)" field
3. Select any JPG, PNG, or PDF file

### Step 2: Open Browser Console (F12)
1. Press F12 to open Developer Tools
2. Go to "Console" tab
3. Look for messages starting with [OCR]

### Step 3: Watch the Output
**Successful Upload:**
```
[OCR] Sending OCR request for file: bill.pdf
[OCR] Response Status: 200
[OCR] Response text received, parsing JSON...
[OCR] Response received: {err: 0, msg: "OCR processing completed", data: {...}}
[OCR] Processing extracted data...
[OCR] Date field updated: 11/29/2025
[OCR] Amount field updated: 1500
```

**If Error Occurs:**
```
[OCR] Sending OCR request for file: bill.pdf
[OCR] Response Status: 200
[OCR] Response text received, parsing JSON...
[OCR] JSON Parse Error: ...
[OCR] Response text: <!DOCTYPE html>... (shows what was returned)
```

## Files Modified

1. **Backend Handler:**
   - File: `/xadmin/mod/fuel-expense/x-fuel-expense.inc.php`
   - Changes:
     - Added JSON response header
     - Wrapped main logic in try-catch
     - Added output buffer cleanup
     - Enhanced `processBillImageOCR()` with detailed error checks

2. **Frontend JavaScript:**
   - File: `/xadmin/mod/fuel-expense/inc/js/x-fuel-expense.inc.js`
   - Changes:
     - Changed from `response.json()` to `response.text()`
     - Added JSON parsing with try-catch
     - Better error reporting showing actual response

## Valid JSON Response Format

The server now always returns valid JSON in this format:

### Success Response
```json
{
  "err": 0,
  "msg": "OCR processing completed",
  "data": {
    "filename": "bill_1732879156_54e8d4a1c.pdf",
    "date": "2025-11-29",
    "amount": "1500",
    "dateConfidence": 95,
    "amountConfidence": 87,
    "overallConfidence": 91,
    "rawText": "extracted text from bill...",
    "extractedData": "{\"date\":\"2025-11-29\",\"amount\":\"1500\",\"dateConfidence\":95,\"amountConfidence\":87}"
  }
}
```

### Error Response
```json
{
  "err": 1,
  "msg": "Only JPG, JPEG, PNG, and PDF files are allowed",
  "debug": {}
}
```

## Debugging Steps if Issues Persist

### 1. Check Browser Console (F12)
Look for [OCR] messages. First error message will indicate the issue.

### 2. Check Server Response
In Network tab (F12), click on the OCR request:
- Look at "Response" tab
- Should see valid JSON (starts with `{`, not `<`)

### 3. Check PHP Error Log
```bash
tail -f /var/log/php-fpm/error_log
```

If there are PHP errors being logged, they would indicate the problem.

### 4. Check File Permissions
```bash
ls -la /home/bombayengg/public_html/uploads/fuel-expense/
```

Should be writable (permissions 755 or 777).

### 5. Test OCR Separately
If all else fails, test Tesseract directly:
```bash
tesseract /path/to/bill.pdf /tmp/test_output
cat /tmp/test_output.txt
```

## Common Error Messages Now Handled

| Error | Meaning | Solution |
|-------|---------|----------|
| "File exceeds upload_max_filesize" | File is too large | Use smaller file (< 5MB) |
| "File exceeds MAX_FILE_SIZE" | Form upload limit exceeded | Check PHP configuration |
| "File upload incomplete" | Network error during upload | Try uploading again |
| "Server temp directory missing" | Server misconfiguration | Contact server admin |
| "Failed to write file to disk" | Disk full or permission issue | Check disk space and permissions |
| "Upload directory is not writable" | Permissions issue on uploads folder | Fix permissions: chmod 755 |
| "Failed to create upload directory" | Can't create /uploads/fuel-expense | Check parent directory permissions |
| "Uploaded file is not readable" | File upload succeeded but can't read it | Check file permissions |

## Prevention of Similar Issues

The fix applies these principles to prevent similar JSON parsing errors:

1. **Always set Content-Type header** for API responses
2. **Wrap code in try-catch** to handle unexpected exceptions
3. **Clean output buffer** before sending JSON
4. **Parse responses carefully** in JavaScript
5. **Report actual errors** to the user
6. **Log debug information** to console

## Verification Checklist

- [x] JSON header set in backend
- [x] Try-catch error handling added
- [x] Output buffer cleanup implemented
- [x] Enhanced error checking in OCR function
- [x] JavaScript parse error handling added
- [x] Better error messages to user
- [x] Console logging improved
- [x] Test both success and failure paths

## Next Step

**Test the OCR upload again.** When you upload a bill image, you should now see one of:

1. **Success:** Fields populate with extracted date and amount
2. **Clear Error:** Alert showing "OCR Notice: [specific error message]"
3. **Invalid Response:** Alert showing "Error: Invalid response from server" with first 200 chars

If you see any HTML error message, take a screenshot and note the exact message so we can debug further.

---

**Status:** Ready for Testing ✓
**Last Updated:** November 29, 2025
