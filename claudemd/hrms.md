# HR Portal Implementation Plan (Revised)

---

## IMPORTANT: Reference Documentation

> **Before working on this HRMS module, read these essential documents:**
>
> ### 📖 [SITE_STRUCTURE_OVERVIEW.md](SITE_STRUCTURE_OVERVIEW.md)
> Complete site architecture reference:
> - Directory structure (`/core/`, `/xsite/`, `/xadmin/`, `/uploads/`)
> - Configuration files (`config.inc.php`, `core.inc.php`)
> - Database connection patterns (`$DB->vals`, `$DB->types`, `$DB->sql`)
> - Frontend (xsite) architecture & routing
> - Backend (xadmin) module structure
> - Template class usage (`$TPL`)
> - Security features (CSRF, prepared statements)
> - Image upload paths and sizes
> - GitHub deployment workflow
>
> ### 📖 [XADMIN_MODULE_CREATION.md](XADMIN_MODULE_CREATION.md)
> Step-by-step guide for creating admin modules:
> - Database table naming (`mx_{module}`, `{module}ID`, `status` column)
> - File structure (`x-{module}.inc.php`, `x-{module}-list.php`, `x-{module}-add-edit.php`)
> - Controller logic (`add{Module}()`, `update{Module}()`, `setModVars()`)
> - List view with search filters (`$arrSearch`, `$MXCOLS`, `getMAction()`)
> - Form view with field types (`text`, `editor`, `file`, `date`, `select`)
> - Menu registration in `mx_admin_menu`
> - Sub-tables (one-to-many) with `getFormG()`
> - File upload handling and validation

---

## Overview

Build a comprehensive HR Portal with:
- **Backend (xadmin):** Employee onboarding, salary management, document management, attendance sync, leave management
- **Frontend (xsite):** Employee self-service portal with Email+OTP login
- **Manager View:** Managers can view & manage their assigned team members (labour/non-computer users)

**Scale:** Under 25 employees | **Architecture:** Extend existing `x_admin_user` system

---

## IMPLEMENTATION STATUS

### Phase 1: COMPLETED (Commit: f4c628d)

| Module | Status | Location |
|--------|--------|----------|
| Database Tables | DONE | `database_migrations/hrms_migration_001.sql` |
| Admin User HR Fields | DONE | `/xadmin/core-admin/mod/admin-user/` |
| HRMS Menu Structure | DONE | Menu IDs 69-75 in `mx_x_admin_menu` |
| Attendance Module | DONE | `/xadmin/mod/attendance/` |
| Salary Structure | DONE | `/xadmin/mod/salary-structure/` |
| Salary Slip | DONE | `/xadmin/mod/salary-slip/` |
| Salary Advance | DONE | `/xadmin/mod/salary-advance/` |
| Employee Document | DONE | `/xadmin/mod/employee-document/` |
| HR Email Settings | DONE | `/xadmin/mod/hr-email-settings/` |

**Employee-Specific Settings Added to `mx_x_admin_user`:**
- `workStartTime` - Override global check-in time
- `workEndTime` - Override global check-out time
- `lateGraceMinutes` - Override global late grace
- `paidLeaveDays`, `casualLeaveDays`, `sickLeaveDays` - Per-employee leave quotas

### Phase 2: COMPLETED

| Module | Status | Location |
|--------|--------|----------|
| Employee Portal - Login | DONE | `/xsite/mod/hrms/x-login.php` - Email+OTP auth |
| Employee Portal - Dashboard | DONE | `/xsite/mod/hrms/x-home.php` - Own + team view |
| Employee Portal - Attendance | DONE | `/xsite/mod/hrms/x-attendance.php` - View + remarks |
| Employee Portal - Salary | DONE | `/xsite/mod/hrms/x-salary.php` - View/download slips |
| Employee Portal - Documents | DONE | `/xsite/mod/hrms/x-documents.php` - View docs |
| Employee Portal - Team | DONE | `/xsite/mod/hrms/x-team.php` - Manager team view |
| Employee Portal - Profile | DONE | `/xsite/mod/hrms/x-profile.php` - View profile |
| Portal Header/Footer | DONE | `/xsite/mod/hrms/header-hrms.php`, `footer-hrms.php` |
| Backend AJAX Handler | DONE | `/xsite/mod/hrms/x-hrms.inc.php` |

**Portal Features Implemented:**
- Email + OTP authentication via Brevo
- Session management with HRMS-specific session vars
- Responsive design with mobile bottom navigation
- Manager view for team attendance/leaves/remarks
- CSS variables for consistent theming

### Phase 2.5: BUG FIXES COMPLETED (January 2026)

| Fix | Status | Details |
|-----|--------|---------|
| Team Page API URLs | DONE | Fixed all fetch() calls to use `/xsite/mod/hrms/x-hrms.inc.php` |
| GET Handler DB Init | DONE | Added DB config includes to GET ajax handler |
| Leave Requests SQL | DONE | Fixed duplicate parameter binding in `getTeamLeaveRequests()` |
| Documents Data Format | DONE | Fixed `getEmployeeDocuments()` to return properly formatted data |
| Approve/Reject Leave | DONE | Fixed POST body to include `xAction` parameter |

### Phase 2.6: ENHANCED ATTENDANCE REPORTING - COMPLETED (January 2026)

| Module | Status | Location |
|--------|--------|----------|
| Database Migration | DONE | `database_migrations/hrms_attendance_enhanced_002.sql` |
| Admin Dashboard | DONE | `/xadmin/mod/attendance/x-attendance-dashboard.php` |
| Attendance API | DONE | `/xadmin/mod/attendance/x-attendance-api.php` |
| Export Handler | DONE | `/xadmin/mod/attendance/x-attendance-export.php` |
| Reports Page | DONE | `/xadmin/mod/attendance/x-attendance-reports.php` |
| Employee Portal Calendar | DONE | `/xsite/mod/hrms/x-attendance.php` (enhanced) |

**Features Implemented:**
- **Admin Dashboard**: Real-time KPI cards (Present, Absent, Late, Leave), Chart.js visualizations (trend line, department donut), live attendance table with search, alert cards for late/absent
- **6 Report Types**: Daily Attendance, Monthly Muster Roll, Payroll Summary, Late/Early Report, Overtime Report, Absenteeism Report
- **Export Formats**: Excel (PhpSpreadsheet with HTML fallback), PDF (MPDF with HTML fallback), CSV for payroll
- **Employee Portal**: Calendar view with color-coded status badges (P/A/L/H/WO/HD/LT), day detail modal, personal download option
- **Database Enhancements**: shift_master, attendance_punch_log, holiday_master, overtime_rates, report_templates, scheduled_reports tables

**Next Steps to Activate:**
1. Run database migration: `database_migrations/hrms_attendance_enhanced_002.sql`
2. Run menu migration: `database_migrations/hrms_attendance_menu_003.sql`
3. Run pages migration: `database_migrations/hrms_pages_migration.sql` (if not already run)
4. (Optional) Install PhpSpreadsheet for proper Excel exports
5. (Optional) Install MPDF for styled PDF exports

### Phase 2.7: EMPLOYEE PORTAL REPORTS - NEEDS FIXES (January 2026)

| Module | Status | Location |
|--------|--------|----------|
| Reports Page | CREATED | `/xsite/mod/hrms/x-reports.php` |
| Report API Handlers | CREATED | Added to `/xsite/mod/hrms/x-hrms.inc.php` |
| Header Navigation | UPDATED | Reports link in `header-hrms.php` |
| Mobile Navigation | UPDATED | Reports link in `footer-hrms.php` |
| Menu SQL Migration | CREATED | `database_migrations/hrms_attendance_menu_003.sql` |

### Phase 2.8: SALARY PROCESSING & ADVANCES - COMPLETED (January 2026)

| Module | Status | Location |
|--------|--------|----------|
| Salary Processing UI | DONE | `/xsite/mod/hrms/x-salary-processing.php` |
| Salary Advances Tab | DONE | Tabbed UI within salary processing page |
| Database Migration | DONE | `database_migrations/hrms_salary_advance.sql` |
| Backend Functions | DONE | Added to `/xsite/mod/hrms/x-hrms.inc.php` |

**Salary Advance Features Implemented:**
- **Tabbed Interface**: "Salary Processing" and "Salary Advances" tabs
- **Add New Advance**: Modal form with employee selection, amount, date, reason
- **Repayment Types**: One-time, Monthly, Quarterly, Half-Yearly, Yearly, Custom
- **Custom Frequency**: Specify exact months (comma-separated, e.g., "1,4,7,10")
- **Approval Workflow**: Pending → Approved → Repaying → Cleared
- **Record Repayments**: Regular Installment, Partial Payment, Full & Final Settlement
- **Auto Deduction**: `getAdvanceDeductionForSalary()` calculates monthly deduction based on frequency
- **Progress Tracking**: Visual progress bars showing repayment status

**Database Tables Created:**
```sql
-- mx_salary_advance: Main advance records
-- Fields: advanceID, userID, advanceAmount, advanceDate, reason,
--         deductionFrequency (one_time/monthly/quarterly/half_yearly/yearly/custom),
--         customMonths, monthlyDeduction, totalDeducted, remainingAmount,
--         advanceStatus (pending/approved/rejected/repaying/cleared), etc.

-- mx_salary_advance_repayment: Individual repayment tracking
-- Fields: repaymentID, advanceID, userID, repaymentAmount, repaymentDate,
--         repaymentMonth, repaymentYear, salarySlipID, repaymentMode, etc.
```

**Backend Functions Added:**
| Function | Purpose |
|----------|---------|
| `getSalaryAdvances()` | List all advances with stats (total, active, pending counts) |
| `saveSalaryAdvance()` | Create or update salary advance |
| `approveSalaryAdvance()` | Approve pending advance with approval details |
| `recordAdvanceRepayment()` | Record individual repayment/deduction |
| `getAdvanceDeductionForSalary($userID, $month, $year)` | Calculate deduction for salary processing |
| `shouldDeductThisMonth()` | Frequency-based deduction logic |

### Phase 2.9: EARNED LEAVE ACCRUAL - COMPLETED (January 2026)

| Task | Status | Details |
|------|--------|---------|
| Accrual Calculation | DONE | Based on actual days worked in FY |
| Updated getLeaveBalance() | DONE | Now calculates EL dynamically |
| Added calculateAccruedEarnedLeave() | DONE | Core accrual logic |

**Accrual Rules (Financial Year: April to March):**
| Days Worked | Earned Leave |
|-------------|--------------|
| 30 days | 1 EL |
| 60 days | 2 EL |
| 90 days | 3 EL |
| 120 days | 4 EL |
| 150 days | 5 EL |
| 180 days | 6 EL |
| 210 days | 7 EL |
| 240 days | 8 EL |
| 270 days | 9 EL |
| 300 days | 10 EL |
| 330 days | 11 EL |
| 365 days | 12 EL (max) |

**Calculation Logic:**
- Counts `present` days from `mx_attendance` table
- Half days count as 0.5 days
- Financial year runs April 1 to March 31
- Uses `dateOfJoining` column (not joiningDate)

### Phase 2.10: UI/UX FIXES - COMPLETED (January 2026)

| Fix | Status | Details |
|-----|--------|---------|
| Apply Leave Link | DONE | Fixed from `/leave/apply/` to `/hrms/leave/` in x-home.php |
| Rupee Symbol (₹) | DONE | All currency displays use ₹ instead of $ |
| Salary Advances Icon | DONE | Changed SVG from $ to ₹ in tab button |

### Phase 2.11: ATTENDANCE & REPORTING FIXES - COMPLETED (January 10, 2026)

| Fix | Status | Details |
|-----|--------|---------|
| Saturday Weekly Off | FIXED | Saturday is a working day (10 AM - 4 PM), only Sunday is off |
| Attendance Page UI | FIXED | Removed Saturday from weekly off display in calendar and popup |
| Reports isSaturdayOff | DONE | Added `isSaturdayOff` column support in all report functions |
| Non-Biometric Auto-Attendance | FIXED | Cron job scheduled at 10:05 AM daily |
| Historical Leaves | DONE | Added for Ganesh, Manish, Sakshi |
| Official Trip/On Duty | DONE | New leave type (ID=16) with `countsAsPresent=1` |
| Half Day Leave Types | DONE | Early Leave (ID=17), Late Arrival (ID=18) |
| Grace Period Update | DONE | Changed to 45 mins for Sakshi, Manish, Ganesh |
| Working Hours Calculation | FIXED | On-the-fly calculation when DB value is 0 |
| Financial Year Filter | DONE | Leave History report now uses FY filter (Apr-Mar) |
| Report PDF Titles | FIXED | Dynamic titles based on report type (Leave Report, etc.) |

**Non-Biometric Employees:**
| User | ID | Weekday Hours | Saturday Hours |
|------|-----|---------------|----------------|
| Prakash Patil | 18 | 10 AM - 6 PM | 10 AM - 4 PM |
| Pravin Jadhav | 19 | 10 AM - 6 PM | 10 AM - 4 PM |

**Auto-Attendance Cron:**
```
# Runs daily at 10:05 AM
5 10 * * * /usr/bin/php /home/bombayengg/public_html/cron/hrms-auto-attendance.php >> /home/bombayengg/logs/hrms-auto-attendance.log 2>&1
```

**Database Columns Added:**
- `mx_x_admin_user.isSaturdayOff` - Flag for employees with Saturday off (currently unused)
- `mx_x_admin_user.isNonBiometric` - Flag for non-biometric employees
- `mx_leave_type.countsAsPresent` - Flag for leave types that count as present (e.g., On Duty)

**Report Functions Updated for isSaturdayOff:**
- `getMonthlyAttendanceReport()` - Individual monthly report
- `getSummaryReport()` - Individual summary
- `getDetailedAttendanceReport()` - Individual detailed
- `getMasterMonthlyReport()` - All employees monthly
- `getMasterSummaryReport()` - All employees summary
- `getMasterDetailedReport()` - All employees detailed

