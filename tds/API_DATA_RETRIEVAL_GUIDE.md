# API Data Retrieval Guide - FY 2025-26 Q2

**Date:** December 6, 2025
**Status:** Complete reference with examples

---

## Quick Answer

### Can the API retrieve data for FY 2025-26 Q2?

**✅ YES - Partially**

The API CAN retrieve:
- ✅ List current invoices (filtered by FY/Q)
- ✅ List current challans (filtered by FY/Q)
- ✅ Tracking details from filed returns
- ✅ Filing job status & acknowledgements

The API CANNOT retrieve:
- ❌ Historical data from Tax Authority
- ❌ Old filed returns from government
- ❌ Vendor data from government database
- ❌ CSI data without bank integration

---

## What Each API Endpoint Does

### 1. GET /tds/api/list_invoices
**Purpose:** Retrieve invoices stored in YOUR database

**Parameters:**
```
GET /tds/api/list_invoices?fy=2025-26&quarter=Q2
```

**Returns:** All invoices YOU added for FY 2025-26 Q2
```json
{
  "ok": true,
  "invoices": [
    {
      "id": 2,
      "vendor_name": "Pari",
      "vendor_pan": "AHWPA3261C",
      "invoice_no": "123",
      "invoice_date": "2025-08-15",
      "base_amount": 100000,
      "section_code": "194A",
      "total_tds": 10000,
      "fy": "2025-26",
      "quarter": "Q2",
      "allocation_status": "unallocated"
    },
    {
      "id": 3,
      "vendor_name": "Sunil",
      "vendor_pan": "AAARRRE3",
      "invoice_no": "1111",
      "invoice_date": "2025-09-20",
      "base_amount": 250000,
      "section_code": "194C",
      "total_tds": 2500,
      "fy": "2025-26",
      "quarter": "Q2",
      "allocation_status": "unallocated"
    }
  ]
}
```

**What it's for:** Verify your data entry, check TDS calculations, reconciliation

---

### 2. GET /tds/api/list_challans
**Purpose:** Retrieve challans stored in YOUR database

**Parameters:**
```
GET /tds/api/list_challans?fy=2025-26&quarter=Q2
```

**Returns:** All challans YOU added for FY 2025-26 Q2
```json
{
  "ok": true,
  "challans": [
    {
      "id": 1,
      "bsr_code": "0123456",
      "challan_date": "2025-08-17",
      "challan_serial_no": "11223",
      "amount_tds": 22500,
      "fy": "2025-26",
      "quarter": "Q2"
    }
  ]
}
```

**What it's for:** Verify challan entry, check TDS payment amounts

---

### 3. GET /tds/api/filing/check-status?job_id=5
**Purpose:** Retrieve status of your TDS filing

**Returns:** Complete filing workflow status
```json
{
  "ok": true,
  "job_id": 5,
  "fy": "2025-26",
  "quarter": "Q2",
  "status_overview": {
    "txt_generation": "completed",
    "csi_download": "completed",
    "fvu_generation": "succeeded",
    "e_filing": "acknowledged"
  },
  "filing_details": {
    "job_id": "f845f37e-7f05-4de9-a282-a3b23b9d370a",
    "status": "acknowledged",
    "ack_no": "ACK/2025/MUMT14861A/Q2/12345",
    "filed_at": "2025-12-06 15:30:00"
  },
  "control_totals": {
    "records": 2,
    "amount": 350000.00,
    "tds": 12500.00
  }
}
```

**What it's for:** Track filing progress, get acknowledgement number

---

## What Data Sources Are Available

### Data YOU Must Provide (Manual Entry or Import)

| Data Type | Source | How to Get |
|-----------|--------|-----------|
| **Invoices** | Your vendor bills | CSV export or manual entry |
| **Challans** | Your bank statements | CSI file or manual entry |
| **Allocation** | Your accounting records | Manual reconciliation |

### Data the API PROVIDES FROM SANDBOX

| Data Type | Available? | Via |
|-----------|-----------|-----|
| Government vendor database | ❌ NO | - |
| Historical filed returns | ❌ NO | - |
| CSI from bank | ⚠️ Only if integrated | CSI download endpoint |
| Form 26Q validation | ✅ YES | FVU generation |
| Filing acknowledgement | ✅ YES | Status tracking |
| TDS rates/rules | ✅ YES | Documentation |

---

## Step-by-Step: Retrieve FY 2025-26 Q2 Data

### Step 1: Check What Invoices Are Stored
```bash
curl http://bombayengg.net/tds/api/list_invoices?fy=2025-26&quarter=Q2 \
  --cookie "PHPSESSID=abc123"
```

**Response:** Shows your 2 invoices (₹100K + ₹250K)

**Interpretation:**
- You added these manually
- API just returns what YOU stored
- Shows allocation status (currently unallocated)

---

