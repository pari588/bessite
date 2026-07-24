# Driver Management Module

## Overview
The Driver Management module handles driver attendance tracking, overtime calculation, and payment settlement. It consists of a frontend driver portal (xsite) and backend admin panel (xadmin).

## Module Structure

```
/xsite/mod/driver/           # Frontend Driver Portal
├── x-driver.inc.php         # Backend functions (login, markIn, markOut)
├── x-home.php               # Driver home page with Mark In/Out buttons
├── x-login.php              # Driver PIN login page
├── pwa/                     # Progressive Web App assets
│   ├── manifest.json
│   ├── sw.js
│   └── icons/
└── js/
    └── x-driver.inc.js      # Frontend JavaScript

/xadmin/mod/driver-management/   # Admin Panel
├── x-driver-management.inc.php  # Backend functions (add, update, settle, overtime calc)
├── x-list.php                   # List view of all driver records
├── x-form.php                   # Add/Edit overtime record form
└── inc/
    └── js/
        └── x-driver-management.inc.js
```

## Business Logic

### Standard Work Hours
Configurable per driver in `mx_user` table:

| Driver | Shift Start | Shift End | Off Day | OT Rate |
|--------|-------------|-----------|---------|---------|
| Dilkhush | 10:00 AM | 8:00 PM | Sunday | Rs. 75/hr |
| Suraj | 9:00 AM | 7:00 PM | Saturday | Rs. 75/hr |

### Overtime Types

#### 1. Early Overtime (Mark In)
- Driver arrives **before 10:00 AM**
- Mark In available from **6:00 AM to 10:00 AM**
- Overtime calculated from arrival time to 10:00 AM

#### 2. Late Overtime (Mark Out)
- Driver leaves **after 8:00 PM**
- Mark Out available from **8:00 PM to 6:00 AM (next day)**
- Overtime calculated from 8:00 PM to departure time

### Time Windows
```
12 AM  ----[Mark Out Window]----  6 AM  ----[Mark In Window]----  10 AM
                                                                    |
                                                              Normal Shift
                                                                    |
8 PM  ----[Mark Out Window]----  12 AM  ----[continues]----  6 AM
```

### Overnight Shift Handling
- If current time is **before 6:00 AM**, it's considered previous day's shift
- Mark Out at 2:00 AM on Dec 28 = Overtime for Dec 27's shift
- `$relevantDate = ($currentHour < 6) ? date('Y-m-d', strtotime('-1 day')) : date('Y-m-d');`

## Allowances & Rates

| Allowance | Rate | Condition |
|-----------|------|-----------|
| Overtime | Rs. 75/hr | After 8:00 PM |
| Dinner | Rs. 150 (fixed) | After 10:00 PM |
| Taxi | Rs. 100 (fixed) | After 12:00 AM (midnight) |
| Sunday (≤4 hrs) | Rs. 450 | Working on off-day |
| Sunday (>4 hrs) | Rs. 600 | Working on off-day |

**Note:** Rates are configurable per driver in `mx_user` table.

## Database Tables

### mx_driver_management
| Field | Type | Description |
|-------|------|-------------|
| driverManagementID | INT | Primary key |
| userID | INT | FK to mx_user |
| dmDate | DATE | Date of record |
| fromTime | DATETIME | Check-in time |
| toTime | DATETIME | Check-out time |
| recordType | INT | 1=Cron (auto), 2=Manual (app) |
| overtimeHrs | DECIMAL | Calculated OT hours |
| totalOvertimePay | DECIMAL | OT hours × rate |
| dinnerAllowance | DECIMAL | Dinner amount |
| taxiAllowance | DECIMAL | Taxi amount |
| sunAllowance | DECIMAL | Sunday/off-day amount |
| totalPay | DECIMAL | Total payment |
| expenseAmt | DECIMAL | Other expenses |
| otherExpense | TEXT | Expense description |
| supportingDoc | VARCHAR | Uploaded document |
| isSettled | TINYINT | 0=Pending, 1=Settled |
| isVerify | TINYINT | 0=Unverified, 1=Verified |
| status | TINYINT | 1=Active, 0=Deleted |

### mx_user (Driver-related fields)
| Field | Description |
|-------|-------------|
| userID | Primary key |
| userName | Driver name |
| userLoginOTP | 4-digit PIN for driver login |
| userFromTime | Shift start time (default 10:00) |
| userToTime | Shift end time (default 20:00) |
| workingHrs | Standard working hours (default 10) |
| overtimeAllowance | OT rate per hour |
| dinnerTime | Time after which dinner applies |
| dinnerAllowance | Dinner amount |
| taxiAllowanceTime | Time after which taxi applies |
| taxiAllowance | Taxi amount |
| offDayPriceBelow4Hr | Sunday rate ≤4 hrs |
| offDayPriceAbove4Hr | Sunday rate >4 hrs |