**Financial Year Filter (Leave History):**
- Leave History report uses Financial Year (April to March) instead of month/year
- FY dropdown shows current and 2 previous years: "FY 2025-2026 (Apr 2025 - Mar 2026)"
- Backend accepts `fyYear` parameter and calculates date range automatically
- Files updated: `x-reports.php`, `x-hrms.inc.php` (`getEmployeeReport`, `downloadEmployeeReport`)

**Report PDF Titles:**
| Report Type | PDF Title |
|-------------|-----------|
| monthly | Monthly Attendance Report |
| summary | Attendance Summary Report |
| late_early | Late/Early Report |
| leave | Leave Report |
| detailed | Detailed Attendance Report |

### Phase 2.12: PWA IMPLEMENTATION - COMPLETED (January 10, 2026)

The HRMS portal has been converted to a full Progressive Web App (PWA) with offline support, installability, and push notifications.

| Feature | Status | Details |
|---------|--------|---------|
| Web App Manifest | DONE | `/xsite/mod/hrms/manifest.json` |
| PWA Icons | DONE | 8 sizes (72-512px) in `/xsite/mod/hrms/icons/` |
| Service Worker | DONE | `/hrms-sw.js` with caching strategies |
| Offline Support | DONE | Network-first with offline fallback |
| Install Prompt | DONE | Banner UI with Install/Dismiss buttons |
| Offline Indicator | DONE | Yellow banner when connection lost |
| Update Toast | DONE | Notification when new version available |
| Push Notifications | DONE | Infrastructure ready (needs VAPID keys) |

**Files Created:**

| File | Purpose |
|------|---------|
| `/xsite/mod/hrms/manifest.json` | PWA manifest with app metadata, icons, shortcuts |
| `/xsite/mod/hrms/generate-icons.php` | PHP script to generate PWA icons |
| `/xsite/mod/hrms/icons/icon-*.png` | 8 icon sizes: 72, 96, 128, 144, 152, 192, 384, 512 |
| `/hrms-sw.js` | Service worker with caching & offline support |

**Files Modified:**

| File | Changes |
|------|---------|
| `header-hrms.php` | PWA meta tags, manifest link, apple-touch-icons |
| `footer-hrms.php` | Service worker registration, install banner, offline indicator |

**Service Worker Features:**
- **Cache First**: Static assets (JS, CSS, images, fonts)
- **Network First**: API requests and HTML pages
- **Offline Fallback**: Custom offline page when network unavailable
- **Background Sync**: Queue offline actions for later sync
- **Push Notifications**: Ready for server-side implementation
- **IndexedDB Storage**: Stores pending offline actions

**Caching Strategy:**
```javascript
STATIC_CACHE = 'hrms-static-v1.0.0'   // JS, CSS, images
DYNAMIC_CACHE = 'hrms-dynamic-v1.0.0' // Dynamic content
DATA_CACHE = 'hrms-data-v1.0.0'       // API responses
```

**PWA Manifest Shortcuts:**
- Attendance: `/hrms/attendance/`
- Leave: `/hrms/leave/`
- Salary: `/hrms/salary/`

**Install Prompt Behavior:**
1. Banner appears 3 seconds after page load (if installable)
2. "Not now" dismisses for 24 hours (stored in localStorage)
3. After install, banner hidden permanently

**Offline Indicator:**
- Yellow banner below header when offline
- Automatically shows/hides based on connection status
- Text: "You're offline. Some features may be limited."

**Update Notification:**
- Toast appears when new service worker installed
- "Update" button triggers immediate update and reload
- Automatic reload when service worker takes control

**Push Notifications (Server Setup Required):**
To enable push notifications, generate VAPID keys:
```bash
npx web-push generate-vapid-keys
```
Store keys and implement server-side push logic.

