# TDS AutoFile - Updates (December 10, 2025)

**Date:** December 10, 2025
**Status:** ✅ All Changes Applied

---

## Summary of Changes

This document outlines all the fixes and improvements made to the TDS AutoFile system on December 10, 2025.

---

## 1. Challan Addition Fixes

### Issue
- HTTP 500 error when adding challans
- BSR code field was broken in layout
- BSR code allowing more than 7 characters

### Fixes Applied

**File: `/tds/api/add_challan.php`**
- Added output buffering to catch unexpected output
- Removed dependency on ajax_helpers.php to avoid double header issue
- Added proper error handling with Throwable
- Returns HTTP 200 with JSON error messages for better JS handling
- Added BSR code validation (must be exactly 7 digits)

**File: `/tds/admin/challans.php`**
- Changed BSR code field from MD3 component to standard HTML input
- Added `maxlength="7"` attribute
- Added JavaScript to enforce 7-digit numeric input only
- Fixed form data collection for MD3 components

**Database Change:**
```sql
-- BSR code column (kept as CHAR(7) for standard BSR codes)
ALTER TABLE challans MODIFY COLUMN bsr_code CHAR(7) NOT NULL;
```

---

## 2. Duplicate Entry Constraints Removed

### Issue
- Unique constraints preventing legitimate duplicate entries

### Fixes Applied

**Challans Table:**
```sql
ALTER TABLE challans DROP INDEX uniq_cin;
```
- Removed unique constraint on `bsr_code + challan_date + challan_serial_no`
- Same BSR code, date, and serial can now be added multiple times

**Filing Jobs Table:**
```sql
ALTER TABLE tds_filing_jobs DROP INDEX unique_filing;
```
- Removed unique constraint on `firm_id + fy + quarter`
- Multiple filing jobs can now be created for the same FY/Quarter

---

## 3. Timezone Settings (IST)

### Fixes Applied

**File: `/tds/lib/db.php`**
- Added `date_default_timezone_set('Asia/Kolkata')` for PHP
- Added `SET time_zone = '+05:30'` for MySQL session

All timestamps now display in Indian Standard Time (IST).

---

## 4. Filing Jobs Delete Option

### Issue
- No way to delete filing jobs from the UI

### Fixes Applied

**File: `/tds/admin/filing-status.php`**
- Added "Delete" link next to "View" for each filing job
- Added JavaScript confirmation before deletion
- Added `deleteJob()` function for AJAX deletion

**New File: `/tds/api/delete_filing_job.php`**
- Deletes related records from `tds_filing_logs`
- Deletes related records from `deductees`
- Deletes the filing job from `tds_filing_jobs`

---

## 5. Invoice Reconciliation Status Fix

### Issue
- Invoices showing as "needs reconciliation" even after being reconciled
- `allocation_status` not being updated in invoices table

### Fixes Applied

**File: `/tds/api/reconcile.php`**
- Added `UPDATE invoices SET allocation_status=?` after allocation
- Status set to `complete` if fully allocated, `partial` if partially allocated

**Database Fix (one-time):**
```sql
UPDATE invoices i
SET allocation_status = CASE
    WHEN (SELECT COALESCE(SUM(allocated_tds), 0) FROM challan_allocations ca WHERE ca.invoice_id = i.id) >= i.total_tds THEN 'complete'
    WHEN (SELECT COALESCE(SUM(allocated_tds), 0) FROM challan_allocations ca WHERE ca.invoice_id = i.id) > 0 THEN 'partial'
    ELSE 'unallocated'
END;
```

---

## 6. Dashboard Risk Level Fix

### Issue
- Risk Level showing "(/100)" and "❌ Fix issues first" - not updating
- `$riskAssessment` variable was undefined

### Fixes Applied

**File: `/tds/admin/dashboard.php`**
- Added complete risk assessment calculation
- Risk score based on 4 checks (100 points total):
  - 40 points: All invoices reconciled
  - 20 points: Invoices exist for quarter
  - 20 points: Challans exist for quarter
  - 20 points: All vendors have valid PAN

**Risk Levels:**
| Score | Level | Safe to File |
|-------|-------|--------------|
| 80-100 | LOW | ✓ Yes |
| 50-79 | MEDIUM | ✗ No |
| 0-49 | HIGH | ✗ No |

---

## 7. Compliance Page FY/Quarter Filter

### Issue
- Compliance page not filterable by Financial Year and Quarter

### Fixes Applied

**File: `/tds/admin/compliance.php`**
- Added FY/Quarter filter dropdown at top of page
- Filing jobs now filtered by selected FY/Quarter
- Workflow status checks use selected FY/Quarter
- Filter shows: Q1 (Apr-Jun), Q2 (Jul-Sep), Q3 (Oct-Dec), Q4 (Jan-Mar)

---

## Files Modified

| File | Changes |
|------|---------|
| `/tds/api/add_challan.php` | Complete rewrite with error handling, validation |
| `/tds/admin/challans.php` | BSR field fix, form data collection fix |
| `/tds/lib/db.php` | Added IST timezone settings |
| `/tds/api/reconcile.php` | Added allocation_status update |
| `/tds/admin/dashboard.php` | Added risk assessment calculation |
| `/tds/admin/filing-status.php` | Added delete functionality |
| `/tds/admin/compliance.php` | Added FY/Quarter filter |

## New Files Created

| File | Purpose |
|------|---------|
| `/tds/api/delete_filing_job.php` | API to delete filing jobs |
| `/tds/mds/UPDATES_DEC_10_2025.md` | This documentation |

---

## Database Changes Summary

```sql
-- 1. BSR code column (already CHAR(7), kept as is)

-- 2. Remove challan unique constraint
ALTER TABLE challans DROP INDEX uniq_cin;

-- 3. Remove filing job unique constraint
ALTER TABLE tds_filing_jobs DROP INDEX unique_filing;

-- 4. Fix invoice allocation status (one-time update)
UPDATE invoices i
SET allocation_status = CASE
    WHEN (SELECT COALESCE(SUM(allocated_tds), 0) FROM challan_allocations ca WHERE ca.invoice_id = i.id) >= i.total_tds THEN 'complete'
    WHEN (SELECT COALESCE(SUM(allocated_tds), 0) FROM challan_allocations ca WHERE ca.invoice_id = i.id) > 0 THEN 'partial'
    ELSE 'unallocated'
END;
```

---

## Testing Checklist

- [x] Add challan with 7-digit BSR code - Works
- [x] BSR field stops at 7 digits - Works
- [x] Add duplicate challan - Works (constraint removed)
- [x] Delete filing job - Works
- [x] Reconcile invoices - Status updates correctly
- [x] Dashboard risk level - Shows correct score
- [x] Compliance page filter - Filters by FY/Quarter
- [x] Timestamps show IST - Works

---

## Status

✅ **All changes applied and tested**

The TDS AutoFile system is updated and ready for use.
