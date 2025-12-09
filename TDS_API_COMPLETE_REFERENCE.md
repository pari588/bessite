# Complete TDS API Reference - All Modules & Endpoints

## Overview

Sandbox Financial Technologies provides a **comprehensive TDS (Tax Deducted at Source) API** with 4 major modules covering the entire TDS lifecycle:

1. **Calculator API** - Tax computation
2. **Compliance API** - E-filing and certificates
3. **Analytics API** - Risk analysis and compliance checks
4. **Reports API** - TDS return generation

---

## Module 1: Calculator API

### Purpose
Calculates TDS (Tax Deducted at Source) and TCS (Tax Collected at Source) amounts based on payment data.

### Endpoints

#### 1.1 Calculate TDS
**Endpoint:** `POST /tds/calculator/calculate`

**Purpose:** Calculate TDS amount for salary and non-salary payments

**Parameters:**
```json
{
  "payment_type": "salary|non-salary|nri",
  "gross_amount": 100000,
  "payment_date": "2024-06-15",
  "section": "194C|194D|194H|194J|194LA",
  "financial_year": "2024-25"
}
```

**Response:**
```json
{
  "tds_amount": 10000,
  "tds_rate": 10,
  "net_amount": 90000,
  "applicability": true
}
```

#### 1.2 Calculate TCS
**Endpoint:** `POST /tds/calculator/calculate-tcs`

**Purpose:** Calculate TCS amount for sales transactions

**Parameters:**
```json
{
  "sale_amount": 500000,
  "transaction_date": "2024-06-15",
  "financial_year": "2024-25"
}
```

**Response:**
```json
{
  "tcs_amount": 10000,
  "tcs_rate": 2,
  "net_amount": 490000
}
```

---

## Module 2: Compliance API

### Purpose
Handles e-filing, certificate downloads, FVU generation, and challan status information.

### Endpoints

#### 2.1 Generate FVU (File Validation Utility)
**Endpoint:** `POST /tds/compliance/generate-fvu`

**Purpose:** Submit TDS form and generate validation file

**Parameters:**
```json
{
  "txt_content": "NS1 format TDS form content",
  "csi_content": "Challan Status Information",
  "form_type": "26Q|27Q|24Q"
}
```

**Response:**
```json
{
  "job_id": "uuid",
  "status": "submitted",
  "estimated_time": "5 minutes"
}
```

#### 2.2 Poll FVU Status
**Endpoint:** `GET /tds/compliance/e-file/poll?job_id={job_id}`

**Purpose:** Check FVU generation progress

**Response:**
```json
{
  "status": "succeeded|processing|failed",
  "fvu_url": "download link",
  "form27a_url": "download link",
  "error": null
}
```

#### 2.3 Submit for E-Filing
**Endpoint:** `POST /tds/compliance/e-file/submit`

**Purpose:** Submit FVU + Form 27A to tax authority

**Parameters:**
```json
{
  "fvu_zip_path": "path/to/fvu.zip",
  "form27a_path": "path/to/form27a",
  "tin_fc_username": "username",
  "tin_fc_password": "password"
}
```

**Response:**
```json
{
  "job_id": "uuid",
  "status": "submitted",
  "expected_ack_time": "24 hours"
}
```

#### 2.4 Poll E-Filing Status
**Endpoint:** `GET /tds/compliance/e-file/poll?job_id={job_id}`

**Purpose:** Check e-filing submission status

**Response:**
```json
{
  "status": "acknowledged|rejected|accepted|processing",
  "ack_no": "ACK0123456789",
  "ack_date": "2024-06-20",
  "error": null
}
```

#### 2.5 Download Form 16/16A
**Endpoint:** `GET /tds/compliance/certificates/download?type=form16&deductee_pan={pan}&fy={fy}`

**Purpose:** Download Form 16 (salary) or Form 16A (non-salary) certificates

**Response:** PDF file download

#### 2.6 Download CSI (Challan Status Information)
**Endpoint:** `POST /tds/compliance/csi/download`

**Purpose:** Download challan status from bank

**Parameters:**
```json
{
  "tan": "AHMA09719B",
  "fy": "2024-25",
  "quarter": "Q1",
  "bank_name": "ICICI|HDFC|SBI",
  "otp": "OTP from bank statement"
}
```

**Response:** CSI file content

---

## Module 3: Analytics API

### Purpose
Analyzes TDS returns for compliance risks, potential notices, and regulatory violations.

