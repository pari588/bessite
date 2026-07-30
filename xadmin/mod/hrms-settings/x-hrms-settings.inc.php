<?php
/**
 * HRMS Settings Module - Controller
 * Handles all HRMS configuration: Employees, Holidays, Shifts, Leave Types, etc.
 */

// Handle AJAX requests
if (isset($_POST['xAction'])) {
    require_once dirname(__FILE__) . "/../../../config.inc.php";
    require_once COREPATH . "/core.inc.php";

    header('Content-Type: application/json');

    $response = array('err' => 1, 'msg' => 'Invalid action');

    switch ($_POST['xAction']) {
        // Holiday Management
        case 'getHolidays':
            $response = getHolidays();
            break;
        case 'saveHoliday':
            $response = saveHoliday();
            break;
        case 'deleteHoliday':
            $response = deleteHoliday();
            break;

        // Shift Management
        case 'getShifts':
            $response = getShifts();
            break;
        case 'saveShift':
            $response = saveShift();
            break;
        case 'deleteShift':
            $response = deleteShift();
            break;

        // Leave Types
        case 'getLeaveTypes':
            $response = getLeaveTypes();
            break;
        case 'saveLeaveType':
            $response = saveLeaveType();
            break;
        case 'deleteLeaveType':
            $response = deleteLeaveType();
            break;

        // Leave Settings
        case 'getLeaveSettings':
            $response = getLeaveSettings();
            break;
        case 'saveLeaveSettings':
            $response = saveLeaveSettings();
            break;

        // Biometric Settings
        case 'getBiometricStatus':
            $response = getBiometricStatus();
            break;
        case 'assignBiometricID':
            $response = assignBiometricIDToEmployee();
            break;
        case 'bulkAssignBiometricIDs':
            $response = bulkAssignBiometricIDsToEmployees();
            break;

        // Email Settings
        case 'getEmailSettings':
            $response = getEmailSettings();
            break;
        case 'saveEmailSettings':
            $response = saveEmailSettings();
            break;

        // General HRMS Settings
        case 'getGeneralSettings':
            $response = getGeneralSettings();
            break;
        case 'saveGeneralSettings':
            $response = saveGeneralSettings();
            break;
    }

    echo json_encode($response);
    exit;
}

// ============================================================================
// HOLIDAY MANAGEMENT
// ============================================================================

function getHolidays()
{
    global $DB;

    $year = intval($_POST['year'] ?? date('Y'));

    $DB->vals = array($year, $year);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "attendance_holidays`
                WHERE YEAR(ahDate) = ? OR YEAR(ahDate) = ?
                ORDER BY ahDate ASC";
    $holidays = $DB->dbRows();

    return array('err' => 0, 'holidays' => $holidays, 'year' => $year);
}

function saveHoliday()
{
    global $DB;

    $ahID = intval($_POST['ahID'] ?? 0);
    $ahDate = trim($_POST['ahDate'] ?? '');
    $ahName = trim($_POST['ahName'] ?? '');
    $ahType = trim($_POST['ahType'] ?? 'company');
    $isHalfDay = intval($_POST['isHalfDay'] ?? 0);

    if (empty($ahDate) || empty($ahName)) {
        return array('err' => 1, 'msg' => 'Date and Name are required');
    }

    $DB->table = $DB->pre . "attendance_holidays";
    $DB->data = array(
        'ahDate' => $ahDate,
        'ahName' => $ahName,
        'ahType' => $ahType,
        'isHalfDay' => $isHalfDay,
        'status' => 1
    );

    if ($ahID > 0) {
        // Update
        if ($DB->dbUpdate("ahID=?", "i", array($ahID))) {
            return array('err' => 0, 'msg' => 'Holiday updated successfully');
        }
    } else {
        // Insert
        if ($DB->dbInsert()) {
            return array('err' => 0, 'msg' => 'Holiday added successfully', 'ahID' => $DB->insertID);
        }
    }

    return array('err' => 1, 'msg' => 'Failed to save holiday');
}

