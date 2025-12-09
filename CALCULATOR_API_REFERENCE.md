# Calculator API - Complete Reference

**Date:** December 9, 2025
**Status:** ✅ Implementation Ready
**Source:** Sandbox.co.in Official Documentation

---

## Overview

The **Calculator API** provides comprehensive tax calculation services for:
- **TDS (Tax Deducted at Source)** - Salary and Non-Salary payments
- **TCS (Tax Collected at Source)** - Various transaction types

This API handles complex Indian tax calculations including exemptions, deductions, and thresholds.

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
x-api-key: <Your API Key>
Authorization: Bearer <JWT Token>
x-api-version: 1.0 (optional)
Content-Type: application/json
```

---

## Endpoints Summary

| Method | Endpoint | Purpose | Mode |
|--------|----------|---------|------|
| `POST` | `/tds/calculator/non-salary` | Calculate TDS on non-salary payments | Synchronous |
| `POST` | `/tcs/calculator` | Calculate TCS on transactions | Synchronous |
| `POST` | `/tds/calculator/salary` | Submit bulk salary TDS job | Asynchronous |
| `GET` | `/tds/calculator/salary` | Poll salary TDS job status | Asynchronous |
| `POST` | `/tds/calculator/salary/sync` | Calculate TDS on salary (immediate) | Synchronous |

---

## 1. TDS Non-Salary Calculator

**Endpoint:** `POST /tds/calculator/non-salary`

Calculates TDS on non-salary payments including:
- Contract services fees
- Interest payments
- Winnings from lottery/games
- Professional fees
- Rental payments
- And more...

### Request Schema

```json
{
  "@entity": "in.co.sandbox.tds.calculator.non_salary.request",
  "deductee_type": "individual|huf|company|firm|trust|local_authority|body_of_individuals",
  "is_pan_available": true,
  "residential_status": "resident|non_resident",
  "is_206ab_applicable": false,
  "is_pan_operative": true,
  "nature_of_payment": "fees|interest|winnings|rent|...",
  "credit_amount": 100000,
  "credit_date": 1732300800000
}
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `@entity` | string | Yes | Must be `"in.co.sandbox.tds.calculator.non_salary.request"` |
| `deductee_type` | string | Yes | Type of entity receiving payment |
| `is_pan_available` | boolean | Yes | Is PAN available? |
| `residential_status` | string | Yes | "resident" or "non_resident" |
| `is_206ab_applicable` | boolean | Yes | Section 206AB applicability (for Non-Resident) |
| `is_pan_operative` | boolean | Yes | Is PAN operative (active)? |
| `nature_of_payment` | string | Yes | Type of payment (affects TDS rate) |
| `credit_amount` | number | Yes | Payment amount in INR |
| `credit_date` | number | Yes | Payment date in milliseconds (EPOCH) |

### Response Schema

```json
{
  "transaction_id": "uuid-here",
  "code": 200,
  "timestamp": 1732300800000,
  "data": {
    "deduction_rate": 10.0,
    "deduction_amount": 10000,
    "section": "194C",
    "threshold": 30000,
    "due_date": 1732300800000,
    "pan_status": "operative|not_available|invalid"
  }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `deduction_rate` | number | TDS rate (percentage) |
| `deduction_amount` | number | Calculated TDS amount |
| `section` | string | Applicable Income Tax section |
| `threshold` | number | Amount threshold for TDS applicability |
| `due_date` | number | TDS deposit due date (EPOCH) |
| `pan_status` | string | Status of PAN validation |

### Example Usage

```bash
curl -X POST https://test-api.sandbox.co.in/tds/calculator/non-salary \
  -H "x-api-key: your-api-key" \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -d '{
    "@entity": "in.co.sandbox.tds.calculator.non_salary.request",
    "deductee_type": "individual",
    "is_pan_available": true,
    "residential_status": "resident",
    "is_206ab_applicable": false,
    "is_pan_operative": true,
    "nature_of_payment": "fees",
    "credit_amount": 100000,
    "credit_date": 1732300800000
  }'
```

---

## 2. TCS Calculator

**Endpoint:** `POST /tcs/calculator`

Calculates TCS on various transactions:
- Sales of goods
- Service provisions
- Material transactions
- Scrap sales
- And more...

### Request Schema

```json
{
  "@entity": "in.co.sandbox.tcs.calculator.request",
  "collectee_type": "individual|huf|company|firm|trust|...",
  "is_pan_available": true,
  "residential_status": "resident|non_resident",
  "is_206cca_applicable": false,
  "is_pan_operative": true,
  "nature_of_payment": "goods|services|material|...",
  "payment_amount": 1000000,
  "payment_date": 1732300800000
}
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `@entity` | string | Yes | Must be `"in.co.sandbox.tcs.calculator.request"` |
| `collectee_type` | string | Yes | Type of entity making payment |
| `is_pan_available` | boolean | Yes | Is PAN available? |
| `residential_status` | string | Yes | "resident" or "non_resident" |
| `is_206cca_applicable` | boolean | Yes | Section 206CCA applicability |
| `is_pan_operative` | boolean | Yes | Is PAN operative? |
| `nature_of_payment` | string | Yes | Type of transaction |
| `payment_amount` | number | Yes | Transaction amount in INR (including GST) |
| `payment_date` | number | Yes | Transaction date in milliseconds (EPOCH) |

