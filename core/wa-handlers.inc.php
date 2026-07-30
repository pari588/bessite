<?php
/**
 * WhatsApp HRMS Bot — Message Handlers
 * Handles all inbound message routing and HRMS self-service flows
 */

/**
 * Send text message followed by a Main Menu button
 */
function sendWithMenuButton($wa, $fromNumber, $msg)
{
    $buttons = [
        ['id' => 'menu_main', 'title' => 'Main Menu']
    ];
    $wa->sendInteractiveButtons($fromNumber, $msg, $buttons);
}

/**
 * Look up employee by phone number
 * Tries whatsappNumber first, then userMobile (with/without country code)
 */
function lookupUserByPhone($DB, $fromNumber)
{
    // Strip leading + if present
    $phone = preg_replace('/[^0-9]/', '', $fromNumber);

    // Try whatsappNumber exact match
    $DB->vals = array($phone, 1);
    $DB->types = "si";
    $DB->sql = "SELECT userID, displayName, employeeCode, designation, department,
                       dateOfJoining, userMobile, whatsappNumber, managerID, roleID
                FROM " . $DB->pre . "x_admin_user
                WHERE whatsappNumber = ? AND status = ?
                LIMIT 1";
    $user = $DB->dbRow();
    if ($user) return $user;

    // Try userMobile exact match (with country code)
    $DB->vals = array($phone, 1);
    $DB->types = "si";
    $DB->sql = "SELECT userID, displayName, employeeCode, designation, department,
                       dateOfJoining, userMobile, whatsappNumber, managerID, roleID
                FROM " . $DB->pre . "x_admin_user
                WHERE userMobile = ? AND status = ?
                LIMIT 1";
    $user = $DB->dbRow();
    if ($user) return $user;

    // Try without country code (strip 91 from start)
    if (strlen($phone) > 10 && substr($phone, 0, 2) === '91') {
        $phoneWithout91 = substr($phone, 2);
        $DB->vals = array($phoneWithout91, 1);
        $DB->types = "si";
        $DB->sql = "SELECT userID, displayName, employeeCode, designation, department,
                           dateOfJoining, userMobile, whatsappNumber, managerID, roleID
                    FROM " . $DB->pre . "x_admin_user
                    WHERE userMobile = ? AND status = ?
                    LIMIT 1";
        $user = $DB->dbRow();
        if ($user) return $user;
    }

    return null;
}

/**
 * Handle unregistered numbers — show customer menu
 */
function handleUnregisteredUser($wa, $fromNumber)
{
    handleCustomerMenu($wa, $fromNumber);
}

/**
 * Check if user is a manager (has team members reporting to them)
 */
