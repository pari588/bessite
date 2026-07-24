# Driver Management System - Changes & Fixes
**Date:** December 30, 2025

---

## 1. Bug Fix: Dilkhush's Incorrect Record

### Problem
Dilkhush checked in early at 6:53 AM but the record showed:
- `fromTime`: 10:00:00 (wrong)
- `toTime`: 06:53:51 (his actual arrival stored in wrong field)

### Root Cause
1. The cron (`autoMarkin`) ran at 6:30 AM IST and created a record with `fromTime = 10:00`, `toTime = NULL`
2. When Dilkhush opened the app at 6:53 AM, the UI saw an "open shift" (record with `toTime = NULL`) and showed **Mark Out** button instead of Mark In
3. When he clicked Mark Out, it updated `toTime = 6:53 AM` - resulting in illogical checkout before check-in

### Fix Applied
- **Database:** Corrected record 960 to `fromTime = 06:53:51`, `toTime = NULL`
- **Code:** Updated UI to distinguish between cron-created records (recordType=1) and manual records (recordType=2)

---

## 2. Driver-Specific Shift Timings

### Before
Hardcoded timings for all drivers:
- Shift: 10:00 AM - 8:00 PM
- Early OT window: 6 AM - 10 AM
- Late OT window: 8 PM - 6 AM

### After
Dynamic timings from `mx_user` table:

| Driver | Shift Start | Shift End | Early OT Window | Late OT Window |
|--------|-------------|-----------|-----------------|----------------|
| Dilkhush | 10:00 AM | 8:00 PM | 6 AM - 10 AM | 8 PM - 6 AM |
| Suraj | 9:00 AM | 7:00 PM | 6 AM - 9 AM | 7 PM - 6 AM |

### Files Modified
- `xsite/mod/driver/x-home.php` - UI reads `userFromTime` and `userToTime`
- `xsite/mod/driver/x-driver.inc.php` - `markIn()` and `markOut()` validate against driver's specific times

---

## 3. Weekly Off Day Support

### Configuration
| Driver | Off Day | Flat Rate (<4 hrs) | Flat Rate (≥4 hrs) |
|--------|---------|--------------------|--------------------|
| Dilkhush | Sunday (7) | ₹450 | ₹600 |
| Suraj | Saturday (6) | ₹600 | ₹600 |

### New Logic

**On Normal Working Days:**
- Mark In: Available 6 AM to shift start (early overtime)
- Mark Out: Available after shift end to 6 AM (late overtime)
- Calculation: Overtime hours × rate + dinner/taxi allowance

**On Weekly Off Days:**
- Mark In: Available anytime after 6 AM
- Mark Out: Available after marking in OR after shift end time
- Calculation: **Flat rate + overtime (if past shift end) + dinner/taxi allowance**

### Example - Dilkhush works Sunday 10 AM to 10 PM:
```
Flat rate (6+ hrs):     ₹600
Overtime (2 hrs × ₹75): ₹150
Dinner allowance:       ₹100 (if after 10 PM)
--------------------------------
Total:                  ₹750 - ₹850
```

### Files Modified
- `xsite/mod/driver/x-home.php` - Added `$isOffDay` check for UI button display
- `xsite/mod/driver/x-driver.inc.php` - `markIn()` and `markOut()` allow operations on off days
- `xadmin/mod/driver-management/x-driver-management.inc.php` - `overtimeManagement()` calculates flat rate + overtime on off days

---

## 4. Email Notification Updates

### Change
- Recipient changed from `akash.tdf@gmail.com` to `paritosh.ajmera@gmail.com`

### Automatic Notifications Sent For:
1. **Early Check-In** (before shift start) - `markIn()` function
2. **Late Check-Out** (after shift end) - `markOut()` function

### File Modified
- `core/brevo.inc.php` - Updated recipient email

---

## 5. Code Improvements

### UI Logic (`x-home.php`)

**Open Shift Detection:**
```php
// Only consider recordType=2 (manual) as open shifts
// Cron-created records (recordType=1) are NOT open shifts
$DB->sql = "SELECT * FROM driver_management
            WHERE status=? AND userID=? AND dmDate=?
            AND (toTime IS NULL OR toTime = '')
            AND recordType = 2";
```

**Off Day Button Logic:**
```php
if ($isOffDay) {
    $showMarkIn = !$hasOpenShift && !$hasCompletedRecord && ($currentHour >= 6);
    $showMarkOut = $hasOpenShift || ($isLateOvertimeWindow && $hasTodayRecord && !$hasCompletedRecord);
} else {
    // Normal day logic...
}
```

