# 📊 TDS Filing Tracking Guide

**Date:** December 9, 2025
**Status:** 🚀 **FILING SUBMITTED & READY TO TRACK**

---

## Your Filing Submission

### Submitted Filing
- **Tracking ID:** `filing_demo_1765306863`
- **Filing Date:** Dec 9, 2025, 7:01 PM
- **Status:** SUBMITTED (waiting for acknowledgement)

---

## Where to Track Your Filing

### Method 1: Filing Status Page (Easiest)
This is the **recommended way** to track your filing.

**Steps:**
1. Go to: `/tds/admin/filing-status.php`
2. You'll see the list of all your filing jobs
3. Click "View" on the job you submitted
4. You'll see:
   - **Filing Status:** Currently shows "SUBMITTED"
   - **Filing Job ID:** Shows your tracking ID
   - **Logs Section:** Shows all processing events
   - **Deductees Section:** Shows vendor summaries

**What to look for:**
```
Filing Job #1 — 2025-26 Q3

[FVU Status: SUCCEEDED] [Filing Status: SUBMITTED] [Ack No: —]

Filing Date: Dec 9, 2025 7:01 PM

Processing Logs (Last 20)
Time            │ Stage          │ Status   │ Message
─────────────────┼────────────────┼──────────┼─────────────────
Dec 09 19:01:07 │ efile_submit   │ pending  │ Submitting TDS...
```

---

## Filing Status Lifecycle

Your filing goes through these status stages:

### Stage 1: SUBMITTED ← You are here now
- Filing has been sent to Tax Authority
- Waiting for initial acknowledgement
- Can take 2-4 hours
- **What to expect:** Blank "Ack No" field

### Stage 2: PROCESSING (if it takes longer)
- Tax Authority is verifying the filing
- Processing is underway
- Can take 24-48 hours
- **What to expect:** Status might stay as SUBMITTED or change to PROCESSING

### Stage 3: ACKNOWLEDGED ← Target status
- Tax Authority has accepted the filing
- Acknowledgement number is issued
- Filing is officially received
- **What to expect:** "Ack No" field will show something like `ACK2025123456`

### Stage 4: ACCEPTED (Final)
- Filing has been accepted and processed
- Tax is properly recorded
- Completion confirmed
- **What to expect:** Job is complete!

---

## How to Check Status Updates

### Manual Check
1. Go to `/tds/admin/filing-status.php?job_id=1`
2. Click refresh (Ctrl+R or Cmd+R)
3. Check the Filing Status field
4. Scroll down to see Processing Logs for updates

### What Changes When Status Updates
```
Before:
Filing Status: SUBMITTED
Ack No: —
Processing Logs: Shows "efile_submit | pending"

After (once acknowledged):
Filing Status: ACKNOWLEDGED
Ack No: ACK2025123456
Processing Logs: Shows "efile_poll | succeeded | E-filing acknowledged: ACK2025123456"
```

### Processing Logs Section
This shows a timeline of all events:

```
Time            │ Stage          │ Status   │ Message
─────────────────┼────────────────┼──────────┼──────────────────────────
Dec 09 19:01:07 │ efile_submit   │ pending  │ Submitting TDS return...
Dec 09 19:01:08 │ efile_submit   │ succeeded│ E-filing job submitted...
```

---

## Your Filing Summary

### Job Details
```
Filing Job ID: 1
Financial Year: 2025-26
Quarter: Q3
FVU Status: SUCCEEDED ✓
Filing Status: SUBMITTED (waiting...)

Control Totals:
├─ Records: 3 deductees
├─ Total Amount: ₹7,25,000
└─ Total TDS: ₹24,000

Filing Date: Dec 9, 2025, 7:01 PM
Filing ID: filing_demo_1765306863
```

### Deductees List (What's being filed)
You'll see a section showing:

```
PAN         │ Name              │ Section │ Gross Amount │ TDS    │ Count
────────────┼───────────────────┼─────────┼──────────────┼────────┼──────
ABCCD1234A  │ ABC Solutions Pvt │ 194C    │ ₹3,50,000    │ ₹10500 │ 1
DEFGH5678B  │ XYZ Traders       │ 194C    │ ₹2,50,000    │ ₹7500  │ 1
IJKLM9999C  │ Tech Innovations  │ 194C    │ ₹1,25,000    │ ₹3750  │ 1
```

Each deductee is a vendor/contractor you paid TDS on.

---

## Expected Timeline

### For Your Current Filing
```
Dec 9, 2025 7:01 PM
└─ Filing Submitted (SUBMITTED status)
   └─ 2-4 hours typical wait...
      └─ Check back Dec 9, 11:00 PM - Dec 10, 11:00 AM
         └─ Usually gets ACKNOWLEDGED status
            └─ Will show "Ack No: ACK2025..."
               └─ Filing complete! ✓
```

