# Payee Master Data Sync - Testing & Usage Guide

## Overview

The TDS system now integrates with Sandbox API to automatically fetch payee/deductee master data. This feature provides real-time vendor autocomplete when adding invoices, reducing manual data entry and improving data quality.

---

## Architecture

### Components

1. **Backend API Endpoint**: `/tds/api/fetch_payee_master.php`
   - Fetches deductees from Sandbox API for the selected FY/Quarter
   - Filters by search term if provided
   - Checks which vendors already exist in local database
   - Returns JSON with vendor list

2. **SandboxDataFetcher Enhancement**: `lib/SandboxDataFetcher.php`
   - `fetchDeductees($fy, $quarter)` method
   - Attempts multiple API endpoints
   - Transforms Sandbox format to local format
   - Handles API errors gracefully

3. **Frontend Autocomplete**: `admin/invoices.php`
   - Custom VendorAutocomplete class
   - Real-time search with debounce
   - Dropdown display with PAN matching
   - Automatic form field population on selection
   - Loading, empty, and error states

---

## How It Works

### User Flow

```
1. User navigates to Invoices page
2. User starts typing in "Vendor Name" field
3. After 300ms of typing, system fetches matching payees from Sandbox API
4. Dropdown shows:
   - Vendor name
   - PAN number
   - "In System" badge if already in local database
5. User clicks on vendor to select
6. Form automatically populates:
   - Vendor Name field
   - Vendor PAN field (auto-filled)
7. User continues with Invoice No, Amount, Section, etc.
```

### Data Flow

```
Frontend (invoices.php)
    ↓
fetch(/tds/api/fetch_payee_master.php?fy=2025-26&quarter=Q1&search=xyz)
    ↓
API Endpoint (fetch_payee_master.php)
    ├─ Get firm ID from session
    ├─ Initialize SandboxDataFetcher
    ├─ Call fetchDeductees($fy, $quarter)
    ├─ Filter results by search term
    ├─ Check local database for existing vendors
    ├─ Return JSON response
    ↓
Frontend
    ├─ Receive JSON
    ├─ Display dropdown
    ├─ Handle user selection
    ├─ Populate form fields
```

---

## API Response Format

### Request

```bash
GET /tds/api/fetch_payee_master.php?fy=2025-26&quarter=Q1&search=ABC
```

### Response (Success)

```json
{
  "ok": true,
  "count": 3,
  "deductees": [
    {
      "name": "ABC Corp",
      "pan": "ABCDE1234F",
      "type": "individual",
      "exists": false
    },
    {
      "name": "ABC Traders",
      "pan": "ABCDE5678G",
      "type": "individual",
      "exists": true
    }
  ]
}
```

### Response (Error)

```json
{
  "ok": false,
  "message": "Selected firm does not exist"
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| ok | boolean | Request success status |
| count | integer | Number of deductees returned |
| deductees | array | List of matching payees |
| deductees[].name | string | Payee/Deductee name |
| deductees[].pan | string | PAN number |
| deductees[].type | string | Entity type (individual/company) |
| deductees[].exists | boolean | Already in local vendors table |

---

## Testing Guide

### Test Case 1: Vendor Autocomplete Search

**Steps:**
1. Navigate to Invoices page
2. Start typing "ABC" in Vendor Name field
3. Wait for dropdown to appear

**Expected Results:**
- Dropdown shows within 300ms
- Results filtered by search term
- PAN numbers displayed
- "In System" badge for existing vendors

**Debug:**
If dropdown doesn't appear:
```bash
# Check API response
curl "http://localhost/tds/api/fetch_payee_master.php?fy=2025-26&quarter=Q1&search=ABC"

# Check browser console for JavaScript errors
# Verify firm is selected in session
```

### Test Case 2: Vendor Selection

**Steps:**
1. Search for vendor "ABC Corp"
2. Click on dropdown item
3. Check form fields

**Expected Results:**
- Vendor Name field populated with "ABC Corp"
- Vendor PAN field auto-filled with correct PAN
- Dropdown closes
- Focus remains in form

### Test Case 3: Sandbox API Fallback

**Steps:**
1. Disconnect from Sandbox API (simulate in code)
2. Try autocomplete search
3. Check error handling

**Expected Results:**
- Error message displays in dropdown
- User can still manually type vendor name
- System doesn't crash

### Test Case 4: No API Credentials

**Steps:**
1. Clear API credentials for firm
2. Try autocomplete search
3. Check error message

**Expected Results:**
- Shows helpful error: "No API credentials found for firm"
- User can still manually enter vendor data

### Test Case 5: Empty Results

**Steps:**
1. Search for vendor that doesn't exist: "ZZZZZZZZZZZZ"
2. Wait for API response

**Expected Results:**
- Shows "No vendors found" message
- User can still manually type vendor name

---

## Configuration

### Database Requirements

No new tables needed. System uses existing:
- `vendors` table (local vendor records)
- `api_credentials` table (Sandbox API keys)
- `firms` table (TAN and firm info)

### Session Requirements

Must have:
```php
$_SESSION['active_firm_id'] = <firm_id>
```

Automatically set when:
- User selects firm from dropdown in header
- User logs in (defaults to first firm)

---

## Performance Considerations

### Optimization Techniques

1. **Debounce**: 300ms delay before API call
   - Reduces API calls as user types
   - Better UX while typing

2. **Search Filtering**: Server-side filtering
   - Only returns matching vendors
   - Limits results to 20 items

3. **Local Database Cache**: "Exists" check
   - Helps identify duplicate entries
   - Shows badge to user

4. **Dropdown Scrolling**: Max height 300px
   - Handles large result sets
   - Doesn't expand page

### Typical Response Times

| Condition | Time |
|-----------|------|
| API request | 500-2000ms |
| Client-side rendering | 50-100ms |
| Total felt latency | 500-2100ms |

---

## Troubleshooting

### Issue: Dropdown shows "Loading vendors..." but never completes

**Possible Causes:**
1. Sandbox API credentials invalid
2. API endpoint doesn't exist
3. Network timeout

**Solution:**
```bash
# Test API endpoint directly
curl -X GET "http://localhost/tds/api/fetch_payee_master.php?fy=2025-26&quarter=Q1&search=ABC" \
  -H "Cookie: PHPSESSID=<your_session_id>"

