<?php
/**
 * HRMS Auto-Attendance Cron Job
 *
 * Marks attendance for employees flagged with autoAttendance = 1
 * These are typically warehouse/field staff without biometric access
 *
 * Cron Command (run daily at 10:05 AM):
 * 5 10 * * * /usr/bin/php /home/bombayengg/public_html/cron/hrms-auto-attendance.php >> /home/bombayengg/logs/hrms-auto-attendance.log 2>&1
 */

// Set server variables for CLI mode
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'www.bombayengg.net';
    $_SERVER['REQUEST_URI'] = '/cron/hrms-auto-attendance.php';
    $_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';
}

// Prevent unauthorized browser access
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BES_AUTO_ATT_2024')) {
    die('Access denied');
}

require_once(dirname(__FILE__) . '/../core/core.inc.php');

$today = date('Y-m-d');
$dayOfWeek = strtolower(date('l')); // sunday, monday, etc.

logAutoAtt("=== Auto-Attendance Processing Started ===");
logAutoAtt("Date: $today ($dayOfWeek)");

// Skip Sundays (weekly off)
if ($dayOfWeek === 'sunday') {
    logAutoAtt("Skipping - Sunday is weekly off");
    logAutoAtt("=== Processing Completed ===\n");
    exit;
}

// Check if today is a holiday
$DB->vals = array($today, 1);
$DB->types = "si";
$DB->sql = "SELECT holidayID, holidayName FROM " . $DB->pre . "holiday_master
            WHERE holidayDate = ? AND status = ?";
$holiday = $DB->dbRow();

if ($holiday) {
    logAutoAtt("Skipping - Today is a holiday: " . $holiday['holidayName']);
    logAutoAtt("=== Processing Completed ===\n");
    exit;
}

// Get all employees flagged for auto-attendance (non-biometric users)
$DB->vals = array(1, 1);
$DB->types = "ii";
$DB->sql = "SELECT userID, displayName, employeeCode, workStartTime, workEndTime,
                   saturdayStartTime, saturdayEndTime
            FROM " . $DB->pre . "x_admin_user
            WHERE autoAttendance = ? AND status = ?";
$employees = $DB->dbRows();

if (empty($employees)) {
    logAutoAtt("No employees flagged for auto-attendance");
    logAutoAtt("=== Processing Completed ===\n");
    exit;
}

logAutoAtt("Found " . count($employees) . " employees for auto-attendance");

$processed = 0;
$skipped = 0;

foreach ($employees as $emp) {
    $userID = $emp['userID'];
    $name = $emp['displayName'];

    // Use Saturday timings if it's Saturday
    if ($dayOfWeek === 'saturday') {
        $startTime = $emp['saturdayStartTime'] ?: $emp['workStartTime'] ?: '10:00:00';
        $endTime = $emp['saturdayEndTime'] ?: '16:00:00';
    } else {
        $startTime = $emp['workStartTime'] ?: '10:00:00';
        $endTime = $emp['workEndTime'] ?: '18:00:00';
    }

    // Check if attendance already exists for today
    $DB->vals = array($userID, $today);
    $DB->types = "is";
    $DB->sql = "SELECT attendanceID FROM " . $DB->pre . "attendance
                WHERE userID = ? AND attendanceDate = ?";
    $existing = $DB->dbRow();

    if ($existing) {
        logAutoAtt("  SKIP: $name - Attendance already exists");
        $skipped++;
        continue;
    }

    // Calculate working hours
    $start = new DateTime($today . ' ' . $startTime);
    $end = new DateTime($today . ' ' . $endTime);
    $diff = $start->diff($end);
    $workingHours = $diff->h + ($diff->i / 60);

    // Insert attendance record
    $DB->table = $DB->pre . "attendance";
    $DB->data = array(
        'userID' => $userID,
        'attendanceDate' => $today,
        'scheduledIn' => $startTime,
        'scheduledOut' => $endTime,
        'checkIn' => $today . ' ' . $startTime,
        'checkOut' => $today . ' ' . $endTime,
        'workingHours' => $workingHours,
        'isLate' => 0,
        'isEarlyCheckout' => 0,
        'lateMinutes' => 0,
        'earlyMinutes' => 0,
        'attendanceStatus' => 'present',
        'source' => 'auto',
        'remarks' => 'Auto-marked (no biometric)',
        'status' => 1
    );
    $DB->dbInsert();

    logAutoAtt("  OK: $name - Marked Present ($startTime - $endTime)");
    $processed++;
}

logAutoAtt("Processed: $processed, Skipped: $skipped");
logAutoAtt("=== Auto-Attendance Processing Completed ===\n");

/**
 * Log function
 */
function logAutoAtt($message)
{
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";

    $logFile = dirname(__FILE__) . '/../logs/hrms-auto-attendance.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}