### mx_user_off_days
| Field | Description |
|-------|-------------|
| userOffDayID | Primary key |
| userID | FK to mx_user |
| weekdayNo | 1=Mon, 2=Tue, ..., 7=Sun |

## Key Functions

### Frontend (x-driver.inc.php)

#### `driverLogin()`
- Validates 4-digit PIN against `userLoginOTP` in mx_user
- Sets session: `DRIVER_LOGIN_OTP`, `USER_ID`, `USER_NAME`

#### `markIn()`
- Blocked before 6:00 AM
- Creates new record with `fromTime` = current time
- Sends email notification if before 10:00 AM (early overtime)

#### `markOut()`
- Determines relevant date (today or yesterday if before 6 AM)
- If no existing record, creates one with `fromTime = 10:00 AM`
- Calls admin backend to update record and calculate overtime
- Sends email notification for late checkout

#### `driverLogout()`
- Clears driver session variables

### Backend (x-driver-management.inc.php)

#### `overtimeManagement()`
Calculates all allowances based on times:
```php
// Overtime hours = Total hours worked - Standard working hours
$data["overtimeHrs"] = $totalWorkingHrs - $WORKINGHRS;

// Overtime pay
$data["totalOvertimePay"] = $OVERTIMEALLOW * $data["overtimeHrs"];

// Dinner (after 10 PM)
if ($outgoingTime >= $DATIME) {
    $data["dinnerAllowance"] = $DINNERALLOW;
}

// Taxi (after midnight)
if ($outgoingTime >= $TAXIALLOTIME) {
    $data["taxiAllowance"] = $TAXIALLOW;
}
```

#### `settlePayment()`
- Receives array of driverManagementIDs to settle
- Creates petty cash credit entry
- Creates petty cash debit entry
- Creates voucher with detailed narration
- Marks records as `isSettled = 1`

#### `verifyMarkin()`
- Admin verification of driver mark-in records

#### `isWeekend()`
- Checks if date falls on driver's configured off-days

#### `getUserSetting()`
- Loads driver-specific allowance rates from mx_user

## Frontend UI Logic (x-home.php)

```php
// Determine time windows (uses driver-specific shift times)
$currentHour = (int)date('H');
$currentTime = date('H:i');
$driverShiftStart = $userData['userFromTime']; // e.g., "10:00" or "09:00"
$driverShiftEnd = $userData['userToTime'];     // e.g., "20:00" or "19:00"

$isBeforeMorningCutoff = ($currentHour < 6);
$isEarlyOvertimeWindow = ($currentHour >= 6 && $currentTime < $driverShiftStart);
$isLateOvertimeWindow = ($currentTime >= $driverShiftEnd || $currentHour < 6);

// Determine relevant date for overtime
// Before 6 AM = previous day's shift still active
$relevantDate = ($currentHour < 6) ? date('Y-m-d', strtotime('-1 day')) : date('Y-m-d');

// Check for open shifts (manual records with no toTime)
$hasOpenShift = /* recordType=2 with toTime=NULL for relevantDate */;

// Check for cron records needing Mark Out (cron records with no toTime)
$hasCronRecordNeedingMarkOut = /* recordType=1 with toTime=NULL for relevantDate */;

// Button Display Logic
if ($isOffDay) {
    // Off day logic
    $showMarkIn = !$hasOpenShift && !$hasCronRecordNeedingMarkOut && !$hasCompletedRecord && ($currentHour >= 6);
    $showMarkOut = $hasOpenShift || $hasCronRecordNeedingMarkOut || ($isLateOvertimeWindow && $hasTodayRecord && !$hasCompletedRecord);
} else {
    // Normal working day
    $showMarkOut = $hasOpenShift || $hasCronRecordNeedingMarkOut || ($isLateOvertimeWindow && !$hasCompletedRecord);
    $showMarkIn = !$showMarkOut && $isEarlyOvertimeWindow && (!$hasTodayRecord || $isCronRecordWithoutManualUpdate);
}
```

## Email Notifications

Uses Brevo API for overtime notifications.
**File:** `/core/brevo.inc.php`
**Function:** `sendDriverOvertimeNotification()`

Triggered on:
- Early check-in (before 10 AM)
- Late check-out (after 8 PM)

## Settlement Workflow

