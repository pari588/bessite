# Reports API - Complete Reference

**Date:** December 9, 2025
**Status:** ✅ Implementation Ready
**Source:** Sandbox.co.in Official Documentation

---

## Overview

The **Reports API** provides comprehensive TDS and TCS report generation services for tax filing:

- **TDS Reports** - Forms 24Q, 26Q, 27Q (Salary, Non-Salary, NRI Payments)
- **TCS Reports** - Form 27EQ (Tax Collected at Source)

This API handles asynchronous report generation with job-based processing.

---

## API Servers

| Environment | URL |
|---|---|
| **Production** | `https://api.sandbox.co.in` |
| **Testing/Sandbox** | `https://test-api.sandbox.co.in` |

---

## Authentication

All endpoints require three headers:

```
Authorization: Bearer <JWT Token>
x-api-key: <Your API Key>
x-api-version: 1.0 (optional)
Content-Type: application/json
```

---

## Endpoints Summary

| Method | Endpoint | Purpose | Mode |
|--------|----------|---------|------|
| `POST` | `/tds/reports/txt` | Create TDS Reports Job | Asynchronous |
| `GET` | `/tds/reports/txt` | Poll TDS Reports Job Status | Asynchronous |
| `POST` | `/tds/reports/txt/search` | Search TDS Reports Jobs | Synchronous |
| `POST` | `/tcs/reports/txt` | Create TCS Reports Job | Asynchronous |
| `GET` | `/tcs/reports/txt` | Poll TCS Reports Job Status | Asynchronous |
| `POST` | `/tcs/reports/txt/search` | Search TCS Reports Jobs | Synchronous |

---

## 1. TDS Reports - Create Job

**Endpoint:** `POST /tds/reports/txt`

Creates an asynchronous job to generate TDS report (TXT file) for forms 24Q, 26Q, or 27Q.

### Request Schema

