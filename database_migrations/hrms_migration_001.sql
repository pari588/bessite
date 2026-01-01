-- ============================================================================
-- HRMS DATABASE MIGRATION - Version 001
-- Bombay Engineering Syndicate
-- Created: 2026-01-01
-- ============================================================================

-- ============================================================================
-- PART 1: EXTEND mx_x_admin_user TABLE WITH HR FIELDS
-- ============================================================================

-- Add HR fields to existing admin user table
ALTER TABLE mx_x_admin_user
  ADD COLUMN employeeCode VARCHAR(20) NULL AFTER userID,
  ADD COLUMN dateOfBirth DATE NULL AFTER imageName,
  ADD COLUMN gender ENUM('M','F','O') NULL AFTER dateOfBirth,
  ADD COLUMN bloodGroup VARCHAR(5) NULL AFTER gender,
  ADD COLUMN emergencyContact VARCHAR(15) NULL AFTER bloodGroup,
  ADD COLUMN emergencyContactName VARCHAR(100) NULL AFTER emergencyContact,
  ADD COLUMN dateOfJoining DATE NULL AFTER emergencyContactName,
  ADD COLUMN designation VARCHAR(100) NULL AFTER dateOfJoining,
  ADD COLUMN department VARCHAR(100) NULL AFTER designation,
  ADD COLUMN employmentType ENUM('permanent','contract','probation') DEFAULT 'permanent' AFTER department,
  ADD COLUMN managerID INT NULL AFTER employmentType,
  ADD COLUMN bankName VARCHAR(100) NULL AFTER managerID,
  ADD COLUMN bankAccountNo VARCHAR(30) NULL AFTER bankName,
  ADD COLUMN bankIFSC VARCHAR(15) NULL AFTER bankAccountNo,
  ADD COLUMN panNo VARCHAR(15) NULL AFTER bankIFSC,
  ADD COLUMN aadhaarNo VARCHAR(15) NULL AFTER panNo,
  ADD COLUMN currentAddress TEXT NULL AFTER aadhaarNo,
  ADD COLUMN permanentAddress TEXT NULL AFTER currentAddress,
  ADD COLUMN biometricID VARCHAR(50) NULL AFTER permanentAddress,
  ADD COLUMN loginOTP VARCHAR(6) NULL AFTER biometricID,
  ADD COLUMN otpExpiry DATETIME NULL AFTER loginOTP,
  ADD COLUMN lastPortalLogin DATETIME NULL AFTER otpExpiry,
  ADD COLUMN dateOfExit DATE NULL AFTER lastPortalLogin,
  ADD COLUMN exitReason TEXT NULL AFTER dateOfExit;

-- Add indexes for efficient lookups
ALTER TABLE mx_x_admin_user
  ADD INDEX idx_employee_code (employeeCode),
  ADD INDEX idx_manager (managerID),
  ADD INDEX idx_biometric (biometricID),
  ADD INDEX idx_department (department);

