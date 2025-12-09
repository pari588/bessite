# Sandbox Compliance API - Complete Reference

**Source:** https://github.com/in-co-sandbox/in-co-sandbox-docs/tree/main/api-reference/tds/compliance
**Last Updated:** December 9, 2025
**API Base URLs:**
- Production: `https://api.sandbox.co.in`
- Testing: `https://test-api.sandbox.co.in`

---

## Overview

The **Sandbox Compliance API** provides a complete end-to-end solution for TDS (Tax Deducted at Source) compliance automation, including:

1. **FVU Generation** - Validate and generate File Validation Unit for TDS returns
2. **E-Filing Submission** - Submit validated FVU to tax authorities
3. **Compliance Checks** - Verify compliance with Section 206AB & 206CCA rules
4. **CSI Download** - Get Challan Status Information for reconciliation
5. **Certificates** - Generate and download Form 16/16A certificates

---

## API Endpoints

### 1. Section 206AB & 206CCA Compliance Check

**Endpoint:** `POST /tds/compliance/206ab/check`

**Purpose:** Check whether a PAN holder is a "specified person" under Section 206AB & 206CCA rules.

**Request Headers:**
```
Authorization: JWT access token (required)
x-api-key: API key for identification (required)
x-api-version: API version (optional)
Content-Type: application/json
```

**Request Body:**
```json
{
  "@entity": "in.co.sandbox.tds.compliance.206ab_check.request",
  "pan": "XXXPX1234A",
  "financial_year": "FY 2024-25"
}
```

**Response (200 OK):**
```json
{
  "code": 200,
  "timestamp": 1763362637000,
  "transaction_id": "e2b9145f-69d5-4bbe-a6de-be6fc08b426f",
  "data": {
    "@entity": "in.co.sandbox.tds.compliance.206ab_check.response",
    "pan": "XXXPX1234A",
    "name": "SXXXXJ MXXXXXXXXR",
    "pan_allotment_date": 1527100200000,
    "financial_year": "FY 2024-25",
    "specified_person_us_206ab_&_206cca": "y|n",
    "pan_status": "operative|inoperative"
  }
}
```

**Response Fields:**
- `pan`: PAN of the person being checked
- `name`: Name of the PAN holder
- `pan_allotment_date`: Unix timestamp of PAN allotment
- `financial_year`: FY in format "FY YYYY-YY"
- `specified_person_us_206ab_&_206cca`: "y" (yes) or "n" (no)
- `pan_status`: "operative" or "inoperative"

**Use Cases:**
- Verify if a vendor is a "specified person"
- Determine if TDS exemption applies (206AB/206CCA)
- Validate vendor tax compliance status

---

### 2. Generate FVU (File Validation Unit)

**Endpoint:** `POST /tds/compliance/fvu/generate`

**Purpose:** Submit Form 26Q/24Q/27Q for validation and generate FVU file required for TDS return filing.

**Request Headers:**
```
Authorization: JWT access token (required)
x-api-key: API key for identification (required)
x-api-version: API version (optional)
Content-Type: application/json
```

**Request Body:**
```json
{
  "@entity": "in.co.sandbox.tds.compliance.fvu.generate.request",
  "tan": "AHMA09719B",
  "deductor_name": "Company Name",
  "form_type": "26Q|24Q|27Q",
  "form_variant": "ORIGINAL|CORRECTED",
  "financial_year": "FY 2024-25",
  "quarter": "Q1|Q2|Q3|Q4",
  "form_content": "<base64 encoded form content>",
  "text_content": "<raw form text content>",
  "email": "contact@company.com",
  "mobile": "+919999999999"
}
```

**Request Parameters:**
- `tan`: TAN of deductor (e.g., AHMA09719B) - required
- `deductor_name`: Name of the deducting entity - required
- `form_type`: Form type - "26Q" (Non-Salary TDS), "24Q" (TCS), or "27Q" (NRI) - required
- `form_variant`: "ORIGINAL" or "CORRECTED" - required
- `financial_year`: FY in format "FY YYYY-YY" (e.g., "FY 2024-25") - required
- `quarter`: Quarter code "Q1", "Q2", "Q3", or "Q4" - required
- `form_content`: Base64 encoded binary form content - optional
- `text_content`: Raw text form content - optional (at least one of form_content or text_content required)
- `email`: Email address for notifications - optional
- `mobile`: Mobile number for notifications - optional

