# Analytics API - Complete Usage Guide

**Last Updated:** December 9, 2025
**Status:** ✅ Production Ready

---

## Quick Start

### Accessing the Analytics Page

Navigate to: `http://localhost/tds/admin/analytics.php`

You'll see:
- **Financial Year Dropdown** - Select FY (Last 5 to Next 2 years)
- **Quarter Dropdown** - Select Q1, Q2, Q3, or Q4
- **Two Tabs:** TDS Analytics | TCS Analytics

---

## Analytics Features

### 1. Submit Forms for Analysis

#### TDS Forms (Tab: TDS Analytics)
Submit Forms 24Q, 26Q, or 27Q for risk analysis:
- **24Q:** Salary TDS form
- **26Q:** Non-Salary TDS form
- **27Q:** NRI TDS form

Steps:
1. Click on "TDS Analytics" tab
2. Select Financial Year from dropdown
3. Select Quarter from dropdown
4. Choose form type (24Q, 26Q, or 27Q)
5. Paste form content (XML or JSON)
6. Click "🚀 Submit for Analysis"

#### TCS Forms (Tab: TCS Analytics)
Submit Form 27EQ for Tax Collected at Source analysis:

Steps:
1. Click on "TCS Analytics" tab
2. Select Financial Year from dropdown
3. Select Quarter from dropdown
4. Paste Form 27EQ content (XML or JSON)
5. Click "🚀 Submit for Analysis"

### 2. Check Job Status

After submission, you'll receive a **Job ID**. Use it to check status:

1. Copy the Job ID from the success message
2. Go to "Check Job Status" panel
3. Paste Job ID in the input field
4. Click "⏱️ Check Status"
5. View results when job completes

---

## Understanding Results

### Risk Score & Level

When a job completes, you'll see:

**Risk Score:** 0-100 scale
- **0-33:** ✅ LOW Risk (compliant)
- **34-66:** ⚠️ MEDIUM Risk (review recommended)
- **67-100:** ❌ HIGH Risk (remediation needed)

**Risk Level:** Categorical assessment
- **LOW:** Form is compliant, minimal issues
- **MEDIUM:** Some compliance gaps, suggestions provided
- **HIGH:** Significant issues found, immediate action needed

### Potential Notices

The analysis identifies potential tax notices that might be issued:

**potential_notices_count:** Total number of issues detected

Each issue includes:
- **Code:** Issue identifier (e.g., "206AB_001")
- **Description:** Explanation of the issue
- **Severity:** LOW / MEDIUM / HIGH

Example issues:
```
- 206AB_001: Specified person check failed (HIGH)
- SALARY_001: Salary amount mismatch with Form 16 (MEDIUM)
- PATTERN_001: Unusual deduction pattern detected (LOW)
```

### Report URL

Download the complete analysis report:
- Full issue breakdown
- Remediation suggestions
- Detailed risk assessment
- Compliance recommendations

---

## API Endpoints

### 1. Submit TDS Job

**Endpoint:** `POST /tds/api/submit_analytics_job_tds.php`

**Request:**
```json
{
  "tan": "AHMA09719B",
  "quarter": "Q1",
  "form": "26Q",
  "fy": "FY 2024-25",
  "form_content": "<?xml version='1.0'?><Form26Q>...</Form26Q>"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "job_id": "job-12345-uuid",
  "tan": "AHMA09719B",
  "quarter": "Q1",
  "form": "26Q",
  "financial_year": "FY 2024-25",
  "status": "created",
  "created_at": "2025-12-09T20:15:00Z",
  "message": "Analytics job submitted successfully. Job ID: job-12345-uuid"
}
```

### 2. Submit TCS Job

**Endpoint:** `POST /tds/api/submit_analytics_job_tcs.php`

**Request:**
```json
{
  "tan": "AHMA09719B",
  "quarter": "Q1",
  "fy": "FY 2024-25",
  "form_content": "<?xml version='1.0'?><Form27EQ>...</Form27EQ>"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "job_id": "tcs-job-uuid",
  "status": "created",
  "message": "Analytics job submitted successfully. Job ID: tcs-job-uuid"
}
```

### 3. Poll Job Status

**Endpoint:** `GET /tds/api/poll_analytics_job.php?job_id=<job_id>&type=<tds|tcs>`