### Response Schema

```json
{
  "transaction_id": "uuid-here",
  "code": 200,
  "timestamp": 1732300800000,
  "data": {
    "collection_rate": 0.75,
    "collection_amount": 7500,
    "section": "206C(1)",
    "threshold": 5000000,
    "due_date": 1732300800000,
    "pan_status": "operative"
  }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `collection_rate` | number | TCS rate (percentage) |
| `collection_amount` | number | Calculated TCS amount |
| `section` | string | Applicable section |
| `threshold` | number | Amount threshold for TCS |
| `due_date` | number | TCS deposit due date |
| `pan_status` | string | PAN validation status |

---

## 3. TDS Salary Calculator - Asynchronous

### Submit Job

**Endpoint:** `POST /tds/calculator/salary`

Submit bulk salary data for TDS calculation.

**Query Parameters:**
- `financial_year` (required): Financial year (e.g., "2024-25")

### Request Schema

```json
{
  "@entity": "in.co.sandbox.tds.calculator.salary.request",
  "employees": [
    {
      "employee_id": "EMP001",
      "pan": "AAAPA1234K",
      "gross_salary": 500000,
      "month": "April"
    },
    {
      "employee_id": "EMP002",
      "pan": "BBPB1234K",
      "gross_salary": 750000,
      "month": "April"
    }
  ]
}
```

### Response Schema

```json
{
  "transaction_id": "uuid",
  "code": 201,
  "timestamp": 1732300800000,
  "data": {
    "job_id": "job-uuid",
    "status": "created",
    "financial_year": "FY 2024-25"
  }
}
```

---

### Poll Job Status

**Endpoint:** `GET /tds/calculator/salary?job_id=<job_id>&financial_year=<fy>`

Check the status of a submitted TDS calculation job.

### Response Schema (Processing)

```json
{
  "transaction_id": "uuid",
  "code": 200,
  "timestamp": 1732300800000,
  "data": {
    "job_id": "job-uuid",
    "status": "processing",
    "financial_year": "FY 2024-25"
  }
}
```

### Response Schema (Complete)

```json
{
  "transaction_id": "uuid",
  "code": 200,
  "timestamp": 1732300800000,
  "data": {
    "job_id": "job-uuid",
    "status": "succeeded",
    "financial_year": "FY 2024-25",
    "workbook_url": "https://s3.../workbook.xlsx",
    "record_count": 2
  }
}
```

### Status Values

| Status | Meaning |
|--------|---------|
| `created` | Job created, awaiting processing |
| `queued` | In processing queue |
| `processing` | Currently calculating |
| `succeeded` | Complete with results |
| `failed` | Error during processing |

---

## 4. TDS Salary Calculator - Synchronous

**Endpoint:** `POST /tds/calculator/salary/sync`

Calculate TDS on salary immediately (no job submission).

**Query Parameters:**
- `financial_year` (required): Financial year

### Request Schema

```json
{
  "@entity": "in.co.sandbox.tds.calculator.salary.sync.request",
  "employees": [
    {
      "employee_id": "EMP001",
      "pan": "AAAPA1234K",
      "salary_details": {
        "basic": 25000,
        "da": 5000,
        "hra": 10000,
        "bonus": 5000
      },
      "deductions": {
        "pf": 1800,
        "insurance": 500
      }
    }
  ]
}
```

### Response Schema

```json
{
  "transaction_id": "uuid",
  "code": 200,
  "timestamp": 1732300800000,
  "data": {
    "workbook_data": "base64-encoded-excel-file",
    "record_count": 1,
    "financial_year": "FY 2024-25"
  }
}
```

Response contains base64-encoded Excel workbook with:
- Employee salary details
- Deduction heads (80C, 80D, 80G, etc.)
- Tax calculations
- Net tax payable

---

## HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| `200` | OK | Successful calculation |
| `201` | Created | Job submitted successfully |
| `400` | Bad Request | Invalid request format |
| `422` | Unprocessable Entity | Validation errors (missing/invalid fields) |
| `500` | Server Error | Reference data unavailable |

---

## Error Response Format

```json
{
  "transaction_id": "uuid",
  "code": 422,
  "timestamp": 1732300800000,
  "message": "Validation errors: credit_amount is required"
}
```

---

## Nature of Payment Codes

### For Non-Salary TDS

- `fees` - Professional/technical fees
- `interest` - Interest payments
- `rent` - Rental payments
- `winnings` - Lottery/game winnings
- `commissions` - Commission payments
- `contractor_payments` - Contractor payments
- `royalties` - Royalty payments
- And more...

### For TCS

- `goods` - Sale of goods
- `services` - Service provision
- `material` - Material transactions
- `scrap` - Scrap sales
- `e_commerce` - E-commerce transactions
- And more...

---

## Residential Status

- `resident` - Individual resident in India for tax year
- `non_resident` - Individual not resident for tax year

---

## Entity Types

- `individual` - Individual person
- `huf` - Hindu Undivided Family
- `company` - Company/Corporation
- `firm` - Partnership firm
- `trust` - Trust entity
- `local_authority` - Local authority
- `body_of_individuals` - Association of individuals

---

## Important Sections

### TDS Sections

- **194C** - Payments to contractors
- **195** - Payments to non-residents
- **206AB** - Specified persons TDS
- **194BA** - Horse race winnings
- **194LC** - Payments on insurance

### TCS Sections

- **206C(1)** - Sale of goods over threshold
- **206C(1F)** - E-commerce related
- **206C(1G)** - Highway cess
- **206C(1H)** - Specified goods
- **206CCA** - High-value transactions

---

## Implementation Notes

### Date Format
All dates must be in **milliseconds since EPOCH** (Unix timestamp × 1000)

Example:
- `1732300800000` = 2024-12-23 00:00:00 UTC

### Amount Format
All amounts are in **INR** (Indian Rupees) as integers or decimals

### PAN Format
PAN must be 10 characters: 5 letters + 4 digits + 1 letter

Example: `AAAPA1234K`

### Financial Year Format
- "2024-25" (Assessment Year 2024-25, Financial Year 2023-24)
- Always in "YYYY-YY" format

### Thresholds

**Non-Salary TDS:**
- Varies by section and nature of payment
- Typically 30,000 INR for contractor payments

**TCS:**
- Varies by section
- Typically 5,000,000 INR for goods sales

---

## Response Structure (All Endpoints)

Every response follows this structure:

```json
{
  "transaction_id": "Unique ID for this API call",
  "code": 200,
  "timestamp": 1732300800000,
  "data": {
    "calculation_results": "Varies by endpoint"
  },
  "message": "Error message if applicable"
}
```

---

## Common Error Scenarios

### Invalid PAN
```json
{
  "code": 422,
  "message": "Invalid PAN format: must be 10 characters"
}
```

### Missing Required Field
```json
{
  "code": 400,
  "message": "Missing required field: credit_amount"
}
```

### Invalid Entity Type
```json
{
  "code": 422,
  "message": "Invalid deductee_type: must be one of [individual, huf, company, ...]"
}
```

### Insufficient Funds
```json
{
  "code": 422,
  "message": "Calculation not possible: amount below minimum threshold"
}
```

---

## Workflow Examples

### TDS Non-Salary Calculation

```
1. Collect payment details from user
   - Amount, date, nature of payment
   - Recipient details (PAN, type)