**Response (202 Accepted - Job Created):**
```json
{
  "code": 202,
  "timestamp": 1763362637000,
  "transaction_id": "e2b9145f-69d5-4bbe-a6de-be6fc08b426f",
  "data": {
    "@entity": "in.co.sandbox.tds.compliance.fvu.generate.response",
    "job_id": "550e8400-e29b-41d4-a716-446655440000",
    "tan": "AHMA09719B",
    "deductor_name": "Company Name",
    "form_type": "26Q",
    "form_variant": "ORIGINAL",
    "financial_year": "FY 2024-25",
    "quarter": "Q1",
    "status": "created|queued|processing|succeeded|failed",
    "created_at": 1763362637000,
    "json_url": "https://api.sandbox.co.in/...",
    "presigned_url": "https://s3-bucket.com/...",
    "estimated_completion_time": 600000
  }
}
```

**Status Lifecycle:**
- `created` → Job created, validation pending
- `queued` → Job queued for processing
- `processing` → FVU generation in progress (1-30 min)
- `succeeded` → FVU generated successfully
- `failed` → FVU generation failed

**Download FVU:**
Once status is "succeeded", download from `json_url` or `presigned_url`

---

### 3. Poll FVU Generation Status

**Endpoint:** `GET /tds/compliance/fvu/generate?job_id={job_id}`

**Purpose:** Check the status of FVU generation and retrieve the FVU file when ready.

**Request Headers:**
```
Authorization: JWT access token (required)
x-api-key: API key for identification (required)
x-api-version: API version (optional)
```

**Query Parameters:**
- `job_id`: Job ID from FVU generation request - required

**Response (200 OK):**
```json
{
  "code": 200,
  "timestamp": 1763362637000,
  "transaction_id": "e2b9145f-69d5-4bbe-a6de-be6fc08b426f",
  "data": {
    "@entity": "in.co.sandbox.tds.compliance.fvu.generate.response",
    "job_id": "550e8400-e29b-41d4-a716-446655440000",
    "tan": "AHMA09719B",
    "form_type": "26Q",
    "financial_year": "FY 2024-25",
    "quarter": "Q1",
    "status": "succeeded|processing|failed",
    "created_at": 1763362637000,
    "updated_at": 1763362700000,
    "json_url": "https://api.sandbox.co.in/download/fvu/...",
    "presigned_url": "https://s3-bucket.com/fvu/...",
    "error_message": "Error details if status is failed",
    "validation_errors": ["Error 1", "Error 2"]
  }
}
```

---

### 4. Search FVU Generation Jobs

**Endpoint:** `POST /tds/compliance/fvu/generate/search`

**Purpose:** Search and retrieve all FVU generation jobs matching criteria with pagination.

**Request Body:**
```json
{
  "@entity": "in.co.sandbox.tds.compliance.fvu.generate.search.request",
  "tan": "AHMA09719B",
  "form_type": "26Q|24Q|27Q",
  "financial_year": "FY 2024-25",
  "quarter": "Q1|Q2|Q3|Q4",
  "status": "created|queued|processing|succeeded|failed",
  "page_size": 50,
  "last_evaluated_key": "cursor_token_for_pagination"
}
```

**Response:**
```json
{
  "code": 200,
  "timestamp": 1763362637000,
  "data": {
    "@entity": "in.co.sandbox.tds.compliance.fvu.generate.search.response",
    "count": 5,
    "items": [
      {
        "job_id": "550e8400-e29b-41d4-a716-446655440000",
        "tan": "AHMA09719B",
        "form_type": "26Q",
        "financial_year": "FY 2024-25",
        "quarter": "Q1",
        "status": "succeeded",
        "created_at": 1763362637000,
        "json_url": "https://api.sandbox.co.in/download/fvu/..."
      }
    ],
    "last_evaluated_key": "next_page_cursor",
    "has_more": true
  }
}
```

---

### 5. Submit for E-Filing

**Endpoint:** `POST /tds/compliance/e-file`

**Purpose:** Submit validated FVU + Form 27A (acknowledgement) to tax authorities for final e-filing.

**Request Headers:**
```
Authorization: JWT access token (required)
x-api-key: API key for identification (required)
Content-Type: multipart/form-data
```

**Request Body (Multipart Form):**
```
fvu_file: <ZIP file with FVU content>
form27a_file: <signed Form 27A PDF>
tan: "AHMA09719B"
form_type: "26Q"
financial_year: "FY 2024-25"
quarter: "Q1"
```

