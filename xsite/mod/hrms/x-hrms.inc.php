<?php
/**
 * HRMS Employee Portal - Backend Logic
 * Handles Email+OTP authentication and employee data retrieval
 */

require_once dirname(__FILE__) . "/../../../core/brevo.inc.php";
require_once dirname(__FILE__) . "/../../../core/leave-management.inc.php";

// Load PhpSpreadsheet for Excel exports
require_once dirname(__FILE__) . "/../../../vendor/autoload.php";
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Master HR Admin Email
define('HRMS_MASTER_ADMIN_EMAIL', 'paritosh.ajmera@gmail.com');

/**
 * Check if current user is HR Admin
 * Also returns true if we're in "viewing as" mode (original user was HR Admin)
 */
function isHRMasterAdmin()
{
    // If we're viewing as another user, check the original HR Admin flag
    if (!empty($_SESSION['HRMS_VIEWING_AS']) && !empty($_SESSION['HRMS_ORIGINAL_IS_HR_ADMIN'])) {
        return $_SESSION['HRMS_ORIGINAL_IS_HR_ADMIN'] === true;
    }

    // Master admin: userID=3 (paritosh.ajmera@gmail.com / admin) always has full access
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;
    if ($userID == 3) {
        return true;
    }

    // Check current user's HR Admin flag
    return ($_SESSION['HRMS_IS_HR_ADMIN'] ?? false) === true;
}

/**
 * Check if user can view employee details (is manager of employee OR is HR admin)
 */
function canViewEmployee($employeeUserID)
{
    global $DB;

    // HR Master Admin can view everyone
    if (isHRMasterAdmin()) {
        return true;
    }

    // Check if current user is manager of this employee
    $managerID = $_SESSION['HRMS_USER_ID'] ?? 0;
    if (!$managerID) return false;

    $DB->vals = array(1, $employeeUserID, $managerID);
    $DB->types = "iii";
    $DB->sql = "SELECT userID FROM `" . $DB->pre . "x_admin_user` WHERE status=? AND userID=? AND managerID=?";
    $DB->dbRow();

    return $DB->numRows > 0;
}

// Handle /hrms/ base URL redirect (before any output)
global $TPL;
if (isset($TPL) && $TPL->pageUri === 'hrms') {
    // Check session directly since function may not be defined yet
    if (isset($_SESSION['HRMS_LOGIN']) && $_SESSION['HRMS_LOGIN'] === true) {
        header('Location: ' . SITEURL . '/hrms/home/');
    } else {
        header('Location: ' . SITEURL . '/hrms/login/');
    }
    exit;
}

/**
 * Send OTP to employee email
 */
function sendEmployeeOTP()
{
    global $DB;
    $response = array('err' => 1, 'msg' => 'Invalid email address');

    $email = trim(mysqli_real_escape_string($DB->con, $_POST['userEmail'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['msg'] = 'Please enter a valid email address';
        return $response;
    }

    // Check if email exists in admin_user table (active employees only)
    $DB->vals = array(1, $email);
    $DB->types = "is";
    $DB->sql = "SELECT userID, userName, userEmail FROM `" . $DB->pre . "x_admin_user` WHERE status=? AND userEmail=?";
    $employee = $DB->dbRow();

    if ($DB->numRows == 0) {
        $response['msg'] = 'Email not found. Please contact HR.';
        return $response;
    }

    // Generate 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Store OTP in database
    $DB->table = $DB->pre . "x_admin_user";
    $DB->data = array(
        "loginOTP" => $otp,
        "otpExpiry" => $otpExpiry
    );

    if ($DB->dbUpdate("userID=?", "i", array($employee['userID']))) {
        // Send OTP via Brevo
        $emailSent = sendHRMSOTPEmail($employee['userEmail'], $employee['userName'], $otp);

        if ($emailSent) {
            $response['err'] = 0;
            $response['msg'] = 'OTP sent to your email';
            $response['email'] = maskEmail($email);
        } else {
            $response['msg'] = 'Failed to send OTP. Please try again.';
        }
    } else {
        $response['msg'] = 'System error. Please try again.';
    }

    return $response;
}

/**
 * Verify OTP and login
 */
function verifyEmployeeOTP()
{
    global $DB;
    $response = array('err' => 1, 'msg' => 'Invalid OTP');

    $email = trim(mysqli_real_escape_string($DB->con, $_POST['userEmail'] ?? ''));
    $otp = trim(mysqli_real_escape_string($DB->con, $_POST['otp'] ?? ''));

    if (empty($email) || empty($otp)) {
        $response['msg'] = 'Email and OTP are required';
        return $response;
    }

    // Verify OTP
    $DB->vals = array(1, $email, $otp);
    $DB->types = "iss";
    $DB->sql = "SELECT userID, userName, displayName, userEmail, isLeaveManager, isAccountsPerson, isHRAdmin, techIlliterate, otpExpiry
                FROM `" . $DB->pre . "x_admin_user`
                WHERE status=? AND userEmail=? AND loginOTP=?";
    $employee = $DB->dbRow();

    if ($DB->numRows == 0) {
        $response['msg'] = 'Invalid OTP. Please try again.';
        return $response;
    }

    // Check OTP expiry
    if (strtotime($employee['otpExpiry']) < time()) {
        $response['msg'] = 'OTP has expired. Please request a new one.';
        return $response;
    }

    // Clear OTP and update last login
    $DB->table = $DB->pre . "x_admin_user";
    $DB->data = array(
        "loginOTP" => null,
        "otpExpiry" => null,
        "lastPortalLogin" => date('Y-m-d H:i:s')
    );
    $DB->dbUpdate("userID=?", "i", array($employee['userID']));

    // Set session
    $_SESSION['HRMS_LOGIN'] = true;
    $_SESSION['HRMS_USER_ID'] = $employee['userID'];
    $_SESSION['HRMS_USER_NAME'] = $employee['displayName'] ?: $employee['userName'];
    $_SESSION['HRMS_USER_EMAIL'] = $employee['userEmail'];

    // Master admin (userID=3 / admin) gets all privileges
    $isMasterAdmin = ($employee['userID'] == 3 || $employee['isHRAdmin'] == 1);
    $_SESSION['HRMS_IS_MANAGER'] = (($employee['isLeaveManager'] == 1 || $isMasterAdmin) ? true : false);
    $_SESSION['HRMS_IS_ACCOUNTS'] = (($employee['isAccountsPerson'] == 1) ? true : false); // Don't inherit from master admin
    $_SESSION['HRMS_IS_HR_ADMIN'] = ($isMasterAdmin ? true : false);

    $response['err'] = 0;
    $response['msg'] = 'Login successful';
    $response['redirect'] = SITEURL . '/hrms/home/';

    return $response;
}

/**
 * Logout employee
 */
function hrmsLogout()
{
    unset($_SESSION['HRMS_LOGIN']);
    unset($_SESSION['HRMS_USER_ID']);
    unset($_SESSION['HRMS_USER_NAME']);
    unset($_SESSION['HRMS_USER_EMAIL']);
    unset($_SESSION['HRMS_IS_MANAGER']);

    return array('err' => 0, 'msg' => 'Logged out successfully');
}

/**
 * Get employee dashboard data
 */
function getEmployeeDashboard()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;

    if (!$userID) {
        return array('err' => 1, 'msg' => 'Not authenticated');
    }

    $data = array();

    // Get employee details
    $DB->vals = array(1, $userID);
    $DB->types = "ii";
    $DB->sql = "SELECT u.*,
                       (SELECT COALESCE(displayName, userName) FROM `" . $DB->pre . "x_admin_user` WHERE userID = u.managerID) as managerName
                FROM `" . $DB->pre . "x_admin_user` u
                WHERE u.status=? AND u.userID=?";
    $data['employee'] = $DB->dbRow();

    // Get current month attendance summary
    $currentMonth = date('m');
    $currentYear = date('Y');
    $DB->vals = array(1, $userID, $currentYear, $currentMonth);
    $DB->types = "iiii";
    $DB->sql = "SELECT
                    COUNT(*) as totalDays,
                    SUM(CASE WHEN attendanceStatus = 'present' THEN 1 ELSE 0 END) as presentDays,
                    SUM(CASE WHEN attendanceStatus = 'absent' THEN 1 ELSE 0 END) as absentDays,
                    SUM(CASE WHEN attendanceStatus = 'leave' THEN 1 ELSE 0 END) as leaveDays,
                    SUM(CASE WHEN isLate = 1 THEN 1 ELSE 0 END) as lateDays
                FROM `" . $DB->pre . "attendance`
                WHERE status=? AND userID=? AND YEAR(attendanceDate)=? AND MONTH(attendanceDate)=?";
    $data['attendanceSummary'] = $DB->dbRow();

    // Get recent attendance (last 4 days)
    $DB->vals = array(1, $userID);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "attendance`
                WHERE status=? AND userID=?
                ORDER BY attendanceDate DESC LIMIT 4";
    $data['recentAttendance'] = $DB->dbRows();

    // Get pending salary slips
    $DB->vals = array($userID);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "salary_slip`
                WHERE userID=? AND slipStatus IN ('paid', 'slip_generated', 'emailed')
                ORDER BY salaryYear DESC, salaryMonth DESC LIMIT 3";
    $data['recentSalarySlips'] = $DB->dbRows();

    // Get actual leave balance (allowed - used)
    $leaveBalanceResult = getLeaveBalance();
    if ($leaveBalanceResult['err'] == 0) {
        $balanceData = $leaveBalanceResult['data'];
        $data['leaveBalance'] = array(
            'paidLeave' => $balanceData['earned'] ?? 0,
            'casualLeave' => $balanceData['casual'] ?? 0,
            'sickLeave' => $balanceData['sick'] ?? 0
        );
    } else {
        // Fallback to employee settings if balance calculation fails
        $data['leaveBalance'] = array(
            'paidLeave' => $data['employee']['paidLeaveDays'] ?? 12,
            'casualLeave' => $data['employee']['casualLeaveDays'] ?? 6,
            'sickLeave' => $data['employee']['sickLeaveDays'] ?? 6
        );
    }

    // If manager or HR Admin, get team data
    if ($_SESSION['HRMS_IS_MANAGER'] || isHRMasterAdmin()) {
        // HR Admin sees ALL employees, managers see only their direct reports
        if (isHRMasterAdmin()) {
            $DB->vals = array(1, $userID);
            $DB->types = "ii";
            $DB->sql = "SELECT userID, COALESCE(displayName, userName) as displayName, userEmail, designation, department
                        FROM `" . $DB->pre . "x_admin_user`
                        WHERE status=? AND userID != ?
                        ORDER BY COALESCE(displayName, userName)";
        } else {
            $DB->vals = array(1, $userID);
            $DB->types = "ii";
            $DB->sql = "SELECT userID, COALESCE(displayName, userName) as displayName, userEmail, designation, department
                        FROM `" . $DB->pre . "x_admin_user`
                        WHERE status=? AND managerID=?
                        ORDER BY COALESCE(displayName, userName)";
        }
        $data['teamMembers'] = $DB->dbRows();
        $data['teamCount'] = $DB->numRows;
    }

    // Get upcoming birthdays (next 30 days, including today)
    $today = date('Y-m-d');
    $currentMonth = date('m');
    $currentDay = date('d');

    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT userID, COALESCE(displayName, userName) as displayName, userName,
                       dateOfBirth, department, designation,
                       DATE_FORMAT(dateOfBirth, '%d') as day,
                       DATE_FORMAT(dateOfBirth, '%b') as month,
                       CASE
                           WHEN DATE_FORMAT(dateOfBirth, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d') THEN 1
                           ELSE 0
                       END as isToday,
                       CASE
                           WHEN DATE_FORMAT(dateOfBirth, '%m-%d') >= DATE_FORMAT(CURDATE(), '%m-%d')
                           THEN DATEDIFF(
                               CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(dateOfBirth, '%m-%d')),
                               CURDATE()
                           )
                           ELSE DATEDIFF(
                               CONCAT(YEAR(CURDATE()) + 1, '-', DATE_FORMAT(dateOfBirth, '%m-%d')),
                               CURDATE()
                           )
                       END as daysUntil
                FROM `" . $DB->pre . "x_admin_user`
                WHERE status = ?
                AND dateOfBirth IS NOT NULL
                AND dateOfBirth != '0000-00-00'
                HAVING daysUntil <= 30
                ORDER BY daysUntil ASC
                LIMIT 5";
    $birthdays = $DB->dbRows();

    $data['upcomingBirthdays'] = array();
    foreach ($birthdays as $bday) {
        $data['upcomingBirthdays'][] = array(
            'userID' => $bday['userID'],
            'displayName' => $bday['displayName'],
            'userName' => $bday['userName'],
            'department' => $bday['department'],
            'designation' => $bday['designation'],
            'day' => $bday['day'],
            'month' => $bday['month'],
            'isToday' => $bday['isToday'] == 1
        );
    }

    return array('err' => 0, 'data' => $data);
}

/**
 * Get employee attendance for a month
 */
function getEmployeeAttendance()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $month = intval($_POST['month'] ?? date('m'));
    $year = intval($_POST['year'] ?? date('Y'));

    if (!$userID) {
        return array('err' => 1, 'msg' => 'Not authenticated');
    }

    $DB->vals = array(1, $userID, $year, $month);
    $DB->types = "iiii";
    $DB->sql = "SELECT a.*,
                       (SELECT reason FROM `" . $DB->pre . "attendance_remarks` ar
                        WHERE ar.attendanceID = a.attendanceID AND ar.status = 1
                        ORDER BY ar.submittedAt DESC LIMIT 1) as lastRemark
                FROM `" . $DB->pre . "attendance` a
                WHERE a.status=? AND a.userID=? AND YEAR(a.attendanceDate)=? AND MONTH(a.attendanceDate)=?
                ORDER BY a.attendanceDate ASC";
    $attendance = $DB->dbRows();

    // Get holidays for this month
    $DB->vals = array(1, $year, $month);
    $DB->types = "iii";
    $DB->sql = "SELECT ahDate, ahReason FROM `" . $DB->pre . "attendance_holidays`
                WHERE status=? AND YEAR(ahDate)=? AND MONTH(ahDate)=?
                ORDER BY ahDate ASC";
    $holidaysRaw = $DB->dbRows();

    // Convert holidays to associative array keyed by date
    $holidays = array();
    foreach ($holidaysRaw as $h) {
        $holidays[$h['ahDate']] = $h['ahReason'];
    }

    // Get approved leaves for this month
    $DB->vals = array($userID, $year, $month, 'Approved');
    $DB->types = "iiis";
    $DB->sql = "SELECT ld.leaveDate, lt.leaveTypeName
                FROM `" . $DB->pre . "leave_details` ld
                INNER JOIN `" . $DB->pre . "leave` l ON ld.leaveID = l.leaveID
                LEFT JOIN `" . $DB->pre . "leave_type` lt ON l.leaveType = lt.leaveTypeID
                WHERE ld.userID = ? AND YEAR(ld.leaveDate) = ? AND MONTH(ld.leaveDate) = ?
                AND l.leaveStatus = ? AND l.status = 1 AND ld.status = 1
                ORDER BY ld.leaveDate ASC";
    $leavesRaw = $DB->dbRows();

    // Convert leaves to associative array keyed by date
    $leaves = array();
    foreach ($leavesRaw as $lv) {
        // Generate short code from leave type name (e.g., "Casual Leave" -> "CL")
        $typeName = $lv['leaveTypeName'] ?: 'Leave';
        $words = explode(' ', $typeName);
        $code = '';
        foreach ($words as $word) {
            if (!empty($word)) $code .= strtoupper(substr($word, 0, 1));
        }
        if (empty($code)) $code = 'L';

        $leaves[$lv['leaveDate']] = array(
            'type' => $typeName,
            'code' => $code
        );
    }

    return array('err' => 0, 'data' => $attendance, 'holidays' => $holidays, 'leaves' => $leaves);
}

/**
 * Submit attendance remark
 */
function submitAttendanceRemark()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $attendanceID = intval($_POST['attendanceID'] ?? 0);
    $remarkType = mysqli_real_escape_string($DB->con, $_POST['remarkType'] ?? 'other');
    $reason = trim(mysqli_real_escape_string($DB->con, $_POST['reason'] ?? ''));

    if (!$userID || !$attendanceID || empty($reason)) {
        return array('err' => 1, 'msg' => 'Missing required fields');
    }

    // Verify attendance belongs to this user
    $DB->vals = array(1, $attendanceID, $userID);
    $DB->types = "iii";
    $DB->sql = "SELECT attendanceID FROM `" . $DB->pre . "attendance` WHERE status=? AND attendanceID=? AND userID=?";
    $DB->dbRow();

    if ($DB->numRows == 0) {
        return array('err' => 1, 'msg' => 'Invalid attendance record');
    }

    // Insert remark
    $DB->table = $DB->pre . "attendance_remarks";
    $DB->data = array(
        "attendanceID" => $attendanceID,
        "userID" => $userID,
        "remarkType" => $remarkType,
        "reason" => $reason,
        "submittedBy" => $userID,
        "status" => 1
    );

    if ($DB->dbInsert()) {
        return array('err' => 0, 'msg' => 'Remark submitted successfully');
    }

    return array('err' => 1, 'msg' => 'Failed to submit remark');
}

/**
 * Get employee salary slips
 */
function getEmployeeSalarySlips()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;

    if (!$userID) {
        return array('err' => 1, 'msg' => 'Not authenticated');
    }

    $DB->vals = array($userID);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "salary_slip`
                WHERE userID=? AND slipStatus IN ('paid', 'slip_generated', 'emailed')
                ORDER BY salaryYear DESC, salaryMonth DESC";
    $slips = $DB->dbRows();

    return array('err' => 0, 'data' => $slips);
}

/**
 * Get employee documents
 */
function getEmployeeDocuments()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;

    if (!$userID) {
        return array('err' => 1, 'msg' => 'Not authenticated');
    }

    $DB->vals = array(1, $userID);
    $DB->types = "ii";
    $DB->sql = "SELECT documentID, documentName, documentType, fileName, fileSize, createdAt
                FROM `" . $DB->pre . "employee_document`
                WHERE status=? AND userID=?
                ORDER BY createdAt DESC";
    $documents = $DB->dbRows();

    $formattedDocs = array();
    foreach ($documents as $doc) {
        $formattedDocs[] = array(
            'documentID' => $doc['documentID'],
            'documentName' => $doc['documentName'],
            'documentType' => $doc['documentType'],
            'fileType' => strtolower(pathinfo($doc['fileName'], PATHINFO_EXTENSION)),
            'fileSize' => intval($doc['fileSize'] ?? 0),
            'uploadedAt' => $doc['createdAt'],
            'fileUrl' => UPLOADURL . '/employee-documents/' . $doc['fileName']
        );
    }

    return array('err' => 0, 'data' => $formattedDocs);
}

/**
 * Get team attendance (for managers) - Monthly view with summary
 */
function getTeamAttendance()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = isHRMasterAdmin();

    if (!$userID || (!$isManager && !$isHRAdmin)) {
        return array('success' => false, 'message' => 'Access denied');
    }

    $month = intval($_GET['month'] ?? date('m'));
    $year = intval($_GET['year'] ?? date('Y'));
    $today = date('Y-m-d');

    // Get team members - HR Admin sees all employees, managers see their direct reports
    if ($isHRAdmin) {
        // HR Admin sees all active employees except themselves
        $DB->vals = array(1, $userID);
        $DB->types = "ii";
        $DB->sql = "SELECT u.userID, u.userName, u.displayName, u.designation, u.department
                    FROM `" . $DB->pre . "x_admin_user` u
                    WHERE u.status=? AND u.userID != ?
                    ORDER BY COALESCE(u.displayName, u.userName)";
    } else {
        // Manager sees only their direct reports
        $DB->vals = array(1, $userID);
        $DB->types = "ii";
        $DB->sql = "SELECT u.userID, u.userName, u.displayName, u.designation, u.department
                    FROM `" . $DB->pre . "x_admin_user` u
                    WHERE u.status=? AND u.managerID=?
                    ORDER BY COALESCE(u.displayName, u.userName)";
    }
    $teamMembers = $DB->dbRows();

    $team = array();
    foreach ($teamMembers as $member) {
        // Get monthly summary for this member
        $DB->vals = array(1, $member['userID'], $year, $month);
        $DB->types = "iiii";
        $DB->sql = "SELECT
                        SUM(CASE WHEN attendanceStatus = 'present' THEN 1 ELSE 0 END) as presentDays,
                        SUM(CASE WHEN attendanceStatus = 'absent' THEN 1 ELSE 0 END) as absentDays,
                        SUM(CASE WHEN attendanceStatus = 'leave' THEN 1 ELSE 0 END) as leaveDays,
                        SUM(CASE WHEN isLate = 1 THEN 1 ELSE 0 END) as lateDays
                    FROM `" . $DB->pre . "attendance`
                    WHERE status=? AND userID=? AND YEAR(attendanceDate)=? AND MONTH(attendanceDate)=?";
        $summary = $DB->dbRow();

        // Get today's status
        $DB->vals = array(1, $member['userID'], $today);
        $DB->types = "iis";
        $DB->sql = "SELECT checkIn, checkOut, attendanceStatus, isLate
                    FROM `" . $DB->pre . "attendance`
                    WHERE status=? AND userID=? AND attendanceDate=?";
        $todayAttendance = $DB->dbRow();

        $todayStatus = 'absent';
        $checkIn = '-';
        $checkOut = '-';

        if ($todayAttendance) {
            $todayStatus = $todayAttendance['attendanceStatus'];
            if ($todayAttendance['isLate']) $todayStatus = 'late';
            $checkIn = $todayAttendance['checkIn'] ? date('h:i A', strtotime($todayAttendance['checkIn'])) : '-';
            $checkOut = $todayAttendance['checkOut'] ? date('h:i A', strtotime($todayAttendance['checkOut'])) : '-';
        }

        $team[] = array(
            'userID' => $member['userID'],
            'userName' => $member['displayName'] ?: $member['userName'],
            'designation' => $member['designation'],
            'department' => $member['department'],
            'presentDays' => intval($summary['presentDays'] ?? 0),
            'absentDays' => intval($summary['absentDays'] ?? 0),
            'leaveDays' => intval($summary['leaveDays'] ?? 0),
            'lateDays' => intval($summary['lateDays'] ?? 0),
            'todayStatus' => $todayStatus,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut
        );
    }

    return array('success' => true, 'team' => $team);
}

/**
 * Get team stats for today (for managers dashboard)
 */
function getTeamStats()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = isHRMasterAdmin();

    if (!$userID || (!$isManager && !$isHRAdmin)) {
        return array('success' => false, 'message' => 'Access denied');
    }

    $today = date('Y-m-d');

    // Count total team members - HR Admin sees all, managers see direct reports
    if ($isHRAdmin) {
        $DB->vals = array(1, $userID);
        $DB->types = "ii";
        $DB->sql = "SELECT COUNT(*) as total FROM `" . $DB->pre . "x_admin_user` WHERE status=? AND userID != ?";
        $total = $DB->dbRow();

        // Count today's attendance for all employees
        $DB->vals = array(1, $userID, $today);
        $DB->types = "iis";
        $DB->sql = "SELECT
                        SUM(CASE WHEN a.attendanceStatus = 'present' AND a.isLate = 0 THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN a.isLate = 1 THEN 1 ELSE 0 END) as late
                    FROM `" . $DB->pre . "attendance` a
                    INNER JOIN `" . $DB->pre . "x_admin_user` u ON a.userID = u.userID
                    WHERE a.status=? AND u.userID != ? AND a.attendanceDate=?";
        $todayStats = $DB->dbRow();
    } else {
        $DB->vals = array(1, $userID);
        $DB->types = "ii";
        $DB->sql = "SELECT COUNT(*) as total FROM `" . $DB->pre . "x_admin_user` WHERE status=? AND managerID=?";
        $total = $DB->dbRow();

        // Count today's attendance for direct reports
        $DB->vals = array(1, $userID, $today);
        $DB->types = "iis";
        $DB->sql = "SELECT
                        SUM(CASE WHEN a.attendanceStatus = 'present' AND a.isLate = 0 THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN a.isLate = 1 THEN 1 ELSE 0 END) as late
                    FROM `" . $DB->pre . "attendance` a
                    INNER JOIN `" . $DB->pre . "x_admin_user` u ON a.userID = u.userID
                    WHERE a.status=? AND u.managerID=? AND a.attendanceDate=?";
        $todayStats = $DB->dbRow();
    }

    $totalMembers = intval($total['total'] ?? 0);
    $present = intval($todayStats['present'] ?? 0);
    $late = intval($todayStats['late'] ?? 0);
    $absent = $totalMembers - $present - $late;

    return array(
        'success' => true,
        'stats' => array(
            'totalMembers' => $totalMembers,
            'presentToday' => $present,
            'lateToday' => $late,
            'absentToday' => max(0, $absent)
        )
    );
}

/**
 * Get leave balance for employee
 * Earned Leave is accrual-based:
 * - 90 continuous days worked = 3 EL
 * - 180 continuous days worked = 6 EL
 * - Full year (365 days) = 12 EL
 */
function getLeaveBalance()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;

    if (!$userID) {
        return array('err' => 1, 'msg' => 'Not authenticated');
    }

    // Get financial year dates
    $currentMonth = date('n');
    $currentYear = date('Y');
    if ($currentMonth >= 4) {
        $fyStart = $currentYear . '-04-01';
        $fyEnd = ($currentYear + 1) . '-03-31';
    } else {
        $fyStart = ($currentYear - 1) . '-04-01';
        $fyEnd = $currentYear . '-03-31';
    }

    // Get user's leave allocation
    $DB->vals = array($userID, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT totalLeaves, paidLeaveDays, casualLeaveDays, sickLeaveDays, dateOfJoining FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=?";
    $user = $DB->dbRow();

    $casualAllowed = intval($user['casualLeaveDays'] ?? 6);
    $sickAllowed = intval($user['sickLeaveDays'] ?? 6);
    $joiningDate = $user['dateOfJoining'] ?? null;

    // Calculate accrued earned leave based on continuous days worked
    $earnedAccrued = calculateAccruedEarnedLeave($userID, $fyStart, $fyEnd, $joiningDate);

    // Get leaves taken this FY (approved only, exclude cancelled)
    $DB->vals = array($userID, 1, $fyStart, $fyEnd);
    $DB->types = "isss";
    $DB->sql = "SELECT l.leaveType, lt.leaveTypeName,
                       SUM(CASE WHEN ld.lType IN (2,3) THEN 0.5 ELSE 1 END) as daysTaken
                FROM `" . $DB->pre . "leave` l
                INNER JOIN `" . $DB->pre . "leave_details` ld ON l.leaveID = ld.leaveID
                LEFT JOIN `" . $DB->pre . "leave_type` lt ON l.leaveType = lt.leaveTypeID
                WHERE l.userID=? AND l.status=? AND l.leaveStatus IN ('Approved', 'Parsley Approved')
                AND ld.leaveDate >= ? AND ld.leaveDate <= ?
                AND ld.lType != -1
                GROUP BY l.leaveType";
    $leavesTaken = $DB->dbRows();

    $casualTaken = 0;
    $sickTaken = 0;
    $earnedTaken = 0;
    $otherTaken = 0;

    foreach ($leavesTaken as $lt) {
        $typeName = strtolower($lt['leaveTypeName'] ?? '');
        $days = floatval($lt['daysTaken']);

        if (strpos($typeName, 'casual') !== false) {
            $casualTaken += $days;
        } elseif (strpos($typeName, 'sick') !== false || strpos($typeName, 'medical') !== false) {
            $sickTaken += $days;
        } elseif (strpos($typeName, 'earned') !== false || strpos($typeName, 'privilege') !== false || strpos($typeName, 'paid') !== false) {
            $earnedTaken += $days;
        } else {
            $otherTaken += $days;
        }
    }

    // Allow negative balances to show over-usage
    $casualBalance = $casualAllowed - $casualTaken;
    $sickBalance = $sickAllowed - $sickTaken;
    $earnedBalance = $earnedAccrued - $earnedTaken;
    $totalBalance = $casualBalance + $sickBalance + $earnedBalance;

    return array(
        'err' => 0,
        'data' => array(
            'casual' => $casualBalance,
            'sick' => $sickBalance,
            'earned' => $earnedBalance,
            'total' => $totalBalance,
            'casualUsed' => $casualTaken,
            'sickUsed' => $sickTaken,
            'earnedUsed' => $earnedTaken,
            'earnedAccrued' => $earnedAccrued,
            'casualAllowed' => $casualAllowed,
            'sickAllowed' => $sickAllowed,
            'earnedAllowed' => $earnedAccrued
        )
    );
}

/**
 * Get leave balance for a specific user (for checking before applying leave)
 * Returns balance info with warning if over limit
 */
