# Clearing Dummy Data & Understanding API Data Retrieval

**Date:** December 6, 2025
**Status:** Guide provided

---

## Current Dummy Data

### What's in the database now:

**Invoices:**
- Invoice 123: ₹100,000 (Pari, AHWPA3261C)
- Invoice 1111: ₹250,000 (Sunil, AAARRRE3)

**Vendors:**
- Pari (PAN: AHWPA3261C)
- Sunil (PAN: AAARRRE3)

**Other Data:**
- 1 Firm (T D Framjee and Co)
- 1 Admin User

---

## How to Clear Dummy Data

### OPTION 1: Delete via Database (Recommended)

```bash
# Connect to database
mysql -h 127.0.0.1 -u tdsuser -pStrongPass123 tds_autofile

# Clear all data SAFELY (keeps schema)
DELETE FROM challan_linkages;      -- TDS allocations
DELETE FROM deductees;              -- Deductee aggregates
DELETE FROM tds_filing_logs;        -- Filing audit logs
DELETE FROM tds_filing_jobs;        -- Filing jobs
DELETE FROM challan_allocations;    -- Challan allocations
DELETE FROM invoices;               -- All invoices
DELETE FROM challans;               -- All challans
DELETE FROM vendors WHERE id > 6;   -- Delete test vendors (keep system ones)
```

**Safe & Fast:** Only 10 tables, ~10 records total

### OPTION 2: Keep Current Data

If you want to keep the dummy data for testing Form 16 generation:

1. First reconcile (allocate TDS):
   - Go to `/tds/admin/reconcile.php`
   - Allocate invoice TDS to challans
   
2. Then generate Form 16:
   - Go to `/tds/admin/forms.php`
   - Form 16 will work! ✓

3. Test the system end-to-end

4. Then delete if needed

---

## Can API Retrieve Old Data?

### Short Answer: NO - The API Cannot Retrieve Historical TDS Data

The Sandbox.co.in TDS API **does NOT provide data retrieval endpoints**. Here's why:

### What Sandbox TDS API Does (Only):

1. **Generates FVU** (File Validation Utility)
   - Takes your Form 26Q TXT file
   - Validates it
   - Creates FVU file for submission
   - ✓ YOU provide the data, API validates it

2. **Submits e-filing**
   - Takes FVU file
   - Submits to Tax Authority
   - Returns acknowledgement
   - ✓ YOU provide the files, API submits them

3. **Tracks Filing Status**
   - Checks if filing was accepted
   - Returns acknowledgement number
   - ✓ Tax Authority provides status, API relays it

### What Sandbox TDS API Does NOT Do:

❌ Retrieve historical TDS data
❌ Pull invoices from your bank
❌ Get vendor details from government
❌ Export previously filed returns
❌ Retrieve old CSI files
❌ Access historical tax records

---

## Where Does TDS Data Come From?

### Data Sources (YOU Must Provide):

1. **Invoices** → From your records
   - Sales/expense bills to vendors
   - Amount, date, vendor details
   - You manually enter or bulk upload

2. **Challans** → From bank statements
   - TDS payments made to government
   - Challan number, date, amount
   - You download CSI file or manually enter

3. **Reconciliation** → Your accounting
   - Map which invoices were paid via which challans
   - Allocate TDS amounts
   - You manage in `/tds/admin/reconcile.php`

### Can You Bulk Import Old Data?

**YES!** Use the API endpoints:

**1. Upload Invoices (Bulk)**
```bash
POST /tds/api/upload_invoices
Content-Type: multipart/form-data

CSV Format:
vendor_name,vendor_pan,invoice_no,invoice_date,base_amount,section_code,tds_rate
Vendor A,ABCDE1234F,INV-001,2025-07-15,100000,194A,10
Vendor B,BCDEF2345G,INV-002,2025-08-20,250000,194C,1
```

**2. Upload Challans (From CSI file)**
```bash
POST /tds/api/upload_challan
Content-Type: multipart/form-data

Upload CSI file from bank portal (pipe-delimited format)
0123456|17/08/2025|11223|100000|200
```

**3. Reconcile via API**
```bash
POST /tds/api/reconcile
Content-Type: application/x-www-form-urlencoded

invoice_id=1&challan_id=1&allocated_tds=10000
```

---

## To Retrieve Data From Another System:

### If you have old TDS data in another application:

**Option 1: Export CSV from old system**
→ Import via `/tds/api/upload_invoices`

**Option 2: Manual re-entry**
→ Use `/tds/admin/invoices.php` UI to add each invoice

**Option 3: Database migration**
→ If database structure similar, migrate via SQL

**Option 4: Bank CSI file**
→ If bank provides CSI, upload via `/tds/api/upload_challan`

---

## API Capabilities Summary