### Endpoints

#### 3.1 Submit Potential Notice Analysis Job
**Endpoint:** `POST /tds/analytics/potential-notices`

**Purpose:** Initiate analysis to identify potential tax authority notices

**Parameters:**
```json
{
  "@entity": "in.co.sandbox.tds.analytics.potential_notice.request",
  "tan": "AHMA09719B",
  "quarter": "Q1",
  "form": "26Q|27Q|24Q",
  "financial_year": "FY 2024-25"
}
```

**Response:**
```json
{
  "job_id": "uuid",
  "status": "created",
  "created_at": 1716515767000,
  "json_url": "https://api.sandbox.co.in/.../analysis.json"
}
```

#### 3.2 Fetch Analytics Jobs
**Endpoint:** `POST /tds/analytics/potential-notices/search`

**Purpose:** Retrieve list of analytics jobs with pagination

**Parameters:**
```json
{
  "@entity": "in.co.sandbox.tds.analytics.potential_notice.jobs.search",
  "tan": "AHMA09719B",
  "quarter": "Q1",
  "form": "26Q",
  "financial_year": "FY 2024-25",
  "page_size": 20,
  "last_evaluated_key": null
}
```

**Response:**
```json
{
  "items": [
    {
      "job_id": "uuid",
      "status": "succeeded",
      "potential_notice_report_url": "https://..."
    }
  ],
  "last_evaluated_key": null
}
```

#### 3.3 Poll Analytics Job Status
**Endpoint:** `GET /tds/analytics/potential-notices?job_id={job_id}`

**Purpose:** Check analysis progress and get report URL

**Response:**
```json
{
  "job_id": "uuid",
  "status": "succeeded|processing|failed",
  "potential_notice_report_url": "https://...",
  "form": "26Q",
  "quarter": "Q1",
  "financial_year": "FY 2024-25"
}
```

#### 3.4 Analyze Risk Assessment
**Endpoint:** `POST /tds/analytics/risk-assessment`

**Purpose:** Comprehensive risk scoring for TDS/TCS returns

**Parameters:**
```json
{
  "tan": "AHMA09719B",
  "form": "26Q",
  "fy": "FY 2024-25",
  "invoice_count": 100,
  "total_tds": 500000
}
```

**Response:**
```json
{
  "risk_score": 15,
  "risk_level": "LOW|MEDIUM|HIGH|CRITICAL",
  "risk_factors": ["missing_pan", "amount_mismatch"],
  "recommendations": ["verify_pan_format", "reconcile_challans"]
}
```

#### 3.5 Non-Salary Payments Analysis
**Endpoint:** `POST /tds/analytics/non-salary`

**Purpose:** Analyze non-salary payment TDS deductions

**Parameters:**
```json
{
  "deduction_type": "professional_fees|contractor|consultancy",
  "amount": 100000,
  "section": "194J|194C|194D"
}
```

#### 3.6 Salary Payments Analysis
**Endpoint:** `POST /tds/analytics/salary`

**Purpose:** Analyze salary payment TDS deductions

#### 3.7 NRI Payments Analysis
**Endpoint:** `POST /tds/analytics/nri`

**Purpose:** Analyze NRI payment TDS deductions

---

## Module 4: Reports API

### Purpose
Generates filing-ready TDS/TCS return documents in NS1 format.

### Endpoints

#### 4.1 Generate Form 26Q
**Endpoint:** `POST /tds/reports/generate-26q`

**Purpose:** Generate Form 26Q (Non-Salary TDS) return

**Parameters:**
```json
{
  "tan": "AHMA09719B",
  "fy": "2024-25",
  "quarter": "Q1",
  "control_totals": {
    "records": 100,
    "amount": 5000000,
    "tds": 500000
  }
}
```

**Response:**
```json
{
  "txt_content": "NS1 formatted form",
  "status": "success"
}
```

#### 4.2 Generate Form 27Q
**Endpoint:** `POST /tds/reports/generate-27q`

**Purpose:** Generate Form 27Q (NRI TDS) return

#### 4.3 Generate Form 24Q
**Endpoint:** `POST /tds/reports/generate-24q`

**Purpose:** Generate Form 24Q (TCS) return

#### 4.4 Validate TDS Form
**Endpoint:** `POST /tds/reports/validate`

**Purpose:** Validate form format and content

**Parameters:**
```json
{
  "form_type": "26Q|27Q|24Q",
  "txt_content": "NS1 format content"
}
```