1. Admin reviews unsettled overtime records in xadmin
2. Selects records to settle (checkboxes)
3. Clicks "Settle Payment" button
4. System creates:
   - Petty Cash Credit entry (adding funds)
   - Petty Cash Debit entry (driver welfare expense)
   - Voucher with detailed narration
5. Records marked as settled (`isSettled = 1`)

## Cron Jobs

**File:** `/xsite/mx-crons.php`
**Schedule:** Both run `@hourly`

### autoMarkin
Creates daily attendance records for drivers.
- Runs every hour but only creates records **after driver's shift start time**
- Dilkhush: Record created after 10:00 AM IST
- Suraj: Record created after 9:00 AM IST
- Skips driver's configured off-days
- Creates record with `fromTime = shift start`, `toTime = NULL`, `recordType = 1`

```php
// Only create record after driver's shift start time
$userFromTime = date("H:i:s", strtotime($v["userFromTime"]));
if ($currentTime < $userFromTime) {
    continue; // Too early, skip
}
```

### autoMarkout
Sets default checkout time for records not manually marked out.
- Checks **previous day's** records
- If `toTime` is empty/NULL, sets it to driver's shift end time (e.g., 20:00)
- Calls the overtime calculation endpoint to update totals

**Cron URLs:**
```
https://www.bombayengg.net/xsite/mx-crons.php?xAction=autoMarkin
https://www.bombayengg.net/xsite/mx-crons.php?xAction=autoMarkout
```

## Related Modules

- **Petty Cash Book:** `/xadmin/mod/petty-cash-book/`
- **Voucher:** `/xadmin/mod/voucher/`
- **User Management:** `/xadmin/mod/user/`

## Common Issues & Fixes

### Mark Out Not Showing After Midnight
- Check `$isLateOvertimeWindow` logic includes `$currentHour < 6`
- Verify `$relevantDate` calculation for overnight shifts

### Mark Out Not Showing When Crossing Into Off Day
- **Cause:** Off day logic checked for "today's" record instead of "relevant date" record
- **Fix:** Added `$hasCronRecordNeedingMarkOut` check that uses `$relevantDate`
- Example: Mark Out at 12:20 AM on Sunday (off day) for Saturday's shift

### Cron Records Not Showing Mark Out Button
- **Cause:** Open shift query only checked `recordType = 2` (manual records)
- **Fix:** Added separate check for `recordType = 1` (cron records) with NULL toTime
- Both manual and cron records now show Mark Out button when needed

### Old Mark-In Showing Next Day
- Query must scope to `$relevantDate` only
- Check for completed records (toTime not null) for that date

### Overtime Not Calculating
- Verify `fromTime` is set to actual arrival or 10:00 AM
- Check `toTime` is properly recorded
- Ensure driver's rates are configured in mx_user

### Cron Creating Records Too Early (Midnight)
- **Cause:** autoMarkin ran at midnight creating next day's record
- **Fix:** autoMarkin now checks if current time >= driver's shift start time
- Records only created after 10 AM for Dilkhush, 9 AM for Suraj

### Cached PDF Shows Old Data
- Delete PDF from `/uploads/voucher/`
- Clear `voucherFile` field in mx_voucher table

## Configuration

### Adding New Driver
1. Create user in `/xadmin/mod/user/`
2. Set `userLoginOTP` (4-digit PIN)
3. Configure shift times and allowance rates
4. Set off-days in mx_user_off_days

### Changing Allowance Rates
Update in mx_user table or via User edit form:
- `overtimeAllowance` - OT rate per hour
- `dinnerAllowance` - Dinner amount
- `taxiAllowance` - Taxi amount
- `offDayPriceBelow4Hr` / `offDayPriceAbove4Hr` - Sunday rates

## Change History
- **Jan 2026:** Fixed Mark Out not showing when crossing midnight into off day
- **Jan 2026:** Added `$hasCronRecordNeedingMarkOut` for cron records needing Mark Out
- **Jan 2026:** Fixed autoMarkin to only create records after driver's shift start time
- **Dec 2025:** Driver-specific shift timings (Dilkhush 10AM-8PM, Suraj 9AM-7PM)
- **Dec 2025:** Weekly off day support with flat rate + overtime calculation
- **Dec 2024:** Fixed overnight shift handling (Mark Out after midnight)
- **Dec 2024:** Added time window logic for Mark In/Mark Out buttons
- **Dec 2024:** Fixed `fromTime` to always be 10:00 AM for late-only overtime
- **Dec 2024:** Added detailed narration to settlement vouchers
- **Dec 2024:** Integrated Brevo email notifications for overtime
