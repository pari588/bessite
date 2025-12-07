# TDS AutoFile - API Reference

## Authentication
All endpoints require authentication via session (`auth_require()`).

---

## Filing Endpoints

### 1. Initiate Filing Workflow
**Endpoint**: `POST /tds/api/filing/initiate`

**Purpose**: Start TDS filing for a fiscal year/quarter

**Parameters**:
```json
{
  "firm_id": 1,
  "fy": "2025-26",
  "quarter": "Q1"
}
```

**Response** (Success):
```json
{
  "ok": true,
  "job_id": 5,
  "fvu_job_id": "job_8e9f2a1b3c4d",
  "fy": "2025-26",
  "quarter": "Q1",
  "status": "fvu_pending",
  "control_totals": {
    "records": 3,
    "amount": 500000.00,
    "tds": 50000.00
  },
  "message": "FVU generation job submitted. Check status using job_id.",
  "next_action": "Poll /api/filing/check-status?job_id=5"
}
```

**Errors**:
- `Invalid firm ID` - firm_id not provided or invalid
- `Invalid FY format` - FY must be YYYY-YY (e.g., 2025-26)
- `Invalid quarter` - must be Q1, Q2, Q3, or Q4
- `Firm not found` - firm_id doesn't exist
- `Filing job already exists` - FY/Quarter already filed
- `No invoices found` - no data for this period
- `X invoices have incomplete allocation` - reconciliation incomplete

---

### 2. Check Filing Status
**Endpoint**: `GET /tds/api/filing/check-status?job_id=5`

**Purpose**: Poll job progress (FVU generation & e-filing)

**Parameters**:
```
job_id=5 (required, integer)
```

**Response**:
```json
{
  "ok": true,
  "job_id": 5,
  "fy": "2025-26",
  "quarter": "Q1",
  "status_overview": {
    "txt_generation": "completed",
    "csi_download": "completed",
    "fvu_generation": "succeeded",
    "e_filing": "pending"
  },
  "fvu_details": {
    "job_id": "job_8e9f2a1b3c4d",
    "status": "succeeded",
    "error": null,
    "generated_at": "2025-12-06 14:30:00"
  },
  "filing_details": {
    "job_id": null,
    "status": "pending",
    "ack_no": null,
    "error": null,
    "filed_at": null
  },
  "control_totals": {
    "records": 3,
    "amount": 500000.00,
    "tds": 50000.00
  },
  "next_action": "Submit for e-filing using /api/filing/submit",
  "can_submit_efiling": true,
  "recent_logs": [
    {
      "stage": "fvu_generation",
      "status": "succeeded",
      "message": "FVU and Form 27A files downloaded",
      "timestamp": "2025-12-06 14:30:00"
    }
  ],
  "created_at": "2025-12-06 13:00:00",
  "updated_at": "2025-12-06 14:30:00"
}
```

**Status Values**:
- `pending` - Not yet processed
- `submitted` - Job submitted to Sandbox
- `processing` - In progress
- `succeeded` - Completed successfully
- `failed` - Failed (see error message)
- `acknowledged` - E-filing acknowledged by IT
- `accepted` - Final acceptance
- `rejected` - Rejected by IT

---

### 3. Submit for E-Filing
**Endpoint**: `POST /tds/api/filing/submit`

**Purpose**: Submit validated TDS return to Tax Authority

**Parameters**:
```json
{
  "job_id": 5
}
```

**Prerequisites**:
- FVU generation must be completed (`fvu_status = 'succeeded'`)
- Form 27A must be available

**Response** (Success):
```json
{
  "ok": true,
  "job_id": 5,
  "filing_job_id": "filing_abc456def789",
  "filing_status": "submitted",
  "message": "TDS return submitted for e-filing to Tax Authority",
  "next_action": "Use /api/filing/check-status to track acknowledgement",
  "expected_processing_time": "2-4 hours for acknowledgement"
}
```

**Errors**:
- `Invalid job_id` - job_id not provided or invalid
- `Job not found` - job_id doesn't exist
- `FVU generation not complete` - must complete FVU first
- `FVU file not found` - files missing
- `Form 27A file not found` - files missing
- `Filing already submitted` - can't submit twice

---

## Legacy Endpoints (Still Available)

### Upload Invoices
**Endpoint**: `POST /tds/api/upload_invoices` OR `/tds/api/add_invoice`

### Upload Challans
**Endpoint**: `POST /tds/api/upload_challan` OR `/tds/api/add_challan`

### Reconcile (Manual Allocation)
**Endpoint**: `POST /tds/api/reconcile`

### List Data
**Endpoints**:
- `GET /tds/api/list_invoices` - List all invoices
- `GET /tds/api/list_challans` - List all challans
- `GET /tds/api/list_recent_invoices` - Last 50 invoices
- `GET /tds/api/list_recent_challans` - Last 50 challans

---

## Admin Pages

### Invoices
**URL**: `/tds/admin/invoices.php`
- Add single invoice
- Bulk upload CSV
- Edit/Delete invoices
- View recent invoices

**CSV Format**:
```
vendor_name,vendor_pan,invoice_no,invoice_date,base_amount,section_code,tds_rate
Vendor A,ABCDE1234F,INV001,2025-08-15,100000,194A,10
```