2. Call POST /tds/calculator/non-salary

3. Receive:
   - TDS rate and amount
   - Applicable section
   - Due date for deposit

4. Display to user
   - Show calculation breakdown
   - Provide compliance info
```

### Salary TDS - Asynchronous Processing

```
1. Collect salary data for multiple employees

2. Submit via POST /tds/calculator/salary
   - Receive job_id

3. Poll GET /tds/calculator/salary with job_id
   - Check status periodically
   - Statuses: created → queued → processing → succeeded

4. When succeeded:
   - Download workbook via workbook_url
   - Contains detailed tax calculations
```

### Salary TDS - Synchronous Calculation

```
1. Collect employee salary details

2. Submit via POST /tds/calculator/salary/sync

3. Receive Excel workbook immediately
   - No job submission
   - Results in response
```

---

## Best Practices

✅ **DO:**
- Cache reference data (sections, thresholds) locally
- Validate dates are in EPOCH format
- Handle async jobs with proper polling intervals
- Implement retry logic for transient failures
- Log all calculator calls for audit

❌ **DON'T:**
- Hardcode tax rates (use API for current rates)
- Skip PAN validation
- Forget to handle all error codes
- Submit duplicate jobs without checking status
- Store sensitive calculation data insecurely

---

## Performance Tips

- Batch salary calculations (up to 1000 employees per job)
- Use async endpoints for large datasets
- Poll with exponential backoff (wait 1s, 2s, 4s, etc.)
- Cache successful calculations by parameters
- Implement circuit breaker for API failures

---

## Status: ✅ Ready for Implementation

All endpoints documented and ready to integrate into the application.