**PWA Icon Design:**
- Single letter "B" on blue (#2563eb) background
- Large font size (60% of icon size) for readability
- Generated via `/xsite/mod/hrms/generate-icons.php`

**PWA Fixes Applied:**
| Issue | Fix |
|-------|-----|
| Service worker 404 | Added `/hrms-sw.js` exception in `.htaccess` |
| Manifest 403 | Fixed file permissions (644) for manifest.json |
| Icons unreadable | Changed from "BES" to "B" for better visibility |

**IMPORTANT - Currency Symbol Standard:**
> **Always use ₹ (Indian Rupee) symbol throughout HRMS module.**
> - JavaScript: `formatCurrency()` function uses `'₹' + amount.toLocaleString('en-IN')`
> - SVG Icon: Bootstrap Icons rupee path: `<path d="M4 3.06h2.726c1.22 0 2.12.575 2.325 1.724H4v1.051h5.051C8.855 7.001 8 7.558 6.788 7.558H4v1.317L8.437 14h2.11L6.095 8.884h.855c2.316-.018 3.465-1.476 3.688-3.049H12V4.784h-1.345c-.08-.778-.357-1.335-.793-1.732H12V2H4z"/>`
> - Never use $ symbol in any HRMS UI element

**Employee Portal Report Features (Intended):**
- **4 Report Types for Employees**:
  1. Monthly Attendance - Day-wise grid with status codes
  2. Summary Report - Present/Absent/Leave/Hours summary
  3. Late/Early Report - Details of late arrivals with minutes
  4. Leave History - All leaves with type, dates, status
- **Download Formats**: Excel (.xls) and PDF (HTML print)
- **Interactive Preview**: Generate and preview before download
- **Mobile Responsive**: Works on all devices

**Admin Panel Updates:**
- Dashboard accessible via `?mod=attendance&pg=dashboard`
- Reports accessible via `?mod=attendance&pg=reports`
- Menu entries added via migration SQL

---

### Phase 2.13: UI/MOBILE FIXES - COMPLETED (January 12, 2026)

| Fix | Status | File |
|-----|--------|------|
| Team grid mobile layout | DONE | `/xsite/mod/hrms/x-home.php` |
| Team member card spacing | DONE | Reduced gap, padding, font sizes |
| Team count letter-spacing | DONE | Fixed "(9 members)" spacing issue |
| OTP input overflow on mobile | DONE | `/xsite/mod/hrms/x-login.php` |
| Login card padding on small screens | DONE | Responsive padding added |

**Team Grid Changes:**
- Single column on mobile (< 480px), 2 columns on tablet, 3 columns on desktop
- Reduced gap from `var(--space-md)` to `8px`
- Reduced card padding from `12px` to `10px`
- Reduced avatar size from `40px` to `36px`
- Added text truncation to team role

**OTP Input Changes:**
- Reduced input size from `48x56px` to `42x50px`
- Reduced gap from `10px` to `6px`
- Added smaller breakpoint for screens < 360px (`38x46px`)
- Added responsive login card padding for screens < 400px

### Phase 2.14: MONTHLY EMAIL AUTOMATION - COMPLETED (January 12, 2026)

| Task | Status | Details |
|------|--------|---------|
| Monthly Cron Job | DONE | `/cron/hrms-monthly-attendance-email.php` |
| Individual Employee Emails | DONE | Each employee receives their attendance report |
| Individual PDF Generation | DONE | Beautiful styled PDFs with company branding |
| Master Admin Batch Emails | DONE | All PDFs sent to admins in batches of 10 |
| Cron Schedule | DONE | Runs 1st of every month at 8:00 AM IST |

**What the cron does:**
1. **Generates detailed attendance PDFs** for all employees with:
   - Company logo and blue gradient header
   - Summary cards (Present, Absent, Leave, Late, Hours, Working Days)
   - Day-by-day table with scheduled/actual check-in/out times
   - Late arrival and early checkout indicators
   - Statistics legend and footer

2. **Sends individual emails** to each employee with their own report

3. **Batches all individual PDFs** and sends to master admins:
   - Recipients: `manishbeskkc@gmail.com`, `paritosh.ajmera@gmail.com`
   - 10 PDFs per email to avoid attachment limits
   - Each batch email lists which employees are included

4. **Sends master report** to HR admins (summary of all employees)

**Cron Schedule:**
```
30 2 1 * * /usr/bin/php /home/bombayengg/public_html/cron/hrms-monthly-attendance-email.php
```
(8:00 AM IST on 1st of every month)

**PDF Storage:** `/uploads/attendance-reports/YYYY-MM/`

**Key Functions Added:**
| Function | File | Purpose |
|----------|------|---------|
| `getDetailedEmployeeAttendanceData()` | `hrms-monthly-attendance-email.php` | Full attendance with leaves, holidays, Saturday timings |
| `generateIndividualDetailedPDF()` | `hrms-monthly-attendance-email.php` | Beautiful PDF using mPDF |
| `sendMasterAdminBatchEmail()` | `hrms-monthly-attendance-email.php` | Send batched PDFs to admins |
| `buildMasterAdminBatchEmailHTML()` | `hrms-monthly-attendance-email.php` | Build email with employee list |

---

## PENDING UI IMPROVEMENTS

| Task | Priority | Description |
|------|----------|-------------|
| Attendance calendar | Medium | Better touch interactions, swipe for months |
| Leave application form | Low | Multi-day date picker improvements |
| Salary slip PDF | Low | Better print layout for mobile |
| Profile page | Low | Add profile photo upload |
| Dark mode | Low | Add dark theme toggle |

---

## PENDING ISSUES TO FIX

✅ **ALL CRITICAL ISSUES FIXED** (January 3, 2026)

| Issue | Status | Fix Applied |
|-------|--------|-------------|
| PHP syntax error in x-attendance-export.php | ✅ FIXED | Moved `use` statements to top of file |
| Table name `leave_requests` vs `leave_request` | ✅ FIXED | Changed to `leave_request` |
| Wrong DB prefix `bombayengg_` in migrations | ✅ FIXED | Changed to `mx_` |
| Missing columns (displayName, empCode, deptID) | ✅ FIXED | Using aliases in SQL queries |
| Missing leave_request/leave_types tables | ✅ FIXED | Added to migration file |
| SQL injection vulnerabilities | ✅ FIXED | Using parameterized queries |
| View/Stored procedure column names | ✅ FIXED | Updated to use correct columns |

**Next Steps:**
1. Run database migration: `database_migrations/hrms_attendance_enhanced_002.sql`
2. Run menu migration: `database_migrations/hrms_attendance_menu_003.sql`
3. Test employee portal and admin dashboard

---

### Phase 3: IN PROGRESS

#### 3.1 Camsunit Biometric Integration - COMPLETED (January 2026)

| Task | Status | Location |
|------|--------|----------|
| Camsunit API Wrapper | DONE | `/core/camsunit.inc.php` |
| Callback Handler | DONE | `/core/camsunit-callback.php` |
| Test Mode Config | DONE | `CAMS_TEST_MODE` in config.inc.php |
| Employee Sync Functions | DONE | `assignBiometricID()`, `bulkAssignBiometricIDs()`, `getSyncStatus()` |

**Available API Functions:**
- `loadPunchLog($fromDate, $toDate)` - Fetch punch logs from device
- `syncPunchLogsToDatabase()` - Sync punches to attendance table
- `assignBiometricID($userID)` - Assign biometric ID to employee (auto-generates EMP00001 format)
- `bulkAssignBiometricIDs()` - Assign IDs to all employees without one
- `getSyncStatus()` - Compare DB employees vs device users
- `getDeviceUsers()` - List users on device

**To Go Live:**
1. Set `CAMS_STGID` in config.inc.php (get from device setup)
2. Set `CAMS_TEST_MODE = false`
3. Configure device callback URL: `https://www.bombayengg.net/core/camsunit-callback.php`

---

#### 3.2 Leave Module Integration - COMPLETED (January 2026)

**Objective:** Keep existing database tables, create better admin UI and integrate with employee portal.

**Completed Implementation:**

| Task | Status | Location |
|------|--------|----------|
| Employee Leave Page | DONE | `/xsite/mod/hrms/x-leave.php` |
| Leave Balance API | DONE | `getLeaveBalance()` in `x-hrms.inc.php` |
| Leave Types API | DONE | `getLeaveTypes()` in `x-hrms.inc.php` |
| Leave History API | DONE | `getLeaveHistory()` in `x-hrms.inc.php` |
| Apply Leave API | DONE | `applyLeave()` in `x-hrms.inc.php` |
| Cancel Leave API | DONE | `cancelLeave()` in `x-hrms.inc.php` |
| Manager Approve API | DONE | `approveLeave()` - fixed to use `mx_leave` |
| Manager Reject API | DONE | `rejectLeave()` - fixed to use `mx_leave` |
| Team Leave Requests | DONE | `getTeamLeaveRequests()` - fixed to use `mx_leave` |
| Navigation Links | DONE | Header nav + mobile bottom nav |

**Employee Portal Features:**
- View leave balance (Casual, Sick, Earned, Total)
- Apply for leave with date range, leave type, day type (Full/Half), reason
- View leave history with status badges
- Cancel pending leave requests
- Automatic holiday detection (skips holidays in leave calculation)
- Overlap detection (prevents duplicate leave requests)

**Manager Portal Features:**
- View team's pending leave requests
- Approve/Reject with reason
- Updates both `mx_leave` and `mx_leave_details` tables

**Existing Database Tables (DO NOT MODIFY):**
| Table | Purpose |
|-------|---------|
| `mx_leave` | Leave applications (leaveID, userID, leaveType, fromDate, toDate, reason, leaveStatus) |
| `mx_leave_details` | Day-wise leave breakdown (leaveID, leaveDate, lType: 1=Full, 2=First Half, 3=Second Half) |
| `mx_leave_type` | Leave types master (leaveTypeID, leaveTypeName, allotedLeave) |
| `mx_leave_setting` | Financial year settings (FYStartDate, FYEndDate, totalLeave) |
| `mx_user_leaves` | User's monthly leave balance (yrBalanceLeaves, casualBalanceLeaves, sickBalanceLeaves) |

**Existing Admin Modules:**
| Module | Location | Purpose |
|--------|----------|---------|
| Employee Leave | `/xadmin/mod/employee-leave/` | Apply/manage leave requests |
| Leave Type | `/xadmin/mod/leave-type/` | Define leave types (CL, SL, EL, etc.) |
| Leave Setting | `/xadmin/mod/leave-setting/` | FY settings, total leaves per year |
| Monthly Leave Report | `/xadmin/mod/monthly-leave-report/` | Leave reports |

**Leave Status Flow:**
```
Pending → Approved/Disapproved/Cancel
         ↓
    UnpaidApproved (if unpaid leave)
         ↓
    Paid/Unpaid (final status)
```

**Integration Tasks:**

| Task | Priority | Description |
|------|----------|-------------|
| Portal Leave Page | HIGH | `/xsite/mod/hrms/x-leave.php` - Apply for leave, view history |
| Leave Balance Widget | HIGH | Show remaining CL/SL/EL on dashboard |
| Leave Application Form | HIGH | Select dates, type, half/full day, reason |
| Leave History List | MEDIUM | View all past leave requests with status |
| Cancel Leave | MEDIUM | Cancel pending leave requests |
| Manager Approval (Portal) | MEDIUM | Manager can approve/reject from portal |
| Leave Calendar | LOW | Visual calendar showing leaves |

**Admin UI Improvements Needed:**

| Current Issue | Improvement |
|---------------|-------------|
| Scattered modules | Single "HRMS Settings" with tabs |
| Basic leave type form | Add: isPaid, maxDays, carryForward, requiresApproval |
| No holiday integration | Link leave calendar with holidays |
| Manual balance update | Auto-calculate from leave requests |

---

#### 3.3 Admin HRMS Settings Panel - COMPLETED (January 2026)

**Objective:** Single unified admin panel for all HRMS settings instead of scattered modules.

**New Admin Module:** `/xadmin/mod/hrms-settings/`

| File | Purpose |
|------|---------|
| `x-hrms-settings.inc.php` | Backend controller with all AJAX handlers |
| `x-hrms-settings-list.php` | Unified tabbed UI for all settings |

**Menu Location:** HRMS > HRMS Settings (Menu ID: 78)

**Implemented Tabs:**
```
┌─────────────────────────────────────────────────────────────────────────┐
│  HRMS Settings                                                          │
├─────────────────────────────────────────────────────────────────────────┤
│  [General] [Holidays] [Shifts] [Leave Types] [Biometric] [Email]        │
└─────────────────────────────────────────────────────────────────────────┘
```

**Tab 1: Employees**
- List all employees with HR fields
- Add/Edit employee (bank details, PAN, Aadhaar, department, designation)
- Assign manager, shift, biometric ID
- Set individual leave quota (override defaults)

**Tab 2: Holidays**
- Holiday Master for the year
- Add: Date, Name, Type (National/Regional/Company), Half Day option
- Bulk import holidays
- Link: `mx_attendance_holidays` table (existing)

**Tab 3: Shifts**
- Create shifts with different timings
- Fields: Shift Name, Start Time, End Time, Grace Minutes, Break Duration
- Assign default shift or per-employee
- Link: `mx_shift_master` table

**Tab 4: Leave Types** (Improve existing `/xadmin/mod/leave-type/`)
- Uses existing `mx_leave_type` table
- Add fields: isPaid, maxConsecutiveDays, requiresDocument, carryForward
- Better UI with toggle switches

**Tab 5: Leave Settings** (Improve existing `/xadmin/mod/leave-setting/`)
- Uses existing `mx_leave_setting` table
- Financial year configuration
- Default leave quota per type
- Leave encashment rules

**Tab 6: Salary Structure**
- Uses existing `/xadmin/mod/salary-structure/`
- Link to employee for easy access

**Tab 7: Biometric**
- List employees with/without biometric ID
- Bulk assign biometric IDs
- Sync status with Camsunit device
- Uses: `CamsunitAPI::getSyncStatus()`, `bulkAssignBiometricIDs()`

**Tab 8: Email Settings**
- HR email recipients
- Email schedule (1st of month)
- Uses existing `/xadmin/mod/hr-email-settings/`

**Existing Holiday Table:** `mx_attendance_holidays`
- Already exists in database, reuse for holiday master

**Shift Master Table:** `mx_shift_master` (defined in Phase 2.6 migration)

---

#### 3.4 Attendance Remarks Flow - CLARIFIED

**Current Issue:** Remarks column exists but employee entry point is unclear.

**Proposed Flow:**

```
┌─────────────────────────────────────────────────────────────────────┐
│                    ATTENDANCE REMARKS WORKFLOW                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. TRIGGER: Employee marked LATE or EARLY CHECKOUT                 │
│  ─────────────────────────────────────────────────────              │
│  • Biometric punch records late arrival (checkIn > scheduled + grace)│
│  • System sets isLate = 1, lateMinutes = X                          │
│  • Same for early checkout                                          │
│                                                                      │
│                           ↓                                          │
│                                                                      │
│  2. EMPLOYEE PORTAL: Add Remark                                     │
│  ─────────────────────────────────                                  │
│  Location: /hrms/attendance/ → Calendar → Click on late day         │
│  • Day detail modal opens                                           │
│  • Shows: Check-in time, Late by X mins, Status                     │
│  • "Add Reason" button → Opens remark form                          │
│  • Remark types: Traffic, Medical, Personal, Client Visit, Other    │
│  • Employee submits reason                                          │
│                                                                      │
│                           ↓                                          │
│                                                                      │
│  3. MANAGER/HR REVIEW (Optional)                                    │
│  ────────────────────────────────                                   │
│  Location: Admin Panel → Attendance → Pending Remarks               │
│  • View all pending remarks                                         │
│  • Approve/Reject with note                                         │
│  • Approved remarks may waive salary deduction                      │
│                                                                      │
│                           ↓                                          │
│                                                                      │
│  4. SALARY CALCULATION                                              │
│  ─────────────────────────                                          │
│  • Unapproved late days → may deduct from salary                    │
│  • Approved remarks → no deduction                                  │
│  • Configurable: X late arrivals = 1 day deduction                  │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**UI Entry Points for Remarks:**

| Who | Where | Action |
|-----|-------|--------|
| Employee | Portal → Attendance → Click late day | Add reason for late/early |
| Manager | Portal → Team → Click team member's late day | Add reason on behalf of tech-illiterate employee |
| HR Admin | Admin Panel → Attendance → Bulk remarks | Add remarks for multiple employees |

**Current Implementation Status:**
- ✅ `mx_attendance_remarks` table exists
- ✅ `submitAttendanceRemark()` function exists in `x-hrms.inc.php`
- ⚠️ UI button to trigger remark submission needs verification
- ⚠️ Day detail modal needs "Add Reason" button

---

#### 3.5 Salary Processing & Slip Generation - PENDING

**Objective:** Accounts person (assignable role) processes salaries monthly. Salary slip PDF generated ONLY after payment is marked.

**Role Assignment:**
- Add `isAccountsPerson` flag to `mx_x_admin_user` table
- Assign user "Reena" (or any user) as Accounts Person
- Both Admin AND Accounts Person can access salary processing

**Database Change Required:**
```sql
ALTER TABLE mx_x_admin_user ADD COLUMN isAccountsPerson TINYINT DEFAULT 0 AFTER isLeaveManager;
-- Assign Reena as accounts person
UPDATE mx_x_admin_user SET isAccountsPerson = 1 WHERE userEmail = 'reena@bombayengg.net';
```

**Salary Processing Workflow:**

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    MONTHLY SALARY PROCESSING WORKFLOW                    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  WHO CAN ACCESS: Admin + Accounts Person (isAccountsPerson = 1)         │
│  WHEN: End of month / Beginning of next month                           │
│  WHERE: Admin Panel → HRMS → Salary Processing                          │
│         OR Employee Portal → Settings (for Accounts Person)             │
│                                                                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  STEP 1: VIEW SALARY LIST (Auto-calculated by system)                   │
│  ─────────────────────────────────────────────────────                  │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │ SALARY PROCESSING - January 2026                    [Generate All]│   │
│  ├──────┬────────────┬────────┬────────┬────────┬────────┬─────────┤   │
│  │ Emp  │ Name       │ Basic  │ Deduct │ Final  │ Paid   │ Action  │   │
│  │ Code │            │ Salary │ -ions  │ Salary │ Amount │         │   │
│  ├──────┼────────────┼────────┼────────┼────────┼────────┼─────────┤   │
│  │ E001 │ Ramesh K   │ 25,000 │ -1,000 │ 24,000 │ [____] │ [Save]  │   │
│  │ E002 │ Suresh P   │ 30,000 │ -0     │ 30,000 │ [____] │ [Save]  │   │
│  │ E003 │ Priya M    │ 28,000 │ -2,000 │ 26,000 │ [____] │ [Save]  │   │
│  │ E004 │ Amit S     │ 22,000 │ -500   │ 21,500 │ [____] │ [Save]  │   │
│  └──────┴────────────┴────────┴────────┴────────┴────────┴─────────┘   │
│                                                                          │
│  • Basic Salary: From salary structure                                  │
│  • Deductions: Auto-calculated from attendance (absent days, late, etc.)│
│  • Final Salary: Basic - Deductions (system calculated)                 │
│  • Paid Amount: MANUAL entry by Accounts Person (optional override)     │
│                                                                          │
│                           ↓                                              │
│                                                                          │
│  STEP 2: ENTER PAID AMOUNT (Optional)                                   │
│  ────────────────────────────────────                                   │
│  • If Paid Amount is EMPTY → Final Salary = System Calculated           │
│  • If Paid Amount is FILLED → Use that as actual paid amount            │
│  • Accounts person can add remarks for any adjustments                  │
│                                                                          │
│                           ↓                                              │
│                                                                          │
│  STEP 3: MARK AS PAID                                                   │
│  ────────────────────────                                               │
│  • Click "Mark Paid" for individual OR "Mark All Paid" for bulk         │
│  • Enter payment details: Mode (Bank/Cash/UPI), Transaction Ref, Date   │
│  • Status changes: pending → paid                                       │
│                                                                          │
│                           ↓                                              │
│                                                                          │
│  STEP 4: GENERATE SALARY SLIP (Only after payment)                      │
│  ─────────────────────────────────────────────────                      │
│  • "Generate Slip" button appears ONLY after status = paid              │
│  • Creates PDF with actual paid amount                                  │
│  • PDF stored in: /uploads/salary-slip/{year}/{month}/                  │
│  • Status changes: paid → slip_generated                                │
│  • Employee can now view/download from portal                           │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

**Deduction Calculation (Auto):**

| Deduction Type | Calculation |
|----------------|-------------|
| Absent Days | (Basic Salary / Working Days) × Absent Days |
| Unpaid Leave | (Basic Salary / Working Days) × Unpaid Leave Days |
| Late Arrivals | Configurable: X late marks = 1 day deduction |
| Salary Advance | Monthly EMI from `mx_salary_advance` |
| Other Deductions | Manual entry by Accounts Person |

**UI Table Layout for Accounts Person:**

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│ SALARY PROCESSING - January 2026                          [Export Excel] [Print]     │
├──────────────────────────────────────────────────────────────────────────────────────┤
│ Filter: [All Employees ▼]  [All Departments ▼]  Status: [All ▼]  [🔍 Search]         │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                       │
│ ┌─────┬───────────────┬──────────┬───────────────────────────────┬─────────────────┐ │
│ │     │               │          │         DEDUCTIONS            │                 │ │
│ │ Emp │ Employee      │ Basic    ├────────┬────────┬─────┬───────┤ Final   │ Paid  │ │
│ │ ID  │ Name          │ Salary   │ Absent │ Late   │ Adv │ Other │ Salary  │ Amt   │ │
│ ├─────┼───────────────┼──────────┼────────┼────────┼─────┼───────┼─────────┼───────┤ │
│ │ 001 │ Ramesh Kumar  │ 25,000   │ 962    │ 0      │ 0   │ 0     │ 24,038  │[     ]│ │
│ │     │ Dept: Sales   │          │ (1 day)│        │     │       │         │       │ │
│ ├─────┼───────────────┼──────────┼────────┼────────┼─────┼───────┼─────────┼───────┤ │
│ │ 002 │ Priya Sharma  │ 30,000   │ 0      │ 577    │ 0   │ 0     │ 29,423  │[     ]│ │
│ │     │ Dept: Admin   │          │        │(3 late)│     │       │         │       │ │
│ ├─────┼───────────────┼──────────┼────────┼────────┼─────┼───────┼─────────┼───────┤ │
│ │ 003 │ Amit Singh    │ 22,000   │ 1,692  │ 0      │2000 │ 0     │ 18,308  │[     ]│ │
│ │     │ Dept: Ops     │          │ (2 day)│        │ EMI │       │         │       │ │
│ └─────┴───────────────┴──────────┴────────┴────────┴─────┴───────┴─────────┴───────┘ │
│                                                                                       │
│ TOTALS:                  77,000    2,654    577    2000    0      71,769              │
│                                                                                       │
│ [Mark Selected as Paid]  [Generate Slips for Paid]  [Send Emails]                    │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

**Implementation Tasks:**

| Task | Priority | Description |
|------|----------|-------------|
| Add isAccountsPerson flag | HIGH | Database column + admin user edit form |
| Salary Processing Page | HIGH | `/xadmin/mod/salary-processing/` - List with deductions |
| Auto Deduction Calculation | HIGH | Calculate from attendance data |
| Paid Amount Override | HIGH | Manual entry field for actual paid |
| Mark as Paid | HIGH | Update status, record payment details |
| Generate PDF Slip | HIGH | Only after payment marked |
| Portal Access for Accounts | MEDIUM | Accounts person sees salary menu in portal settings |
| Bulk Actions | MEDIUM | Mark all paid, generate all slips |
| Export to Excel | LOW | Download salary sheet |

**Access Control (REVISED):**

| User Type | Salary Processing | Attendance | Salary Advance | Documents | Leave | Other HRMS |
|-----------|-------------------|------------|----------------|-----------|-------|------------|
| Admin (HR) | Full | Full | Full | Full | Full | Full |
| Accounts Person | Full | View Only | Full | NO | NO | NO |
| Manager | Team Only | Team Only | NO | Team Only | Team Only | Team Only |
| Employee | Own Slips | Own | Request Only | Own | Own | Own |

**Accounts Person Restrictions:**
- CAN access: Salary Processing, Salary Advance, View Attendance (for deduction verification)
- CANNOT access: Documents, Leave Management, Employee Personal Details, Profile Edit

---

#### 3.6 Salary Advance Module - PENDING

**Objective:** Employees can request early salary/loan which gets deducted from future salaries over configurable months.

**Existing Table:** `mx_salary_advance` (already created in Phase 1 migration)

**Table Structure:**
```sql
CREATE TABLE IF NOT EXISTS mx_salary_advance (
  advanceID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT NOT NULL,
  advanceAmount DECIMAL(12,2) NOT NULL,        -- Total loan/advance amount
  advanceDate DATE NOT NULL,                    -- Date advance was given
  reason TEXT NULL,                             -- Why employee needs advance
  approvedBy INT NULL,                          -- Admin/HR who approved
  approvedAt DATETIME NULL,
  -- Deduction Settings
  deductFromMonth INT NULL,                     -- Start deduction from this month (1-12)
  deductFromYear INT NULL,                      -- Start deduction from this year
  repaymentMonths INT DEFAULT 3,                -- 3, 6, 9, 12 or custom
  monthlyDeduction DECIMAL(12,2) DEFAULT 0,     -- EMI amount per month
  totalDeducted DECIMAL(12,2) DEFAULT 0,        -- Amount already recovered
  remainingAmount DECIMAL(12,2),                -- Balance to be recovered
  -- Status
  advanceStatus ENUM('pending','approved','rejected','active','completed','cancelled') DEFAULT 'pending',
  remarks TEXT NULL,                            -- Admin notes
  status TINYINT DEFAULT 1,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  updatedAt DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
);
```

**Salary Advance Workflow:**

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    SALARY ADVANCE / LOAN WORKFLOW                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  STEP 1: EMPLOYEE REQUESTS (Optional - can be admin-initiated too)      │
│  ───────────────────────────────────────────────────────────────        │
│  • Employee Portal → Salary → Request Advance                           │
│  • Enter: Amount needed, Reason                                         │
│  • Status: pending                                                      │
│                                                                          │
│                           ↓                                              │
│                                                                          │
│  STEP 2: ADMIN/ACCOUNTS APPROVES                                        │
│  ────────────────────────────────                                       │
│  • Admin Panel → Salary Advance → Pending Requests                      │
│  • Review request, can modify amount                                    │
│  • Set repayment plan:                                                  │
│    - Repayment Months: [3] [6] [9] [12] [Custom: ___]                  │
│    - Start From: [Month] [Year]                                         │
│    - Monthly EMI: Auto-calculated OR manual                             │
│  • Approve / Reject with remarks                                        │
│  • Status: pending → approved                                           │
│                                                                          │
│                           ↓                                              │
│                                                                          │
│  STEP 3: ADVANCE DISBURSED                                              │
│  ─────────────────────────                                              │
│  • Once approved, admin marks as "Active" after giving money           │
│  • Enter: Payment Mode, Transaction Ref, Date Given                     │
│  • Status: approved → active                                            │
│                                                                          │
│                           ↓                                              │
│                                                                          │
│  STEP 4: AUTO DEDUCTION FROM SALARY                                     │
│  ──────────────────────────────────                                     │
│  • Every month during salary processing:                                │
│    - System checks active advances for each employee                    │
│    - Deducts monthlyDeduction (EMI) from salary                        │
│    - Updates totalDeducted and remainingAmount                          │
│  • When remainingAmount = 0 → Status: active → completed               │
│                                                                          │
│                           ↓                                              │
│                                                                          │
│  STEP 5: COMPLETION                                                     │
│  ──────────────────                                                     │
│  • Full amount recovered                                                │
│  • Status: completed                                                    │
│  • No more deductions                                                   │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

**Repayment Options:**

| Months | Example (₹30,000 advance) |
|--------|---------------------------|
| 3 months | ₹10,000/month EMI |
| 6 months | ₹5,000/month EMI |
| 9 months | ₹3,333/month EMI |
| 12 months | ₹2,500/month EMI |
| Custom (e.g., 4) | ₹7,500/month EMI |

**UI - Salary Advance List (Admin/Accounts):**

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│ SALARY ADVANCE MANAGEMENT                                    [+ New Advance]         │
├──────────────────────────────────────────────────────────────────────────────────────┤
│ Filter: [All Status ▼]  [All Employees ▼]  Year: [2026 ▼]                            │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                       │
│ ┌─────┬───────────────┬──────────┬────────┬─────────┬──────────┬──────────┬────────┐ │
│ │ ID  │ Employee      │ Amount   │ EMI    │ Months  │ Deducted │ Balance  │ Status │ │
│ ├─────┼───────────────┼──────────┼────────┼─────────┼──────────┼──────────┼────────┤ │
│ │ 001 │ Ramesh Kumar  │ 30,000   │ 5,000  │ 6 (3/6) │ 15,000   │ 15,000   │ Active │ │
│ │     │ Given: 01-Nov │          │        │         │          │          │ [View] │ │
│ ├─────┼───────────────┼──────────┼────────┼─────────┼──────────┼──────────┼────────┤ │
│ │ 002 │ Priya Sharma  │ 20,000   │ 6,667  │ 3 (0/3) │ 0        │ 20,000   │Approved│ │
│ │     │ Approved: Today│         │        │         │          │          │[Disburse]│
│ ├─────┼───────────────┼──────────┼────────┼─────────┼──────────┼──────────┼────────┤ │
│ │ 003 │ Amit Singh    │ 15,000   │ -      │ -       │ -        │ -        │Pending │ │
│ │     │ Req: 02-Jan   │          │        │         │          │          │[Review]│ │
│ ├─────┼───────────────┼──────────┼────────┼─────────┼──────────┼──────────┼────────┤ │
│ │ 004 │ Suresh P      │ 50,000   │ 4,167  │12(12/12)│ 50,000   │ 0        │Complete│ │
│ │     │ Completed     │          │        │         │          │          │        │ │
│ └─────┴───────────────┴──────────┴────────┴─────────┴──────────┴──────────┴────────┘ │
│                                                                                       │
│ SUMMARY: Active: 1 (₹15,000 pending) | Pending Approval: 1 | Completed This Year: 1  │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

**UI - Approve/Edit Advance Modal:**

```
┌─────────────────────────────────────────────────────────┐
│ SALARY ADVANCE - Amit Singh                        [X]  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Requested Amount:  ₹ 15,000                           │
│  Reason:            Medical emergency                   │
│  Requested On:      02-Jan-2026                         │
│                                                         │
│  ─────────────────────────────────────────────────────  │
│                                                         │
│  Approved Amount:   [₹ 15,000    ]  (can modify)       │
│                                                         │
│  Repayment Plan:    ○ 3 months   ○ 6 months            │
│                     ○ 9 months   ○ 12 months           │
│                     ● Custom: [4] months                │
│                                                         │
│  Monthly EMI:       ₹ 3,750 (auto-calculated)          │
│                     OR [      ] (manual override)       │
│                                                         │
│  Start Deduction:   [February ▼] [2026 ▼]              │
│                                                         │
│  Remarks:           [_______________________________]   │
│                                                         │
│  ─────────────────────────────────────────────────────  │
│                                                         │
│  [Reject]                              [Approve]        │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

**Employee Portal - View Own Advances:**

```
┌─────────────────────────────────────────────────────────┐
│ MY SALARY ADVANCES                                      │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  [+ Request New Advance]                                │
│                                                         │
│  ┌───────────────────────────────────────────────────┐  │
│  │ Advance #001                          Status: Active│ │
│  │ Amount: ₹30,000    EMI: ₹5,000/month               │  │
│  │ Repayment: 3 of 6 months completed                 │  │
│  │ Remaining: ₹15,000                                 │  │
│  │ ████████████░░░░░░░░░░ 50%                        │  │
│  └───────────────────────────────────────────────────┘  │
│                                                         │
│  ┌───────────────────────────────────────────────────┐  │
│  │ Advance #002                       Status: Pending │  │
│  │ Amount: ₹10,000    Requested: 02-Jan-2026         │  │
│  │ Awaiting approval...                [Cancel]       │  │
│  └───────────────────────────────────────────────────┘  │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

**Integration with Salary Processing:**

During monthly salary calculation:
```php
// In salary processing logic
$activeAdvances = getActiveAdvances($userID);
foreach ($activeAdvances as $advance) {
    $deduction = $advance['monthlyDeduction'];
    $totalDeductions += $deduction;

    // Update advance record
    updateAdvanceDeduction($advance['advanceID'], $deduction);
}
```

**Implementation Tasks:**

| Task | Priority | Description |
|------|----------|-------------|
| Advance Request (Portal) | HIGH | Employee can request advance from portal |
| Advance List (Admin) | HIGH | View all advances with status filter |
| Approve/Reject Modal | HIGH | Set repayment plan, approve or reject |
| Disburse Advance | HIGH | Mark as active after giving money |
| Auto Deduction | HIGH | Integrate with salary processing |
| Advance History | MEDIUM | View completed advances |
| Employee View | MEDIUM | Employee sees own advances in portal |
| Partial Prepayment | LOW | Employee can pay extra to close early |

---

#### 3.7 ICICI Bank CMS File Generation - COMPLETED (January 2026)

**Objective:** Generate ICICI Bank CMS (Corporate Management System) file for bulk salary transfers. Only Admin and Accounts Person can access.

**Implementation Location:**
| File | Purpose |
|------|---------|
| `/xsite/mod/hrms/x-hrms.inc.php` | Backend functions: `generateICICICMSFile()`, `getICICIBankSettings()`, `saveICICIBankSettings()` |
| `/xsite/mod/hrms/x-salary-processing.php` | UI: ICICI CMS button, Settings modal, Download modal |
| `mx_hrms_settings` table | Stores ICICI bank settings (company code, debit account) |

**Features Implemented:**
- ICICI CMS button in Salary Processing page (orange button)
- Bank Settings modal (company code, debit account)
- CMS Generation modal with summary (valid/invalid employees, total amount)
- IFSC validation (11 chars, format check)
- Download as .txt file with pipe-delimited format
- Preview first 5 lines before download
- Shows excluded employees with reasons

**What is CMS File?**
- Standard text/CSV file format accepted by ICICI Bank for bulk payments
- Upload to ICICI Corporate Internet Banking → Bulk Upload
- Bank processes all salary transfers in one go

**ICICI CMS File Format (Pipe-delimited):**

```
PAYMENT|SALARY|{CompanyCode}|{DebitAccount}|{PaymentDate}|{BatchRef}|{TotalAmount}|{TotalCount}
{BeneName}|{BeneAccNo}|{BeneIFSC}|{Amount}|{Remarks}|{EmailID}|{MobileNo}
{BeneName}|{BeneAccNo}|{BeneIFSC}|{Amount}|{Remarks}|{EmailID}|{MobileNo}
...
```

**Header Record Fields:**
| Field | Description | Example |
|-------|-------------|---------|
| Record Type | Always "PAYMENT" | PAYMENT |
| Payment Type | Always "SALARY" | SALARY |
| Company Code | ICICI assigned code | BOMENG001 |
| Debit Account | Company's ICICI account | 123456789012 |
| Payment Date | DD-MM-YYYY | 01-02-2026 |
| Batch Reference | Unique batch ID | SAL-JAN2026 |
| Total Amount | Sum of all payments | 500000.00 |
| Total Count | Number of beneficiaries | 25 |

**Detail Record Fields:**
| Field | Description | Example |
|-------|-------------|---------|
| Beneficiary Name | Employee name | RAMESH KUMAR |
| Account Number | Employee bank A/C | 987654321098 |
| IFSC Code | Bank IFSC | ICIC0001234 |
| Amount | Net salary | 25000.00 |
| Remarks | Payment narration | SALARY JAN 2026 |
| Email ID | Employee email | ramesh@email.com |
| Mobile No | Employee mobile | 9876543210 |

**Sample CMS File:**
```
PAYMENT|SALARY|BOMENG001|123456789012|01-02-2026|SAL-JAN2026|71769.00|3
RAMESH KUMAR|987654321098|ICIC0001234|24038.00|SALARY JAN 2026|ramesh@email.com|9876543210
PRIYA SHARMA|876543210987|HDFC0005678|29423.00|SALARY JAN 2026|priya@email.com|9876543211
AMIT SINGH|765432109876|SBIN0009012|18308.00|SALARY JAN 2026|amit@email.com|9876543212
```

**UI - Generate CMS File:**

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│ GENERATE ICICI CMS FILE                                                              │
├──────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                       │
│  Month: [January ▼]  Year: [2026 ▼]                                                  │
│                                                                                       │
│  ─────────────────────────────────────────────────────────────────────────────────   │
│                                                                                       │
│  Company Settings:                                                                    │
│  ┌─────────────────────────────────────────────────────────────────────────────┐    │
│  │ ICICI Company Code:    [BOMENG001        ]                                   │    │
│  │ Debit Account No:      [123456789012     ]                                   │    │
│  │ Payment Date:          [05-02-2026       ]                                   │    │
│  │ Batch Reference:       [SAL-JAN2026      ] (auto-generated)                  │    │
│  └─────────────────────────────────────────────────────────────────────────────┘    │
│                                                                                       │
│  ─────────────────────────────────────────────────────────────────────────────────   │
│                                                                                       │
│  Employees to Include:     ● All Paid   ○ Selected Only                             │
│                                                                                       │
│  ┌─────┬───────────────┬──────────────┬─────────────┬──────────┬─────────┐          │
│  │ [✓] │ Employee      │ Account No   │ IFSC        │ Amount   │ Status  │          │
│  ├─────┼───────────────┼──────────────┼─────────────┼──────────┼─────────┤          │
│  │ [✓] │ Ramesh Kumar  │ 9876...1098  │ ICIC0001234 │ 24,038   │ ✓ Valid │          │
│  │ [✓] │ Priya Sharma  │ 8765...0987  │ HDFC0005678 │ 29,423   │ ✓ Valid │          │
│  │ [✓] │ Amit Singh    │ 7654...9876  │ SBIN0009012 │ 18,308   │ ✓ Valid │          │
│  │ [ ] │ Suresh P      │ ----         │ ----        │ 22,000   │ ✗ No A/C│          │
│  └─────┴───────────────┴──────────────┴─────────────┴──────────┴─────────┘          │
│                                                                                       │
│  Total Selected: 3 employees                                                          │
│  Total Amount: ₹71,769.00                                                            │
│                                                                                       │
│  ⚠ 1 employee has missing bank details (excluded)                                   │
│                                                                                       │
│  [Preview CMS File]                    [Download CMS File]                           │
│                                                                                       │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

**Validation Before Generate:**
- Employee must have bank account number
- Employee must have IFSC code
- Salary must be marked as "Paid"
- Amount must be > 0

**Database - Store Company Bank Settings:**
```sql
-- Add to mx_hrms_settings or create new table
INSERT INTO mx_hrms_settings (settingKey, settingValue, settingDescription) VALUES
('icici_company_code', '', 'ICICI CMS Company Code'),
('icici_debit_account', '', 'ICICI Debit Account Number'),
('company_bank_name', 'ICICI Bank', 'Company Primary Bank');
```

**Implementation Tasks:**

| Task | Priority | Description |
|------|----------|-------------|
| CMS Settings Page | HIGH | Store ICICI company code, debit account |
| Generate CMS Function | HIGH | Create pipe-delimited file from salary data |
| Validation | HIGH | Check bank details before generation |
| Download CMS File | HIGH | Download as .txt file |
| Preview Modal | MEDIUM | Show file content before download |
| CMS Generation Log | MEDIUM | Track when CMS was generated |
| Support Other Banks | LOW | HDFC, SBI formats (future) |

**Access Control:**
- Only **Admin** and **Accounts Person** can generate CMS file
- Button appears in Salary Processing page after payments marked

**PHP Function Signature:**
```php
/**
 * Generate ICICI CMS file for bulk salary payment
 *
 * @param int $month Salary month (1-12)
 * @param int $year Salary year
 * @param array $employeeIds Optional - specific employees, empty = all paid
 * @return string CMS file content
 */
function generateICICICMSFile($month, $year, $employeeIds = []) {
    // Validate access (admin or accounts person only)
    // Fetch paid salaries for month/year
    // Validate bank details
    // Generate header record
    // Generate detail records
    // Return file content
}
```

**File Naming Convention:**
```
ICICI_CMS_SAL_{MONTH}_{YEAR}_{TIMESTAMP}.txt
Example: ICICI_CMS_SAL_JAN_2026_20260205_143022.txt
```

---

#### 3.8 Automated Monthly Emails - PENDING

| Task | Priority | Notes |
|------|----------|-------|
| Email cron job | MEDIUM | Run on 1st of month at 9 AM |
| Individual salary slip email | MEDIUM | Send PDF to each employee (only if slip generated) |
| HR master summary email | MEDIUM | Consolidated report to HR |
| Email templates | LOW | Beautiful HTML templates |

---

#### 3.9 Testing Checklist

**Dashboard (`x-home.php`):**
- [ ] Layout on different screen sizes
- [ ] Attendance summary cards
- [ ] Recent salary slip display

**Attendance Page (`x-attendance.php`):**
- [ ] Calendar view with color-coded status
- [ ] Click day → show detail modal
- [ ] Add remark from detail modal
- [ ] Download PDF

**Leave Page (NEW):**
- [ ] Apply for leave
- [ ] View leave balance
- [ ] View leave history
- [ ] Cancel pending leave

**Documents Page (`x-documents.php`):**
- [ ] List documents
- [ ] Preview/download documents

**General:**
- [ ] Login with OTP
- [ ] Manager vs employee view
- [ ] Switch user (HR Admin only)
- [ ] Mobile responsiveness

---

## Key Design Decisions

| Decision | Approach |
|----------|----------|
| Employee Master | **Extend `x_admin_user`** table instead of creating separate `mx_employee` |
| Manager-Employee Relationship | Add `managerID` field for specific team assignment |
| Non-computer employees | Use existing `techIlliterate` flag (employees who don't login themselves) |
| Portal Access | Managers see own data + all assigned `techIlliterate` employees |
| Attendance Remarks | Employees/managers can add reasons for late arrival, early checkout |
| Monthly Emails | Automated on 1st of every month - individual + HR master summary |
| Email Templates | Use `frontend-design` skill for beautiful HTML templates |

---

## Phase 1: Database Schema

### 1.1 Extend `x_admin_user` Table

```sql
-- Add HR fields to existing x_admin_user table
ALTER TABLE bombayengg_x_admin_user ADD COLUMN IF NOT EXISTS
  employeeCode VARCHAR(20) UNIQUE AFTER userID,
  dateOfBirth DATE NULL,
  gender ENUM('M','F','O') NULL,
  bloodGroup VARCHAR(5) NULL,
  emergencyContact VARCHAR(15) NULL,
  emergencyContactName VARCHAR(100) NULL,
  -- Employment Details
  dateOfJoining DATE NULL,
  designation VARCHAR(100) NULL,
  department VARCHAR(100) NULL,
  employmentType ENUM('permanent','contract','probation') DEFAULT 'permanent',
  managerID INT NULL,                          -- FK to x_admin_user.userID (for team assignment)
  -- Bank Details
  bankName VARCHAR(100) NULL,
  bankAccountNo VARCHAR(30) NULL,
  bankIFSC VARCHAR(15) NULL,
  -- ID Proofs
  panNo VARCHAR(15) NULL,
  aadhaarNo VARCHAR(15) NULL,
  -- Address
  currentAddress TEXT NULL,
  permanentAddress TEXT NULL,
  -- Biometric
  biometricID VARCHAR(50) NULL,                -- Camsunit device ID
  -- Portal Auth (for employees who login)
  loginOTP VARCHAR(6) NULL,
  otpExpiry DATETIME NULL,
  lastPortalLogin DATETIME NULL,
  -- Exit Details
  dateOfExit DATE NULL,
  exitReason TEXT NULL;

-- Add index for manager lookup
ALTER TABLE bombayengg_x_admin_user ADD INDEX idx_manager (managerID);
ALTER TABLE bombayengg_x_admin_user ADD INDEX idx_biometric (biometricID);
```

### 1.2 New Tables

```sql
-- 1. Salary Structure (per employee)
CREATE TABLE bombayengg_salary_structure (
  structureID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT,                                  -- FK to x_admin_user
  effectiveFrom DATE,
  effectiveTo DATE NULL,
  -- Earnings
  basicSalary DECIMAL(12,2),
  hra DECIMAL(12,2) DEFAULT 0,
  conveyanceAllowance DECIMAL(12,2) DEFAULT 0,
  medicalAllowance DECIMAL(12,2) DEFAULT 0,
  specialAllowance DECIMAL(12,2) DEFAULT 0,
  otherAllowance DECIMAL(12,2) DEFAULT 0,
  -- Calculated
  grossSalary DECIMAL(12,2),
  -- Metadata
  remarks TEXT,
  status TINYINT DEFAULT 1,
  createdBy INT,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_effective (userID, effectiveFrom)
);

-- 2. Monthly Salary Slip
-- WORKFLOW: Attendance Review → Mark Paid → Generate PDF Slip
CREATE TABLE bombayengg_salary_slip (
  slipID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT,                                  -- FK to x_admin_user
  salaryMonth INT,                             -- 1-12
  salaryYear INT,                              -- 2025
  structureID INT,                             -- FK to salary_structure
  -- Earnings (copied from structure + adjustments)
  basicSalary DECIMAL(12,2),
  hra DECIMAL(12,2),
  conveyanceAllowance DECIMAL(12,2),
  medicalAllowance DECIMAL(12,2),
  specialAllowance DECIMAL(12,2),
  otherAllowance DECIMAL(12,2),
  totalEarnings DECIMAL(12,2),
  -- Deductions
  leavesDeducted INT DEFAULT 0,
  leaveDeductionAmount DECIMAL(12,2) DEFAULT 0,
  advanceDeduction DECIMAL(12,2) DEFAULT 0,
  otherDeduction DECIMAL(12,2) DEFAULT 0,
  totalDeductions DECIMAL(12,2),
  -- Net
  netSalary DECIMAL(12,2),
  -- Actual Amount Paid (may differ from calculated netSalary)
  amountPaid DECIMAL(12,2) NULL,               -- Actual amount paid by admin
  -- Attendance Summary
  workingDays INT,
  presentDays INT,
  absentDays INT,
  leavesTaken INT,
  lateDays INT DEFAULT 0,
  earlyCheckoutDays INT DEFAULT 0,
  -- Document
  slipPDF VARCHAR(255),                        -- Generated PDF filename (only after paid)
  -- Status: pending → paid → slip_generated → emailed
  slipStatus ENUM('pending','paid','slip_generated','emailed') DEFAULT 'pending',
  paidOn DATE NULL,
  paidBy INT NULL,                             -- Admin who marked as paid
  paymentMode VARCHAR(50),                     -- Cash/Bank Transfer/UPI/Cheque
  transactionRef VARCHAR(100),                 -- Bank ref/Cheque no/UPI ID
  paymentRemarks TEXT NULL,                    -- Any notes by admin
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  generatedAt DATETIME NULL,                   -- When PDF was generated
  emailSentAt DATETIME NULL,
  UNIQUE KEY unique_slip (userID, salaryMonth, salaryYear)
);

-- 3. Employee Documents
CREATE TABLE bombayengg_employee_document (
  documentID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT,                                  -- FK to x_admin_user
  documentType ENUM('aadhaar','pan','passport','photo','appointment_letter',
                    'increment_letter','exit_letter','experience_letter',
                    'policy','training_cert','other'),
  documentName VARCHAR(255),
  fileName VARCHAR(255),
  fileSize INT,
  uploadedBy INT,
  remarks TEXT,
  validUpto DATE NULL,                         -- For documents with expiry
  status TINYINT DEFAULT 1,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (userID)
);

-- 4. Attendance (synced from Camsunit)
CREATE TABLE bombayengg_attendance (
  attendanceID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT,                                  -- FK to x_admin_user
  attendanceDate DATE,
  scheduledIn TIME DEFAULT '09:00:00',         -- Expected check-in time
  scheduledOut TIME DEFAULT '18:00:00',        -- Expected check-out time
  checkIn DATETIME NULL,
  checkOut DATETIME NULL,
  workingHours DECIMAL(5,2) DEFAULT 0,
  -- Status flags
  isLate TINYINT DEFAULT 0,
  isEarlyCheckout TINYINT DEFAULT 0,
  lateMinutes INT DEFAULT 0,
  earlyMinutes INT DEFAULT 0,
  attendanceStatus ENUM('present','absent','half_day','leave','holiday','weekend') DEFAULT 'present',
  source ENUM('biometric','manual','system') DEFAULT 'biometric',
  biometricRaw TEXT NULL,                      -- Raw API response for audit
  remarks TEXT,
  syncedAt DATETIME,
  UNIQUE KEY unique_attendance (userID, attendanceDate),
  INDEX idx_date (attendanceDate),
  INDEX idx_user_month (userID, attendanceDate)
);

-- 5. Attendance Remarks (employee explanations for late/early)
CREATE TABLE bombayengg_attendance_remarks (
  remarkID INT AUTO_INCREMENT PRIMARY KEY,
  attendanceID INT,                            -- FK to attendance
  userID INT,                                  -- FK to x_admin_user (the employee)
  remarkType ENUM('late_arrival','early_checkout','absence','correction','other'),
  reason TEXT NOT NULL,
  submittedBy INT,                             -- Could be employee or manager
  submittedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  -- Manager Review
  reviewedBy INT NULL,
  reviewedAt DATETIME NULL,
  reviewStatus ENUM('pending','approved','rejected') DEFAULT 'pending',
  reviewNote TEXT NULL,
  status TINYINT DEFAULT 1,
  INDEX idx_attendance (attendanceID),
  INDEX idx_user (userID)
);

-- 6. Salary Advance
CREATE TABLE bombayengg_salary_advance (
  advanceID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT,                                  -- FK to x_admin_user
  advanceAmount DECIMAL(12,2),
  advanceDate DATE,
  reason TEXT,
  approvedBy INT,
  -- Deduction Settings
  deductFromMonth INT,                         -- Start month
  deductFromYear INT,
  monthlyDeduction DECIMAL(12,2),              -- EMI amount
  totalDeducted DECIMAL(12,2) DEFAULT 0,
  remainingAmount DECIMAL(12,2),
  -- Status
  advanceStatus ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (userID)
);

-- 7. Monthly Email Log
CREATE TABLE bombayengg_hr_email_log (
  logID INT AUTO_INCREMENT PRIMARY KEY,
  emailType ENUM('individual_slip','hr_master','attendance_summary') NOT NULL,
  userID INT NULL,                             -- NULL for HR master emails
  salaryMonth INT,
  salaryYear INT,
  recipientEmail VARCHAR(255),
  emailSubject VARCHAR(255),
  emailStatus ENUM('sent','failed','pending') DEFAULT 'pending',
  errorMessage TEXT NULL,
  sentAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_month_year (salaryMonth, salaryYear)
);

-- 8. HR Email Recipients (configurable)
CREATE TABLE bombayengg_hr_email_recipients (
  recipientID INT AUTO_INCREMENT PRIMARY KEY,
  recipientName VARCHAR(100),
  recipientEmail VARCHAR(255) NOT NULL,
  emailTypes SET('individual_slip','hr_master','attendance_summary') DEFAULT 'hr_master',
  status TINYINT DEFAULT 1,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## Phase 2: Backend Modules (xadmin)

### 2.1 Extend Admin User Module
**Location:** `/xadmin/core-admin/mod/admin-user/`

**Changes:**
- Add new HR fields to add/edit form (bank details, PAN, Aadhaar, etc.)
- Add manager dropdown (`managerID`) - shows only users with `isLeaveManager = 1`
- Add biometric ID field for Camsunit linking
- Keep existing `techIlliterate` and `isLeaveManager` flags

### 2.2 Salary Structure Module
**Location:** `/xadmin/mod/salary-structure/`

**Files:**
```
salary-structure/
├── x-salary-structure.inc.php      # Business logic
├── x-salary-structure-list.php     # List with employee filter
├── x-salary-structure-add-edit.php # Define salary components
└── inc/js/x-salary-structure.inc.js
```

**Features:**
- Define salary components per employee
- Effective date tracking (for increments)
- Auto-calculate gross salary
- View salary history per employee

### 2.3 Salary Slip Module
**Location:** `/xadmin/mod/salary-slip/`

**Files:**
```
salary-slip/
├── x-salary-slip.inc.php           # Business logic
├── x-salary-slip-list.php          # Monthly slip list (with payment status)
├── x-salary-slip-pay.php           # Mark as paid form
├── x-salary-slip-view.php          # View slip details + generate PDF
├── inc/slip-template.php           # PDF template (MPDF)
└── inc/js/x-salary-slip.inc.js
```

**⚠️ IMPORTANT: Salary Payment Workflow**

```
┌─────────────────────────────────────────────────────────────────────┐
│                    MASTER ADMIN SALARY WORKFLOW                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  STEP 1: Review Attendance (View All Employees)                     │
│  ────────────────────────────────────────────                       │
│  • Master admin views all employees' monthly attendance             │
│  • Reviews: Present days, Absent days, Late arrivals, Leaves        │
│  • Checks attendance remarks (reasons for late/early)               │
│  • System auto-calculates: Working days, Deductions                 │
│                                                                      │
│                           ↓                                          │
│                                                                      │
│  STEP 2: Calculate Salary (Auto + Manual Adjustments)               │
│  ─────────────────────────────────────────────────────              │
│  • System fetches salary structure for each employee                │
│  • Auto-calculates: Gross - Deductions = Net Salary                 │
│  • Admin can adjust if needed (bonus, extra deduction)              │
│  • Shows "Pending Payment" status                                   │
│                                                                      │
│                           ↓                                          │
│                                                                      │
│  STEP 3: Pay Salary (Mark as Paid)                                  │
│  ─────────────────────────────────                                  │
│  • Admin pays salary via Bank/Cash/UPI                              │
│  • Clicks "Mark as Paid" for each employee                          │
│  • Enters: Amount Paid, Payment Mode, Transaction Ref, Date         │
│  • Status changes: "pending" → "paid"                               │
│                                                                      │
│                           ↓                                          │
│                                                                      │
│  STEP 4: Generate Salary Slip (PDF)                                 │
│  ───────────────────────────────────                                │
│  • ONLY after payment is marked                                     │
│  • Click "Generate Slip" button                                     │
│  • PDF created with actual paid amount                              │
│  • Status changes: "paid" → "slip_generated"                        │
│  • PDF stored in: /uploads/salary-slip/{year}/{month}/              │
│                                                                      │
│                           ↓                                          │
│                                                                      │
│  STEP 5: Email Salary Slip (Optional/Automated)                     │
│  ───────────────────────────────────────────────                    │
│  • Manual: Click "Send Email" button                                │
│  • Auto: Cron on 1st of month sends all generated slips             │
│  • Status changes: "slip_generated" → "emailed"                     │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**Salary Slip Status Flow:**
```
pending → paid → slip_generated → emailed
   │        │          │             │
   │        │          │             └── Email sent to employee
   │        │          └── PDF generated and saved
   │        └── Admin marked payment complete
   └── Initial state (salary calculated)
```

**Features:**
- Monthly salary listing with filters (month, year, status)
- View all employees' attendance summary for the month
- Calculate salary based on structure + attendance
- **Mark as Paid:** Enter payment details (amount, mode, ref, date)
- **Generate PDF:** Only available after payment marked
- Bulk actions: Mark all as paid, Generate all slips
- Email salary slips (individual or bulk)
- Payment history and audit trail

### 2.4 Employee Documents Module
**Location:** `/xadmin/mod/employee-document/`

**Features:**
- Upload documents per employee
- Categorize by type (ID proofs, letters, policies)
- Document expiry tracking
- View/download documents

### 2.5 Attendance Module (Enhanced)
**Location:** `/xadmin/mod/attendance/`

**Files:**
```
attendance/
├── x-attendance.inc.php            # Business logic + Camsunit sync
├── x-attendance-list.php           # Daily/monthly attendance view
├── x-attendance-calendar.php       # Calendar view
├── x-attendance-manual.php         # Manual attendance entry
├── x-attendance-remarks.php        # View/approve employee remarks
├── x-attendance-reports.php        # Master reports dashboard
├── x-attendance-export.php         # Excel/PDF export handler
└── inc/js/x-attendance.inc.js
```

**Core Features:**
- Camsunit API integration (real-time + cron sync)
- View attendance calendar
- Mark manual attendance (exceptions)
- **Late/Early tracking with reason capture**
- Approve/reject employee remarks

---

## ENHANCED ATTENDANCE REPORTING SYSTEM

### Report Types

#### 1. Daily Attendance Report
| Field | Description |
|-------|-------------|
| Employee Name | Full name with employee code |
| Department | Department name |
| Shift | Assigned shift timing |
| Check-In Time | Actual punch-in time |
| Check-Out Time | Actual punch-out time |
| Working Hours | Total hours worked |
| Status | Present / Absent / Late / Half-Day / Leave |
| Late By (mins) | Minutes late if applicable |
| Early Exit (mins) | Minutes early if left early |
| Overtime (hrs) | Extra hours beyond shift |
| Remarks | Employee/manager remarks |

#### 2. Weekly Attendance Summary
- Week-wise breakdown (Mon-Sun)
- Total present/absent/late days per employee
- Weekly working hours vs expected hours
- Overtime summary
- Trend comparison with previous week

#### 3. Monthly Attendance Report (Muster Roll)
| Column | Description |
|--------|-------------|
| Emp Code | Employee ID |
| Emp Name | Full name |
| Department | Department |
| Days 1-31 | Status code (P/A/L/H/WO/HD) |
| Total Present | Count of present days |
| Total Absent | Count of absent days |
| Total Leave | Count of leave days |
| Total Late | Count of late arrivals |
| Working Days | Total working days in month |
| Payable Days | Days eligible for salary |
| Overtime Hours | Total OT hours |
| Deduction Days | LOP days |

**Status Codes:**
- P = Present
- A = Absent
- L = Leave (Approved)
- H = Holiday
- WO = Weekly Off
- HD = Half Day
- LT = Late (with Late marker)

#### 4. Attendance Data for Payroll
| Field | Description |
|-------|-------------|
| Employee Code | Unique ID |
| Employee Name | Full name |
| Department | Department |
| Total Calendar Days | Days in month |
| Working Days | Excluding holidays/weekends |
| Present Days | Actual present |
| Absent Days | Unexcused absences |
| Paid Leave | CL/EL/SL taken |
| Unpaid Leave | LOP days |
| Late Arrivals | Count with penalty calculation |
| Early Departures | Count with penalty |
| Overtime Hours | Total OT |
| Overtime Amount | OT hours × rate |
| Deduction Amount | (Absent + LOP) × per day |
| Net Payable Days | For salary calculation |

#### 5. Productivity Report
- On-time arrivals vs late arrivals
- Average working hours per employee
- Productive hours (excluding breaks)
- Department-wise productivity comparison
- Employee ranking by punctuality

#### 6. Early/Late Check-in Report
| Field | Description |
|-------|-------------|
| Date | Attendance date |
| Employee | Name and code |
| Scheduled In | Expected check-in |
| Actual In | Actual check-in |
| Difference | Minutes early/late |
| Scheduled Out | Expected check-out |
| Actual Out | Actual check-out |
| Difference | Minutes early/late |
| Reason | If remark submitted |
| Manager Approval | Approved/Rejected/Pending |

#### 7. Absenteeism Report
- Employees with highest absence rate
- Absence patterns (day-of-week analysis)
- Consecutive absence tracking
- Unexcused vs excused absences
- Department-wise absenteeism comparison

#### 8. Overtime Report
| Field | Description |
|-------|-------------|
| Employee | Name and code |
| Date | Work date |
| Shift End | Scheduled end time |
| Actual Out | Actual departure |
| OT Hours | Overtime worked |
| OT Type | Weekday / Weekend / Holiday |
| OT Rate | Multiplier (1.5x, 2x, etc.) |
| OT Amount | Calculated amount |
| Approved By | Manager name |

#### 9. Leave Balance Report
- Leave type wise balance (CL, EL, SL, etc.)
- Leave taken this month/year
- Leave pending approval
- Leave encashment eligible

#### 10. Shift-wise Report
- Employees per shift
- Shift coverage analysis
- Cross-shift comparison
- Shift change history

---

### Dashboard Visualizations

#### KPI Cards (Top Row)
```
┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│  Present    │ │   Absent    │ │    Late     │ │  On Leave   │
│     23      │ │      2      │ │      3      │ │      1      │
│   (92%)     │ │    (8%)     │ │   (12%)     │ │    (4%)     │
└─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘
```

#### Charts & Graphs

1. **Attendance Trend Line Chart**
   - X-axis: Days of month
   - Y-axis: Attendance percentage
   - Lines: Present %, Absent %, Late %

2. **Department-wise Donut Chart**
   - Attendance distribution by department
   - Color-coded segments

3. **Punctuality Gauge**
   - Radial gauge showing on-time %
   - Red/Yellow/Green zones

4. **Weekly Heatmap**
   - Days (Mon-Sun) vs Weeks
   - Color intensity = attendance rate

5. **Late Arrivals Bar Chart**
   - Employee-wise late frequency
   - Stacked by reason category

6. **Working Hours Comparison**
   - Expected vs Actual hours
   - Grouped bar chart by department

7. **Absenteeism Trend**
   - Month-over-month comparison
   - Seasonality patterns

8. **Employee Attendance Grid**
   - Calendar-style grid per employee
   - Color-coded status per day

---

### Export Options

#### Excel Export (.xlsx)
- **Individual Reports**: Single employee detailed report
- **Master Report**: All employees consolidated
- **Custom Date Range**: Select from/to dates
- **Department Filter**: Export by department
- **Formatted with**:
  - Company header/logo
  - Auto-calculated totals
  - Conditional formatting (late=yellow, absent=red)
  - Print-ready layout
  - Multiple sheets (Summary + Details)

#### PDF Export
- **Styled PDF Reports** using MPDF
- **Options**:
  - Portrait/Landscape orientation
  - Include/exclude charts
  - Digital signature placeholder
  - Watermark for draft reports
- **Templates**:
  - Daily Attendance Sheet
  - Monthly Muster Roll
  - Individual Employee Report
  - Payroll Summary Report
  - Management Summary (charts + KPIs)

---

### Admin Master Report Dashboard

**Location:** `/xadmin/mod/attendance/x-attendance-master.php`

#### Features:
1. **Real-time Dashboard**
   - Live attendance count
   - Who's in/out right now
   - Late arrivals alert

2. **Quick Filters**
   - Date range picker
   - Department dropdown
   - Employee search
   - Status filter (Present/Absent/Late/Leave)
   - Shift filter

3. **Bulk Actions**
   - Mark holiday for all
   - Approve pending remarks
   - Generate bulk reports
   - Send attendance reminders

4. **Scheduled Reports**
   - Daily summary email to HR
   - Weekly report to managers
   - Monthly payroll report auto-generate

5. **Export Center**
   - One-click Excel download
   - PDF with charts
   - CSV for payroll import
   - Custom report builder

---

### Employee Portal Attendance View

#### Calendar View
- Month calendar with color-coded days
- Click day to see details
- Legend for status codes
- Quick add remark option

#### List View (Enhanced)
- Sortable columns
- Search/filter
- Export own attendance (PDF)
- Print-friendly view

#### Summary Cards
- This month stats
- Year-to-date summary
- Leave balance
- Overtime hours

---

### Database Updates Required

```sql
-- Add overtime tracking fields
ALTER TABLE bombayengg_attendance ADD COLUMN
  overtimeHours DECIMAL(5,2) DEFAULT 0 AFTER earlyMinutes,
  overtimeApproved TINYINT DEFAULT 0,
  overtimeApprovedBy INT NULL,
  shiftID INT NULL;

-- Add shift master table
CREATE TABLE bombayengg_shift_master (
  shiftID INT AUTO_INCREMENT PRIMARY KEY,
  shiftName VARCHAR(50),
  shiftCode VARCHAR(10),
  startTime TIME,
  endTime TIME,
  graceMinutes INT DEFAULT 15,
  halfDayHours DECIMAL(3,1) DEFAULT 4.0,
  fullDayHours DECIMAL(3,1) DEFAULT 8.0,
  isNightShift TINYINT DEFAULT 0,
  status TINYINT DEFAULT 1
);

-- Default shift
INSERT INTO bombayengg_shift_master (shiftName, shiftCode, startTime, endTime)
VALUES ('General Shift', 'GEN', '09:00:00', '18:00:00');
```

### 2.6 Salary Advance Module
**Location:** `/xadmin/mod/salary-advance/`

**Features:**
- Advance request management
- Approval workflow
- EMI deduction setup
- Track remaining balance

### 2.7 HR Email Settings Module
**Location:** `/xadmin/mod/hr-email-settings/`

**Features:**
- Configure HR email recipients
- View email send log
- Manual resend option

---

## Phase 3: Employee Frontend Portal (xsite)

**⚠️ MANDATORY: Use `frontend-design` skill for all frontend portal pages and email templates**

### 3.1 Portal Structure
**Location:** `/xsite/mod/employee-portal/`

**Files:**
```
employee-portal/
├── x-login.php                     # Email + OTP login
├── x-home.php                      # Dashboard (own + team if manager)
├── x-attendance.php                # View attendance + add remarks
├── x-salary.php                    # View/download salary slips
├── x-documents.php                 # View/download documents
├── x-leave.php                     # Leave application (link to existing)
├── x-profile.php                   # View/edit profile
├── x-team.php                      # Manager: view team attendance/salary
├── x-employee-portal.inc.php       # Backend logic
├── header-employee.php             # Custom header
├── footer-employee.php             # Custom footer
└── js/x-employee-portal.inc.js
```

### 3.2 Portal Access Logic

```php
// On login, determine what user can see
$userID = $_SESSION['EMPLOYEE_ID'];
$isManager = $user['isLeaveManager'] == 1;

// Get own data
$myData = getEmployeeData($userID);

// If manager, also get team data
if ($isManager) {
    // Get all employees where managerID = current user
    $teamData = getTeamData($userID);
    // This includes techIlliterate employees assigned to this manager
}
```

### 3.3 Manager's Team View Features

When a manager logs into the portal:

1. **Dashboard shows:**
   - Own attendance summary
   - Team attendance overview (who's present today, late arrivals)
   - Pending remarks to review

2. **Team Attendance:**
   - View each team member's attendance calendar
   - See late arrivals and early checkouts
   - Review and approve/reject remarks
   - Add remarks on behalf of team members

3. **Team Salary Slips:**
   - View and download team members' salary slips
   - For `techIlliterate` employees who don't login

4. **Team Documents:**
   - View team members' uploaded documents

### 3.4 Attendance Remarks Flow

```
1. Biometric punch recorded as LATE (checkIn > scheduledIn + grace period)
2. System marks isLate = 1, lateMinutes = X
3. Employee (or manager for techIlliterate) can:
   - Go to Attendance page
   - Click "Add Reason" for that day
   - Submit explanation (traffic, medical, etc.)
4. Manager reviews pending remarks
5. Approves/Rejects with optional note
6. Approved remarks can affect salary deduction calculations
```

### 3.5 Authentication Flow (Email + OTP)

```
1. Employee enters email on login page
2. System validates email exists in x_admin_user (status = 1)
3. Generate 6-digit OTP, store with 10-min expiry
4. Send OTP via Brevo email
5. Employee enters OTP
6. Validate OTP and expiry
7. Set session: EMPLOYEE_LOGIN, EMPLOYEE_ID, EMPLOYEE_NAME, IS_MANAGER
8. Redirect to dashboard
```

---

## Phase 4: Camsunit Biometric Integration

### 4.1 Configuration (Already Added)

```php
// In config.inc.php
define("CAMS_AUTH_TOKEN", "sjSyrdgyeOyWgrJfVEBdQlwYkQfWCMg1");
define("CAMS_SECURITY_KEY", "pJR1U92U5ZavbgA8leRPBsr2XAuJqxQg");
define("CAMS_STGID", "");  // To be configured after device setup
define("CAMS_API_URL", "https://api.camsunit.com");
define("CAMS_CALLBACK_URL", SITEURL . "/core/camsunit-callback.php");
```

### 4.2 API Integration Files

**Location:** `/core/camsunit.inc.php`

```php
class CamsunitAPI {
    private $apiUrl;
    private $authToken;
    private $securityKey;
    private $stgid;

    public function __construct() {
        $this->apiUrl = CAMS_API_URL;
        $this->authToken = CAMS_AUTH_TOKEN;
        $this->securityKey = CAMS_SECURITY_KEY;
        $this->stgid = CAMS_STGID;
    }

    // Load punch logs with date range
    public function loadPunchLog($fromDate, $toDate, $offset = 0) { ... }

    // Add user to device
    public function addUser($userId, $firstName, $lastName) { ... }

    // Delete user from device
    public function deleteUser($userId) { ... }

    // Decrypt response if encryption enabled
    private function decryptResponse($encrypted) { ... }
}
```

### 4.3 Callback Handler

**Location:** `/core/camsunit-callback.php`

Receives real-time punches from device and:
1. Decrypts if encryption enabled
2. Finds employee by biometricID
3. Creates/updates attendance record
4. Calculates late/early status
5. Logs raw response for audit

### 4.4 Cron Sync (Backup)

**Location:** `/xsite/mx-crons.php`

Daily sync at 11:59 PM to catch any missed real-time punches.

---

## Phase 5: Automated Monthly Emails

### 5.1 Email Schedule

**Trigger:** 1st of every month at 9:00 AM
**Cron:** `0 9 1 * * php /home/bombayengg/public_html/xsite/mx-crons.php sendMonthlyHREmails`

### 5.2 Email Types

#### A. Individual Employee Email
- **To:** Each employee's email (from x_admin_user.userEmail)
- **Subject:** "Your Salary Slip for {Month} {Year} - Bombay Engineering"
- **Content:**
  - Attendance summary (present, absent, late, early checkout)
  - Salary slip breakdown
  - PDF attachment
  - Link to portal for details

#### B. HR Master Summary Email
- **To:** Configured HR email recipients (1-2 specific emails)
- **Subject:** "HR Monthly Report - {Month} {Year}"
- **Content:**
  - All employees salary summary table
  - Total payroll amount
  - Attendance summary per employee
  - Late/absence statistics
  - Excel attachment with full data

### 5.3 Email Template Design

**⚠️ Use `frontend-design` skill for HTML email templates**

Templates location: `/xsite/mod/employee-portal/email-templates/`
```
email-templates/
├── salary-slip-individual.php      # Individual salary email
├── hr-master-summary.php           # HR summary email
└── attendance-alert.php            # Optional: daily late alerts
```

---

## Phase 6: File Structure Summary

### New/Modified Directories
```
/xadmin/mod/
├── salary-structure/               # NEW: Salary components
├── salary-slip/                    # NEW: Monthly slips
├── employee-document/              # NEW: Document management
├── attendance/                     # NEW: Attendance sync
├── salary-advance/                 # NEW: Advances
└── hr-email-settings/              # NEW: Email config

/xadmin/core-admin/mod/
└── admin-user/                     # MODIFY: Add HR fields

/xsite/mod/
└── employee-portal/                # NEW: Employee self-service
    └── email-templates/            # NEW: Beautiful email templates

/uploads/
├── employee-document/              # Employee documents
└── salary-slip/                    # Generated PDFs

/core/
├── camsunit.inc.php               # NEW: Biometric API wrapper
└── camsunit-callback.php          # NEW: Real-time punch handler
```

---

## Implementation Order

### Sprint 1: Foundation
1. Alter `x_admin_user` table (add HR fields + managerID)
2. Create new database tables
3. Update admin-user module with new fields
4. Employee document module

### Sprint 2: Attendance System
5. Camsunit API integration (`camsunit.inc.php`)
6. Callback handler for real-time punches
7. Attendance module (list, calendar, manual entry)
8. Attendance remarks system
9. Attendance cron sync

### Sprint 3: Salary System
10. Salary structure module
11. Salary slip module with PDF generation
12. Salary advance module

### Sprint 4: Employee Portal (use frontend-design skill)
13. Portal authentication (Email + OTP)
14. Dashboard (own + team view for managers)
15. Attendance view with remarks submission
16. Salary slip view
17. Documents view
18. Team management for managers

### Sprint 5: Automated Emails (use frontend-design skill)
19. Design email templates (individual + HR master)
20. Email sending logic
21. Cron job for monthly emails
22. HR email settings module
23. Email log and resend functionality

### Sprint 6: Testing & Polish
24. End-to-end testing
25. Bug fixes
26. Performance optimization

---

## Critical Files to Reference

| Purpose | File |
|---------|------|
| User management | `/xadmin/core-admin/mod/admin-user/` |
| Leave system | `/xadmin/mod/employee-leave/` |
| Driver portal (auth pattern) | `/xsite/mod/driver/` |
| PDF generation | `/xadmin/mod/voucher/inc/voucher-print.inc.php` |
| Email sending | `/core/brevo.inc.php` |
| File uploads | `/core/file.inc.php` |
| Form handling | `/core/form.inc.php` |

---

## Security Considerations

1. **OTP Expiry:** 10 minutes, single use
2. **Session Timeout:** 30 minutes of inactivity
3. **Document Access:** Only own documents (or team's if manager)
4. **Salary Access:** Only own slips (or team's if manager)
5. **Manager Scope:** Can only see employees where `managerID = their userID`
6. **Audit Trail:** Log all salary slip generations, downloads, and email sends
7. **Sensitive Data:** Encrypt bank account numbers, mask Aadhaar in displays

---

## Configuration Checklist

- [x] Camsunit API credentials saved in `config.inc.php`
- [x] CAMS_STGID configured: `AWXH181060065`
- [ ] HR email recipients to be added later
- [ ] Scheduled check-in/out times (default 9:00 AM - 6:00 PM)
- [ ] Grace period for late marking (default 15 minutes?)
- [ ] Leave deduction per day formula

---

## BIOMETRIC DEVICE INTEGRATION

### Device Details
| Setting | Value |
|---------|-------|
| Model | Cams Protocol Update |
| Serial Number / STGID | `AWXH181060065` |
| Auth Token | `WDpDUNiqMz6sYtvdWvrVMcgXKVXhwTAf` |
| Security Key (AES-256-ECB) | `ZkSlVioJlraKWhHbrBW76Ou8PmM6M7ha` |
| API Endpoint | `https://robot.camsbiometrics.com/external/api3.0/biometric` |
| API Valid Until | 08-Jan-2027 |
| Server IP | `69.62.81.218` |
| Callback URL | `http://69.62.81.218/core/cams-biometric-callback.php` |

### Integration Status ✅
| Item | Status | Notes |
|------|--------|-------|
| Database tables created | ✅ DONE | `camsDevice`, `camsUser`, `camsPunch` |
| Device registered in DB | ✅ DONE | `camsDevice` table |
| Callback handler (API 3.0) | ✅ DONE | `/core/cams-biometric-callback.php` |
| HRMS sync to mx_attendance | ✅ DONE | Auto-syncs when punch received |
| Apache IP-based virtual host | ✅ DONE | Routes `69.62.81.218` to bombayengg |
| HTTP access (no HTTPS redirect) | ✅ DONE | `.htaccess` + `config.inc.php` bypassed for IP |
| Callback URL configured | ✅ DONE | Set in CAMS dashboard |
| Device connected to CAMS cloud | ✅ DONE | Device online |
| Real-time punch callback | ✅ DONE | Receiving encrypted punches |
| REST API with encryption | ✅ DONE | Requests must be AES-256-ECB encrypted |
| Auto-sync employees to device | ✅ DONE | `/core/cams-api.inc.php` + admin user integration |
| LoadPunchLog API | ✅ ENABLED | Can pull punch logs via REST |
| LoadUserInfo API | ❌ DISABLED | Returns StatusCode 33 - not enabled in CAMS settings |

### How It Works
```
Biometric Device → CAMS DataServer (cloud) → Your Callback URL
                                                    ↓
                                           camsPunch table
                                                    ↓
                                           mx_attendance table
```

1. Employee punches on biometric device
2. Device sends data to CAMS cloud (DataServer)
3. CAMS DataServer calls our callback URL with encrypted punch data
4. Callback handler decrypts, stores in `camsPunch` and syncs to `mx_attendance`

### CRITICAL: REST API Requires Encryption (8-Jan-2026)

**Discovery:** CAMS REST API returns `StatusCode 19` (API_RESPONSE_INVALID_RAW_DATA) for unencrypted requests. All REST API requests MUST be encrypted with AES-256-ECB.

**How to make REST API requests:**
```php
$key = "ZkSlVioJlraKWhHbrBW76Ou8PmM6M7ha";
$payload = ["Load" => ["PunchLog" => ["Filter" => [...]]], "AuthToken" => "..."];

// ENCRYPT the request
$encrypted = openssl_encrypt(json_encode($payload), "aes-256-ecb", $key, OPENSSL_RAW_DATA);
$encodedPayload = base64_encode($encrypted);

// Send encrypted payload as POST body
curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedPayload);

// DECRYPT the response
$respData = base64_decode($response);
$decrypted = openssl_decrypt($respData, "aes-256-ecb", $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
```

### REST API Commands Working
| Command | Status | Notes |
|---------|--------|-------|
| `Load.PunchLog` | ✅ Works | Pull punch logs with date filter |
| `Load.UserInfo` | ❌ Disabled | StatusCode 33 - needs enabling in CAMS extended settings |
| `Sync` | ✅ Works | Triggers device sync |
| `DeviceSync` | ✅ Works | Triggers device sync |
| `ReloadLog` | ✅ Works | Reload attendance logs |
| `FetchLog` | ✅ Works | Fetch logs from device |
| `UploadLog` | ✅ Works | Upload logs to cloud |

### CAMS Extended Settings Required
In CAMS Dashboard → Extended Settings:
- ✅ LoadPunchLog = Yes
- ✅ Push User Data = Yes
- ✅ Restful API = Yes
- ❌ Load User Data = No (needs to be enabled to pull users via API)

### Configuration Changes Made (7-Jan-2026)

**1. Apache Virtual Host** (`/etc/httpd/conf/httpd.conf`)
```apache
<VirtualHost 69.62.81.218:80>
    DocumentRoot "/home/bombayengg/public_html"
    ServerName 69.62.81.218
    ...
</VirtualHost>
```

**2. PHP www redirect bypass** (`/config.inc.php`)
```php
// Force www redirect (except for IP access - biometric device)
$isIPAccess = preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $host);
if (strpos($host, 'www.') !== 0 && !$isIPAccess) {
    // redirect to www...
}
```

**3. .htaccess IP bypass** (`/public_html/.htaccess`)
```apache
RewriteCond %{HTTP_HOST} ^69\.62\.81\.218 [NC,OR]
RewriteRule ^ - [L]
```

### Known Issue: Historical Data (8-Jan-2026)

**Problem:** CAMS cloud only has recent punch data. Historical data from device not synced to cloud.

**Why:** Punch logs in CAMS cloud are marked as "sent" once delivered via callback. The LoadPunchLog API only returns unsent data.

**Solutions:**
1. Use "Resend All Attendance Data" in CAMS dashboard (only sends what's in cloud)
2. On device: Menu → USB/Communications → Upload Logs (forces full upload)
3. Enable "Fetch Attendance from Device" in CAMS dashboard
4. Contact CAMS support for manual device sync

### CAMS API Commands Available
| Command | Entity | Purpose | Status |
|---------|--------|---------|--------|
| `Load` | `PunchLog` | Pull attendance logs with date filter | ✅ Working |
| `Load` | `UserInfo` | Get all registered users from device | ❌ Disabled |
| `Add` | `User` | Add new user to device | ✅ Working |
| `Delete` | `User` | Remove user from device | ✅ Working |
| `Sync` | - | Trigger device sync | ✅ Working |
| `DeviceSync` | - | Trigger device sync | ✅ Working |
| `ReloadLog` | - | Reload attendance logs | ✅ Working |
| `FetchLog` | - | Fetch logs from device | ✅ Working |
| `UploadLog` | - | Upload logs to cloud | ✅ Working |

### Files for Integration
| File | Purpose |
|------|---------|
| `/core/cams-biometric-callback.php` | Main callback - receives punches, syncs to mx_attendance |
| `/core/cams-api.inc.php` | API helper - encrypted REST API functions |
| `/core/cams-pull-all-data.php` | Pull data via encrypted REST API |
| `/database_migrations/cams_biometric_tables.sql` | Creates all CAMS tables + registers device |

### API Functions in cams-api.inc.php (Updated 8-Jan-2026)

| Function | Purpose | Returns |
|----------|---------|---------|
| `camsMakeRequest($payload)` | Make encrypted API request to CAMS | `['success' => bool, 'data' => array, 'message' => string]` |
| `camsAddUser($userId, $firstName, $lastName)` | Add user to biometric device | `['success' => bool, 'message' => string]` |
| `camsDeleteUser($userId)` | Delete user from biometric device | `['success' => bool, 'message' => string]` |
| `camsLoadPunchLog($startDate, $endDate)` | Pull punch logs (Y-m-d format) | `['success' => bool, 'data' => array, 'total' => int]` |
| `camsSyncDevice()` | Trigger device sync | `['success' => bool, 'message' => string]` |
| `camsDecryptResponse($response)` | Decrypt AES-256-ECB response | Decoded JSON array or null |
| `camsLogApi($action, $userId, $name, $response, $httpCode)` | Log API calls | void |

**Usage Example:**
```php
require_once COREPATH . '/cams-api.inc.php';

// Add user to device
$result = camsAddUser('5', 'New', 'Employee');
if ($result['success']) {
    echo "User added successfully";
}

// Pull punch logs
$punches = camsLoadPunchLog('2026-01-01', '2026-01-08');
if ($punches['success']) {
    foreach ($punches['data'] as $punch) {
        echo $punch['UserId'] . ' punched at ' . $punch['LogTime'];
    }
}
```

### Auto-Sync Employees to Device
When a new employee is added or their biometricID is changed in the admin panel, the system automatically syncs with the CAMS biometric device:

**Location:** `/xadmin/core-admin/mod/admin-user/x-admin-user.inc.php`

- **On Add:** If biometricID is set and autoAttendance is OFF, calls `camsAddUser()`
- **On Update:** If biometricID changed, calls `camsDeleteUser()` for old ID, `camsAddUser()` for new ID
- **Skipped for:** Employees with autoAttendance enabled (warehouse/field staff)

### Employee Biometric Mapping (8-Jan-2026)

The `mx_x_admin_user.biometricID` field stores the device user ID. When punches come in via callback, they're mapped using this field.

| BiometricID | Employee | Admin UserID | Status |
|-------------|----------|--------------|--------|
| 1 | Manish Narvekar | 8 | ✅ Active |
| 2 | Ganesh Murkute | 11 | ✅ Active |
| 3 | Sakshi Satam | 12 | ✅ Active |
| 4 | Ananda Pawar | 13 | ✅ Active |
| 6 | Paritosh Ajmera | 22 | ✅ Active |
| 7 | Pranav Gandhi | 10 | ✅ Active |

**Data Flow:**
1. Employee punches on device (registered with biometricID, e.g., "2")
2. Device sends punch to CAMS cloud
3. Callback receives punch with `user_id = "2"`
4. System looks up `mx_x_admin_user WHERE biometricID = "2"` → finds Ganesh (userID 11)
5. Attendance recorded in `mx_attendance` for userID 11

### Encryption/Decryption Code (PHP)
```php
$key = "ZkSlVioJlraKWhHbrBW76Ou8PmM6M7ha";

// ENCRYPT request (required for REST API)
$payload = json_encode(["Load" => ["PunchLog" => [...]], "AuthToken" => "..."]);
$encrypted = openssl_encrypt($payload, "aes-256-ecb", $key, OPENSSL_RAW_DATA);
$requestBody = base64_encode($encrypted);

// DECRYPT response
$response = "encrypted_base64_string";
$data = base64_decode($response);
$decrypted = openssl_decrypt($data, "aes-256-ecb", $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
echo trim($decrypted);
```

### Running CLI Scripts
CLI scripts need to set `$_SERVER` vars before including config.inc.php:
```php
$_SERVER['HTTP_HOST'] = 'www.bombayengg.com';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';
require_once "/home/bombayengg/public_html/config.inc.php";
```

---

## AUTO-ATTENDANCE FOR WAREHOUSE EMPLOYEES

### Overview
Employees without access to biometric devices (warehouse/field staff) get automatic attendance marked daily via cron job. Managers can modify if exceptions occur (late arrival, early leave, absent).

### Database Changes

```sql
-- Added to mx_x_admin_user
ALTER TABLE mx_x_admin_user ADD COLUMN autoAttendance TINYINT(1) DEFAULT 0;
ALTER TABLE mx_x_admin_user ADD COLUMN saturdayStartTime TIME DEFAULT NULL;
ALTER TABLE mx_x_admin_user ADD COLUMN saturdayEndTime TIME DEFAULT NULL;

-- Added 'auto' to attendance source enum
ALTER TABLE mx_attendance MODIFY COLUMN source ENUM('biometric','manual','system','auto') DEFAULT 'biometric';
```

### Employee Admin Form Changes
**Location:** `/xadmin/core-admin/mod/admin-user/x-admin-user-add-edit.php`

Added "No Biometric Device Access" checkbox in Work Timing section:
- When checked: Employee gets auto-attendance daily (Mon-Sat)
- When unchecked: Employee uses biometric or manual entry

### Cron Jobs

#### 1. Auto-Attendance Cron
**File:** `/cron/hrms-auto-attendance.php`

**Schedule:** Daily at 10:05 AM IST
```
35 4 * * * /usr/bin/php /home/bombayengg/public_html/cron/hrms-auto-attendance.php >> /home/bombayengg/logs/hrms-auto-attendance.log 2>&1
```

**Logic:**
1. Skip Sundays (weekly off)
2. Skip holidays (from `mx_holiday_master`)
3. For each employee with `autoAttendance = 1`:
   - Use Saturday timings if Saturday
   - Check if attendance already exists
   - Insert attendance record with:
     - `attendanceStatus = 'present'`
     - `source = 'auto'`
     - `remarks = 'Auto-marked (no biometric)'`

---

#### 2. Monthly Attendance Reports Cron (NEW - January 2026)
**File:** `/cron/hrms-monthly-attendance-email.php`

**Schedule:** 1st of every month at 8:00 AM IST
```
30 2 1 * * /usr/bin/php /home/bombayengg/public_html/cron/hrms-monthly-attendance-email.php >> /home/bombayengg/logs/hrms-monthly-email.log 2>&1
```

**What it does:**
1. **Individual Employee Emails** - Each employee receives their own attendance report for the previous month
2. **Individual PDF Generation** - Creates beautiful detailed PDFs for all employees with:
   - Company logo and branding
   - Summary cards (Present, Absent, Leave, Late, Hours, Working Days)
   - Day-by-day detailed table with scheduled/actual times
   - Late/early checkout indicators
   - Legend and statistics
3. **Master Admin Batch Emails** - Sends all individual PDFs to master admins:
   - Recipients: `manishbeskkc@gmail.com`, `paritosh.ajmera@gmail.com`
   - PDFs batched (10 per email) to avoid attachment limits
   - If 15 employees: 2 emails sent (Batch 1/2, Batch 2/2)
4. **Master Report** - Summary report with all employees to HR admins

**PDF Storage Location:**
```
/uploads/attendance-reports/YYYY-MM/
Example: /uploads/attendance-reports/2026-01/Manish_Narvekar_Attendance_2026_1.pdf
```

**Manual Testing:**
```bash
# CLI
php /home/bombayengg/public_html/cron/hrms-monthly-attendance-email.php

# Browser (with key)
https://www.bombayengg.net/cron/hrms-monthly-attendance-email.php?cron_key=BES_HRMS_2024_SECURE
```

**Master Admin Recipients Defined:**
```php
define('MASTER_ADMIN_RECIPIENTS', [
    ['email' => 'manishbeskkc@gmail.com', 'name' => 'Manish Narvekar'],
    ['email' => 'paritosh.ajmera@gmail.com', 'name' => 'Paritosh Ajmera']
]);
```

**Key Functions Added:**
| Function | Purpose |
|----------|---------|
| `getDetailedEmployeeAttendanceData()` | Get full attendance data with leave, holidays, Saturday timings |
| `generateIndividualDetailedPDF()` | Create beautiful PDF using mPDF |
| `sendMasterAdminBatchEmail()` | Send batched PDFs to admins |
| `buildMasterAdminBatchEmailHTML()` | Build email HTML with employee list |

### Shift Timings Configuration

| Day | Default Start | Default End |
|-----|---------------|-------------|
| Mon-Fri | 10:00 AM | 6:00 PM |
| Saturday | 10:00 AM | 4:00 PM |

Employee-specific overrides available in admin panel:
- Mon-Fri Start Time
- Mon-Fri End Time
- Saturday Start Time
- Saturday End Time
- Late Grace (minutes)

### Current Auto-Attendance Employees

| Employee | User ID | Mon-Fri | Saturday | Grace |
|----------|---------|---------|----------|-------|
| Pravin Jadhav | 19 | 10:00-18:00 | 10:00-16:00 | 15 min |
| Ganesh Patil | 18 | 10:00-18:00 | 10:00-16:00 | 15 min |

### Files Modified/Created

| File | Change |
|------|--------|
| `/cron/hrms-auto-attendance.php` | NEW - Daily auto-attendance cron |
| `/xadmin/core-admin/mod/admin-user/x-admin-user-add-edit.php` | Added checkbox + Saturday timing fields |
| `/xadmin/core-admin/mod/admin-user/x-admin-user.inc.php` | Handle checkbox in add/update |

---

## DATA UPDATES

### Historical Leave Records Added (January 12, 2026)

**Manish (userID: 8)** - Historical leaves added as Casual Leave (Approved):

| Date | Type | Reason |
|------|------|--------|
| 19-Jul-2025 | Full Day | Personal |
| 09-Aug-2025 | Full Day | Personal |
| 02-Sep-2025 | Full Day | Personal |
| 05-Nov-2025 | Half Day | Personal |
| 26-Nov-2025 | Full Day | Personal |
| 27-Nov-2025 | Full Day | Personal |

---

## IMPLEMENTATION STATUS

### ✅ Completed

| Feature | Status | Location |
|---------|--------|----------|
| Employee HR fields in admin | ✅ DONE | `/xadmin/core-admin/mod/admin-user/` |
| Leave management system | ✅ DONE | `/xadmin/mod/employee-leave/` |
| Holiday master | ✅ DONE | `/xadmin/mod/hrms-settings/` |
| Auto-attendance for warehouse | ✅ DONE | `/cron/hrms-auto-attendance.php` |
| Employee-specific shift timings | ✅ DONE | Mon-Fri + Saturday separate |
| CAMS biometric callback | ✅ DONE | `/core/cams-biometric-callback.php` |
| Attendance database tables | ✅ DONE | Full schema in place |
| **Salary structure module** | ✅ DONE | `/xadmin/mod/salary-structure/` |
| **Salary slip module** | ✅ DONE | `/xadmin/mod/salary-slip/` |
| **Employee portal (xsite)** | ✅ DONE | `/xsite/mod/hrms/` |
| **Attendance module (admin)** | ✅ DONE | `/xadmin/mod/attendance/` |
| **Attendance reports/exports** | ✅ DONE | Dashboard, Excel, PDF exports |
| **Monthly email automation** | ✅ DONE | `/cron/hrms-monthly-attendance-email.php` |
| **Individual PDF reports** | ✅ DONE | Beautiful styled PDFs for all employees |
| **Master admin batch emails** | ✅ DONE | Batched PDFs (10/email) to admins |

### ⏳ Pending

| Feature | Priority | Notes |
|---------|----------|-------|
| Enable LoadUserInfo in CAMS | MEDIUM | Need to enable in CAMS Extended Settings to pull users |
| Pull historical data from device | HIGH | Data is on device but not in cloud - need manual upload |
| ~~Schedule auto-attendance cron~~ | ✅ DONE | Added to crontab - runs daily at 10:05 AM |
| ~~HR email automation~~ | ✅ DONE | Monthly attendance reports to master admins (Jan 2026) |

---

## MODULE LOCATIONS

### Admin Panel (xadmin)
| Module | Path | Features |
|--------|------|----------|
| Salary Structure | `/xadmin/mod/salary-structure/` | Define salary components per employee |
| Salary Slip | `/xadmin/mod/salary-slip/` | Generate, mark paid, PDF |
| Attendance | `/xadmin/mod/attendance/` | List, dashboard, reports, export |
| Employee Leave | `/xadmin/mod/employee-leave/` | Apply, approve, reject |
| HRMS Settings | `/xadmin/mod/hrms-settings/` | Holiday master, config |

### Employee Portal (xsite)
| Page | Path | Features |
|------|------|----------|
| Login | `/xsite/mod/hrms/x-login.php` | Email + OTP authentication |
| Dashboard | `/xsite/mod/hrms/x-home.php` | Welcome, quick stats |
| Attendance | `/xsite/mod/hrms/x-attendance.php` | View own attendance |
| Salary | `/xsite/mod/hrms/x-salary.php` | View/download salary slips |
| Leave | `/xsite/mod/hrms/x-leave.php` | Apply for leave |
| Profile | `/xsite/mod/hrms/x-profile.php` | View/edit profile |
| Team | `/xsite/mod/hrms/x-team.php` | Manager: view team |
| Reports | `/xsite/mod/hrms/x-reports.php` | Attendance reports |
| Documents | `/xsite/mod/hrms/x-documents.php` | View documents |

---

## NOTES

- **Auto-attendance cron:** ✅ Scheduled - runs daily at 10:05 AM for non-biometric employees
- **CAMS callback:** ✅ Configured and working - receiving real-time punches
- **REST API:** ✅ Working with encryption - can pull punch logs, trigger syncs
- **Historical data issue:** Device has data locally but CAMS cloud doesn't have it. Need to force upload from device menu or contact CAMS support.
- **Users API:** Disabled in CAMS settings - enable "Load User Data" in Extended Settings to pull user list