function deleteHoliday()
{
    global $DB;

    $ahID = intval($_POST['ahID'] ?? 0);
    if (!$ahID) {
        return array('err' => 1, 'msg' => 'Invalid holiday ID');
    }

    $DB->sql = "DELETE FROM `" . $DB->pre . "attendance_holidays` WHERE ahID = ?";
    $DB->vals = array($ahID);
    $DB->types = "i";

    if ($DB->dbQuery()) {
        return array('err' => 0, 'msg' => 'Holiday deleted');
    }

    return array('err' => 1, 'msg' => 'Failed to delete holiday');
}

// ============================================================================
// SHIFT MANAGEMENT
// ============================================================================

function getShifts()
{
    global $DB;

    $DB->vals = array();
    $DB->types = "";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "shift_master` ORDER BY shiftName";
    $shifts = $DB->dbRows();

    return array('err' => 0, 'shifts' => $shifts);
}

function saveShift()
{
    global $DB;

    $shiftID = intval($_POST['shiftID'] ?? 0);
    $shiftName = trim($_POST['shiftName'] ?? '');
    $shiftCode = trim($_POST['shiftCode'] ?? '');
    $startTime = trim($_POST['startTime'] ?? '09:00');
    $endTime = trim($_POST['endTime'] ?? '18:00');
    $graceMinutes = intval($_POST['graceMinutes'] ?? 15);
    $halfDayHours = floatval($_POST['halfDayHours'] ?? 4.0);
    $fullDayHours = floatval($_POST['fullDayHours'] ?? 8.0);
    $isNightShift = intval($_POST['isNightShift'] ?? 0);

    if (empty($shiftName)) {
        return array('err' => 1, 'msg' => 'Shift name is required');
    }

    // Create table if not exists
    $DB->sql = "CREATE TABLE IF NOT EXISTS `" . $DB->pre . "shift_master` (
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
    )";
    $DB->dbQuery();

    $DB->table = $DB->pre . "shift_master";
    $DB->data = array(
        'shiftName' => $shiftName,
        'shiftCode' => $shiftCode,
        'startTime' => $startTime,
        'endTime' => $endTime,
        'graceMinutes' => $graceMinutes,
        'halfDayHours' => $halfDayHours,
        'fullDayHours' => $fullDayHours,
        'isNightShift' => $isNightShift,
        'status' => 1
    );

    if ($shiftID > 0) {
        if ($DB->dbUpdate("shiftID=?", "i", array($shiftID))) {
            return array('err' => 0, 'msg' => 'Shift updated successfully');
        }
    } else {
        if ($DB->dbInsert()) {
            return array('err' => 0, 'msg' => 'Shift added successfully', 'shiftID' => $DB->insertID);
        }
    }

    return array('err' => 1, 'msg' => 'Failed to save shift');
}

function deleteShift()
{
    global $DB;

    $shiftID = intval($_POST['shiftID'] ?? 0);
    if (!$shiftID) {
        return array('err' => 1, 'msg' => 'Invalid shift ID');
    }

    // Soft delete
    $DB->table = $DB->pre . "shift_master";
    $DB->data = array('status' => 0);
    if ($DB->dbUpdate("shiftID=?", "i", array($shiftID))) {
        return array('err' => 0, 'msg' => 'Shift deleted');
    }

    return array('err' => 1, 'msg' => 'Failed to delete shift');
}

// ============================================================================
// LEAVE TYPE MANAGEMENT
// ============================================================================

function getLeaveTypes()
{
    global $DB;

    $DB->vals = array();
    $DB->types = "";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "leave_type` ORDER BY leaveTypeID";
    $types = $DB->dbRows();

    return array('err' => 0, 'leaveTypes' => $types);
}