### Step 2: Check What Challans Are Stored
```bash
curl http://bombayengg.net/tds/api/list_challans?fy=2025-26&quarter=Q2 \
  --cookie "PHPSESSID=abc123"
```

**Response:** Shows your 0-1 challans (currently empty)

**Interpretation:**
- No challans added yet
- Need to add them before reconciliation
- Can upload from bank CSI or add manually

---

### Step 3: File TDS Return
```bash
curl -X POST http://bombayengg.net/tds/api/filing/initiate \
  -d "firm_id=1&fy=2025-26&quarter=Q2" \
  --cookie "PHPSESSID=abc123"
```

**Response:**
```json
{
  "ok": true,
  "job_id": 5,
  "fvu_job_id": "job_xyz123",
  "status": "fvu_pending"
}
```

---

### Step 4: Check Filing Status
```bash
curl http://bombayengg.net/tds/api/filing/check-status?job_id=5 \
  --cookie "PHPSESSID=abc123"
```

**First poll (after 30s):**
```json
{
  "status_overview": {
    "fvu_generation": "processing"
  }
}
```

**After 1-2 minutes:**
```json
{
  "status_overview": {
    "fvu_generation": "succeeded"
  },
  "filing_details": {
    "status": "pending"
  }
}
```

**After submission (hours):**
```json
{
  "status_overview": {
    "e_filing": "acknowledged"
  },
  "filing_details": {
    "ack_no": "ACK/2025/MUMT14861A/Q2/12345"
  }
}
```

---

## Complete Workflow with API Calls

### Full Example for FY 2025-26 Q2

```bash
#!/bin/bash

API_URL="http://bombayengg.net/tds/api"
COOKIE="PHPSESSID=abc123"
FY="2025-26"
QUARTER="Q2"
FIRM_ID=1

echo "=== TDS Filing Workflow for $FY $QUARTER ==="

# 1. Check existing invoices
echo -e "\n1. Checking invoices..."
curl "$API_URL/list_invoices?fy=$FY&quarter=$QUARTER" \
  --cookie "$COOKIE" | jq '.invoices[] | {no: .invoice_no, vendor: .vendor_name, tds: .total_tds}'

# 2. Check existing challans
echo -e "\n2. Checking challans..."
curl "$API_URL/list_challans?fy=$FY&quarter=$QUARTER" \
  --cookie "$COOKIE" | jq '.challans[] | {bsr: .bsr_code, amount: .amount_tds}'

# 3. Initiate filing
echo -e "\n3. Initiating filing..."
RESPONSE=$(curl -X POST "$API_URL/filing/initiate" \
  -d "firm_id=$FIRM_ID&fy=$FY&quarter=$QUARTER" \
  --cookie "$COOKIE")
JOB_ID=$(echo $RESPONSE | jq -r '.job_id')
echo "Job ID: $JOB_ID"

# 4. Poll status until FVU ready (every 30 seconds)
echo -e "\n4. Monitoring FVU generation..."
for i in {1..20}; do
  STATUS=$(curl "$API_URL/filing/check-status?job_id=$JOB_ID" \
    --cookie "$COOKIE" | jq -r '.status_overview.fvu_generation')

  echo "Attempt $i: $STATUS"

  if [ "$STATUS" == "succeeded" ]; then
    echo "✓ FVU Ready!"
    break
  fi

  sleep 30
done

# 5. Submit for e-filing
echo -e "\n5. Submitting for e-filing..."
curl -X POST "$API_URL/filing/submit" \
  -d "job_id=$JOB_ID" \
  --cookie "$COOKIE" | jq '.filing_job_id'

# 6. Poll for acknowledgement (every 5 minutes)
echo -e "\n6. Waiting for acknowledgement..."
for i in {1..24}; do
  STATUS=$(curl "$API_URL/filing/check-status?job_id=$JOB_ID" \
    --cookie "$COOKIE")

  ACK_NO=$(echo $STATUS | jq -r '.filing_details.ack_no // "pending"')
  echo "Check $i: Ack No: $ACK_NO"

  if [ "$ACK_NO" != "pending" ] && [ "$ACK_NO" != "null" ]; then
    echo "✓ Filed Successfully! Ack: $ACK_NO"
    break
  fi

  sleep 300  # 5 minutes
done

echo -e "\n=== COMPLETE ==="
```

---

## API Reference Table

| Method | Endpoint | Purpose | Returns |
|--------|----------|---------|---------|
| GET | `/tds/api/list_invoices` | List invoices for FY/Q | Array of invoices |
| GET | `/tds/api/list_challans` | List challans for FY/Q | Array of challans |
| GET | `/tds/api/filing/check-status?job_id=X` | Check filing status | Status object |
| POST | `/tds/api/filing/initiate` | Start filing process | job_id |
| POST | `/tds/api/filing/submit` | Submit for e-filing | filing_job_id |
| POST | `/tds/api/upload_invoices` | Bulk import invoices | Count uploaded |
| POST | `/tds/api/upload_challan` | Upload CSI file | Count imported |