function isManager($DB, $userID)
{
    $DB->vals = array($userID, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT COUNT(*) as cnt FROM " . $DB->pre . "x_admin_user WHERE managerID = ? AND status = ?";
    $row = $DB->dbRow();
    return ($row && $row['cnt'] > 0);
}

/**
 * Get team members for a manager
 */
function getTeamMembers($DB, $managerID)
{
    $DB->vals = array($managerID, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT userID, displayName, employeeCode, designation
                FROM " . $DB->pre . "x_admin_user
                WHERE managerID = ? AND status = ?
                ORDER BY displayName";
    return $DB->dbRows();
}

/**
 * Get or create conversation state
 */
function getConversationState($DB, $userID, $fromNumber)
{
    $DB->vals = array($userID);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM " . $DB->pre . "wa_conversation_state WHERE userID = ?";
    $state = $DB->dbRow();

    // Check if expired
    if ($state && strtotime($state['expiresAt']) < time()) {
        clearConversationState($DB, $userID);
        return null;
    }

    return $state;
}

/**
 * Set conversation state
 */
function setConversationState($DB, $userID, $fromNumber, $flow, $step, $flowData = [])
{
    $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    // Check if state exists
    $DB->vals = array($userID);
    $DB->types = "i";
    $DB->sql = "SELECT stateID FROM " . $DB->pre . "wa_conversation_state WHERE userID = ?";
    $existing = $DB->dbRow();

    if ($existing) {
        $DB->table = $DB->pre . "wa_conversation_state";
        $DB->data = [
            'fromNumber' => $fromNumber,
            'currentFlow' => $flow,
            'currentStep' => $step,
            'flowData' => json_encode($flowData),
            'expiresAt' => $expiresAt
        ];
        $DB->dbUpdate("stateID='" . intval($existing['stateID']) . "'");
    } else {
        $DB->table = $DB->pre . "wa_conversation_state";
        $DB->data = [
            'userID' => $userID,
            'fromNumber' => $fromNumber,
            'currentFlow' => $flow,
            'currentStep' => $step,
            'flowData' => json_encode($flowData),
            'expiresAt' => $expiresAt
        ];
        $DB->dbInsert();
    }
}

/**
 * Clear conversation state
 */
function clearConversationState($DB, $userID)
{
    $DB->vals = array($userID);
    $DB->types = "i";
    $DB->sql = "DELETE FROM " . $DB->pre . "wa_conversation_state WHERE userID = ?";
    $DB->dbQuery();
}

/**
 * Main message router
 */
function routeMessage($DB, $wa, $user, $fromNumber, $messageBody, $buttonPayload, $messageType)
{
    $userID = $user['userID'];
    $text = strtolower(trim($messageBody));

    // Cancel at any point
    if ($text === 'cancel' || $buttonPayload === 'cancel') {
        clearConversationState($DB, $userID);
        sendWithMenuButton($wa, $fromNumber, "Cancelled.");
        return;
    }

    // "Hi/menu" always goes to main menu, clearing any active flow
    if ($buttonPayload === 'menu_main' || preg_match('/^(hi|hello|hey|menu|start|namaste|hola)$/i', $text)) {
        clearConversationState($DB, $userID);
        handleMainMenu($DB, $wa, $user, $fromNumber);
        return;
    }

    // Check for leave approval button callbacks (from admin notifications)
    if (strpos($buttonPayload, 'approve_leave_') === 0 || strpos($buttonPayload, 'reject_leave_') === 0) {
        handleLeaveApprovalCallback($DB, $wa, $user, $fromNumber, $buttonPayload);
        return;
    }

    // Driver overtime mark-out confirmation (sent by dvNotifyOwner). Confirming sets
    // isVerify=1 on the record; querying leaves it unverified for review in the admin panel.
    if (strpos($buttonPayload, 'dvok_') === 0 || strpos($buttonPayload, 'dvq_') === 0) {
        require_once COREPATH . '/wa-driver-handlers.inc.php';
        handleDriverOvertimeConfirm($DB, $wa, $fromNumber, $buttonPayload);
        return;
    }

    // Check for active conversation flow
    $state = getConversationState($DB, $userID, $fromNumber);

    if ($state && $state['currentFlow']) {
        $flowData = json_decode($state['flowData'], true) ?: [];

        switch ($state['currentFlow']) {
            case 'apply_leave':
                handleApplyLeaveFlow($DB, $wa, $user, $fromNumber, $messageBody, $buttonPayload, $state['currentStep'], $flowData);
                return;
            case 'team_leave':
                handleTeamLeaveFlow($DB, $wa, $user, $fromNumber, $messageBody, $buttonPayload, $state['currentStep'], $flowData);
                return;
        }
    }

    // Route by button payload (interactive replies)
    if ($buttonPayload) {
        switch ($buttonPayload) {
            case 'menu_leave_balance':
                handleLeaveBalance($DB, $wa, $user, $fromNumber);
                return;
            case 'menu_apply_leave':
                handleApplyLeaveStart($DB, $wa, $user, $fromNumber, $userID, $user['displayName'], false);
                return;
            case 'menu_attendance':
                handleTodayAttendance($DB, $wa, $user, $fromNumber);
                return;
            case 'menu_holidays':
                handleHolidayCalendar($DB, $wa, $fromNumber);
                return;
            case 'menu_profile':
                handleMyProfile($wa, $user, $fromNumber);
                return;
            case 'menu_team_leave':
                handleTeamLeaveStart($DB, $wa, $user, $fromNumber);
                return;
        }
    }

    // Route by text keywords
    if (preg_match('/leave\s*balance|check\s*balance/i', $text)) {
        handleLeaveBalance($DB, $wa, $user, $fromNumber);
        return;
    }

    if (preg_match('/apply\s*(for\s*)?leave/i', $text)) {
        handleApplyLeaveStart($DB, $wa, $user, $fromNumber, $userID, $user['displayName'], false);
        return;
    }

    if (preg_match('/attendance|check.?in|check.?out/i', $text)) {
        handleTodayAttendance($DB, $wa, $user, $fromNumber);
        return;
    }

    if (preg_match('/holiday|chutti|holidays/i', $text)) {
        handleHolidayCalendar($DB, $wa, $fromNumber);
        return;
    }

    if (preg_match('/profile|my\s*info|my\s*details/i', $text)) {
        handleMyProfile($wa, $user, $fromNumber);
        return;
    }

    if (preg_match('/team\s*leave/i', $text)) {
        if (isManager($DB, $userID)) {
            handleTeamLeaveStart($DB, $wa, $user, $fromNumber);
            return;
        }
    }

    // Default: show main menu
    handleMainMenu($DB, $wa, $user, $fromNumber);
}

// ==========================================
// HANDLER: Main Menu
// ==========================================
function handleMainMenu($DB, $wa, $user, $fromNumber)
{
    $firstName = explode(' ', $user['displayName'])[0];
    $isManagerUser = isManager($DB, $user['userID']);

    $rows = [
        ['id' => 'menu_leave_balance', 'title' => 'Check Leave Balance', 'description' => 'View your leave balance for this year'],
        ['id' => 'menu_apply_leave', 'title' => 'Apply for Leave', 'description' => 'Submit a new leave application'],
        ['id' => 'menu_attendance', 'title' => "Today's Attendance", 'description' => 'Check your check-in/out times'],
        ['id' => 'menu_holidays', 'title' => 'Holiday Calendar', 'description' => 'View upcoming holidays'],
        ['id' => 'menu_profile', 'title' => 'My Profile', 'description' => 'View your employee details'],
    ];

    if ($isManagerUser) {
        $rows[] = ['id' => 'menu_team_leave', 'title' => 'Team Leave', 'description' => 'Apply or check leave for your team'];
    }

    $sections = [
        [
            'title' => 'HRMS Services',
            'rows' => $rows
        ]
    ];

    $wa->sendInteractiveList(
        $fromNumber,
        'BES HRMS',
        "Hi $firstName! How can I help you today?",
        'Select an option',
        $sections
    );
}

// ==========================================
// HANDLER: Leave Balance
// ==========================================
function handleLeaveBalance($DB, $wa, $user, $fromNumber)
{
    $summary = getLeaveBalanceSummary($user['userID']);

    if (empty($summary['balances'])) {
        $wa->sendText($fromNumber, "No leave balance records found. Please contact HR.");
        return;
    }

    $msg = "*Leave Balance (FY " . $summary['financialYear'] . ")*\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n";

    foreach ($summary['balances'] as $b) {
        $used = $b['used'];
        $total = $b['credited'];
        $available = $b['available'];
        $pending = $b['pending'];

        $msg .= "\n*" . $b['leaveTypeName'] . "*\n";
        $msg .= "Credited: $total | Used: $used | Available: $available";
        if ($pending > 0) {
            $msg .= " | Pending: $pending";
        }
        $msg .= "\n";
    }

    if ($summary['compOff']['available'] > 0) {
        $msg .= "\n*Comp-Off Balance*: " . $summary['compOff']['available'] . " day(s)\n";
    }

    $msg .= "\n━━━━━━━━━━━━━━━━━━━━";

    sendWithMenuButton($wa, $fromNumber, $msg);
}

// ==========================================
// HANDLER: Apply Leave — Start Flow
// ==========================================
function handleApplyLeaveStart($DB, $wa, $user, $fromNumber, $targetUserID, $targetName, $isOnBehalf)
{
    $leaveTypes = getLeaveTypesConfig(true);

    if (empty($leaveTypes)) {
        $wa->sendText($fromNumber, "No leave types configured. Please contact HR.");
        return;
    }

    // Filter out non-leave types (leaveSubType = 1 is Late Coming Request)
    $validTypes = [];
    foreach ($leaveTypes as $lt) {
        if (($lt['leaveSubType'] ?? 0) != 1) {
            $validTypes[] = $lt;
        }
    }

    $flowData = [
        'targetUserID' => $targetUserID,
        'targetName' => $targetName,
        'appliedByUserID' => $user['userID'],
        'appliedByName' => $user['displayName'],
        'isOnBehalf' => $isOnBehalf
    ];

    // Use interactive list for leave types
    $rows = [];
    foreach ($validTypes as $lt) {
        $rows[] = [
            'id' => 'lt_' . $lt['leaveTypeID'],
            'title' => substr($lt['leaveTypeName'], 0, 24),
            'description' => 'Annual: ' . ($lt['annualEntitlement'] ?? 'N/A') . ' days'
        ];
    }

    $sections = [['title' => 'Leave Types', 'rows' => $rows]];

    $prefix = $isOnBehalf ? "Applying leave for *{$targetName}*\n\n" : "";

    setConversationState($DB, $user['userID'], $fromNumber, 'apply_leave', 0, $flowData);

    $wa->sendInteractiveList(
        $fromNumber,
        'Apply Leave',
        $prefix . "Select the type of leave:",
        'Choose Leave Type',
        $sections
    );
}

// ==========================================
// HANDLER: Apply Leave — Multi-step Flow
// ==========================================
function handleApplyLeaveFlow($DB, $wa, $user, $fromNumber, $messageBody, $buttonPayload, $step, $flowData)
{
    $userID = $user['userID'];

    switch ($step) {
        case 0:
            // Expecting leave type selection
            $payload = $buttonPayload ?: $messageBody;
            if (strpos($payload, 'lt_') === 0) {
                $leaveTypeID = intval(substr($payload, 3));
            } else {
                $wa->sendText($fromNumber, "Please select a leave type from the list above.");
                return;
            }

            // Get leave type name
            $DB->vals = array($leaveTypeID, 1);
            $DB->types = "ii";
            $DB->sql = "SELECT leaveTypeName FROM " . $DB->pre . "leave_type WHERE leaveTypeID = ? AND status = ?";
            $lt = $DB->dbRow();

            if (!$lt) {
                $wa->sendText($fromNumber, "Invalid leave type. Please try again.");
                return;
            }

            $flowData['leaveTypeID'] = $leaveTypeID;
            $flowData['leaveTypeName'] = $lt['leaveTypeName'];
            setConversationState($DB, $userID, $fromNumber, 'apply_leave', 1, $flowData);

            $wa->sendText($fromNumber, "*{$lt['leaveTypeName']}* selected.\n\nFrom which date?\nFormat: *DD-MM-YYYY* (e.g. 10-03-2026)");
            break;

        case 1:
            // Expecting from date
            $text = trim($messageBody);
            $fromDate = parseDateInput($text);

            if (!$fromDate) {
                $wa->sendText($fromNumber, "Invalid date format. Please enter date as *DD-MM-YYYY* (e.g. 10-03-2026)");
                return;
            }

            $flowData['fromDate'] = $fromDate;
            setConversationState($DB, $userID, $fromNumber, 'apply_leave', 2, $flowData);

            $wa->sendText($fromNumber, "From: *$fromDate*\n\nTo which date?\nFormat: *DD-MM-YYYY*\n\nType *same* if it's a single day leave.");
            break;

        case 2:
            // Expecting to date
            $text = strtolower(trim($messageBody));

            if ($text === 'same') {
                $toDate = $flowData['fromDate'];
            } else {
                $toDate = parseDateInput($text);
                if (!$toDate) {
                    $wa->sendText($fromNumber, "Invalid date format. Please enter date as *DD-MM-YYYY* or type *same* for single day.");
                    return;
                }
            }

            // Validate to >= from
            if (strtotime($toDate) < strtotime($flowData['fromDate'])) {
                $wa->sendText($fromNumber, "To-date cannot be before from-date. Please enter a valid to-date.");
                return;
            }

            $flowData['toDate'] = $toDate;

            // Calculate number of days
            $days = floor((strtotime($toDate) - strtotime($flowData['fromDate'])) / 86400) + 1;
            $flowData['days'] = $days;

            setConversationState($DB, $userID, $fromNumber, 'apply_leave', 3, $flowData);

            $wa->sendText($fromNumber, "From: *{$flowData['fromDate']}*\nTo: *$toDate* ($days day" . ($days > 1 ? 's' : '') . ")\n\nPlease provide a reason for leave:");
            break;

        case 3:
            // Expecting reason
            $reason = trim($messageBody);
            if (strlen($reason) < 3) {
                $wa->sendText($fromNumber, "Please provide a valid reason (at least 3 characters).");
                return;
            }

            $flowData['reason'] = $reason;
            setConversationState($DB, $userID, $fromNumber, 'apply_leave', 4, $flowData);

            // Show summary with confirm/cancel buttons
            $targetName = $flowData['targetName'];
            $isOnBehalf = $flowData['isOnBehalf'] ?? false;

            $summary = "*Leave Application Summary*\n";
            $summary .= "━━━━━━━━━━━━━━━━━━━━\n";
            if ($isOnBehalf) {
                $summary .= "Employee: *{$targetName}*\n";
            }
            $summary .= "Type: *{$flowData['leaveTypeName']}*\n";
            $summary .= "From: *{$flowData['fromDate']}*\n";
            $summary .= "To: *{$flowData['toDate']}*\n";
            $summary .= "Days: *{$flowData['days']}*\n";
            $summary .= "Reason: {$flowData['reason']}\n";
            $summary .= "━━━━━━━━━━━━━━━━━━━━\n";
            $summary .= "Please confirm or cancel.";

            $buttons = [
                ['id' => 'confirm_leave', 'title' => 'Confirm'],
                ['id' => 'cancel', 'title' => 'Cancel']
            ];

            $wa->sendInteractiveButtons($fromNumber, $summary, $buttons);
            break;

        case 4:
            // Expecting confirm/cancel
            if ($buttonPayload === 'confirm_leave') {
                $result = submitLeaveApplication($DB, $wa, $user, $fromNumber, $flowData);
                clearConversationState($DB, $userID);
            } else {
                clearConversationState($DB, $userID);
                sendWithMenuButton($wa, $fromNumber, "Leave application cancelled.");
            }
            break;
    }
}

/**
 * Submit the leave application to database
 */
function submitLeaveApplication($DB, $wa, $user, $fromNumber, $flowData)
{
    $targetUserID = $flowData['targetUserID'];
    $isOnBehalf = $flowData['isOnBehalf'] ?? false;
    $reason = $flowData['reason'];

    if ($isOnBehalf) {
        $reason .= " [Applied by {$flowData['appliedByName']} via WhatsApp]";
    } else {
        $reason .= " [Applied via WhatsApp]";
    }

    // Format dates for DB (fromDate/toDate stored as VARCHAR in mx_leave)
    $fromDate = $flowData['fromDate']; // Already YYYY-MM-DD
    $toDate = $flowData['toDate'];

    // Insert into mx_leave
    $DB->table = $DB->pre . "leave";
    $DB->data = [
        'userID' => $targetUserID,
        'leaveType' => $flowData['leaveTypeID'],
        'fromDate' => $fromDate,
        'toDate' => $toDate,
        'reason' => $reason,
        'leaveStatus' => 'Pending',
        'dateAdded' => date('Y-m-d H:i:s'),
        'status' => 1
    ];
    $DB->dbInsert();
    $leaveID = $DB->insertID;

    if (!$leaveID) {
        $wa->sendText($fromNumber, "Failed to submit leave. Please try again or contact HR.");
        return;
    }

    // Insert leave details (one row per day)
    $startDate = strtotime($fromDate);
    $endDate = strtotime($toDate);

    for ($date = $startDate; $date <= $endDate; $date = strtotime('+1 day', $date)) {
        $leaveDate = date('Y-m-d', $date);
        $DB->table = $DB->pre . "leave_details";
        $DB->data = [
            'leaveID' => $leaveID,
            'userID' => $targetUserID,
            'leaveDate' => $leaveDate,
            'leaveTime' => '',
            'lType' => 1, // Full day
            'leaveStatus' => 'Pending',
            'status' => 1
        ];
        $DB->dbInsert();
    }

    // Get target user name for notification
    $targetName = $flowData['targetName'];
    $leaveTypeName = $flowData['leaveTypeName'];
    $days = $flowData['days'];

    // Send confirmation to applicant
    $msg = "Leave application submitted successfully!\n\n";
    $msg .= "*Leave ID:* #$leaveID\n";
    $msg .= "*Type:* $leaveTypeName\n";
    $msg .= "*From:* $fromDate\n";
    $msg .= "*To:* $toDate\n";
    $msg .= "*Days:* $days\n";
    $msg .= "*Status:* Pending\n\n";
    $msg .= "You will be notified once it's approved or rejected.";

    sendWithMenuButton($wa, $fromNumber, $msg);

    // Notify admin numbers for approval
    notifyAdminsForLeaveApproval($DB, $wa, $leaveID, $targetName, $targetUserID, $leaveTypeName, $fromDate, $toDate, $days, $reason);
}

/**
 * Notify admin numbers about new leave application
 */
function notifyAdminsForLeaveApproval($DB, $wa, $leaveID, $employeeName, $employeeID, $leaveType, $fromDate, $toDate, $days, $reason)
{
    $adminNumbers = explode(',', WA_ADMIN_NUMBERS);

    $msg = "*New Leave Application*\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
    $msg .= "Employee: *$employeeName*\n";
    $msg .= "Leave Type: *$leaveType*\n";
    $msg .= "From: *$fromDate*\n";
    $msg .= "To: *$toDate* ($days day" . ($days > 1 ? 's' : '') . ")\n";
    $msg .= "Reason: $reason\n";
    $msg .= "Leave ID: #$leaveID\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━";

    $buttons = [
        ['id' => 'approve_leave_' . $leaveID, 'title' => 'Approve'],
        ['id' => 'reject_leave_' . $leaveID, 'title' => 'Reject']
    ];

    foreach ($adminNumbers as $adminNumber) {
        $adminNumber = trim($adminNumber);
        if (!empty($adminNumber)) {
            $wa->sendInteractiveButtons($adminNumber, $msg, $buttons);
        }
    }
}

/**
 * Handle leave approval/rejection callback from admin
 */
function handleLeaveApprovalCallback($DB, $wa, $user, $fromNumber, $buttonPayload)
{
    // Check if this user is an admin
    $adminNumbers = explode(',', WA_ADMIN_NUMBERS);
    $senderPhone = preg_replace('/[^0-9]/', '', $fromNumber);

    $isAdmin = false;
    foreach ($adminNumbers as $num) {
        if (trim($num) === $senderPhone) {
            $isAdmin = true;
            break;
        }
    }

    if (!$isAdmin) {
        $wa->sendText($fromNumber, "You are not authorized to approve/reject leave.");
        return;
    }

    // Parse action
    $isApproval = (strpos($buttonPayload, 'approve_leave_') === 0);
    $leaveID = intval(str_replace(['approve_leave_', 'reject_leave_'], '', $buttonPayload));

    if (!$leaveID) {
        $wa->sendText($fromNumber, "Invalid leave ID.");
        return;
    }

    // Get leave details
    $DB->vals = array($leaveID, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT l.*, u.displayName, u.whatsappNumber, u.userMobile
                FROM " . $DB->pre . "leave l
                JOIN " . $DB->pre . "x_admin_user u ON l.userID = u.userID
                WHERE l.leaveID = ? AND l.status = ?";
    $leave = $DB->dbRow();

    if (!$leave) {
        $wa->sendText($fromNumber, "Leave application #$leaveID not found.");
        return;
    }

    if ($leave['leaveStatus'] !== 'Pending') {
        $wa->sendText($fromNumber, "Leave #$leaveID has already been " . strtolower($leave['leaveStatus']) . ".");
        return;
    }

    $newStatus = $isApproval ? 'Approved' : 'Disapproved';
    $approverName = $user['displayName'];

    // Update leave status
    $DB->table = $DB->pre . "leave";
    $DB->data = [
        'leaveStatus' => $newStatus,
        'approvedBy' => $user['userID'],
        'approvedDate' => date('Y-m-d H:i:s')
    ];
    $DB->dbUpdate("leaveID='" . intval($leaveID) . "'");

    // Update leave details status
    $DB->table = $DB->pre . "leave_details";
    $DB->data = ['leaveStatus' => $newStatus];
    $DB->dbUpdate("leaveID='" . intval($leaveID) . "'");

    // If approved, deduct leave balance
    if ($isApproval) {
        $days = floor((strtotime($leave['toDate']) - strtotime($leave['fromDate'])) / 86400) + 1;
        deductLeaveBalance($leave['userID'], $leave['leaveType'], $days);
    }

    // Confirm to admin
    $action = $isApproval ? 'approved' : 'rejected';
    sendWithMenuButton($wa, $fromNumber, "Leave #$leaveID for *{$leave['displayName']}* has been *$action* by $approverName.");

    // Notify employee
    $employeePhone = $leave['whatsappNumber'] ?: $leave['userMobile'];
    if ($employeePhone) {
        // Add country code if not present
        $employeePhone = preg_replace('/[^0-9]/', '', $employeePhone);
        if (strlen($employeePhone) === 10) {
            $employeePhone = '91' . $employeePhone;
        }

        $leaveTypeName = '';
        $DB->vals = array($leave['leaveType']);
        $DB->types = "i";
        $DB->sql = "SELECT leaveTypeName FROM " . $DB->pre . "leave_type WHERE leaveTypeID = ?";
        $lt = $DB->dbRow();
        if ($lt) $leaveTypeName = $lt['leaveTypeName'];

        $emoji = $isApproval ? '' : '';
        $msg = "*Leave Update* $emoji\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Your leave application has been *$action*.\n\n";
        $msg .= "Leave ID: #$leaveID\n";
        $msg .= "Type: $leaveTypeName\n";
        $msg .= "From: {$leave['fromDate']}\n";
        $msg .= "To: {$leave['toDate']}\n";
        $msg .= "By: $approverName\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━";

        sendWithMenuButton($wa, $employeePhone, $msg);
    }
}

// ==========================================
// HANDLER: Today's Attendance
// ==========================================
function handleTodayAttendance($DB, $wa, $user, $fromNumber)
{
    $today = date('Y-m-d');
    $userID = $user['userID'];

    $DB->vals = array($userID, $today);
    $DB->types = "is";
    $DB->sql = "SELECT * FROM " . $DB->pre . "attendance WHERE userID = ? AND attendanceDate = ?";
    $att = $DB->dbRow();

    $msg = "*Today's Attendance*\n";
    $msg .= "Date: " . date('d-M-Y') . "\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n";

    if (!$att) {
        $msg .= "\nNo attendance record found for today.\n";
        $msg .= "You may not have checked in yet.";
    } else {
        $checkIn = $att['checkIn'] ? date('h:i A', strtotime($att['checkIn'])) : 'Not recorded';
        $checkOut = $att['checkOut'] ? date('h:i A', strtotime($att['checkOut'])) : 'Not yet';
        $status = ucfirst($att['attendanceStatus'] ?? 'N/A');

        $msg .= "\nStatus: *$status*\n";
        $msg .= "Check-in: *$checkIn*\n";
        $msg .= "Check-out: *$checkOut*\n";

        if ($att['isLate']) {
            $msg .= "Late: Yes (" . $att['lateMinutes'] . " min)\n";
        }

        if ($att['workingHours']) {
            $msg .= "Working Hours: " . round($att['workingHours'], 1) . " hrs\n";
        }
    }

    $msg .= "\n━━━━━━━━━━━━━━━━━━━━";
    sendWithMenuButton($wa, $fromNumber, $msg);
}

// ==========================================
// HANDLER: Holiday Calendar
// ==========================================
function handleHolidayCalendar($DB, $wa, $fromNumber)
{
    $today = date('Y-m-d');

    $DB->vals = array($today, 1);
    $DB->types = "si";
    $DB->sql = "SELECT holidayName, holidayDate, holidayType
                FROM " . $DB->pre . "holiday_master
                WHERE holidayDate >= ? AND status = ?
                ORDER BY holidayDate ASC
                LIMIT 10";
    $holidays = $DB->dbRows();

    $msg = "*Upcoming Holidays*\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n";

    if (empty($holidays)) {
        $msg .= "\nNo upcoming holidays found.";
    } else {
        foreach ($holidays as $h) {
            $date = date('d-M-Y (D)', strtotime($h['holidayDate']));
            $type = ucfirst($h['holidayType'] ?? '');
            $msg .= "\n*{$h['holidayName']}*\n";
            $msg .= "$date";
            if ($type) $msg .= " — $type";
            $msg .= "\n";
        }
    }

    $msg .= "\n━━━━━━━━━━━━━━━━━━━━";
    sendWithMenuButton($wa, $fromNumber, $msg);
}

// ==========================================
// HANDLER: My Profile
// ==========================================
function handleMyProfile($wa, $user, $fromNumber)
{
    $msg = "*My Profile*\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    $msg .= "Name: *{$user['displayName']}*\n";

    if (!empty($user['employeeCode'])) {
        $msg .= "Employee Code: {$user['employeeCode']}\n";
    }
    if (!empty($user['designation'])) {
        $msg .= "Designation: {$user['designation']}\n";
    }
    if (!empty($user['department'])) {
        $msg .= "Department: {$user['department']}\n";
    }
    if (!empty($user['dateOfJoining'])) {
        $msg .= "Joining Date: " . date('d-M-Y', strtotime($user['dateOfJoining'])) . "\n";
    }
    if (!empty($user['userMobile'])) {
        $msg .= "Mobile: {$user['userMobile']}\n";
    }

    $msg .= "\n━━━━━━━━━━━━━━━━━━━━";
    sendWithMenuButton($wa, $fromNumber, $msg);
}

// ==========================================
// HANDLER: Team Leave — Start
// ==========================================
function handleTeamLeaveStart($DB, $wa, $user, $fromNumber)
{
    $userID = $user['userID'];

    if (!isManager($DB, $userID)) {
        $wa->sendText($fromNumber, "You don't have any team members assigned to you.");
        return;
    }

    $members = getTeamMembers($DB, $userID);

    if (empty($members)) {
        $wa->sendText($fromNumber, "No active team members found.");
        return;
    }

    $rows = [];
    foreach ($members as $m) {
        $rows[] = [
            'id' => 'team_' . $m['userID'],
            'title' => substr($m['displayName'], 0, 24),
            'description' => $m['designation'] ?: $m['employeeCode'] ?: ''
        ];
    }

    $sections = [['title' => 'Your Team', 'rows' => $rows]];

    setConversationState($DB, $userID, $fromNumber, 'team_leave', 0, []);

    $wa->sendInteractiveList(
        $fromNumber,
        'Team Leave',
        "Select a team member:",
        'Choose Member',
        $sections
    );
}

// ==========================================
// HANDLER: Team Leave — Multi-step Flow
// ==========================================
function handleTeamLeaveFlow($DB, $wa, $user, $fromNumber, $messageBody, $buttonPayload, $step, $flowData)
{
    $userID = $user['userID'];

    switch ($step) {
        case 0:
            // Expecting team member selection
            $payload = $buttonPayload ?: $messageBody;
            if (strpos($payload, 'team_') === 0) {
                $targetUserID = intval(substr($payload, 5));
            } else {
                $wa->sendText($fromNumber, "Please select a team member from the list.");
                return;
            }

            // Get target user info
            $DB->vals = array($targetUserID, $userID, 1);
            $DB->types = "iii";
            $DB->sql = "SELECT userID, displayName, employeeCode FROM " . $DB->pre . "x_admin_user
                        WHERE userID = ? AND managerID = ? AND status = ?";
            $target = $DB->dbRow();

            if (!$target) {
                $wa->sendText($fromNumber, "Invalid team member selection.");
                return;
            }

            $flowData['targetUserID'] = $targetUserID;
            $flowData['targetName'] = $target['displayName'];
            setConversationState($DB, $userID, $fromNumber, 'team_leave', 1, $flowData);

            $buttons = [
                ['id' => 'team_check_balance', 'title' => 'Check Balance'],
                ['id' => 'team_apply_leave', 'title' => 'Apply Leave']
            ];

            $wa->sendInteractiveButtons(
                $fromNumber,
                "Selected: *{$target['displayName']}*\n\nWhat would you like to do?",
                $buttons
            );
            break;

        case 1:
            // Expecting action (check balance or apply leave)
            $payload = $buttonPayload ?: strtolower(trim($messageBody));

            if ($payload === 'team_check_balance' || strpos($payload, 'balance') !== false) {
                // Show team member's leave balance
                $summary = getLeaveBalanceSummary($flowData['targetUserID']);
                clearConversationState($DB, $userID);

                if (empty($summary['balances'])) {
                    $wa->sendText($fromNumber, "No leave balance records found for {$flowData['targetName']}.");
                    return;
                }

                $msg = "*Leave Balance: {$flowData['targetName']}*\n";
                $msg .= "FY " . $summary['financialYear'] . "\n";
                $msg .= "━━━━━━━━━━━━━━━━━━━━\n";

                foreach ($summary['balances'] as $b) {
                    $msg .= "\n*{$b['leaveTypeName']}*\n";
                    $msg .= "Credited: {$b['credited']} | Used: {$b['used']} | Available: {$b['available']}\n";
                }

                $msg .= "\n━━━━━━━━━━━━━━━━━━━━";
                sendWithMenuButton($wa, $fromNumber, $msg);

            } elseif ($payload === 'team_apply_leave' || strpos($payload, 'apply') !== false) {
                // Switch to apply leave flow for the team member
                clearConversationState($DB, $userID);
                handleApplyLeaveStart($DB, $wa, $user, $fromNumber, $flowData['targetUserID'], $flowData['targetName'], true);

            } else {
                $wa->sendText($fromNumber, "Please select *Check Balance* or *Apply Leave*.");
            }
            break;
    }
}

// ==========================================
// CUSTOMER FLOWS (Phase 2 — Unregistered Users)
// ==========================================

/**
 * Show customer welcome menu
 */
function handleCustomerMenu($wa, $fromNumber)
{
    $sections = [
        [
            'title' => 'How can we help?',
            'rows' => [
                ['id' => 'cust_pump', 'title' => 'Find a Pump', 'description' => 'AI-powered pump recommendation'],
                ['id' => 'cust_motor', 'title' => 'Motor Inquiry', 'description' => 'Industrial motor requirement'],
                ['id' => 'cust_sales', 'title' => 'Talk to Sales', 'description' => 'Connect with our sales team'],
                ['id' => 'cust_locations', 'title' => 'Our Locations', 'description' => 'Mumbai & Ahmedabad offices'],
            ]
        ]
    ];

    $wa->sendInteractiveList(
        $fromNumber,
        'BES',
        "Welcome to *Bombay Engineering Syndicate*!\nSince 1957, your trusted partner for industrial motors & pumps.\n\nHow can we help you today?",
        'Select an option',
        $sections
    );
}

/**
 * Route messages from unregistered (customer) numbers
 * Uses a separate conversation state with userID=0 prefix keyed by phone number
 */
function routeCustomerMessage($DB, $wa, $fromNumber, $messageBody, $buttonPayload, $messageType)
{
    $text = strtolower(trim($messageBody));

    // Cancel at any point
    if ($text === 'cancel' || $buttonPayload === 'cancel') {
        clearCustomerState($DB, $fromNumber);
        handleCustomerMenu($wa, $fromNumber);
        return;
    }

    // "Hi/menu/start" always goes back to customer menu
    if ($buttonPayload === 'cust_menu' || preg_match('/^(hi|hello|hey|menu|start|namaste|hola)$/i', $text)) {
        clearCustomerState($DB, $fromNumber);
        handleCustomerMenu($wa, $fromNumber);
        return;
    }

    // Check for active customer conversation flow
    $state = getCustomerState($DB, $fromNumber);

    if ($state && $state['currentFlow']) {
        $flowData = json_decode($state['flowData'], true) ?: [];

        switch ($state['currentFlow']) {
            case 'pump_search':
                handlePumpSearchFlow($DB, $wa, $fromNumber, $messageBody, $buttonPayload, $state['currentStep'], $flowData);
                return;
            case 'motor_inquiry':
                handleMotorInquiryFlow($DB, $wa, $fromNumber, $messageBody, $buttonPayload, $state['currentStep'], $flowData);
                return;
        }
    }

    // Route by button payload
    if ($buttonPayload) {
        switch ($buttonPayload) {
            case 'cust_pump':
                handlePumpSearchStart($DB, $wa, $fromNumber);
                return;
            case 'cust_motor':
                handleMotorInquiryStart($DB, $wa, $fromNumber);
                return;
            case 'cust_sales':
                handleTalkToSales($wa, $fromNumber);
                return;
            case 'cust_locations':
                handleLocations($wa, $fromNumber);
                return;
        }

        // Location buttons (from Talk to Sales)
        if ($buttonPayload === 'loc_mumbai' || $buttonPayload === 'loc_ahmedabad') {
            handleLocationResponse($wa, $fromNumber, $buttonPayload);
            return;
        }

        // Pump use-case guided question buttons
        if (strpos($buttonPayload, 'pumpcat_') === 0) {
            handlePumpCategorySelection($DB, $wa, $fromNumber, $buttonPayload);
            return;
        }
    }

    // Check for knowledge base match
    $kbMatch = matchKnowledgeBase($text);
    if ($kbMatch) {
        handleKnowledgeBaseMatch($DB, $wa, $fromNumber, $kbMatch, $text);
        return;
    }

    // Try pump matching on free text (customer typed a product query directly)
    $pumpResult = matchPump($text, $DB);
    if ($pumpResult && !empty($pumpResult['matches'])) {
        // Direct match — show results
        showPumpResults($DB, $wa, $fromNumber, $pumpResult, $text);
        return;
    }

    // Default: show customer menu
    handleCustomerMenu($wa, $fromNumber);
}

/**
 * Get customer conversation state (keyed by phone number, userID=0)
 */
function getCustomerState($DB, $fromNumber)
{
    $phone = preg_replace('/[^0-9]/', '', $fromNumber);
    $DB->vals = array($phone);
    $DB->types = "s";
    $DB->sql = "SELECT * FROM " . $DB->pre . "wa_conversation_state WHERE fromNumber = ? AND userID = 0";
    $state = $DB->dbRow();

    if ($state && strtotime($state['expiresAt']) < time()) {
        clearCustomerState($DB, $fromNumber);
        return null;
    }
    return $state;
}

/**
 * Set customer conversation state
 */
function setCustomerState($DB, $fromNumber, $flow, $step, $flowData = [])
{
    $phone = preg_replace('/[^0-9]/', '', $fromNumber);
    $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    $DB->vals = array($phone);
    $DB->types = "s";
    $DB->sql = "SELECT stateID FROM " . $DB->pre . "wa_conversation_state WHERE fromNumber = ? AND userID = 0";
    $existing = $DB->dbRow();

    if ($existing) {
        $DB->table = $DB->pre . "wa_conversation_state";
        $DB->data = [
            'currentFlow' => $flow,
            'currentStep' => $step,
            'flowData' => json_encode($flowData),
            'expiresAt' => $expiresAt
        ];
        $DB->dbUpdate("stateID='" . intval($existing['stateID']) . "'");
    } else {
        $DB->table = $DB->pre . "wa_conversation_state";
        $DB->data = [
            'userID' => 0,
            'fromNumber' => $phone,
            'currentFlow' => $flow,
            'currentStep' => $step,
            'flowData' => json_encode($flowData),
            'expiresAt' => $expiresAt
        ];
        $DB->dbInsert();
    }
}

/**
 * Clear customer conversation state
 */
function clearCustomerState($DB, $fromNumber)
{
    $phone = preg_replace('/[^0-9]/', '', $fromNumber);
    $DB->vals = array($phone);
    $DB->types = "s";
    $DB->sql = "DELETE FROM " . $DB->pre . "wa_conversation_state WHERE fromNumber = ? AND userID = 0";
    $DB->dbQuery();
}

/**
 * Send text with a "Back to Menu" button for customers
 */
function sendWithCustomerMenuButton($wa, $fromNumber, $msg)
{
    $buttons = [
        ['id' => 'cust_menu', 'title' => 'Main Menu']
    ];
    $wa->sendInteractiveButtons($fromNumber, $msg, $buttons);
}

// ==========================================
// PUMP SEARCH FLOW
// ==========================================

/**
 * Start pump search — ask for requirement
 */
function handlePumpSearchStart($DB, $wa, $fromNumber)
{
    setCustomerState($DB, $fromNumber, 'pump_search', 0, []);

    $wa->sendText($fromNumber, "Tell me what you need the pump for.\n\nYou can say things like:\n- \"BMC line pulling\"\n- \"2HP pump for 150ft borewell\"\n- \"DMB10D price\"\n- \"Pump for overhead tank\"\n\nOr just describe your requirement:");
}

/**
 * Handle pump search multi-step flow
 */
function handlePumpSearchFlow($DB, $wa, $fromNumber, $messageBody, $buttonPayload, $step, $flowData)
{
    switch ($step) {
        case 0:
            // Awaiting requirement text
            $text = trim($messageBody);
            if (strlen($text) < 2) {
                $wa->sendText($fromNumber, "Please describe your pump requirement.");
                return;
            }

            // Try matching
            $result = matchPump($text, $DB);

            if ($result && !empty($result['matches'])) {
                showPumpResults($DB, $wa, $fromNumber, $result, $text);
            } else {
                // No match — show guided question
                showPumpGuidedQuestion($DB, $wa, $fromNumber, $text);
            }
            break;

        case 1:
            // Awaiting pump selection (after results shown)
            if ($buttonPayload === 'pump_interested') {
                // Customer wants this pump — capture lead
                setCustomerState($DB, $fromNumber, 'pump_search', 2, $flowData);
                $wa->sendText($fromNumber, "Great choice! To send you a quotation, may I have your name?");
                return;
            }
            if ($buttonPayload === 'pump_more') {
                // Show more results
                $moreMatches = $flowData['moreMatches'] ?? [];
                if (!empty($moreMatches)) {
                    $flowData['moreMatches'] = [];
                    setCustomerState($DB, $fromNumber, 'pump_search', 1, $flowData);
                    foreach ($moreMatches as $match) {
                        $formatted = formatPumpResult($match);
                        if ($formatted['imageUrl']) {
                            $wa->sendImage($fromNumber, $formatted['imageUrl'], $formatted['caption']);
                        } else {
                            $wa->sendText($fromNumber, $formatted['caption']);
                        }
                    }
                    $buttons = [
                        ['id' => 'pump_interested', 'title' => 'Get Quotation'],
                        ['id' => 'cust_sales', 'title' => 'Talk to Sales'],
                        ['id' => 'cust_menu', 'title' => 'Main Menu'],
                    ];
                    $wa->sendInteractiveButtons($fromNumber, "Interested in any of these? Or talk to our sales team directly.", $buttons);
                } else {
                    $buttons = [
                        ['id' => 'cust_sales', 'title' => 'Talk to Sales'],
                        ['id' => 'cust_menu', 'title' => 'Main Menu'],
                    ];
                    $wa->sendInteractiveButtons($fromNumber, "Those were all the matches I found. Want to talk to our sales team?", $buttons);
                }
                return;
            }
            if ($buttonPayload === 'cust_sales') {
                clearCustomerState($DB, $fromNumber);
                handleTalkToSales($wa, $fromNumber);
                return;
            }
            // Free text while waiting for button — try as new requirement
            $result = matchPump(trim($messageBody), $DB);
            if ($result && !empty($result['matches'])) {
                showPumpResults($DB, $wa, $fromNumber, $result, trim($messageBody));
            } else {
                $buttons = [
                    ['id' => 'pump_interested', 'title' => 'Get Quotation'],
                    ['id' => 'cust_sales', 'title' => 'Talk to Sales'],
                    ['id' => 'cust_menu', 'title' => 'Main Menu'],
                ];
                $wa->sendInteractiveButtons($fromNumber, "Would you like a quotation for the pump shown above, or talk to sales?", $buttons);
            }
            break;

        case 2:
            // Awaiting customer name
            $name = trim($messageBody);
            if (strlen($name) < 2) {
                $wa->sendText($fromNumber, "Please enter your name.");
                return;
            }
            $flowData['customerName'] = $name;
            setCustomerState($DB, $fromNumber, 'pump_search', 3, $flowData);
            $wa->sendText($fromNumber, "Thanks $name! Which city are you from?");
            break;

        case 3:
            // Awaiting city
            $city = trim($messageBody);
            if (strlen($city) < 2) {
                $wa->sendText($fromNumber, "Please enter your city.");
                return;
            }
            $flowData['customerCity'] = $city;

            // Create inquiry and send lead email
            clearCustomerState($DB, $fromNumber);
            $inquiry = createPumpInquiry($DB, $wa, $fromNumber, $flowData);

            $assignedOffice = getOfficeByCity($city);
            $officeLabel = ($assignedOffice === 'ahmedabad') ? 'Ahmedabad' : 'Mumbai';

            $msg = "Thank you *{$flowData['customerName']}*!\n\n";
            $msg .= "Our $officeLabel team will contact you within 4 hours with a detailed quotation";
            if (!empty($flowData['selectedPumpTitle'])) {
                $msg .= " for the *{$flowData['selectedPumpTitle']}*";
            }
            $msg .= ".\n\nReference: *#{$inquiry['referenceNumber']}*\n";
            $msg .= "\nFor immediate assistance: +91 98200 42210";

            sendWithCustomerMenuButton($wa, $fromNumber, $msg);
            break;
    }
}

/**
 * Show pump search results
 */
function showPumpResults($DB, $wa, $fromNumber, $result, $rawText)
{
    $matches = $result['matches'];
    $firstMatch = $matches[0];

    // Show first result with image
    $formatted = formatPumpResult($firstMatch);
    $introText = '';

    if ($result['matchType'] === 'model') {
        $introText = '';
    } elseif ($result['matchType'] === 'usecase') {
        $introText = "For *{$result['matchedUseCase']}*, we recommend:\n\n";
    } else {
        $introText = "Based on your requirement, here's our best match:\n\n";
    }

    if ($formatted['imageUrl']) {
        $wa->sendImage($fromNumber, $formatted['imageUrl'], $introText . $formatted['caption']);
    } else {
        $wa->sendText($fromNumber, $introText . $formatted['caption']);
    }

    // Store in flow data for lead capture
    $flowData = [
        'rawRequirement' => $rawText,
        'selectedPumpID' => $firstMatch['pumpID'],
        'selectedPumpDID' => $firstMatch['pumpDID'] ?? null,
        'selectedPumpTitle' => $firstMatch['pumpTitle'],
        'matchType' => $result['matchType'],
        'matchConfidence' => $result['confidence'] ?? 0,
        'parsedParams' => $result['parsedParams'] ?? null,
        'matchedProducts' => array_map(function ($m) {
            return ['pumpID' => $m['pumpID'], 'pumpDID' => $m['pumpDID'] ?? null, 'score' => $m['score'] ?? 0];
        }, $matches),
    ];

    // Store remaining matches for "Show More"
    $moreMatches = array_slice($matches, 1);
    $flowData['moreMatches'] = $moreMatches;

    setCustomerState($DB, $fromNumber, 'pump_search', 1, $flowData);

    // Build buttons
    $buttons = [
        ['id' => 'pump_interested', 'title' => 'Get Quotation'],
    ];
    if (!empty($moreMatches)) {
        $buttons[] = ['id' => 'pump_more', 'title' => 'Show More'];
    }
    $buttons[] = ['id' => 'cust_sales', 'title' => 'Talk to Sales'];

    $wa->sendInteractiveButtons($fromNumber, "Interested? We'll send you a quotation!", $buttons);
}

/**
 * Show guided question when no pump match found
 */
function showPumpGuidedQuestion($DB, $wa, $fromNumber, $rawText)
{
    $flowData = ['rawRequirement' => $rawText];
    setCustomerState($DB, $fromNumber, 'pump_search', 0, $flowData);

    $sections = [
        [
            'title' => 'Pump Application',
            'rows' => [
                ['id' => 'pumpcat_dmb', 'title' => 'BMC Line / Tank', 'description' => 'Municipal line pulling, overhead tank'],
                ['id' => 'pumpcat_bore', 'title' => 'Borewell / Boring', 'description' => 'Submersible borewell pumps'],
                ['id' => 'pumpcat_agri', 'title' => 'Farm / Agriculture', 'description' => 'Agricultural & irrigation pumps'],
                ['id' => 'pumpcat_boost', 'title' => 'Pressure Boost', 'description' => 'High floor / multistory buildings'],
                ['id' => 'pumpcat_open', 'title' => 'Open Well / Kuwa', 'description' => 'Openwell submersible pumps'],
                ['id' => 'pumpcat_other', 'title' => 'Other', 'description' => 'Describe your requirement'],
            ]
        ]
    ];

    $wa->sendInteractiveList(
        $fromNumber,
        'Pump Finder',
        "I'd love to help! What do you need the pump for?",
        'Select Application',
        $sections
    );
}

/**
 * Handle pump category selection from guided question
 */
function handlePumpCategorySelection($DB, $wa, $fromNumber, $buttonPayload)
{
    $catMap = [
        'pumpcat_dmb' => [25],        // DMB-CMB
        'pumpcat_bore' => [27, 28],    // 3" + 4" Borewell
        'pumpcat_agri' => [32, 4],     // Agricultural + Centrifugal
        'pumpcat_boost' => [30],       // Booster
        'pumpcat_open' => [29],        // Openwell
    ];

    if ($buttonPayload === 'pumpcat_other') {
        setCustomerState($DB, $fromNumber, 'pump_search', 0, []);
        $wa->sendText($fromNumber, "Please describe what you need the pump for, and I'll find the best match.");
        return;
    }

    $catIDs = $catMap[$buttonPayload] ?? [24]; // Default mini
    $placeholders = implode(',', array_fill(0, count($catIDs), '?'));
    $types = str_repeat('i', count($catIDs));

    $DB->vals = array_merge($catIDs, [1, 1]);
    $DB->types = $types . "ii";
    $DB->sql = "SELECT pd.pumpDID, pd.pumpID, pd.categoryref, pd.powerHp, pd.powerKw,
                       pd.supplyPhaseD, pd.headRange, pd.dischargeRange, pd.mrp, pd.warrenty,
                       p.pumpTitle, p.seoUri, p.pumpImage, p.categoryPID,
                       pc.categoryTitle, pc.seoUri as catSeoUri
                FROM " . $DB->pre . "pump_detail pd
                JOIN " . $DB->pre . "pump p ON pd.pumpID = p.pumpID
                JOIN " . $DB->pre . "pump_category pc ON p.categoryPID = pc.categoryPID
                WHERE p.categoryPID IN ($placeholders) AND pd.status = ? AND p.status = ?
                ORDER BY pd.powerHp ASC, pd.mrp ASC
                LIMIT 5";
    $results = $DB->dbRows();

    if (empty($results)) {
        sendWithCustomerMenuButton($wa, $fromNumber, "No products found in that category. Please try describing your requirement or talk to our sales team.");
        clearCustomerState($DB, $fromNumber);
        return;
    }

    // Deduplicate by pumpID
    $seen = [];
    $deduped = [];
    foreach ($results as $r) {
        if (!isset($seen[$r['pumpID']])) {
            $r['score'] = 85;
            $r['matchType'] = 'guided';
            $deduped[] = $r;
            $seen[$r['pumpID']] = true;
        }
    }

    $fakeResult = [
        'matches' => array_slice($deduped, 0, 3),
        'confidence' => 0.8,
        'matchType' => 'usecase',
        'matchedUseCase' => 'your selection',
    ];

    showPumpResults($DB, $wa, $fromNumber, $fakeResult, 'guided selection');
}

// ==========================================
// MOTOR INQUIRY FLOW
// ==========================================

/**
 * Start motor inquiry
 */
function handleMotorInquiryStart($DB, $wa, $fromNumber)
{
    setCustomerState($DB, $fromNumber, 'motor_inquiry', 0, []);

    $wa->sendText($fromNumber, "Please describe your motor requirement.\n\nInclude details like: type (LT/HT), power (kW/HP), voltage, application, quantity needed.\n\nOr simply tell us what you need and our team will help!");
}

/**
 * Handle motor inquiry multi-step flow
 */
function handleMotorInquiryFlow($DB, $wa, $fromNumber, $messageBody, $buttonPayload, $step, $flowData)
{
    switch ($step) {
        case 0:
            // Awaiting motor requirement
            $text = trim($messageBody);
            if (strlen($text) < 3) {
                $wa->sendText($fromNumber, "Please describe your motor requirement.");
                return;
            }
            $flowData['requirementText'] = $text;
            setCustomerState($DB, $fromNumber, 'motor_inquiry', 1, $flowData);
            $wa->sendText($fromNumber, "Got it! To send your inquiry to our sales team, may I have your name?");
            break;

        case 1:
            // Awaiting name
            $name = trim($messageBody);
            if (strlen($name) < 2) {
                $wa->sendText($fromNumber, "Please enter your name.");
                return;
            }
            $flowData['customerName'] = $name;
            setCustomerState($DB, $fromNumber, 'motor_inquiry', 2, $flowData);
            $wa->sendText($fromNumber, "Company name? (Type *skip* if not applicable)");
            break;

        case 2:
            // Awaiting company
            $company = trim($messageBody);
            if (strtolower($company) === 'skip' || strtolower($company) === 'na' || strtolower($company) === 'n/a') {
                $company = '';
            }
            $flowData['companyName'] = $company;
            setCustomerState($DB, $fromNumber, 'motor_inquiry', 3, $flowData);
            $wa->sendText($fromNumber, "Your city?");
            break;

        case 3:
            // Awaiting city
            $city = trim($messageBody);
            if (strlen($city) < 2) {
                $wa->sendText($fromNumber, "Please enter your city.");
                return;
            }
            $flowData['customerCity'] = $city;

            // Create inquiry
            clearCustomerState($DB, $fromNumber);
            $inquiry = createMotorInquiry($DB, $wa, $fromNumber, $flowData);

            $assignedOffice = getOfficeByCity($city);
            $officeLabel = ($assignedOffice === 'ahmedabad') ? 'Ahmedabad' : 'Mumbai';

            $msg = "Thank you *{$flowData['customerName']}*!\n\n";
            $msg .= "Our $officeLabel team will contact you within 4 hours regarding your motor requirement.\n\n";
            $msg .= "Reference: *#{$inquiry['referenceNumber']}*\n";
            $msg .= "\nFor immediate assistance: +91 98200 42210";

            sendWithCustomerMenuButton($wa, $fromNumber, $msg);
            break;
    }
}

// ==========================================
// TALK TO SALES / LOCATIONS
// ==========================================

function handleTalkToSales($wa, $fromNumber)
{
    $buttons = [
        ['id' => 'loc_mumbai', 'title' => 'Mumbai'],
        ['id' => 'loc_ahmedabad', 'title' => 'Ahmedabad'],
    ];
    $wa->sendInteractiveButtons($fromNumber, "Which office is convenient for you?", $buttons);
}

function handleLocations($wa, $fromNumber)
{
    $msg = "*Our Offices*\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    $msg .= "*Mumbai*\n";
    $msg .= "Ground Floor, Modern House,\n17, Dr. V.B. Gandhi Marg,\nKala Ghoda, Fort, Mumbai - 400001\n";
    $msg .= "Tel: +91 98200 42210 / 022-22842982\n";
    $msg .= "Email: besyndicate@gmail.com\n\n";
    $msg .= "*Ahmedabad*\n";
    $msg .= "611-612, Ratnanjali Solitaire,\nSatellite, Ahmedabad - 380015\n";
    $msg .= "Tel: +91 98250 14977 / 079-26929806\n";
    $msg .= "Email: besahmedabad@gmail.com\n\n";
    $msg .= "Mon-Sat: 10:00 AM - 6:00 PM\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━";

    sendWithCustomerMenuButton($wa, $fromNumber, $msg);
}

/**
 * Handle location button responses (from Talk to Sales)
 */
function handleLocationResponse($wa, $fromNumber, $buttonPayload)
{
    if ($buttonPayload === 'loc_mumbai') {
        $msg = "*Mumbai Office*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $msg .= "Call: +91 98200 42210 / 022-22842982\n";
        $msg .= "Email: besyndicate@gmail.com\n\n";
        $msg .= "Address: Ground Floor, Modern House,\n17, Dr. V.B. Gandhi Marg,\nKala Ghoda, Fort, Mumbai - 400001\n\n";
        $msg .= "Mon-Sat: 10:00 AM - 6:00 PM\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━";
    } else {
        $msg = "*Ahmedabad Office*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $msg .= "Call: +91 98250 14977 / 079-26929806\n";
        $msg .= "Email: besahmedabad@gmail.com\n\n";
        $msg .= "Address: 611-612, Ratnanjali Solitaire,\nSatellite, Ahmedabad - 380015\n\n";
        $msg .= "Mon-Sat: 10:00 AM - 6:00 PM\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━";
    }

    sendWithCustomerMenuButton($wa, $fromNumber, $msg);
}

// ==========================================
// KNOWLEDGE BASE
// ==========================================

function handleKnowledgeBaseMatch($DB, $wa, $fromNumber, $kbMatch, $originalText)
{
    // Get article URL
    $DB->vals = array($kbMatch['articleID'], 1);
    $DB->types = "ii";
    $DB->sql = "SELECT knowledgeCenterTitle, seoUri FROM " . $DB->pre . "knowledge_center WHERE knowledgeCenterID = ? AND status = ?";
    $article = $DB->dbRow();

    $msg = "Here's a helpful guide:\n\n";
    $msg .= "*{$kbMatch['title']}*\n";
    if ($article) {
        $msg .= "https://www.bombayengg.net/knowledge-center/{$article['seoUri']}/\n";
    }
    $msg .= "\nWant me to help you find the right product?";

    $buttons = [
        ['id' => 'cust_pump', 'title' => 'Find a Pump'],
        ['id' => 'cust_motor', 'title' => 'Motor Inquiry'],
        ['id' => 'cust_menu', 'title' => 'Main Menu'],
    ];

    $wa->sendInteractiveButtons($fromNumber, $msg, $buttons);
}

// ==========================================
// LEAD CREATION
// ==========================================

/**
 * Create pump inquiry record and send lead email
 */
function createPumpInquiry($DB, $wa, $fromNumber, $flowData)
{
    $phone = preg_replace('/[^0-9]/', '', $fromNumber);
    $refNumber = generateReferenceNumber($DB);
    $city = $flowData['customerCity'] ?? '';
    $assignedTo = getOfficeByCity($city);

    $DB->table = $DB->pre . "wa_inquiry";
    $DB->data = [
        'fromNumber' => $phone,
        'customerName' => $flowData['customerName'] ?? '',
        'city' => $city,
        'productType' => 'pump',
        'requirementText' => $flowData['rawRequirement'] ?? '',
        'parsedParams' => json_encode($flowData['parsedParams'] ?? null),
        'matchedProducts' => json_encode($flowData['matchedProducts'] ?? []),
        'selectedProductID' => $flowData['selectedPumpID'] ?? null,
        'selectedProductTitle' => $flowData['selectedPumpTitle'] ?? '',
        'referenceNumber' => $refNumber,
        'assignedTo' => $assignedTo,
        'inquiryStatus' => 'new',
        'status' => 1,
    ];
    $DB->dbInsert();
    $inquiryID = $DB->insertID;

    // Send lead email
    sendWhatsAppPumpLeadEmail([
        'inquiryID' => $inquiryID,
        'referenceNumber' => $refNumber,
        'customerName' => $flowData['customerName'] ?? '',
        'fromNumber' => $phone,
        'city' => $city,
        'assignedTo' => $assignedTo,
        'requirementText' => $flowData['rawRequirement'] ?? '',
        'selectedPumpTitle' => $flowData['selectedPumpTitle'] ?? '',
        'selectedPumpID' => $flowData['selectedPumpID'] ?? null,
        'matchType' => $flowData['matchType'] ?? '',
        'matchConfidence' => $flowData['matchConfidence'] ?? 0,
    ]);

    // Update emailSentAt
    $DB->table = $DB->pre . "wa_inquiry";
    $DB->data = ['emailSentAt' => date('Y-m-d H:i:s')];
    $DB->dbUpdate("inquiryID='" . intval($inquiryID) . "'");

    return ['inquiryID' => $inquiryID, 'referenceNumber' => $refNumber];
}

/**
 * Create motor inquiry record and send lead email
 */
function createMotorInquiry($DB, $wa, $fromNumber, $flowData)
{
    $phone = preg_replace('/[^0-9]/', '', $fromNumber);
    $refNumber = generateReferenceNumber($DB);
    $city = $flowData['customerCity'] ?? '';
    $assignedTo = getOfficeByCity($city);

    $DB->table = $DB->pre . "wa_inquiry";
    $DB->data = [
        'fromNumber' => $phone,
        'customerName' => $flowData['customerName'] ?? '',
        'companyName' => $flowData['companyName'] ?? '',
        'city' => $city,
        'productType' => 'motor',
        'requirementText' => $flowData['requirementText'] ?? '',
        'referenceNumber' => $refNumber,
        'assignedTo' => $assignedTo,
        'inquiryStatus' => 'new',
        'status' => 1,
    ];
    $DB->dbInsert();
    $inquiryID = $DB->insertID;

    // Send lead email
    sendWhatsAppMotorLeadEmail([
        'inquiryID' => $inquiryID,
        'referenceNumber' => $refNumber,
        'customerName' => $flowData['customerName'] ?? '',
        'companyName' => $flowData['companyName'] ?? '',
        'fromNumber' => $phone,
        'city' => $city,
        'assignedTo' => $assignedTo,
        'requirementText' => $flowData['requirementText'] ?? '',
    ]);

    // Update emailSentAt
    $DB->table = $DB->pre . "wa_inquiry";
    $DB->data = ['emailSentAt' => date('Y-m-d H:i:s')];
    $DB->dbUpdate("inquiryID='" . intval($inquiryID) . "'");

    return ['inquiryID' => $inquiryID, 'referenceNumber' => $refNumber];
}

/**
 * Determine office assignment by city
 */
function getOfficeByCity($city)
{
    $city = strtolower(trim($city));
    $ahmedabadCities = ['ahmedabad', 'ahemdabad', 'amdavad', 'gandhinagar', 'rajkot', 'surat', 'vadodara', 'baroda', 'jamnagar', 'bhavnagar', 'junagadh', 'anand', 'nadiad', 'mehsana', 'palanpur', 'bhuj', 'kutch', 'morbi', 'porbandar', 'veraval', 'diu', 'silvassa', 'vapi', 'navsari', 'bharuch', 'dahod', 'godhra'];
    foreach ($ahmedabadCities as $ac) {
        if (strpos($city, $ac) !== false) {
            return 'ahmedabad';
        }
    }
    return 'mumbai';
}

// ==========================================
// BREVO LEAD EMAILS
// ==========================================

/**
 * Send WhatsApp pump lead email to sales team
 */
function sendWhatsAppPumpLeadEmail($data)
{
    // Brevo may not be loaded in webhook context — check and load
    if (!function_exists('getBrevoService')) {
        // Define Brevo constants if not defined
        if (!defined('BREVO_API_KEY')) {
            // Key must come from config.inc.php or whatsapp-config.php (both gitignored).
            @require_once '/home/bombayengg/whatsapp-config.php';
            if (!defined('BREVO_API_KEY')) define('BREVO_API_KEY', '');
        }
        if (!defined('BREVO_SENDER_EMAIL')) {
            define('BREVO_SENDER_EMAIL', 'info@bombayengg.net');
        }
        if (!defined('BREVO_SENDER_NAME')) {
            define('BREVO_SENDER_NAME', 'Bombay Engineering Syndicate');
        }
        require_once COREPATH . '/brevo.inc.php';
    }

    $brevo = getBrevoService();
    if (!$brevo->isConfigured()) {
        error_log("Brevo not configured - skipping WhatsApp pump lead email");
        return;
    }

    try {
        $htmlContent = buildWhatsAppPumpLeadEmail($data);

        // Route by assigned office
        $recipients = [
            ['email' => 'info@bombayengg.net', 'name' => 'Bombay Engineering'],
            ['email' => 'manishbeskkc@gmail.com', 'name' => 'Manish'],
        ];
        if ($data['assignedTo'] === 'ahmedabad') {
            $recipients[] = ['email' => 'besahmedabad@gmail.com', 'name' => 'BES Ahmedabad'];
        }

        $brevo->sendEmail([
            'to' => $recipients,
            'subject' => "WhatsApp Pump Lead - {$data['customerName']} - {$data['selectedPumpTitle']}",
            'htmlContent' => $htmlContent,
            'tags' => ['whatsapp-lead', 'pump'],
        ]);
    } catch (Exception $e) {
        error_log("WhatsApp Pump Lead Email Error: " . $e->getMessage());
    }
}

/**
 * Send WhatsApp motor lead email to sales team
 */
function sendWhatsAppMotorLeadEmail($data)
{
    if (!function_exists('getBrevoService')) {
        if (!defined('BREVO_API_KEY')) {
            // Key must come from config.inc.php or whatsapp-config.php (both gitignored).
            @require_once '/home/bombayengg/whatsapp-config.php';
            if (!defined('BREVO_API_KEY')) define('BREVO_API_KEY', '');
        }
        if (!defined('BREVO_SENDER_EMAIL')) {
            define('BREVO_SENDER_EMAIL', 'info@bombayengg.net');
        }
        if (!defined('BREVO_SENDER_NAME')) {
            define('BREVO_SENDER_NAME', 'Bombay Engineering Syndicate');
        }
        require_once COREPATH . '/brevo.inc.php';
    }

    $brevo = getBrevoService();
    if (!$brevo->isConfigured()) {
        error_log("Brevo not configured - skipping WhatsApp motor lead email");
        return;
    }

    try {
        $htmlContent = buildWhatsAppMotorLeadEmail($data);

        $recipients = [
            ['email' => 'info@bombayengg.net', 'name' => 'Bombay Engineering'],
            ['email' => 'manishbeskkc@gmail.com', 'name' => 'Manish'],
        ];
        if ($data['assignedTo'] === 'ahmedabad') {
            $recipients[] = ['email' => 'besahmedabad@gmail.com', 'name' => 'BES Ahmedabad'];
        }

        $brevo->sendEmail([
            'to' => $recipients,
            'subject' => "WhatsApp Motor Inquiry - {$data['customerName']}",
            'htmlContent' => $htmlContent,
            'tags' => ['whatsapp-lead', 'motor'],
        ]);
    } catch (Exception $e) {
        error_log("WhatsApp Motor Lead Email Error: " . $e->getMessage());
    }
}

/**
 * Build HTML email for WhatsApp pump lead
 */
function buildWhatsAppPumpLeadEmail($data)
{
    $name = htmlspecialchars($data['customerName'] ?? 'Unknown');
    $phone = htmlspecialchars($data['fromNumber'] ?? 'N/A');
    $city = htmlspecialchars($data['city'] ?? 'N/A');
    $ref = htmlspecialchars($data['referenceNumber'] ?? '');
    $requirement = htmlspecialchars($data['requirementText'] ?? 'N/A');
    $pumpTitle = htmlspecialchars($data['selectedPumpTitle'] ?? 'Not selected');
    $matchType = htmlspecialchars($data['matchType'] ?? 'N/A');
    $confidence = round(($data['matchConfidence'] ?? 0) * 100);
    $assignedTo = ucfirst($data['assignedTo'] ?? 'mumbai');

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        .header { background-color: #25D366; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .section { margin: 15px 0; padding: 15px; background-color: white; border-left: 4px solid #25D366; }
        .section-title { font-weight: bold; color: #25D366; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; width: 30%; color: #555; }
        .badge { display: inline-block; background: #25D366; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>WhatsApp Pump Lead</h2>
            <p>Reference: {$ref} | <span class="badge">Via WhatsApp</span></p>
        </div>
        <div class="content">
            <div class="section">
                <div class="section-title">CUSTOMER INFORMATION</div>
                <table>
                    <tr><td class="label">Name:</td><td><strong>{$name}</strong></td></tr>
                    <tr><td class="label">Phone:</td><td>{$phone}</td></tr>
                    <tr><td class="label">City:</td><td>{$city}</td></tr>
                    <tr><td class="label">Assigned To:</td><td>{$assignedTo} Office</td></tr>
                </table>
            </div>
            <div class="section">
                <div class="section-title">PRODUCT INTEREST</div>
                <table>
                    <tr><td class="label">Selected Pump:</td><td><strong>{$pumpTitle}</strong></td></tr>
                    <tr><td class="label">Original Requirement:</td><td>{$requirement}</td></tr>
                    <tr><td class="label">AI Match Type:</td><td>{$matchType} ({$confidence}% confidence)</td></tr>
                </table>
            </div>
            <p><strong>Action Required:</strong> Contact the customer within 4 hours with a quotation.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Build HTML email for WhatsApp motor lead
 */
function buildWhatsAppMotorLeadEmail($data)
{
    $name = htmlspecialchars($data['customerName'] ?? 'Unknown');
    $company = htmlspecialchars($data['companyName'] ?? 'N/A');
    $phone = htmlspecialchars($data['fromNumber'] ?? 'N/A');
    $city = htmlspecialchars($data['city'] ?? 'N/A');
    $ref = htmlspecialchars($data['referenceNumber'] ?? '');
    $requirement = htmlspecialchars($data['requirementText'] ?? 'N/A');
    $assignedTo = ucfirst($data['assignedTo'] ?? 'mumbai');

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        .header { background-color: #25D366; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .section { margin: 15px 0; padding: 15px; background-color: white; border-left: 4px solid #25D366; }
        .section-title { font-weight: bold; color: #25D366; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; width: 30%; color: #555; }
        .badge { display: inline-block; background: #25D366; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>WhatsApp Motor Inquiry</h2>
            <p>Reference: {$ref} | <span class="badge">Via WhatsApp</span></p>
        </div>
        <div class="content">
            <div class="section">
                <div class="section-title">CUSTOMER INFORMATION</div>
                <table>
                    <tr><td class="label">Name:</td><td><strong>{$name}</strong></td></tr>
                    <tr><td class="label">Company:</td><td>{$company}</td></tr>
                    <tr><td class="label">Phone:</td><td>{$phone}</td></tr>
                    <tr><td class="label">City:</td><td>{$city}</td></tr>
                    <tr><td class="label">Assigned To:</td><td>{$assignedTo} Office</td></tr>
                </table>
            </div>
            <div class="section">
                <div class="section-title">MOTOR REQUIREMENT</div>
                <p style="white-space: pre-wrap;">{$requirement}</p>
            </div>
            <p><strong>Action Required:</strong> Contact the customer within 4 hours.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

// ==========================================
// UTILITY: Parse date input
// ==========================================
function parseDateInput($text)
{
    $text = trim($text);

    // DD-MM-YYYY or DD/MM/YYYY
    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $text, $m)) {
        $day = intval($m[1]);
        $month = intval($m[2]);
        $year = intval($m[3]);
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    // YYYY-MM-DD
    if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/', $text, $m)) {
        $year = intval($m[1]);
        $month = intval($m[2]);
        $day = intval($m[3]);
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    // DD Mon YYYY (e.g. "10 Mar 2026")
    $ts = strtotime($text);
    if ($ts !== false && $ts > strtotime('2020-01-01')) {
        return date('Y-m-d', $ts);
    }

    return null;
}