**Or JSON:**
```json
{
  "@entity": "in.co.sandbox.tds.compliance.e-file.request",
  "tan": "AHMA09719B",
  "form_type": "26Q",
  "financial_year": "FY 2024-25",
  "quarter": "Q1",
  "fvu_job_id": "550e8400-e29b-41d4-a716-446655440000",
  "form27a_content": "<base64 encoded signed Form 27A>",
  "digital_signature": "<digital signature content>"
}
```

**Response (202 Accepted):**
```json
{
  "code": 202,
  "timestamp": 1763362637000,
  "transaction_id": "e2b9145f-69d5-4bbe-a6de-be6fc08b426f",
  "data": {
    "@entity": "in.co.sandbox.tds.compliance.e-file.response",
    "job_id": "550e8500-e29b-41d4-a716-446655440001",
    "tan": "AHMA09719B",
    "form_type": "26Q",
    "financial_year": "FY 2024-25",
    "quarter": "Q1",
    "status": "submitted|processing|acknowledged|rejected",
    "filing_date": 1763362637000,
    "ack_number": null,
    "expected_ack_time": "Within 2 hours"
  }
}
```

**Status Lifecycle:**
- `submitted` → Submitted to tax authority
- `processing` → Being processed
- `acknowledged` → ACK number received
- `rejected` → Filing rejected

---

### 6. Poll E-Filing Status

**Endpoint:** `GET /tds/compliance/e-file?job_id={job_id}`

**Purpose:** Check the status of submitted e-filing and retrieve acknowledgement number when available.

**Query Parameters:**
- `job_id`: Job ID from e-filing submission - required

**Response:**
```json
{
  "code": 200,
  "timestamp": 1763362637000,
  "data": {
    "@entity": "in.co.sandbox.tds.compliance.e-file.response",
    "job_id": "550e8500-e29b-41d4-a716-446655440001",
    "tan": "AHMA09719B",
    "form_type": "26Q",
    "financial_year": "FY 2024-25",
    "quarter": "Q1",
    "status": "acknowledged|processing|rejected",
    "ack_number": "ACK123456789",
    "ack_date": 1763362700000,
    "ack_download_url": "https://api.sandbox.co.in/download/ack/..."
  }
}
```

---

### 7. Search E-Filing Jobs

**Endpoint:** `POST /tds/compliance/e-file/search`

**Purpose:** Search and retrieve all e-filing jobs with filtering and pagination.

**Request Body:**
```json
{
  "@entity": "in.co.sandbox.tds.compliance.e-file.search.request",
  "tan": "AHMA09719B",
  "form_type": "26Q|24Q|27Q",
  "financial_year": "FY 2024-25",
  "quarter": "Q1|Q2|Q3|Q4",
  "status": "submitted|acknowledged|rejected",
  "page_size": 50,
  "last_evaluated_key": "pagination_cursor"
}
```

**Response:**
```json
{
  "code": 200,
  "data": {
    "count": 10,
    "items": [
      {
        "job_id": "550e8500-e29b-41d4-a716-446655440001",
        "tan": "AHMA09719B",
        "form_type": "26Q",
        "status": "acknowledged",
        "ack_number": "ACK123456789",
        "ack_date": 1763362700000
      }
    ],
    "last_evaluated_key": "next_cursor",
    "has_more": true
  }
}
```

---

### 8. Download CSI - Generate OTP

**Endpoint:** `POST /tds/compliance/csi/otp`

**Purpose:** Generate OTP to download Challan Status Information (CSI) for reconciliation.

**Request Body:**
```json
{
  "@entity": "in.co.sandbox.tds.compliance.csi.otp.request",
  "tan": "AHMA09719B",
  "financial_year": "FY 2024-25",
  "quarter": "Q1|Q2|Q3|Q4",
  "mobile": "+919999999999",
  "date_range": {
    "from": 1763276400000,
    "to": 1763362800000
  },
  "consent": true,
  "reason": "TDS reconciliation"
}
```

**Response:**
```json
{
  "code": 200,
  "timestamp": 1763362637000,
  "data": {
    "@entity": "in.co.sandbox.tds.compliance.csi.otp.response",
    "tan": "AHMA09719B",
    "otp_sent": true,
    "otp_delivery_medium": "mobile",
    "otp_expiry_time": 600,
    "message": "OTP sent to registered mobile number"
  }
}
```

---

### 9. Download CSI - Verify OTP

**Endpoint:** `POST /tds/compliance/csi/otp/verify`

**Purpose:** Verify OTP and get download link for CSI file.

**Request Body:**
```json
{
  "@entity": "in.co.sandbox.tds.compliance.csi.otp.verify.request",
  "tan": "AHMA09719B",
  "otp": "123456",
  "mobile": "+919999999999"
}
```