function getLeaveBalanceForUser($userID, $leaveTypeID = null)
{
    global $DB;

    // Get financial year dates
    $currentMonth = date('n');
    $currentYear = date('Y');
    if ($currentMonth >= 4) {
        $fyStart = $currentYear . '-04-01';
        $fyEnd = ($currentYear + 1) . '-03-31';
    } else {
        $fyStart = ($currentYear - 1) . '-04-01';
        $fyEnd = $currentYear . '-03-31';
    }

    // Get user's leave allocation
    $DB->vals = array($userID, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT totalLeaves, paidLeaveDays, casualLeaveDays, sickLeaveDays, dateOfJoining, displayName, userName
                FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=?";
    $user = $DB->dbRow();

    if (!$user) {
        return null;
    }

    $employeeName = $user['displayName'] ?: $user['userName'];
    $casualAllowed = intval($user['casualLeaveDays'] ?? 18);
    $sickAllowed = intval($user['sickLeaveDays'] ?? 0);
    $joiningDate = $user['dateOfJoining'] ?? null;
    $earnedAccrued = calculateAccruedEarnedLeave($userID, $fyStart, $fyEnd, $joiningDate);

    // Get leaves taken this FY (approved only)
    $DB->vals = array($userID, 1, $fyStart, $fyEnd);
    $DB->types = "isss";
    $DB->sql = "SELECT l.leaveType, lt.leaveTypeName,
                       SUM(CASE WHEN ld.lType IN (2,3) THEN 0.5 ELSE 1 END) as daysTaken
                FROM `" . $DB->pre . "leave` l
                INNER JOIN `" . $DB->pre . "leave_details` ld ON l.leaveID = ld.leaveID
                LEFT JOIN `" . $DB->pre . "leave_type` lt ON l.leaveType = lt.leaveTypeID
                WHERE l.userID=? AND l.status=? AND l.leaveStatus IN ('Approved', 'Paid')
                AND ld.leaveDate >= ? AND ld.leaveDate <= ?
                AND ld.lType != -1
                GROUP BY l.leaveType";
    $leavesTaken = $DB->dbRows();

    $balanceByType = array();

    // Initialize with allowed values
    $balanceByType[1] = array('name' => 'Casual Leave', 'allowed' => $casualAllowed, 'used' => 0, 'balance' => $casualAllowed);
    $balanceByType[2] = array('name' => 'Sick Leave', 'allowed' => $sickAllowed, 'used' => 0, 'balance' => $sickAllowed);
    // Earned leave type ID may vary - check leave_type table
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT leaveTypeID FROM `" . $DB->pre . "leave_type` WHERE leaveTypeName LIKE '%Earned%' AND status=? LIMIT 1";
    $earnedType = $DB->dbRow();
    $earnedTypeID = $earnedType['leaveTypeID'] ?? 14;
    $balanceByType[$earnedTypeID] = array('name' => 'Earned Leave', 'allowed' => $earnedAccrued, 'used' => 0, 'balance' => $earnedAccrued);

    foreach ($leavesTaken as $lt) {
        $typeID = intval($lt['leaveType']);
        $days = floatval($lt['daysTaken']);
        $typeName = $lt['leaveTypeName'] ?? 'Leave';

        if (isset($balanceByType[$typeID])) {
            $balanceByType[$typeID]['used'] = $days;
            $balanceByType[$typeID]['balance'] = $balanceByType[$typeID]['allowed'] - $days;
        } else {
            // Other leave types - get allowed from leave_type table
            $DB->vals = array($typeID, 1);
            $DB->types = "ii";
            $DB->sql = "SELECT annualEntitlement FROM `" . $DB->pre . "leave_type` WHERE leaveTypeID=? AND status=?";
            $typeInfo = $DB->dbRow();
            $allowed = floatval($typeInfo['annualEntitlement'] ?? 0);
            $balanceByType[$typeID] = array('name' => $typeName, 'allowed' => $allowed, 'used' => $days, 'balance' => $allowed - $days);
        }
    }

    return array(
        'employeeName' => $employeeName,
        'balanceByType' => $balanceByType
    );
}

/**
 * Check if applying for leave will exceed balance
 * Returns warning message if over limit
 */
function checkLeaveBalanceWarning($userID, $leaveTypeID, $daysRequested)
{
    global $DB;

    $balanceInfo = getLeaveBalanceForUser($userID, $leaveTypeID);
    if (!$balanceInfo) {
        return null;
    }

    $balance = $balanceInfo['balanceByType'][$leaveTypeID] ?? null;
    if (!$balance) {
        // Get leave type name
        $DB->vals = array($leaveTypeID, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT leaveTypeName, annualEntitlement FROM `" . $DB->pre . "leave_type` WHERE leaveTypeID=? AND status=?";
        $typeInfo = $DB->dbRow();
        $balance = array(
            'name' => $typeInfo['leaveTypeName'] ?? 'Leave',
            'allowed' => floatval($typeInfo['annualEntitlement'] ?? 0),
            'used' => 0,
            'balance' => floatval($typeInfo['annualEntitlement'] ?? 0)
        );
    }

    $currentBalance = $balance['balance'];
    $newBalance = $currentBalance - $daysRequested;

    $warning = null;
    if ($newBalance < 0) {
        $overBy = abs($newBalance);
        if ($currentBalance < 0) {
            // Already in negative
            $warning = array(
                'type' => 'over_limit',
                'message' => "{$balanceInfo['employeeName']} has already exhausted {$balance['name']} quota. Current balance: {$currentBalance} days. After this leave: {$newBalance} days.",
                'currentBalance' => $currentBalance,
                'afterBalance' => $newBalance,
                'leaveTypeName' => $balance['name'],
                'allowed' => $balance['allowed'],
                'used' => $balance['used']
            );
        } else {
            // Will go negative with this request
            $warning = array(
                'type' => 'will_exceed',
                'message' => "This request will exceed {$balance['name']} quota by {$overBy} day(s). Current balance: {$currentBalance} days. After this leave: {$newBalance} days.",
                'currentBalance' => $currentBalance,
                'afterBalance' => $newBalance,
                'leaveTypeName' => $balance['name'],
                'allowed' => $balance['allowed'],
                'used' => $balance['used']
            );
        }
    }

    return $warning;
}

/**
 * Calculate accrued earned leave based on continuous days worked
 * - 90 days = 3 EL
 * - 180 days = 6 EL
 * - 365 days = 12 EL
 */
function calculateAccruedEarnedLeave($userID, $fyStart, $fyEnd, $joiningDate = null)
{
    global $DB;

    // Determine start date (FY start or joining date, whichever is later)
    $startDate = $fyStart;
    if ($joiningDate && $joiningDate > $fyStart) {
        $startDate = $joiningDate;
    }

    // End date is today or FY end, whichever is earlier
    $today = date('Y-m-d');
    $endDate = ($today < $fyEnd) ? $today : $fyEnd;

    // If start date is after end date, no accrual
    if ($startDate > $endDate) {
        return 0;
    }

    // Count present days (including half days as 0.5) from attendance
    $DB->vals = array($userID, $startDate, $endDate);
    $DB->types = "iss";
    $DB->sql = "SELECT
                    SUM(CASE
                        WHEN attendanceStatus = 'present' THEN 1
                        WHEN attendanceStatus = 'half_day' THEN 0.5
                        ELSE 0
                    END) as daysWorked
                FROM `" . $DB->pre . "attendance`
                WHERE userID = ?
                AND attendanceDate >= ?
                AND attendanceDate <= ?";
    $result = $DB->dbRow();

    $daysWorked = floatval($result['daysWorked'] ?? 0);

    // Calculate earned leave based on days worked
    // 90 days = 3 EL, 180 days = 6 EL, 365 days = 12 EL
    // Linear calculation: 1 EL per 30.42 days (365/12)
    if ($daysWorked >= 365) {
        return 12;
    } elseif ($daysWorked >= 180) {
        // Between 180-365 days: 6 EL + proportional for days above 180
        $extraDays = $daysWorked - 180;
        $extraEL = floor($extraDays / 30.83); // (365-180)/6 = ~30.83 days per additional EL
        return min(12, 6 + $extraEL);
    } elseif ($daysWorked >= 90) {
        // Between 90-180 days: 3 EL + proportional for days above 90
        $extraDays = $daysWorked - 90;
        $extraEL = floor($extraDays / 30); // 90 days = 3 more EL
        return min(6, 3 + $extraEL);
    } elseif ($daysWorked >= 30) {
        // Between 30-90 days: proportional
        return floor($daysWorked / 30);
    }

    return 0;
}

/**
 * Get leave types for dropdown
 */
function getLeaveTypes()
{
    global $DB;

    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT leaveTypeID, leaveTypeName, allotedLeave FROM `" . $DB->pre . "leave_type` WHERE status=? ORDER BY leaveTypeName";
    $types = $DB->dbRows();

    return array('err' => 0, 'data' => $types);
}

/**
 * Get leave history for employee
 */
function getLeaveHistory()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;

    if (!$userID) {
        return array('err' => 1, 'msg' => 'Not authenticated');
    }

    // Get last 12 months of leaves
    $fromDate = date('Y-m-d', strtotime('-12 months'));

    $DB->vals = array($userID, 1, $fromDate);
    $DB->types = "iis";
    $DB->sql = "SELECT l.*, lt.leaveTypeName
                FROM `" . $DB->pre . "leave` l
                LEFT JOIN `" . $DB->pre . "leave_type` lt ON l.leaveType = lt.leaveTypeID
                WHERE l.userID=? AND l.status=? AND l.fromDate >= ?
                ORDER BY l.dateAdded DESC";
    $leaves = $DB->dbRows();

    $formattedLeaves = array();
    foreach ($leaves as $leave) {
        // Get total days from leave_details
        $DB->vals = array($leave['leaveID'], 1);
        $DB->types = "ii";
        $DB->sql = "SELECT SUM(CASE WHEN lType IN (2,3) THEN 0.5 ELSE 1 END) as totalDays
                    FROM `" . $DB->pre . "leave_details` WHERE leaveID=? AND status=? AND lType != -1";
        $dayInfo = $DB->dbRow();
        $totalDays = floatval($dayInfo['totalDays'] ?? 1);

        $formattedLeaves[] = array(
            'leaveID' => $leave['leaveID'],
            'leaveType' => $leave['leaveType'],
            'leaveTypeName' => $leave['leaveTypeName'] ?? 'Leave',
            'fromDate' => $leave['fromDate'],
            'toDate' => $leave['toDate'],
            'reason' => $leave['reason'],
            'leaveStatus' => $leave['leaveStatus'],
            'totalDays' => $totalDays,
            'dateApplied' => $leave['dateAdded'],
            'approvedDate' => $leave['approvedDate'],
            'snote' => $leave['snote']
        );
    }

    return array('err' => 0, 'data' => $formattedLeaves);
}

/**
 * Get pending leave requests for HR Admin
 */
function getPendingLeavesForAdmin()
{
    global $DB;

    // Check if user is HR Admin
    $isHRAdmin = !empty($_SESSION['HRMS_IS_HR_ADMIN']);
    if (!$isHRAdmin) {
        return array('success' => false, 'message' => 'Not authorized');
    }

    $DB->vals = array('Pending', 1);
    $DB->types = "si";
    $DB->sql = "SELECT l.*, u.displayName as employeeName, u.userEmail as employeeEmail, lt.leaveTypeName
                FROM `" . $DB->pre . "leave` l
                LEFT JOIN `" . $DB->pre . "x_admin_user` u ON l.userID = u.userID
                LEFT JOIN `" . $DB->pre . "leave_type` lt ON l.leaveType = lt.leaveTypeID
                WHERE l.leaveStatus=? AND l.status=?
                ORDER BY l.dateAdded DESC";
    $leaves = $DB->dbRows();

    $formattedLeaves = array();
    foreach ($leaves as $leave) {
        $formattedLeaves[] = array(
            'leaveID' => $leave['leaveID'],
            'userID' => $leave['userID'],
            'employeeName' => $leave['employeeName'] ?? 'Employee',
            'employeeEmail' => $leave['employeeEmail'] ?? '',
            'leaveType' => $leave['leaveType'],
            'leaveTypeName' => $leave['leaveTypeName'] ?? 'Leave',
            'fromDate' => $leave['fromDate'],
            'toDate' => $leave['toDate'],
            'reason' => $leave['reason'],
            'dateAdded' => $leave['dateAdded']
        );
    }

    return array('success' => true, 'leaves' => $formattedLeaves);
}

/**
 * Update leave status from HRMS Portal (HR Admin only)
 */
function updateLeaveStatusFromPortal()
{
    global $DB;

    // Check if user is HR Admin
    $isHRAdmin = !empty($_SESSION['HRMS_IS_HR_ADMIN']);
    if (!$isHRAdmin) {
        return array('err' => 1, 'msg' => 'Not authorized');
    }

    $leaveID = intval($_POST['leaveID'] ?? 0);
    $newStatus = trim($_POST['leaveStatus'] ?? '');

    if (!$leaveID || !in_array($newStatus, ['Approved', 'Disapproved'])) {
        return array('err' => 1, 'msg' => 'Invalid parameters');
    }

    // Get leave details
    $DB->vals = array($leaveID, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "leave` WHERE leaveID=? AND status=?";
    $leave = $DB->dbRow();

    if (!$leave) {
        return array('err' => 1, 'msg' => 'Leave not found');
    }

    if ($leave['leaveStatus'] !== 'Pending') {
        return array('err' => 1, 'msg' => 'Leave has already been processed');
    }

    // Update leave status
    $DB->table = $DB->pre . "leave";
    $DB->data = array(
        'leaveStatus' => $newStatus,
        'snote' => 'Action via HRMS Portal on ' . date('Y-m-d H:i:s'),
        'approvedDate' => date('Y-m-d H:i:s')
    );

    if (!$DB->dbUpdate("leaveID=?", "i", array($leaveID))) {
        return array('err' => 1, 'msg' => 'Failed to update leave status');
    }

    // Update leave details status too
    $DB->table = $DB->pre . "leave_details";
    $DB->data = array('leaveStatus' => $newStatus);
    $DB->dbUpdate("leaveID=?", "i", array($leaveID));

    // Update user leave balance
    require_once(dirname(__FILE__) . '/../../../inc/common.inc.php');
    if (function_exists('updateUserLeaves')) {
        $year = date("Y", strtotime($leave["fromDate"]));
        $month = date("m", strtotime($leave["fromDate"]));
        updateUserLeaves($year, $month, $leave["userID"]);
    }

    // Send email notification to employee
    $DB->vals = array($leave['userID'], 1);
    $DB->types = "ii";
    $DB->sql = "SELECT displayName, userEmail FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=?";
    $employee = $DB->dbRow();

    $DB->vals = array($leave['leaveType'], 1);
    $DB->types = "ii";
    $DB->sql = "SELECT leaveTypeName FROM `" . $DB->pre . "leave_type` WHERE leaveTypeID=? AND status=?";
    $leaveTypeInfo = $DB->dbRow();

    $brevoPath = defined('COREPATH') ? COREPATH . '/brevo.inc.php' : ROOTPATH . '/core/brevo.inc.php';
    if (file_exists($brevoPath)) {
        include_once($brevoPath);
        if (function_exists('sendLeaveStatusNotification')) {
            $leaveData = array(
                'employeeName' => $employee['displayName'] ?? 'Employee',
                'employeeEmail' => $employee['userEmail'] ?? '',
                'leaveType' => $leaveTypeInfo['leaveTypeName'] ?? 'Leave',
                'fromDate' => $leave['fromDate'],
                'toDate' => $leave['toDate'],
                'status' => $newStatus,
                'remarks' => ''
            );
            sendLeaveStatusNotification($leaveData);
        }
    }

    return array('err' => 0, 'msg' => 'Leave status updated successfully');
}

/**
 * Apply for leave
 */
function applyLeave()
{
    global $DB;
    $currentUserID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = $_SESSION['HRMS_IS_HR_ADMIN'] ?? false;

    if (!$currentUserID) {
        return array('err' => 1, 'msg' => 'Not authenticated');
    }

    // Check if applying on behalf of someone else
    $applyForUserID = intval($_POST['applyForUserID'] ?? 0);
    $applyingOnBehalf = false;

    if ($applyForUserID && $applyForUserID != $currentUserID) {
        // Must be manager or HR Admin to apply on behalf
        if (!$isManager && !$isHRAdmin) {
            return array('err' => 1, 'msg' => 'Not authorized to apply leave for others');
        }

        // Verify the target user exists and is under this manager (if not HR Admin)
        if (!$isHRAdmin) {
            $DB->vals = array($applyForUserID, 1, $currentUserID);
            $DB->types = "iii";
            $DB->sql = "SELECT userID FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=? AND managerID=?";
            $DB->dbRow();
            if ($DB->numRows == 0) {
                return array('err' => 1, 'msg' => 'This employee is not in your team');
            }
        }

        $userID = $applyForUserID;
        $applyingOnBehalf = true;
    } else {
        $userID = $currentUserID;
    }

    $leaveType = intval($_POST['leaveType'] ?? 0);
    $fromDate = trim($_POST['fromDate'] ?? '');
    $toDate = trim($_POST['toDate'] ?? '');
    $dayType = intval($_POST['dayType'] ?? 1); // 1=Full, 2=First Half, 3=Second Half
    $reason = trim($_POST['reason'] ?? '');

    // Validation
    if (!$leaveType || !$fromDate || !$toDate || !$reason) {
        return array('err' => 1, 'msg' => 'All fields are required');
    }

    // Validate dates
    $from = strtotime($fromDate);
    $to = strtotime($toDate);
    $today = strtotime(date('Y-m-d'));

    if ($from === false || $to === false) {
        return array('err' => 1, 'msg' => 'Invalid date format');
    }

    // Allow past dates only when applying on behalf (manager applying for their team)
    if ($from < $today && !$applyingOnBehalf) {
        return array('err' => 1, 'msg' => 'Cannot apply leave for past dates');
    }

    if ($to < $from) {
        return array('err' => 1, 'msg' => 'To date cannot be before from date');
    }

    // Check for overlapping leaves for the target user
    $DB->vals = array($userID, 1, $fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate);
    $DB->types = "isssssss";
    $DB->sql = "SELECT leaveID FROM `" . $DB->pre . "leave`
                WHERE userID=? AND status=? AND leaveStatus NOT IN ('Cancel', 'Disapproved')
                AND ((fromDate BETWEEN ? AND ?) OR (toDate BETWEEN ? AND ?)
                     OR (fromDate <= ? AND toDate >= ?))";
    $DB->dbRow();
    if ($DB->numRows > 0) {
        $errorMsg = $applyingOnBehalf ? 'This employee already has a leave request for these dates' : 'You already have a leave request for these dates';
        return array('err' => 1, 'msg' => $errorMsg);
    }

    // Get user email
    $DB->vals = array($userID, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT userEmail, displayName FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=?";
    $userInfo = $DB->dbRow();
    $userEmail = $userInfo['userEmail'] ?? '';
    $employeeName = $userInfo['displayName'] ?? 'Employee';

    // Add note if applied on behalf
    $reasonWithNote = $reason;
    if ($applyingOnBehalf) {
        // Get applicant name
        $DB->vals = array($currentUserID, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT displayName FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=?";
        $applicantInfo = $DB->dbRow();
        $applicantName = $applicantInfo['displayName'] ?? 'Manager';
        $reasonWithNote = $reason . "\n\n[Applied by {$applicantName} on behalf of {$employeeName}]";
    }

    // Calculate total days being requested
    $totalDaysRequested = 0;
    $tempDate = $from;
    while ($tempDate <= $to) {
        $dateStr = date('Y-m-d', $tempDate);
        // Check if it's a holiday (holidays don't count)
        $DB->vals = array($dateStr, 1);
        $DB->types = "si";
        $DB->sql = "SELECT ahID FROM `" . $DB->pre . "attendance_holidays` WHERE ahDate=? AND status=?";
        $DB->dbRow();
        $isHoliday = $DB->numRows > 0;

        if (!$isHoliday) {
            // Check if it's first or last day for half-day calculation
            if ($dayType == 2 && $tempDate == $from) {
                $totalDaysRequested += 0.5;
            } elseif ($dayType == 3 && $tempDate == $to) {
                $totalDaysRequested += 0.5;
            } else {
                $totalDaysRequested += 1;
            }
        }
        $tempDate = strtotime('+1 day', $tempDate);
    }

    // Check leave balance warning
    $balanceWarning = checkLeaveBalanceWarning($userID, $leaveType, $totalDaysRequested);

    // Insert leave record
    $DB->table = $DB->pre . "leave";
    $DB->data = array(
        "userID" => $userID,
        "leaveType" => $leaveType,
        "fromDate" => $fromDate,
        "toDate" => $toDate,
        "reason" => $reasonWithNote,
        "emailID" => $userEmail,
        "leaveStatus" => "Pending",
        "dateAdded" => date('Y-m-d H:i:s'),
        "applyLeaveIPAddress" => $_SERVER['REMOTE_ADDR'] ?? '',
        "status" => 1
    );

    if (!$DB->dbInsert()) {
        return array('err' => 1, 'msg' => 'Failed to submit leave request');
    }

    $leaveID = $DB->insertID;

    // Send email notification to approvers
    $DB->vals = array($userID, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT displayName, userEmail FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=?";
    $empInfo = $DB->dbRow();

    // Get leave type name
    $DB->vals = array($leaveType, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT leaveTypeName FROM `" . $DB->pre . "leave_type` WHERE leaveTypeID=? AND status=?";
    $leaveTypeInfo = $DB->dbRow();

    $leaveData = array(
        'leaveID' => $leaveID,
        'employeeName' => $empInfo['displayName'] ?? 'Employee',
        'employeeEmail' => $empInfo['userEmail'] ?? $userEmail,
        'leaveType' => $leaveTypeInfo['leaveTypeName'] ?? 'Leave',
        'fromDate' => $fromDate,
        'toDate' => $toDate,
        'reason' => $reason,
        'dayType' => $dayType == 1 ? 'Full Day' : ($dayType == 2 ? 'First Half' : 'Second Half'),
        'appliedOn' => date('Y-m-d H:i:s'),
        'totalDays' => $totalDaysRequested,
        'balanceWarning' => $balanceWarning
    );

    // Include Brevo and send notification
    $brevoPath = defined('COREPATH') ? COREPATH . '/brevo.inc.php' : ROOTPATH . '/core/brevo.inc.php';
    if (file_exists($brevoPath)) {
        include_once($brevoPath);
        if (function_exists('sendLeaveApplicationNotification')) {
            $emailResult = sendLeaveApplicationNotification($leaveData);
            error_log("Leave Application: Email notification for leaveID=$leaveID, employee={$leaveData['employeeName']}, result=" . ($emailResult ? 'success' : 'failed'));
        } else {
            error_log("Leave Application: sendLeaveApplicationNotification function not found");
        }
    } else {
        error_log("Leave Application: brevo.inc.php file not found at " . $brevoPath);
    }

    // Insert leave details for each day
    $currentDate = $from;
    while ($currentDate <= $to) {
        $dateStr = date('Y-m-d', $currentDate);

        // Check if it's a holiday
        $DB->vals = array($dateStr, 1);
        $DB->types = "si";
        $DB->sql = "SELECT ahID FROM `" . $DB->pre . "attendance_holidays` WHERE ahDate=? AND status=?";
        $DB->dbRow();
        $isHoliday = $DB->numRows > 0;

        $lType = $isHoliday ? -1 : $dayType;

        // For multi-day leave, only apply half-day type on first/last day
        if ($dayType == 2 && $currentDate != $from) {
            $lType = 1; // Full day for other days
        }
        if ($dayType == 3 && $currentDate != $to) {
            $lType = 1; // Full day for other days
        }

        $DB->table = $DB->pre . "leave_details";
        $DB->data = array(
            "leaveID" => $leaveID,
            "userID" => $userID,
            "leaveDate" => $dateStr,
            "lType" => $lType,
            "leaveStatus" => "Pending",
            "status" => 1
        );
        $DB->dbInsert();

        $currentDate = strtotime('+1 day', $currentDate);
    }

    $successMsg = $applyingOnBehalf
        ? "Leave request submitted successfully for {$employeeName}"
        : 'Leave request submitted successfully';

    $response = array('err' => 0, 'msg' => $successMsg, 'leaveID' => $leaveID);

    // Include balance warning in response if applicable
    if ($balanceWarning) {
        $response['warning'] = $balanceWarning;
    }

    return $response;
}

/**
 * API to check leave balance before applying
 */
function checkLeaveBalanceAPI()
{
    global $DB;
    $currentUserID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = $_SESSION['HRMS_IS_HR_ADMIN'] ?? false;

    if (!$currentUserID) {
        return array('err' => 1, 'msg' => 'Not authenticated');
    }

    $applyForUserID = intval($_POST['applyForUserID'] ?? 0);
    $leaveType = intval($_POST['leaveType'] ?? 0);
    $fromDate = trim($_POST['fromDate'] ?? '');
    $toDate = trim($_POST['toDate'] ?? '');
    $dayType = intval($_POST['dayType'] ?? 1);

    // Determine target user
    $userID = $currentUserID;
    if ($applyForUserID && $applyForUserID != $currentUserID && ($isManager || $isHRAdmin)) {
        $userID = $applyForUserID;
    }

    if (!$leaveType || !$fromDate || !$toDate) {
        return array('err' => 1, 'msg' => 'Missing required fields');
    }

    // Calculate total days
    $from = strtotime($fromDate);
    $to = strtotime($toDate);
    if ($from === false || $to === false || $to < $from) {
        return array('err' => 1, 'msg' => 'Invalid dates');
    }

    $totalDaysRequested = 0;
    $tempDate = $from;
    while ($tempDate <= $to) {
        $dateStr = date('Y-m-d', $tempDate);
        $DB->vals = array($dateStr, 1);
        $DB->types = "si";
        $DB->sql = "SELECT ahID FROM `" . $DB->pre . "attendance_holidays` WHERE ahDate=? AND status=?";
        $DB->dbRow();
        $isHoliday = $DB->numRows > 0;

        if (!$isHoliday) {
            if ($dayType == 2 && $tempDate == $from) {
                $totalDaysRequested += 0.5;
            } elseif ($dayType == 3 && $tempDate == $to) {
                $totalDaysRequested += 0.5;
            } else {
                $totalDaysRequested += 1;
            }
        }
        $tempDate = strtotime('+1 day', $tempDate);
    }

    $warning = checkLeaveBalanceWarning($userID, $leaveType, $totalDaysRequested);

    return array(
        'err' => 0,
        'totalDays' => $totalDaysRequested,
        'warning' => $warning
    );
}

/**
 * Cancel pending leave request
 */
function cancelLeave()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $leaveID = intval($_POST['leaveID'] ?? 0);

    if (!$userID || !$leaveID) {
        return array('err' => 1, 'msg' => 'Invalid request');
    }

    // Verify this leave belongs to the user and is pending
    $DB->vals = array($leaveID, $userID, 1);
    $DB->types = "iii";
    $DB->sql = "SELECT leaveID, leaveStatus FROM `" . $DB->pre . "leave` WHERE leaveID=? AND userID=? AND status=?";
    $leave = $DB->dbRow();

    if ($DB->numRows == 0) {
        return array('err' => 1, 'msg' => 'Leave request not found');
    }

    if ($leave['leaveStatus'] !== 'Pending') {
        return array('err' => 1, 'msg' => 'Only pending leave requests can be cancelled');
    }

    // Update leave status to Cancel
    $DB->table = $DB->pre . "leave";
    $DB->data = array(
        "leaveStatus" => "Cancel",
        "cancelReason" => "Cancelled by employee"
    );

    if ($DB->dbUpdate("leaveID=?", "i", array($leaveID))) {
        // Also update leave_details
        $DB->table = $DB->pre . "leave_details";
        $DB->data = array("leaveStatus" => "Disapproved");
        $DB->dbUpdate("leaveID=?", "i", array($leaveID));

        return array('err' => 0, 'msg' => 'Leave request cancelled');
    }

    return array('err' => 1, 'msg' => 'Failed to cancel leave request');
}

/**
 * Get team leave requests (for managers)
 * Uses existing mx_leave table
 */
function getTeamLeaveRequests()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = isHRMasterAdmin();

    if (!$userID || (!$isManager && !$isHRAdmin)) {
        return array('success' => false, 'message' => 'Access denied');
    }

    $month = intval($_GET['month'] ?? $_POST['month'] ?? date('m'));
    $year = intval($_GET['year'] ?? $_POST['year'] ?? date('Y'));

    // Get leave requests for team members from mx_leave table
    if ($isHRAdmin) {
        // HR Admin sees all leave requests except their own
        $DB->vals = array(1, $userID, $year, $month);
        $DB->types = "iiii";
        $DB->sql = "SELECT l.*, u.userName, u.displayName, lt.leaveTypeName
                    FROM `" . $DB->pre . "leave` l
                    INNER JOIN `" . $DB->pre . "x_admin_user` u ON l.userID = u.userID
                    LEFT JOIN `" . $DB->pre . "leave_type` lt ON l.leaveType = lt.leaveTypeID
                    WHERE l.status=? AND l.userID != ?
                    AND (YEAR(l.fromDate)=? AND MONTH(l.fromDate)=?)
                    ORDER BY l.dateAdded DESC";
    } else {
        $DB->vals = array(1, $userID, $year, $month);
        $DB->types = "iiii";
        $DB->sql = "SELECT l.*, u.userName, u.displayName, lt.leaveTypeName
                    FROM `" . $DB->pre . "leave` l
                    INNER JOIN `" . $DB->pre . "x_admin_user` u ON l.userID = u.userID
                    LEFT JOIN `" . $DB->pre . "leave_type` lt ON l.leaveType = lt.leaveTypeID
                    WHERE l.status=? AND u.managerID=?
                    AND (YEAR(l.fromDate)=? AND MONTH(l.fromDate)=?)
                    ORDER BY l.dateAdded DESC";
    }
    $leaves = $DB->dbRows();

    $formattedLeaves = array();
    foreach ($leaves as $leave) {
        // Calculate total days from leave_details
        $DB->vals = array($leave['leaveID'], 1);
        $DB->types = "ii";
        $DB->sql = "SELECT COUNT(*) as dayCount, SUM(CASE WHEN lType IN (2,3) THEN 0.5 ELSE 1 END) as totalDays
                    FROM `" . $DB->pre . "leave_details` WHERE leaveID=? AND status=?";
        $dayInfo = $DB->dbRow();
        $totalDays = $dayInfo['totalDays'] ?? 1;

        $formattedLeaves[] = array(
            'leaveID' => $leave['leaveID'],
            'userName' => $leave['displayName'] ?: $leave['userName'],
            'leaveType' => $leave['leaveTypeName'] ?? 'Leave',
            'fromDate' => date('d M', strtotime($leave['fromDate'])),
            'toDate' => date('d M', strtotime($leave['toDate'])),
            'totalDays' => $totalDays,
            'reason' => $leave['reason'],
            'status' => $leave['leaveStatus'] ?? 'Pending'
        );
    }

    return array('success' => true, 'leaves' => $formattedLeaves);
}

/**
 * Get team remarks (for managers)
 */
function getTeamRemarks()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = isHRMasterAdmin();

    if (!$userID || (!$isManager && !$isHRAdmin)) {
        return array('success' => false, 'message' => 'Access denied');
    }

    $month = intval($_GET['month'] ?? date('m'));
    $year = intval($_GET['year'] ?? date('Y'));

    // Get remarks for team members
    if ($isHRAdmin) {
        // HR Admin sees all remarks except their own
        $DB->vals = array($userID, 1, $year, $month);
        $DB->types = "iiii";
        $DB->sql = "SELECT r.*, u.userName, u.displayName, a.attendanceDate, a.attendanceStatus, a.isLate
                    FROM `" . $DB->pre . "attendance_remarks` r
                    INNER JOIN `" . $DB->pre . "attendance` a ON r.attendanceID = a.attendanceID
                    INNER JOIN `" . $DB->pre . "x_admin_user` u ON r.userID = u.userID
                    WHERE r.userID != ? AND r.status=?
                    AND YEAR(a.attendanceDate)=? AND MONTH(a.attendanceDate)=?
                    ORDER BY r.submittedAt DESC";
    } else {
        $DB->vals = array($userID, 1, $year, $month);
        $DB->types = "iiii";
        $DB->sql = "SELECT r.*, u.userName, u.displayName, a.attendanceDate, a.attendanceStatus, a.isLate
                    FROM `" . $DB->pre . "attendance_remarks` r
                    INNER JOIN `" . $DB->pre . "attendance` a ON r.attendanceID = a.attendanceID
                    INNER JOIN `" . $DB->pre . "x_admin_user` u ON r.userID = u.userID
                    WHERE u.managerID=? AND r.status=?
                    AND YEAR(a.attendanceDate)=? AND MONTH(a.attendanceDate)=?
                    ORDER BY r.submittedAt DESC";
    }
    $remarks = $DB->dbRows();

    $formattedRemarks = array();
    foreach ($remarks as $remark) {
        $issueType = 'other';
        $issueDetails = '';
        if ($remark['isLate']) {
            $issueType = 'late';
            $issueDetails = 'Late arrival';
        } elseif ($remark['attendanceStatus'] === 'absent') {
            $issueType = 'absent';
            $issueDetails = 'Absent';
        }

        $formattedRemarks[] = array(
            'remarkID' => $remark['remarkID'],
            'userName' => $remark['displayName'] ?: $remark['userName'],
            'attendanceDate' => $remark['attendanceDate'],
            'issueType' => $issueType,
            'issueDetails' => $issueDetails,
            'remark' => $remark['reason']
        );
    }

    return array('success' => true, 'remarks' => $formattedRemarks);
}

/**
 * Mark attendance for a team member (for managers)
 */
function markTeamMemberAttendance()
{
    global $DB;
    $managerID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = $_SESSION['HRMS_IS_HR_ADMIN'] ?? false;

    if (!$managerID || (!$isManager && !$isHRAdmin)) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    $employeeID = intval($_POST['employeeID'] ?? 0);
    $attendanceDate = $_POST['attendanceDate'] ?? date('Y-m-d');
    $attendanceStatus = $_POST['attendanceStatus'] ?? 'present'; // present, absent, half_day, leave
    $checkIn = $_POST['checkIn'] ?? null;
    $checkOut = $_POST['checkOut'] ?? null;
    $remarks = trim($_POST['remarks'] ?? '');

    if (!$employeeID) {
        return array('err' => 1, 'msg' => 'Employee ID is required');
    }

    // Verify this employee is under this manager (or HR admin can access all)
    if (!$isHRAdmin) {
        $DB->vals = array($employeeID, $managerID, 1);
        $DB->types = "iii";
        $DB->sql = "SELECT userID FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND managerID=? AND status=?";
        $DB->dbRow();
        if ($DB->numRows == 0) {
            return array('err' => 1, 'msg' => 'You can only mark attendance for your team members');
        }
    }

    // Check if attendance already exists for this date
    $DB->vals = array($employeeID, $attendanceDate, 1);
    $DB->types = "isi";
    $DB->sql = "SELECT attendanceID FROM `" . $DB->pre . "attendance` WHERE userID=? AND attendanceDate=? AND status=?";
    $existing = $DB->dbRow();

    $isLate = 0;
    $isEarlyOut = 0;

    // Determine late/early status based on shift
    if ($attendanceStatus === 'present' || $attendanceStatus === 'half_day') {
        // Get employee shift
        $DB->vals = array($employeeID, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT shiftID FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=?";
        $empData = $DB->dbRow();
        $shiftID = $empData['shiftID'] ?? 1;

        $DB->vals = array($shiftID, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT * FROM `" . $DB->pre . "shift_master` WHERE shiftID=? AND status=?";
        $shift = $DB->dbRow();

        if ($shift && $checkIn) {
            $shiftStart = strtotime($shift['shiftStart']);
            $checkInTime = strtotime($checkIn);
            $gracePeriod = intval($shift['gracePeriod'] ?? 15) * 60;
            if ($checkInTime > ($shiftStart + $gracePeriod)) {
                $isLate = 1;
            }
        }

        if ($shift && $checkOut) {
            $shiftEnd = strtotime($shift['shiftEnd']);
            $checkOutTime = strtotime($checkOut);
            if ($checkOutTime < $shiftEnd) {
                $isEarlyOut = 1;
            }
        }
    }

    $attendanceData = array(
        "userID" => $employeeID,
        "attendanceDate" => $attendanceDate,
        "attendanceStatus" => $attendanceStatus,
        "checkIn" => $checkIn ?: null,
        "checkOut" => $checkOut ?: null,
        "isLate" => $isLate,
        "isEarlyOut" => $isEarlyOut,
        "source" => "manager_entry",
        "modifiedBy" => $managerID,
        "status" => 1
    );

    $DB->table = $DB->pre . "attendance";
    $DB->data = $attendanceData;

    if ($existing && $existing['attendanceID']) {
        // Update existing
        if ($DB->dbUpdate("attendanceID=?", "i", array($existing['attendanceID']))) {
            // Add remark if provided
            if (!empty($remarks)) {
                addManagerRemark($existing['attendanceID'], $employeeID, $managerID, $remarks, $attendanceStatus);
            }
            return array('err' => 0, 'msg' => 'Attendance updated successfully');
        }
    } else {
        // Insert new
        if ($attendanceID = $DB->dbInsert()) {
            // Add remark if provided
            if (!empty($remarks)) {
                addManagerRemark($attendanceID, $employeeID, $managerID, $remarks, $attendanceStatus);
            }
            return array('err' => 0, 'msg' => 'Attendance marked successfully');
        }
    }

    return array('err' => 1, 'msg' => 'Failed to mark attendance');
}

/**
 * Add manager remark for attendance
 */
function addManagerRemark($attendanceID, $employeeID, $managerID, $remarks, $status)
{
    global $DB;

    $remarkType = 'manager_note';
    if ($status === 'absent') {
        $remarkType = 'absent_marked';
    } elseif ($status === 'half_day') {
        $remarkType = 'half_day_marked';
    }

    $DB->table = $DB->pre . "attendance_remarks";
    $DB->data = array(
        "attendanceID" => $attendanceID,
        "userID" => $employeeID,
        "remarkType" => $remarkType,
        "reason" => $remarks,
        "submittedBy" => $managerID,
        "managerReview" => "noted",
        "status" => 1
    );
    $DB->dbInsert();
}

/**
 * Add remark for team member by manager
 */
function addTeamMemberRemark()
{
    global $DB;
    $managerID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = $_SESSION['HRMS_IS_HR_ADMIN'] ?? false;

    if (!$managerID || (!$isManager && !$isHRAdmin)) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    $employeeID = intval($_POST['employeeID'] ?? 0);
    $attendanceDate = $_POST['attendanceDate'] ?? date('Y-m-d');
    $remarkType = $_POST['remarkType'] ?? 'manager_note';
    $remarks = trim($_POST['remarks'] ?? '');

    if (!$employeeID || empty($remarks)) {
        return array('err' => 1, 'msg' => 'Employee and remarks are required');
    }

    // Verify this employee is under this manager (or HR admin can access all)
    if (!$isHRAdmin) {
        $DB->vals = array($employeeID, $managerID, 1);
        $DB->types = "iii";
        $DB->sql = "SELECT userID FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND managerID=? AND status=?";
        $DB->dbRow();
        if ($DB->numRows == 0) {
            return array('err' => 1, 'msg' => 'You can only add remarks for your team members');
        }
    }

    // Get or create attendance record for this date
    $DB->vals = array($employeeID, $attendanceDate, 1);
    $DB->types = "isi";
    $DB->sql = "SELECT attendanceID FROM `" . $DB->pre . "attendance` WHERE userID=? AND attendanceDate=? AND status=?";
    $attendance = $DB->dbRow();

    $attendanceID = null;
    if ($attendance && $attendance['attendanceID']) {
        $attendanceID = $attendance['attendanceID'];
    } else {
        // Create attendance record first
        $DB->table = $DB->pre . "attendance";
        $DB->data = array(
            "userID" => $employeeID,
            "attendanceDate" => $attendanceDate,
            "attendanceStatus" => "present",
            "source" => "manager_entry",
            "modifiedBy" => $managerID,
            "status" => 1
        );
        $attendanceID = $DB->dbInsert();
    }

    if (!$attendanceID) {
        return array('err' => 1, 'msg' => 'Failed to create attendance record');
    }

    // Add remark
    $DB->table = $DB->pre . "attendance_remarks";
    $DB->data = array(
        "attendanceID" => $attendanceID,
        "userID" => $employeeID,
        "remarkType" => $remarkType,
        "reason" => $remarks,
        "submittedBy" => $managerID,
        "managerReview" => "noted",
        "status" => 1
    );

    if ($DB->dbInsert()) {
        return array('err' => 0, 'msg' => 'Remark added successfully');
    }

    return array('err' => 1, 'msg' => 'Failed to add remark');
}

/**
 * Get team member attendance history for a specific date
 */
function getTeamMemberAttendanceForDate()
{
    global $DB;
    $managerID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = $_SESSION['HRMS_IS_HR_ADMIN'] ?? false;

    if (!$managerID || (!$isManager && !$isHRAdmin)) {
        return array('success' => false, 'message' => 'Access denied');
    }

    $employeeID = intval($_GET['employeeID'] ?? 0);
    $attendanceDate = $_GET['date'] ?? date('Y-m-d');

    if (!$employeeID) {
        return array('success' => false, 'message' => 'Employee ID is required');
    }

    // Verify this employee is under this manager (or HR admin can access all)
    if (!$isHRAdmin) {
        $DB->vals = array($employeeID, $managerID, 1);
        $DB->types = "iii";
        $DB->sql = "SELECT userID, userName, displayName, designation FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND managerID=? AND status=?";
        $employee = $DB->dbRow();
        if ($DB->numRows == 0) {
            return array('success' => false, 'message' => 'Access denied');
        }
    } else {
        $DB->vals = array($employeeID, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT userID, userName, displayName, designation FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=?";
        $employee = $DB->dbRow();
    }

    // Get attendance for this date
    $DB->vals = array($employeeID, $attendanceDate, 1);
    $DB->types = "isi";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "attendance` WHERE userID=? AND attendanceDate=? AND status=?";
    $attendance = $DB->dbRow();

    // Get remarks for this date if attendance exists
    $remarks = array();
    if ($attendance && $attendance['attendanceID']) {
        $DB->vals = array($attendance['attendanceID'], 1);
        $DB->types = "ii";
        $DB->sql = "SELECT r.*, u.userName as submittedByName
                    FROM `" . $DB->pre . "attendance_remarks` r
                    LEFT JOIN `" . $DB->pre . "x_admin_user` u ON r.submittedBy = u.userID
                    WHERE r.attendanceID=? AND r.status=?
                    ORDER BY r.submittedAt DESC";
        $remarks = $DB->dbRows();
    }

    return array(
        'success' => true,
        'employee' => array(
            'userID' => $employee['userID'],
            'userName' => $employee['displayName'] ?: $employee['userName'],
            'designation' => $employee['designation'] ?? 'Employee'
        ),
        'attendance' => $attendance ? array(
            'attendanceID' => $attendance['attendanceID'],
            'attendanceStatus' => $attendance['attendanceStatus'],
            'checkIn' => $attendance['checkIn'],
            'checkOut' => $attendance['checkOut'],
            'isLate' => $attendance['isLate'] ?? 0,
            'isEarlyOut' => $attendance['isEarlyOut'] ?? 0,
            'source' => $attendance['source'] ?? 'unknown'
        ) : null,
        'remarks' => $remarks
    );
}

/**
 * Approve leave request (for managers)
 * Uses existing mx_leave table
 */
function approveLeave()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $leaveID = intval($_POST['leaveID'] ?? 0);

    if (!$userID || !$isManager || !$leaveID) {
        return array('success' => false, 'message' => 'Invalid request', 'err' => 1);
    }

    // Verify this leave belongs to a team member
    $DB->vals = array($leaveID, 1, $userID);
    $DB->types = "iii";
    $DB->sql = "SELECT l.leaveID FROM `" . $DB->pre . "leave` l
                INNER JOIN `" . $DB->pre . "x_admin_user` u ON l.userID = u.userID
                WHERE l.leaveID=? AND l.status=? AND u.managerID=?";
    $DB->dbRow();

    if ($DB->numRows == 0) {
        return array('success' => false, 'message' => 'Leave request not found', 'err' => 1);
    }

    $DB->table = $DB->pre . "leave";
    $DB->data = array(
        "leaveStatus" => "Approved",
        "approvedBy" => $userID,
        "approvedDate" => date('Y-m-d H:i:s')
    );

    if ($DB->dbUpdate("leaveID=?", "i", array($leaveID))) {
        // Also update leave_details status
        $DB->table = $DB->pre . "leave_details";
        $DB->data = array("leaveStatus" => "Approved");
        $DB->dbUpdate("leaveID=?", "i", array($leaveID));

        return array('success' => true, 'message' => 'Leave approved', 'err' => 0);
    }

    return array('success' => false, 'message' => 'Failed to approve leave', 'err' => 1);
}

/**
 * Reject leave request (for managers)
 * Uses existing mx_leave table
 */
function rejectLeave()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $leaveID = intval($_POST['leaveID'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if (!$userID || !$isManager || !$leaveID) {
        return array('success' => false, 'message' => 'Invalid request', 'err' => 1);
    }

    // Verify this leave belongs to a team member
    $DB->vals = array($leaveID, 1, $userID);
    $DB->types = "iii";
    $DB->sql = "SELECT l.leaveID FROM `" . $DB->pre . "leave` l
                INNER JOIN `" . $DB->pre . "x_admin_user` u ON l.userID = u.userID
                WHERE l.leaveID=? AND l.status=? AND u.managerID=?";
    $DB->dbRow();

    if ($DB->numRows == 0) {
        return array('success' => false, 'message' => 'Leave request not found', 'err' => 1);
    }

    $DB->table = $DB->pre . "leave";
    $DB->data = array(
        "leaveStatus" => "Disapproved",
        "snote" => $reason,
        "approvedBy" => $userID,
        "approvedDate" => date('Y-m-d H:i:s')
    );

    if ($DB->dbUpdate("leaveID=?", "i", array($leaveID))) {
        // Also update leave_details status
        $DB->table = $DB->pre . "leave_details";
        $DB->data = array("leaveStatus" => "Disapproved");
        $DB->dbUpdate("leaveID=?", "i", array($leaveID));

        return array('success' => true, 'message' => 'Leave rejected', 'err' => 0);
    }

    return array('success' => false, 'message' => 'Failed to reject leave', 'err' => 1);
}

/**
 * Get employee profile
 */
function getEmployeeProfile()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;

    if (!$userID) {
        return array('success' => false, 'message' => 'Not authenticated');
    }

    $DB->vals = array(1, $userID);
    $DB->types = "ii";
    $DB->sql = "SELECT u.*,
                       (SELECT COALESCE(displayName, userName) FROM `" . $DB->pre . "x_admin_user` WHERE userID = u.managerID) as managerName
                FROM `" . $DB->pre . "x_admin_user` u
                WHERE u.status=? AND u.userID=?";
    $employee = $DB->dbRow();

    if (!$employee) {
        return array('success' => false, 'message' => 'Employee not found');
    }

    $profile = array(
        'userID' => $employee['userID'],
        'userName' => $employee['displayName'] ?: $employee['userName'],
        'email' => $employee['userEmail'],
        'phone' => $employee['userMobile'] ?? '',
        'dateOfBirth' => $employee['dateOfBirth'] ?? '',
        'gender' => $employee['gender'] ?? '',
        'address' => $employee['currentAddress'] ?? '',
        'employeeCode' => $employee['employeeCode'] ?? $employee['userID'],
        'designation' => $employee['designation'] ?? '',
        'department' => $employee['department'] ?? '',
        'joiningDate' => $employee['dateOfJoining'] ?? '',
        'managerName' => $employee['managerName'] ?? '',
        'isManager' => ($employee['isLeaveManager'] == 1),
        'bankName' => $employee['bankName'] ?? '',
        'bankBranch' => $employee['bankBranch'] ?? '',
        'accountNumber' => $employee['bankAccountNo'] ?? '',
        'ifscCode' => $employee['bankIFSC'] ?? '',
        'panNumber' => $employee['panNo'] ?? '',
        'emergencyContactName' => $employee['emergencyContactName'] ?? '',
        'emergencyContactRelation' => $employee['emergencyContactRelation'] ?? '',
        'emergencyContactPhone' => $employee['emergencyContact'] ?? ''
    );

    return array('success' => true, 'profile' => $profile);
}

/**
 * Update employee profile (limited fields that employees can edit)
 */
function updateEmployeeProfile()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;

    if (!$userID) {
        return array('err' => 1, 'msg' => 'Not authenticated');
    }

    // Fields that employees are allowed to edit (dbColumn => postFieldName)
    $allowedFields = array(
        'userMobile' => 'phone',
        'currentAddress' => 'address',
        'dateOfBirth' => 'dateOfBirth',
        'bankName' => 'bankName',
        'bankBranch' => 'bankBranch',
        'bankAccountNo' => 'accountNumber',
        'bankIFSC' => 'ifscCode',
        'panNo' => 'panNumber',
        'emergencyContactName' => 'emergencyContactName',
        'emergencyContactRelation' => 'emergencyContactRelation',
        'emergencyContact' => 'emergencyContactPhone'
    );

    $updates = array();
    $vals = array();
    $types = "";

    foreach ($allowedFields as $dbField => $postField) {
        if (isset($_POST[$postField])) {
            $value = trim($_POST[$postField]);
            // Sanitize input
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $updates[] = "`$dbField` = ?";
            $vals[] = $value;
            $types .= "s";
        }
    }

    if (empty($updates)) {
        return array('err' => 1, 'msg' => 'No fields to update');
    }

    // Add userID to the values
    $vals[] = $userID;
    $types .= "i";

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "UPDATE `" . $DB->pre . "x_admin_user`
                SET " . implode(", ", $updates) . "
                WHERE userID = ?";

    if ($DB->dbQuery()) {
        return array('err' => 0, 'msg' => 'Profile updated successfully');
    } else {
        return array('err' => 1, 'msg' => 'Failed to update profile');
    }
}

/**
 * Get employee documents for portal
 */
function getEmployeeDocumentsForPortal()
{
    global $DB;
    $userID = $_SESSION['HRMS_USER_ID'] ?? 0;

    if (!$userID) {
        return array('success' => false, 'message' => 'Not authenticated');
    }

    $DB->vals = array(1, $userID);
    $DB->types = "ii";
    $DB->sql = "SELECT documentID, documentName, documentType, fileName, fileType, fileSize, createdAt
                FROM `" . $DB->pre . "employee_document`
                WHERE status=? AND userID=?
                ORDER BY createdAt DESC";
    $documents = $DB->dbRows();

    $formattedDocs = array();
    foreach ($documents as $doc) {
        $formattedDocs[] = array(
            'documentID' => $doc['documentID'],
            'documentName' => $doc['documentName'],
            'documentType' => $doc['documentType'],
            'fileType' => strtolower(pathinfo($doc['fileName'], PATHINFO_EXTENSION)),
            'fileSize' => intval($doc['fileSize'] ?? 0),
            'uploadedAt' => $doc['createdAt'],
            'fileUrl' => UPLOADURL . '/employee-documents/' . $doc['fileName']
        );
    }

    return array('success' => true, 'documents' => $formattedDocs);
}

/**
 * Get complete employee details for HR Admin/Manager view
 */
function getEmployeeFullDetails()
{
    global $DB;

    $employeeID = intval($_GET['employeeID'] ?? $_POST['employeeID'] ?? 0);

    if (!$employeeID) {
        return array('success' => false, 'message' => 'Employee ID required');
    }

    // Check access permission
    if (!canViewEmployee($employeeID)) {
        return array('success' => false, 'message' => 'Access denied');
    }

    // Get employee profile
    $DB->vals = array(1, $employeeID);
    $DB->types = "ii";
    $DB->sql = "SELECT u.*,
                       (SELECT COALESCE(displayName, userName) FROM `" . $DB->pre . "x_admin_user` WHERE userID = u.managerID) as managerName
                FROM `" . $DB->pre . "x_admin_user` u
                WHERE u.status=? AND u.userID=?";
    $employee = $DB->dbRow();

    if (!$employee) {
        return array('success' => false, 'message' => 'Employee not found');
    }

    $profile = array(
        'userID' => $employee['userID'],
        'userName' => $employee['displayName'] ?: $employee['userName'],
        'email' => $employee['userEmail'],
        'phone' => $employee['userPhone'] ?? '',
        'employeeCode' => $employee['employeeCode'] ?? 'EMP' . str_pad($employee['userID'], 4, '0', STR_PAD_LEFT),
        'designation' => $employee['designation'] ?? '',
        'department' => $employee['department'] ?? '',
        'dateOfJoining' => $employee['dateOfJoining'] ?? '',
        'managerName' => $employee['managerName'] ?? ''
    );

    return array('success' => true, 'profile' => $profile);
}

/**
 * Get employee attendance for HR Admin/Manager view
 */
function getEmployeeAttendanceForAdmin()
{
    global $DB;

    $employeeID = intval($_GET['employeeID'] ?? $_POST['employeeID'] ?? 0);
    $month = intval($_GET['month'] ?? $_POST['month'] ?? date('n'));
    $year = intval($_GET['year'] ?? $_POST['year'] ?? date('Y'));

    if (!$employeeID) {
        return array('success' => false, 'message' => 'Employee ID required');
    }

    // Check access permission
    if (!canViewEmployee($employeeID)) {
        return array('success' => false, 'message' => 'Access denied');
    }

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));
    $daysInMonth = date('t', strtotime($startDate));

    // Get attendance records
    $DB->vals = array($employeeID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "attendance`
                WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=?
                ORDER BY attendanceDate DESC";
    $attendance = $DB->dbRows();

    // Calculate summary
    $summary = array(
        'present' => 0,
        'absent' => 0,
        'late' => 0,
        'leave' => 0,
        'halfDay' => 0,
        'totalHours' => 0
    );

    $records = array();
    foreach ($attendance as $att) {
        $status = $att['attendanceStatus'];
        if ($status === 'present') {
            $summary['present']++;
            if ($att['isLate']) $summary['late']++;
        } elseif ($status === 'absent') {
            $summary['absent']++;
        } elseif ($status === 'leave') {
            $summary['leave']++;
        } elseif ($status === 'half_day') {
            $summary['halfDay']++;
        }
        $summary['totalHours'] += floatval($att['workingHours'] ?? 0);

        $records[] = array(
            'date' => $att['attendanceDate'],
            'dateFormatted' => date('d M Y', strtotime($att['attendanceDate'])),
            'dayName' => date('D', strtotime($att['attendanceDate'])),
            'checkIn' => $att['checkIn'] ? date('h:i A', strtotime($att['checkIn'])) : '-',
            'checkOut' => $att['checkOut'] ? date('h:i A', strtotime($att['checkOut'])) : '-',
            'workingHours' => floatval($att['workingHours'] ?? 0),
            'status' => $status,
            'isLate' => $att['isLate'] == 1,
            'lateMinutes' => intval($att['lateMinutes'] ?? 0)
        );
    }

    $summary['totalHours'] = round($summary['totalHours'], 1);

    return array(
        'success' => true,
        'summary' => $summary,
        'records' => $records,
        'month' => $month,
        'year' => $year
    );
}

/**
 * Get employee salary slips for HR Admin/Manager view
 */
function getEmployeeSalarySlipsForAdmin()
{
    global $DB;

    $employeeID = intval($_GET['employeeID'] ?? $_POST['employeeID'] ?? 0);

    if (!$employeeID) {
        return array('success' => false, 'message' => 'Employee ID required');
    }

    // Check access permission
    if (!canViewEmployee($employeeID)) {
        return array('success' => false, 'message' => 'Access denied');
    }

    $DB->vals = array($employeeID);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "salary_slip`
                WHERE userID=? AND slipStatus IN ('paid', 'slip_generated', 'emailed')
                ORDER BY salaryYear DESC, salaryMonth DESC";
    $slips = $DB->dbRows();

    $formattedSlips = array();
    $months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    foreach ($slips as $slip) {
        $formattedSlips[] = array(
            'slipID' => $slip['slipID'],
            'month' => $months[$slip['salaryMonth']] ?? '',
            'year' => $slip['salaryYear'],
            'period' => $months[$slip['salaryMonth']] . ' ' . $slip['salaryYear'],
            'grossSalary' => floatval($slip['totalEarnings'] ?? 0),
            'deductions' => floatval($slip['totalDeductions'] ?? 0),
            'netSalary' => floatval($slip['netSalary'] ?? 0),
            'status' => $slip['slipStatus'],
            'paidOn' => $slip['paidOn'] ? date('d M Y', strtotime($slip['paidOn'])) : '-',
            'pdfUrl' => $slip['slipPDF'] ? UPLOADURL . '/salary-slip/' . $slip['slipPDF'] : null
        );
    }

    return array('success' => true, 'slips' => $formattedSlips);
}

/**
 * Get employee documents for HR Admin/Manager view
 */
function getEmployeeDocumentsForAdmin()
{
    global $DB;

    $employeeID = intval($_GET['employeeID'] ?? $_POST['employeeID'] ?? 0);

    if (!$employeeID) {
        return array('success' => false, 'message' => 'Employee ID required');
    }

    // Check access permission
    if (!canViewEmployee($employeeID)) {
        return array('success' => false, 'message' => 'Access denied');
    }

    $DB->vals = array(1, $employeeID);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "employee_document`
                WHERE status=? AND userID=?
                ORDER BY createdAt DESC";
    $documents = $DB->dbRows();

    $formattedDocs = array();
    foreach ($documents as $doc) {
        $formattedDocs[] = array(
            'documentID' => $doc['documentID'],
            'documentName' => $doc['documentName'],
            'documentType' => ucwords(str_replace('_', ' ', $doc['documentType'] ?? '')),
            'fileName' => $doc['fileName'],
            'fileType' => strtolower(pathinfo($doc['fileName'], PATHINFO_EXTENSION)),
            'fileSize' => intval($doc['fileSize'] ?? 0),
            'uploadedAt' => date('d M Y', strtotime($doc['createdAt'])),
            'fileUrl' => UPLOADURL . '/employee-document/' . $doc['fileName']
        );
    }

    return array('success' => true, 'documents' => $formattedDocs);
}

/**
 * Get all employees for HR Admin
 */
function getAllEmployeesForAdmin()
{
    global $DB;

    $isHRAdmin = isHRMasterAdmin();
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $currentUserID = $_SESSION['HRMS_USER_ID'] ?? 0;

    // Must be HR Admin or Manager
    if (!$isHRAdmin && !$isManager) {
        return array('success' => false, 'message' => 'Access denied');
    }

    if (!$currentUserID) {
        return array('success' => false, 'message' => 'Not authenticated');
    }

    if ($isHRAdmin) {
        // HR Admin - get all employees
        $DB->vals = array(1);
        $DB->types = "i";
        $DB->sql = "SELECT userID, userName, displayName, userEmail, designation, department, employeeCode
                    FROM `" . $DB->pre . "x_admin_user`
                    WHERE status=?
                    ORDER BY COALESCE(displayName, userName)";
    } else {
        // Manager - return only their direct reports
        $DB->vals = array(1, $currentUserID);
        $DB->types = "ii";
        $DB->sql = "SELECT userID, userName, displayName, userEmail, designation, department, employeeCode
                    FROM `" . $DB->pre . "x_admin_user`
                    WHERE status=? AND managerID=?
                    ORDER BY COALESCE(displayName, userName)";
    }

    $employees = $DB->dbRows();

    $formattedEmployees = array();
    foreach ($employees as $emp) {
        $formattedEmployees[] = array(
            'userID' => $emp['userID'],
            'userName' => $emp['displayName'] ?: $emp['userName'],
            'email' => $emp['userEmail'],
            'designation' => $emp['designation'] ?? '',
            'department' => $emp['department'] ?? '',
            'employeeCode' => $emp['employeeCode'] ?? 'EMP' . str_pad($emp['userID'], 4, '0', STR_PAD_LEFT)
        );
    }

    return array('success' => true, 'employees' => $formattedEmployees, 'isHRAdmin' => $isHRAdmin);
}

/**
 * Switch to view portal as another user (HR Admin only)
 */
function switchToUser()
{
    global $DB;

    // Only HR Admin can switch users
    if (!isHRMasterAdmin()) {
        return array('err' => 1, 'msg' => 'Access denied. Only HR Admin can switch users.');
    }

    $targetUserID = intval($_POST['targetUserID'] ?? 0);

    if (!$targetUserID) {
        return array('err' => 1, 'msg' => 'Invalid user ID');
    }

    // Get target user info
    $DB->vals = array(1, $targetUserID);
    $DB->types = "ii";
    $DB->sql = "SELECT userID, userName, displayName, userEmail, isLeaveManager FROM `" . $DB->pre . "x_admin_user` WHERE status=? AND userID=?";
    $targetUser = $DB->dbRow();

    if (!$targetUser) {
        return array('err' => 1, 'msg' => 'User not found');
    }

    $displayName = $targetUser['displayName'] ?: $targetUser['userName'];

    // Store original admin info before switching
    if (empty($_SESSION['HRMS_VIEWING_AS'])) {
        $_SESSION['HRMS_ORIGINAL_USER_ID'] = $_SESSION['HRMS_USER_ID'];
        $_SESSION['HRMS_ORIGINAL_USER_NAME'] = $_SESSION['HRMS_USER_NAME'];
        $_SESSION['HRMS_ORIGINAL_USER_EMAIL'] = $_SESSION['HRMS_USER_EMAIL'];
        $_SESSION['HRMS_ORIGINAL_IS_HR_ADMIN'] = $_SESSION['HRMS_IS_HR_ADMIN'] ?? false;
    }

    // Switch to target user
    $_SESSION['HRMS_USER_ID'] = $targetUser['userID'];
    $_SESSION['HRMS_USER_NAME'] = $displayName;
    $_SESSION['HRMS_USER_EMAIL'] = $targetUser['userEmail'];
    $_SESSION['HRMS_IS_MANAGER'] = ($targetUser['isLeaveManager'] == 1);
    $_SESSION['HRMS_VIEWING_AS'] = true;

    // SECURITY: Set HR Admin to false for the viewed user - admin features checked via isHRMasterAdmin()
    // isHRMasterAdmin() checks HRMS_ORIGINAL_IS_HR_ADMIN when in viewing mode
    $_SESSION['HRMS_IS_HR_ADMIN'] = false;

    return array('err' => 0, 'msg' => 'Switched to ' . $displayName);
}

/**
 * Switch back to original HR Admin user
 */
function switchBackToAdmin()
{
    // Check if we're in viewing mode
    if (empty($_SESSION['HRMS_VIEWING_AS'])) {
        return array('err' => 1, 'msg' => 'Not in view mode');
    }

    // Check if we have original user info
    if (empty($_SESSION['HRMS_ORIGINAL_USER_ID'])) {
        // Clear any stale viewing state
        unset($_SESSION['HRMS_VIEWING_AS']);
        return array('err' => 1, 'msg' => 'Original user info not found');
    }

    // SECURITY: Verify the original user was actually an HR Admin
    if (empty($_SESSION['HRMS_ORIGINAL_IS_HR_ADMIN']) || $_SESSION['HRMS_ORIGINAL_IS_HR_ADMIN'] !== true) {
        // Clear any stale viewing state - this is a security breach attempt
        unset($_SESSION['HRMS_VIEWING_AS']);
        unset($_SESSION['HRMS_ORIGINAL_USER_ID']);
        unset($_SESSION['HRMS_ORIGINAL_USER_NAME']);
        unset($_SESSION['HRMS_ORIGINAL_USER_EMAIL']);
        unset($_SESSION['HRMS_ORIGINAL_IS_HR_ADMIN']);
        return array('err' => 1, 'msg' => 'Unauthorized access');
    }

    // Restore original admin session
    $_SESSION['HRMS_USER_ID'] = $_SESSION['HRMS_ORIGINAL_USER_ID'];
    $_SESSION['HRMS_USER_NAME'] = $_SESSION['HRMS_ORIGINAL_USER_NAME'];
    $_SESSION['HRMS_USER_EMAIL'] = $_SESSION['HRMS_ORIGINAL_USER_EMAIL'];
    $_SESSION['HRMS_IS_MANAGER'] = true; // HR Admin is always a manager
    $_SESSION['HRMS_IS_HR_ADMIN'] = true; // Only HR Admins can switch back

    // Clear viewing mode
    unset($_SESSION['HRMS_VIEWING_AS']);
    unset($_SESSION['HRMS_ORIGINAL_USER_ID']);
    unset($_SESSION['HRMS_ORIGINAL_USER_NAME']);
    unset($_SESSION['HRMS_ORIGINAL_USER_EMAIL']);
    unset($_SESSION['HRMS_ORIGINAL_IS_HR_ADMIN']);

    return array('err' => 0, 'msg' => 'Switched back to admin');
}

/**
 * Download employee attendance PDF for admin
 */
function downloadEmployeeAttendancePDFAdmin()
{
    global $DB;

    $employeeID = intval($_GET['employeeID'] ?? 0);
    $month = intval($_GET['month'] ?? date('n'));
    $year = intval($_GET['year'] ?? date('Y'));

    if (!$employeeID || !canViewEmployee($employeeID)) {
        die('Access denied');
    }

    // Get employee name
    $DB->vals = array(1, $employeeID);
    $DB->types = "ii";
    $DB->sql = "SELECT userName, displayName FROM `" . $DB->pre . "x_admin_user` WHERE status=? AND userID=?";
    $emp = $DB->dbRow();
    $userName = $emp['displayName'] ?: ($emp['userName'] ?? 'Employee');

    // Temporarily set session for download function
    $originalUserID = $_SESSION['HRMS_USER_ID'] ?? null;
    $originalUserName = $_SESSION['HRMS_USER_NAME'] ?? null;

    $_SESSION['HRMS_USER_ID'] = $employeeID;
    $_SESSION['HRMS_USER_NAME'] = $userName;

    downloadAttendancePDF();

    // Restore original session (won't reach here due to exit in downloadAttendancePDF)
    if ($originalUserID) $_SESSION['HRMS_USER_ID'] = $originalUserID;
    if ($originalUserName) $_SESSION['HRMS_USER_NAME'] = $originalUserName;
}

/**
 * Send OTP Email via Brevo
 */
function sendHRMSOTPEmail($email, $name, $otp)
{
    $brevo = getBrevoService();

    if (!$brevo->isConfigured()) {
        error_log("Brevo not configured - HRMS OTP email skipped");
        return false;
    }

    $htmlContent = buildHRMSOTPEmail($name, $otp);

    $emailParams = array(
        'to' => array(
            array(
                'email' => $email,
                'name' => $name
            )
        ),
        'subject' => 'Your HRMS Portal Login OTP - Bombay Engineering',
        'htmlContent' => $htmlContent,
        'tags' => array('hrms', 'otp', 'login')
    );

    $result = $brevo->sendEmail($emailParams);
    return $result['success'];
}

/**
 * Build OTP Email Template
 */
function buildHRMSOTPEmail($name, $otp)
{
    $name = htmlspecialchars($name);
    $expiryTime = '10 minutes';

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS Login OTP</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width: 480px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 32px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">HRMS Portal</p>
                            <p style="margin: 8px 0 0; font-size: 14px; color: #94a3b8;">Bombay Engineering Syndicate</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; font-size: 16px; color: #334155; line-height: 1.6;">Hello <strong>{$name}</strong>,</p>

                            <p style="margin: 0 0 30px; font-size: 15px; color: #64748b; line-height: 1.6;">Use the following OTP to login to your HRMS Portal account:</p>

                            <!-- OTP Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <div style="background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); border-radius: 12px; padding: 24px 40px; display: inline-block;">
                                            <p style="margin: 0; font-size: 36px; font-weight: 800; color: #0f172a; letter-spacing: 8px; font-family: 'SF Mono', Monaco, monospace;">{$otp}</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Warning -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 0 8px 8px 0; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="margin: 0; font-size: 13px; color: #92400e; line-height: 1.5;">
                                            This OTP will expire in <strong>{$expiryTime}</strong>. Do not share this code with anyone.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.6;">If you didn't request this OTP, please ignore this email or contact HR immediately.</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 40px; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8; text-align: center;">
                                Bombay Engineering Syndicate<br>
                                This is an automated message. Please do not reply.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * Mask email for display
 */
function maskEmail($email)
{
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1] ?? '';

    if (strlen($name) <= 2) {
        $masked = $name[0] . '***';
    } else {
        $masked = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
    }

    return $masked . '@' . $domain;
}

/**
 * Check if user is logged in
 */
function isHRMSLoggedIn()
{
    return isset($_SESSION['HRMS_LOGIN']) && $_SESSION['HRMS_LOGIN'] === true;
}

/**
 * Require HRMS login
 */
function requireHRMSLogin()
{
    if (!isHRMSLoggedIn()) {
        header('Location: ' . SITEURL . '/hrms/login/');
        exit;
    }
}

/**
 * Download Attendance as Excel (from Attendance page)
 */
function downloadAttendanceExcel()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        die('Not authorized');
    }

    $userID = $_SESSION['HRMS_USER_ID'];
    $userName = $_SESSION['HRMS_USER_NAME'] ?? 'Employee';
    $month = intval($_GET['month'] ?? date('n'));
    $year = intval($_GET['year'] ?? date('Y'));

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));
    $daysInMonth = date('t', strtotime($startDate));
    $monthName = date('F Y', strtotime($startDate));

    // Get attendance data
    $DB->vals = array($userID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT attendanceDate, attendanceStatus, isLate, checkIn, checkOut, workingHours
                FROM " . $DB->pre . "attendance
                WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=?
                ORDER BY attendanceDate ASC";
    $attRows = $DB->dbRows();

    $attLookup = [];
    foreach ($attRows as $att) {
        $attLookup[$att['attendanceDate']] = $att;
    }

    // Get holidays
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT ahDate FROM " . $DB->pre . "attendance_holidays WHERE ahDate BETWEEN ? AND ? AND status=?";
    $holidayRows = $DB->dbRows();
    $holidays = array_column($holidayRows, 'ahDate');

    // Build Excel using PhpSpreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Attendance');

    // Header styles
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    // Title
    $sheet->setCellValue('A1', 'Attendance Report - ' . $userName);
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', $monthName);
    $sheet->mergeCells('A2:F2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Column headers
    $headers = ['Day', 'Date', 'Check In', 'Check Out', 'Hours', 'Status'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '4', $header);
        $col++;
    }
    $sheet->getStyle('A4:F4')->applyFromArray($headerStyle);

    // Data rows
    $row = 5;
    $totalHours = 0;
    $presentDays = 0;
    $absentDays = 0;
    $leaveDays = 0;
    $lateDays = 0;

    // Status colors
    $statusColors = [
        'Present' => 'DCFCE7',
        'Late' => 'FEF3C7',
        'Absent' => 'FEE2E2',
        'Leave' => 'DBEAFE',
        'Half Day' => 'FAE8FF',
        'Weekly Off' => 'F1F5F9',
        'Holiday' => 'F1F5F9'
    ];

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dayOfWeek = date('w', strtotime($dateStr));
        $dayName = date('D', strtotime($dateStr));
        $isFuture = strtotime($dateStr) > time();

        $checkIn = '-';
        $checkOut = '-';
        $hours = '-';
        $status = '-';

        if ($isFuture) {
            $status = '-';
        } elseif ($dayOfWeek == 0) {
            $status = 'Weekly Off';
        } elseif (in_array($dateStr, $holidays)) {
            $status = 'Holiday';
        } elseif (isset($attLookup[$dateStr])) {
            $att = $attLookup[$dateStr];
            $checkIn = $att['checkIn'] ? date('h:i A', strtotime($att['checkIn'])) : '-';
            $checkOut = $att['checkOut'] ? date('h:i A', strtotime($att['checkOut'])) : '-';
            $hoursVal = floatval($att['workingHours'] ?? 0);
            if ($hoursVal > 0) {
                $totalHours += $hoursVal;
                $hours = number_format($hoursVal, 1);
            }

            switch ($att['attendanceStatus']) {
                case 'present':
                    $status = $att['isLate'] ? 'Late' : 'Present';
                    $presentDays++;
                    if ($att['isLate']) $lateDays++;
                    break;
                case 'absent':
                    $status = 'Absent';
                    $absentDays++;
                    break;
                case 'leave':
                    $status = 'Leave';
                    $leaveDays++;
                    break;
                case 'half_day':
                    $status = 'Half Day';
                    $presentDays += 0.5;
                    break;
                default:
                    $status = 'Present';
                    $presentDays++;
            }
        } else {
            $status = 'Absent';
            $absentDays++;
        }

        $sheet->setCellValue('A' . $row, $dayName);
        $sheet->setCellValue('B' . $row, date('d M Y', strtotime($dateStr)));
        $sheet->setCellValue('C' . $row, $checkIn);
        $sheet->setCellValue('D' . $row, $checkOut);
        $sheet->setCellValue('E' . $row, $hours);
        $sheet->setCellValue('F' . $row, $status);

        // Apply status color
        if (isset($statusColors[$status])) {
            $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($statusColors[$status]);
        }

        $sheet->getStyle('A' . $row . ':F' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row++;
    }

    // Summary section
    $row += 2;
    $sheet->setCellValue('A' . $row, 'Summary');
    $sheet->setCellValue('B' . $row, 'Count');
    $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($headerStyle);
    $row++;

    $summaryData = [
        ['Present Days', $presentDays],
        ['Absent Days', $absentDays],
        ['Leave Days', $leaveDays],
        ['Late Days', $lateDays],
        ['Total Hours', round($totalHours, 1)]
    ];

    foreach ($summaryData as $item) {
        $sheet->setCellValue('A' . $row, $item[0]);
        $sheet->setCellValue('B' . $row, $item[1]);
        $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row++;
    }

    // Auto-size columns
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Generated timestamp
    $row += 2;
    $sheet->setCellValue('A' . $row, 'Generated on ' . date('d M Y h:i A'));
    $sheet->getStyle('A' . $row)->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('666666'));

    // Output
    $filename = 'attendance_' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Download Attendance as PDF (HTML print-ready page)
 */