**Response:**
```json
{
  "is_valid": true,
  "errors": [],
  "warnings": []
}
```

---

## Module 5: Annexures & Master Data

### Purpose
Reference tables and master data for TDS calculations and compliance.

### Available Resources

#### 5.1 TDS Sections Reference
- Section 194C: Payments to contractors
- Section 194D: Payments for insurance commissions
- Section 194H: Payments for agricultural operations
- Section 194J: Payments to professionals
- Section 194LA: Payments for life insurance policies

#### 5.2 Form Types
- **Form 26Q**: Non-Salary TDS (quarterly)
- **Form 27Q**: NRI Payments TDS (quarterly)
- **Form 24Q**: TCS Return (quarterly)
- **Form 16**: Salary TDS Certificate (annual)
- **Form 16A**: Non-Salary TDS Certificate (annual)

#### 5.3 State/Bank Master Data
- Bank codes for challan submission
- State codes for Form 26A
- PAN format validation rules
- TAN format validation rules

#### 5.4 FY Definition
- Format: "FY YYYY-YY" (e.g., "FY 2024-25")
- Quarters:
  - Q1: April-June
  - Q2: July-September
  - Q3: October-December
  - Q4: January-March

---

## API Workflow Examples

### Workflow 1: Complete TDS Filing Process

```
1. RECEIVE PAYMENTS
   └─> Invoice data from vendors

2. CALCULATE TDS (Calculator API)
   POST /tds/calculator/calculate
   └─> Get TDS amounts per invoice

3. ANALYZE COMPLIANCE (Analytics API)
   POST /tds/analytics/potential-notices
   └─> Identify potential risks
   └─> Poll /tds/analytics/potential-notices for status

4. RECONCILE PAYMENTS
   └─> Match invoices to challans
   └─> Verify TDS coverage

5. GENERATE REPORT (Reports API)
   POST /tds/reports/generate-26q
   └─> Get NS1 format form

6. VALIDATE FORM (Reports API)
   POST /tds/reports/validate
   └─> Check for errors

7. GENERATE FVU (Compliance API)
   POST /tds/compliance/generate-fvu
   └─> Submit form + CSI
   └─> Poll /tds/compliance/e-file/poll for FVU

8. SUBMIT FOR E-FILING (Compliance API)
   POST /tds/compliance/e-file/submit
   └─> Submit FVU + Form 27A
   └─> Poll /tds/compliance/e-file/poll for ACK

9. DOWNLOAD CERTIFICATES (Compliance API)
   GET /tds/compliance/certificates/download
   └─> Get Form 16A for deductees

10. ARCHIVE & REPORT
    └─> Store ACK and certificates
    └─> Generate filing report
```

### Workflow 2: Risk Assessment Before Filing

```
1. Submit Analytics Job
   POST /tds/analytics/potential-notices

2. Wait for Processing (async, 30-120 min)

3. Poll Status
   GET /tds/analytics/potential-notices?job_id={job_id}

4. If Status = succeeded:
   └─> Download Report PDF
   └─> Review Potential Notice Risks
   └─> Fix Issues if Needed
   └─> Proceed to Filing

5. If Status = failed:
   └─> Check error message
   └─> Verify inputs
   └─> Resubmit job
```

---

## Authentication

### Headers Required
```
x-api-key: Your API Key
Authorization: Bearer {JWT_TOKEN}
x-api-version: 1.0 (optional)
Content-Type: application/json
```

### Getting Access Token
```
POST /authenticate
Headers:
  x-api-key: {key}
  x-api-secret: {secret}
  x-api-version: 1.0

Response:
{
  "data": {
    "access_token": "jwt_token",
    "expires_in": 86400
  }
}
```

---

## Error Codes & Status

### HTTP Status Codes
| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Bad request (validation error) |
| 401 | Unauthorized (auth failed) |
| 403 | Forbidden (permission denied) |
| 404 | Not found (resource missing) |
| 409 | Conflict (duplicate) |
| 500 | Server error |

### Job Status Values
| Status | Meaning |
|--------|---------|
| created | Just submitted |
| queued | Waiting in queue |
| processing | Being processed |
| succeeded | Completed successfully |
| failed | Failed with error |

---

## Local Implementation Status

### ✅ Implemented in Application

**Calculator API**
- [x] Basic structure available
- [ ] Not yet fully integrated

