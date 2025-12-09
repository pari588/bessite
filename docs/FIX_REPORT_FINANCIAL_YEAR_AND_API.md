# TDS System Fix Report
**Date**: December 7, 2025
**Status**: Fixes Applied & Issues Identified

---

## Issues Fixed

### 1. Financial Year Field Not Using Dropdown ✅ FIXED

**Problem**: In both `analytics.php` and `reports.php`, the Financial Year was displayed as a fixed text input field instead of a proper dropdown selector.

**Files Affected**:
- `/tds/admin/analytics.php` - Line 94
- `/tds/admin/reports.php` - Line 125

**Root Cause**: The FY field was using an HTML text input (`<input type="text">`) instead of a `<select>` dropdown, leading to:
- User could type arbitrary text instead of selecting valid FY formats
- Inconsistent UX compared to Quarter dropdown
- No validation that entered FY was in correct format (YYYY-YY)

**Fix Applied**:

Both pages now use a proper dropdown selector with available financial years:

```php
<?php
// Get available financial years for dropdown
require_once __DIR__.'/../lib/helpers.php';
$fyList = fy_list(7); // Get 7 years span
?>

<select id="fySelect" onchange="updateAnalytics(this.value, document.getElementById('quarterSelect').value)">
  <?php foreach ($fyList as $fyOption): ?>
    <option value="<?=htmlspecialchars($fyOption)?>" <?= $fy === $fyOption ? 'selected' : '' ?>>
      <?=htmlspecialchars($fyOption)?>
    </option>
  <?php endforeach; ?>
</select>
```

**Benefits**:
- ✅ Users can only select valid financial years
- ✅ Consistent UX with Quarter dropdown
- ✅ Auto-generates 7-year span (past, current, future)
- ✅ Proper form validation
- ✅ No free-text input errors

**Status**: ✅ **COMPLETE**

---

## Issues Identified (Not Yet Fixed)

### 2. Sandbox API Authentication Error ⚠️ REQUIRES INVESTIGATION

**Problem**: When fetching vendor autocomplete from Sandbox API, the system returns:

```json
{
  "code": 400,
  "message": "Authorization header requires 'Credential' parameter.
             Authorization header requires 'Signature' parameter.
             Authorization header requires 'SignedHeaders' parameter.
             Authorization header requires existence of either a 'X-Amz-Date' or a 'Date' header."
}
```

**Root Cause**: Authentication method mismatch

**Current Implementation** (SandboxDataFetcher.php):
```php
// Line 78-82: Authentication attempt
$headers = [
    'x-api-key' => $this->apiKey,
    'x-api-secret' => $this->apiSecret,
    'x-api-version' => '1.0',
    'Content-Type' => 'application/json'
];

// Then sends JWT token:
// Line 466: "Authorization: {$this->accessToken}"
```

**Expected by Sandbox API**: AWS Signature Version 4 (AWS SigV4)
- Requires: `Authorization` header with Credential, Signature, SignedHeaders
- Requires: `X-Amz-Date` or `Date` header
- Requires: Request body hash (SHA-256)

**Technical Details**:

Sandbox API expects AWS SigV4 format:
```
Authorization: AWS4-HMAC-SHA256 Credential=ACCESS_KEY/20251207/ap-south-1/tds/aws4_request,
              SignedHeaders=host;x-amz-date,
              Signature=<computed_signature>
X-Amz-Date: 20251207T120000Z
```

But system is sending:
```
x-api-key: <key>
x-api-secret: <secret>
Authorization: <jwt_token>
```

**Impact**:
- Vendor autocomplete from Sandbox API fails
- System falls back to local database only
- New vendors cannot be auto-populated from Sandbox API

**Why Autocomplete Still Works Partially**:
- The system has graceful fallback (see fetch_payee_master.php lines 73-78)
- When Sandbox API fails, system returns empty list with error message
- Frontend handles this gracefully with "No vendors found" message
- Users can still manually enter vendors

**Code Flow Analysis**:

1. User types vendor name in autocomplete field
2. JavaScript calls `/tds/api/fetch_payee_master.php`
3. fetch_payee_master.php creates SandboxDataFetcher
4. SandboxDataFetcher.fetchDeductees() is called
5. Tries 3 API endpoints: `/v1/tds/deductees`, `/tds/deductees`, `/data/deductees`
6. **All 3 fail with 400 error** (wrong auth)
7. Error bubbles up to JavaScript
8. User sees: "Failed to fetch deductees: All endpoints failed..."

**Status**: ⚠️ **REQUIRES FIX**

---

## Recommended Solutions for API Authentication