function saveLeaveType()
{
    global $DB;

    $leaveTypeID = intval($_POST['leaveTypeID'] ?? 0);
    $leaveTypeName = trim($_POST['leaveTypeName'] ?? '');
    $leaveTypeCode = trim($_POST['leaveTypeCode'] ?? '');
    $allotedLeave = intval($_POST['allotedLeave'] ?? 0);
    $isPaid = intval($_POST['isPaid'] ?? 1);
    $carryForward = intval($_POST['carryForward'] ?? 0);
    $maxCarryForward = intval($_POST['maxCarryForward'] ?? 0);
    $requiresDocument = intval($_POST['requiresDocument'] ?? 0);

    if (empty($leaveTypeName)) {
        return array('err' => 1, 'msg' => 'Leave type name is required');
    }

    $DB->table = $DB->pre . "leave_type";
    $DB->data = array(
        'leaveTypeName' => $leaveTypeName,
        'leaveTypeCode' => $leaveTypeCode,
        'allotedLeave' => $allotedLeave,
        'isPaid' => $isPaid,
        'carryForward' => $carryForward,
        'maxCarryForward' => $maxCarryForward,
        'requiresDocument' => $requiresDocument,
        'status' => 1
    );

    if ($leaveTypeID > 0) {
        if ($DB->dbUpdate("leaveTypeID=?", "i", array($leaveTypeID))) {
            return array('err' => 0, 'msg' => 'Leave type updated successfully');
        }
    } else {
        if ($DB->dbInsert()) {
            return array('err' => 0, 'msg' => 'Leave type added successfully', 'leaveTypeID' => $DB->insertID);
        }
    }

    return array('err' => 1, 'msg' => 'Failed to save leave type');
}

function deleteLeaveType()
{
    global $DB;

    $leaveTypeID = intval($_POST['leaveTypeID'] ?? 0);
    if (!$leaveTypeID) {
        return array('err' => 1, 'msg' => 'Invalid leave type ID');
    }

    // Soft delete
    $DB->table = $DB->pre . "leave_type";
    $DB->data = array('status' => 0);
    if ($DB->dbUpdate("leaveTypeID=?", "i", array($leaveTypeID))) {
        return array('err' => 0, 'msg' => 'Leave type deleted');
    }

    return array('err' => 1, 'msg' => 'Failed to delete leave type');
}

// ============================================================================
// LEAVE SETTINGS
// ============================================================================

function getLeaveSettings()
{
    global $DB;

    $DB->sql = "SELECT * FROM `" . $DB->pre . "leave_setting` LIMIT 1";
    $settings = $DB->dbRow();

    // Get HRMS settings
    $DB->sql = "SELECT settingKey, settingValue FROM `" . $DB->pre . "hrms_settings`";
    $hrmsSettings = $DB->dbRows();
    $hrmsMap = array();
    foreach ($hrmsSettings as $s) {
        $hrmsMap[$s['settingKey']] = $s['settingValue'];
    }

    return array(
        'err' => 0,
        'settings' => $settings,
        'hrmsSettings' => $hrmsMap
    );
}

function saveLeaveSettings()
{
    global $DB;

    $fyStartDate = trim($_POST['fyStartDate'] ?? '');
    $fyEndDate = trim($_POST['fyEndDate'] ?? '');
    $totalLeave = intval($_POST['totalLeave'] ?? 12);

    if (empty($fyStartDate) || empty($fyEndDate)) {
        return array('err' => 1, 'msg' => 'Financial year dates are required');
    }

    // Check if settings exist
    $DB->sql = "SELECT leaveSettingID FROM `" . $DB->pre . "leave_setting` LIMIT 1";
    $existing = $DB->dbRow();

    $DB->table = $DB->pre . "leave_setting";
    $DB->data = array(
        'FYStartDate' => $fyStartDate,
        'FYEndDate' => $fyEndDate,
        'totalLeave' => $totalLeave
    );

    if ($existing) {
        if ($DB->dbUpdate("leaveSettingID=?", "i", array($existing['leaveSettingID']))) {
            return array('err' => 0, 'msg' => 'Leave settings updated');
        }
    } else {
        if ($DB->dbInsert()) {
            return array('err' => 0, 'msg' => 'Leave settings saved');
        }
    }

    return array('err' => 1, 'msg' => 'Failed to save leave settings');
}

// ============================================================================
// BIOMETRIC SETTINGS
// ============================================================================

function getBiometricStatus()
{
    global $DB;

    // Get all employees with biometric status
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT userID, userName, displayName, employeeCode, biometricID, department
                FROM `" . $DB->pre . "x_admin_user`
                WHERE status = ?
                ORDER BY displayName";
    $employees = $DB->dbRows();

    $withBiometric = 0;
    $withoutBiometric = 0;
    foreach ($employees as $emp) {
        if (!empty($emp['biometricID'])) {
            $withBiometric++;
        } else {
            $withoutBiometric++;
        }
    }

    return array(
        'err' => 0,
        'employees' => $employees,
        'withBiometric' => $withBiometric,
        'withoutBiometric' => $withoutBiometric,
        'camsTestMode' => defined('CAMS_TEST_MODE') ? CAMS_TEST_MODE : true,
        'camsConfigured' => !empty(CAMS_STGID)
    );
}