**Compliance API**
- [x] `pollFVUJobStatus()` - Poll FVU generation
- [x] `pollEFilingStatus()` - Poll e-filing status
- [x] `submitEFilingJob()` - Submit for e-filing
- [x] `submitFVUGenerationJob()` - Generate FVU
- [x] `downloadFVUFiles()` - Download FVU
- [x] `downloadCSI()` - Download challan info
- [ ] Certificate downloads (Form 16/16A)

**Analytics API**
- [x] `submitAnalyticsJob()` - Submit analysis job ✨ NEW
- [x] `fetchAnalyticsJobs()` - Fetch job list ✨ NEW
- [x] `pollAnalyticsJob()` - Poll job status
- [ ] `assessRisk()` - Risk assessment
- [ ] `analyzeNonSalary()` - Non-salary analysis
- [ ] `analyzeSalary()` - Salary analysis

**Reports API**
- [ ] Generate Form 26Q
- [ ] Generate Form 27Q
- [ ] Generate Form 24Q
- [ ] Validate forms

**Local Analytics (AnalyticsAPI.php)**
- [x] Local compliance checks
- [x] TDS calculation validation
- [x] Challan matching
- [x] PAN validation
- [x] Risk assessment (local)
- [ ] Integration with Sandbox Analytics

---

## Files

### Backend (PHP)
- `/tds/lib/SandboxTDSAPI.php` - API client (105+ lines)
- `/tds/lib/AnalyticsAPI.php` - Local compliance checks
- `/tds/api/submit_analytics_job.php` - Submit job endpoint
- `/tds/api/fetch_analytics_jobs.php` - Fetch jobs endpoint
- `/tds/api/poll_analytics_job.php` - Poll job endpoint

### Frontend (UI)
- `/tds/admin/compliance.php` - Compliance page with analytics integration

### Database
- `/tds/lib/migrations.php` - `analytics_jobs` table migration

### Documentation
- `/TDS_API_COMPLETE_REFERENCE.md` (this file)
- `/SANDBOX_ANALYTICS_COMPLETE_GUIDE.md`
- `/ANALYTICS_API_IMPLEMENTATION.md`
- `/ANALYTICS_QUICK_START.md`

---

## Next Steps to Complete Integration

### Phase 1: Analytics (DONE ✓)
- [x] Submit potential notice analysis jobs
- [x] Fetch analytics job history
- [x] Poll job status
- [x] UI integration in compliance page

### Phase 2: Reports (TODO)
- [ ] Generate Form 26Q
- [ ] Generate Form 27Q
- [ ] Generate Form 24Q
- [ ] Validate forms before submission

### Phase 3: Enhanced Compliance (TODO)
- [ ] Download Form 16/16A certificates
- [ ] Certificate management
- [ ] Batch certificate generation

### Phase 4: Calculator (TODO)
- [ ] Full Calculator API integration
- [ ] Real-time TDS calculation
- [ ] Tax impact analysis

---

## References

**Official Documentation:**
- Sandbox TDS API: https://developer.sandbox.co.in/api-reference/tds
- GitHub Docs: https://github.com/in-co-sandbox/in-co-sandbox-docs

**Sandbox Postman Collections:**
- TDS Analytics: https://www.postman.com/in-co-sandbox/sandbox-api/collection/xxxxx/tds-analytics
- TDS Calculator: https://www.postman.com/in-co-sandbox/sandbox-api/collection/xxxxx/tds-calculator
- TDS Compliance: https://www.postman.com/in-co-sandbox/sandbox-api/collection/xxxxx/tds-compliance

**Forms Reference:**
- Form 26Q: Non-Salary TDS Return (Quarterly)
- Form 27Q: NRI Payments TDS Return (Quarterly)
- Form 24Q: TCS Return (Quarterly)
- Form 16: Salary TDS Certificate (Annual)
- Form 16A: Non-Salary TDS Certificate (Annual)

---

## Summary

The **TDS API** is a comprehensive system for automating tax compliance:

| Module | Purpose | Status |
|--------|---------|--------|
| **Calculator** | Tax computation | Partial |
| **Compliance** | E-filing & certificates | Implemented |
| **Analytics** | Risk analysis | ✨ Implemented |
| **Reports** | Return generation | TODO |
| **Annexures** | Reference data | Available |

The application now has **full Analytics integration** for identifying compliance risks BEFORE filing.

---

**Last Updated:** December 9, 2025
**Integration Status:** Analytics Module Complete ✓
**Next Phase:** Reports API Integration
