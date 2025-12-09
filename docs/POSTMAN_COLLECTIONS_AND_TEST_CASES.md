# Postman Collections and Test Cases for Sandbox APIs

**Date:** December 9, 2025
**Source:** Sandbox Official Documentation

---

## Overview

Sandbox provides **public Postman collections** with comprehensive test cases and examples for every API endpoint. These collections are the official reference for understanding API behavior and testing integrations.

---

## What is the Sandbox Test Environment?

The Sandbox Test Environment is a **secure, isolated setup** that allows you to:

✅ Test and validate API integrations before going live
✅ Simulate complete workflows
✅ Verify API behavior without affecting real data
✅ Troubleshoot issues safely
✅ Validate request/response formats
✅ Load test your integration

**Benefits:**
- **Safe Validation** - No impact on live data
- **Customizable Testing** - Adapt test cases to your needs
- **Seamless Updates** - Fork collections to stay current
- **Confidence** - Build and test before production

---

## How to Access Postman Collections

### Step 1: Visit Sandbox API Public Workspace

Navigate to the **Sandbox API Public Workspace** (Postman link available at https://developer.sandbox.co.in)

### Step 2: Locate Your API Collection

In the workspace, find the collection for your API:
- **Calculator API** - TDS/TCS calculations
- **Reports API** - Form generation
- **Analytics API** - Compliance analysis
- **Compliance API** - Tax checks

### Step 3: Open the Collection

Click on the collection to expand it and see all available endpoints.

### Step 4: Review Examples

Each endpoint contains **Examples** that show:
- ✅ Required parameters
- ✅ Optional parameters
- ✅ Request headers
- ✅ Request body format
- ✅ Expected response structure
- ✅ Sample response data

---

## How to Use Test Cases

### Basic Workflow

```
1. Open Test Case
   └─ Select endpoint example

2. Construct Request
   └─ Review parameters and headers
   └─ Customize request body as needed

3. Send Request
   └─ Use Test Host URL (https://test-api.sandbox.co.in/{path})

4. Review Response
   └─ Check status code
   └─ Validate response format
   └─ Debug any errors

5. Iterate
   └─ Modify parameters
   └─ Test different scenarios
   └─ Document findings
```

### Detailed Steps

#### Step 1: Open a Test Case

```
Postman Workspace
└─ API Collection (e.g., TDS Reports)
   └─ Endpoint (e.g., POST /tds/reports/txt)
      └─ Examples
         └─ Test Case 1 (Select)
```

#### Step 2: Construct Request

Review the example to see:

```json
POST https://test-api.sandbox.co.in/tds/reports/txt

Headers:
- Authorization: Bearer {token}
- x-api-key: {api_key}
- x-api-version: 1.0
- Content-Type: application/json

Body:
{
  "@entity": "in.co.sandbox.tds.reports.request",
  "tan": "AHMA09719B",
  "quarter": "Q1",
  "form": "24Q",
  "financial_year": "FY 2024-25"
}
```

#### Step 3: Send Request

Click "Send" to send the request to the test environment:

```
POST https://test-api.sandbox.co.in/tds/reports/txt
```

#### Step 4: Review Response

Check the response:

```json
{
  "code": 200,
  "timestamp": 1708926739000,
  "transaction_id": "uuid",
  "data": {
    "job_id": "uuid",
    "status": "created",
    "created_at": 1708926739000,
    "json_url": "https://..."
  }
}
```

#### Step 5: Iterate and Test

- Modify parameters
- Test edge cases
- Test error scenarios
- Document findings

---

## Important: Fork the Collections

To ensure you're always using the **latest APIs and test cases**:

### How to Fork

1. **In Postman Workspace:**
   - Click "Fork" on the collection
   - Create your own copy
   - Name it (e.g., "My Company - Sandbox APIs")

2. **Benefits of Forking:**
   - ✅ Keep your own version
   - ✅ Customize test cases
   - ✅ Add company-specific parameters
   - ✅ Stay updated with latest changes
   - ✅ Share with team members

3. **Keep Updated:**
   - Watch the original collection for updates
   - Pull new changes into your fork
   - Review API changes before implementing

---

## Test Cases for Our Implementation

### Available Test Cases

Based on the Sandbox Collections, we have test cases for:

#### 1. Calculator API

```
Endpoint: POST /calculator/tds (sync)
Endpoint: POST /calculator/tcs (sync)
Endpoint: POST /calculator/salary-tds (async job)

Test Cases Include:
- Individual salary calculation
- Non-salary TDS calculation
- TCS calculation on goods
- Deductee details validation
- Pan validity checks
```

#### 2. Reports API

```
Endpoint: POST /tds/reports/txt
Endpoint: GET /tds/reports/txt?job_id={jobId}
Endpoint: POST /tds/reports/txt/search

Endpoint: POST /tcs/reports/txt
Endpoint: GET /tcs/reports/txt?job_id={jobId}
Endpoint: POST /tcs/reports/txt/search

Test Cases Include:
- Form 24Q generation
- Form 26Q generation
- Form 27Q generation
- Form 27EQ generation
- Job status polling
- Job history search
```

#### 3. Analytics API

```
Endpoint: POST /tds/analytics/potential-notices
Endpoint: GET /tds/analytics/jobs/{jobId}
Endpoint: POST /tds/analytics/jobs (search)

Endpoint: POST /tcs/analytics/potential-notices
Endpoint: GET /tcs/analytics/jobs/{jobId}
Endpoint: POST /tcs/analytics/jobs (search)

Test Cases Include:
- Compliance risk analysis
- Potential notice identification
- Non-filer detection
- Assessment prediction
```

#### 4. Compliance API

```
Endpoint: POST /compliance/validate-pan
Endpoint: POST /compliance/check-deductee
Endpoint: POST /compliance/validate-tan

Test Cases Include:
- PAN validation
- Deductee status checks
- TAN validation
- Section 206AB checks
- Section 206CCA checks
```

---

## Using Test Cases with Our Implementation

### Our Current Setup

```php
// SandboxTDSAPI.php automatically handles:
✅ Test environment: https://test-api.sandbox.co.in
✅ Production environment: https://api.sandbox.co.in
✅ Authentication with Bearer token
✅ x-api-key header
✅ Request/response parsing
```

### Testing Workflow

```
1. Use Postman Collection
   └─ Review test case for your API
   └─ Note parameters and headers

2. Map to SandboxTDSAPI Methods
   └─ Find corresponding PHP method
   └─ Review implementation

3. Test via Postman First
   └─ Send request to test environment
   └─ Verify response format
   └─ Document findings

4. Test via PHP Code
   └─ Use SandboxTDSAPI with test environment
   └─ Verify integration works
   └─ Handle responses correctly

5. Move to Production
   └─ Switch to production environment
   └─ Use production API keys
   └─ Monitor live traffic
```

---

## Environment URLs Reminder

### Test Environment
```
Host URL: https://test-api.sandbox.co.in
For:      Development & Testing
Status:   Safe, no real data impact
```

### Production Environment
```
Host URL: https://api.sandbox.co.in
For:      Live operations
Status:   Real data, live transactions
```

---

## Best Practices for Testing

### Before Moving to Production

✅ Test all endpoints in test environment
✅ Verify request/response formats
✅ Test error scenarios
✅ Load test with realistic volumes
✅ Document any API behavior quirks
✅ Ensure error handling is robust
✅ Validate authentication flow
✅ Check rate limiting behavior
✅ Test edge cases
✅ Verify data validation rules

### During Implementation

✅ Start with Postman collections
✅ Understand API behavior first
✅ Then implement in code
✅ Map Postman examples to code
✅ Verify each endpoint works
✅ Test integrations between endpoints

### Monitoring in Production

✅ Log all API calls
✅ Monitor response times
✅ Track error rates
✅ Alert on failures
✅ Monitor rate limiting
✅ Check data quality
✅ Review transaction logs

---

## Common Testing Scenarios

### Scenario 1: Submit TDS Report

**Postman Steps:**
1. Open POST /tds/reports/txt example
2. Fill in TAN, quarter, form, financial year
3. Send to test environment
4. Verify job_id in response

**Code Equivalent:**
```php
$api = new SandboxTDSAPI($firm_id, $pdo);
$result = $api->submitTDSReportsJob('AHMA09719B', 'Q1', '24Q', 'FY 2024-25');
// Returns: ['status' => 'success', 'job_id' => 'uuid', ...]
```

### Scenario 2: Poll Job Status

**Postman Steps:**
1. Open GET /tds/reports/txt?job_id={jobId} example
2. Use job_id from previous step
3. Send to test environment
4. Verify status changes from created → queued → processing → succeeded

**Code Equivalent:**
```php
$api = new SandboxTDSAPI($firm_id, $pdo);
$result = $api->pollTDSReportsJob($jobId);
// Returns: ['status' => 'succeeded', 'txt_url' => 'presigned_url', ...]
```

### Scenario 3: Handle Errors

**Postman Steps:**
1. Send request with invalid parameters
2. Observe error response
3. Note error code and message
4. Implement error handling

**Code Equivalent:**
```php
try {
    $result = $api->submitTDSReportsJob('INVALID', 'Q1', '24Q', 'FY 2024-25');
    if ($result['error']) {
        // Handle error response
        log_error($result['error']);
    }
} catch (Exception $e) {
    // Handle exception
    log_error($e->getMessage());
}
```

---

## Links & Resources

- **Sandbox Developer Portal:** https://developer.sandbox.co.in
- **Postman Collections:** Available in Sandbox API Public Workspace
- **Help Center:** https://help.sandbox.co.in
- **API References:** See `/docs/` folder

---

## Related Documentation

- [SANDBOX_HOST_URLS_AND_ENVIRONMENTS.md](SANDBOX_HOST_URLS_AND_ENVIRONMENTS.md) - Host URLs and environments
- [QUICK_REFERENCE_SANDBOX_URLS.md](QUICK_REFERENCE_SANDBOX_URLS.md) - Quick reference card
- [CALCULATOR_API_REFERENCE.md](CALCULATOR_API_REFERENCE.md) - Calculator API spec
- [REPORTS_API_REFERENCE.md](REPORTS_API_REFERENCE.md) - Reports API spec
- [SANDBOX_ANALYTICS_API_REFERENCE.md](SANDBOX_ANALYTICS_API_REFERENCE.md) - Analytics API spec
- [SANDBOX_COMPLIANCE_API_REFERENCE.md](SANDBOX_COMPLIANCE_API_REFERENCE.md) - Compliance API spec

---

## Summary

✅ Postman collections available for all APIs
✅ Test cases provided for every endpoint
✅ Test environment ready: https://test-api.sandbox.co.in
✅ Our implementation matches Postman specifications
✅ Use collections to understand APIs first
✅ Then test with our code implementation
✅ Finally move to production with confidence

---

**Last Updated:** December 9, 2025
**Status:** Documentation Complete
**Next Step:** Start testing with Postman collections