```json
{
  "@entity": "in.co.sandbox.tds.reports.request",
  "tan": "AHMA09719B",
  "quarter": "Q1|Q2|Q3|Q4",
  "form": "24Q|26Q|27Q",
  "financial_year": "FY 2024-25",
  "previous_receipt_number": "123456789123456"
}
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `@entity` | string | Yes | Must be `"in.co.sandbox.tds.reports.request"` |
| `tan` | string | Yes | TAN identifier (e.g., AHMA09719B) - Format: [A-Z]{4}[0-9]{5}[A-Z]{1} |
| `quarter` | string | Yes | Quarter (Q1, Q2, Q3, Q4) |
| `form` | string | Yes | Form type (24Q - Salary, 26Q - Non-Salary, 27Q - NRI) |
| `financial_year` | string | Yes | Financial year (e.g., "FY 2024-25" or "FY 2023-24") |
| `previous_receipt_number` | string | No | Previous receipt number for incremental reporting |

### Response Schema (200 - Success)

```json
{
  "code": 200,
  "timestamp": 1708926739000,
  "transaction_id": "c01f847c-c42e-4577-9d01-a7208401a922",
  "data": {
    "@entity": "in.co.sandbox.tds.reports.job",
    "job_id": "c01f847c-c42e-4577-9d01-a7208401a922",
    "tan": "AHMA09719B",
    "quarter": "Q3",
    "form": "26Q",
    "financial_year": "FY 2023-24",
    "previous_receipt_number": "123456789012345",
    "status": "created",
    "created_at": 1708926739000,
    "json_url": "https://test-api.sandbox.co.in/tds/reports/..."
  }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `job_id` | string | Unique job identifier |
| `status` | string | Job status: created, queued, processing, succeeded, failed |
| `created_at` | number | Timestamp when job was created (milliseconds) |
| `json_url` | string | Presigned URL for uploading payload (use HTTP PUT) |

### Example Usage

```bash
curl -X POST https://test-api.sandbox.co.in/tds/reports/txt \
  -H "Authorization: Bearer your-token" \
  -H "x-api-key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "@entity": "in.co.sandbox.tds.reports.request",
    "tan": "AHMA09719B",
    "quarter": "Q3",
    "form": "26Q",
    "financial_year": "FY 2024-25"
  }'
```

---

## 2. TDS Reports - Poll Job Status

**Endpoint:** `GET /tds/reports/txt`

Check the status of a TDS reports job and get results when complete.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID from create job response |

### Response Schema - Job Queued/Processing

```json
{
  "code": 200,
  "data": {
    "@entity": "in.co.sandbox.tds.reports.job",
    "job_id": "48c70ad8-f5df-4dd7-8aff-a71d48992336",
    "tan": "AHMA09719B",
    "quarter": "Q4",
    "form": "26Q",
    "financial_year": "FY 2023-24",
    "status": "queued",
    "created_at": 1701250588000,
    "updated_at": 1701250575000
  }
}
```

### Response Schema - Job Succeeded

```json
{
  "code": 200,
  "data": {
    "@entity": "in.co.sandbox.tds.reports.job",
    "job_id": "c01f847c-c42e-4577-9d01-a7208401a922",
    "tan": "AHMA09719B",
    "quarter": "Q4",
    "form": "26Q",
    "financial_year": "FY 2023-24",
    "status": "succeeded",
    "created_at": 1701250588000,
    "updated_at": 1701250605000,
    "txt_url": "https://in-co-sandbox-tds-reports-test.s3.ap-south-1.amazonaws.com/..."
  }
}
```

### Response Schema - Job Failed

```json
{
  "code": 200,
  "data": {
    "@entity": "in.co.sandbox.tds.reports.job",
    "job_id": "77a45361-9646-4ee0-93ed-9471bb8a615e",
    "tan": "BLRC23456F",
    "quarter": "Q4",
    "form": "26Q",
    "financial_year": "FY 2023-24",
    "status": "failed",
    "created_at": 1701250588000,
    "updated_at": 1701250605000,
    "validation_report_url": "https://in-co-sandbox-tds-reports-test.s3.ap-south-1.amazonaws.com/..."
  }
}
```

### Status Values

| Status | Meaning |
|--------|---------|
| `created` | Job created, awaiting payload upload |
| `queued` | In processing queue |
| `processing` | Currently generating report |
| `succeeded` | Complete with txt_url available |
| `failed` | Error during processing with validation_report_url |

### Example Usage

```bash
curl -X GET "https://test-api.sandbox.co.in/tds/reports/txt?job_id=c01f847c-c42e-4577-9d01-a7208401a922" \
  -H "Authorization: Bearer your-token" \
  -H "x-api-key: your-api-key"
```

---

## 3. TDS Reports - Search Jobs

**Endpoint:** `POST /tds/reports/txt/search`

Search and retrieve historical TDS reports jobs with pagination.

### Request Schema

```json
{
  "@entity": "in.co.sandbox.tds.reports.jobs.search",
  "tan": "AHMA09719B",
  "quarter": "Q1|Q2|Q3|Q4",
  "form": "24Q|26Q|27Q",
  "financial_year": "FY 2024-25",
  "from_date": 1714529043000,
  "to_date": 1714530043000,
  "page_size": 50,
  "last_evaluated_key": "eydadadadada...=="
}
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `@entity` | string | Yes | Must be `"in.co.sandbox.tds.reports.jobs.search"` |
| `tan` | string | Yes | TAN identifier |
| `quarter` | string | Yes | Quarter (Q1-Q4) |
| `form` | string | Yes | Form type (24Q, 26Q, 27Q) |
| `financial_year` | string | Yes | Financial year |
| `from_date` | number | No | Start date (milliseconds EPOCH) |
| `to_date` | number | No | End date (milliseconds EPOCH) |
| `page_size` | number | No | Number of records (max 50) |
| `last_evaluated_key` | string | No | Pagination marker from previous response |

### Response Schema

```json
{
  "code": 200,
  "data": {
    "@entity": "in.co.sandbox.tds.reports.paginated_list",
    "items": [
      {
        "@entity": "in.co.sandbox.tds.reports.job",
        "job_id": "096c4812-1829-4ee1-a3c6-3bd291654b72",
        "tan": "AHMS12345C",
        "financial_year": "FY 2023-24",
        "quarter": "Q2",
        "form": "26Q",
        "previous_receipt_number": "1234567890123",
        "created_at": 1714529043000,
        "updated_at": 1714529044000,
        "status": "succeeded"
      }
    ],
    "last_evaluated_key": "eydadadadada...=="
  }
}
```

---

## 4. TCS Reports - Create Job

**Endpoint:** `POST /tcs/reports/txt`

Creates an asynchronous job to generate TCS report (TXT file) for Form 27EQ.

### Request Schema

```json
{
  "@entity": "in.co.sandbox.tcs.reports.request",
  "tan": "AHMA09719B",
  "quarter": "Q1|Q2|Q3|Q4",
  "financial_year": "FY 2024-25",
  "previous_receipt_number": "123456789123456"
}
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `@entity` | string | Yes | Must be `"in.co.sandbox.tcs.reports.request"` |
| `tan` | string | Yes | TAN identifier |
| `quarter` | string | Yes | Quarter (Q1, Q2, Q3, Q4) |
| `financial_year` | string | Yes | Financial year (e.g., "FY 2024-25") |
| `previous_receipt_number` | string | No | Previous receipt number (optional) |

**Note:** TCS does NOT have a "form" field. Only TAN, quarter, and financial_year are required.

### Response Schema (200 - Success)

```json
{
  "code": 200,
  "timestamp": 1763362637000,
  "transaction_id": "e2b9145f-69d5-4bbe-a6de-be6fc08b426f",
  "data": {
    "@entity": "in.co.sandbox.tcs.reports.job",
    "job_id": "46d96540-e4e0-4188-81f5-959f4732490f",
    "tan": "AHMA09719B",
    "quarter": "Q3",
    "financial_year": "FY 2023-24",
    "previous_receipt_number": "123456789012345",
    "status": "created",
    "created_at": 1763102487000,
    "json_url": "https://test-api.sandbox.co.in/tcs/reports/..."
  }
}
```

### Example Usage

```bash
curl -X POST https://test-api.sandbox.co.in/tcs/reports/txt \
  -H "Authorization: Bearer your-token" \
  -H "x-api-key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "@entity": "in.co.sandbox.tcs.reports.request",
    "tan": "AHMA09719B",
    "quarter": "Q3",
    "financial_year": "FY 2024-25"
  }'
```

---

## 5. TCS Reports - Poll Job Status

**Endpoint:** `GET /tcs/reports/txt`

Check the status of a TCS reports job.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `job_id` | string | Yes | Job ID from create job response |

### Response Schema

Same structure as TDS Reports (Queued, Succeeded, or Failed states).

---

## 6. TCS Reports - Search Jobs

**Endpoint:** `POST /tcs/reports/txt/search`

Search and retrieve historical TCS reports jobs.

### Request Schema

```json
{
  "@entity": "in.co.sandbox.tcs.reports.jobs.search",
  "tan": "AHMA09719B",
  "quarter": "Q1|Q2|Q3|Q4",
  "financial_year": "FY 2024-25",
  "from_date": 1714529043000,
  "to_date": 1714530043000,
  "page_size": 50,
  "last_evaluated_key": "eydadadadada...=="
}
```

**Note:** TCS search does NOT include a "form" field (since TCS is always Form 27EQ).

---

## HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| `200` | OK | Request successful |
| `400` | Bad Request | Invalid request format |
| `422` | Unprocessable Entity | Validation errors (invalid TAN, form, etc.) |
| `500` | Server Error | API error |
| `521` | Data Not Found | Job or data not found |

---

## Error Response Format

```json
{
  "code": 422,
  "timestamp": 1708926739000,
  "message": "Validation errors: Invalid TAN format"
}
```

---

## Common Error Scenarios

### Invalid TAN Format
```json
{
  "code": 422,
  "message": "Invalid TAN. Expected format: [A-Z]{4}[0-9]{5}[A-Z]{1}"
}
```

### Invalid Form Type
```json
{
  "code": 422,
  "message": "Invalid form. Must be one of: 24Q, 26Q, 27Q"
}
```

### Invalid Financial Year
```json
{
  "code": 422,
  "message": "Invalid financial_year format. Expected: 'FY YYYY-YY'"
}
```

### Job Not Found
```json
{
  "code": 521,
  "message": "Job not found"
}
```

---

## Form Types Reference

### TDS Forms (3 forms)

| Form | Description | Type |
|------|-------------|------|
| **24Q** | Salary TDS | Quarterly |
| **26Q** | Non-Salary TDS | Quarterly |
| **27Q** | NRI Payments TDS | Quarterly |

### TCS Forms (1 form)

| Form | Description | Type |
|------|-------------|------|
| **27EQ** | Tax Collected at Source | Quarterly |

---

## Workflow Examples

### TDS Report Generation Workflow

```
1. Call POST /tds/reports/txt
   ├─ Returns: job_id, status="created"
   └─ Also returns: json_url (presigned S3 URL)

2. Upload form data to presigned URL (HTTP PUT)
   └─ Include TXT content in request body

3. Poll GET /tds/reports/txt?job_id=...
   ├─ Status transitions: created → queued → processing
   └─ When status="succeeded": txt_url is available

4. Download report from txt_url
   └─ Contains generated TDS report in TXT format

5. If failed: Download validation_report_url
   └─ Contains error details for troubleshooting
```

### TCS Report Generation Workflow

```
1. Call POST /tcs/reports/txt
   ├─ Returns: job_id, status="created"
   └─ Also returns: json_url (presigned S3 URL)

2. Upload form data to presigned URL (HTTP PUT)
   └─ Include TCS transaction data

3. Poll GET /tcs/reports/txt?job_id=...
   ├─ Check job progress
   └─ When status="succeeded": txt_url is available

4. Download report from txt_url
   └─ TCS report in TXT format ready
```

---

## Best Practices

✅ **DO:**
- Use job_id from response for all subsequent polling
- Poll status at regular intervals (5-10 second backoff)
- Cache successful reports by job_id
- Implement error retry logic
- Log all report job submissions
- Monitor job completion times

❌ **DON'T:**
- Submit duplicate jobs without checking existing status
- Store TAN information insecurely
- Forget to handle job failure responses
- Skip validation report when job fails
- Poll too frequently (causes API throttling)
- Assume job will complete immediately

---

## Performance Metrics

| Operation | Expected Time |
|-----------|---|
| Create Job | 200-500ms |
| Poll Status (while processing) | 100-200ms |
| Job Processing Time | 5-30 seconds (varies) |
| Search Jobs | 500-2000ms |
| Download Report | Depends on file size |

---

## Response Structure (All Endpoints)

Every response follows this structure:

```json
{
  "code": 200,
  "timestamp": 1708926739000,
  "transaction_id": "unique-id",
  "data": {
    // Endpoint-specific data
  },
  "message": "Optional error message"
}
```

---

## Implementation Notes

### TAN Format Validation
- Pattern: `[A-Z]{4}[0-9]{5}[A-Z]{1}`
- Example: `AHMA09719B`
- Length: Exactly 10 characters

### Financial Year Format
- Pattern: `FY YYYY-YY`
- Example: `FY 2024-25`
- Must include "FY" prefix

### Quarter Values
- `Q1` = April-June
- `Q2` = July-September
- `Q3` = October-December
- `Q4` = January-March

### Date Format
All dates in **milliseconds since EPOCH** (Unix timestamp × 1000)

### Pagination
- Default page_size: 10
- Maximum page_size: 50
- Use `last_evaluated_key` from previous response to get next page

---

## Status: ✅ Ready for Implementation

All endpoints documented and ready to integrate into the application.
