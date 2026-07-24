<?php
/**
 * Direct Leave Action Handler
 * Allows one-click approve/reject from email links
 * URL: /xadmin/mod/employee-leave/x-leave-action.php?action=approve&id=123&token=xxx
 */

// Set server vars for CLI compatibility
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'www.bombayengg.net';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/xadmin/mod/employee-leave/x-leave-action.php';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'on';

include __DIR__ . '/../../../config.inc.php';
include __DIR__ . '/../../../core/core.inc.php';

header('Content-Type: text/html; charset=UTF-8');

$action = $_GET['action'] ?? '';
$leaveID = intval($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

// Validate inputs
if (!$leaveID || !$action || !$token) {
    showResult('error', 'Invalid Request', 'Missing required parameters.');
    exit;
}

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    showResult('error', 'Invalid Action', 'Action must be approve or reject.');
    exit;
}

// Verify token - token is md5(leaveID + secret)
$secret = defined('BREVO_API_KEY') ? substr(BREVO_API_KEY, 0, 16) : 'besyndicate2024';
$expectedToken = md5($leaveID . $secret);

if ($token !== $expectedToken) {
    showResult('error', 'Invalid Token', 'Security token verification failed. This link may have expired.');
    exit;
}

// Get leave details
$DB->vals = array($leaveID, 1);
$DB->types = "ii";
$DB->sql = "SELECT l.*, u.displayName, u.userEmail
            FROM `" . $DB->pre . "leave` l
            LEFT JOIN `" . $DB->pre . "x_admin_user` u ON l.userID = u.userID
            WHERE l.leaveID=? AND l.status=?";
$leave = $DB->dbRow();

if (!$leave) {
    showResult('error', 'Leave Not Found', 'This leave application does not exist or has been deleted.');
    exit;
}

// Check if already processed
if ($leave['leaveStatus'] !== 'Pending') {
    showResult('info', 'Already Processed', 'This leave application has already been ' . strtolower($leave['leaveStatus']) . '.');
    exit;
}

// Update leave status
$newStatus = ($action === 'approve') ? 'Approved' : 'Disapproved';

$DB->table = $DB->pre . "leave";
$DB->data = array(
    'leaveStatus' => $newStatus,
    'snote' => 'Action via email link on ' . date('Y-m-d H:i:s')
);

if ($DB->dbUpdate("leaveID=?", "i", array($leaveID))) {
    // Update leave details status too
    $DB->table = $DB->pre . "leave_details";
    $DB->data = array('leaveStatus' => $newStatus);
    $DB->dbUpdate("leaveID=?", "i", array($leaveID));

    // Update user leave balance if approved/disapproved
    include_once(__DIR__ . '/../../../inc/common.inc.php');
    if (function_exists('updateUserLeaves')) {
        $year = date("Y", strtotime($leave["fromDate"]));
        $month = date("m", strtotime($leave["fromDate"]));
        updateUserLeaves($year, $month, $leave["userID"]);
    }

    // Send email notification to employee
    $brevoPath = defined('COREPATH') ? COREPATH . '/brevo.inc.php' : __DIR__ . '/../../../core/brevo.inc.php';
    if (file_exists($brevoPath)) {
        include_once($brevoPath);
        if (function_exists('sendLeaveStatusNotification')) {
            // Get leave type name
            $DB->vals = array($leave['leaveType'], 1);
            $DB->types = "ii";
            $DB->sql = "SELECT leaveTypeName FROM `" . $DB->pre . "leave_type` WHERE leaveTypeID=? AND status=?";
            $leaveTypeInfo = $DB->dbRow();

            $leaveData = array(
                'employeeName' => $leave['displayName'] ?? 'Employee',
                'employeeEmail' => $leave['userEmail'] ?? '',
                'leaveType' => $leaveTypeInfo['leaveTypeName'] ?? 'Leave',
                'fromDate' => $leave['fromDate'],
                'toDate' => $leave['toDate'],
                'status' => $newStatus,
                'remarks' => ''
            );
            sendLeaveStatusNotification($leaveData);
        }
    }

    $title = ($action === 'approve') ? 'Leave Approved!' : 'Leave Rejected';
    $icon = ($action === 'approve') ? '✓' : '✗';
    $color = ($action === 'approve') ? '#10b981' : '#ef4444';

    showResult('success', $title,
        "Leave application for <strong>{$leave['displayName']}</strong> has been {$newStatus}.<br><br>" .
        "From: " . date('d M Y', strtotime($leave['fromDate'])) . "<br>" .
        "To: " . date('d M Y', strtotime($leave['toDate'])) . "<br>" .
        "Reason: {$leave['reason']}",
        $color, $icon
    );
} else {
    showResult('error', 'Update Failed', 'Failed to update leave status. Please try again or use the admin panel.');
}

/**
 * Display result page
 */
function showResult($type, $title, $message, $color = null, $icon = null) {
    if ($color === null) {
        $color = $type === 'success' ? '#10b981' : ($type === 'error' ? '#ef4444' : '#f59e0b');
    }
    if ($icon === null) {
        $icon = $type === 'success' ? '✓' : ($type === 'error' ? '✗' : 'ℹ');
    }

    $adminUrl = defined('SITEURL') ? SITEURL . '/xadmin/?mod=employee-leave' : '/xadmin/?mod=employee-leave';

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - Bombay Engineering Syndicate</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 480px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: {$color};
            padding: 40px;
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }
        .header h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
        }
        .content {
            padding: 40px;
            text-align: center;
        }
        .message {
            color: #4b5563;
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #157bba 0%, #0f5a8a 100%);
            color: white;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(21, 123, 186, 0.3);
        }
        .footer {
            background: #f9fafb;
            padding: 20px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            color: #9ca3af;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">{$icon}</div>
            <h1>{$title}</h1>
        </div>
        <div class="content">
            <p class="message">{$message}</p>
            <a href="{$adminUrl}" class="btn">Go to Leave Management</a>
        </div>
        <div class="footer">
            <p>Bombay Engineering Syndicate - HRMS</p>
        </div>
    </div>
</body>
</html>
HTML;
}
