# TDS Payee Master Integration - Architecture Diagram

## System Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                        BROWSER (User Interface)                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ Invoices Page (/tds/admin/invoices.php)                       │  │
│  ├───────────────────────────────────────────────────────────────┤  │
│  │                                                               │  │
│  │  Vendor Name Input        VendorAutocomplete Class          │  │
│  │  ┌──────────────────┐     ┌──────────────────────────────┐  │  │
│  │  │ [ABC_________]   │────→│ Real-time Search            │  │  │
│  │  └──────────────────┘     │ Debounce 300ms              │  │  │
│  │         ↓                  │ Fetch from API              │  │  │
│  │   [Dropdown List]          │ Display Results             │  │  │
│  │   • ABC Corp       ──────→ │ Handle Selection            │  │  │
│  │   • ABC Traders           │                             │  │  │
│  │   • ABC Industries         └──────────────────────────────┘  │  │
│  │         ↓                                                     │  │
│  │   [Auto-fill PAN]                                           │  │
│  │                                                               │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                       │
│              JavaScript Event Listeners                              │
│              • input → handleInput()                                 │
│              • focus → handleFocus()                                 │
│              • click → handleClickOutside()                          │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
                               ↕ (AJAX)
                        fetch() API Call
                               ↓
┌─────────────────────────────────────────────────────────────────────┐
│                        WEB SERVER (PHP)                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ /tds/api/fetch_payee_master.php                              │  │
│  ├───────────────────────────────────────────────────────────────┤  │
│  │                                                               │  │
│  │  1. Validate Request                                         │  │
│  │     • Get FY, Quarter, Search term from URL                  │  │
│  │     • Get firm ID from session                               │  │
│  │     • Verify firm exists in database                         │  │
│  │                                                               │  │
│  │  2. Initialize Sandbox Integration                           │  │
│  │     • Create SandboxDataFetcher instance                     │  │
│  │     • Load API credentials from database                     │  │
│  │     • Check token validity                                   │  │
│  │                                                               │  │
│  │  3. Fetch Data from Sandbox API                              │  │
│  │     • Call fetchDeductees($fy, $quarter)                     │  │
│  │     • Try multiple endpoints (fallback logic)                │  │
│  │     • Transform response to local format                     │  │
│  │                                                               │  │
│  │  4. Filter Results                                           │  │
│  │     • Apply search filter (name & PAN)                       │  │
│  │     • Limit to 20 results                                    │  │
│  │                                                               │  │
│  │  5. Check Local Database                                     │  │
│  │     • Query vendors table                                    │  │
│  │     • Mark existing vendors with "exists" flag               │  │
│  │                                                               │  │
│  │  6. Return JSON Response                                     │  │
│  │     • {ok: true, deductees: [...]}                           │  │
│  │     • or {ok: false, message: "error"}                       │  │
│  │                                                               │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                       │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ /tds/lib/SandboxDataFetcher.php                              │  │
│  ├───────────────────────────────────────────────────────────────┤  │
│  │                                                               │  │
│  │  fetchDeductees($fy, $quarter)                               │  │
│  │  ├─ Get firm TAN from database                               │  │
│  │  ├─ Calculate date range from FY/Quarter                     │  │
│  │  ├─ Try multiple API endpoints:                              │  │
│  │  │  • /v1/tds/deductees                                      │  │
│  │  │  • /tds/deductees                                         │  │
│  │  │  • /data/deductees                                        │  │
│  │  ├─ Handle response (deductees / data / empty)               │  │
│  │  └─ Transform to local format                                │  │
│  │     {name, pan, type, exists}                                │  │
│  │                                                               │  │
│  │  makeRequest($method, $endpoint, $data)                      │  │
│  │  ├─ Build HTTP request                                       │  │
│  │  ├─ Add JWT authorization header                             │  │
│  │  ├─ Execute cURL request                                     │  │
│  │  ├─ Handle HTTP errors                                       │  │
│  │  └─ Parse JSON response                                      │  │
│  │                                                               │  │
│  │  authenticate()                                              │  │
│  │  ├─ Send API key + secret                                    │  │
│  │  ├─ Receive JWT token                                        │  │
│  │  ├─ Store token in database                                  │  │
│  │  └─ Use token for subsequent requests                        │  │
│  │                                                               │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
                               ↕
                        cURL HTTPS
                               ↓