function assignBiometricIDToEmployee()
{
    global $DB;

    $userID = intval($_POST['userID'] ?? 0);
    $biometricID = trim($_POST['biometricID'] ?? '');

    if (!$userID) {
        return array('err' => 1, 'msg' => 'Invalid employee');
    }

    // Auto-generate if not provided
    if (empty($biometricID)) {
        // Get next available ID
        $DB->sql = "SELECT MAX(CAST(SUBSTRING(biometricID, 4) AS UNSIGNED)) as maxID
                    FROM `" . $DB->pre . "x_admin_user`
                    WHERE biometricID LIKE 'EMP%'";
        $result = $DB->dbRow();
        $nextID = intval($result['maxID'] ?? 0) + 1;
        $biometricID = 'EMP' . str_pad($nextID, 5, '0', STR_PAD_LEFT);
    }

    $DB->table = $DB->pre . "x_admin_user";
    $DB->data = array('biometricID' => $biometricID);

    if ($DB->dbUpdate("userID=?", "i", array($userID))) {
        return array('err' => 0, 'msg' => 'Biometric ID assigned: ' . $biometricID, 'biometricID' => $biometricID);
    }

    return array('err' => 1, 'msg' => 'Failed to assign biometric ID');
}

function bulkAssignBiometricIDsToEmployees()
{
    global $DB;

    // Get all employees without biometric ID
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT userID FROM `" . $DB->pre . "x_admin_user`
                WHERE status = ? AND (biometricID IS NULL OR biometricID = '')";
    $employees = $DB->dbRows();

    if (empty($employees)) {
        return array('err' => 0, 'msg' => 'All employees already have biometric IDs', 'assigned' => 0);
    }

    // Get current max ID
    $DB->sql = "SELECT MAX(CAST(SUBSTRING(biometricID, 4) AS UNSIGNED)) as maxID
                FROM `" . $DB->pre . "x_admin_user`
                WHERE biometricID LIKE 'EMP%'";
    $result = $DB->dbRow();
    $nextID = intval($result['maxID'] ?? 0) + 1;

    $assigned = 0;
    foreach ($employees as $emp) {
        $biometricID = 'EMP' . str_pad($nextID, 5, '0', STR_PAD_LEFT);

        $DB->table = $DB->pre . "x_admin_user";
        $DB->data = array('biometricID' => $biometricID);

        if ($DB->dbUpdate("userID=?", "i", array($emp['userID']))) {
            $assigned++;
            $nextID++;
        }
    }

    return array('err' => 0, 'msg' => "Assigned biometric IDs to $assigned employees", 'assigned' => $assigned);
}

// ============================================================================
// EMAIL SETTINGS
// ============================================================================

function getEmailSettings()
{
    global $DB;

    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "hr_email_recipients` WHERE status = ?";
    $recipients = $DB->dbRows();

    // Get HRMS settings
    $DB->sql = "SELECT settingKey, settingValue FROM `" . $DB->pre . "hrms_settings` WHERE settingKey LIKE 'email_%'";
    $settings = $DB->dbRows();
    $settingsMap = array();
    foreach ($settings as $s) {
        $settingsMap[$s['settingKey']] = $s['settingValue'];
    }

    return array(
        'err' => 0,
        'recipients' => $recipients,
        'settings' => $settingsMap
    );
}