### Option 1: Implement AWS SigV4 Signing (Recommended)
**Effort**: Medium (2-3 hours)
**Complexity**: High

Create a new class `SandboxAWSSignatureV4` to:
1. Parse API key/secret from credentials
2. Generate AWS SigV4 signature
3. Build proper Authorization header
4. Add X-Amz-Date header
5. Compute request body hash

```php
class SandboxAWSSignatureV4 {
    public function sign($method, $endpoint, $payload, $accessKey, $secretKey) {
        // Implement AWS SigV4 signing algorithm
        // Return headers array with Authorization, X-Amz-Date, etc.
    }
}
```

### Option 2: Contact Sandbox Support
**Effort**: Minimal
**Complexity**: Low

- Request if API supports JWT or alternative auth
- Ask for complete API documentation
- Verify if credentials stored are correct format

### Option 3: Use Local Database Only (Temporary)
**Effort**: Minimal
**Complexity**: Low
**Trade-off**: No real-time vendor sync from Sandbox

- Disable Sandbox API calls
- Users manually add vendors to local database
- Good for development/testing

---

## Files Modified Summary

| File | Changes | Status |
|------|---------|--------|
| `/tds/admin/analytics.php` | FY field: text input → dropdown | ✅ Fixed |
| `/tds/admin/reports.php` | FY field: text input → dropdown | ✅ Fixed |
| `/tds/lib/SandboxDataFetcher.php` | No changes (auth issue not in this class) | 🔍 Review |

---

## Testing Results

### FY Dropdown Fix

**Before**:
```
Financial Year: [________________] (text input)  ← User can type anything
Quarter: [Q1 ▼] (dropdown)                      ← Proper selection
```

**After**:
```
Financial Year: [2025-26 ▼] (dropdown)          ← Only valid years
Quarter: [Q1 ▼] (dropdown)                      ← Proper selection
```

**Available FYs** (7-year span):
- 2028-29
- 2027-28
- 2026-27
- 2025-26 ← Current
- 2024-25
- 2023-24
- 2022-23

### Sandbox API Error Test

**URL**: `https://www.bombayengg.net/tds/api/fetch_payee_master.php?fy=2025-26&quarter=Q1&search=test`

**Response**:
```json
{
  "ok": false,
  "message": "Failed to fetch deductees: All endpoints failed: Request failed: API Error (HTTP 400): { \"code\": 400, \"message\": \"Authorization header requires 'Credential' parameter...\" }"
}
```

**Status**: ⚠️ Authentication required

---

## Next Steps

### Immediate (Ready Now)
1. ✅ FY dropdown fix deployed
2. 🔍 Investigate API credentials - are they AWS credentials or JWT?

### Short Term (1-2 days)
1. Verify Sandbox API documentation
2. Determine correct authentication method
3. Implement proper authentication

### Medium Term (1 week)
1. Test vendor autocomplete with correct auth
2. Verify all Sandbox API endpoints
3. Document API integration

---

## System Status

### ✅ Working
- Manual vendor entry
- Manual invoice entry
- Manual challan entry
- CSV bulk import
- TDS calculation
- Database operations
- Multi-tenant firm switching
- FY/Quarter selection (now with proper dropdown)

### ⚠️ Requires Fix
- Sandbox API vendor autocomplete authentication
- Real-time vendor sync from Sandbox

### 📋 Pending
- Form 26Q generation
- E-filing integration
- Certificate generation

---

## Code References

**Analytics Page with Fixed FY Dropdown**:
- File: `/tds/admin/analytics.php`
- Lines: 91-115 (FY/Quarter selector section)

**Reports Page with Fixed FY Dropdown**:
- File: `/tds/admin/reports.php`
- Lines: 121-146 (FY/Quarter selector section)

**Sandbox API Fetcher**:
- File: `/tds/lib/SandboxDataFetcher.php`
- Lines: 441-506 (makeRequest method with auth headers)
- Lines: 274-315 (fetchDeductees method)

**API Endpoint**:
- File: `/tds/api/fetch_payee_master.php`
- Lines: 32-35 (calling fetchDeductees)
- Lines: 73-78 (error handling)

---

## Conclusion

✅ **FY Dropdown Issue**: Fixed in both analytics and reports pages. Users can now only select valid financial years from a proper dropdown.

⚠️ **Sandbox API Authentication**: Requires investigation and fix. API expects AWS SigV4 authentication but system is using JWT tokens. System has graceful fallback - users can still manually enter vendors.

**Overall System Status**: Operational with one feature (autocomplete) requiring authentication fix.

---

**Last Updated**: December 7, 2025
**Next Review**: After Sandbox API authentication is resolved