**Response:**
```json
{
  "code": 200,
  "timestamp": 1763362637000,
  "data": {
    "@entity": "in.co.sandbox.tds.compliance.csi.otp.verify.response",
    "tan": "AHMA09719B",
    "otp_verified": true,
    "csi_download_url": "https://api.sandbox.co.in/download/csi/CSI_AHMA09719B_FY2024-25_Q1.txt",
    "file_format": "TXT",
    "file_expiry": 86400,
    "message": "CSI file ready for download"
  }
}
```

**CSI File Format:** Pipe-delimited (|) text file containing:
- Challan Serial Number
- Challan Date
- BSR Code
- TDS Amount
- Deposit Status
- And other bank reconciliation details

---

### 10. Generate Form 16 Certificate

**Endpoint:** `POST /tds/compliance/traces/deductors/forms/16`

**Purpose:** Generate Form 16 (TDS Certificate) for deductees.

**Request Body:**
```json
{
  "@entity": "in.co.sandbox.tds.compliance.form16.generate.request",
  "tan": "AHMA09719B",
  "financial_year": "FY 2024-25",
  "deductee_pan": "AAAPA1234A",
  "deductee_name": "Deductee Name",
  "form_variant": "ORIGINAL|CORRECTED"
}
```

**Response:**
```json
{
  "code": 202,
  "timestamp": 1763362637000,
  "data": {
    "@entity": "in.co.sandbox.tds.compliance.form16.generate.response",
    "job_id": "550e8600-e29b-41d4-a716-446655440002",
    "tan": "AHMA09719B",
    "deductee_pan": "AAAPA1234A",
    "certificate_number": "CERT-AAAPA-20241209",
    "financial_year": "FY 2024-25",
    "status": "processing|succeeded|failed",
    "pdf_download_url": "https://api.sandbox.co.in/download/form16/..."
  }
}
```

---

### 11. Poll Form 16 Status

**Endpoint:** `POST /tds/compliance/traces/deductors/forms/16/status`

**Purpose:** Check the status of Form 16 generation.

**Request Body:**
```json
{
  "job_id": "550e8600-e29b-41d4-a716-446655440002"
}
```

**Response:**
```json
{
  "code": 200,
  "data": {
    "job_id": "550e8600-e29b-41d4-a716-446655440002",
    "status": "succeeded|processing|failed",
    "certificate_number": "CERT-AAAPA-20241209",
    "pdf_download_url": "https://api.sandbox.co.in/download/form16/...",
    "pdf_expiry": 86400
  }
}
```

---

### 12. Search Form 16 Certificates

**Endpoint:** `POST /tds/compliance/traces/deductors/forms/16/search`

**Purpose:** Search Form 16 certificates for deductees.

**Request Body:**
```json
{
  "tan": "AHMA09719B",
  "financial_year": "FY 2024-25",
  "status": "succeeded|failed",
  "page_size": 50,
  "last_evaluated_key": "pagination_cursor"
}
```

**Response:**
```json
{
  "code": 200,
  "data": {
    "count": 25,
    "items": [
      {
        "job_id": "550e8600-e29b-41d4-a716-446655440002",
        "deductee_pan": "AAAPA1234A",
        "certificate_number": "CERT-AAAPA-20241209",
        "status": "succeeded",
        "pdf_download_url": "https://api.sandbox.co.in/download/form16/..."
      }
    ],
    "has_more": true
  }
}
```

---

## Common Request Headers

All API endpoints require these headers:

```
Authorization: Bearer {jwt_access_token}
x-api-key: {your_api_key}
x-api-version: v1 (optional)
Content-Type: application/json
```

## Error Responses

### 400 Bad Request
```json
{
  "code": 400,
  "error": "INVALID_REQUEST",
  "message": "Invalid request parameters"
}
```

### 401 Unauthorized
```json
{
  "code": 401,
  "error": "UNAUTHORIZED",
  "message": "Authentication failed"
}
```

### 403 Forbidden
```json
{
  "code": 403,
  "error": "FORBIDDEN",
  "message": "Insufficient permissions"
}
```

### 404 Not Found
```json
{
  "code": 404,
  "error": "NOT_FOUND",
  "message": "Resource not found"
}
```

### 429 Rate Limited
```json
{
  "code": 429,
  "error": "RATE_LIMIT_EXCEEDED",
  "message": "Too many requests"
}
```

### 500 Server Error
```json
{
  "code": 500,
  "error": "INTERNAL_SERVER_ERROR",
  "message": "Server error occurred"
}
```

---

## Complete TDS Filing Workflow