function saveEmailSettings()
{
    global $DB;

    $recipients = $_POST['recipients'] ?? array();
    $sendDay = intval($_POST['sendDay'] ?? 1);
    $sendHour = intval($_POST['sendHour'] ?? 9);
    $enableAutoSend = intval($_POST['enableAutoSend'] ?? 0);

    // Create hrms_settings table if not exists
    $DB->sql = "CREATE TABLE IF NOT EXISTS `" . $DB->pre . "hrms_settings` (
        settingID INT AUTO_INCREMENT PRIMARY KEY,
        settingKey VARCHAR(100) NOT NULL UNIQUE,
        settingValue TEXT,
        settingDescription VARCHAR(255),
        updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $DB->dbQuery();

    // Save email schedule settings
    $settings = array(
        'email_send_day' => $sendDay,
        'email_send_hour' => $sendHour,
        'email_auto_send' => $enableAutoSend
    );

    foreach ($settings as $key => $value) {
        $DB->sql = "INSERT INTO `" . $DB->pre . "hrms_settings` (settingKey, settingValue)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE settingValue = ?";
        $DB->vals = array($key, $value, $value);
        $DB->types = "sss";
        $DB->dbQuery();
    }

    // Handle recipients
    if (!empty($recipients) && is_array($recipients)) {
        // Deactivate all existing
        $DB->table = $DB->pre . "hr_email_recipients";
        $DB->data = array('status' => 0);
        $DB->dbUpdate("1=1", "", array());

        foreach ($recipients as $recipient) {
            if (!empty($recipient['email'])) {
                // Check if exists
                $DB->vals = array($recipient['email']);
                $DB->types = "s";
                $DB->sql = "SELECT recipientID FROM `" . $DB->pre . "hr_email_recipients` WHERE recipientEmail = ?";
                $existing = $DB->dbRow();

                $DB->table = $DB->pre . "hr_email_recipients";
                $DB->data = array(
                    'recipientName' => $recipient['name'] ?? '',
                    'recipientEmail' => $recipient['email'],
                    'emailTypes' => $recipient['types'] ?? 'hr_master',
                    'status' => 1
                );

                if ($existing) {
                    $DB->dbUpdate("recipientID=?", "i", array($existing['recipientID']));
                } else {
                    $DB->dbInsert();
                }
            }
        }
    }

    return array('err' => 0, 'msg' => 'Email settings saved');
}

// ============================================================================
// GENERAL SETTINGS
// ============================================================================

function getGeneralSettings()
{
    global $DB;

    $DB->sql = "SELECT settingKey, settingValue FROM `" . $DB->pre . "hrms_settings`";
    $rows = $DB->dbRows();

    $settings = array();
    foreach ($rows as $row) {
        $settings[$row['settingKey']] = $row['settingValue'];
    }

    // Add defaults if not set
    $defaults = array(
        'work_start_time' => '09:00',
        'work_end_time' => '18:00',
        'late_grace_minutes' => '15',
        'late_deduction_count' => '3',
        'half_day_hours' => '4',
        'full_day_hours' => '8',
        'week_off_day' => '0'
    );

    foreach ($defaults as $key => $value) {
        if (!isset($settings[$key])) {
            $settings[$key] = $value;
        }
    }

    return array('err' => 0, 'settings' => $settings);
}

function saveGeneralSettings()
{
    global $DB;

    $settingsToSave = array(
        'work_start_time' => trim($_POST['workStartTime'] ?? '09:00'),
        'work_end_time' => trim($_POST['workEndTime'] ?? '18:00'),
        'late_grace_minutes' => intval($_POST['lateGraceMinutes'] ?? 15),
        'late_deduction_count' => intval($_POST['lateDeductionCount'] ?? 3),
        'half_day_hours' => floatval($_POST['halfDayHours'] ?? 4),
        'full_day_hours' => floatval($_POST['fullDayHours'] ?? 8),
        'week_off_day' => intval($_POST['weekOffDay'] ?? 0)
    );

    // Create table if not exists
    $DB->sql = "CREATE TABLE IF NOT EXISTS `" . $DB->pre . "hrms_settings` (
        settingID INT AUTO_INCREMENT PRIMARY KEY,
        settingKey VARCHAR(100) NOT NULL UNIQUE,
        settingValue TEXT,
        settingDescription VARCHAR(255),
        updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $DB->dbQuery();

    foreach ($settingsToSave as $key => $value) {
        $DB->sql = "INSERT INTO `" . $DB->pre . "hrms_settings` (settingKey, settingValue)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE settingValue = ?";
        $DB->vals = array($key, $value, $value);
        $DB->types = "sss";
        $DB->dbQuery();
    }

    return array('err' => 0, 'msg' => 'General settings saved');
}