### Challans
**URL**: `/tds/admin/challans.php`
- Add single challan
- Upload CSI file
- Edit/Delete challans

**CSI Format** (pipe-delimited):
```
0123456|17/08/2025|11223|100000|200
```

### Reconciliation
**URL**: `/tds/admin/reconcile.php`
- View allocation status
- Allocate invoices to challans
- View unallocated amounts

### Filing Status
**URL**: `/tds/admin/filing-status.php` (Future)
- View all filing jobs
- Track FVU generation
- Track e-filing status
- Download generated files

---

## Database Tables

### tds_filing_jobs
```sql
SELECT * FROM tds_filing_jobs
WHERE firm_id = 1 AND fy = '2025-26' AND quarter = 'Q1';
```

### tds_filing_logs
```sql
SELECT stage, status, message, created_at
FROM tds_filing_logs
WHERE job_id = 5
ORDER BY created_at DESC;
```

### api_credentials
```sql
SELECT firm_id, api_key, api_secret, token_expires_at, is_active
FROM api_credentials
WHERE firm_id = 1;
```

---

## Examples

### Complete Filing Workflow
```bash
# 1. Initiate filing
RESPONSE=$(curl -X POST http://localhost/tds/api/filing/initiate \
  -d "firm_id=1&fy=2025-26&quarter=Q2" \
  --cookie "PHPSESSID=xxx")
JOB_ID=$(echo $RESPONSE | jq '.job_id')

# 2. Check status every 30 seconds
for i in {1..20}; do
  curl http://localhost/tds/api/filing/check-status?job_id=$JOB_ID \
    --cookie "PHPSESSID=xxx" | jq '.status_overview'
  sleep 30
done

# 3. When FVU ready, submit
curl -X POST http://localhost/tds/api/filing/submit \
  -d "job_id=$JOB_ID" \
  --cookie "PHPSESSID=xxx"

# 4. Check filing status every 5 minutes
for i in {1..24}; do
  curl http://localhost/tds/api/filing/check-status?job_id=$JOB_ID \
    --cookie "PHPSESSID=xxx" | jq '.filing_details'
  sleep 300
done
```

---

### Using JavaScript
```javascript
// Check filing status
async function checkFilingStatus(jobId) {
  const response = await fetch(`/tds/api/filing/check-status?job_id=${jobId}`);
  const data = await response.json();

  if (data.ok) {
    console.log('FVU Status:', data.status_overview.fvu_generation);
    console.log('Filing Status:', data.status_overview.e_filing);
    console.log('Ack No:', data.filing_details.ack_no);

    if (data.can_submit_efiling) {
      console.log('Ready to submit for e-filing!');
    }
  }
}

// Submit for e-filing
async function submitEFiling(jobId) {
  const response = await fetch('/tds/api/filing/submit', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `job_id=${jobId}`
  });
  const data = await response.json();

  if (data.ok) {
    console.log('Filed! Job:', data.filing_job_id);
    console.log('Expected time:', data.expected_processing_time);
  }
}
```

---

## Error Handling

### Standard Error Response
```json
{
  "ok": false,
  "msg": "Error message describing what went wrong"
}
```

### HTTP Status Codes
- `200` - Success
- `400` - Bad request (validation error)
- `401` - Unauthorized (not logged in)
- `500` - Server error

---

## Rate Limits
- No rate limiting on filing endpoints
- Sandbox API: Up to 1000 requests/hour
- E-filing: One submission per FY/Quarter

---

## Polling Strategy

For FVU generation (usually 1-2 minutes):
```
Interval: 30 seconds
Max iterations: 20 (10 minutes total timeout)
```

For E-filing (usually 2-4 hours):
```
Interval: 5 minutes (start)
Backoff: Increase to 15 minutes after 1 hour
Max: Check for 24 hours
```

---

## File Locations

All generated files stored in:
```
/tds/uploads/filings/{job_id}/
  ├── form26q.txt (Form 26Q in NS1 format)
  ├── form26q_csi (CSI from bank)
  ├── form26q_fvu.zip (FVU from Sandbox)
  └── form26q_form27a.pdf (Form 27A from Sandbox)
```

Downloaded for archival/compliance.

---

## Troubleshooting

### FVU Generation Fails
1. Check TXT file generated: `SELECT txt_file_path FROM tds_filing_jobs WHERE id=5`
2. Verify API credentials: `SELECT * FROM api_credentials WHERE firm_id=1`
3. Check logs: `SELECT * FROM tds_filing_logs WHERE job_id=5 AND stage LIKE 'fvu%'`

### E-Filing Fails
1. Verify FVU succeeded: `SELECT fvu_status FROM tds_filing_jobs WHERE id=5`
2. Check Form 27A: `SELECT form27a_file_path FROM tds_filing_jobs WHERE id=5`
3. Review error: `SELECT filing_error_message FROM tds_filing_jobs WHERE id=5`

### CSI Download Issues
- Real CSI requires bank portal integration
- Sandbox mode uses mock CSI (marked as test data)
- Production: Implement bank API integration

