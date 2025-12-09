# Sandbox Analytics API - Complete Reference

**Source:** https://github.com/in-co-sandbox/in-co-sandbox-docs/tree/main/api-reference/tds/analytics/tds-analytics
**Last Updated:** December 9, 2025
**API Base URLs:**
- Production: `https://api.sandbox.co.in`
- Testing: `https://test-api.sandbox.co.in`

---

## Overview

The **Sandbox Analytics API** provides **Potential Notice Analysis** to identify tax compliance risks and flag issues that might trigger tax authority notices. Unlike the Compliance API which is for TDS filing operations, Analytics is for **risk assessment and analysis**.

### Key Differences from Compliance API

| Aspect | Analytics API | Compliance API |
|--------|---|---|
| **Purpose** | Risk analysis & potential notice identification | TDS filing operations |
| **When to Use** | Before/after filing to assess risks | For FVU generation and e-filing |
| **Processing** | 30 min - 2 hours | 1-30 minutes |
| **Output** | Risk report with potential issues | FVU file, acknowledgement |
| **Use Case** | Risk dashboard, compliance alerts | Filing workflow |

---

## Features

The Analytics API analyzes TDS returns to:
- ✅ Identify compliance risks in TDS returns
- ✅ Flag issues that might trigger tax authority notices
- ✅ Validate forms against TRACES requirements
- ✅ Provide detailed risk reports with risk scoring
- ✅ Support all TDS forms (24Q, 26Q, 27Q) and TCS form (27EQ)
- ✅ Track analysis jobs with polling

---

## API Endpoints

### TDS Analytics (Forms 24Q, 26Q, 27Q)

#### 1. Submit TDS Potential Notice Job

**Endpoint:** `POST /tds/analytics/potential-notices`