**Request Parameters:**
- `job_id` (required): Job UUID from submission
- `type` (optional): "tds" or "tcs" (defaults to "tds")

**Response (200 OK - Job Processing):**
```json
{
  "success": true,
  "job_id": "job-uuid",
  "type": "tds",
  "status": "processing",
  "risk_level": null,
  "risk_score": null,
  "potential_notices_count": null,
  "issues": [],
  "error": null
}
```

**Response (200 OK - Job Complete):**
```json
{
  "success": true,
  "job_id": "job-uuid",
  "type": "tds",
  "status": "succeeded",
  "risk_level": "MEDIUM",
  "risk_score": 65,
  "potential_notices_count": 3,
  "report_url": "https://s3.example.com/reports/job-uuid.json",
  "issues": [
    {
      "code": "206AB_001",
      "description": "Specified person check failed",
      "severity": "HIGH"
    },
    {
      "code": "SALARY_001",
      "description": "Salary amount mismatch",
      "severity": "MEDIUM"
    }
  ],
  "error": null
}
```

### 4. Fetch Job History

**Endpoint:** `POST /tds/api/fetch_analytics_jobs.php`

**Request:**
```json
{
  "type": "tds",
  "tan": "AHMA09719B",
  "quarter": "Q1",
  "fy": "FY 2024-25",
  "form": "26Q",
  "page_size": 50,
  "last_evaluated_key": null
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "type": "tds",
  "count": 25,
  "jobs": [
    {
      "job_id": "job-uuid-1",
      "form": "26Q",
      "status": "succeeded",
      "risk_score": 65,
      "created_at": "2025-12-09T20:15:00Z"
    },
    {
      "job_id": "job-uuid-2",
      "form": "26Q",
      "status": "processing",
      "risk_score": null,
      "created_at": "2025-12-09T20:10:00Z"
    }
  ],
  "has_more": false,
  "last_evaluated_key": null
}
```

---

## Job Status Lifecycle

```
┌─────────┐
│ created │ ← Submitted, awaiting processing
└────┬────┘
     │
     ▼
┌─────────┐
│ queued  │ ← In processing queue
└────┬────┘
     │
     ▼
┌──────────────┐
│ processing   │ ← Currently analyzing
└────┬─────────┘
     │
     ├─────────────────────────┐
     │                         │
     ▼                         ▼
┌──────────┐            ┌────────┐
│succeeded │ ← Complete │ failed │ ← Error occurred
└──────────┘            └────────┘
```

**Polling Recommendations:**
- Check status every 10-30 seconds initially
- For jobs taking longer, increase interval to 1-5 minutes
- Processing typically takes 30 minutes to 2 hours

---

## Form Content Formats

### TDS Forms (24Q, 26Q, 27Q)

Supported formats:
- **XML:** Standard Form structure in XML format
- **JSON:** Form data in JSON format

Example XML:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<Form26Q>
  <Header>
    <TAN>AHMA09719B</TAN>
    <AY>2024-25</AY>
  </Header>
  <FormData>
    <!-- Form content here -->
  </FormData>
</Form26Q>
```

### TCS Forms (27EQ)

Supported formats:
- **XML:** Standard Form 27EQ structure
- **JSON:** Form data in JSON format

Example XML:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<Form27EQ>
  <Header>
    <TAN>AHMA09719B</TAN>
    <AY>2024-25</AY>
  </Header>
  <TCSData>
    <!-- TCS collection details -->
  </TCSData>
</Form27EQ>
```

---

## Error Handling

### Common Errors & Solutions

#### 1. Invalid TAN Format
**Error:** "Invalid TAN format. Expected: XXXXXNXXXXX (e.g., AHMA09719B)"

**Solution:** TAN must be:
- 4 uppercase letters
- Followed by 5 digits
- Followed by 1 uppercase letter
- Example: AHMA09719B

#### 2. Invalid Quarter
**Error:** "Invalid quarter. Format: Q1, Q2, Q3, Q4"

**Solution:** Use valid quarter values:
- Q1 (April-June)
- Q2 (July-September)
- Q3 (October-December)
- Q4 (January-March)

#### 3. Invalid FY Format
**Error:** "Invalid FY format. Expected: FY YYYY-YY (e.g., FY 2024-25)"