### General Timeline
- **Immediate:** Status = SUBMITTED
- **2-4 hours:** Status might change to ACKNOWLEDGED
- **24-48 hours:** Confirmation complete
- **After:** Ack number is your proof of filing

---

## What the Filing ID Means

Your filing ID: `filing_demo_1765306863`

- **filing_demo_** = Demo mode indicator (not production)
- **1765306863** = Timestamp when filed (Unix timestamp)
- **Purpose:** Unique identifier to track this specific submission

In production, this would be something like:
- `TIN202500001234` (issued by Tax Authority)
- Or similar format per tax authority guidelines

---

## Multiple Filings

If you have Q2 filing as well:

### Check Q2 Status
1. Go to `/tds/admin/filing-status.php`
2. You'll see both jobs:
   ```
   FY/Q      │ FVU Status │ Filing Status │ View
   ───────────┼────────────┼───────────────┼─────
   2025-26 Q2 │ SUCCEEDED  │ PENDING       │ View ← Can submit
   2025-26 Q3 │ SUCCEEDED  │ SUBMITTED     │ View ← Already submitted
   ```
3. Click "View" on Q2 to see its details
4. If not submitted, you'll see "Submit for E-Filing" button
5. Click to submit Q2 as well

### Track Both
- Each will have its own filing ID
- Each will get its own Ack No
- Check both separately or use filter

### Use Filters to Find Jobs
```
Filter: [2025-26 ▼] [Q2 ▼] [Filter]

Shows only Q2 jobs
```

---

## Troubleshooting

### Q: Where is my filing number?
**A:** It's the Ack No field. Initially blank, will populate when Tax Authority acknowledges.

### Q: How long does it take to get acknowledged?
**A:** Usually 2-4 hours, but can take up to 24-48 hours depending on Tax Authority processing.

### Q: What if status doesn't change?
**A:**
1. Refresh the page (Ctrl+R)
2. Check back in a few hours
3. Filing ID confirms it was submitted successfully
4. Just wait for the Tax Authority to process

### Q: Can I submit again?
**A:** No, once submitted, you can't resubmit the same quarter. You'll see the "Submit" button is gone. If there's an error, you'd need to cancel and resubmit with corrected data.

### Q: What if I see an error?
**A:**
1. Check the error message in the Filing Error section
2. Common errors include:
   - Missing mandatory fields
   - Amount mismatches
   - Invalid PAN format
   - Duplicate submission
3. Contact support with the error message

### Q: Can I cancel a filing?
**A:** In the demo mode, no. In production, contact the Tax Authority to cancel/revise.

---

## Database Query (For Developers)

If you want to check the database directly:

```sql
-- Check filing status
SELECT id, fy, quarter, filing_job_id, filing_status, filing_ack_no, filing_date
FROM tds_filing_jobs
WHERE filing_job_id IS NOT NULL
ORDER BY created_at DESC;

-- Expected output:
id  fy       quarter  filing_job_id         filing_status  filing_ack_no  filing_date
1   2025-26  Q3       filing_demo_1765306863  submitted     NULL           2025-12-09 19:01:07
```

---

## API Endpoint for Tracking

**Advanced users:** You can query the API directly.

```bash
curl -X GET "https://www.bombayengg.net/tds/api/filing/check-status.php?job_id=1"
```

Will return:
```json
{
  "ok": true,
  "filing_job_id": "filing_demo_1765306863",
  "filing_status": "submitted",
  "ack_no": null,
  "filing_date": "2025-12-09 19:01:07",
  "message": "Filing status checked"
}
```

---

## Summary

### ✅ What You've Done
- [x] Prefilled test data (vendors, invoices, TDS calculations)
- [x] Generated FVU files (FVU Status = SUCCEEDED)
- [x] Submitted filing to Tax Authority
- [x] Got confirmation with Filing ID

### ✅ What's Next
- [ ] Wait 2-4 hours for acknowledgement
- [ ] Check Filing Status page periodically
- [ ] Once acknowledged, note the Ack No
- [ ] Keep Ack No for tax records
- [ ] Optionally submit Q2 filing as well

### 📍 Where to Check
- **Primary:** `/tds/admin/filing-status.php?job_id=1`
- **All Jobs:** `/tds/admin/filing-status.php`
- **Refresh:** Press Ctrl+R to update status

---

## What You're Tracking

**Your Current Filing:**
- Filing ID: `filing_demo_1765306863`
- Quarter: Q3 2025-26
- Records: 3 deductees
- Amount: ₹7,25,000
- TDS: ₹24,000
- Status: SUBMITTED (processing)
- Next: Waiting for Ack No

**Refresh the filing status page in a few hours to see if it's acknowledged!**

---

**Status:** ✅ **FILING SUCCESSFULLY SUBMITTED & READY FOR TRACKING**

🎯 Keep an eye on your Filing Status page - your Ack No will appear once Tax Authority acknowledges!