┌─────────────────────────────────────────────────────────────────────┐
│                       SANDBOX API SERVER                             │
│                   (sandbox.co.in / test-api.*)                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  Authentication Endpoint                                             │
│  POST /authenticate                                                  │
│  ├─ Input: API Key + Secret                                         │
│  └─ Output: JWT Token                                               │
│                                                                       │
│  Data Endpoints (Attempted in order)                                │
│  GET /v1/tds/deductees?tan=...&from_date=...&to_date=...           │
│  GET /tds/deductees?tan=...&from_date=...&to_date=...              │
│  GET /data/deductees?tan=...&from_date=...&to_date=...             │
│                                                                       │
│  Response Format                                                     │
│  {                                                                   │
│    "deductees": [                                                    │
│      {                                                               │
│        "pan": "ABCDE1234F",                                          │
│        "name": "ABC Corp",                                           │
│        "type": "individual"                                          │
│      }                                                               │
│    ]                                                                 │
│  }                                                                   │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
                               ↕
                         Database
                               ↓
┌─────────────────────────────────────────────────────────────────────┐
│                    SANDBOX API DATABASE                              │
│           (Contains all TDS-related master data)                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  Payee Master Table                                                  │
│  ├─ pan (primary key)                                               │
│  ├─ name                                                             │
│  ├─ type (individual/company)                                       │
│  ├─ address                                                          │
│  ├─ contact_info                                                     │
│  ├─ tds_applicability_date                                          │
│  └─ last_updated                                                     │
│                                                                       │
│  TDS Statement Data (filtered by FY/Quarter)                        │
│  ├─ deductee_pan                                                     │
│  ├─ deductee_name                                                    │
│  ├─ amount                                                           │
│  ├─ section_code                                                     │
│  ├─ payment_date                                                     │
│  └─ tds_amount                                                       │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
                               ↕
                         MySQL Query
                               ↓
┌─────────────────────────────────────────────────────────────────────┐
│                    LOCAL DATABASE (Our System)                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  firms table                                                         │
│  ├─ id, name, tan, pan, ...                                         │
│  └─ Used to get firm TAN for API call                               │
│                                                                       │
│  vendors table (Local Cache)                                        │
│  ├─ id, firm_id, name, pan, type                                    │
│  └─ Checked to see if vendor already exists                         │
│                                                                       │
│  api_credentials table                                              │
│  ├─ firm_id, api_key, api_secret, access_token                     │
│  └─ Used for authentication with Sandbox API                       │
│                                                                       │
│  invoices table                                                     │
│  ├─ id, firm_id, vendor_id, invoice_no, ...                        │
│  └─ Stores entered invoice data                                     │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Data Flow Sequence

```
User Types "ABC"
    ↓ (input event)
VendorAutocomplete.handleInput()
    ↓
clearTimeout(debounceTimer)
debounceTimer = setTimeout(() => fetchVendors("ABC"), 300)
    ↓ (300ms later)
VendorAutocomplete.fetchVendors("ABC")
    ↓
fetch('/tds/api/fetch_payee_master.php?fy=2025-26&quarter=Q1&search=ABC')
    ↓
[HTTP POST to server]
    ↓
fetch_payee_master.php
    ├─ Get firm_id from session
    ├─ Initialize SandboxDataFetcher
    ├─ Call fetchDeductees('2025-26', 'Q1')
    │   ├─ Get firm TAN
    │   ├─ Calculate date range
    │   ├─ Try /v1/tds/deductees
    │   ├─ Try /tds/deductees
    │   ├─ Try /data/deductees
    │   └─ Return results (or empty)
    ├─ Filter by search ("ABC")
    ├─ Query local vendors table
    ├─ Add "exists" flags
    └─ Return JSON
    ↓
[HTTP Response]
    ↓
Browser receives JSON
    ↓
VendorAutocomplete.showDropdown(vendors)
    ├─ Build HTML
    ├─ Render dropdown
    └─ Show to user
    ↓
User clicks "ABC Corp"
    ↓
VendorAutocomplete.selectVendor('ABC Corp', 'ABCDE1234F')
    ├─ Set vendor_name_create.value = 'ABC Corp'
    ├─ Set vendor_pan_create.value = 'ABCDE1234F'
    └─ Hide dropdown
    ↓
Form is ready for user to continue
```

---

## Error Handling Flow

```
User Types Vendor Name
    ↓
fetch() API request initiated
    ↓
    ├─ Network Error?
    │  └─ showError("Network error occurred")
    │
    ├─ Invalid Firm ID?
    │  └─ showError("No firm selected")
    │
    ├─ Invalid FY/Quarter?
    │  └─ showError("FY and Quarter required")
    │
    ├─ Sandbox API Error?
    │  └─ showError("Failed to fetch vendors")
    │
    ├─ No Matching Vendors?
    │  └─ showEmpty("No vendors found")
    │
    └─ Success - Return Data
       └─ showDropdown(vendors)

↓

User sees friendly error message and can:
• Manually type vendor name
• Manually type vendor PAN
• Use CSV import instead
• Try again later

System continues working - no crashes
```

---

## Component Interaction Diagram