function downloadAttendancePDF()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        die('Not authorized');
    }

    $userID = $_SESSION['HRMS_USER_ID'];
    $userName = $_SESSION['HRMS_USER_NAME'] ?? 'Employee';
    $month = intval($_GET['month'] ?? date('n'));
    $year = intval($_GET['year'] ?? date('Y'));

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));
    $daysInMonth = date('t', strtotime($startDate));
    $monthName = date('F Y', strtotime($startDate));

    // Get attendance data
    $DB->vals = array($userID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT attendanceDate, attendanceStatus, isLate, checkIn, checkOut, workingHours, lateMinutes
                FROM " . $DB->pre . "attendance
                WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=?
                ORDER BY attendanceDate ASC";
    $attRows = $DB->dbRows();

    $attLookup = [];
    foreach ($attRows as $att) {
        $attLookup[$att['attendanceDate']] = $att;
    }

    // Get holidays
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT ahDate FROM " . $DB->pre . "attendance_holidays WHERE ahDate BETWEEN ? AND ? AND status=?";
    $holidayRows = $DB->dbRows();
    $holidays = array_column($holidayRows, 'ahDate');

    // Build HTML for PDF
    header('Content-Type: text/html; charset=utf-8');

    $totalHours = 0;
    $presentDays = 0;
    $absentDays = 0;
    $leaveDays = 0;
    $lateDays = 0;
    $halfDays = 0;

    $rows = '';
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dayOfWeek = date('w', strtotime($dateStr));
        $dayName = date('D', strtotime($dateStr));
        $isFuture = strtotime($dateStr) > time();

        $checkIn = '-';
        $checkOut = '-';
        $hours = '-';
        $status = '-';
        $statusClass = '';
        $lateInfo = '';

        if ($isFuture) {
            $status = '-';
            $statusClass = 'future';
        } elseif ($dayOfWeek == 0) {
            $status = 'Weekly Off';
            $statusClass = 'weekoff';
        } elseif (in_array($dateStr, $holidays)) {
            $status = 'Holiday';
            $statusClass = 'holiday';
        } elseif (isset($attLookup[$dateStr])) {
            $att = $attLookup[$dateStr];
            $checkIn = $att['checkIn'] ? date('h:i A', strtotime($att['checkIn'])) : '-';
            $checkOut = $att['checkOut'] ? date('h:i A', strtotime($att['checkOut'])) : '-';
            $hoursVal = floatval($att['workingHours'] ?? 0);
            if ($hoursVal > 0) {
                $totalHours += $hoursVal;
                $hours = number_format($hoursVal, 1) . ' hrs';
            }

            switch ($att['attendanceStatus']) {
                case 'present':
                    if ($att['isLate']) {
                        $status = 'Late';
                        $statusClass = 'late';
                        $lateDays++;
                        $lateInfo = ' (' . intval($att['lateMinutes']) . ' min)';
                    } else {
                        $status = 'Present';
                        $statusClass = 'present';
                    }
                    $presentDays++;
                    break;
                case 'absent':
                    $status = 'Absent';
                    $statusClass = 'absent';
                    $absentDays++;
                    break;
                case 'leave':
                    $status = 'Leave';
                    $statusClass = 'leave';
                    $leaveDays++;
                    break;
                case 'half_day':
                    $status = 'Half Day';
                    $statusClass = 'halfday';
                    $halfDays++;
                    $presentDays += 0.5;
                    break;
                default:
                    $status = 'Present';
                    $statusClass = 'present';
                    $presentDays++;
            }
        } else {
            $status = 'Absent';
            $statusClass = 'absent';
            $absentDays++;
        }

        $rows .= '<tr class="' . $statusClass . '">';
        $rows .= '<td class="day-col">' . $dayName . '</td>';
        $rows .= '<td class="date-col">' . date('d M Y', strtotime($dateStr)) . '</td>';
        $rows .= '<td class="time-col">' . $checkIn . '</td>';
        $rows .= '<td class="time-col">' . $checkOut . '</td>';
        $rows .= '<td class="hours-col">' . $hours . '</td>';
        $rows .= '<td class="status-col">' . $status . $lateInfo . '</td>';
        $rows .= '</tr>';
    }

    // Calculate working days (excluding Sundays and holidays)
    $workingDays = $daysInMonth;
    $sundays = 0;
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
        if (date('w', strtotime($dateStr)) == 0) $sundays++;
    }
    $workingDays = $daysInMonth - $sundays - count($holidays);

    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - ' . htmlspecialchars($userName) . ' - ' . $monthName . '</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f1f5f9;
            padding: 20px;
            color: #1e293b;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .header .subtitle {
            font-size: 14px;
            color: #94a3b8;
        }
        .header .month {
            font-size: 18px;
            color: #f59e0b;
            font-weight: 600;
            margin-top: 12px;
        }
        .print-actions {
            padding: 16px 30px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        .print-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .print-btn-primary {
            background: #0f172a;
            color: #fff;
        }
        .print-btn-primary:hover { background: #1e293b; }
        .print-btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }
        .print-btn-secondary:hover { background: #cbd5e1; }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            padding: 24px 30px;
            background: #f8fafc;
        }
        .summary-card {
            background: #fff;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .summary-card .value {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
        }
        .summary-card .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-top: 4px;
        }
        .summary-card.present .value { color: #16a34a; }
        .summary-card.absent .value { color: #dc2626; }
        .summary-card.late .value { color: #f59e0b; }
        .summary-card.leave .value { color: #2563eb; }
        .summary-card.hours .value { color: #7c3aed; font-size: 22px; }
        .content { padding: 0 30px 30px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 13px;
        }
        th {
            background: #0f172a;
            color: #fff;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:hover { background: #f8fafc; }
        tr.present td { background: rgba(22, 163, 74, 0.05); }
        tr.absent td { background: rgba(220, 38, 38, 0.05); }
        tr.late td { background: rgba(245, 158, 11, 0.08); }
        tr.leave td { background: rgba(37, 99, 235, 0.05); }
        tr.weekoff td, tr.holiday td { background: #f1f5f9; color: #64748b; }
        tr.future td { color: #cbd5e1; }
        .day-col { width: 60px; font-weight: 600; }
        .date-col { width: 120px; }
        .time-col { width: 100px; font-family: monospace; }
        .hours-col { width: 80px; font-family: monospace; }
        .status-col { font-weight: 600; }
        tr.present .status-col { color: #16a34a; }
        tr.absent .status-col { color: #dc2626; }
        tr.late .status-col { color: #f59e0b; }
        tr.leave .status-col { color: #2563eb; }
        tr.halfday .status-col { color: #7c3aed; }
        .footer {
            padding: 20px 30px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .container { box-shadow: none; }
            .print-actions { display: none !important; }
            .header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .summary-cards { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr.present td, tr.absent td, tr.late td, tr.leave td, tr.weekoff td, tr.holiday td {
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
        }
        @media (max-width: 768px) {
            .summary-cards { grid-template-columns: repeat(3, 1fr); }
            .print-actions { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Attendance Report</h1>
            <div class="subtitle">' . htmlspecialchars($userName) . '</div>
            <div class="month">' . $monthName . '</div>
        </div>

        <div class="print-actions">
            <button class="print-btn print-btn-secondary" onclick="window.close()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Close
            </button>
            <button class="print-btn print-btn-primary" onclick="window.print()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print / Save as PDF
            </button>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="value">' . $workingDays . '</div>
                <div class="label">Working Days</div>
            </div>
            <div class="summary-card present">
                <div class="value">' . $presentDays . '</div>
                <div class="label">Present</div>
            </div>
            <div class="summary-card absent">
                <div class="value">' . $absentDays . '</div>
                <div class="label">Absent</div>
            </div>
            <div class="summary-card late">
                <div class="value">' . $lateDays . '</div>
                <div class="label">Late</div>
            </div>
            <div class="summary-card hours">
                <div class="value">' . round($totalHours, 1) . ' hrs</div>
                <div class="label">Total Hours</div>
            </div>
        </div>

        <div class="content">
            <table>
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $rows . '
                </tbody>
            </table>
        </div>

        <div class="footer">
            <span>Bombay Engineering Syndicate - HRMS</span>
            <span>Generated on ' . date('d M Y, h:i A') . '</span>
        </div>
    </div>

    <script>
        // Auto-trigger print dialog after page loads (optional)
        // window.onload = function() { window.print(); };
    </script>
</body>
</html>';
    exit;
}

// Handle download via action parameter (legacy support)
if (isset($_GET["action"]) && $_GET["action"] === "downloadAttendance") {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once dirname(__FILE__) . "/../../../config.inc.php";
    require_once dirname(__FILE__) . "/../../../core/db.inc.php";
    $DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);

    downloadAttendanceExcel();
    exit;
}

// Handle PDF download
if (isset($_GET["action"]) && $_GET["action"] === "downloadAttendancePDF") {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once dirname(__FILE__) . "/../../../config.inc.php";
    require_once dirname(__FILE__) . "/../../../core/db.inc.php";
    $DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);

    downloadAttendancePDF();
    exit;
}

// Handle Admin PDF download (for viewing employee attendance)
if (isset($_GET["action"]) && $_GET["action"] === "downloadEmployeeAttendancePDF") {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once dirname(__FILE__) . "/../../../config.inc.php";
    require_once dirname(__FILE__) . "/../../../core/db.inc.php";
    $DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);

    downloadEmployeeAttendancePDFAdmin();
    exit;
}

// Handle AJAX requests via GET (for portal pages)
if (isset($_GET["ajax"])) {
    // Start session if not already started (required for HRMS login check)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Include core files for DB access
    require_once dirname(__FILE__) . "/../../../config.inc.php";
    require_once dirname(__FILE__) . "/../../../core/db.inc.php";
    $DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);

    header('Content-Type: application/json');
    $result = array('success' => false, 'message' => 'Invalid action');

    switch ($_GET["ajax"]) {
        case "getEmployeeDashboard":
            $result = getEmployeeDashboard();
            break;
        case "getEmployeeAttendance":
            $result = getEmployeeAttendance();
            break;
        case "getEmployeeSalarySlips":
            $result = getEmployeeSalarySlips();
            break;
        case "getEmployeeDocuments":
            $result = getEmployeeDocumentsForPortal();
            break;
        case "getEmployeeProfile":
            $result = getEmployeeProfile();
            break;
        case "getTeamAttendance":
            $result = getTeamAttendance();
            break;
        case "getTeamStats":
            $result = getTeamStats();
            break;
        case "getTeamLeaveRequests":
            $result = getTeamLeaveRequests();
            break;
        case "getTeamRemarks":
            $result = getTeamRemarks();
            break;
        case "getTeamMemberAttendance":
            $result = getTeamMemberAttendanceForDate();
            break;
        case "downloadReport":
            downloadEmployeeReport();
            exit;
        // Employee Detail APIs for HR Admin/Managers
        case "getEmployeeDetails":
            $result = getEmployeeFullDetails();
            break;
        case "getEmployeeAttendanceAdmin":
            $result = getEmployeeAttendanceForAdmin();
            break;
        case "getEmployeeSalarySlipsAdmin":
            $result = getEmployeeSalarySlipsForAdmin();
            break;
        case "getEmployeeDocumentsAdmin":
            $result = getEmployeeDocumentsForAdmin();
            break;
        case "getAllEmployees":
            $result = getAllEmployeesForAdmin();
            break;
        case "getPendingLeaves":
            $result = getPendingLeavesForAdmin();
            break;
    }

    echo json_encode($result);
    exit;
}

// Handle AJAX requests via POST
if (isset($_POST["xAction"])) {
    // Start session if not already started (required for HRMS login check)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Include core files for DB access (skip mxCheckRequest - HRMS has its own session)
    require_once dirname(__FILE__) . "/../../../config.inc.php";
    require_once dirname(__FILE__) . "/../../../core/db.inc.php";
    $DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);

    header('Content-Type: application/json');
    $MXRES = array('err' => 0);

    switch ($_POST["xAction"]) {
        case "sendOTP":
            $MXRES = sendEmployeeOTP();
            break;
        case "verifyOTP":
            $MXRES = verifyEmployeeOTP();
            break;
        case "logout":
            $MXRES = hrmsLogout();
            break;
        case "getDashboard":
            $MXRES = getEmployeeDashboard();
            break;
        case "getAttendance":
            $MXRES = getEmployeeAttendance();
            break;
        case "submitRemark":
            $MXRES = submitAttendanceRemark();
            break;
        case "getSalarySlips":
            $MXRES = getEmployeeSalarySlips();
            break;
        case "getDocuments":
            $MXRES = getEmployeeDocuments();
            break;
        case "getTeamAttendance":
            $MXRES = getTeamAttendance();
            break;
        case "approveLeave":
            $MXRES = approveLeave();
            break;
        case "rejectLeave":
            $MXRES = rejectLeave();
            break;
        case "markTeamAttendance":
            $MXRES = markTeamMemberAttendance();
            break;
        case "addTeamRemark":
            $MXRES = addTeamMemberRemark();
            break;
        case "getLeaveBalance":
            $MXRES = getLeaveBalance();
            break;
        case "getLeaveTypes":
            $MXRES = getLeaveTypes();
            break;
        case "getLeaveHistory":
            $MXRES = getLeaveHistory();
            break;
        case "applyLeave":
            $MXRES = applyLeave();
            break;
        case "checkLeaveBalance":
            $MXRES = checkLeaveBalanceAPI();
            break;
        case "cancelLeave":
            $MXRES = cancelLeave();
            break;
        case "updateLeaveStatus":
            $MXRES = updateLeaveStatusFromPortal();
            break;
        case "getEmployeeReport":
            $MXRES = getEmployeeReport();
            break;
        case "switchUser":
            $MXRES = switchToUser();
            break;
        case "switchBack":
            $MXRES = switchBackToAdmin();
            break;
        case "updateProfile":
            $MXRES = updateEmployeeProfile();
            break;
        case "getSalaryProcessingList":
            $MXRES = getSalaryProcessingList();
            break;
        case "saveSalaryPayment":
            $MXRES = saveSalaryPayment();
            break;
        case "markSalaryPaid":
            $MXRES = markSalaryPaid();
            break;
        case "generateSalarySlip":
            $MXRES = generateSalarySlipPDF();
            break;
        case "downloadSalarySlip":
            $MXRES = downloadSalarySlipPDF();
            break;
        case "getSalaryAdvances":
            $MXRES = getSalaryAdvances();
            break;
        case "saveSalaryAdvance":
            $MXRES = saveSalaryAdvance();
            break;
        case "approveSalaryAdvance":
            $MXRES = approveSalaryAdvance();
            break;
        case "recordAdvanceRepayment":
            $MXRES = recordAdvanceRepayment();
            break;
        case "generateICICICMS":
            $MXRES = generateICICICMSFile();
            break;
        case "getICICIBankSettings":
            $MXRES = getICICIBankSettings();
            break;
        case "saveICICIBankSettings":
            $MXRES = saveICICIBankSettings();
            break;
        case "getPaymentHistory":
            $MXRES = getPaymentHistory();
            break;

        // Enhanced Leave Management APIs
        case "getLeaveBalanceSummary":
            $MXRES = getLeaveBalanceSummaryAPI();
            break;
        case "getCompOffBalance":
            $MXRES = getCompOffBalanceAPI();
            break;
        case "getCompOffHistory":
            $MXRES = getCompOffHistoryAPI();
            break;
        case "applyCompOff":
            $MXRES = applyCompOffAPI();
            break;
        case "processCompOff":
            $MXRES = processCompOffAPI();
            break;
        case "getEncashmentEligibility":
            $MXRES = getEncashmentEligibilityAPI();
            break;
        case "applyLeaveEncashment":
            $MXRES = applyLeaveEncashmentAPI();
            break;
        case "getLeaveTypeConfig":
            $MXRES = getLeaveTypeConfigAPI();
            break;

        default:
            $MXRES = array('err' => 1, 'msg' => 'Invalid action');
    }

    echo json_encode($MXRES);
    exit;
}

/**
 * Get Employee Report Data for Portal
 */
function getEmployeeReport()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'message' => 'Not logged in');
    }

    $currentUserID = $_SESSION['HRMS_USER_ID'];
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = isHRMasterAdmin();
    $targetUserIDRaw = $_POST['targetUserID'] ?? '';

    $reportType = $_POST['reportType'] ?? 'monthly';
    $month = intval($_POST['month'] ?? date('n'));
    $year = intval($_POST['year'] ?? date('Y'));
    $fyYear = isset($_POST['fyYear']) ? intval($_POST['fyYear']) : null;

    // For leave reports, use Financial Year dates
    if ($reportType === 'leave' && $fyYear) {
        $startDate = sprintf('%04d-04-01', $fyYear); // April 1st of FY start year
        $endDate = sprintf('%04d-03-31', $fyYear + 1); // March 31st of FY end year
        $daysInMonth = 0; // Not applicable for FY
        $monthName = 'FY ' . $fyYear . '-' . ($fyYear + 1);
        $fyLabel = 'FY ' . $fyYear . '-' . (substr($fyYear + 1, -2));
    } else {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $daysInMonth = date('t', strtotime($startDate));
        $monthName = date('F Y', strtotime($startDate));
        $fyLabel = null;
    }

    // Handle "all" employees (Master Report)
    if ($targetUserIDRaw === 'all') {
        // Only HR Admin can view all employees report
        if (!$isHRAdmin) {
            return array('err' => 1, 'message' => 'Not authorized to view all employees report');
        }
        return getMasterEmployeeReport($reportType, $month, $year, $startDate, $endDate, $daysInMonth, $monthName);
    }

    $targetUserID = intval($targetUserIDRaw);

    // Determine which user's report to generate
    if ($targetUserID && $targetUserID != $currentUserID) {
        // Trying to view another user's report - check authorization
        if (!$isManager && !$isHRAdmin) {
            return array('err' => 1, 'message' => 'Not authorized to view other employees\' reports');
        }

        // If manager (not HR Admin), verify the target is in their team
        if (!$isHRAdmin) {
            $DB->vals = array($targetUserID, 1, $currentUserID);
            $DB->types = "iii";
            $DB->sql = "SELECT userID FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=? AND managerID=?";
            $DB->dbRow();
            if ($DB->numRows == 0) {
                return array('err' => 1, 'message' => 'This employee is not in your team');
            }
        }

        $userID = $targetUserID;
    } else {
        $userID = $currentUserID;
    }

    switch ($reportType) {
        case 'monthly':
            return getMonthlyAttendanceReport($userID, $month, $year, $daysInMonth, $monthName);
        case 'summary':
            return getSummaryReport($userID, $month, $year, $startDate, $endDate, $daysInMonth, $monthName);
        case 'late_early':
            return getLateEarlyReportEmployee($userID, $startDate, $endDate, $monthName);
        case 'leave':
            $result = getLeaveHistoryReport($userID, $startDate, $endDate, $monthName);
            if ($fyLabel) {
                $result['fyLabel'] = $fyLabel;
            }
            return $result;
        case 'detailed':
            return getDetailedAttendanceReport($userID, $month, $year, $daysInMonth, $monthName);
        default:
            return array('err' => 1, 'message' => 'Invalid report type');
    }
}

/**
 * Get Monthly Attendance Report
 */
function getMonthlyAttendanceReport($userID, $month, $year, $daysInMonth, $monthName)
{
    global $DB;

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));

    // Get user's Saturday off setting
    $DB->vals = array($userID);
    $DB->types = "i";
    $DB->sql = "SELECT COALESCE(isSaturdayOff, 0) as isSaturdayOff FROM " . $DB->pre . "x_admin_user WHERE userID=?";
    $userSettings = $DB->dbRow();
    $isSaturdayOff = $userSettings['isSaturdayOff'] ?? 0;

    // Get holidays
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT ahDate FROM " . $DB->pre . "attendance_holidays WHERE ahDate BETWEEN ? AND ? AND status=?";
    $holidayRows = $DB->dbRows();
    $holidays = array_column($holidayRows, 'ahDate');

    // Get attendance
    $DB->vals = array($userID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT attendanceDate, attendanceStatus, isLate, checkIn, checkOut, workingHours
                FROM " . $DB->pre . "attendance
                WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=?";
    $attRows = $DB->dbRows();

    $attLookup = [];
    foreach ($attRows as $att) {
        $attLookup[$att['attendanceDate']] = $att;
    }

    // Get approved leaves for this period (including countsAsPresent flag for Official Trip/On Duty)
    $DB->vals = array($userID, $startDate, $endDate, 'Approved');
    $DB->types = "isss";
    $DB->sql = "SELECT ld.leaveDate, COALESCE(lt.countsAsPresent, 0) as countsAsPresent
                FROM `" . $DB->pre . "leave_details` ld
                INNER JOIN `" . $DB->pre . "leave` l ON ld.leaveID = l.leaveID
                LEFT JOIN `" . $DB->pre . "leave_type` lt ON l.leaveType = lt.leaveTypeID
                WHERE ld.userID = ? AND ld.leaveDate BETWEEN ? AND ?
                AND l.leaveStatus = ? AND l.status = 1 AND ld.status = 1";
    $leaveRows = $DB->dbRows();

    $leaveLookup = [];
    foreach ($leaveRows as $lv) {
        // OD = On Duty (counts as present), L = Leave
        $leaveLookup[$lv['leaveDate']] = $lv['countsAsPresent'] ? 'OD' : 'L';
    }

    $days = [];
    $summary = ['P' => 0, 'A' => 0, 'L' => 0, 'LT' => 0, 'H' => 0, 'WO' => 0, 'HD' => 0, 'OD' => 0];
    $totalHours = 0;

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dayOfWeek = date('w', strtotime($dateStr));
        $isFuture = strtotime($dateStr) > time();

        if ($isFuture) {
            $days[$d] = '-';
        } elseif ($dayOfWeek == 0 || ($dayOfWeek == 6 && $isSaturdayOff)) {
            // Sunday or Saturday (if Saturday off for this user)
            $days[$d] = 'WO';
            $summary['WO']++;
        } elseif (in_array($dateStr, $holidays)) {
            $days[$d] = 'H';
            $summary['H']++;
        } elseif (isset($leaveLookup[$dateStr])) {
            // Check for approved leave first - OD counts as present
            $leaveType = $leaveLookup[$dateStr];
            $days[$d] = $leaveType;
            if ($leaveType === 'OD') {
                $summary['OD']++;
                $summary['P']++; // On Duty counts as present
            } else {
                $summary['L']++;
            }
        } elseif (isset($attLookup[$dateStr])) {
            $att = $attLookup[$dateStr];
            switch ($att['attendanceStatus']) {
                case 'present':
                    $days[$d] = $att['isLate'] ? 'LT' : 'P';
                    $summary['P']++;
                    if ($att['isLate']) $summary['LT']++;
                    break;
                case 'absent':
                    $days[$d] = 'A';
                    $summary['A']++;
                    break;
                case 'leave':
                    $days[$d] = 'L';
                    $summary['L']++;
                    break;
                case 'half_day':
                    $days[$d] = 'HD';
                    $summary['HD']++;
                    break;
                default:
                    $days[$d] = 'P';
                    $summary['P']++;
            }
            $totalHours += floatval($att['workingHours'] ?? 0);
        } else {
            $days[$d] = 'A';
            $summary['A']++;
        }
    }

    return array(
        'err' => 0,
        'monthName' => $monthName,
        'daysInMonth' => $daysInMonth,
        'days' => $days,
        'summary' => $summary,
        'totalHours' => round($totalHours, 1)
    );
}

/**
 * Get Summary Report
 */
function getSummaryReport($userID, $month, $year, $startDate, $endDate, $daysInMonth, $monthName)
{
    global $DB;

    // Get user's Saturday off setting
    $DB->vals = array($userID);
    $DB->types = "i";
    $DB->sql = "SELECT COALESCE(isSaturdayOff, 0) as isSaturdayOff FROM " . $DB->pre . "x_admin_user WHERE userID=?";
    $userSettings = $DB->dbRow();
    $isSaturdayOff = intval($userSettings['isSaturdayOff'] ?? 0);

    // Get holidays count and list
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT ahDate FROM " . $DB->pre . "attendance_holidays WHERE ahDate BETWEEN ? AND ? AND status=?";
    $holidayRows = $DB->dbRows();
    $holidays = array_column($holidayRows, 'ahDate');
    $holidayCount = count($holidays);

    // Count Sundays and Saturdays (if Saturday off for user)
    $weeklyOffs = 0;
    $currentDate = strtotime($startDate);
    while ($currentDate <= strtotime($endDate)) {
        $dow = date('w', $currentDate);
        if ($dow == 0 || ($dow == 6 && $isSaturdayOff)) {
            $weeklyOffs++;
        }
        $currentDate = strtotime('+1 day', $currentDate);
    }
    $workingDays = $daysInMonth - $weeklyOffs - $holidayCount;

    // Get approved leaves count for this period
    $DB->vals = array($userID, $startDate, $endDate, 'Approved');
    $DB->types = "isss";
    $DB->sql = "SELECT COUNT(*) as cnt FROM `" . $DB->pre . "leave_details` ld
                INNER JOIN `" . $DB->pre . "leave` l ON ld.leaveID = l.leaveID
                WHERE ld.userID = ? AND ld.leaveDate BETWEEN ? AND ?
                AND l.leaveStatus = ? AND l.status = 1 AND ld.status = 1";
    $leaveCount = intval($DB->dbRow()['cnt'] ?? 0);

    // Get attendance summary
    $DB->vals = array($userID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT
                    COUNT(*) as totalRecords,
                    SUM(CASE WHEN attendanceStatus='present' THEN 1 ELSE 0 END) as presentDays,
                    SUM(CASE WHEN attendanceStatus='absent' THEN 1 ELSE 0 END) as absentDays,
                    SUM(CASE WHEN attendanceStatus='leave' THEN 1 ELSE 0 END) as leaveDays,
                    SUM(CASE WHEN attendanceStatus='half_day' THEN 1 ELSE 0 END) as halfDays,
                    SUM(CASE WHEN isLate=1 THEN 1 ELSE 0 END) as lateDays,
                    SUM(COALESCE(workingHours, 0)) as totalHours
                FROM " . $DB->pre . "attendance
                WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=?";
    $att = $DB->dbRow();

    // Use leave count from leave_details (approved leaves) instead of attendance table
    $presentDays = intval($att['presentDays'] ?? 0);
    $totalLeaveDays = $leaveCount > 0 ? $leaveCount : intval($att['leaveDays'] ?? 0);

    // Calculate actual absent days (working days minus present, leaves, half days)
    $halfDays = intval($att['halfDays'] ?? 0);
    $calculatedAbsent = $workingDays - $presentDays - $totalLeaveDays - ($halfDays * 0.5);
    $absentDays = max(0, $calculatedAbsent);

    $summary = array(
        'P' => $presentDays,
        'A' => intval($absentDays),
        'L' => $totalLeaveDays,
        'HD' => $halfDays,
        'LT' => intval($att['lateDays'] ?? 0),
        'H' => $holidayCount,
        'WO' => $weeklyOffs
    );

    $payableDays = $summary['P'] + $summary['L'] + ($summary['HD'] * 0.5);

    return array(
        'err' => 0,
        'monthName' => $monthName,
        'workingDays' => $workingDays,
        'summary' => $summary,
        'totalHours' => round(floatval($att['totalHours'] ?? 0), 1),
        'payableDays' => $payableDays
    );
}

/**
 * Get Late/Early Report
 */
function getLateEarlyReportEmployee($userID, $startDate, $endDate, $monthName)
{
    global $DB;

    $DB->vals = array($userID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT a.*, ar.reason, ar.reviewStatus
                FROM " . $DB->pre . "attendance a
                LEFT JOIN " . $DB->pre . "attendance_remarks ar ON a.attendanceID = ar.attendanceID
                WHERE a.userID=? AND a.attendanceDate BETWEEN ? AND ? AND a.status=?
                  AND (a.isLate=1 OR a.isEarlyCheckout=1)
                ORDER BY a.attendanceDate DESC";
    $rows = $DB->dbRows();

    $records = [];
    foreach ($rows as $row) {
        $records[] = array(
            'date' => date('d M Y', strtotime($row['attendanceDate'])),
            'checkIn' => $row['checkIn'] ? date('h:i A', strtotime($row['checkIn'])) : '-',
            'checkOut' => $row['checkOut'] ? date('h:i A', strtotime($row['checkOut'])) : '-',
            'lateMinutes' => intval($row['lateMinutes'] ?? 0),
            'earlyMinutes' => intval($row['earlyMinutes'] ?? 0),
            'reason' => $row['reason'] ?? '',
            'status' => ucfirst($row['reviewStatus'] ?? 'pending')
        );
    }

    return array(
        'err' => 0,
        'monthName' => $monthName,
        'records' => $records
    );
}

/**
 * Get Detailed Attendance Report (Day, Date, Check-In, Check-Out, Working Hours)
 */
function getDetailedAttendanceReport($userID, $month, $year, $daysInMonth, $monthName)
{
    global $DB;

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));

    // Get user's Saturday off setting
    $DB->vals = array($userID);
    $DB->types = "i";
    $DB->sql = "SELECT COALESCE(isSaturdayOff, 0) as isSaturdayOff FROM " . $DB->pre . "x_admin_user WHERE userID=?";
    $userSettings = $DB->dbRow();
    $isSaturdayOff = intval($userSettings['isSaturdayOff'] ?? 0);

    // Get holidays
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT ahDate FROM " . $DB->pre . "attendance_holidays WHERE ahDate BETWEEN ? AND ? AND status=?";
    $holidayRows = $DB->dbRows();
    $holidays = array_column($holidayRows, 'ahDate');

    // Get all attendance records for the month with remarks
    $DB->vals = array($userID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT a.attendanceID, a.attendanceDate, a.attendanceStatus, a.isLate, a.checkIn, a.checkOut, a.workingHours, a.lateMinutes,
                       ar.reason as remark, ar.remarkType
                FROM " . $DB->pre . "attendance a
                LEFT JOIN " . $DB->pre . "attendance_remarks ar ON a.attendanceID = ar.attendanceID
                WHERE a.userID=? AND a.attendanceDate BETWEEN ? AND ? AND a.status=?
                ORDER BY a.attendanceDate ASC";
    $attRows = $DB->dbRows();

    $attLookup = [];
    foreach ($attRows as $att) {
        $attLookup[$att['attendanceDate']] = $att;
    }

    // Get approved leaves for this period (including countsAsPresent for On Duty)
    $DB->vals = array($userID, $startDate, $endDate, 'Approved');
    $DB->types = "isss";
    $DB->sql = "SELECT ld.leaveDate, lt.leaveTypeName, COALESCE(lt.countsAsPresent, 0) as countsAsPresent
                FROM `" . $DB->pre . "leave_details` ld
                INNER JOIN `" . $DB->pre . "leave` l ON ld.leaveID = l.leaveID
                LEFT JOIN `" . $DB->pre . "leave_type` lt ON l.leaveType = lt.leaveTypeID
                WHERE ld.userID = ? AND ld.leaveDate BETWEEN ? AND ?
                AND l.leaveStatus = ? AND l.status = 1 AND ld.status = 1";
    $leaveRows = $DB->dbRows();

    $leaveLookup = [];
    foreach ($leaveRows as $lv) {
        $leaveLookup[$lv['leaveDate']] = [
            'name' => $lv['leaveTypeName'] ?: 'Leave',
            'countsAsPresent' => $lv['countsAsPresent']
        ];
    }

    $records = [];
    $totalHours = 0;
    $presentDays = 0;
    $leaveDays = 0;
    $onDutyDays = 0;
    $absentDays = 0;
    $lateDays = 0;
    $halfDays = 0;
    $workingDays = 0;
    $sundays = 0;
    $saturdays = 0;
    $holidayCount = 0;

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dayOfWeek = date('w', strtotime($dateStr));
        $dayName = date('D', strtotime($dateStr));
        $isFuture = strtotime($dateStr) > time();

        $record = array(
            'dayName' => $dayName,
            'date' => date('d M Y', strtotime($dateStr)),
            'checkIn' => '-',
            'checkOut' => '-',
            'workingHours' => '-',
            'status' => '-',
            'statusCode' => '-'
        );

        if ($isFuture) {
            $record['status'] = '-';
            $record['statusCode'] = '-';
        } elseif ($dayOfWeek == 0) {
            // Sunday
            $record['status'] = 'Weekly Off';
            $record['statusCode'] = 'WO';
            $sundays++;
        } elseif ($dayOfWeek == 6 && $isSaturdayOff) {
            // Saturday (if Saturday off for this user)
            $record['status'] = 'Weekly Off';
            $record['statusCode'] = 'WO';
            $saturdays++;
        } elseif (in_array($dateStr, $holidays)) {
            $record['status'] = 'Holiday';
            $record['statusCode'] = 'H';
            $holidayCount++;
        } elseif (isset($leaveLookup[$dateStr])) {
            // Check for approved leave first - On Duty counts as present
            $leaveInfo = $leaveLookup[$dateStr];
            $record['status'] = $leaveInfo['name'];
            if ($leaveInfo['countsAsPresent']) {
                $record['statusCode'] = 'OD';
                $onDutyDays++;
                $presentDays++; // On Duty counts as present
            } else {
                $record['statusCode'] = 'L';
                $leaveDays++;
            }
        } elseif (isset($attLookup[$dateStr])) {
            $att = $attLookup[$dateStr];
            $record['checkIn'] = $att['checkIn'] ? date('h:i A', strtotime($att['checkIn'])) : '-';
            $record['checkOut'] = $att['checkOut'] ? date('h:i A', strtotime($att['checkOut'])) : '-';
            $record['remark'] = $att['remark'] ?? '';
            $record['lateMinutes'] = intval($att['lateMinutes'] ?? 0);

            $hours = floatval($att['workingHours'] ?? 0);
            // Calculate working hours on-the-fly if not stored but checkIn and checkOut exist
            if ($hours <= 0 && $att['checkIn'] && $att['checkOut']) {
                $hours = round((strtotime($att['checkOut']) - strtotime($att['checkIn'])) / 3600, 2);
            }
            if ($hours > 0) {
                $record['workingHours'] = number_format($hours, 1) . ' hrs';
                $totalHours += $hours;
            }

            switch ($att['attendanceStatus']) {
                case 'present':
                    $record['status'] = $att['isLate'] ? 'Late' : 'Present';
                    $record['statusCode'] = $att['isLate'] ? 'LT' : 'P';
                    $presentDays++;
                    if ($att['isLate']) {
                        $lateDays++;
                    }
                    break;
                case 'absent':
                    $record['status'] = 'Absent';
                    $record['statusCode'] = 'A';
                    $absentDays++;
                    break;
                case 'leave':
                    $record['status'] = 'Leave';
                    $record['statusCode'] = 'L';
                    $leaveDays++;
                    break;
                case 'half_day':
                    $record['status'] = 'Half Day';
                    $record['statusCode'] = 'HD';
                    $presentDays += 0.5;
                    $halfDays++;
                    break;
                default:
                    $record['status'] = 'Present';
                    $record['statusCode'] = 'P';
                    $presentDays++;
            }
        } else {
            // No attendance record and not a future date - mark as absent
            if (!$isFuture) {
                $absentDays++;
            }
            $record['status'] = 'Absent';
            $record['statusCode'] = 'A';
        }

        $records[] = $record;
    }

    // Calculate working days (total days - sundays - saturdays - holidays)
    $workingDays = $daysInMonth - $sundays - $saturdays - $holidayCount;

    $avgHours = $presentDays > 0 ? round($totalHours / $presentDays, 1) : 0;

    return array(
        'err' => 0,
        'monthName' => $monthName,
        'totalDays' => $daysInMonth,
        'workingDays' => $workingDays,
        'presentDays' => $presentDays,
        'absentDays' => $absentDays,
        'leaveDays' => $leaveDays,
        'halfDays' => $halfDays,
        'lateDays' => $lateDays,
        'onDutyDays' => $onDutyDays,
        'sundays' => $sundays,
        'saturdays' => $saturdays,
        'holidays' => $holidayCount,
        'totalHours' => round($totalHours, 1),
        'avgHours' => $avgHours,
        'records' => $records
    );
}

/**
 * Get Master Employee Report (All Employees - Consolidated)
 */
function getMasterEmployeeReport($reportType, $month, $year, $startDate, $endDate, $daysInMonth, $monthName)
{
    global $DB;

    // Get all active employees with their Saturday off setting
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT userID, employeeCode as empCode, COALESCE(NULLIF(displayName,''), userName) as empName, COALESCE(isSaturdayOff, 0) as isSaturdayOff
                FROM " . $DB->pre . "x_admin_user
                WHERE status=?
                ORDER BY empName";
    $employees = $DB->dbRows();

    // Get holidays
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT ahDate FROM " . $DB->pre . "attendance_holidays WHERE ahDate BETWEEN ? AND ? AND status=?";
    $holidayRows = $DB->dbRows();
    $holidays = array_column($holidayRows, 'ahDate');

    // Count Sundays and working days
    $sundays = 0;
    $currentDate = strtotime($startDate);
    while ($currentDate <= strtotime($endDate)) {
        if (date('w', $currentDate) == 0) $sundays++;
        $currentDate = strtotime('+1 day', $currentDate);
    }
    $workingDays = $daysInMonth - $sundays - count($holidays);

    // Get all attendance records for the month for all employees
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT a.*, u.employeeCode as empCode, COALESCE(NULLIF(u.displayName,''), u.userName) as empName
                FROM " . $DB->pre . "attendance a
                JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                WHERE a.attendanceDate BETWEEN ? AND ? AND a.status=? AND u.status=1
                ORDER BY u.displayName, a.attendanceDate";
    $allAttendance = $DB->dbRows();

    // Build lookup by userID and date
    $attLookup = [];
    foreach ($allAttendance as $att) {
        $attLookup[$att['userID']][$att['attendanceDate']] = $att;
    }

    // Get all approved leaves for the period - build lookup by userID and date (including countsAsPresent for On Duty)
    $DB->vals = array($startDate, $endDate, 'Approved', 1, 1);
    $DB->types = "sssii";
    $DB->sql = "SELECT ld.userID, ld.leaveDate, COALESCE(lt.countsAsPresent, 0) as countsAsPresent
                FROM `" . $DB->pre . "leave_details` ld
                INNER JOIN `" . $DB->pre . "leave` l ON ld.leaveID = l.leaveID
                LEFT JOIN `" . $DB->pre . "leave_type` lt ON l.leaveType = lt.leaveTypeID
                WHERE ld.leaveDate BETWEEN ? AND ?
                AND l.leaveStatus = ? AND l.status = ? AND ld.status = ?";
    $leaveRows = $DB->dbRows();

    $leaveLookup = [];
    foreach ($leaveRows as $lv) {
        // OD = On Duty (counts as present), L = Leave
        $leaveLookup[$lv['userID']][$lv['leaveDate']] = $lv['countsAsPresent'] ? 'OD' : 'L';
    }

    // Based on report type, generate the appropriate master report
    switch ($reportType) {
        case 'monthly':
            return getMasterMonthlyReport($employees, $attLookup, $leaveLookup, $holidays, $month, $year, $daysInMonth, $monthName);
        case 'summary':
            return getMasterSummaryReport($employees, $attLookup, $leaveLookup, $holidays, $workingDays, $sundays, $daysInMonth, $monthName);
        case 'detailed':
            return getMasterDetailedReport($employees, $attLookup, $leaveLookup, $holidays, $month, $year, $daysInMonth, $monthName);
        default:
            // For late_early and leave, show a simple message that these are individual reports
            return array('err' => 1, 'message' => 'This report type is only available for individual employees. Please select a specific employee.');
    }
}

/**
 * Get Master Monthly Report - All employees' attendance in muster roll format
 */
function getMasterMonthlyReport($employees, $attLookup, $leaveLookup, $holidays, $month, $year, $daysInMonth, $monthName)
{
    $data = [];
    $grandSummary = ['P' => 0, 'A' => 0, 'L' => 0, 'LT' => 0, 'H' => 0, 'WO' => 0, 'HD' => 0, 'OD' => 0];

    foreach ($employees as $emp) {
        $days = [];
        $summary = ['P' => 0, 'A' => 0, 'L' => 0, 'LT' => 0, 'H' => 0, 'WO' => 0, 'HD' => 0, 'OD' => 0];
        $isSaturdayOff = intval($emp['isSaturdayOff'] ?? 0);

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dayOfWeek = date('w', strtotime($dateStr));
            $isFuture = strtotime($dateStr) > time();

            if ($isFuture) {
                $days[$d] = '-';
            } elseif ($dayOfWeek == 0 || ($dayOfWeek == 6 && $isSaturdayOff)) {
                // Sunday or Saturday (if Saturday off for this employee)
                $days[$d] = 'WO';
                $summary['WO']++;
            } elseif (in_array($dateStr, $holidays)) {
                $days[$d] = 'H';
                $summary['H']++;
            } elseif (isset($leaveLookup[$emp['userID']][$dateStr])) {
                // Check approved leave first - OD (On Duty) counts as present
                $leaveType = $leaveLookup[$emp['userID']][$dateStr];
                $days[$d] = $leaveType;
                if ($leaveType === 'OD') {
                    $summary['OD']++;
                    $summary['P']++; // On Duty counts as present
                } else {
                    $summary['L']++;
                }
            } elseif (isset($attLookup[$emp['userID']][$dateStr])) {
                $att = $attLookup[$emp['userID']][$dateStr];
                switch ($att['attendanceStatus']) {
                    case 'present':
                        $days[$d] = $att['isLate'] ? 'LT' : 'P';
                        $summary['P']++;
                        if ($att['isLate']) $summary['LT']++;
                        break;
                    case 'absent':
                        $days[$d] = 'A';
                        $summary['A']++;
                        break;
                    case 'leave':
                        $days[$d] = 'L';
                        $summary['L']++;
                        break;
                    case 'half_day':
                        $days[$d] = 'HD';
                        $summary['HD']++;
                        break;
                    default:
                        $days[$d] = 'P';
                        $summary['P']++;
                }
            } else {
                $days[$d] = 'A';
                $summary['A']++;
            }
        }

        // Add to grand summary
        foreach ($summary as $key => $val) {
            $grandSummary[$key] += $val;
        }

        $data[] = array(
            'empCode' => $emp['empCode'] ?: 'EMP' . str_pad($emp['userID'], 4, '0', STR_PAD_LEFT),
            'empName' => $emp['empName'],
            'days' => $days,
            'summary' => $summary
        );
    }

    return array(
        'err' => 0,
        'monthName' => $monthName,
        'daysInMonth' => $daysInMonth,
        'data' => $data,
        'summary' => $grandSummary,
        'isMaster' => true
    );
}

/**
 * Get Master Summary Report - All employees' summary
 */
function getMasterSummaryReport($employees, $attLookup, $leaveLookup, $holidays, $workingDays, $sundays, $daysInMonth, $monthName)
{
    $data = [];
    $grandTotals = ['P' => 0, 'A' => 0, 'L' => 0, 'LT' => 0, 'HD' => 0, 'totalHours' => 0];

    foreach ($employees as $emp) {
        $summary = ['P' => 0, 'A' => 0, 'L' => 0, 'LT' => 0, 'HD' => 0];
        $totalHours = 0;
        $isSaturdayOff = intval($emp['isSaturdayOff'] ?? 0);

        // Count approved leaves from leave_details table (excluding On Duty days marked as OD)
        $leaveCount = 0;
        foreach ($leaveLookup[$emp['userID']] ?? [] as $date => $leaveType) {
            if ($leaveType !== 'OD') {
                $leaveCount++;
            }
        }
        $summary['L'] = $leaveCount;

        foreach ($attLookup[$emp['userID']] ?? [] as $date => $att) {
            // Skip if this date is already counted as leave
            if (isset($leaveLookup[$emp['userID']][$date])) {
                continue;
            }
            // Skip Saturday if user has Saturday off
            $dow = date('w', strtotime($date));
            if ($dow == 6 && $isSaturdayOff) {
                continue;
            }
            $totalHours += floatval($att['workingHours'] ?? 0);
            switch ($att['attendanceStatus']) {
                case 'present':
                    $summary['P']++;
                    if ($att['isLate']) $summary['LT']++;
                    break;
                case 'absent':
                    $summary['A']++;
                    break;
                case 'leave':
                    $summary['L']++;
                    break;
                case 'half_day':
                    $summary['HD']++;
                    break;
            }
        }

        $payableDays = $summary['P'] + $summary['L'] + ($summary['HD'] * 0.5);

        // Add to grand totals
        foreach ($summary as $key => $val) {
            $grandTotals[$key] += $val;
        }
        $grandTotals['totalHours'] += $totalHours;

        $data[] = array(
            'empCode' => $emp['empCode'] ?: 'EMP' . str_pad($emp['userID'], 4, '0', STR_PAD_LEFT),
            'empName' => $emp['empName'],
            'present' => $summary['P'],
            'absent' => $summary['A'],
            'leave' => $summary['L'],
            'halfDay' => $summary['HD'],
            'late' => $summary['LT'],
            'totalHours' => round($totalHours, 1),
            'payableDays' => $payableDays
        );
    }

    return array(
        'err' => 0,
        'monthName' => $monthName,
        'workingDays' => $workingDays,
        'totalEmployees' => count($employees),
        'data' => $data,
        'grandTotals' => $grandTotals,
        'summary' => array(
            'P' => $grandTotals['P'],
            'A' => $grandTotals['A'],
            'L' => $grandTotals['L'],
            'LT' => $grandTotals['LT'],
            'HD' => $grandTotals['HD'],
            'H' => count($holidays),
            'WO' => $sundays
        ),
        'totalHours' => round($grandTotals['totalHours'], 1),
        'isMaster' => true
    );
}

/**
 * Get Master Detailed Report - All employees' detailed attendance
 */
function getMasterDetailedReport($employees, $attLookup, $leaveLookup, $holidays, $month, $year, $daysInMonth, $monthName)
{
    $records = [];
    $totalHours = 0;
    $totalPresent = 0;
    $totalAbsent = 0;
    $totalLeave = 0;

    foreach ($employees as $emp) {
        $isSaturdayOff = intval($emp['isSaturdayOff'] ?? 0);

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dayOfWeek = date('w', strtotime($dateStr));
            $dayName = date('D', strtotime($dateStr));
            $isFuture = strtotime($dateStr) > time();

            $record = array(
                'empCode' => $emp['empCode'] ?: 'EMP' . str_pad($emp['userID'], 4, '0', STR_PAD_LEFT),
                'empName' => $emp['empName'],
                'dayName' => $dayName,
                'date' => date('d M Y', strtotime($dateStr)),
                'checkIn' => '-',
                'checkOut' => '-',
                'workingHours' => '-',
                'status' => '-',
                'statusCode' => '-'
            );

            if ($isFuture) {
                continue; // Skip future dates
            } elseif ($dayOfWeek == 0 || ($dayOfWeek == 6 && $isSaturdayOff)) {
                // Sunday or Saturday (if Saturday off for this employee)
                $record['status'] = 'Weekly Off';
                $record['statusCode'] = 'WO';
            } elseif (in_array($dateStr, $holidays)) {
                $record['status'] = 'Holiday';
                $record['statusCode'] = 'H';
            } elseif (isset($leaveLookup[$emp['userID']][$dateStr])) {
                // Check approved leave first - OD (On Duty) counts as present
                $leaveType = $leaveLookup[$emp['userID']][$dateStr];
                if ($leaveType === 'OD') {
                    $record['status'] = 'On Duty';
                    $record['statusCode'] = 'OD';
                    $totalPresent++; // On Duty counts as present
                } else {
                    $record['status'] = 'Leave';
                    $record['statusCode'] = 'L';
                    $totalLeave++;
                }
            } elseif (isset($attLookup[$emp['userID']][$dateStr])) {
                $att = $attLookup[$emp['userID']][$dateStr];
                $record['checkIn'] = $att['checkIn'] ? date('h:i A', strtotime($att['checkIn'])) : '-';
                $record['checkOut'] = $att['checkOut'] ? date('h:i A', strtotime($att['checkOut'])) : '-';

                $hours = floatval($att['workingHours'] ?? 0);
                // Calculate working hours on-the-fly if not stored but checkIn and checkOut exist
                if ($hours <= 0 && $att['checkIn'] && $att['checkOut']) {
                    $hours = round((strtotime($att['checkOut']) - strtotime($att['checkIn'])) / 3600, 2);
                }
                if ($hours > 0) {
                    $record['workingHours'] = number_format($hours, 1) . ' hrs';
                    $totalHours += $hours;
                }

                switch ($att['attendanceStatus']) {
                    case 'present':
                        $record['status'] = $att['isLate'] ? 'Late' : 'Present';
                        $record['statusCode'] = $att['isLate'] ? 'LT' : 'P';
                        $totalPresent++;
                        break;
                    case 'absent':
                        $record['status'] = 'Absent';
                        $record['statusCode'] = 'A';
                        $totalAbsent++;
                        break;
                    case 'leave':
                        $record['status'] = 'Leave';
                        $record['statusCode'] = 'L';
                        $totalLeave++;
                        break;
                    case 'half_day':
                        $record['status'] = 'Half Day';
                        $record['statusCode'] = 'HD';
                        $totalPresent += 0.5;
                        break;
                    default:
                        $record['status'] = 'Present';
                        $record['statusCode'] = 'P';
                        $totalPresent++;
                }
            } else {
                $record['status'] = 'Absent';
                $record['statusCode'] = 'A';
                $totalAbsent++;
            }

            $records[] = $record;
        }
    }

    $employeeCount = count($employees);

    return array(
        'err' => 0,
        'monthName' => $monthName,
        'totalEmployees' => $employeeCount,
        'totalDays' => $daysInMonth,
        'presentDays' => $totalPresent,
        'absentDays' => $totalAbsent,
        'leaveDays' => $totalLeave,
        'totalHours' => round($totalHours, 1),
        'avgHours' => $totalPresent > 0 ? round($totalHours / $totalPresent, 1) : 0,
        'records' => $records,
        'isMaster' => true
    );
}

/**
 * Get Leave History Report
 */
function getLeaveHistoryReport($userID, $startDate, $endDate, $monthName)
{
    global $DB;

    // Use existing mx_leave table with mx_leave_type
    $DB->vals = array($userID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT l.*, lt.leaveTypeName
                FROM " . $DB->pre . "leave l
                LEFT JOIN " . $DB->pre . "leave_type lt ON l.leaveType = lt.leaveTypeID
                WHERE l.userID=? AND l.fromDate >= ? AND l.fromDate <= ? AND l.status=?
                ORDER BY l.fromDate DESC";
    $rows = $DB->dbRows();

    $leaves = [];
    foreach ($rows as $row) {
        // Calculate days between fromDate and toDate
        $from = strtotime($row['fromDate']);
        $to = strtotime($row['toDate']);
        $days = $from && $to ? round(($to - $from) / 86400) + 1 : 1;

        // Map leave status
        $status = strtolower($row['leaveStatus'] ?? 'pending');
        if (strpos($status, 'approved') !== false) {
            $status = 'approved';
        } elseif (strpos($status, 'disapproved') !== false || strpos($status, 'cancel') !== false) {
            $status = 'rejected';
        } else {
            $status = 'pending';
        }

        $leaves[] = array(
            'leaveType' => $row['leaveTypeName'] ?? 'General',
            'fromDate' => $row['fromDate'] ? date('d M Y', strtotime($row['fromDate'])) : '-',
            'toDate' => $row['toDate'] ? date('d M Y', strtotime($row['toDate'])) : '-',
            'days' => $days,
            'reason' => $row['reason'] ?? '',
            'status' => $status
        );
    }

    return array(
        'err' => 0,
        'monthName' => $monthName,
        'leaves' => $leaves
    );
}

/**
 * Download Employee Report as Excel/PDF
 */
function downloadEmployeeReport()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        die('Not authorized');
    }

    $currentUserID = $_SESSION['HRMS_USER_ID'];
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = isHRMasterAdmin();
    $targetUserIDRaw = $_GET['targetUserID'] ?? '';

    $format = $_GET['format'] ?? 'excel';
    $reportType = $_GET['type'] ?? 'monthly';
    $month = intval($_GET['month'] ?? date('n'));
    $year = intval($_GET['year'] ?? date('Y'));
    $fyYear = isset($_GET['fyYear']) ? intval($_GET['fyYear']) : null;

    // For leave reports, use Financial Year dates
    if ($reportType === 'leave' && $fyYear) {
        $startDate = sprintf('%04d-04-01', $fyYear); // April 1st of FY start year
        $endDate = sprintf('%04d-03-31', $fyYear + 1); // March 31st of FY end year
        $daysInMonth = 0; // Not applicable for FY
        $monthName = 'FY ' . $fyYear . '-' . ($fyYear + 1);
    } else {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $daysInMonth = date('t', strtotime($startDate));
        $monthName = date('F Y', strtotime($startDate));
    }

    // Handle "all" employees (Master Report)
    if ($targetUserIDRaw === 'all') {
        if (!$isHRAdmin) {
            die('Not authorized to download all employees report');
        }
        $data = getMasterEmployeeReport($reportType, $month, $year, $startDate, $endDate, $daysInMonth, $monthName);
        $filename = 'all_employees_' . $reportType . '_' . date('Y-m', strtotime($startDate));
        $userName = 'All Employees';

        if ($format === 'excel') {
            generateMasterReportExcel($reportType, $data, $monthName, $daysInMonth, $filename);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>All Employees Report</title>';
            echo '<style>body{font-family:Arial,sans-serif;font-size:12px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:6px 8px;text-align:left;} th{background:#e2e8f0;} h1{font-size:18px;margin:0;} .subtitle{color:#666;margin-bottom:20px;} .print-btn{margin:20px 0;} @media print{.print-btn{display:none;}}</style>';
            echo '</head><body>';
            echo '<button class="print-btn" onclick="window.print()">Print / Save as PDF</button>';
            echo generateMasterReportHTML($reportType, $data, $monthName, $daysInMonth);
            echo '</body></html>';
        }
        exit;
    }

    $targetUserID = intval($targetUserIDRaw);

    // Determine which user's report to download
    if ($targetUserID && $targetUserID != $currentUserID) {
        // Trying to download another user's report - check authorization
        if (!$isManager && !$isHRAdmin) {
            die('Not authorized to download other employees\' reports');
        }

        // If manager (not HR Admin), verify the target is in their team
        if (!$isHRAdmin) {
            $DB->vals = array($targetUserID, 1, $currentUserID);
            $DB->types = "iii";
            $DB->sql = "SELECT userID, userName, displayName FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=? AND managerID=?";
            $targetUser = $DB->dbRow();
            if ($DB->numRows == 0) {
                die('This employee is not in your team');
            }
            $userID = $targetUserID;
            $userName = $targetUser['displayName'] ?: $targetUser['userName'];
        } else {
            // HR Admin can download any employee's report
            $DB->vals = array($targetUserID, 1);
            $DB->types = "ii";
            $DB->sql = "SELECT userID, userName, displayName FROM `" . $DB->pre . "x_admin_user` WHERE userID=? AND status=?";
            $targetUser = $DB->dbRow();
            if ($DB->numRows == 0) {
                die('Employee not found');
            }
            $userID = $targetUserID;
            $userName = $targetUser['displayName'] ?: $targetUser['userName'];
        }
    } else {
        $userID = $currentUserID;
        $userName = $_SESSION['HRMS_USER_NAME'] ?? 'Employee';
    }

    // Get report data
    switch ($reportType) {
        case 'monthly':
            $data = getMonthlyAttendanceReport($userID, $month, $year, $daysInMonth, $monthName);
            break;
        case 'summary':
            $data = getSummaryReport($userID, $month, $year, $startDate, $endDate, $daysInMonth, $monthName);
            break;
        case 'late_early':
            $data = getLateEarlyReportEmployee($userID, $startDate, $endDate, $monthName);
            break;
        case 'leave':
            $data = getLeaveHistoryReport($userID, $startDate, $endDate, $monthName);
            break;
        case 'detailed':
            $data = getDetailedAttendanceReport($userID, $month, $year, $daysInMonth, $monthName);
            break;
        default:
            die('Invalid report type');
    }

    // Include employee name in filename when downloading for team member
    $fileNamePrefix = ($targetUserID && $targetUserID != $currentUserID) ? preg_replace('/[^a-zA-Z0-9]/', '_', $userName) . '_' : '';
    $filename = $fileNamePrefix . 'attendance_' . $reportType . '_' . date('Y-m', strtotime($startDate));

    if ($format === 'excel') {
        generateEmployeeReportExcel($reportType, $data, $userName, $monthName, $daysInMonth, $filename);
    } else {
        // PDF - Beautiful HTML report with print button
        header('Content-Type: text/html; charset=utf-8');
        $pdfTitles = array('monthly' => 'Monthly Attendance Report', 'summary' => 'Attendance Summary Report', 'late_early' => 'Late/Early Report', 'leave' => 'Leave Report', 'detailed' => 'Detailed Attendance Report');
        $pdfTitle = $pdfTitles[$reportType] ?? 'Report';
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . $pdfTitle . ' - BES HRMS</title>';
        echo '<style>.print-btn{position:fixed;top:20px;right:20px;z-index:1000;background:linear-gradient(135deg,#2563eb,#3b82f6);color:white;border:none;padding:14px 28px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 14px rgba(37,99,235,0.4);transition:all 0.2s;} .print-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(37,99,235,0.5);} @media print{.print-btn{display:none !important;}}</style>';
        echo '</head><body style="margin:0;padding:20px;background:#f1f5f9;">';
        echo '<button class="print-btn" onclick="window.print()">🖨️ Print / Save PDF</button>';
        echo generateEmployeeReportHTML($reportType, $data, $userName, $monthName, $daysInMonth);
        echo '</body></html>';
    }
    exit;
}

/**
 * Generate Excel Report using PhpSpreadsheet
 */
function generateEmployeeReportExcel($reportType, $data, $userName, $monthName, $daysInMonth, $filename)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Report titles
    $reportTitles = [
        'monthly' => 'Monthly Attendance',
        'summary' => 'Summary Report',
        'late_early' => 'Late/Early Report',
        'leave' => 'Leave History',
        'detailed' => 'Detailed Report'
    ];
    $sheet->setTitle(substr($reportTitles[$reportType] ?? 'Report', 0, 31));

    // Common styles
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $statusColors = [
        'P' => 'DCFCE7', 'Present' => 'DCFCE7',
        'LT' => 'FEF3C7', 'Late' => 'FEF3C7',
        'A' => 'FEE2E2', 'Absent' => 'FEE2E2',
        'L' => 'DBEAFE', 'Leave' => 'DBEAFE',
        'HD' => 'FAE8FF', 'Half Day' => 'FAE8FF',
        'WO' => 'F1F5F9', 'Weekly Off' => 'F1F5F9',
        'H' => 'F1F5F9', 'Holiday' => 'F1F5F9'
    ];

    // Title row
    $sheet->setCellValue('A1', ($reportTitles[$reportType] ?? 'Report') . ' - ' . $userName);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    $sheet->setCellValue('A2', $monthName);
    $sheet->getStyle('A2')->getFont()->setItalic(true);

    $row = 4;

    switch ($reportType) {
        case 'monthly':
            // Monthly Muster Roll
            $headers = ['Date'];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $headers[] = $d;
            }
            $headers[] = 'P';
            $headers[] = 'A';
            $headers[] = 'L';

            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $lastCol = chr(ord('A') + count($headers) - 1);
            if (count($headers) > 26) {
                $lastCol = 'A' . chr(ord('A') + count($headers) - 27);
            }
            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($headerStyle);
            $row++;

            // Data row
            $sheet->setCellValue('A' . $row, $userName);
            $col = 'B';
            $days = $data['days'] ?? [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $status = $days[$d] ?? '-';
                $sheet->setCellValue($col . $row, $status);
                if (isset($statusColors[$status])) {
                    $sheet->getStyle($col . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($statusColors[$status]);
                }
                $col++;
            }
            $summary = $data['summary'] ?? [];
            $sheet->setCellValue($col . $row, $summary['P'] ?? 0);
            $col++;
            $sheet->setCellValue($col . $row, $summary['A'] ?? 0);
            $col++;
            $sheet->setCellValue($col . $row, $summary['L'] ?? 0);
            break;

        case 'summary':
            $headers = ['Metric', 'Value'];
            $sheet->setCellValue('A' . $row, $headers[0]);
            $sheet->setCellValue('B' . $row, $headers[1]);
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($headerStyle);
            $row++;

            $summaryData = [
                ['Working Days', $data['workingDays'] ?? 0],
                ['Present Days', $data['presentDays'] ?? 0],
                ['Absent Days', $data['absentDays'] ?? 0],
                ['Leave Days', $data['leaveDays'] ?? 0],
                ['Half Days', $data['halfDays'] ?? 0],
                ['Late Days', $data['lateDays'] ?? 0],
                ['Early Checkout', $data['earlyDays'] ?? 0],
                ['Total Hours', $data['totalHours'] ?? 0],
                ['Payable Days', $data['payableDays'] ?? 0]
            ];

            foreach ($summaryData as $item) {
                $sheet->setCellValue('A' . $row, $item[0]);
                $sheet->setCellValue('B' . $row, $item[1]);
                $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }
            break;

        case 'late_early':
            $headers = ['Date', 'Day', 'Check In', 'Scheduled', 'Late By', 'Check Out', 'Scheduled', 'Early By', 'Remark'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($headerStyle);
            $row++;

            foreach ($data['records'] ?? [] as $record) {
                $sheet->setCellValue('A' . $row, $record['date'] ?? '');
                $sheet->setCellValue('B' . $row, $record['dayName'] ?? '');
                $sheet->setCellValue('C' . $row, $record['checkIn'] ?? '-');
                $sheet->setCellValue('D' . $row, $record['scheduledIn'] ?? '-');
                $sheet->setCellValue('E' . $row, $record['lateBy'] ?? '-');
                $sheet->setCellValue('F' . $row, $record['checkOut'] ?? '-');
                $sheet->setCellValue('G' . $row, $record['scheduledOut'] ?? '-');
                $sheet->setCellValue('H' . $row, $record['earlyBy'] ?? '-');
                $sheet->setCellValue('I' . $row, $record['remark'] ?? '');
                $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }
            break;

        case 'leave':
            $headers = ['Leave Type', 'From', 'To', 'Days', 'Status', 'Applied On', 'Reason'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($headerStyle);
            $row++;

            foreach ($data['records'] ?? [] as $record) {
                $sheet->setCellValue('A' . $row, $record['leaveType'] ?? '');
                $sheet->setCellValue('B' . $row, $record['fromDate'] ?? '');
                $sheet->setCellValue('C' . $row, $record['toDate'] ?? '');
                $sheet->setCellValue('D' . $row, $record['days'] ?? '');
                $sheet->setCellValue('E' . $row, $record['status'] ?? '');
                $sheet->setCellValue('F' . $row, $record['appliedOn'] ?? '');
                $sheet->setCellValue('G' . $row, $record['reason'] ?? '');
                $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }

            // Leave balance summary
            $row += 2;
            $sheet->setCellValue('A' . $row, 'Leave Balance Summary');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            $sheet->setCellValue('A' . $row, 'Leave Type');
            $sheet->setCellValue('B' . $row, 'Entitled');
            $sheet->setCellValue('C' . $row, 'Used');
            $sheet->setCellValue('D' . $row, 'Balance');
            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($headerStyle);
            $row++;

            foreach ($data['balance'] ?? [] as $bal) {
                $sheet->setCellValue('A' . $row, $bal['leaveType'] ?? '');
                $sheet->setCellValue('B' . $row, $bal['entitled'] ?? 0);
                $sheet->setCellValue('C' . $row, $bal['used'] ?? 0);
                $sheet->setCellValue('D' . $row, $bal['balance'] ?? 0);
                $sheet->getStyle('A' . $row . ':D' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }
            break;

        case 'detailed':
            $headers = ['Day', 'Date', 'Check In', 'Check Out', 'Hours', 'Status', 'Remark'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($headerStyle);
            $row++;

            foreach ($data['records'] ?? [] as $record) {
                $sheet->setCellValue('A' . $row, $record['dayName'] ?? '');
                $sheet->setCellValue('B' . $row, $record['date'] ?? '');
                $sheet->setCellValue('C' . $row, $record['checkIn'] ?? '-');
                $sheet->setCellValue('D' . $row, $record['checkOut'] ?? '-');
                $sheet->setCellValue('E' . $row, $record['workingHours'] ?? '-');
                $sheet->setCellValue('F' . $row, $record['status'] ?? '');
                $sheet->setCellValue('G' . $row, $record['remark'] ?? '');

                $statusCode = $record['statusCode'] ?? '';
                if (isset($statusColors[$statusCode])) {
                    $sheet->getStyle('A' . $row . ':G' . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($statusColors[$statusCode]);
                }
                $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }

            // Summary
            $row += 2;
            $sheet->setCellValue('A' . $row, 'ATTENDANCE SUMMARY');
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($headerStyle);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;

            $summaryData = [
                ['Total Days in Month', $data['totalDays'] ?? 0],
                ['Working Days (excl. Sundays/Holidays)', $data['workingDays'] ?? 0],
                ['', ''], // Empty row for spacing
                ['Present Days', $data['presentDays'] ?? 0],
                ['Absent Days', $data['absentDays'] ?? 0],
                ['Leave Days', $data['leaveDays'] ?? 0],
                ['Half Days', $data['halfDays'] ?? 0],
                ['Late Days', $data['lateDays'] ?? 0],
                ['On Duty Days', $data['onDutyDays'] ?? 0],
                ['', ''], // Empty row for spacing
                ['Sundays', $data['sundays'] ?? 0],
                ['Saturdays Off', $data['saturdays'] ?? 0],
                ['Holidays', $data['holidays'] ?? 0],
                ['', ''], // Empty row for spacing
                ['Total Hours Worked', $data['totalHours'] ?? 0],
                ['Average Hours/Day', $data['avgHours'] ?? 0]
            ];

            foreach ($summaryData as $item) {
                if ($item[0] === '' && $item[1] === '') {
                    // Empty row - skip border
                    $row++;
                    continue;
                }
                $sheet->setCellValue('A' . $row, $item[0]);
                $sheet->setCellValue('B' . $row, $item[1]);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);

                // Highlight important rows
                if (in_array($item[0], ['Working Days (excl. Sundays/Holidays)', 'Present Days', 'Absent Days'])) {
                    $sheet->getStyle('A' . $row . ':B' . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E0F2FE');
                }

                $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }
            break;
    }

    // Auto-size columns
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Generated timestamp
    $row += 2;
    $sheet->setCellValue('A' . $row, 'Generated on ' . date('d M Y h:i A'));
    $sheet->getStyle('A' . $row)->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('666666'));

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Generate Master Report Excel using PhpSpreadsheet
 */
function generateMasterReportExcel($reportType, $data, $monthName, $daysInMonth, $filename)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $reportTitles = [
        'monthly' => 'Monthly Muster Roll',
        'summary' => 'Attendance Summary',
        'late_early' => 'Late/Early Report',
        'leave' => 'Leave History',
        'detailed' => 'Detailed Report'
    ];
    $sheet->setTitle(substr($reportTitles[$reportType] ?? 'Master Report', 0, 31));

    // Common styles
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $statusColors = [
        'P' => 'DCFCE7', 'Present' => 'DCFCE7',
        'LT' => 'FEF3C7', 'Late' => 'FEF3C7',
        'A' => 'FEE2E2', 'Absent' => 'FEE2E2',
        'L' => 'DBEAFE', 'Leave' => 'DBEAFE',
        'HD' => 'FAE8FF', 'Half Day' => 'FAE8FF',
        'WO' => 'F1F5F9', 'Weekly Off' => 'F1F5F9',
        'H' => 'F1F5F9', 'Holiday' => 'F1F5F9'
    ];

    $totalEmployees = $data['totalEmployees'] ?? count($data['data'] ?? []);

    // Title rows
    $sheet->setCellValue('A1', 'Bombay Engineering Syndicate - Master HRMS Report');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    $sheet->setCellValue('A2', ($reportTitles[$reportType] ?? 'Report') . ' - ' . $monthName);
    $sheet->getStyle('A2')->getFont()->setItalic(true);

    $sheet->setCellValue('A3', 'Total Employees: ' . $totalEmployees);

    $row = 5;

    switch ($reportType) {
        case 'monthly':
            // Muster Roll Headers
            $headers = ['Employee'];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $headers[] = $d;
            }
            $headers[] = 'P';
            $headers[] = 'A';
            $headers[] = 'L';

            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $lastColIndex = count($headers) - 1;
            $lastCol = $lastColIndex < 26 ? chr(ord('A') + $lastColIndex) : 'A' . chr(ord('A') + $lastColIndex - 26);
            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($headerStyle);
            $row++;

            foreach ($data['data'] ?? [] as $emp) {
                $sheet->setCellValue('A' . $row, $emp['empName'] ?? '');
                $col = 'B';
                $days = $emp['days'] ?? [];
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $status = $days[$d] ?? '-';
                    $sheet->setCellValue($col . $row, $status);
                    if (isset($statusColors[$status])) {
                        $sheet->getStyle($col . $row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB($statusColors[$status]);
                    }
                    $col++;
                }
                $summary = $emp['summary'] ?? [];
                $sheet->setCellValue($col . $row, $summary['P'] ?? 0);
                $col++;
                $sheet->setCellValue($col . $row, $summary['A'] ?? 0);
                $col++;
                $sheet->setCellValue($col . $row, $summary['L'] ?? 0);
                $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }
            break;

        case 'summary':
            $headers = ['Employee', 'Present', 'Absent', 'Leave', 'Half Day', 'Late', 'Hours', 'Payable'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($headerStyle);
            $row++;

            foreach ($data['data'] ?? [] as $emp) {
                $sheet->setCellValue('A' . $row, $emp['empName'] ?? '');
                $sheet->setCellValue('B' . $row, $emp['present'] ?? 0);
                $sheet->setCellValue('C' . $row, $emp['absent'] ?? 0);
                $sheet->setCellValue('D' . $row, $emp['leave'] ?? 0);
                $sheet->setCellValue('E' . $row, $emp['halfDay'] ?? 0);
                $sheet->setCellValue('F' . $row, $emp['late'] ?? 0);
                $sheet->setCellValue('G' . $row, $emp['totalHours'] ?? 0);
                $sheet->setCellValue('H' . $row, $emp['payableDays'] ?? 0);
                $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }

            // Grand totals
            $grandTotals = $data['grandTotals'] ?? [];
            $sheet->setCellValue('A' . $row, 'GRAND TOTAL');
            $sheet->setCellValue('B' . $row, $grandTotals['P'] ?? 0);
            $sheet->setCellValue('C' . $row, $grandTotals['A'] ?? 0);
            $sheet->setCellValue('D' . $row, $grandTotals['L'] ?? 0);
            $sheet->setCellValue('E' . $row, $grandTotals['HD'] ?? 0);
            $sheet->setCellValue('F' . $row, $grandTotals['LT'] ?? 0);
            $sheet->setCellValue('G' . $row, $grandTotals['totalHours'] ?? 0);
            $sheet->setCellValue('H' . $row, '-');
            $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':H' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DBEAFE');
            $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            break;

        case 'detailed':
            $headers = ['Employee', 'Day', 'Date', 'Check In', 'Check Out', 'Hours', 'Status', 'Remark'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($headerStyle);
            $row++;

            foreach ($data['records'] ?? [] as $record) {
                $sheet->setCellValue('A' . $row, $record['empName'] ?? '');
                $sheet->setCellValue('B' . $row, $record['dayName'] ?? '');
                $sheet->setCellValue('C' . $row, $record['date'] ?? '');
                $sheet->setCellValue('D' . $row, $record['checkIn'] ?? '-');
                $sheet->setCellValue('E' . $row, $record['checkOut'] ?? '-');
                $sheet->setCellValue('F' . $row, $record['workingHours'] ?? '-');
                $sheet->setCellValue('G' . $row, $record['status'] ?? '');
                $sheet->setCellValue('H' . $row, $record['remark'] ?? '');

                $statusCode = $record['statusCode'] ?? '';
                if (isset($statusColors[$statusCode])) {
                    $sheet->getStyle('A' . $row . ':H' . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($statusColors[$statusCode]);
                }
                $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }

            // Summary
            $row += 2;
            $sheet->setCellValue('A' . $row, 'Summary');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            $sheet->setCellValue('A' . $row, 'Metric');
            $sheet->setCellValue('B' . $row, 'Value');
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($headerStyle);
            $row++;

            $summaryData = [
                ['Employees', $totalEmployees],
                ['Total Present', $data['presentDays'] ?? 0],
                ['Total Absent', $data['absentDays'] ?? 0],
                ['Total Leave', $data['leaveDays'] ?? 0],
                ['Total Hours', $data['totalHours'] ?? 0]
            ];
            foreach ($summaryData as $item) {
                $sheet->setCellValue('A' . $row, $item[0]);
                $sheet->setCellValue('B' . $row, $item[1]);
                $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }
            break;

        default:
            $sheet->setCellValue('A' . $row, 'This report type is not supported for master Excel export.');
    }

    // Auto-size columns (up to column J)
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Generated timestamp
    $row += 2;
    $sheet->setCellValue('A' . $row, 'Generated on ' . date('d M Y h:i A'));
    $sheet->getStyle('A' . $row)->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('666666'));

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Generate HTML for Employee Report - Beautiful PDF Template
 */
function generateEmployeeReportHTML($reportType, $data, $userName, $monthName, $daysInMonth)
{
    // Set report title based on type
    $reportTitles = array(
        'monthly' => 'Monthly Attendance Report',
        'summary' => 'Attendance Summary Report',
        'late_early' => 'Late / Early Arrival Report',
        'leave' => 'Leave History Report',
        'detailed' => 'Detailed Attendance Report'
    );
    $reportTitle = $reportTitles[$reportType] ?? 'Attendance Report';
    $logoUrl = SITEURL . '/xsite/images/logo-icon.png';

    // Beautiful PDF Template
    $html = '
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap");

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 11px;
            color: #1f2937;
            background: #fff;
            line-height: 1.5;
        }

        .report-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0;
        }

        /* Header */
        .report-header {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
            color: white;
            padding: 24px 32px;
            position: relative;
            overflow: hidden;
        }

        .report-header::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .report-header::after {
            content: "";
            position: absolute;
            bottom: -30%;
            right: 20%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .company-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .company-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 10px;
            padding: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .company-details h1 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
            margin-bottom: 2px;
        }

        .company-details .tagline {
            font-size: 10px;
            opacity: 0.85;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .report-meta {
            text-align: right;
        }

        .report-type-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .report-date {
            font-size: 11px;
            opacity: 0.9;
        }

        /* Employee Info Bar */
        .employee-bar {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .employee-name {
            font-size: 16px;
            font-weight: 700;
            color: #1e40af;
        }

        .period-badge {
            background: linear-gradient(135deg, #dbeafe, #eff6ff);
            border: 1px solid #bfdbfe;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #1e40af;
        }

        /* Content Area */
        .report-content {
            padding: 24px 32px;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .summary-card.present::before { background: linear-gradient(90deg, #22c55e, #4ade80); }
        .summary-card.absent::before { background: linear-gradient(90deg, #ef4444, #f87171); }
        .summary-card.leave::before { background: linear-gradient(90deg, #2563eb, #60a5fa); }
        .summary-card.late::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .summary-card.total::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
        .summary-card.hours::before { background: linear-gradient(90deg, #06b6d4, #22d3ee); }

        .summary-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 4px;
        }

        .summary-card.present .summary-value { color: #16a34a; }
        .summary-card.absent .summary-value { color: #dc2626; }
        .summary-card.leave .summary-value { color: #2563eb; }
        .summary-card.late .summary-value { color: #d97706; }
        .summary-card.total .summary-value { color: #7c3aed; }
        .summary-card.hours .summary-value { color: #0891b2; }

        .summary-label {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .data-table th {
            background: linear-gradient(180deg, #1e40af, #1d4ed8);
            color: white;
            padding: 12px 14px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .data-table tbody tr:hover {
            background: #eff6ff;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-P { background: #dcfce7; color: #166534; }
        .status-A { background: #fee2e2; color: #991b1b; }
        .status-L { background: #dbeafe; color: #1e40af; }
        .status-LT { background: #fef3c7; color: #92400e; }
        .status-H { background: #f3e8ff; color: #6b21a8; }
        .status-WO { background: #f1f5f9; color: #475569; }
        .status-HD { background: #fce7f3; color: #9d174d; }

        .status-approved { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-rejected { background: #fee2e2; color: #991b1b; }

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }

        /* Leave Breakdown */
        .leave-breakdown {
            background: linear-gradient(145deg, #f8fafc, #ffffff);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .leave-breakdown-title {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .leave-type-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .leave-type-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .leave-type-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .leave-type-dot.casual { background: #3b82f6; }
        .leave-type-dot.sick { background: #ef4444; }
        .leave-type-dot.earned { background: #22c55e; }
        .leave-type-dot.compoff { background: #f59e0b; }
        .leave-type-dot.other { background: #8b5cf6; }

        .leave-type-name {
            flex: 1;
            font-size: 11px;
            color: #64748b;
        }

        .leave-type-days {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }

        .leave-total-row {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px dashed #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .leave-total-label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        .leave-total-value {
            font-size: 18px;
            font-weight: 700;
            color: #2563eb;
        }

        /* Muster Table (Calendar Grid) */
        .muster-table {
            font-size: 9px;
        }

        .muster-table th {
            padding: 8px 4px;
            text-align: center;
            min-width: 24px;
        }

        .muster-table td {
            padding: 6px 4px;
            text-align: center;
        }

        .muster-table .status-badge {
            padding: 3px 6px;
            font-size: 8px;
        }

        /* Footer */
        .report-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 24px;
        }

        .footer-left {
            font-size: 9px;
            color: #94a3b8;
        }

        .footer-right {
            font-size: 9px;
            color: #64748b;
        }

        /* Legend */
        .legend {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 9px;
            color: #64748b;
        }

        /* Print Styles */
        @media print {
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .report-header { -webkit-print-color-adjust: exact !important; }
            .summary-card::before { -webkit-print-color-adjust: exact !important; }
            .data-table th { -webkit-print-color-adjust: exact !important; }
            .status-badge { -webkit-print-color-adjust: exact !important; }
            .print-btn { display: none !important; }
        }
    </style>

    <div class="report-container">
        <!-- Header -->
        <div class="report-header">
            <div class="header-content">
                <div class="company-info">
                    <div class="company-logo">
                        <img src="' . $logoUrl . '" alt="BES">
                    </div>
                    <div class="company-details">
                        <h1>Bombay Engineering Syndicate</h1>
                        <div class="tagline">HRMS Report</div>
                    </div>
                </div>
                <div class="report-meta">
                    <div class="report-type-badge">' . htmlspecialchars($reportTitle) . '</div>
                    <div class="report-date">Generated: ' . date('d M Y, h:i A') . '</div>
                </div>
            </div>
        </div>

        <!-- Employee Info Bar -->
        <div class="employee-bar">
            <div class="employee-name">' . htmlspecialchars($userName) . '</div>
            <div class="period-badge">' . htmlspecialchars($monthName) . '</div>
        </div>

        <!-- Content -->
        <div class="report-content">';

    switch ($reportType) {
        case 'monthly':
            // Summary Cards
            $html .= '<div class="summary-cards">';
            $html .= '<div class="summary-card present"><div class="summary-value">' . ($data['summary']['P'] ?? 0) . '</div><div class="summary-label">Present</div></div>';
            $html .= '<div class="summary-card absent"><div class="summary-value">' . ($data['summary']['A'] ?? 0) . '</div><div class="summary-label">Absent</div></div>';
            $html .= '<div class="summary-card leave"><div class="summary-value">' . ($data['summary']['L'] ?? 0) . '</div><div class="summary-label">Leave</div></div>';
            $html .= '<div class="summary-card late"><div class="summary-value">' . ($data['summary']['LT'] ?? 0) . '</div><div class="summary-label">Late</div></div>';
            $html .= '</div>';

            // Calendar Grid
            $html .= '<table class="data-table muster-table">';
            $html .= '<thead><tr><th>Day</th>';
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $html .= '<th>' . $d . '</th>';
            }
            $html .= '</tr></thead>';
            $html .= '<tbody><tr><td style="font-weight:600;">Status</td>';
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $status = $data['days'][$d] ?? '-';
                $html .= '<td><span class="status-badge status-' . $status . '">' . $status . '</span></td>';
            }
            $html .= '</tr></tbody></table>';

            // Legend
            $html .= '<div class="legend">';
            $html .= '<div class="legend-item"><span class="status-badge status-P">P</span> Present</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-A">A</span> Absent</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-L">L</span> Leave</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-LT">LT</span> Late</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-H">H</span> Holiday</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-WO">WO</span> Weekly Off</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-HD">HD</span> Half Day</div>';
            $html .= '</div>';
            break;

        case 'summary':
            // Summary Cards
            $html .= '<div class="summary-cards">';
            $html .= '<div class="summary-card present"><div class="summary-value">' . ($data['summary']['P'] ?? 0) . '</div><div class="summary-label">Present</div></div>';
            $html .= '<div class="summary-card absent"><div class="summary-value">' . ($data['summary']['A'] ?? 0) . '</div><div class="summary-label">Absent</div></div>';
            $html .= '<div class="summary-card leave"><div class="summary-value">' . ($data['summary']['L'] ?? 0) . '</div><div class="summary-label">Leave</div></div>';
            $html .= '<div class="summary-card hours"><div class="summary-value">' . ($data['totalHours'] ?? 0) . '</div><div class="summary-label">Total Hours</div></div>';
            $html .= '</div>';

            // Detailed Summary Table
            $html .= '<table class="data-table">';
            $html .= '<thead><tr><th>Metric</th><th style="text-align:center;">Value</th></tr></thead>';
            $html .= '<tbody>';
            $html .= '<tr><td>Total Working Days</td><td style="text-align:center;font-weight:600;">' . ($data['workingDays'] ?? 0) . '</td></tr>';
            $html .= '<tr><td>Days Present</td><td style="text-align:center;color:#16a34a;font-weight:600;">' . ($data['summary']['P'] ?? 0) . '</td></tr>';
            $html .= '<tr><td>Days Absent</td><td style="text-align:center;color:#dc2626;font-weight:600;">' . ($data['summary']['A'] ?? 0) . '</td></tr>';
            $html .= '<tr><td>Days on Leave</td><td style="text-align:center;color:#2563eb;font-weight:600;">' . ($data['summary']['L'] ?? 0) . '</td></tr>';
            $html .= '<tr><td>Half Days</td><td style="text-align:center;">' . ($data['summary']['HD'] ?? 0) . '</td></tr>';
            $html .= '<tr><td>Late Arrivals</td><td style="text-align:center;color:#d97706;">' . ($data['summary']['LT'] ?? 0) . '</td></tr>';
            $html .= '<tr><td>Holidays</td><td style="text-align:center;">' . ($data['summary']['H'] ?? 0) . '</td></tr>';
            $html .= '<tr><td>Weekly Offs</td><td style="text-align:center;">' . ($data['summary']['WO'] ?? 0) . '</td></tr>';
            $html .= '<tr><td>Total Working Hours</td><td style="text-align:center;">' . ($data['totalHours'] ?? 0) . ' hrs</td></tr>';
            $html .= '<tr style="background:#eff6ff;"><td style="font-weight:700;">Payable Days</td><td style="text-align:center;font-weight:700;font-size:14px;color:#1e40af;">' . ($data['payableDays'] ?? 0) . '</td></tr>';
            $html .= '</tbody></table>';
            break;

        case 'late_early':
            // Count summary
            $totalLate = 0;
            $totalEarly = 0;
            $totalLateMinutes = 0;
            foreach ($data['records'] ?? [] as $row) {
                if ($row['lateMinutes'] > 0) { $totalLate++; $totalLateMinutes += $row['lateMinutes']; }
                if ($row['earlyMinutes'] > 0) $totalEarly++;
            }

            // Summary Cards
            $html .= '<div class="summary-cards">';
            $html .= '<div class="summary-card late"><div class="summary-value">' . $totalLate . '</div><div class="summary-label">Late Days</div></div>';
            $html .= '<div class="summary-card total"><div class="summary-value">' . $totalLateMinutes . '</div><div class="summary-label">Late Minutes</div></div>';
            $html .= '<div class="summary-card absent"><div class="summary-value">' . $totalEarly . '</div><div class="summary-label">Early Checkout</div></div>';
            $html .= '<div class="summary-card present"><div class="summary-value">' . count($data['records'] ?? []) . '</div><div class="summary-label">Total Records</div></div>';
            $html .= '</div>';

            // Late/Early Table
            $html .= '<table class="data-table">';
            $html .= '<thead><tr><th>Date</th><th>Check In</th><th>Late By</th><th>Check Out</th><th>Early By</th><th>Status</th><th>Remark</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($data['records'] ?? [] as $row) {
                $html .= '<tr>';
                $html .= '<td style="font-weight:500;">' . $row['date'] . '</td>';
                $html .= '<td>' . ($row['checkIn'] ?: '-') . '</td>';
                $html .= '<td>' . ($row['lateMinutes'] > 0 ? '<span class="status-badge status-LT">' . $row['lateMinutes'] . ' min</span>' : '-') . '</td>';
                $html .= '<td>' . ($row['checkOut'] ?: '-') . '</td>';
                $html .= '<td>' . ($row['earlyMinutes'] > 0 ? '<span class="status-badge status-A">' . $row['earlyMinutes'] . ' min</span>' : '-') . '</td>';
                $html .= '<td>' . $row['status'] . '</td>';
                $html .= '<td style="color:#64748b;font-size:10px;max-width:150px;">' . htmlspecialchars($row['reason'] ?? '-') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            break;

        case 'leave':
            // Calculate summary by leave type (approved leaves only)
            $leaveSummary = [];
            $totalDays = 0;
            $totalApproved = 0;
            $totalPending = 0;
            $totalRejected = 0;

            foreach ($data['leaves'] ?? [] as $row) {
                $days = floatval($row['days'] ?? 0);
                $status = strtolower($row['status'] ?? '');

                if ($status === 'approved') {
                    $leaveType = $row['leaveType'] ?? 'Other';
                    if (!isset($leaveSummary[$leaveType])) {
                        $leaveSummary[$leaveType] = 0;
                    }
                    $leaveSummary[$leaveType] += $days;
                    $totalDays += $days;
                    $totalApproved++;
                } elseif ($status === 'pending') {
                    $totalPending++;
                } elseif ($status === 'rejected' || $status === 'disapproved') {
                    $totalRejected++;
                }
            }

            // Summary Cards
            $html .= '<div class="summary-cards">';
            $html .= '<div class="summary-card present"><div class="summary-value">' . $totalApproved . '</div><div class="summary-label">Approved</div></div>';
            $html .= '<div class="summary-card late"><div class="summary-value">' . $totalPending . '</div><div class="summary-label">Pending</div></div>';
            $html .= '<div class="summary-card absent"><div class="summary-value">' . $totalRejected . '</div><div class="summary-label">Rejected</div></div>';
            $html .= '<div class="summary-card leave"><div class="summary-value">' . $totalDays . '</div><div class="summary-label">Days Taken</div></div>';
            $html .= '</div>';

            // Leave breakdown by type
            if (!empty($leaveSummary)) {
                $html .= '<div class="leave-breakdown">';
                $html .= '<div class="leave-breakdown-title">Leave Breakdown (Approved)</div>';
                $html .= '<div class="leave-type-grid">';

                foreach ($leaveSummary as $leaveType => $days) {
                    $typeLower = strtolower($leaveType);
                    $dotClass = 'other';
                    if (strpos($typeLower, 'casual') !== false) $dotClass = 'casual';
                    elseif (strpos($typeLower, 'sick') !== false || strpos($typeLower, 'medical') !== false) $dotClass = 'sick';
                    elseif (strpos($typeLower, 'earned') !== false || strpos($typeLower, 'privilege') !== false) $dotClass = 'earned';
                    elseif (strpos($typeLower, 'comp') !== false) $dotClass = 'compoff';

                    $html .= '<div class="leave-type-item">';
                    $html .= '<span class="leave-type-dot ' . $dotClass . '"></span>';
                    $html .= '<span class="leave-type-name">' . htmlspecialchars($leaveType) . '</span>';
                    $html .= '<span class="leave-type-days">' . $days . '</span>';
                    $html .= '</div>';
                }
                $html .= '</div>';

                $html .= '<div class="leave-total-row">';
                $html .= '<span class="leave-total-label">Total Leaves Taken</span>';
                $html .= '<span class="leave-total-value">' . $totalDays . ' days</span>';
                $html .= '</div>';
                $html .= '</div>';
            }

            // Leave history table
            $html .= '<table class="data-table">';
            $html .= '<thead><tr><th>Leave Type</th><th>From</th><th>To</th><th style="text-align:center;">Days</th><th>Reason</th><th style="text-align:center;">Status</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($data['leaves'] ?? [] as $row) {
                $status = strtolower($row['status'] ?? '');
                $statusClass = 'status-pending';
                if ($status === 'approved') $statusClass = 'status-approved';
                elseif ($status === 'rejected' || $status === 'disapproved') $statusClass = 'status-rejected';

                $html .= '<tr>';
                $html .= '<td style="font-weight:500;">' . htmlspecialchars($row['leaveType']) . '</td>';
                $html .= '<td>' . $row['fromDate'] . '</td>';
                $html .= '<td>' . $row['toDate'] . '</td>';
                $html .= '<td style="text-align:center;font-weight:600;">' . $row['days'] . '</td>';
                $html .= '<td style="color:#64748b;font-size:10px;">' . htmlspecialchars($row['reason'] ?? '-') . '</td>';
                $html .= '<td style="text-align:center;"><span class="status-badge ' . $statusClass . '">' . ucfirst($row['status']) . '</span></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            break;

        case 'detailed':
            // Summary Cards
            $html .= '<div class="summary-cards">';
            $html .= '<div class="summary-card total"><div class="summary-value">' . ($data['totalDays'] ?? 0) . '</div><div class="summary-label">Total Days</div></div>';
            $html .= '<div class="summary-card total"><div class="summary-value">' . ($data['workingDays'] ?? 0) . '</div><div class="summary-label">Working Days</div></div>';
            $html .= '<div class="summary-card present"><div class="summary-value">' . ($data['presentDays'] ?? 0) . '</div><div class="summary-label">Present</div></div>';
            $html .= '<div class="summary-card absent"><div class="summary-value">' . ($data['absentDays'] ?? 0) . '</div><div class="summary-label">Absent</div></div>';
            $html .= '</div>';

            $html .= '<div class="summary-cards">';
            $html .= '<div class="summary-card leave"><div class="summary-value">' . ($data['leaveDays'] ?? 0) . '</div><div class="summary-label">Leave Days</div></div>';
            $html .= '<div class="summary-card late"><div class="summary-value">' . ($data['lateDays'] ?? 0) . '</div><div class="summary-label">Late Days</div></div>';
            $html .= '<div class="summary-card leave"><div class="summary-value">' . ($data['halfDays'] ?? 0) . '</div><div class="summary-label">Half Days</div></div>';
            $html .= '<div class="summary-card present"><div class="summary-value">' . ($data['onDutyDays'] ?? 0) . '</div><div class="summary-label">On Duty</div></div>';
            $html .= '</div>';

            $html .= '<div class="summary-cards">';
            $html .= '<div class="summary-card hours"><div class="summary-value">' . ($data['totalHours'] ?? 0) . '</div><div class="summary-label">Total Hours</div></div>';
            $html .= '<div class="summary-card hours"><div class="summary-value">' . ($data['avgHours'] ?? 0) . '</div><div class="summary-label">Avg Hours/Day</div></div>';
            $html .= '<div class="summary-card total"><div class="summary-value">' . ($data['sundays'] ?? 0) . '</div><div class="summary-label">Sundays</div></div>';
            $html .= '<div class="summary-card total"><div class="summary-value">' . ($data['holidays'] ?? 0) . '</div><div class="summary-label">Holidays</div></div>';
            $html .= '</div>';

            // Detailed records table
            $html .= '<table class="data-table">';
            $html .= '<thead><tr><th>Day</th><th>Date</th><th>Check In</th><th>Check Out</th><th style="text-align:center;">Hours</th><th style="text-align:center;">Status</th><th>Remark</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($data['records'] ?? [] as $row) {
                $statusCode = $row['statusCode'] ?? 'P';
                $html .= '<tr>';
                $html .= '<td style="font-weight:500;">' . $row['dayName'] . '</td>';
                $html .= '<td>' . $row['date'] . '</td>';
                $html .= '<td>' . ($row['checkIn'] ?: '-') . '</td>';
                $html .= '<td>' . ($row['checkOut'] ?: '-') . '</td>';
                $html .= '<td style="text-align:center;font-weight:600;">' . ($row['workingHours'] ?: '-') . '</td>';
                $html .= '<td style="text-align:center;"><span class="status-badge status-' . $statusCode . '">' . $row['status'] . '</span></td>';
                $html .= '<td style="color:#64748b;font-size:10px;max-width:150px;">' . htmlspecialchars($row['remark'] ?? '-') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';

            // Legend
            $html .= '<div class="legend">';
            $html .= '<div class="legend-item"><span class="status-badge status-P">P</span> Present</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-A">A</span> Absent</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-L">L</span> Leave</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-H">H</span> Holiday</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-WO">WO</span> Weekly Off</div>';
            $html .= '</div>';
            break;
    }

    // Close content div
    $html .= '</div>';

    // Footer
    $html .= '
        <div class="report-footer">
            <div class="footer-left">
                This is a system-generated report from BES HRMS Portal
            </div>
            <div class="footer-right">
                Generated on ' . date('d M Y, h:i A') . '
            </div>
        </div>
    </div>';

    return $html;
}

/**
 * Generate HTML for Master (All Employees) Report for Excel/PDF export - Beautiful Template
 */
function generateMasterReportHTML($reportType, $data, $monthName, $daysInMonth)
{
    if (isset($data['err']) && $data['err'] == 1) {
        return '<h1>Report Not Available</h1><p>' . htmlspecialchars($data['message'] ?? 'This report type is only available for individual employees.') . '</p>';
    }

    $logoUrl = SITEURL . '/xsite/images/logo-icon.png';
    $reportTitles = array(
        'monthly' => 'Monthly Muster Roll',
        'summary' => 'Attendance Summary Report',
        'late_early' => 'Late / Early Report',
        'leave' => 'Leave History Report',
        'detailed' => 'Detailed Attendance Report'
    );
    $reportTitle = $reportTitles[$reportType] ?? 'Master Report';
    $totalEmployees = $data['totalEmployees'] ?? count($data['data'] ?? []);

    // Beautiful PDF Template with same styles
    $html = '
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap");

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 10px;
            color: #1f2937;
            background: #fff;
            line-height: 1.4;
        }

        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
        }

        .report-header {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
            color: white;
            padding: 20px 28px;
            position: relative;
            overflow: hidden;
        }

        .report-header::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -10%;
            width: 250px;
            height: 250px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .company-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .company-logo {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 8px;
            padding: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .company-details h1 {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -0.3px;
            margin-bottom: 2px;
        }

        .company-details .tagline {
            font-size: 9px;
            opacity: 0.85;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .report-meta {
            text-align: right;
        }

        .report-type-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 5px 12px;
            border-radius: 100px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .report-date {
            font-size: 10px;
            opacity: 0.9;
        }

        .info-bar {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-bar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-label {
            font-size: 10px;
            color: #64748b;
        }

        .info-value {
            font-size: 12px;
            font-weight: 700;
            color: #1e40af;
        }

        .period-badge {
            background: linear-gradient(135deg, #dbeafe, #eff6ff);
            border: 1px solid #bfdbfe;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            color: #1e40af;
        }

        .report-content {
            padding: 20px 28px;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .summary-card.present::before { background: linear-gradient(90deg, #22c55e, #4ade80); }
        .summary-card.absent::before { background: linear-gradient(90deg, #ef4444, #f87171); }
        .summary-card.leave::before { background: linear-gradient(90deg, #2563eb, #60a5fa); }
        .summary-card.late::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .summary-card.total::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }

        .summary-value {
            font-size: 22px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 3px;
        }

        .summary-card.present .summary-value { color: #16a34a; }
        .summary-card.absent .summary-value { color: #dc2626; }
        .summary-card.leave .summary-value { color: #2563eb; }
        .summary-card.late .summary-value { color: #d97706; }
        .summary-card.total .summary-value { color: #7c3aed; }

        .summary-label {
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            margin-bottom: 16px;
            font-size: 9px;
        }

        .data-table th {
            background: linear-gradient(180deg, #1e40af, #1d4ed8);
            color: white;
            padding: 8px 6px;
            text-align: center;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .data-table th.name-col {
            text-align: left;
            min-width: 120px;
        }

        .data-table td {
            padding: 6px;
            border-bottom: 1px solid #f1f5f9;
            text-align: center;
        }

        .data-table td.name-col {
            text-align: left;
            font-weight: 500;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .data-table tbody tr.grand-total {
            background: linear-gradient(90deg, #eff6ff, #dbeafe) !important;
            font-weight: 700;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-P { background: #dcfce7; color: #166534; }
        .status-A { background: #fee2e2; color: #991b1b; }
        .status-L { background: #dbeafe; color: #1e40af; }
        .status-LT { background: #fef3c7; color: #92400e; }
        .status-H { background: #f3e8ff; color: #6b21a8; }
        .status-WO { background: #f1f5f9; color: #475569; }
        .status-HD { background: #fce7f3; color: #9d174d; }

        .legend {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 8px;
            color: #64748b;
        }

        .report-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 12px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .footer-left {
            font-size: 8px;
            color: #94a3b8;
        }

        .footer-right {
            font-size: 8px;
            color: #64748b;
        }

        @media print {
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .report-header { -webkit-print-color-adjust: exact !important; }
            .summary-card::before { -webkit-print-color-adjust: exact !important; }
            .data-table th { -webkit-print-color-adjust: exact !important; }
            .status-badge { -webkit-print-color-adjust: exact !important; }
            .print-btn { display: none !important; }
        }
    </style>

    <div class="report-container">
        <div class="report-header">
            <div class="header-content">
                <div class="company-info">
                    <div class="company-logo">
                        <img src="' . $logoUrl . '" alt="BES">
                    </div>
                    <div class="company-details">
                        <h1>Bombay Engineering Syndicate</h1>
                        <div class="tagline">HRMS Master Report</div>
                    </div>
                </div>
                <div class="report-meta">
                    <div class="report-type-badge">' . htmlspecialchars($reportTitle) . '</div>
                    <div class="report-date">Generated: ' . date('d M Y, h:i A') . '</div>
                </div>
            </div>
        </div>

        <div class="info-bar">
            <div class="info-bar-left">
                <div class="info-item">
                    <span class="info-label">Employees:</span>
                    <span class="info-value">' . $totalEmployees . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Working Days:</span>
                    <span class="info-value">' . ($data['workingDays'] ?? $daysInMonth) . '</span>
                </div>
            </div>
            <div class="period-badge">' . htmlspecialchars($monthName) . '</div>
        </div>

        <div class="report-content">';

    switch ($reportType) {
        case 'monthly':
            // Summary Cards
            $grandSummary = $data['summary'] ?? [];
            $html .= '<div class="summary-cards">';
            $html .= '<div class="summary-card present"><div class="summary-value">' . ($grandSummary['P'] ?? 0) . '</div><div class="summary-label">Total Present</div></div>';
            $html .= '<div class="summary-card absent"><div class="summary-value">' . ($grandSummary['A'] ?? 0) . '</div><div class="summary-label">Total Absent</div></div>';
            $html .= '<div class="summary-card leave"><div class="summary-value">' . ($grandSummary['L'] ?? 0) . '</div><div class="summary-label">Total Leave</div></div>';
            $html .= '<div class="summary-card late"><div class="summary-value">' . ($grandSummary['LT'] ?? 0) . '</div><div class="summary-label">Total Late</div></div>';
            $html .= '<div class="summary-card total"><div class="summary-value">' . $totalEmployees . '</div><div class="summary-label">Employees</div></div>';
            $html .= '</div>';

            // Muster Roll Table
            $html .= '<table class="data-table">';
            $html .= '<thead><tr><th class="name-col">Employee</th>';
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $html .= '<th>' . $d . '</th>';
            }
            $html .= '<th>P</th><th>A</th><th>L</th></tr></thead>';
            $html .= '<tbody>';

            foreach ($data['data'] ?? [] as $emp) {
                $html .= '<tr>';
                $html .= '<td class="name-col">' . htmlspecialchars($emp['empName']) . '</td>';
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $status = $emp['days'][$d] ?? '-';
                    $html .= '<td><span class="status-badge status-' . $status . '">' . $status . '</span></td>';
                }
                $summary = $emp['summary'] ?? [];
                $html .= '<td style="font-weight:700;color:#16a34a;">' . ($summary['P'] ?? 0) . '</td>';
                $html .= '<td style="font-weight:700;color:#dc2626;">' . ($summary['A'] ?? 0) . '</td>';
                $html .= '<td style="font-weight:700;color:#2563eb;">' . ($summary['L'] ?? 0) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';

            // Legend
            $html .= '<div class="legend">';
            $html .= '<div class="legend-item"><span class="status-badge status-P">P</span> Present</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-A">A</span> Absent</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-L">L</span> Leave</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-LT">LT</span> Late</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-H">H</span> Holiday</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-WO">WO</span> Weekly Off</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-HD">HD</span> Half Day</div>';
            $html .= '</div>';
            break;

        case 'summary':
            // Summary Cards
            $grandTotals = $data['grandTotals'] ?? [];
            $html .= '<div class="summary-cards">';
            $html .= '<div class="summary-card present"><div class="summary-value">' . ($grandTotals['P'] ?? 0) . '</div><div class="summary-label">Total Present</div></div>';
            $html .= '<div class="summary-card absent"><div class="summary-value">' . ($grandTotals['A'] ?? 0) . '</div><div class="summary-label">Total Absent</div></div>';
            $html .= '<div class="summary-card leave"><div class="summary-value">' . ($grandTotals['L'] ?? 0) . '</div><div class="summary-label">Total Leave</div></div>';
            $html .= '<div class="summary-card late"><div class="summary-value">' . ($grandTotals['LT'] ?? 0) . '</div><div class="summary-label">Total Late</div></div>';
            $html .= '<div class="summary-card total"><div class="summary-value">' . ($grandTotals['totalHours'] ?? 0) . '</div><div class="summary-label">Total Hours</div></div>';
            $html .= '</div>';

            // Summary Table
            $html .= '<table class="data-table">';
            $html .= '<thead><tr><th class="name-col">Employee</th><th>Present</th><th>Absent</th><th>Leave</th><th>Half Day</th><th>Late</th><th>Hours</th><th>Payable</th></tr></thead>';
            $html .= '<tbody>';

            foreach ($data['data'] ?? [] as $emp) {
                $html .= '<tr>';
                $html .= '<td class="name-col">' . htmlspecialchars($emp['empName']) . '</td>';
                $html .= '<td style="color:#16a34a;font-weight:600;">' . ($emp['present'] ?? 0) . '</td>';
                $html .= '<td style="color:#dc2626;">' . ($emp['absent'] ?? 0) . '</td>';
                $html .= '<td style="color:#2563eb;">' . ($emp['leave'] ?? 0) . '</td>';
                $html .= '<td>' . ($emp['halfDay'] ?? 0) . '</td>';
                $html .= '<td style="color:#d97706;">' . ($emp['late'] ?? 0) . '</td>';
                $html .= '<td>' . ($emp['totalHours'] ?? 0) . '</td>';
                $html .= '<td style="font-weight:700;color:#1e40af;">' . ($emp['payableDays'] ?? 0) . '</td>';
                $html .= '</tr>';
            }

            // Grand Total Row
            $html .= '<tr class="grand-total">';
            $html .= '<td class="name-col">GRAND TOTAL</td>';
            $html .= '<td style="color:#16a34a;">' . ($grandTotals['P'] ?? 0) . '</td>';
            $html .= '<td style="color:#dc2626;">' . ($grandTotals['A'] ?? 0) . '</td>';
            $html .= '<td style="color:#2563eb;">' . ($grandTotals['L'] ?? 0) . '</td>';
            $html .= '<td>' . ($grandTotals['HD'] ?? 0) . '</td>';
            $html .= '<td style="color:#d97706;">' . ($grandTotals['LT'] ?? 0) . '</td>';
            $html .= '<td>' . ($grandTotals['totalHours'] ?? 0) . '</td>';
            $html .= '<td>-</td>';
            $html .= '</tr>';
            $html .= '</tbody></table>';
            break;

        case 'detailed':
            // Summary Cards for Master Detailed Report
            $totalPresent = $data['presentDays'] ?? 0;
            $totalAbsent = $data['absentDays'] ?? 0;
            $totalLeave = $data['leaveDays'] ?? 0;
            $totalHours = $data['totalHours'] ?? 0;

            $html .= '<div class="summary-cards">';
            $html .= '<div class="summary-card total"><div class="summary-value">' . $totalEmployees . '</div><div class="summary-label">Employees</div></div>';
            $html .= '<div class="summary-card present"><div class="summary-value">' . $totalPresent . '</div><div class="summary-label">Total Present</div></div>';
            $html .= '<div class="summary-card absent"><div class="summary-value">' . $totalAbsent . '</div><div class="summary-label">Total Absent</div></div>';
            $html .= '<div class="summary-card leave"><div class="summary-value">' . $totalLeave . '</div><div class="summary-label">Total Leave</div></div>';
            $html .= '<div class="summary-card late"><div class="summary-value">' . round($totalHours, 1) . '</div><div class="summary-label">Total Hours</div></div>';
            $html .= '</div>';

            // Detailed Report Table
            $html .= '<table class="data-table">';
            $html .= '<thead><tr><th class="name-col">Employee</th><th>Day</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th><th>Remark</th></tr></thead>';
            $html .= '<tbody>';

            foreach ($data['records'] ?? [] as $row) {
                $statusCode = $row['statusCode'] ?? 'P';
                $html .= '<tr>';
                $html .= '<td class="name-col">' . htmlspecialchars($row['empName'] ?? '') . '</td>';
                $html .= '<td>' . ($row['dayName'] ?? '') . '</td>';
                $html .= '<td>' . ($row['date'] ?? '') . '</td>';
                $html .= '<td>' . ($row['checkIn'] ?: '-') . '</td>';
                $html .= '<td>' . ($row['checkOut'] ?: '-') . '</td>';
                $html .= '<td>' . ($row['workingHours'] ?: '-') . '</td>';
                $html .= '<td><span class="status-badge status-' . $statusCode . '">' . ($row['status'] ?? '') . '</span></td>';
                $html .= '<td>' . htmlspecialchars($row['remark'] ?? '') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';

            // Legend
            $html .= '<div class="legend">';
            $html .= '<div class="legend-item"><span class="status-badge status-P">P</span> Present</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-A">A</span> Absent</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-L">L</span> Leave</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-H">H</span> Holiday</div>';
            $html .= '<div class="legend-item"><span class="status-badge status-WO">WO</span> Weekly Off</div>';
            $html .= '</div>';
            break;

        default:
            $html .= '<p>Report type not supported for master view.</p>';
    }

    // Close content div
    $html .= '</div>';

    // Footer
    $html .= '
        <div class="report-footer">
            <div class="footer-left">
                This is a system-generated report from BES HRMS Portal
            </div>
            <div class="footer-right">
                Generated on ' . date('d M Y, h:i A') . '
            </div>
        </div>
    </div>';

    return $html;
}

/**
 * Check if current user can access salary processing
 * Only HR Admin and Accounts Person can access
 */
function canAccessSalaryProcessing()
{
    return ($_SESSION['HRMS_IS_HR_ADMIN'] ?? false) || ($_SESSION['HRMS_IS_ACCOUNTS'] ?? false);
}

/**
 * Get salary processing list for a month
 * Accessible by HR Admin and Accounts Person
 */
function getSalaryProcessingList()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    if (!canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied. Only HR Admin and Accounts can access salary processing.');
    }

    $month = intval($_POST['month'] ?? date('n'));
    $year = intval($_POST['year'] ?? date('Y'));

    // Get all active employees with their salary structure
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));

    // For current month, only count up to today
    $today = date('Y-m-d');
    $isCurrentMonth = ($year == date('Y') && $month == date('n'));
    $effectiveEndDate = $isCurrentMonth ? $today : $endDate;

    $DB->vals = array($startDate, $startDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT u.userID, u.userName, u.displayName, u.employeeCode, u.department, u.designation,
                       u.bankName, u.bankAccountNo, u.bankIFSC,
                       ss.basicSalary, ss.hra, ss.conveyanceAllowance, ss.medicalAllowance,
                       ss.specialAllowance, ss.otherAllowance, ss.grossSalary
                FROM `" . $DB->pre . "x_admin_user` u
                LEFT JOIN `" . $DB->pre . "salary_structure` ss ON u.userID = ss.userID
                    AND ss.status = 1
                    AND ss.effectiveFrom <= ?
                    AND (ss.effectiveTo IS NULL OR ss.effectiveTo >= ?)
                WHERE u.status = ?
                ORDER BY u.userName";
    $employees = $DB->dbRows();

    $salaryList = array();
    $workingDaysInMonth = getWorkingDaysInMonth($year, $month);

    // Calculate working days elapsed (up to today for current month)
    $workingDaysElapsed = $isCurrentMonth ? getWorkingDaysUpToDate($year, $month, date('j')) : $workingDaysInMonth;

    foreach ($employees as $emp) {
        $userID = $emp['userID'];

        // Get attendance summary for this month (up to effectiveEndDate)
        $DB->vals = array($userID, $startDate, $effectiveEndDate);
        $DB->types = "iss";
        $DB->sql = "SELECT
                        COUNT(*) as totalRecords,
                        SUM(CASE WHEN attendanceStatus = 'present' THEN 1 ELSE 0 END) as presentDays,
                        SUM(CASE WHEN attendanceStatus = 'absent' THEN 1 ELSE 0 END) as absentDays,
                        SUM(CASE WHEN attendanceStatus = 'half_day' THEN 1 ELSE 0 END) as halfDays,
                        SUM(CASE WHEN attendanceStatus = 'leave' THEN 1 ELSE 0 END) as leaveDaysFromAttendance,
                        SUM(CASE WHEN isLate = 1 THEN 1 ELSE 0 END) as lateDays
                    FROM `" . $DB->pre . "attendance`
                    WHERE userID = ? AND attendanceDate BETWEEN ? AND ?";
        $attendance = $DB->dbRow();

        // Get approved leaves from leave management system for this month (up to effectiveEndDate)
        $DB->vals = array($userID, 1, $startDate, $effectiveEndDate);
        $DB->types = "iiss";
        $DB->sql = "SELECT
                        SUM(CASE WHEN ld.lType IN (2,3) THEN 0.5 ELSE 1 END) as leaveDays
                    FROM `" . $DB->pre . "leave` l
                    INNER JOIN `" . $DB->pre . "leave_details` ld ON l.leaveID = ld.leaveID
                    WHERE l.userID = ?
                        AND l.status = ?
                        AND l.leaveStatus IN ('Approved', 'Parsley Approved')
                        AND ld.leaveDate BETWEEN ? AND ?
                        AND ld.lType != -1";
        $leaveData = $DB->dbRow();
        $actualLeaveDays = floatval($leaveData['leaveDays'] ?? 0);

        // Skip employees with no attendance records for this month
        if (intval($attendance['totalRecords'] ?? 0) == 0) {
            continue;
        }

        // Get active salary advances for this employee (considers frequency)
        $advanceDeductionAmount = getAdvanceDeductionForSalary($userID, $month, $year);

        // Get existing salary slip if any
        $DB->vals = array($userID, $month, $year);
        $DB->types = "iii";
        $DB->sql = "SELECT * FROM `" . $DB->pre . "salary_slip`
                    WHERE userID = ? AND salaryMonth = ? AND salaryYear = ?";
        $existingSlip = $DB->dbRow();

        // Calculate salary
        $basicSalary = floatval($emp['grossSalary'] ?? 0);
        $perDaySalary = $workingDaysInMonth > 0 ? $basicSalary / $workingDaysInMonth : 0;

        $presentDays = intval($attendance['presentDays'] ?? 0);
        $halfDays = intval($attendance['halfDays'] ?? 0);
        $lateDays = intval($attendance['lateDays'] ?? 0);

        // Calculate actual absent days (working days elapsed minus present, leaves, half days)
        $calculatedAbsent = $workingDaysElapsed - $presentDays - $actualLeaveDays - ($halfDays * 0.5);
        $absentDays = max(0, intval($calculatedAbsent));

        // Calculate deductions
        $absentDeduction = $perDaySalary * $absentDays;
        $halfDayDeduction = ($perDaySalary / 2) * $halfDays;
        // Late deduction: 3 late = 1 day deduction (configurable)
        $lateDeduction = floor($lateDays / 3) * $perDaySalary;
        $advanceDeduction = $advanceDeductionAmount;

        $totalDeductions = $absentDeduction + $halfDayDeduction + $lateDeduction + $advanceDeduction;
        $netSalary = $basicSalary - $totalDeductions;

        $salaryList[] = array(
            'userID' => $userID,
            'employeeCode' => $emp['employeeCode'] ?? '',
            'employeeName' => $emp['displayName'] ?: $emp['userName'],
            'department' => $emp['department'] ?? '',
            'designation' => $emp['designation'] ?? '',
            'bankName' => $emp['bankName'] ?? '',
            'bankAccountNo' => $emp['bankAccountNo'] ?? '',
            'bankIFSC' => $emp['bankIFSC'] ?? '',
            'basicSalary' => $basicSalary,
            'workingDays' => $workingDaysInMonth,
            'presentDays' => $presentDays,
            'absentDays' => $absentDays,
            'halfDays' => $halfDays,
            'leaveDays' => $actualLeaveDays,
            'lateDays' => $lateDays,
            'absentDeduction' => round($absentDeduction, 2),
            'halfDayDeduction' => round($halfDayDeduction, 2),
            'lateDeduction' => round($lateDeduction, 2),
            'advanceDeduction' => round($advanceDeduction, 2),
            'totalDeductions' => round($totalDeductions, 2),
            'netSalary' => round($netSalary, 2),
            'paidAmount' => $existingSlip ? floatval($existingSlip['amountPaid']) : null,
            'slipStatus' => $existingSlip ? $existingSlip['slipStatus'] : 'pending',
            'slipID' => $existingSlip ? $existingSlip['slipID'] : null,
            'paymentMode' => $existingSlip ? $existingSlip['paymentMode'] : '',
            'transactionRef' => $existingSlip ? $existingSlip['transactionRef'] : '',
            'paidOn' => $existingSlip ? $existingSlip['paidOn'] : null
        );
    }

    return array(
        'err' => 0,
        'month' => $month,
        'year' => $year,
        'monthName' => date('F Y', strtotime($startDate)),
        'workingDays' => $workingDaysInMonth,
        'employees' => $salaryList,
        'totalGross' => array_sum(array_column($salaryList, 'basicSalary')),
        'totalDeductions' => array_sum(array_column($salaryList, 'totalDeductions')),
        'totalNet' => array_sum(array_column($salaryList, 'netSalary'))
    );
}

/**
 * Get working days in a month (excluding Sundays and holidays)
 */
function getWorkingDaysInMonth($year, $month)
{
    global $DB;

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $daysInMonth = date('t', strtotime($startDate));
    $endDate = date('Y-m-t', strtotime($startDate));

    $workingDays = 0;
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $dayOfWeek = date('w', strtotime($date));
        // Exclude Sundays (0)
        if ($dayOfWeek != 0) {
            $workingDays++;
        }
    }

    // Subtract holidays (ahDate is the column name in attendance_holidays table)
    $DB->vals = array($startDate, $endDate);
    $DB->types = "ss";
    $DB->sql = "SELECT COUNT(*) as holidayCount FROM `" . $DB->pre . "attendance_holidays`
                WHERE ahDate BETWEEN ? AND ? AND DAYOFWEEK(ahDate) != 1";
    $holidays = $DB->dbRow();
    $workingDays -= intval($holidays['holidayCount'] ?? 0);

    return max($workingDays, 1);
}

/**
 * Get working days up to a specific day in the month
 * Used for current month calculations
 */
function getWorkingDaysUpToDate($year, $month, $day)
{
    global $DB;

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = sprintf('%04d-%02d-%02d', $year, $month, $day);

    $workingDays = 0;
    for ($d = 1; $d <= $day; $d++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dayOfWeek = date('w', strtotime($date));
        // Exclude Sundays (0)
        if ($dayOfWeek != 0) {
            $workingDays++;
        }
    }

    // Subtract holidays
    $DB->vals = array($startDate, $endDate);
    $DB->types = "ss";
    $DB->sql = "SELECT COUNT(*) as holidayCount FROM `" . $DB->pre . "attendance_holidays`
                WHERE ahDate BETWEEN ? AND ? AND DAYOFWEEK(ahDate) != 1";
    $holidays = $DB->dbRow();
    $workingDays -= intval($holidays['holidayCount'] ?? 0);

    return max($workingDays, 1);
}

/**
 * Get payment history for all employees
 * Accessible by HR Admin and Accounts Person
 */
function getPaymentHistory()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    if (!canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied. Only HR Admin and Accounts can access payment history.');
    }

    $month = intval($_POST['month'] ?? 0);
    $year = intval($_POST['year'] ?? date('Y'));
    $userID = intval($_POST['userID'] ?? 0);

    // Build query
    $sql = "SELECT ss.*, u.displayName as employeeName, u.department, u.designation
            FROM `" . $DB->pre . "salary_slip` ss
            INNER JOIN `" . $DB->pre . "x_admin_user` u ON ss.userID = u.userID
            WHERE ss.salaryYear = ?";

    $vals = array($year);
    $types = "i";

    if ($month > 0) {
        $sql .= " AND ss.salaryMonth = ?";
        $vals[] = $month;
        $types .= "i";
    }

    if ($userID > 0) {
        $sql .= " AND ss.userID = ?";
        $vals[] = $userID;
        $types .= "i";
    }

    $sql .= " ORDER BY ss.salaryYear DESC, ss.salaryMonth DESC, u.displayName ASC";

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = $sql;
    $payments = $DB->dbRows();

    // Calculate statistics
    $totalPayments = count($payments);
    $totalAmountPaid = 0;
    $totalDeductions = 0;
    $monthsSet = array();

    foreach ($payments as $pay) {
        $totalAmountPaid += floatval($pay['amountPaid'] ?? 0);
        $totalDeductions += floatval($pay['totalDeductions'] ?? 0);
        $monthKey = $pay['salaryYear'] . '-' . $pay['salaryMonth'];
        $monthsSet[$monthKey] = true;
    }

    return array(
        'err' => 0,
        'payments' => $payments,
        'stats' => array(
            'totalPayments' => $totalPayments,
            'totalAmountPaid' => $totalAmountPaid,
            'totalDeductions' => $totalDeductions,
            'monthsProcessed' => count($monthsSet)
        )
    );
}

/**
 * Save salary payment details (update paid amount)
 */
function saveSalaryPayment()
{
    global $DB;

    if (!isHRMSLoggedIn() || !canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    $userID = intval($_POST['userID'] ?? 0);
    $month = intval($_POST['month'] ?? 0);
    $year = intval($_POST['year'] ?? 0);
    $paidAmount = floatval($_POST['paidAmount'] ?? 0);
    $paymentMode = trim($_POST['paymentMode'] ?? '');
    $transactionRef = trim($_POST['transactionRef'] ?? '');

    if (!$userID || !$month || !$year) {
        return array('err' => 1, 'msg' => 'Invalid parameters');
    }

    // Check if slip exists
    $DB->vals = array($userID, $month, $year);
    $DB->types = "iii";
    $DB->sql = "SELECT slipID FROM `" . $DB->pre . "salary_slip` WHERE userID = ? AND salaryMonth = ? AND salaryYear = ?";
    $existing = $DB->dbRow();

    if ($existing) {
        // Update existing
        $DB->table = $DB->pre . "salary_slip";
        $DB->data = array(
            "amountPaid" => $paidAmount,
            "paymentMode" => $paymentMode,
            "transactionRef" => $transactionRef
        );
        $DB->dbUpdate("slipID = ?", "i", array($existing['slipID']));
    } else {
        // Create new slip record
        $DB->table = $DB->pre . "salary_slip";
        $DB->data = array(
            "userID" => $userID,
            "salaryMonth" => $month,
            "salaryYear" => $year,
            "amountPaid" => $paidAmount,
            "paymentMode" => $paymentMode,
            "transactionRef" => $transactionRef,
            "slipStatus" => "pending"
        );
        $DB->dbInsert();
    }

    return array('err' => 0, 'msg' => 'Payment details saved');
}

/**
 * Mark salary as paid
 */
function markSalaryPaid()
{
    global $DB;

    if (!isHRMSLoggedIn() || !canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    $userID = intval($_POST['userID'] ?? 0);
    $month = intval($_POST['month'] ?? 0);
    $year = intval($_POST['year'] ?? 0);
    $paidAmount = floatval($_POST['paidAmount'] ?? 0);
    $paymentMode = trim($_POST['paymentMode'] ?? 'Bank Transfer');
    $transactionRef = trim($_POST['transactionRef'] ?? '');

    if (!$userID || !$month || !$year || $paidAmount <= 0) {
        return array('err' => 1, 'msg' => 'Invalid parameters');
    }

    // Get salary structure for this employee
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));

    $DB->vals = array($userID, $startDate, $startDate);
    $DB->types = "iss";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "salary_structure`
                WHERE userID = ? AND status = 1
                AND effectiveFrom <= ?
                AND (effectiveTo IS NULL OR effectiveTo >= ?)";
    $structure = $DB->dbRow();

    // Get attendance summary
    $DB->vals = array($userID, $startDate, $endDate);
    $DB->types = "iss";
    $DB->sql = "SELECT
                    COUNT(*) as totalRecords,
                    SUM(CASE WHEN attendanceStatus = 'present' THEN 1 ELSE 0 END) as presentDays,
                    SUM(CASE WHEN attendanceStatus = 'absent' THEN 1 ELSE 0 END) as absentDays,
                    SUM(CASE WHEN attendanceStatus = 'half_day' THEN 1 ELSE 0 END) as halfDays,
                    SUM(CASE WHEN attendanceStatus = 'leave' THEN 1 ELSE 0 END) as leaveDays,
                    SUM(CASE WHEN isLate = 1 THEN 1 ELSE 0 END) as lateDays
                FROM `" . $DB->pre . "attendance`
                WHERE userID = ? AND attendanceDate BETWEEN ? AND ?";
    $attendance = $DB->dbRow();

    // Get advance deduction
    $DB->vals = array($userID, $year, $year, $month);
    $DB->types = "iiii";
    $DB->sql = "SELECT SUM(monthlyDeduction) as advanceDeduction
                FROM `" . $DB->pre . "salary_advance`
                WHERE userID = ? AND advanceStatus = 'active'
                AND ((deductFromYear < ?) OR (deductFromYear = ? AND deductFromMonth <= ?))
                AND remainingAmount > 0";
    $advance = $DB->dbRow();

    // Calculate values
    $workingDays = getWorkingDaysInMonth($year, $month);
    $basicSalary = floatval($structure['basicSalary'] ?? 0);
    $hra = floatval($structure['hra'] ?? 0);
    $conveyance = floatval($structure['conveyanceAllowance'] ?? 0);
    $medical = floatval($structure['medicalAllowance'] ?? 0);
    $special = floatval($structure['specialAllowance'] ?? 0);
    $other = floatval($structure['otherAllowance'] ?? 0);
    $grossSalary = floatval($structure['grossSalary'] ?? 0);

    $presentDays = intval($attendance['presentDays'] ?? 0);
    $absentDays = intval($attendance['absentDays'] ?? 0);
    $halfDays = intval($attendance['halfDays'] ?? 0);
    $leaveDays = intval($attendance['leaveDays'] ?? 0);
    $lateDays = intval($attendance['lateDays'] ?? 0);
    $advanceDeduction = floatval($advance['advanceDeduction'] ?? 0);

    // Calculate deductions
    $perDaySalary = $workingDays > 0 ? $grossSalary / $workingDays : 0;
    $absentDeduction = $perDaySalary * $absentDays;
    $halfDayDeduction = ($perDaySalary / 2) * $halfDays;
    $lateDeduction = floor($lateDays / 3) * $perDaySalary;
    $totalDeductions = $absentDeduction + $halfDayDeduction + $lateDeduction + $advanceDeduction;
    $netSalary = $grossSalary - $totalDeductions;

    $paidBy = $_SESSION['HRMS_USER_ID'];
    $paidOn = date('Y-m-d');

    // Check if slip exists
    $DB->vals = array($userID, $month, $year);
    $DB->types = "iii";
    $DB->sql = "SELECT slipID FROM `" . $DB->pre . "salary_slip` WHERE userID = ? AND salaryMonth = ? AND salaryYear = ?";
    $existing = $DB->dbRow();

    $slipData = array(
        "userID" => $userID,
        "salaryMonth" => $month,
        "salaryYear" => $year,
        "structureID" => $structure['structureID'] ?? null,
        "basicSalary" => $basicSalary,
        "hra" => $hra,
        "conveyanceAllowance" => $conveyance,
        "medicalAllowance" => $medical,
        "specialAllowance" => $special,
        "otherAllowance" => $other,
        "totalEarnings" => $grossSalary,
        "leavesDeducted" => $absentDays + $halfDays,
        "leaveDeductionAmount" => $absentDeduction + $halfDayDeduction,
        "advanceDeduction" => $advanceDeduction,
        "otherDeduction" => $lateDeduction,
        "deductionRemarks" => $lateDays > 0 ? "Late deduction: {$lateDays} days" : null,
        "totalDeductions" => $totalDeductions,
        "netSalary" => $netSalary,
        "amountPaid" => $paidAmount,
        "workingDays" => $workingDays,
        "presentDays" => $presentDays,
        "absentDays" => $absentDays,
        "leavesTaken" => $leaveDays,
        "lateDays" => $lateDays,
        "slipStatus" => "paid",
        "paidOn" => $paidOn,
        "paidBy" => $paidBy,
        "paymentMode" => $paymentMode,
        "transactionRef" => $transactionRef
    );

    $DB->table = $DB->pre . "salary_slip";
    $DB->data = $slipData;

    if ($existing) {
        $DB->dbUpdate("slipID = ?", "i", array($existing['slipID']));
    } else {
        $DB->dbInsert();
    }

    return array('err' => 0, 'msg' => 'Salary marked as paid');
}

/**
 * Generate salary slip PDF (placeholder - returns success)
 */
function generateSalarySlipPDF()
{
    global $DB;

    if (!isHRMSLoggedIn() || !canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    $userID = intval($_POST['userID'] ?? 0);
    $month = intval($_POST['month'] ?? 0);
    $year = intval($_POST['year'] ?? 0);

    if (!$userID || !$month || !$year) {
        return array('err' => 1, 'msg' => 'Invalid parameters');
    }

    // Check if salary is paid
    $DB->vals = array($userID, $month, $year);
    $DB->types = "iii";
    $DB->sql = "SELECT slipID, slipStatus FROM `" . $DB->pre . "salary_slip` WHERE userID = ? AND salaryMonth = ? AND salaryYear = ?";
    $slip = $DB->dbRow();

    if (!$slip || $slip['slipStatus'] !== 'paid') {
        return array('err' => 1, 'msg' => 'Salary must be marked as paid before generating slip');
    }

    // Generate the actual PDF
    $result = createSalarySlipPDF($userID, $month, $year);

    if ($result['err'] == 0) {
        // Update status to slip_generated
        $DB->table = $DB->pre . "salary_slip";
        $DB->data = array(
            "slipStatus" => "slip_generated",
            "slipPDF" => $result['filename'],
            "generatedAt" => date('Y-m-d H:i:s')
        );
        $DB->dbUpdate("slipID = ?", "i", array($slip['slipID']));
    }

    return $result;
}

/**
 * Download salary slip PDF for employee
 */
function downloadSalarySlipPDF()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    $slipID = intval($_POST['slipID'] ?? 0);

    if (!$slipID) {
        return array('err' => 1, 'msg' => 'Invalid slip ID');
    }

    // Get slip and verify it belongs to the logged in user
    $userID = $_SESSION['HRMS_USER_ID'];
    $DB->vals = array($slipID, $userID);
    $DB->types = "ii";
    $DB->sql = "SELECT SS.*, U.displayName, U.employeeCode, U.designation, U.department,
                       U.bankName, U.bankAccountNo, U.bankIFSC, U.panNo
                FROM `" . $DB->pre . "salary_slip` SS
                LEFT JOIN `" . $DB->pre . "x_admin_user` U ON SS.userID = U.userID
                WHERE SS.slipID = ? AND SS.userID = ?";
    $slip = $DB->dbRow();

    if (!$slip) {
        return array('err' => 1, 'msg' => 'Salary slip not found');
    }

    if ($slip['slipStatus'] !== 'paid' && $slip['slipStatus'] !== 'slip_generated') {
        return array('err' => 1, 'msg' => 'Salary slip not yet available');
    }

    // Generate PDF
    $result = createSalarySlipPDF($slip['userID'], $slip['salaryMonth'], $slip['salaryYear']);

    return $result;
}

/**
 * Create styled salary slip PDF with logo
 */
function createSalarySlipPDF($userID, $month, $year)
{
    global $DB;

    // Get full slip data with employee details
    $DB->vals = array($userID, $month, $year);
    $DB->types = "iii";
    $DB->sql = "SELECT SS.*, U.displayName, U.employeeCode, U.designation, U.department,
                       U.bankName, U.bankAccountNo, U.bankIFSC, U.panNo
                FROM `" . $DB->pre . "salary_slip` SS
                LEFT JOIN `" . $DB->pre . "x_admin_user` U ON SS.userID = U.userID
                WHERE SS.userID = ? AND SS.salaryMonth = ? AND SS.salaryYear = ?";
    $slip = $DB->dbRow();

    if (!$slip) {
        return array('err' => 1, 'msg' => 'Salary slip not found');
    }

    // Generate PDF using MPDF
    require_once(ROOTPATH . '/vendor/autoload.php');

    $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                   'July', 'August', 'September', 'October', 'November', 'December'];
    $monthName = $monthNames[$month] ?? 'Unknown';

    $filename = "salary_slip_" . $userID . "_" . $year . "_" . str_pad($month, 2, '0', STR_PAD_LEFT) . ".pdf";
    $uploadDir = ROOTPATH . "/uploads/salary-slips/" . $year . "/" . $month . "/";
    $filepath = $uploadDir . $filename;

    // Create directory if not exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Logo path
    $logoPath = ROOTPATH . '/xsite/images/logo-icon.png';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }

    // Build HTML with styling
    $html = getSalarySlipStyledHTML($slip, $monthName, $logoBase64);

    try {
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => '/tmp',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($filepath, 'F');

        // Return download URL
        $downloadUrl = SITEURL . "/uploads/salary-slips/" . $year . "/" . $month . "/" . $filename;

        return array(
            'err' => 0,
            'msg' => 'PDF generated successfully',
            'filename' => $filename,
            'downloadUrl' => $downloadUrl
        );
    } catch (Exception $e) {
        return array('err' => 1, 'msg' => 'PDF generation failed: ' . $e->getMessage());
    }
}

/**
 * Generate styled HTML for salary slip PDF
 */
function getSalarySlipStyledHTML($slip, $monthName, $logoBase64)
{
    // Build earnings rows (skip zero values)
    $earningsHtml = '';
    $earningsHtml .= '<tr><td style="padding: 10px; border-bottom: 1px solid #e0e0e0;">Basic Salary</td>';
    $earningsHtml .= '<td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right;">₹' . number_format($slip['basicSalary'], 2) . '</td></tr>';

    if (floatval($slip['hra']) > 0) {
        $earningsHtml .= '<tr><td style="padding: 10px; border-bottom: 1px solid #e0e0e0;">HRA</td>';
        $earningsHtml .= '<td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right;">₹' . number_format($slip['hra'], 2) . '</td></tr>';
    }
    if (floatval($slip['conveyanceAllowance']) > 0) {
        $earningsHtml .= '<tr><td style="padding: 10px; border-bottom: 1px solid #e0e0e0;">Conveyance Allowance</td>';
        $earningsHtml .= '<td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right;">₹' . number_format($slip['conveyanceAllowance'], 2) . '</td></tr>';
    }
    if (floatval($slip['medicalAllowance']) > 0) {
        $earningsHtml .= '<tr><td style="padding: 10px; border-bottom: 1px solid #e0e0e0;">Medical Allowance</td>';
        $earningsHtml .= '<td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right;">₹' . number_format($slip['medicalAllowance'], 2) . '</td></tr>';
    }
    if (floatval($slip['specialAllowance']) > 0) {
        $earningsHtml .= '<tr><td style="padding: 10px; border-bottom: 1px solid #e0e0e0;">Special Allowance</td>';
        $earningsHtml .= '<td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right;">₹' . number_format($slip['specialAllowance'], 2) . '</td></tr>';
    }
    if (floatval($slip['otherAllowance']) > 0) {
        $earningsHtml .= '<tr><td style="padding: 10px; border-bottom: 1px solid #e0e0e0;">Other Allowance</td>';
        $earningsHtml .= '<td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right;">₹' . number_format($slip['otherAllowance'], 2) . '</td></tr>';
    }

    // Build deductions rows (skip zero values)
    $deductionsHtml = '';
    if (floatval($slip['leaveDeductionAmount']) > 0) {
        $deductionsHtml .= '<tr><td style="padding: 10px; border-bottom: 1px solid #e0e0e0;">Leave Deduction (' . intval($slip['leavesDeducted']) . ' days)</td>';
        $deductionsHtml .= '<td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right; color: #c62828;">-₹' . number_format($slip['leaveDeductionAmount'], 2) . '</td></tr>';
    }
    if (floatval($slip['advanceDeduction']) > 0) {
        $deductionsHtml .= '<tr><td style="padding: 10px; border-bottom: 1px solid #e0e0e0;">Advance Deduction</td>';
        $deductionsHtml .= '<td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right; color: #c62828;">-₹' . number_format($slip['advanceDeduction'], 2) . '</td></tr>';
    }
    if (floatval($slip['otherDeduction'] ?? 0) > 0) {
        $deductionsHtml .= '<tr><td style="padding: 10px; border-bottom: 1px solid #e0e0e0;">Other Deductions</td>';
        $deductionsHtml .= '<td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right; color: #c62828;">-₹' . number_format($slip['otherDeduction'], 2) . '</td></tr>';
    }

    // If no deductions, show "No Deductions"
    if (empty($deductionsHtml)) {
        $deductionsHtml = '<tr><td style="padding: 10px; border-bottom: 1px solid #e0e0e0; color: #666;">No Deductions</td>';
        $deductionsHtml .= '<td style="padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: right;">₹0.00</td></tr>';
    }

    $paidOnFormatted = $slip['paidOn'] ? date('d M Y', strtotime($slip['paidOn'])) : '-';

    $html = '
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            background: #ffffff;
            color: #1565C0;
            padding: 20px 25px 15px 25px;
            margin: -15px -15px 20px -15px;
            text-align: center;
            border-bottom: 3px solid #1976D2;
        }
        .slip-title {
            font-size: 18px;
            font-weight: bold;
            color: #1565C0;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0;
        }
        .slip-period {
            font-size: 15px;
            margin-top: 8px;
            font-weight: 600;
            color: #666;
        }
        .employee-info {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-grid {
            width: 100%;
        }
        .info-grid td {
            padding: 6px 10px;
            vertical-align: top;
        }
        .info-label {
            color: #666;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            color: #333;
            font-weight: 500;
            font-size: 12px;
        }
        .section-title {
            background: #1976D2;
            color: white;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 4px 4px 0 0;
            margin-bottom: 0;
        }
        .section-content {
            border: 1px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 4px 4px;
            margin-bottom: 20px;
        }
        .section-table {
            width: 100%;
            border-collapse: collapse;
        }
        .section-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #e8e8e8;
        }
        .section-table tr:last-child td {
            border-bottom: none;
        }
        .total-row {
            background: #e3f2fd;
            font-weight: bold;
        }
        .total-row td {
            padding: 12px 15px !important;
        }
        .net-salary-box {
            background: linear-gradient(135deg, #1565C0 0%, #1976D2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin: 20px 0;
        }
        .net-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .net-amount {
            font-size: 28px;
            font-weight: bold;
        }
        .attendance-grid {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .attendance-grid td {
            padding: 12px 15px;
            text-align: center;
            border-right: 1px solid #e0e0e0;
        }
        .attendance-grid td:last-child {
            border-right: none;
        }
        .att-value {
            font-size: 18px;
            font-weight: bold;
            color: #1976D2;
        }
        .att-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 10px;
        }
        .payment-info {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .payment-title {
            color: #2e7d32;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
        }
    </style>

    <div class="header">
        <div style="text-align: center; margin-bottom: 15px;">
            ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" style="width: 200px; height: auto;">' : '') . '
        </div>
        <div class="slip-title">Salary Slip</div>
        <div class="slip-period">' . $monthName . ' ' . $slip['salaryYear'] . '</div>
    </div>

    <div class="employee-info">
        <table class="info-grid">
            <tr>
                <td style="width: 50%;">
                    <div class="info-label">Employee Name</div>
                    <div class="info-value">' . htmlspecialchars($slip['displayName'] ?? '-') . '</div>
                </td>
                <td style="width: 50%;">
                    <div class="info-label">Employee Code</div>
                    <div class="info-value">' . htmlspecialchars($slip['employeeCode'] ?? '-') . '</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="info-label">Designation</div>
                    <div class="info-value">' . htmlspecialchars($slip['designation'] ?? '-') . '</div>
                </td>
                <td>
                    <div class="info-label">Department</div>
                    <div class="info-value">' . htmlspecialchars($slip['department'] ?? '-') . '</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="info-label">Bank Account</div>
                    <div class="info-value">' . htmlspecialchars($slip['bankName'] ?? '-') . ' - ' . htmlspecialchars($slip['bankAccountNo'] ?? '-') . '</div>
                </td>
                <td>
                    <div class="info-label">PAN Number</div>
                    <div class="info-value">' . htmlspecialchars($slip['panNo'] ?? '-') . '</div>
                </td>
            </tr>
        </table>
    </div>

    <table style="width: 100%;">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div class="section-title">Earnings</div>
                <div class="section-content">
                    <table class="section-table">
                        ' . $earningsHtml . '
                        <tr class="total-row">
                            <td>Total Earnings</td>
                            <td style="text-align: right; color: #2e7d32;">₹' . number_format($slip['totalEarnings'], 2) . '</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top;">
                <div class="section-title">Deductions</div>
                <div class="section-content">
                    <table class="section-table">
                        ' . $deductionsHtml . '
                        <tr class="total-row">
                            <td>Total Deductions</td>
                            <td style="text-align: right; color: #c62828;">-₹' . number_format($slip['totalDeductions'], 2) . '</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="net-salary-box">
        <div class="net-label">Net Salary Payable</div>
        <div class="net-amount">₹' . number_format($slip['netSalary'], 2) . '</div>
    </div>

    <div class="section-title" style="margin-bottom: 0;">Attendance Summary</div>
    <table class="attendance-grid" style="margin-bottom: 20px;">
        <tr>
            <td>
                <div class="att-value">' . intval($slip['workingDays']) . '</div>
                <div class="att-label">Working Days</div>
            </td>
            <td>
                <div class="att-value">' . intval($slip['presentDays']) . '</div>
                <div class="att-label">Present</div>
            </td>
            <td>
                <div class="att-value">' . intval($slip['absentDays']) . '</div>
                <div class="att-label">Absent</div>
            </td>
            <td>
                <div class="att-value">' . intval($slip['leavesTaken'] ?? 0) . '</div>
                <div class="att-label">Leaves</div>
            </td>
            <td>
                <div class="att-value">' . intval($slip['lateDays'] ?? 0) . '</div>
                <div class="att-label">Late Days</div>
            </td>
        </tr>
    </table>

    ' . ($slip['paidOn'] ? '
    <div class="payment-info">
        <div class="payment-title">✓ Payment Confirmed</div>
        <table style="width: 100%;">
            <tr>
                <td style="width: 33%;"><strong>Paid On:</strong> ' . $paidOnFormatted . '</td>
                <td style="width: 33%;"><strong>Mode:</strong> ' . htmlspecialchars(ucfirst(str_replace('_', ' ', $slip['paymentMode'] ?? '-'))) . '</td>
                <td style="width: 34%;"><strong>Ref:</strong> ' . htmlspecialchars($slip['transactionRef'] ?? '-') . '</td>
            </tr>
        </table>
    </div>
    ' : '') . '

    <div class="footer">
        This is a computer-generated salary slip and does not require a signature.<br>
        For any discrepancies, please contact HR.<br><br>
        <span style="color: #999;">Generated on ' . date('d M Y, h:i A') . '</span>
    </div>
    ';

    return $html;
}

/**
 * Get all salary advances
 */
function getSalaryAdvances()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    if (!canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT a.*, u.displayName as employeeName
                FROM `" . $DB->pre . "salary_advance` a
                LEFT JOIN `" . $DB->pre . "x_admin_user` u ON a.userID = u.userID
                WHERE a.status = ?
                ORDER BY a.advanceDate DESC";
    $advances = $DB->dbRows();

    // Calculate stats
    $activeCount = 0;
    $totalOutstanding = 0;
    $thisMonthDeductions = 0;
    $currentMonth = intval(date('n'));
    $currentYear = intval(date('Y'));

    foreach ($advances as &$adv) {
        if (in_array($adv['advanceStatus'], ['approved', 'repaying'])) {
            $activeCount++;
            $totalOutstanding += floatval($adv['remainingAmount'] ?? 0);

            // Check if deduction applies this month based on frequency
            $deductMonth = intval($adv['deductFromMonth'] ?? 1);
            $deductYear = intval($adv['deductFromYear'] ?? $currentYear);
            $freq = $adv['deductionFrequency'] ?? 'monthly';
            $customMonths = intval($adv['customMonths'] ?? 1);

            if (shouldDeductThisMonth($currentMonth, $currentYear, $deductMonth, $deductYear, $freq, $customMonths)) {
                $thisMonthDeductions += floatval($adv['monthlyDeduction'] ?? 0);
            }
        }
    }

    return array(
        'err' => 0,
        'advances' => $advances,
        'stats' => array(
            'activeCount' => $activeCount,
            'totalOutstanding' => $totalOutstanding,
            'monthlyDeductions' => $thisMonthDeductions
        )
    );
}

/**
 * Check if deduction should happen this month based on frequency
 */
function shouldDeductThisMonth($currentMonth, $currentYear, $startMonth, $startYear, $frequency, $customMonths = 1)
{
    // If current date is before start date, no deduction
    if ($currentYear < $startYear || ($currentYear == $startYear && $currentMonth < $startMonth)) {
        return false;
    }

    // Calculate months since start
    $monthsSinceStart = (($currentYear - $startYear) * 12) + ($currentMonth - $startMonth);

    switch ($frequency) {
        case 'one_time':
            return $monthsSinceStart == 0; // Only in the first month
        case 'monthly':
            return true; // Every month
        case 'quarterly':
            return $monthsSinceStart % 3 == 0;
        case 'half_yearly':
            return $monthsSinceStart % 6 == 0;
        case 'yearly':
            return $monthsSinceStart % 12 == 0;
        case 'custom':
            return $monthsSinceStart % $customMonths == 0;
        default:
            return true;
    }
}

/**
 * Save salary advance
 */
function saveSalaryAdvance()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    if (!canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    $advanceID = intval($_POST['advanceID'] ?? 0);
    $userID = intval($_POST['userID'] ?? 0);
    $advanceAmount = floatval($_POST['advanceAmount'] ?? 0);
    $advanceDate = $_POST['advanceDate'] ?? date('Y-m-d');
    $reason = trim($_POST['reason'] ?? '');
    $deductionFrequency = $_POST['deductionFrequency'] ?? 'monthly';
    $customMonths = intval($_POST['customMonths'] ?? 1);
    $monthlyDeduction = floatval($_POST['monthlyDeduction'] ?? 0);
    $deductFromMonth = intval($_POST['deductFromMonth'] ?? (date('n') + 1));
    $deductFromYear = intval($_POST['deductFromYear'] ?? date('Y'));

    // Adjust if month overflows
    if ($deductFromMonth > 12) {
        $deductFromMonth = 1;
        $deductFromYear++;
    }

    if (!$userID || $advanceAmount <= 0) {
        return array('err' => 1, 'msg' => 'Invalid employee or amount');
    }

    if ($deductionFrequency !== 'one_time' && $monthlyDeduction <= 0) {
        return array('err' => 1, 'msg' => 'Deduction amount is required');
    }

    // For one-time, deduction amount = full amount
    if ($deductionFrequency === 'one_time') {
        $monthlyDeduction = $advanceAmount;
    }

    $adminUserID = $_SESSION['HRMS_USER_ID'];

    if ($advanceID > 0) {
        // Update existing advance
        $DB->vals = array(
            $userID, $advanceAmount, $advanceDate, $reason,
            $monthlyDeduction, $deductionFrequency, $customMonths,
            $deductFromMonth, $deductFromYear, $advanceAmount, $advanceID
        );
        $DB->types = "idssdsiidii";
        $DB->sql = "UPDATE `" . $DB->pre . "salary_advance`
                    SET userID = ?, advanceAmount = ?, advanceDate = ?, reason = ?,
                        monthlyDeduction = ?, deductionFrequency = ?, customMonths = ?,
                        deductFromMonth = ?, deductFromYear = ?, remainingAmount = ?
                    WHERE advanceID = ?";
        $DB->dbQuery();
    } else {
        // Insert new advance (auto-approve for HR admin)
        $DB->vals = array(
            $userID, $advanceAmount, $advanceDate, $reason,
            $adminUserID, $deductFromMonth, $deductFromYear,
            $monthlyDeduction, $deductionFrequency, $customMonths,
            $advanceAmount, 'approved'
        );
        $DB->types = "idssiiiidsds";
        $DB->sql = "INSERT INTO `" . $DB->pre . "salary_advance`
                    (userID, advanceAmount, advanceDate, reason, approvedBy, deductFromMonth, deductFromYear,
                     monthlyDeduction, deductionFrequency, customMonths, remainingAmount, advanceStatus, approvedAt)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $DB->dbQuery();
    }

    return array('err' => 0, 'msg' => 'Salary advance saved successfully');
}

/**
 * Approve salary advance
 */
function approveSalaryAdvance()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    if (!canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    $advanceID = intval($_POST['advanceID'] ?? 0);
    if (!$advanceID) {
        return array('err' => 1, 'msg' => 'Invalid advance ID');
    }

    $adminUserID = $_SESSION['HRMS_USER_ID'];

    $DB->vals = array($adminUserID, date('Y-m-d H:i:s'), $advanceID);
    $DB->types = "isi";
    $DB->sql = "UPDATE `" . $DB->pre . "salary_advance`
                SET advanceStatus = 'approved', approvedBy = ?, approvedDate = ?
                WHERE advanceID = ?";
    $DB->dbQuery();

    return array('err' => 0, 'msg' => 'Advance approved');
}

/**
 * Record advance repayment
 */
function recordAdvanceRepayment()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    if (!canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    $advanceID = intval($_POST['advanceID'] ?? 0);
    $repaymentAmount = floatval($_POST['repaymentAmount'] ?? 0);
    $repaymentMode = $_POST['repaymentMode'] ?? 'salary_deduction';
    $remarks = trim($_POST['remarks'] ?? '');

    if (!$advanceID || $repaymentAmount <= 0) {
        return array('err' => 1, 'msg' => 'Invalid advance ID or amount');
    }

    // Get the advance
    $DB->vals = array($advanceID);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "salary_advance` WHERE advanceID = ?";
    $advance = $DB->dbRow();

    if (!$advance) {
        return array('err' => 1, 'msg' => 'Advance not found');
    }

    $remainingAmount = floatval($advance['remainingAmount'] ?? 0);
    if ($repaymentAmount > $remainingAmount) {
        $repaymentAmount = $remainingAmount; // Cap at balance
    }

    $newRemaining = $remainingAmount - $repaymentAmount;
    $newTotalDeducted = floatval($advance['totalDeducted'] ?? 0) + $repaymentAmount;
    $newStatus = ($newRemaining <= 0) ? 'completed' : 'repaying';

    // Insert repayment record
    $DB->vals = array(
        $advanceID, $advance['userID'], $repaymentAmount, date('Y-m-d'),
        date('n'), date('Y'), $repaymentMode, $remarks
    );
    $DB->types = "iidsiiss";
    $DB->sql = "INSERT INTO `" . $DB->pre . "salary_advance_repayment`
                (advanceID, userID, repaymentAmount, repaymentDate, repaymentMonth, repaymentYear, repaymentMode, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $DB->dbQuery();

    // Update advance balance
    $DB->vals = array($newTotalDeducted, $newRemaining, $newStatus, $advanceID);
    $DB->types = "ddsi";
    $DB->sql = "UPDATE `" . $DB->pre . "salary_advance`
                SET totalDeducted = ?, remainingAmount = ?, advanceStatus = ?
                WHERE advanceID = ?";
    $DB->dbQuery();

    return array('err' => 0, 'msg' => 'Repayment recorded successfully');
}

/**
 * Get pending advance deduction for a user for salary processing
 * Takes into account deduction frequency
 */
function getAdvanceDeductionForSalary($userID, $month = null, $year = null)
{
    global $DB;

    $currentMonth = $month ?? intval(date('n'));
    $currentYear = $year ?? intval(date('Y'));

    $DB->vals = array($userID);
    $DB->types = "i";
    $DB->sql = "SELECT advanceID, monthlyDeduction, deductionFrequency, customMonths,
                       deductFromMonth, deductFromYear, remainingAmount
                FROM `" . $DB->pre . "salary_advance`
                WHERE userID = ? AND advanceStatus IN ('approved', 'repaying') AND remainingAmount > 0";
    $advances = $DB->dbRows();

    $totalDeduction = 0;
    foreach ($advances as $adv) {
        $deductMonth = intval($adv['deductFromMonth'] ?? 1);
        $deductYear = intval($adv['deductFromYear'] ?? $currentYear);
        $freq = $adv['deductionFrequency'] ?? 'monthly';
        $customMonths = intval($adv['customMonths'] ?? 1);
        $monthlyDeduction = floatval($adv['monthlyDeduction'] ?? 0);
        $remainingAmount = floatval($adv['remainingAmount'] ?? 0);

        // Check if deduction should happen this month
        if (shouldDeductThisMonth($currentMonth, $currentYear, $deductMonth, $deductYear, $freq, $customMonths)) {
            // Cap deduction at remaining amount
            $deduction = min($monthlyDeduction, $remainingAmount);
            $totalDeduction += $deduction;
        }
    }

    return $totalDeduction;
}

// ============================================================================
// ICICI BANK CMS FILE GENERATION
// ============================================================================

/**
 * Get ICICI Bank Settings
 */
function getICICIBankSettings()
{
    global $DB;

    if (!isHRMSLoggedIn() || !canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    // Get settings from hrms_settings table
    $DB->vals = array('icici_%');
    $DB->types = "s";
    $DB->sql = "SELECT settingKey, settingValue FROM `" . $DB->pre . "hrms_settings` WHERE settingKey LIKE ?";
    $rows = $DB->dbRows();

    $settings = array(
        'companyCode' => '',
        'debitAccount' => '',
        'companyName' => 'BOMBAY ENGINEERING SYNDICATE',
        'encryptionKey' => false // Boolean: whether key is set (don't expose actual key)
    );

    foreach ($rows as $row) {
        switch ($row['settingKey']) {
            case 'icici_company_code':
                $settings['companyCode'] = $row['settingValue'];
                break;
            case 'icici_debit_account':
                $settings['debitAccount'] = $row['settingValue'];
                break;
            case 'icici_company_name':
                $settings['companyName'] = $row['settingValue'];
                break;
            case 'icici_encryption_key':
                // Don't expose the actual key - just indicate if it's set
                $settings['encryptionKey'] = !empty($row['settingValue']);
                break;
        }
    }

    return array('err' => 0, 'settings' => $settings);
}

/**
 * Save ICICI Bank Settings
 */
function saveICICIBankSettings()
{
    global $DB;

    if (!isHRMSLoggedIn() || !canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    $companyCode = trim($_POST['companyCode'] ?? '');
    $debitAccount = trim($_POST['debitAccount'] ?? '');
    $companyName = trim($_POST['companyName'] ?? 'BOMBAY ENGINEERING SYNDICATE');

    // Check if hrms_settings table exists, if not create it
    $DB->sql = "CREATE TABLE IF NOT EXISTS `" . $DB->pre . "hrms_settings` (
        settingID INT AUTO_INCREMENT PRIMARY KEY,
        settingKey VARCHAR(100) NOT NULL UNIQUE,
        settingValue TEXT,
        settingDescription VARCHAR(255),
        updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $DB->dbQuery();

    // Get encryption key (optional - for AES encryption)
    $encryptionKey = trim($_POST['encryptionKey'] ?? '');

    // Upsert settings
    $settings = array(
        'icici_company_code' => $companyCode,
        'icici_debit_account' => $debitAccount,
        'icici_company_name' => $companyName,
        'icici_encryption_key' => $encryptionKey
    );

    foreach ($settings as $key => $value) {
        $DB->sql = "INSERT INTO `" . $DB->pre . "hrms_settings` (settingKey, settingValue)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE settingValue = ?";
        $DB->vals = array($key, $value, $value);
        $DB->types = "sss";
        $DB->dbQuery();
    }

    return array('err' => 0, 'msg' => 'Settings saved successfully');
}

/**
 * Encrypt content using AES-256-CBC for ICICI CMS file
 *
 * @param string $plaintext The plaintext content to encrypt
 * @param string $key The encryption key/password
 * @return array Array with encrypted content (base64) or error
 */
function encryptCMSContent($plaintext, $key)
{
    if (empty($key)) {
        return array('err' => 1, 'msg' => 'Encryption key is required');
    }

    // AES-256-CBC requires 32-byte key
    // Hash the password to get consistent 32-byte key
    $keyBytes = hash('sha256', $key, true);

    // Generate random 16-byte IV
    $iv = openssl_random_pseudo_bytes(16);

    // Encrypt using AES-256-CBC with PKCS7 padding (default in openssl)
    $encrypted = openssl_encrypt(
        $plaintext,
        'aes-256-cbc',
        $keyBytes,
        OPENSSL_RAW_DATA,
        $iv
    );

    if ($encrypted === false) {
        return array('err' => 1, 'msg' => 'Encryption failed: ' . openssl_error_string());
    }

    // Prepend IV to encrypted data (IV + Encrypted-data)
    $encryptedWithIV = $iv . $encrypted;

    // Base64 encode the final result
    $base64Encrypted = base64_encode($encryptedWithIV);

    return array(
        'err' => 0,
        'encrypted' => $base64Encrypted,
        'rawBytes' => $encryptedWithIV // For binary file download
    );
}

/**
 * Generate ICICI Bank CMS File for Bulk Salary Payment
 *
 * ICICI CMS Format (Pipe-delimited):
 * Header: PAYMENT|SALARY|{CompanyCode}|{DebitAccount}|{PaymentDate}|{BatchRef}|{TotalAmount}|{TotalCount}
 * Detail: {BeneName}|{BeneAccNo}|{BeneIFSC}|{Amount}|{Remarks}|{EmailID}|{MobileNo}
 */
function generateICICICMSFile()
{
    global $DB;

    if (!isHRMSLoggedIn() || !canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied. Only HR Admin and Accounts can generate CMS file.');
    }

    $month = intval($_POST['month'] ?? date('n'));
    $year = intval($_POST['year'] ?? date('Y'));
    $paymentDate = trim($_POST['paymentDate'] ?? date('d-m-Y'));
    $selectedEmployees = $_POST['employeeIds'] ?? array(); // Optional: specific employees

    // Get bank settings
    $settingsResult = getICICIBankSettings();
    if ($settingsResult['err'] != 0) {
        return $settingsResult;
    }
    $settings = $settingsResult['settings'];

    if (empty($settings['companyCode']) || empty($settings['debitAccount'])) {
        return array('err' => 1, 'msg' => 'Please configure ICICI Bank settings first (Company Code and Debit Account)');
    }

    // Get all paid salaries for this month with bank details
    $startDate = sprintf('%04d-%02d-01', $year, $month);

    $sql = "SELECT u.userID, u.userName, u.displayName, u.employeeCode,
                   u.bankName, u.bankAccountNo, u.bankIFSC, u.userEmail, u.userPhone,
                   ss.amountPaid, ss.netSalary, ss.slipStatus
            FROM `" . $DB->pre . "salary_slip` ss
            JOIN `" . $DB->pre . "x_admin_user` u ON ss.userID = u.userID
            WHERE ss.salaryMonth = ? AND ss.salaryYear = ?
            AND ss.slipStatus IN ('paid', 'slip_generated')
            AND u.status = 1
            ORDER BY u.displayName";

    $DB->vals = array($month, $year);
    $DB->types = "ii";
    $DB->sql = $sql;
    $employees = $DB->dbRows();

    if (empty($employees)) {
        return array('err' => 1, 'msg' => 'No paid salaries found for ' . date('F Y', strtotime($startDate)));
    }

    // Filter by selected employees if provided
    if (!empty($selectedEmployees) && is_array($selectedEmployees)) {
        $employees = array_filter($employees, function ($emp) use ($selectedEmployees) {
            return in_array($emp['userID'], $selectedEmployees);
        });
    }

    // Validate and prepare data
    $validEmployees = array();
    $invalidEmployees = array();
    $totalAmount = 0;

    foreach ($employees as $emp) {
        $bankAccount = trim($emp['bankAccountNo'] ?? '');
        $ifsc = trim($emp['bankIFSC'] ?? '');
        $amount = floatval($emp['amountPaid'] ?? $emp['netSalary'] ?? 0);

        if (empty($bankAccount) || empty($ifsc) || $amount <= 0) {
            $invalidEmployees[] = array(
                'name' => $emp['displayName'] ?: $emp['userName'],
                'reason' => empty($bankAccount) ? 'No bank account' : (empty($ifsc) ? 'No IFSC code' : 'Invalid amount')
            );
            continue;
        }

        // Validate IFSC format (11 characters, first 4 letters, 5th is 0, last 6 alphanumeric)
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/i', $ifsc)) {
            $invalidEmployees[] = array(
                'name' => $emp['displayName'] ?: $emp['userName'],
                'reason' => 'Invalid IFSC format'
            );
            continue;
        }

        $validEmployees[] = array(
            'userID' => $emp['userID'],
            'name' => strtoupper($emp['displayName'] ?: $emp['userName']),
            'accountNo' => $bankAccount,
            'ifsc' => strtoupper($ifsc),
            'amount' => number_format($amount, 2, '.', ''),
            'email' => $emp['userEmail'] ?? '',
            'phone' => preg_replace('/[^0-9]/', '', $emp['userPhone'] ?? '')
        );

        $totalAmount += $amount;
    }

    if (empty($validEmployees)) {
        return array(
            'err' => 1,
            'msg' => 'No employees with valid bank details found',
            'invalid' => $invalidEmployees
        );
    }

    // Generate batch reference
    $monthNames = array('', 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC');
    $batchRef = 'SAL-' . $monthNames[$month] . $year;

    // Generate CMS file content
    $cmsLines = array();

    // Header record
    $header = array(
        'PAYMENT',
        'SALARY',
        $settings['companyCode'],
        $settings['debitAccount'],
        $paymentDate,
        $batchRef,
        number_format($totalAmount, 2, '.', ''),
        count($validEmployees)
    );
    $cmsLines[] = implode('|', $header);

    // Salary narration
    $salaryNarration = 'SALARY ' . $monthNames[$month] . ' ' . $year;

    // Detail records
    foreach ($validEmployees as $emp) {
        $detail = array(
            $emp['name'],
            $emp['accountNo'],
            $emp['ifsc'],
            $emp['amount'],
            $salaryNarration,
            $emp['email'],
            $emp['phone']
        );
        $cmsLines[] = implode('|', $detail);
    }

    $cmsContent = implode("\n", $cmsLines);

    // Check if encryption is required
    $encryptFile = isset($_POST['encrypt']) && $_POST['encrypt'] == '1';
    $encryptionKey = $settings['encryptionKey'] ?? '';

    // Generate filename
    $baseFilename = 'ICICI_CMS_SAL_' . $monthNames[$month] . '_' . $year . '_' . date('Ymd_His');

    // If encryption requested
    if ($encryptFile) {
        if (empty($encryptionKey)) {
            return array('err' => 1, 'msg' => 'Encryption key not configured. Please set the encryption key in ICICI Bank Settings.');
        }

        $encryptResult = encryptCMSContent($cmsContent, $encryptionKey);
        if ($encryptResult['err'] != 0) {
            return $encryptResult;
        }

        return array(
            'err' => 0,
            'msg' => 'Encrypted CMS file generated successfully',
            'filename' => $baseFilename . '.enc',
            'content' => $encryptResult['encrypted'], // Base64 encoded for transfer
            'encrypted' => true,
            'plainContent' => $cmsContent, // For preview only (not included in download)
            'summary' => array(
                'totalEmployees' => count($validEmployees),
                'totalAmount' => $totalAmount,
                'formattedAmount' => '₹' . number_format($totalAmount, 2),
                'batchRef' => $batchRef,
                'paymentDate' => $paymentDate,
                'invalidCount' => count($invalidEmployees),
                'invalid' => $invalidEmployees
            )
        );
    }

    // Return plain text file (unencrypted)
    return array(
        'err' => 0,
        'msg' => 'CMS file generated successfully',
        'filename' => $baseFilename . '.txt',
        'content' => $cmsContent,
        'encrypted' => false,
        'summary' => array(
            'totalEmployees' => count($validEmployees),
            'totalAmount' => $totalAmount,
            'formattedAmount' => '₹' . number_format($totalAmount, 2),
            'batchRef' => $batchRef,
            'paymentDate' => $paymentDate,
            'invalidCount' => count($invalidEmployees),
            'invalid' => $invalidEmployees
        )
    );
}

/**
 * Preview CMS data before generating file
 */
function previewICICICMSData()
{
    global $DB;

    if (!isHRMSLoggedIn() || !canAccessSalaryProcessing()) {
        return array('err' => 1, 'msg' => 'Access denied');
    }

    $month = intval($_POST['month'] ?? date('n'));
    $year = intval($_POST['year'] ?? date('Y'));

    $startDate = sprintf('%04d-%02d-01', $year, $month);

    // Get all paid salaries with bank details
    $sql = "SELECT u.userID, u.userName, u.displayName, u.employeeCode,
                   u.bankName, u.bankAccountNo, u.bankIFSC,
                   ss.amountPaid, ss.netSalary, ss.slipStatus
            FROM `" . $DB->pre . "salary_slip` ss
            JOIN `" . $DB->pre . "x_admin_user` u ON ss.userID = u.userID
            WHERE ss.salaryMonth = ? AND ss.salaryYear = ?
            AND ss.slipStatus IN ('paid', 'slip_generated')
            AND u.status = 1
            ORDER BY u.displayName";

    $DB->vals = array($month, $year);
    $DB->types = "ii";
    $DB->sql = $sql;
    $employees = $DB->dbRows();

    $preview = array();
    $validCount = 0;
    $invalidCount = 0;
    $totalAmount = 0;

    foreach ($employees as $emp) {
        $bankAccount = trim($emp['bankAccountNo'] ?? '');
        $ifsc = trim($emp['bankIFSC'] ?? '');
        $amount = floatval($emp['amountPaid'] ?? $emp['netSalary'] ?? 0);

        $isValid = !empty($bankAccount) && !empty($ifsc) && $amount > 0;
        if ($isValid) {
            $isValid = preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/i', $ifsc);
        }

        $status = 'valid';
        $reason = '';
        if (empty($bankAccount)) {
            $status = 'invalid';
            $reason = 'No bank account';
        } elseif (empty($ifsc)) {
            $status = 'invalid';
            $reason = 'No IFSC code';
        } elseif (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/i', $ifsc)) {
            $status = 'invalid';
            $reason = 'Invalid IFSC format';
        } elseif ($amount <= 0) {
            $status = 'invalid';
            $reason = 'Invalid amount';
        }

        if ($status === 'valid') {
            $validCount++;
            $totalAmount += $amount;
        } else {
            $invalidCount++;
        }

        $preview[] = array(
            'userID' => $emp['userID'],
            'name' => $emp['displayName'] ?: $emp['userName'],
            'employeeCode' => $emp['employeeCode'] ?? '',
            'bankName' => $emp['bankName'] ?? '',
            'accountNo' => $bankAccount ? substr($bankAccount, 0, 4) . '****' . substr($bankAccount, -4) : '-',
            'ifsc' => $ifsc ?: '-',
            'amount' => $amount,
            'status' => $status,
            'reason' => $reason
        );
    }

    return array(
        'err' => 0,
        'employees' => $preview,
        'summary' => array(
            'validCount' => $validCount,
            'invalidCount' => $invalidCount,
            'totalAmount' => $totalAmount,
            'formattedAmount' => '₹' . number_format($totalAmount, 2)
        )
    );
}

// =============================================================================
// ENHANCED LEAVE MANAGEMENT API FUNCTIONS
// =============================================================================

/**
 * Get comprehensive leave balance summary for logged-in user
 */
function getLeaveBalanceSummaryAPI()
{
    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    $userID = $_SESSION['HRMS_USER_ID'];
    $summary = getLeaveBalanceSummary($userID);

    return array('err' => 0, 'data' => $summary);
}

/**
 * Get comp-off balance for logged-in user
 */
function getCompOffBalanceAPI()
{
    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    $userID = $_SESSION['HRMS_USER_ID'];
    $balance = getCompOffBalance($userID);

    return array('err' => 0, 'data' => $balance);
}

/**
 * Get comp-off history for logged-in user
 */
function getCompOffHistoryAPI()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    $userID = $_SESSION['HRMS_USER_ID'];
    $status = $_POST['status'] ?? null;

    $history = getCompOffHistory($userID, $status);

    // Format for display
    $formatted = array();
    foreach ($history as $co) {
        $formatted[] = array(
            'compOffID' => $co['compOffID'],
            'workDate' => date('d M Y', strtotime($co['workDate'])),
            'workDateRaw' => $co['workDate'],
            'workReason' => $co['workReason'],
            'hoursWorked' => $co['hoursWorked'],
            'compOffDays' => $co['compOffDays'],
            'expiryDate' => date('d M Y', strtotime($co['expiryDate'])),
            'status' => ucfirst($co['compOffStatus']),
            'statusClass' => getCompOffStatusClass($co['compOffStatus']),
            'approverName' => $co['approverName'] ?? '-',
            'remarks' => $co['remarks'] ?? ''
        );
    }

    return array('err' => 0, 'history' => $formatted);
}

/**
 * Get CSS class for comp-off status
 */
function getCompOffStatusClass($status)
{
    switch ($status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'used': return 'info';
        case 'expired': return 'secondary';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
}

/**
 * Apply for comp-off
 */
function applyCompOffAPI()
{
    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    $userID = $_SESSION['HRMS_USER_ID'];
    $workDate = $_POST['workDate'] ?? '';
    $workReason = trim($_POST['workReason'] ?? '');
    $hoursWorked = floatval($_POST['hoursWorked'] ?? 8);

    if (empty($workDate)) {
        return array('err' => 1, 'msg' => 'Work date is required');
    }

    if (empty($workReason)) {
        return array('err' => 1, 'msg' => 'Work reason is required');
    }

    return applyCompOff($userID, $workDate, $workReason, $hoursWorked);
}

/**
 * Process (approve/reject) comp-off request - for managers
 */
function processCompOffAPI()
{
    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    // Only managers or HR Admin can process
    $isManager = $_SESSION['HRMS_IS_MANAGER'] ?? false;
    $isHRAdmin = isHRMasterAdmin();

    if (!$isManager && !$isHRAdmin) {
        return array('err' => 1, 'msg' => 'Not authorized to process comp-off requests');
    }

    $compOffID = intval($_POST['compOffID'] ?? 0);
    $action = $_POST['action'] ?? ''; // 'approve' or 'reject'
    $remarks = trim($_POST['remarks'] ?? '');
    $approverID = $_SESSION['HRMS_USER_ID'];

    if (!$compOffID) {
        return array('err' => 1, 'msg' => 'Invalid comp-off ID');
    }

    if (!in_array($action, ['approve', 'reject'])) {
        return array('err' => 1, 'msg' => 'Invalid action');
    }

    return processCompOffRequest($compOffID, $action, $approverID, $remarks);
}

/**
 * Get encashment eligibility for logged-in user
 */
function getEncashmentEligibilityAPI()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    $userID = $_SESSION['HRMS_USER_ID'];
    $fy = getCurrentFinancialYear();

    // Get Earned Leave type (the main encashable leave)
    $DB->vals = array(1, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT leaveTypeID, leaveTypeName, encashmentRate, maxCarryForward
                FROM " . $DB->pre . "leave_type
                WHERE isEncashable = ? AND status = ?
                AND (leaveTypeName LIKE '%Earned%' OR leaveTypeName LIKE '%Privilege%')
                LIMIT 1";
    $elType = $DB->dbRow();

    if (!$elType) {
        return array('err' => 0, 'data' => array(
            'elBalance' => 0,
            'maxCarryForward' => 30,
            'eligibleDays' => 0,
            'estimatedAmount' => 0
        ));
    }

    // Get current EL balance
    $DB->vals = array($userID, $elType['leaveTypeID'], $fy);
    $DB->types = "iis";
    $DB->sql = "SELECT closingBalance FROM " . $DB->pre . "employee_leave_balance
                WHERE userID = ? AND leaveTypeID = ? AND financialYear = ?";
    $balance = $DB->dbRow();

    $elBalance = floatval($balance['closingBalance'] ?? 0);
    $maxCarryForward = intval($elType['maxCarryForward'] ?? 30);

    // Eligible for encashment = balance - maxCarryForward (if positive)
    $eligibleDays = max(0, $elBalance - $maxCarryForward);

    // Get basic salary per day for estimated amount
    $estimatedAmount = 0;
    if ($eligibleDays > 0) {
        $DB->vals = array($userID, 1, date('Y-m-d'), date('Y-m-d'));
        $DB->types = "iiss";
        $DB->sql = "SELECT basicSalary FROM " . $DB->pre . "salary_structure
                    WHERE userID = ? AND status = ? AND effectiveFrom <= ?
                    AND (effectiveTo IS NULL OR effectiveTo >= ?)";
        $salary = $DB->dbRow();

        $basicPerDay = floatval($salary['basicSalary'] ?? 0) / 30;
        $encashmentRate = floatval($elType['encashmentRate'] ?? 100);
        $amountPerDay = $basicPerDay * ($encashmentRate / 100);
        $estimatedAmount = round($amountPerDay * $eligibleDays, 2);
    }

    return array('err' => 0, 'data' => array(
        'elBalance' => $elBalance,
        'maxCarryForward' => $maxCarryForward,
        'eligibleDays' => $eligibleDays,
        'estimatedAmount' => $estimatedAmount,
        'encashmentRate' => floatval($elType['encashmentRate'] ?? 100)
    ));
}

/**
 * Apply for leave encashment
 */
function applyLeaveEncashmentAPI()
{
    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    $userID = $_SESSION['HRMS_USER_ID'];
    $leaveTypeID = intval($_POST['leaveTypeID'] ?? 0);
    $daysToEncash = floatval($_POST['daysToEncash'] ?? 0);

    if (!$leaveTypeID || $daysToEncash <= 0) {
        return array('err' => 1, 'msg' => 'Invalid leave type or days');
    }

    return processLeaveEncashment($userID, $leaveTypeID, $daysToEncash);
}

/**
 * Get leave type configuration
 */
function getLeaveTypeConfigAPI()
{
    global $DB;

    if (!isHRMSLoggedIn()) {
        return array('err' => 1, 'msg' => 'Not logged in');
    }

    $DB->vals = array(1, 0);
    $DB->types = "ii";
    $DB->sql = "SELECT leaveTypeID, leaveTypeName, annualEntitlement, isCarryForward, maxCarryForward,
                       isEncashable, encashmentRate, isPaidLeave, requiresApproval, requiresDocument,
                       documentAfterDays, applicableGender, minServiceDays, accrualType, colorCode
                FROM " . $DB->pre . "leave_type
                WHERE status = ? AND leaveSubType = ?
                ORDER BY sortOrder, leaveTypeID";
    $types = $DB->dbRows();

    return array('err' => 0, 'leaveTypes' => $types);
}
