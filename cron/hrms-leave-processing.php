<?php
/**
 * HRMS Leave Processing Cron Job
 *
 * Handles:
 * 1. Monthly EL accrual (run on 1st of every month)
 * 2. Yearly leave reset (run on April 1st)
 * 3. Expire old comp-offs (run daily)
 *
 * Cron Commands:
 * Daily (expire comp-offs):
 * 0 1 * * * /usr/bin/php /home/bombayengg/public_html/cron/hrms-leave-processing.php action=expire_compoff >> /home/bombayengg/logs/hrms-leave.log 2>&1
 *
 * Monthly (EL accrual - 1st of every month):
 * 0 6 1 * * /usr/bin/php /home/bombayengg/public_html/cron/hrms-leave-processing.php action=monthly_accrual >> /home/bombayengg/logs/hrms-leave.log 2>&1
 *
 * Yearly reset (April 1st):
 * 0 0 1 4 * /usr/bin/php /home/bombayengg/public_html/cron/hrms-leave-processing.php action=yearly_reset >> /home/bombayengg/logs/hrms-leave.log 2>&1
 */

// Set server variables for CLI mode
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'www.bombayengg.net';
    $_SERVER['REQUEST_URI'] = '/cron/hrms-leave-processing.php';
    $_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';

    // Parse CLI arguments
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

// Prevent unauthorized browser access
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BES_LEAVE_CRON_2024')) {
    die('Access denied');
}

require_once(dirname(__FILE__) . '/../core/core.inc.php');
require_once(dirname(__FILE__) . '/../xadmin/inc/site.inc.php');
require_once(dirname(__FILE__) . '/../core/leave-management.inc.php');

$action = $_GET['action'] ?? 'all';

logCron("=== HRMS Leave Processing Started ===");
logCron("Action: $action");
logCron("Date: " . date('Y-m-d H:i:s'));

switch ($action) {
    case 'monthly_accrual':
        logCron("\n--- Processing Monthly EL Accrual ---");
        $result = processMonthlyELAccrual();
        logCron($result['msg']);
        break;

    case 'yearly_reset':
        logCron("\n--- Processing Yearly Leave Reset ---");
        $result = processYearlyLeaveReset();
        logCron($result['msg']);
        logCron("From FY: " . $result['fromFY'] . " To FY: " . $result['toFY']);
        break;

    case 'expire_compoff':
        logCron("\n--- Expiring Old Comp-Offs ---");
        $result = expireOldCompOffs();
        logCron($result['msg']);
        break;

    case 'initialize':
        logCron("\n--- Initializing Leave Balances for All Employees ---");
        $result = initializeAllEmployeeLeaveBalances();
        logCron($result['msg']);
        break;

    case 'all':
    default:
        // Run all applicable tasks based on current date
        $today = date('j'); // Day of month
        $month = date('n'); // Month number

        // Always expire comp-offs
        logCron("\n--- Expiring Old Comp-Offs ---");
        $result = expireOldCompOffs();
        logCron($result['msg']);

        // On 1st of month, run monthly accrual
        if ($today == 1) {
            logCron("\n--- Processing Monthly EL Accrual ---");
            $result = processMonthlyELAccrual();
            logCron($result['msg']);

            // On April 1st, also run yearly reset
            if ($month == 4) {
                logCron("\n--- Processing Yearly Leave Reset ---");
                $result = processYearlyLeaveReset();
                logCron($result['msg']);
            }
        }
        break;
}

logCron("\n=== HRMS Leave Processing Completed ===\n");

/**
 * Initialize leave balances for all active employees
 */
function initializeAllEmployeeLeaveBalances()
{
    global $DB;

    $fy = getCurrentFinancialYear();

    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT userID, displayName FROM " . $DB->pre . "x_admin_user WHERE status = ?";
    $employees = $DB->dbRows();

    $processed = 0;
    foreach ($employees as $emp) {
        initializeEmployeeLeaveBalance($emp['userID'], $fy);
        logCron("Initialized: " . $emp['displayName']);
        $processed++;
    }

    return array('err' => 0, 'msg' => "Initialized leave balances for $processed employees");
}

/**
 * Log cron activity
 */
function logCron($message)
{
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";

    // Also log to file
    $logFile = dirname(__FILE__) . '/../logs/hrms-leave.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}