### markIn() Function (`x-driver.inc.php`)

**Added:**
- Driver-specific shift start time validation
- Off-day detection (allows mark in anytime on off days)
- Updates cron-created records instead of failing

```php
// On normal days, Mark In only before shift start
// On off days, Mark In allowed anytime after 6 AM
if (!$isOffDay && date('H:i') >= $driverShiftStart) {
    return "Mark In is only available before X:XX AM";
}
```

### markOut() Function (`x-driver.inc.php`)

**Added:**
- Driver-specific shift end time validation
- Off-day detection (allows mark out after marking in on off days)
- Uses driver's shift end time for new overtime records

```php
// On normal days, Mark Out only after shift end
// On off days, Mark Out allowed if has open shift
if (!$isOffDay && !$isLateOvertimeWindow && $driverManagementID == 0) {
    return "Mark Out is only available after X:XX PM";
}
```

### overtimeManagement() Function (`x-driver-management.inc.php`)

**Before:**
```php
if (isWeekend($dmDate)) {
    // Only flat rate
    $sunAllowance = $ABVFOURHRSALLOW;
}
```

**After:**
```php
if ($isOffDay) {
    // Flat rate based on hours worked
    if ($totalWorkingHrs >= 4) {
        $sunAllowance = $SUNFOURHRSALLOW;
        if ($totalWorkingHrs >= 6) {
            $sunAllowance = $ABVFOURHRSALLOW;
        }
    }

    // PLUS overtime if past shift end
    if ($toTime >= $TOTIME) {
        $overtimeHrs = (toTime - shiftEnd) in hours;
        $totalOvertimePay = $OVERTIMEALLOW * $overtimeHrs;
    }

    // PLUS dinner/taxi allowance if applicable
    if ($toTime >= $DATIME) $dinnerAllowance = $DINNERALLOW;
    if ($toTime >= $TAXIALLOTIME) $taxiAllowance = $TAXIALLOW;
}
```

---

## 6. Database Schema Reference

### mx_user (Driver Settings)
| Column | Description | Dilkhush | Suraj |
|--------|-------------|----------|-------|
| userFromTime | Shift start | 10:00:00 | 09:00:00 |
| userToTime | Shift end | 20:00:00 | 19:00:00 |
| workingHrs | Standard hours | 10 | 10 |
| overtimeAllowance | OT rate/hour | ₹75 | ₹75 |
| dinnerAllowance | After dinnerTime | ₹150 | ₹100 |
| taxiAllowance | After taxiTime | ₹100 | ₹100 |
| dinnerTime | Trigger time | 22:00:00 | 22:00:00 |
| taxiAllowanceTime | Trigger time | 24:00:00 | 24:00:00 |
| offDayPriceBelow4Hr | Off day <4hrs | ₹450 | ₹600 |
| offDayPriceAbove4Hr | Off day ≥4hrs | ₹600 | ₹600 |

### mx_user_off_days (Weekly Off Days)
| userID | weekdayNo | Day |
|--------|-----------|-----|
| 1 (Dilkhush) | 7 | Sunday |
| 2 (Suraj) | 6 | Saturday |

---

## 7. Testing Checklist

- [x] Dilkhush early check-in (before 10 AM) - Mark In button shows
- [x] Suraj early check-in (before 9 AM) - Mark In button shows
- [x] Dilkhush late check-out (after 8 PM) - Mark Out button shows
- [x] Suraj late check-out (after 7 PM) - Mark Out button shows
- [x] Cross-midnight checkout (e.g., 2 AM) - Correctly assigned to previous day
- [x] Off-day mark in/out for Dilkhush (Sunday)
- [x] Off-day mark in/out for Suraj (Saturday)
- [x] Off-day overtime calculation (flat rate + OT)
- [x] Email notifications sent on early check-in
- [x] Email notifications sent on late check-out
- [x] Cron records properly updated by manual mark in

---

## 8. Files Modified Summary

| File | Changes |
|------|---------|
| `xsite/mod/driver/x-home.php` | Driver-specific timings, off-day detection, button logic |
| `xsite/mod/driver/x-driver.inc.php` | `markIn()` and `markOut()` with driver-specific validation |
| `xadmin/mod/driver-management/x-driver-management.inc.php` | `overtimeManagement()` with off-day flat rate + overtime |
| `core/brevo.inc.php` | Email recipient changed to paritosh.ajmera@gmail.com |

---

*Document generated: December 30, 2025*