| Capability | Available? | Method |
|-----------|-----------|--------|
| Retrieve historical invoices | ❌ NO | N/A |
| Retrieve historical challans | ❌ NO | N/A |
| Retrieve filed returns | ❌ NO | N/A |
| Import/Upload invoices | ✅ YES | POST /api/upload_invoices |
| Import/Upload challans | ✅ YES | POST /api/upload_challan |
| List current invoices | ✅ YES | GET /api/list_invoices |
| List current challans | ✅ YES | GET /api/list_challans |
| Generate Form 26Q | ✅ YES | POST /api/filing/initiate |
| Submit for e-filing | ✅ YES | POST /api/filing/submit |
| Check filing status | ✅ YES | GET /api/filing/check-status |

---

## Step-by-Step: Clear & Start Fresh

### 1. Delete Dummy Data

```sql
-- Clear in this order (respects foreign keys)
DELETE FROM challan_linkages;
DELETE FROM deductees;
DELETE FROM tds_filing_logs;
DELETE FROM tds_filing_jobs;
DELETE FROM challan_allocations;
DELETE FROM invoices;
DELETE FROM challans;
DELETE FROM vendors WHERE firm_id = 1 AND name NOT IN ('System Vendor');
```

### 2. Verify Deletion

```sql
-- Check all data is gone
SELECT COUNT(*) FROM invoices;          -- Should be 0
SELECT COUNT(*) FROM challans;          -- Should be 0
SELECT COUNT(*) FROM vendors;           -- Should be 0+
```

### 3. Start With Real Data

**Option A: Manual Entry**
- Go to `/tds/admin/invoices.php`
- Add your real invoices one by one

**Option B: Bulk Upload (CSV)**
- Create CSV file with invoice data
- Upload via `/tds/admin/invoices.php` → "Bulk Upload"

**Option C: API Upload**
```bash
curl -X POST http://bombayengg.net/tds/api/upload_invoices \
  -F "file=@invoices.csv" \
  --cookie "PHPSESSID=xxx"
```

---

## FAQ

### Q: Can Sandbox retrieve my old filed returns?
**A:** No. Sandbox only helps you FILE new returns. For old returns, check Tax Authority portal.

### Q: Can I import data from my ERP/Accounting software?
**A:** Yes! Export as CSV and upload via `/tds/api/upload_invoices`

### Q: What if I lost my old TDS records?
**A:** You'll need to recreate them from:
- Bank statements (for challans)
- Invoices/bills (for invoice data)
- Accounting software backups

### Q: Can I get CSI automatically from my bank?
**A:** Sandbox provides CSI API, but requires bank integration:
- You need to authenticate with your bank via Sandbox
- This is advanced and requires bank support

### Q: What format for bulk import?
**A:** CSV (Comma-Separated Values) with headers:
```
vendor_name,vendor_pan,invoice_no,invoice_date,base_amount,section_code,tds_rate
```

---

## Recommendation

### For Testing:
✅ Keep dummy data for now
✅ Complete the workflow (reconcile → file → get ack)
✅ Test Form 16 generation
✅ Once confident, delete and use real data

### For Production:
✅ Clear dummy data
✅ Import real invoices (CSV or manual)
✅ Upload real challans from bank
✅ Reconcile with real amounts
✅ File actual TDS returns

---

## Scripts Provided

### Clear All Dummy Data Script

```bash
# File: /tds/lib/clear_dummy_data.php

<?php
require_once __DIR__.'/db.php';

try {
  $pdo->exec('DELETE FROM challan_linkages');
  $pdo->exec('DELETE FROM deductees');
  $pdo->exec('DELETE FROM tds_filing_logs');
  $pdo->exec('DELETE FROM tds_filing_jobs');
  $pdo->exec('DELETE FROM challan_allocations');
  $pdo->exec('DELETE FROM invoices');
  $pdo->exec('DELETE FROM challans');
  $pdo->exec('DELETE FROM vendors WHERE firm_id = 1');
  
  echo "✓ All dummy data cleared!\n";
  echo "Database ready for real data.\n";
} catch (Exception $e) {
  echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
```

Run via:
```bash
php /home/bombayengg/public_html/tds/lib/clear_dummy_data.php
```

---

## Summary

| Question | Answer |
|----------|--------|
| Can API retrieve old data? | ❌ NO - Only submission API |
| Can I import from another system? | ✅ YES - Via CSV upload |
| Can I delete dummy data safely? | ✅ YES - Simple DELETE queries |
| Can I start fresh? | ✅ YES - Anytime |
| Will data loss affect API? | ❌ NO - API doesn't store data |

---

**Status:** Ready to clear dummy data or keep for testing

**Next Step:** Let me know when you want to:
1. Clear dummy data
2. Import real data
3. Or continue testing with current data