```
                    ┌──────────────────┐
                    │ Invoices Page    │
                    │ (HTML/CSS)       │
                    └────────┬─────────┘
                             │
                    ┌────────▼────────┐
                    │ VendorAutocomp  │
                    │ class           │
                    │ (JavaScript)    │
                    └────────┬────────┘
                             │
            ┌────────────────┼────────────────┐
            │                │                │
       ┌────▼──┐       ┌─────▼─────┐   ┌─────▼──┐
       │Fetch  │       │Database   │   │Event   │
       │API    │       │Queries    │   │Handler │
       │Call   │       │(local)    │   │        │
       └────┬──┘       └─────┬─────┘   └─────┬──┘
            │                │              │
       ┌────▼──────────────────┼──────────────▼──────┐
       │ fetch_payee_master.php                      │
       │ (PHP API Endpoint)                          │
       └────┬──────────────────┬──────────────────────┘
            │                  │
       ┌────▼──┐         ┌─────▼──────┐
       │Sandbox│         │Local MySQL │
       │API    │         │Database    │
       └───────┘         └────────────┘
```

---

## Request/Response Cycle

### Request

```
GET /tds/api/fetch_payee_master.php?fy=2025-26&quarter=Q1&search=ABC

Headers:
- Cookie: PHPSESSID=...
- Accept: application/json

Parameters:
- fy: 2025-26 (Financial Year)
- quarter: Q1 (Quarter)
- search: ABC (Search term)
```

### Processing

```
1. Receive Request
   ├─ Extract parameters
   ├─ Get firm_id from $_SESSION
   └─ Validate all required fields

2. Connect to Databases
   ├─ Connect to local MySQL
   └─ Connect to Sandbox API

3. Fetch Payee Data
   ├─ Get firm TAN
   ├─ Calculate date range
   ├─ Try multiple endpoints
   └─ Collect results

4. Filter Results
   ├─ Apply search filter
   └─ Limit to 20 items

5. Enrich Data
   ├─ Query local vendors table
   ├─ Add "exists" flags
   └─ Format response

6. Send Response
   └─ Return JSON to browser
```

### Response

```
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
    },
    {
      "name": "ABC Industries",
      "pan": "ABCDZ0000K",
      "type": "company",
      "exists": false
    }
  ]
}
```

---

## State Management

### Frontend State

```javascript
VendorAutocomplete instance maintains:
├─ input (DOM element)
├─ dropdown (DOM element)
├─ hiddenInput (for vendor name)
├─ panInput (for PAN)
├─ vendors (current list from API)
└─ debounceTimer (for input debouncing)

Transitions:
├─ User types → fetchVendors()
├─ API responds → showDropdown()
├─ User clicks → selectVendor()
├─ User clicks outside → close dropdown
└─ User clears input → hide dropdown
```

### Backend State

```php
Per Request:
├─ $firmId (from session)
├─ $fy (from URL parameter)
├─ $quarter (from URL parameter)
├─ $search (from URL parameter)
├─ $accessToken (from database)
└─ $vendors (from API call)

No persistent state maintained
(Stateless API design)
```

---

## Security Boundaries

```
┌────────────────────────────────────────────────────┐
│ Browser (Untrusted)                               │
├────────────────────────────────────────────────────┤
│                                                    │
│  User Input → Validate → API Call                │
│                ↓                                   │
│       [CORS Check]                               │
│       [Session Check]                            │
│       [Firm Ownership Check]                     │
│                ↓                                  │
├────────────────────────────────────────────────────┤
│ Server (Trusted)                                  │
├────────────────────────────────────────────────────┤
│                                                    │
│  API Endpoint → Database Query                   │
│       ↓                                           │
│  [Prepared Statements]                           │
│  [Input Validation]                              │
│  [Error Handling]                                │
│       ↓                                           │
│  Sandbox API Call                                │
│       ↓                                           │
│  [JWT Authorization]                            │
│  [SSL/TLS Encryption]                           │
│       ↓                                           │
├────────────────────────────────────────────────────┤
│ External (Untrusted)                              │
└────────────────────────────────────────────────────┘
```

---

## Performance Optimization Points

```
Frontend:
├─ Debounce (300ms) → Reduces API calls
├─ Client-side dropdown → Instant rendering
├─ Lazy loading → Only fetch when needed
└─ 20 result limit → Keeps response small

Backend:
├─ Result limiting (20 items) → Fast DB queries
├─ Search filtering → Reduces data transfer
├─ Token caching → Avoid repeated auth
└─ Database indexing → Fast vendor lookup

Network:
├─ Gzip compression → Smaller responses
├─ Keep-alive → Connection reuse
├─ Async requests → Non-blocking
└─ Error fallback → Graceful degradation
```

---

This architecture provides:
- **Separation of Concerns**: Clean frontend/backend/API boundaries
- **Fault Tolerance**: Graceful error handling at each layer
- **Performance**: Optimized for typical user interactions
- **Security**: Proper validation and authorization checks
- **Scalability**: Stateless design, can handle concurrent requests
