<?php
/**
 * Holiday Reminder Cron Job
 * Sends WhatsApp reminder notifications for holidays happening tomorrow
 *
 * Schedule: Run daily at 9:00 AM
 * Crontab entry: 0 9 * * * /usr/bin/php /home/bombayengg/public_html/cron/hrms-holiday-reminder.php
 *
 * @author BES HRMS
 * @version 1.0
 */

// Set execution context for CLI
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'www.bombayengg.net';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/cron/hrms-holiday-reminder.php';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'on';

// Define the base path
define('CRON_BASE_PATH', dirname(__DIR__));

// Include configuration and core files
require_once CRON_BASE_PATH . '/config.inc.php';
require_once CRON_BASE_PATH . '/core/core.inc.php';
require_once CRON_BASE_PATH . '/core/whatsapp-api.inc.php';

// Log function for cron output
function cronLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    echo $logMessage;
    error_log("Holiday Reminder Cron: " . $message);
}

cronLog("=== Holiday Reminder Cron Started ===");

/**
 * Get holidays happening tomorrow
 */
function getTomorrowHolidays() {
    global $DB;

    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    cronLog("Checking for holidays on: " . $tomorrow);

    $DB->vals = array($tomorrow, 1);
    $DB->types = "si";
    $DB->sql = "SELECT holidayID, holidayName, holidayDate, holidayType
                FROM " . $DB->pre . "holiday_master
                WHERE holidayDate = ? AND status = ?
                ORDER BY holidayDate ASC";

    return $DB->dbRows();
}

/**
 * Get all employees opted in for WhatsApp notifications
 */
function getOptedInEmployees() {
    global $DB;

    $DB->vals = array(1, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT userID, displayName, whatsappNumber, userMobile
                FROM " . $DB->pre . "x_admin_user
                WHERE status = ? AND whatsappOptIn = ?
                ORDER BY displayName ASC";

    return $DB->dbRows();
}

/**
 * Check if reminder already sent for this holiday
 */
function reminderAlreadySent($holidayID) {
    global $DB;

    $today = date('Y-m-d');

    $DB->vals = array($holidayID, $today . '%');
    $DB->types = "is";
    $DB->sql = "SELECT COUNT(*) as count
                FROM " . $DB->pre . "wa_message_log
                WHERE messageBody LIKE ? AND createdAt LIKE ?";

    $result = $DB->dbRow();
    return ($result && $result['count'] > 0);
}

/**
 * Send holiday reminder via WhatsApp template
 */
function sendHolidayReminder($wa, $employee, $holiday) {
    $phone = $employee['whatsappNumber'] ?: $employee['userMobile'];

    if (empty($phone)) {
        cronLog("  ✗ No phone number for {$employee['displayName']}");
        return false;
    }

    // Format date as "Tomorrow (19-Mar-2026)"
    $dateFormatted = 'Tomorrow (' . date('d-M-Y', strtotime($holiday['holidayDate'])) . ')';

    // Template parameters: {{1}} = Holiday Name, {{2}} = Date
    $components = [
        [
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $holiday['holidayName']],
                ['type' => 'text', 'text' => $dateFormatted]
            ]
        ]
    ];

    try {
        $result = $wa->sendTemplate(
            $phone,
            'holiday_reminder',
            'en',
            $components
        );

        if (isset($result['messages']) && isset($result['messages'][0]['id'])) {
            cronLog("  ✓ Sent to {$employee['displayName']} ({$phone})");
            return true;
        } else {
            cronLog("  ✗ Failed for {$employee['displayName']}: " . json_encode($result));
            return false;
        }
    } catch (Exception $e) {
        cronLog("  ✗ Exception for {$employee['displayName']}: " . $e->getMessage());
        return false;
    }
}

/**
 * Ensure reminderSent column exists in holiday_master
 */
function ensureReminderColumn() {
    global $DB;

    // Check if column exists
    $DB->sql = "SHOW COLUMNS FROM `" . $DB->pre . "holiday_master` LIKE 'reminderSent'";
    $result = $DB->dbRow();

    if (!$result) {
        cronLog("Adding reminderSent column to holiday_master table...");
        $DB->sql = "ALTER TABLE `" . $DB->pre . "holiday_master`
                    ADD COLUMN `reminderSent` TINYINT(1) DEFAULT 0 AFTER `status`,
                    ADD COLUMN `reminderSentOn` DATETIME NULL AFTER `reminderSent`";
        $DB->dbQuery();
        cronLog("Column added successfully");
    }
}

/**
 * Mark holiday reminder as sent
 */
function markHolidayReminderSent($holidayID) {
    global $DB;

    $DB->table = $DB->pre . "holiday_master";
    $DB->data = array(
        'reminderSent' => 1,
        'reminderSentOn' => date('Y-m-d H:i:s')
    );

    return $DB->dbUpdate("holidayID=?", "i", array($holidayID));
}

// Main execution
try {
    // Ensure the reminder column exists
    ensureReminderColumn();

    // Get holidays happening tomorrow
    $holidays = getTomorrowHolidays();

    if (empty($holidays)) {
        cronLog("No holidays found for tomorrow. Exiting.");
        cronLog("=== Holiday Reminder Cron Completed ===\n");
        exit(0);
    }

    $holidayCount = count($holidays);
    cronLog("Found {$holidayCount} holiday(s) tomorrow:");
    foreach ($holidays as $h) {
        cronLog("  - {$h['holidayName']} ({$h['holidayDate']})");
    }

    // Get all opted-in employees
    $employees = getOptedInEmployees();

    if (empty($employees)) {
        cronLog("No employees opted in for WhatsApp notifications. Exiting.");
        cronLog("=== Holiday Reminder Cron Completed ===\n");
        exit(0);
    }

    $employeeCount = count($employees);
    cronLog("Found {$employeeCount} opted-in employee(s).");

    // Initialize WhatsApp API
    require_once '/home/bombayengg/whatsapp-config.php';
    $wa = new WhatsAppAPI($DB);

    // Process each holiday
    foreach ($holidays as $holiday) {
        // Check if reminder already sent today
        if ($holiday['reminderSent']) {
            cronLog("\nReminder already marked as sent for: {$holiday['holidayName']}. Skipping.");
            continue;
        }

        cronLog("\nProcessing holiday: {$holiday['holidayName']}");

        $sentCount = 0;
        $failedCount = 0;

        // Send reminder to each employee
        foreach ($employees as $employee) {
            $success = sendHolidayReminder($wa, $employee, $holiday);

            if ($success) {
                $sentCount++;
            } else {
                $failedCount++;
            }

            // Small delay between messages to avoid rate limiting
            usleep(300000); // 0.3 second delay
        }

        cronLog("\nSummary for {$holiday['holidayName']}: {$sentCount} sent, {$failedCount} failed out of {$employeeCount} total.");

        // Mark holiday reminder as sent
        markHolidayReminderSent($holiday['holidayID']);
        cronLog("Marked holiday reminder as sent.");
    }

} catch (Exception $e) {
    cronLog("ERROR: " . $e->getMessage());
    cronLog("Stack trace: " . $e->getTraceAsString());
}

cronLog("=== Holiday Reminder Cron Completed ===\n");
?>