**Solution:** Financial year must be:
- Start with "FY " (with space)
- Include 4-digit year
- Hyphen
- 2-digit year
- Example: FY 2024-25

#### 4. Empty Form Content
**Error:** "Form content is required"

**Solution:**
- Paste actual form XML or JSON content
- Don't leave textarea empty
- Minimum required content

#### 5. Invalid Form Type
**Error:** "Invalid form type. Allowed: 24Q, 26Q, 27Q"

**Solution:** For TDS, use only:
- 24Q (Salary TDS)
- 26Q (Non-Salary TDS)
- 27Q (NRI TDS)

Note: TCS always uses 27EQ

---

## Compliance Integration

### Workflow: Analytics → Compliance

1. **Analyze Form**
   - Use Analytics API to check risk
   - Review potential notices
   - Identify compliance issues

2. **Remediate Issues** (if needed)
   - Correct form data
   - Address compliance gaps
   - Re-submit for analysis

3. **Proceed to Compliance**
   - When risk is LOW or acceptable
   - Navigate to Compliance page
   - Generate FVU
   - E-file with tax authority

### Risk-Based Decisions

- **LOW Risk:** Proceed directly to FVU generation
- **MEDIUM Risk:** Review suggestions, make corrections if needed
- **HIGH Risk:** Delay filing, address all issues first

---

## Performance & Limits

### Request Limits
- Form content: Up to 10 MB
- Rate limit: 100 requests per minute
- Concurrent jobs: No limit

### Processing Time
- Average: 30 minutes to 2 hours
- Max: 24 hours
- Depends on: Form complexity, form size, server load

### Response Times
- Submit: < 1 second
- Poll: < 1 second
- Fetch history: < 2 seconds

---

## Best Practices

### 1. Form Preparation
- ✅ Validate XML/JSON syntax before submission
- ✅ Ensure all required fields are populated
- ✅ Use consistent date formats
- ❌ Don't submit incomplete forms

### 2. Job Monitoring
- ✅ Store job IDs in logs for reference
- ✅ Implement retry logic for failed jobs
- ✅ Set appropriate polling intervals
- ❌ Don't poll constantly (use exponential backoff)

### 3. Error Handling
- ✅ Catch and log API exceptions
- ✅ Display user-friendly error messages
- ✅ Implement fallback workflows
- ❌ Don't retry failed jobs immediately

### 4. Data Security
- ✅ Use HTTPS for all API calls
- ✅ Store credentials securely (environment variables)
- ✅ Never log sensitive form content
- ❌ Don't expose job IDs in URLs

---

## Troubleshooting

### Issue: "Check Status" button shows error

**Diagnosis:**
1. Verify SANDBOX_API_KEY and SANDBOX_API_SECRET are configured
2. Check if job ID is correct
3. Verify job hasn't expired (jobs valid for 7 days)
4. Check network connectivity

**Solution:**
- Ensure environment variables are set
- Copy job ID correctly from success message
- Use recent jobs (within 7 days)
- Verify internet connection

### Issue: Submission fails silently

**Diagnosis:**
1. Check browser console for errors
2. Verify form content is valid XML/JSON
3. Check file size (< 10 MB)
4. Verify TAN format

**Solution:**
- Review form content structure
- Reduce form size if too large
- Use correct TAN format
- Check network connectivity

### Issue: Job stays in "processing" state

**Diagnosis:**
1. Job is still being analyzed
2. API server might be slow
3. Form complexity might be high

**Solution:**
- Wait longer (up to 24 hours)
- Check if status actually changed
- Try submitting simpler form
- Contact support if > 24 hours

---

## Support & Documentation

### Key Files
- **ANALYTICS_PRESIGNED_URL_IMPLEMENTATION.md** - Technical architecture
- **SANDBOX_ANALYTICS_API_REFERENCE.md** - Official API reference
- **ANALYTICS_IMPLEMENTATION_SUMMARY.md** - Implementation details

### Resources
- Sandbox API Documentation: https://sandbox.co.in/docs
- Form Specifications: https://sandbox.co.in/forms
- Support: support@sandbox.co.in

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 2.0 | Dec 9, 2025 | Added FY/Quarter selectors, fixed presigned URL workflow |
| 1.0 | Dec 9, 2025 | Initial Analytics API implementation |

---

## Status: ✅ PRODUCTION READY

All features tested and validated. Ready for use with real form data.
