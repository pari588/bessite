# HR Portal Implementation Plan (Revised)

## Overview

Build a comprehensive HR Portal with:
- **Backend (xadmin):** Employee onboarding, salary management, document management, attendance sync, leave management
- **Frontend (xsite):** Employee self-service portal with Email+OTP login
- **Manager View:** Managers can view & manage their assigned team members (labour/non-computer users)

**Scale:** Under 25 employees | **Architecture:** Extend existing `x_admin_user` system

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

### 2.5 Attendance Module
**Location:** `/xadmin/mod/attendance/`

**Files:**
```
attendance/
├── x-attendance.inc.php            # Business logic + Camsunit sync
├── x-attendance-list.php           # Daily/monthly attendance view
├── x-attendance-calendar.php       # Calendar view
├── x-attendance-manual.php         # Manual attendance entry
├── x-attendance-remarks.php        # View/approve employee remarks
└── inc/js/x-attendance.inc.js
```

**Features:**
- Camsunit API integration (real-time + cron sync)
- View attendance calendar
- Mark manual attendance (exceptions)
- **Late/Early tracking with reason capture**
- Monthly attendance report
- Approve/reject employee remarks

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
- [ ] CAMS_STGID to be configured after device setup
- [ ] HR email recipients to be added later
- [ ] Scheduled check-in/out times (default 9:00 AM - 6:00 PM)
- [ ] Grace period for late marking (default 15 minutes?)
- [ ] Leave deduction per day formula