---

## FAQ

### Q: Can I get historical data from previous quarters?
**A:** Only if YOU stored it.
- API returns what's in YOUR database
- Not from Tax Authority
- Example: `GET /list_invoices?fy=2024-25&quarter=Q1`

### Q: Can I retrieve Form 26Q for Q2 2025-26?
**A:** YES - After filing it:
1. Check filing status: `GET /filing/check-status?job_id=5`
2. Will show `txt_file_path` (your Form 26Q)
3. Download from: `/tds/uploads/filings/{job_id}/form26q.txt`

### Q: Can I get Tax Authority's filing records?
**A:** No, but you get:
- Your filing acknowledgement number (ACK/2025/...)
- This proves to Tax Authority it was filed
- Use to verify on IT portal: https://tin-fc.incometax.gov.in/

### Q: Where are my filed returns stored?
**A:** All stored locally in:
```
/tds/uploads/filings/{job_id}/
  ├── form26q.txt          (Your Form 26Q)
  ├── form26q_fvu.zip      (Validation file)
  ├── form26q_form27a.pdf  (Digital signature form)
  └── form26q_csi          (CSI from bank)
```

### Q: Can I retrieve data via mobile app?
**A:** Yes, if you build one using these API endpoints
- All endpoints are JSON-based
- Work with any HTTP client
- Include session cookie for auth

### Q: What if I lost my data?
**A:** Check backups:
- Local `/tds/uploads/filings/` directory (has all generated files)
- Database backups in `/home/bombayengg/public_html/xsite.backup.*/`
- Tax Authority (they have your acknowledgement)

---

## Current Data for FY 2025-26 Q2

### In YOUR Database (Can Retrieve):
```
Invoices: 2
  - Invoice 123: ₹100,000 (₹10,000 TDS)
  - Invoice 1111: ₹250,000 (₹2,500 TDS)

Challans: 0 (Not added yet)

Status: Invoices unallocated (cannot file yet)
```

### From Sandbox API:
- ✅ Can validate these invoices
- ✅ Can generate Form 26Q
- ✅ Can submit for e-filing
- ⚠️ Cannot retrieve from Tax Authority (not filed yet)

---

## Example: Get FY 2025-26 Q2 Data

### Using cURL
```bash
# Get invoices
curl "http://bombayengg.net/tds/api/list_invoices?fy=2025-26&quarter=Q2" \
  --cookie "PHPSESSID=your_session_id" | jq '.'

# Get challans
curl "http://bombayengg.net/tds/api/list_challans?fy=2025-26&quarter=Q2" \
  --cookie "PHPSESSID=your_session_id" | jq '.'
```

### Using PHP
```php
<?php
require_once __DIR__.'/tds/lib/db.php';

// Get invoices for FY 2025-26 Q2
$stmt = $pdo->prepare('SELECT * FROM invoices WHERE fy=? AND quarter=? AND firm_id=?');
$stmt->execute(['2025-26', 'Q2', 1]);
$invoices = $stmt->fetchAll();

echo json_encode(['invoices' => $invoices]);
?>
```

### Using JavaScript/Fetch
```javascript
// Get invoices
fetch('/tds/api/list_invoices?fy=2025-26&quarter=Q2', {
  credentials: 'include'  // Include cookies
})
.then(r => r.json())
.then(data => {
  console.log('Invoices:', data.invoices);
  data.invoices.forEach(inv => {
    console.log(`${inv.vendor_name}: ₹${inv.total_tds}`);
  });
});
```

---

## Important Notes

### Data Flow
```
You (add data manually or import)
  ↓
Your Database (invoices, challans)
  ↓
API Retrieves (list_invoices, list_challans)
  ↓
You Use API to File
  ↓
Sandbox API Processes
  ↓
Tax Authority Receives
  ↓
API Tracks Status
  ↓
You Get Acknowledgement
```

### API Limitations
- ❌ Cannot retrieve Tax Authority records
- ❌ Cannot access government vendor database
- ❌ Cannot restore deleted data
- ❌ Cannot modify filed returns (must refile)

### API Capabilities
- ✅ List YOUR stored data
- ✅ Track YOUR filing status
- ✅ Validate YOUR forms
- ✅ Get acknowledgement numbers
- ✅ Submit for e-filing

---

## Support

**Need more info?**
- API Reference: `TDS_API_REFERENCE.md`
- Implementation: `TDS_IMPLEMENTATION_GUIDE.md`
- Status Guide: `ACTION_PLAN.txt`

**All files in:** `/home/bombayengg/public_html/tds/`

---

**Status:** ✅ Complete reference guide

**Last Updated:** December 6, 2025

