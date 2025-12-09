# Sandbox Analytics API Integration Status

## Executive Summary

**Question:** Is the Sandbox Analytics `poll_job` endpoint integrated?

**Answer:** **NO** - The Sandbox Analytics API (`/tds/analytics/potential-notices` endpoint) is **NOT currently integrated** into the application.

---

## Current Integration Status

### ✅ What IS Integrated

The application currently uses **local compliance checking** via `AnalyticsAPI.php`:

#### 1. **Local Compliance Checks** (`/tds/lib/AnalyticsAPI.php`)
   - `performTDSComplianceCheck()` - Validates 8 local compliance checks:
     - Invoice existence
     - TDS calculations
     - Challan matching
     - Deductee PAN validation
     - Amount validation
     - Duplicate invoice detection
     - Invoice date validation
     - Invoice allocation status
   - `assessFilingRisk()` - Calculates risk score based on local checks
   - `analyzeDeducteeTDS()` - Analyzes deductee-wise TDS distribution
   - `reconcileTDSCredits()` - Reconciles TDS credits

#### 2. **Compliance Polling** (FVU & E-Filing)
   - `SandboxTDSAPI::pollFVUJobStatus($job_id)` - Polls FVU generation status
     - Endpoint: `GET /tds/compliance/e-file/poll`
     - Returns: `{status, fvu_url, form27a_url, error}`
   - `SandboxTDSAPI::pollEFilingStatus($job_id)` - Polls e-filing submission status
     - Endpoint: `GET /tds/compliance/e-file/poll`
     - Returns: `{status, ack_no, error}`

---

### ❌ What IS NOT Integrated

The **Sandbox Analytics API** endpoint is NOT integrated:

#### Sandbox Analytics `poll_job` Endpoint
- **Path:** `GET /tds/analytics/potential-notices`
- **Purpose:** Check results of "Potential Notice" analysis job
- **Parameters:**
  - `job_id` (UUID) - Job identifier from potential-notices-analysis job
  - Headers: `x-api-key`, `Authorization` (JWT)
- **Response:** Job status with `potential_notice_report_url`
- **Current Status:** NO implementation found in codebase

#### What This Endpoint Does
The Analytics API performs **Potential Notice Analysis** to identify:
- Tax compliance risks
- Potential notice issues from Tax Authority
- Filing vulnerability assessment
- Form validation against TRACES requirements

---

## Current Polling Architecture

### FVU & E-Filing Polling (INTEGRATED ✅)
```
1. User initiates FVU generation
   ↓
2. SandboxTDSAPI::submitFVUGenerationJob()
   - Sends TXT + CSI to /tds/compliance/generate-fvu
   - Returns: {job_id, status}
   ↓
3. User polls: SandboxTDSAPI::pollFVUJobStatus(job_id)
   - Calls: /tds/compliance/e-file/poll
   - Returns: {status, fvu_url, form27a_url}
   ↓
4. When complete, download FVU files
   ↓
5. Submit for e-filing: SandboxTDSAPI::submitEFilingJob()
   ↓
6. Poll e-filing: SandboxTDSAPI::pollEFilingStatus(job_id)
```

### Analytics Polling (NOT INTEGRATED ❌)
```
1. [NOT IMPLEMENTED] Initiate Potential Notice Analysis job
   ↓
2. [NOT IMPLEMENTED] Receive job_id
   ↓
3. [MISSING] Call: GET /tds/analytics/potential-notices?job_id=XXX
   ↓
4. [NOT IMPLEMENTED] Handle response with potential_notice_report_url
```

---

## Code References

### Files with Current Implementation

1. **`/tds/lib/AnalyticsAPI.php`** (85-603 lines)
   - LOCAL compliance checking only
   - Does NOT call Sandbox Analytics API
   - Direct database queries for compliance validation

2. **`/tds/lib/SandboxTDSAPI.php`** (lines 177-200, 291-315)
   - `pollFVUJobStatus()` - Polls `/tds/compliance/e-file/poll` for FVU
   - `pollEFilingStatus()` - Polls `/tds/compliance/e-file/poll` for e-filing
   - NO method for Analytics API polling

3. **`/tds/api/fetch_from_sandbox.php`**
   - Fetches invoices, challans, deductees from Sandbox
   - Uses `SandboxDataFetcher` class
   - Does NOT integrate Analytics API

4. **`/tds/admin/compliance.php`**
   - Displays 7-step e-filing workflow
   - Uses local `AnalyticsAPI::performTDSComplianceCheck()`
   - NO Analytics API integration

---

## Impact Assessment

### What Works
- Local compliance validation ✅
- FVU generation polling ✅
- E-filing submission polling ✅
- TDS calculation verification ✅
- Risk assessment (local) ✅

### What's Missing
- Potential Notice analysis from Tax Authority perspective ❌
- Tax compliance risk scores from Sandbox ❌
- TRACES form validation results ❌
- Official potential notice reports ❌
- Advanced analytics and insights ❌

---

## Recommendations

### Option 1: Minimal Integration (Recommended)
Add method to `SandboxTDSAPI` to poll analytics:

```php
/**
 * Poll Potential Notice Analysis job results
 *
 * @param string $job_id Job ID from analytics-analysis
 * @return array Job status with report URL
 */
public function pollAnalyticsJob($job_id) {
    $this->ensureValidToken();

    $response = $this->makeAuthenticatedRequest(
        'GET',
        '/tds/analytics/potential-notices',
        ['job_id' => $job_id]
    );

    return [
        'status' => $response['data']['status'],
        'form' => $response['data']['form'],
        'quarter' => $response['data']['quarter'],
        'financial_year' => $response['data']['financial_year'],
        'report_url' => $response['data']['potential_notice_report_url'],
        'error' => $response['data']['error'] ?? null
    ];
}
```

### Option 2: Full Analytics Integration
- Add endpoint to initiate Potential Notice analysis
- Store job IDs in database with status tracking
- Create polling mechanism in compliance page
- Display analytics insights alongside local checks
- Download and store Potential Notice reports

### Option 3: Disable/Document as Future
- Document that Analytics API is not currently used
- Note this as a future enhancement opportunity
- Continue with local compliance validation

---

## Questions for User

1. **Do you need Potential Notice analysis?** (Tax Authority risk detection)
2. **Should this be integrated immediately or deferred?**
3. **Do you want to display analytics insights on the compliance page?**
4. **Should Potential Notice reports be stored/downloadable?**

---

## Conclusion

The Sandbox Analytics API `poll_job` endpoint is **not integrated**. The application uses a local compliance checking system instead. This covers basic TDS validation but doesn't include advanced analytics like Potential Notice analysis that the Sandbox Analytics API would provide.

Integration can be added when needed, as outlined in the recommendations above.