-- ============================================================================
-- PART 2: CREATE SALARY STRUCTURE TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS mx_salary_structure (
  structureID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT NOT NULL,
  effectiveFrom DATE NOT NULL,
  effectiveTo DATE NULL,
  -- Earnings
  basicSalary DECIMAL(12,2) NOT NULL DEFAULT 0,
  hra DECIMAL(12,2) DEFAULT 0,
  conveyanceAllowance DECIMAL(12,2) DEFAULT 0,
  medicalAllowance DECIMAL(12,2) DEFAULT 0,
  specialAllowance DECIMAL(12,2) DEFAULT 0,
  otherAllowance DECIMAL(12,2) DEFAULT 0,
  -- Calculated
  grossSalary DECIMAL(12,2) DEFAULT 0,
  -- Metadata
  remarks TEXT NULL,
  status TINYINT DEFAULT 1,
  createdBy INT NULL,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  updatedAt DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user_effective (userID, effectiveFrom),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- PART 3: CREATE SALARY SLIP TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS mx_salary_slip (
  slipID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT NOT NULL,
  salaryMonth INT NOT NULL,
  salaryYear INT NOT NULL,
  structureID INT NULL,
  -- Earnings (copied from structure + adjustments)
  basicSalary DECIMAL(12,2) DEFAULT 0,
  hra DECIMAL(12,2) DEFAULT 0,
  conveyanceAllowance DECIMAL(12,2) DEFAULT 0,
  medicalAllowance DECIMAL(12,2) DEFAULT 0,
  specialAllowance DECIMAL(12,2) DEFAULT 0,
  otherAllowance DECIMAL(12,2) DEFAULT 0,
  totalEarnings DECIMAL(12,2) DEFAULT 0,
  -- Deductions
  leavesDeducted INT DEFAULT 0,
  leaveDeductionAmount DECIMAL(12,2) DEFAULT 0,
  advanceDeduction DECIMAL(12,2) DEFAULT 0,
  otherDeduction DECIMAL(12,2) DEFAULT 0,
  deductionRemarks TEXT NULL,
  totalDeductions DECIMAL(12,2) DEFAULT 0,
  -- Net
  netSalary DECIMAL(12,2) DEFAULT 0,
  -- Actual Amount Paid
  amountPaid DECIMAL(12,2) NULL,
  -- Attendance Summary
  workingDays INT DEFAULT 0,
  presentDays INT DEFAULT 0,
  absentDays INT DEFAULT 0,
  leavesTaken INT DEFAULT 0,
  lateDays INT DEFAULT 0,
  earlyCheckoutDays INT DEFAULT 0,
  -- Document
  slipPDF VARCHAR(255) NULL,
  -- Status: pending → paid → slip_generated → emailed
  slipStatus ENUM('pending','paid','slip_generated','emailed') DEFAULT 'pending',
  paidOn DATE NULL,
  paidBy INT NULL,
  paymentMode VARCHAR(50) NULL,
  transactionRef VARCHAR(100) NULL,
  paymentRemarks TEXT NULL,
  -- Timestamps
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  generatedAt DATETIME NULL,
  emailSentAt DATETIME NULL,
  status TINYINT DEFAULT 1,
  UNIQUE KEY unique_slip (userID, salaryMonth, salaryYear),
  INDEX idx_month_year (salaryMonth, salaryYear),
  INDEX idx_status (slipStatus),
  INDEX idx_user (userID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- PART 4: CREATE EMPLOYEE DOCUMENTS TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS mx_employee_document (
  documentID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT NOT NULL,
  documentType ENUM('aadhaar','pan','passport','photo','appointment_letter',
                    'increment_letter','exit_letter','experience_letter',
                    'policy','training_cert','other') DEFAULT 'other',
  documentName VARCHAR(255) NOT NULL,
  fileName VARCHAR(255) NOT NULL,
  fileSize INT DEFAULT 0,
  uploadedBy INT NULL,
  remarks TEXT NULL,
  validUpto DATE NULL,
  status TINYINT DEFAULT 1,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (userID),
  INDEX idx_type (documentType),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- PART 5: CREATE ATTENDANCE TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS mx_attendance (
  attendanceID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT NOT NULL,
  attendanceDate DATE NOT NULL,
  scheduledIn TIME DEFAULT '09:00:00',
  scheduledOut TIME DEFAULT '18:00:00',
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
  biometricRaw TEXT NULL,
  remarks TEXT NULL,
  syncedAt DATETIME NULL,
  status TINYINT DEFAULT 1,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_attendance (userID, attendanceDate),
  INDEX idx_date (attendanceDate),
  INDEX idx_user_month (userID, attendanceDate),
  INDEX idx_status (attendanceStatus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- PART 6: CREATE ATTENDANCE REMARKS TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS mx_attendance_remarks (
  remarkID INT AUTO_INCREMENT PRIMARY KEY,
  attendanceID INT NOT NULL,
  userID INT NOT NULL,
  remarkType ENUM('late_arrival','early_checkout','absence','correction','other') NOT NULL,
  reason TEXT NOT NULL,
  submittedBy INT NOT NULL,
  submittedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  -- Manager Review
  reviewedBy INT NULL,
  reviewedAt DATETIME NULL,
  reviewStatus ENUM('pending','approved','rejected') DEFAULT 'pending',
  reviewNote TEXT NULL,
  status TINYINT DEFAULT 1,
  INDEX idx_attendance (attendanceID),
  INDEX idx_user (userID),
  INDEX idx_review_status (reviewStatus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- PART 7: CREATE SALARY ADVANCE TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS mx_salary_advance (
  advanceID INT AUTO_INCREMENT PRIMARY KEY,
  userID INT NOT NULL,
  advanceAmount DECIMAL(12,2) NOT NULL,
  advanceDate DATE NOT NULL,
  reason TEXT NULL,
  approvedBy INT NULL,
  approvedAt DATETIME NULL,
  -- Deduction Settings
  deductFromMonth INT NULL,
  deductFromYear INT NULL,
  monthlyDeduction DECIMAL(12,2) DEFAULT 0,
  totalDeducted DECIMAL(12,2) DEFAULT 0,
  remainingAmount DECIMAL(12,2) DEFAULT 0,
  -- Status
  advanceStatus ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
  status TINYINT DEFAULT 1,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  updatedAt DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user (userID),
  INDEX idx_status (advanceStatus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- PART 8: CREATE HR EMAIL LOG TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS mx_hr_email_log (
  logID INT AUTO_INCREMENT PRIMARY KEY,
  emailType ENUM('individual_slip','hr_master','attendance_summary') NOT NULL,
  userID INT NULL,
  salaryMonth INT NULL,
  salaryYear INT NULL,
  recipientEmail VARCHAR(255) NOT NULL,
  emailSubject VARCHAR(255) NULL,
  emailBody TEXT NULL,
  attachmentPath VARCHAR(255) NULL,
  emailStatus ENUM('sent','failed','pending') DEFAULT 'pending',
  errorMessage TEXT NULL,
  sentAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_month_year (salaryMonth, salaryYear),
  INDEX idx_type (emailType),
  INDEX idx_status (emailStatus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- PART 9: CREATE HR EMAIL RECIPIENTS TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS mx_hr_email_recipients (
  recipientID INT AUTO_INCREMENT PRIMARY KEY,
  recipientName VARCHAR(100) NOT NULL,
  recipientEmail VARCHAR(255) NOT NULL,
  emailTypes SET('individual_slip','hr_master','attendance_summary') DEFAULT 'hr_master',
  status TINYINT DEFAULT 1,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- PART 10: CREATE HRMS SETTINGS TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS mx_hrms_settings (
  settingID INT AUTO_INCREMENT PRIMARY KEY,
  settingKey VARCHAR(100) NOT NULL UNIQUE,
  settingValue TEXT NULL,
  settingDescription VARCHAR(255) NULL,
  status TINYINT DEFAULT 1,
  updatedAt DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default HRMS settings
INSERT INTO mx_hrms_settings (settingKey, settingValue, settingDescription) VALUES
('work_start_time', '09:00', 'Default work start time'),
('work_end_time', '18:00', 'Default work end time'),
('late_grace_minutes', '15', 'Grace period before marking late (minutes)'),
('early_checkout_grace_minutes', '15', 'Grace period for early checkout (minutes)'),
('working_days_per_month', '26', 'Standard working days per month'),
('leave_deduction_per_day', '0', 'Amount to deduct per leave day (0 = auto calculate from salary)'),
('late_deduction_per_instance', '0', 'Amount to deduct per late instance (0 = no deduction)'),
('email_send_day', '1', 'Day of month to send salary emails (1-28)'),
('email_send_time', '09:00', 'Time to send salary emails (HH:MM)');

-- ============================================================================
-- VERIFICATION QUERIES (Run after migration)
-- ============================================================================

-- Check new columns in mx_x_admin_user
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'mx_x_admin_user' AND TABLE_SCHEMA = 'bombayengg';

-- Check all new tables created
-- SHOW TABLES LIKE 'mx_%';

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================
