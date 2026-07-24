<?php
/**
 * Leave Reminder Cron Job
 * Sends reminder emails 2 days before approved leaves start
 *
 * Schedule: Run daily at 8:00 AM
 * Crontab entry: 0 8 * * * /usr/bin/php /home/bombayengg/public_html/cron/leave-reminder-cron.php
 *
 * @author BES HRMS
 * @version 1.0
 */

// Set execution context for CLI
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'www.bombayengg.net';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/cron/leave-reminder-cron.php';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'on';

// Define the base path
define('CRON_BASE_PATH', dirname(__DIR__));

// Include configuration and core files
require_once CRON_BASE_PATH . '/config.inc.php';
require_once CRON_BASE_PATH . '/core/core.inc.php';
require_once CRON_BASE_PATH . '/core/brevo.inc.php';

// Log function for cron output
function cronLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    echo $logMessage;
    error_log("Leave Reminder Cron: " . $message);
}

cronLog("=== Leave Reminder Cron Started ===");

/**
 * Get all approved leaves starting in exactly 2 days
 */
function getUpcomingApprovedLeaves() {
    global $DB;

    // Calculate the date 2 days from now
    $targetDate = date('Y-m-d', strtotime('+2 days'));

    cronLog("Checking for approved leaves starting on: " . $targetDate);

    $DB->sql = "SELECT
                    l.leaveID,
                    l.userID,
                    l.fromDate,
                    l.toDate,
                    l.reason,
                    l.leaveStatus,
                    l.dateAdded,
                    DATEDIFF(STR_TO_DATE(l.toDate, '%Y-%m-%d'), STR_TO_DATE(l.fromDate, '%Y-%m-%d')) + 1 AS totalDays,
                    u.displayName AS employeeName,
                    u.userEmail AS employeeEmail,
                    lt.leaveTypeName AS leaveTypeName
                FROM `" . $DB->pre . "leave` l
                LEFT JOIN `" . $DB->pre . "x_admin_user` u ON l.userID = u.userID
                LEFT JOIN `" . $DB->pre . "leave_type` lt ON l.leaveType = lt.leaveTypeID
                WHERE l.status = ?
                  AND l.leaveStatus = ?
                  AND DATE(STR_TO_DATE(l.fromDate, '%Y-%m-%d')) = ?
                  AND l.reminderSent = ?
                ORDER BY l.fromDate ASC";

    $DB->vals = array(1, 'Approved', $targetDate, 0);
    $DB->types = "issi";

    return $DB->dbRows();
}

/**
 * Mark leave as reminder sent
 */
function markReminderSent($leaveID) {
    global $DB;

    $DB->table = $DB->pre . "leave";
    $DB->data = array(
        'reminderSent' => 1,
        'reminderSentOn' => date('Y-m-d H:i:s')
    );

    return $DB->dbUpdate("leaveID=?", "i", array($leaveID));
}

/**
 * Check if reminderSent column exists, add if not
 */
function ensureReminderColumns() {
    global $DB;

    // Check if column exists
    $DB->sql = "SHOW COLUMNS FROM `" . $DB->pre . "leave` LIKE 'reminderSent'";
    $result = $DB->dbRow();

    if (!$result) {
        cronLog("Adding reminderSent column to leave table...");
        $DB->sql = "ALTER TABLE `" . $DB->pre . "leave`
                    ADD COLUMN `reminderSent` TINYINT(1) DEFAULT 0 AFTER `snote`,
                    ADD COLUMN `reminderSentOn` DATETIME NULL AFTER `reminderSent`";
        $DB->dbQuery();
        cronLog("Column added successfully");
    }
}

// Main execution
try {
    // Ensure the reminder columns exist
    ensureReminderColumns();

    // Get upcoming approved leaves
    $upcomingLeaves = getUpcomingApprovedLeaves();

    if (empty($upcomingLeaves)) {
        cronLog("No approved leaves found starting in 2 days.");
    } else {
        $count = count($upcomingLeaves);
        cronLog("Found {$count} approved leave(s) starting in 2 days.");

        $sentCount = 0;
        $failedCount = 0;

        foreach ($upcomingLeaves as $leave) {
            cronLog("Processing leave ID: {$leave['leaveID']} for {$leave['employeeName']}");

            // Prepare leave data for email
            $leaveData = array(
                'leaveID' => $leave['leaveID'],
                'employeeName' => $leave['employeeName'],
                'employeeEmail' => $leave['employeeEmail'],
                'leaveTypeName' => $leave['leaveTypeName'],
                'fromDate' => $leave['fromDate'],
                'toDate' => $leave['toDate'],
                'totalDays' => $leave['totalDays'],
                'reason' => $leave['reason']
            );

            // Send the reminder email
            $emailSent = sendLeaveReminderNotification($leaveData);

            if ($emailSent) {
                // Mark as sent to prevent duplicate emails
                markReminderSent($leave['leaveID']);
                $sentCount++;
                cronLog("✓ Reminder sent successfully for {$leave['employeeName']} (Leave ID: {$leave['leaveID']})");
            } else {
                $failedCount++;
                cronLog("✗ Failed to send reminder for {$leave['employeeName']} (Leave ID: {$leave['leaveID']})");
            }

            // Small delay between emails to avoid rate limiting
            usleep(500000); // 0.5 second delay
        }

        cronLog("Summary: {$sentCount} sent, {$failedCount} failed out of {$count} total.");
    }

} catch (Exception $e) {
    cronLog("ERROR: " . $e->getMessage());
}

cronLog("=== Leave Reminder Cron Completed ===\n");
?>