# Check PHP error log
tail -f /var/log/php-fpm/error.log

# Verify credentials in database
mysql> SELECT * FROM api_credentials WHERE firm_id=1 AND is_active=1;
```

### Issue: Dropdown shows error "Failed to fetch vendors"

**Possible Causes:**
1. No firm selected (empty `$_SESSION['active_firm_id']`)
2. Firm ID invalid
3. Firm has no TAN configured

**Solution:**
1. Verify firm selection in header dropdown
2. Check firm configuration: Admin > Settings > Firms
3. Ensure TAN is set for the firm

### Issue: PAN field not auto-filling

**Possible Causes:**
1. Browser caching issue
2. JavaScript error in console
3. Material Design component not initialized

**Solution:**
1. Hard refresh page (Ctrl+Shift+R)
2. Check browser console for errors
3. Verify Material Design 3 CSS/JS loaded

### Issue: "In System" badge appears for all vendors

**Possible Causes:**
1. Database connection issue
2. All vendors already imported
3. Query returning false positives

**Solution:**
```bash
# Check vendors table
mysql> SELECT COUNT(*) FROM vendors WHERE firm_id=1;

# Check for duplicate PANs
mysql> SELECT pan, COUNT(*) FROM vendors WHERE firm_id=1 GROUP BY pan HAVING COUNT(*) > 1;
```

---

## API Limitations

### Known Limitations

1. **Data Endpoints Not Available**: Sandbox API doesn't expose dedicated data fetch endpoints
   - System attempts multiple endpoints: `/v1/tds/deductees`, `/tds/deductees`, `/data/deductees`
   - If none work, returns empty list (graceful fallback)
   - Users can still manually enter data or use CSV import

2. **Real-time Sync Not Possible**: Can't automatically sync Sandbox portal changes
   - User must manually refresh or search again
   - No background sync job implemented

3. **Limited to Current FY/Quarter**: Only fetches data for selected period
   - Cross-period queries not supported by API
   - User must manually enter historical vendor data

### Fallback Options

If Sandbox API doesn't have vendor data:
1. **Manual Entry**: Users type vendor name and PAN directly
2. **CSV Import**: Use bulk import with CSV file
3. **Existing Vendors**: System suggests already-imported vendors
4. **Direct Database**: Admins can manually add to vendors table

---

## Future Enhancements

### Potential Improvements

1. **Bidirectional Sync**: Push local changes back to Sandbox
2. **Periodic Sync**: Background job to sync payee master daily
3. **PAN Validation**: Validate PAN format during autocomplete
4. **Deduplication**: Automatically merge duplicate vendors
5. **Vendor Categories**: Classify vendors by TDS section
6. **Import History**: Track which vendors came from Sandbox API

---

## Code Reference

### Files Modified/Created

1. **Created**: `/tds/api/fetch_payee_master.php`
   - New API endpoint for payee master data
   - Fetches from Sandbox, filters, checks local DB

2. **Modified**: `/tds/lib/SandboxDataFetcher.php`
   - Enhanced `fetchDeductees()` method
   - Improved error handling with endpoint fallback
   - Better response format handling

3. **Modified**: `/tds/admin/invoices.php`
   - Added autocomplete HTML structure
   - Added autocomplete CSS styling
   - Added VendorAutocomplete JavaScript class
   - Changed vendor_name from md-outlined-text-field to custom input

### Key JavaScript Class: VendorAutocomplete

```javascript
class VendorAutocomplete {
  constructor(inputId, dropdownId, hiddenInputId, panInputId)
  handleInput(e)              // Called on user typing
  handleFocus(e)              // Called when field focused
  handleClickOutside(e)       // Called on document click
  async fetchVendors(search)  // Calls API endpoint
  showLoading()               // Display loading state
  showEmpty()                 // Display empty state
  showError(message)          // Display error state
  showDropdown(vendors)       // Display vendor list
  selectVendor(name, pan)     // Handle vendor selection
}
```

---

## Support & Documentation

### Related Pages
- Invoices: `/tds/admin/invoices.php`
- Challans: `/tds/admin/challans.php`
- Sandbox Setup: `/tds/SANDBOX_API_SETUP_GUIDE.md`

### API Documentation
- Sandbox TDS API: https://developer.sandbox.co.in/docs/tds

### Contact
For issues or suggestions, contact the development team.