### Step 1: Verify Compliance Status
```
POST /tds/compliance/206ab/check
Input: PAN, FY
Output: Is "specified person" under 206AB/206CCA?
```

### Step 2: Generate FVU
```
POST /tds/compliance/fvu/generate
Input: Form content (26Q/24Q/27Q), TAN, FY, Quarter
Output: job_id, FVU file when ready
```

### Step 3: Monitor FVU Generation
```
GET /tds/compliance/fvu/generate?job_id=XXX
Poll until status = "succeeded"
Download FVU file
```

### Step 4: Submit for E-Filing
```
POST /tds/compliance/e-file
Input: FVU + Form 27A (signed)
Output: filing_job_id
```

### Step 5: Monitor E-Filing Status
```
GET /tds/compliance/e-file?job_id=XXX
Poll until status = "acknowledged"
Receive ACK number
```

### Step 6: Download Acknowledgement
```
From ACK URL in e-filing response
```

### Step 7: Download CSI for Reconciliation
```
POST /tds/compliance/csi/otp
Generate OTP
POST /tds/compliance/csi/otp/verify
Verify OTP, get CSI download link
```

### Step 8: Generate Form 16 Certificates
```
POST /tds/compliance/traces/deductors/forms/16
Input: Deductee PAN, FY
Output: Certificate with status
```

---

## Key Data Formats

### TAN Format
```
Pattern: [A-Z]{4}[0-9]{5}[A-Z]
Example: AHMA09719B
Length: 10 characters
```

### PAN Format
```
Pattern: [A-Z]{5}[0-9]{4}[A-Z]
Example: AAAPA1234A
Length: 10 characters
```

### Financial Year Format
```
Pattern: FY YYYY-YY
Example: FY 2024-25
Format: FY [start_year]-[end_year_last_2_digits]
```

### Quarter Format
```
Q1: April - June
Q2: July - September
Q3: October - December
Q4: January - March
```

### Form Types
```
24Q: Tax Collected at Source (TCS)
26Q: Tax Deducted at Source (Non-Salary)
27Q: Tax Deducted from NRI Payments
27EQ: Tax Deducted from Equalization Levy
```

---

## API Response Structure

All successful responses follow this structure:

```json
{
  "code": 200|201|202,
  "timestamp": 1763362637000,
  "transaction_id": "uuid",
  "data": {
    "@entity": "in.co.sandbox.tds.compliance.XXX.response",
    // Response-specific fields
  }
}
```

---

## Rate Limits

- 100 requests per minute per API key
- Burst limit: 200 requests per minute
- Rate limit headers:
  - `X-RateLimit-Limit`: Total requests allowed
  - `X-RateLimit-Remaining`: Requests remaining
  - `X-RateLimit-Reset`: Unix timestamp when limit resets

---

## Webhooks (Optional)

For async operations, you can configure webhooks to receive notifications:

**Webhook Events:**
- `fvu.generation.completed`
- `fvu.generation.failed`
- `efile.acknowledged`
- `efile.rejected`
- `form16.generated`

---

## Testing

### Test Credentials
- API Base URL: `https://test-api.sandbox.co.in`
- Test TAN: `AHMA09719B` (provided by Sandbox)
- Test PAN: `AAAPA1234A` (will return specified_person: "y")

### Test Cases
1. FVU generation with valid form
2. FVU generation with invalid form (should fail)
3. E-filing with acknowledged status
4. CSI download with OTP verification
5. Form 16 generation for multiple deductees

---

## Summary

The **Sandbox Compliance API** provides 10 main endpoints for complete TDS compliance automation:

| Endpoint | Purpose | Method |
|----------|---------|--------|
| `/tds/compliance/206ab/check` | Compliance check | POST |
| `/tds/compliance/fvu/generate` | FVU generation & status | POST/GET |
| `/tds/compliance/fvu/generate/search` | Search FVU jobs | POST |
| `/tds/compliance/e-file` | E-filing & status | POST/GET |
| `/tds/compliance/e-file/search` | Search e-filing jobs | POST |
| `/tds/compliance/csi/otp` | Generate OTP for CSI | POST |
| `/tds/compliance/csi/otp/verify` | Verify OTP & get CSI | POST |
| `/tds/compliance/traces/deductors/forms/16` | Generate Form 16 | POST |
| `/tds/compliance/traces/deductors/forms/16/status` | Poll Form 16 status | POST |
| `/tds/compliance/traces/deductors/forms/16/search` | Search Form 16s | POST |

All endpoints are async job-based with polling support for checking status.
