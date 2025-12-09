# How to Submit for E-Filing

## ✅ The Submit Button Location

The **"Submit for E-Filing"** button is located on the **Filing Status page**, not the Compliance page.

---

## 📋 Step-by-Step Guide

### Step 1: View Your Filing Jobs
Go to: **Filing Status Page**
```
URL: /tds/admin/filing-status.php
```

This page shows all your filing jobs.

### Step 2: Select a Filing Job
You will see 2 filing jobs:
- **Job #1:** FY 2025-26, Q3
- **Job #3:** FY 2025-26, Q2

Click on the job ID to view details. For example:
```
URL: /tds/admin/filing-status.php?job_id=1
URL: /tds/admin/filing-status.php?job_id=3
```

### Step 3: Check FVU Status
On the filing job details page, you'll see:

```
┌─────────────────────────────────────────┐
│  Filing Job #1 — 2025-26 Q3             │
├─────────────────────────────────────────┤
│                                         │
│  [FVU Status: SUCCEEDED] ✓             │
│  [Filing Status: PENDING]               │
│  [Ack No: —]                            │
│                                         │
│  Control Totals                         │
│  Records: 3 | Amount: ₹725,000         │
│  TDS: ₹24,000                          │
│                                         │
│  ┌─────────────────────────────────────┐│
│  │ 📤 Submit for E-Filing      [Button]││
│  └─────────────────────────────────────┘│
└─────────────────────────────────────────┘
```

### Step 4: Click "Submit for E-Filing" Button
- The button will only appear if:
  - ✓ FVU Status = "SUCCEEDED"
  - ✓ Filing Status = "PENDING" (not already submitted)

- When you click it:
  - System submits to Tax Authority
  - Filing job ID is created
  - You see confirmation: "Filing submitted! Tracking ID: ..."
  - Page reloads automatically

### Step 5: Check Filing Status
After submission, the page will show:
```
Filing Status: SUBMITTED

Or eventually:
Filing Status: ACKNOWLEDGED
Ack No: ABC123XYZ
```

---

## 🔗 Direct Links

### Q2 Filing Job (6 Invoices)
```
/tds/admin/filing-status.php?job_id=3
```

### Q3 Filing Job (3 Invoices)
```
/tds/admin/filing-status.php?job_id=1
```

---

## 📊 Filing Job Summary

| Job ID | Quarter | Status | Action |
|--------|---------|--------|--------|
| 1 | Q3 | FVU Succeeded | [Submit](./filing-status.php?job_id=1) |
| 3 | Q2 | FVU Succeeded | [Submit](./filing-status.php?job_id=3) |

---

## ❓ Why is the button blank/not working?

### Possible Reasons:

1. **Wrong URL**
   - Make sure you're on `/tds/admin/filing-status.php?job_id=1` or `?job_id=3`
   - NOT on `/tds/admin/compliance.php`

2. **FVU Status is not "succeeded"**
   - Check the FVU Status box at the top of the page
   - Must be `SUCCEEDED` for the button to appear

3. **Already Submitted**
   - If Filing Status shows "SUBMITTED" or "ACKNOWLEDGED"
   - The button won't show because filing is already in progress
   - Use the status page to track progress

4. **Filing Job not found**
   - Make sure job_id is 1 or 3
   - Check the list of jobs first: `/tds/admin/filing-status.php`

---

## ✅ Correct Workflow

```
1. Go to: /tds/admin/filing-status.php
   ↓
2. Click Job #1 (or Job #3)
   ↓
3. See FVU Status = SUCCEEDED ✓
   ↓
4. Click "Submit for E-Filing" Button
   ↓
5. See Confirmation Message
   ↓
6. Watch as Filing Status changes:
   - SUBMITTED (30 min)
   - PROCESSING (1-2 hours)
   - ACKNOWLEDGED (2-4 hours)
   - ACCEPTED (final)
```

---

## 🔍 Debug: Check Job Details

If you're having issues, check the job details in database:

```sql
SELECT id, fy, quarter, fvu_status, filing_status, filing_ack_no
FROM tds_filing_jobs
ORDER BY id;
```

Expected output:
```
id  fy        quarter  fvu_status  filing_status  filing_ack_no
1   2025-26   Q3       succeeded   pending        NULL
3   2025-26   Q2       succeeded   pending        NULL
```

If `fvu_status` is not "succeeded", FVU generation needs to complete first.

---

## 💡 Alternative: Use Compliance Page

The Compliance page (`/tds/admin/compliance.php`) also has a submit button, but it's an alternative interface. The main/primary button is on the Filing Status page.

**Both buttons do the same thing:**
- Takes the most recent filing job
- Submits it to Tax Authority
- Updates the database
- Shows confirmation

**We recommend using the Filing Status page** because:
- More details visible
- Can track specific job
- Can view job ID and logs

---

## 📞 Still Having Issues?

1. Check you're on the correct URL:
   ```
   /tds/admin/filing-status.php?job_id=1
   ```

2. Verify FVU Status shows "SUCCEEDED"

3. Check browser console for JavaScript errors

4. Try refreshing the page (F5)

5. Check if button is cut off by scrolling right

6. Try a different browser or incognito window

---

**Status:** ✅ Ready to submit for e-filing
**Next:** Click the "Submit for E-Filing" button on the Filing Status page