**Purpose:** Submit Form 24Q, 26Q, or 27Q for potential notice analysis.

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
  "@entity": "in.co.sandbox.tds.analytics.potential_notices.job",
  "tan": "AHMA09719B",
  "quarter": "Q1|Q2|Q3|Q4",
  "financial_year": "FY 2024-25",
  "form": "24Q|26Q|27Q",
  "form_content": "<base64_encoded_form_data>"
}
```

**Request Parameters:**
- `tan`: TAN of deductor (e.g., AHMA09719B) - required
- `quarter`: Quarter (Q1-Q4) - required
- `financial_year`: FY in format "FY YYYY-YY" - required
- `form`: Form type:
  - `24Q`: Tax Collected at Source
  - `26Q`: Tax Deducted at Source (Non-Salary)
  - `27Q`: Tax Deducted from NRI Payments
- `form_content`: Base64 encoded form data in JSON format - required

**Response (200 OK - Job Created):**
```json
{
  "code": 200,
  "timestamp": 1763633700000,
  "transaction_id": "1c1e4905-d07e-4c51-b8c5-1163050ded7f",
  "data": {
    "@entity": "in.co.sandbox.tds.analytics.potential_notices.job",
    "job_id": "1c1e4905-d07e-4c51-b8c5-1163050ded7f",
    "tan": "AHMA09719B",
    "quarter": "Q4",
    "financial_year": "FY 2023-24",
    "form": "26Q",
    "status": "created",
    "created_at": 1716515767000,
    "json_url": "https://test-api.sandbox.co.in/tds/analytics/.../80716d9a.json"
  }
}
```

**Response Fields:**
- `job_id`: Unique identifier for this analysis job
- `tan`: TAN of the deductor
- `quarter`: Quarter analyzed
- `financial_year`: FY analyzed
- `form`: Form type analyzed
- `status`: Job status (created, queued, processing, succeeded, failed)
- `created_at`: Unix timestamp of job creation
- `json_url`: URL to download risk report when job completes

**Status Lifecycle:**
```
created → queued → processing → succeeded (or failed)
```

---

#### 2. Poll TDS Potential Notice Job Status

**Endpoint:** `GET /tds/analytics/potential-notices?job_id={job_id}`

**Purpose:** Check analysis status and retrieve results/risk report.

**Query Parameters:**
- `job_id`: Job ID from submission - required

**Response (200 OK - Job In Progress):**
```json
{
  "code": 200,
  "timestamp": 1763633700000,
  "data": {
    "@entity": "in.co.sandbox.tds.analytics.potential_notices.job",
    "job_id": "1c1e4905-d07e-4c51-b8c5-1163050ded7f",
    "status": "processing",
    "created_at": 1716515767000,
    "message": "Analysis in progress"
  }
}
```

**Response (200 OK - Job Succeeded):**
```json
{
  "code": 200,
  "timestamp": 1763633700000,
  "data": {
    "@entity": "in.co.sandbox.tds.analytics.potential_notices.job",
    "job_id": "1c1e4905-d07e-4c51-b8c5-1163050ded7f",
    "tan": "AHMA09719B",
    "quarter": "Q4",
    "financial_year": "FY 2023-24",
    "form": "26Q",
    "status": "succeeded",
    "created_at": 1716515767000,
    "completed_at": 1716520000000,
    "risk_level": "high|medium|low",
    "potential_notices_count": 5,
    "risk_score": 85,
    "report_url": "https://test-api.sandbox.co.in/tds/analytics/.../report.json",
    "issues": [
      {
        "issue_id": "ISSUE_001",
        "severity": "high|medium|low",
        "category": "validation|compliance|data",
        "description": "Missing TDS amount in some records",
        "records_affected": 10,
        "remediation": "Verify and correct TDS amounts"
      }
    ]
  }
}
```

**Key Response Fields:**
- `status`: Job status
- `risk_level`: Overall risk assessment (high, medium, low)
- `risk_score`: Numerical risk score (0-100)
- `potential_notices_count`: Number of potential issues found
- `report_url`: URL to full risk report in JSON format
- `issues`: Array of identified compliance issues with details

---

#### 3. Search TDS Analytics Jobs

**Endpoint:** `POST /tds/analytics/potential-notices/search`

**Purpose:** Search and retrieve historical analysis jobs with pagination.

**Request Body:**
```json
{
  "@entity": "in.co.sandbox.tds.analytics.potential_notices.search.request",
  "tan": "AHMA09719B",
  "quarter": "Q1|Q2|Q3|Q4",
  "financial_year": "FY 2024-25",
  "form": "24Q|26Q|27Q",
  "status": "created|queued|processing|succeeded|failed",
  "page_size": 50,
  "last_evaluated_key": "pagination_cursor"
}
```

**Response:**
```json
{
  "code": 200,
  "timestamp": 1763633700000,
  "data": {
    "@entity": "in.co.sandbox.tds.analytics.potential_notices.search.response",
    "count": 10,
    "items": [
      {
        "job_id": "1c1e4905-d07e-4c51-b8c5-1163050ded7f",
        "tan": "AHMA09719B",
        "quarter": "Q4",
        "financial_year": "FY 2023-24",
        "form": "26Q",
        "status": "succeeded",
        "risk_level": "high",
        "risk_score": 85,
        "created_at": 1716515767000,
        "completed_at": 1716520000000,
        "report_url": "https://test-api.sandbox.co.in/tds/analytics/.../report.json"
      }
    ],
    "last_evaluated_key": "next_page_cursor",
    "has_more": true
  }
}
```

---

### TCS Analytics (Form 27EQ)

#### 4. Submit TCS Potential Notice Job

**Endpoint:** `POST /tcs/analytics/potential-notices`

**Purpose:** Submit Form 27EQ for TCS potential notice analysis.

**Request Body:**
```json
{
  "@entity": "in.co.sandbox.tcs.analytics.potential_notices.job",
  "tan": "AHMA09719B",
  "quarter": "Q1|Q2|Q3|Q4",
  "financial_year": "FY 2024-25",
  "form": "27EQ",
  "form_content": "<base64_encoded_form_data>"
}
```

**Response:** Same structure as TDS job creation

---

#### 5. Poll TCS Potential Notice Job Status

**Endpoint:** `GET /tcs/analytics/potential-notices?job_id={job_id}`

**Purpose:** Check TCS analysis status and retrieve risk report.

**Response:** Same structure as TDS job status

---

#### 6. Search TCS Analytics Jobs

**Endpoint:** `POST /tcs/analytics/potential-notices/search`

**Purpose:** Search historical TCS analysis jobs.

**Request Body:**
```json
{
  "@entity": "in.co.sandbox.tcs.analytics.potential_notices.search.request",
  "tan": "AHMA09719B",
  "quarter": "Q1|Q2|Q3|Q4",
  "financial_year": "FY 2024-25",
  "form": "27EQ",
  "status": "succeeded|failed",
  "page_size": 50,
  "last_evaluated_key": "pagination_cursor"
}
```

**Response:** Same structure as TDS search

---

## Form Data Formats

### Data Upload Method

1. **Create Job** with POST request → Get `json_url` (presigned S3 URL)
2. **Upload Form Data** to `json_url` using HTTP PUT with base64-encoded JSON
3. **Poll Job Status** to check when analysis completes

### Supported Payment Types

#### TDS Analytics - Salary Payments (Form 24Q)
- Salary deducted at source
- Employer-employee relationships
- Regular monthly/periodic deductions

**Schema:** View at https://sheet-tools.quicko.org.in/sheet-schema-builder?schema_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tds/analytics/forms/form24q_workbook.schema.json

**Sample:** https://sheet-tools.quicko.org.in/sheet-visualiser?schema_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tds/analytics/forms/form24q_workbook.schema.json&data_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tds/analytics/forms/form24q_workbook.data.json

#### TDS Analytics - Non-Salary Payments (Form 26Q)
- Professional fees
- Rent payments
- Commission payments
- Contractor payments
- Any other non-salary deductions

**Schema:** View at https://sheet-tools.quicko.org.in/sheet-schema-builder?schema_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tds/analytics/forms/form26q_workbook.schema.json

**Sample:** https://sheet-tools.quicko.org.in/sheet-visualiser?schema_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tds/analytics/forms/form26q_workbook.schema.json&data_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tds/analytics/forms/form26q_workbook.data.json

#### TDS Analytics - NRI Payments (Form 27Q)
- Payments to Non-Resident Indians
- Foreign contractor payments
- Dividend payments to NRIs
- Interest payments to NRIs

**Schema:** View at https://sheet-tools.quicko.org.in/sheet-schema-builder?schema_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tds/analytics/forms/form27q_workbook.schema.json

**Sample:** https://sheet-tools.quicko.org.in/sheet-visualiser?schema_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tds/analytics/forms/form27q_workbook.schema.json&data_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tds/analytics/forms/form27q_workbook.data.json

#### TCS Analytics - TCS Collections (Form 27EQ)
- Tax collected on e-commerce transactions
- TCS on sale of goods
- TCS on imported goods
- Equalization levy collections

**Schema:** View at https://sheet-tools.quicko.org.in/sheet-schema-builder?schema_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tcs/analytics/form/form27eq_workbook.schema.json

**Sample:** https://sheet-tools.quicko.org.in/sheet-visualiser?schema_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tcs/analytics/form/form27eq_workbook.schema.json&data_url=https://raw.githubusercontent.com/in-co-sandbox/in-co-sandbox-docs/refs/heads/main/data/tcs/analytics/form/form27eq_workbook.data.json

---

## Risk Report Structure

When a job succeeds, the risk report includes:

### Report Fields
```json
{
  "job_id": "uuid",
  "risk_analysis_summary": {
    "total_risk_score": 0-100,
    "risk_level": "high|medium|low",
    "potential_notices_count": 5,
    "critical_issues": 2,
    "warnings": 3
  },
  "categories": {
    "validation_errors": [
      {
        "error_code": "E001",
        "description": "Missing mandatory field",
        "severity": "critical",
        "affected_records": 10
      }
    ],
    "compliance_issues": [
      {
        "issue_code": "C001",
        "description": "Threshold violation",
        "severity": "high",
        "notice_likely": true,
        "notice_type": "Form 206"
      }
    ],
    "data_quality_issues": [
      {
        "issue_code": "Q001",
        "description": "Inconsistent data",
        "severity": "medium"
      }
    ]
  },
  "remediation_plan": [
    {
      "priority": 1,
      "action": "Verify TDS amounts",
      "estimated_effort": "2 hours"
    }
  ]
}
```

### Risk Score Interpretation
- **80-100**: HIGH RISK - Multiple critical issues, likely notice
- **50-79**: MEDIUM RISK - Some compliance issues, possible notice
- **0-49**: LOW RISK - Minor issues, unlikely notice

---

## Workflow: Potential Notice Analysis

### Step 1: Prepare Form Data
- Collect TDS/TCS payment details
- Format as JSON per form schema
- Base64 encode the JSON

### Step 2: Create Analysis Job
```
POST /tds/analytics/potential-notices
Input: TAN, Quarter, FY, Form, FormContent
Output: job_id, json_url
```

### Step 3: Upload Form Data (Optional if included in request)
```
PUT {json_url}
Upload base64-encoded form data
```

### Step 4: Monitor Analysis Progress
```
GET /tds/analytics/potential-notices?job_id={job_id}
Poll until status = "succeeded" or "failed"
Processing time: 30 min - 2 hours
```

### Step 5: Review Risk Report
```
GET {report_url}
Analyze identified issues
Review risk score and severity levels
```

### Step 6: Remediate Issues
- Address high/critical severity issues
- Re-run analysis if changes made
- Prepare for actual TDS filing

---

## Error Responses

### 400 Bad Request
```json
{
  "code": 400,
  "error": "INVALID_REQUEST",
  "message": "Invalid form data format"
}
```

### 401 Unauthorized
```json
{
  "code": 401,
  "error": "UNAUTHORIZED",
  "message": "Invalid API key or token"
}
```

### 429 Rate Limited
```json
{
  "code": 429,
  "error": "RATE_LIMIT_EXCEEDED",
  "message": "Too many requests, retry after 60 seconds"
}
```

### 500 Server Error
```json
{
  "code": 500,
  "error": "INTERNAL_SERVER_ERROR",
  "message": "Analysis failed due to server error"
}
```

---

## Key Data Formats

### TAN Format
```
Pattern: [A-Z]{4}[0-9]{5}[A-Z]
Example: AHMA09719B
Length: 10 characters
```

### Quarter Format
```
Q1: April - June
Q2: July - September
Q3: October - December
Q4: January - March
```

### Financial Year Format
```
Pattern: FY YYYY-YY
Example: FY 2024-25
Format: FY [start_year]-[end_year_last_2_digits]
```

### Form Types

**TDS Forms:**
- `24Q`: Tax Collected at Source (Salary)
- `26Q`: Tax Deducted at Source (Non-Salary)
- `27Q`: Tax Deducted from NRI Payments

**TCS Forms:**
- `27EQ`: Tax Collected on E-commerce

---

## Rate Limits

- 100 requests per minute per API key
- Burst limit: 200 requests per minute
- Analysis jobs can take 30 min - 2 hours
- Recommend polling every 5 minutes during processing

---

## Use Cases

### 1. Pre-Filing Risk Assessment
Analyze forms before creating FVU to identify and fix issues early.

### 2. Compliance Dashboard
Display risk scores for all quarters in a dashboard.

### 3. Automated Alerts
Create alerts when high-risk issues are detected.

### 4. Historical Analysis
Track risk trends across quarters and years.

### 5. Multi-Form Comparison
Compare risk scores across different deductors.

---

## Testing

### Test Credentials
- API Base URL: `https://test-api.sandbox.co.in`
- Test TAN: `AHMA09719B`
- Max test jobs per day: 100

### Test Cases
1. Submit job for Form 26Q with sample data
2. Poll job status while processing
3. Retrieve risk report when complete
4. Search historical jobs
5. Test error cases (invalid TAN, missing fields)

---

## Important Notes

- **Processing Time:** 30 minutes to 2 hours (async operation)
- **Data Storage:** Form data uploaded to presigned S3 URL (secure)
- **Report Retention:** Risk reports available for 30 days
- **Webhook Support:** Available for job completion notifications
- **Pagination:** Max 100 items per page

---

## Summary

The **Sandbox Analytics API** provides 6 main endpoints for TDS/TCS risk analysis:

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/tds/analytics/potential-notices` | POST | Submit TDS analysis job |
| `/tds/analytics/potential-notices` | GET | Poll TDS job status |
| `/tds/analytics/potential-notices/search` | POST | Search TDS jobs |
| `/tcs/analytics/potential-notices` | POST | Submit TCS analysis job |
| `/tcs/analytics/potential-notices` | GET | Poll TCS job status |
| `/tcs/analytics/potential-notices/search` | POST | Search TCS jobs |

All endpoints support async job processing with polling for status and risk reports.
