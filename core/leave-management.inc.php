<?php
/**
 * HRMS Leave Management Functions
 * Phase 2 & 3: Leave Balance, Carry Forward, Encashment, Comp-Off, Yearly Reset
 */

/**
 * Get current financial year string
 * @return string Format: "2025-26"
 */
function getCurrentFinancialYear()
{
    $fyStartMonth = getHRMSSetting('leave_fy_start_month', 4);
    $currentMonth = intval(date('n'));
    $currentYear = intval(date('Y'));

    if ($currentMonth >= $fyStartMonth) {
        return $currentYear . '-' . substr($currentYear + 1, -2);
    } else {
        return ($currentYear - 1) . '-' . substr($currentYear, -2);
    }
}

/**
 * Get previous financial year string
 * @return string Format: "2024-25"
 */
function getPreviousFinancialYear()
{
    $fyStartMonth = getHRMSSetting('leave_fy_start_month', 4);
    $currentMonth = intval(date('n'));
    $currentYear = intval(date('Y'));

    if ($currentMonth >= $fyStartMonth) {
        return ($currentYear - 1) . '-' . substr($currentYear, -2);
    } else {
        return ($currentYear - 2) . '-' . substr($currentYear - 1, -2);
    }
}

/**
 * Get HRMS setting value
 */
function getHRMSSetting($key, $default = null)
{
    global $DB;

    $DB->vals = array($key);
    $DB->types = "s";
    $DB->sql = "SELECT settingValue FROM " . $DB->pre . "hrms_settings WHERE settingKey = ?";
    $row = $DB->dbRow();

    return $DB->numRows > 0 ? $row['settingValue'] : $default;
}

/**
 * Get all leave types with configuration
 */
function getLeaveTypesConfig($activeOnly = true)
{
    global $DB;

    $sql = "SELECT * FROM " . $DB->pre . "leave_type";
    if ($activeOnly) {
        $DB->vals = array(1);
        $DB->types = "i";
        $sql .= " WHERE status = ?";
    }
    $sql .= " ORDER BY sortOrder, leaveTypeID";

    $DB->sql = $sql;
    return $activeOnly ? $DB->dbRows() : $DB->dbRows();
}

/**
 * Get employee leave balance for current FY
 * @param int $userID
 * @param int|null $leaveTypeID - null for all types
 * @return array
 */
function getEmployeeLeaveBalance($userID, $leaveTypeID = null)
{
    global $DB;

    $fy = getCurrentFinancialYear();

    if ($leaveTypeID) {
        $DB->vals = array($userID, $leaveTypeID, $fy);
        $DB->types = "iis";
        $DB->sql = "SELECT elb.*, lt.leaveTypeName, lt.colorCode, lt.isCarryForward, lt.maxCarryForward,
                           lt.isEncashable, lt.maxAccumulation
                    FROM " . $DB->pre . "employee_leave_balance elb
                    JOIN " . $DB->pre . "leave_type lt ON elb.leaveTypeID = lt.leaveTypeID
                    WHERE elb.userID = ? AND elb.leaveTypeID = ? AND elb.financialYear = ?";
        return $DB->dbRow();
    } else {
        $DB->vals = array($userID, $fy);
        $DB->types = "is";
        $DB->sql = "SELECT elb.*, lt.leaveTypeName, lt.colorCode, lt.isCarryForward, lt.maxCarryForward,
                           lt.isEncashable, lt.maxAccumulation, lt.annualEntitlement
                    FROM " . $DB->pre . "employee_leave_balance elb
                    JOIN " . $DB->pre . "leave_type lt ON elb.leaveTypeID = lt.leaveTypeID
                    WHERE elb.userID = ? AND elb.financialYear = ?
                    ORDER BY lt.sortOrder, lt.leaveTypeID";
        return $DB->dbRows();
    }
}

/**
 * Initialize leave balance for an employee for current FY
 * Should be called when employee joins or at start of new FY
 */
function initializeEmployeeLeaveBalance($userID, $financialYear = null)
{
    global $DB;

    $fy = $financialYear ?: getCurrentFinancialYear();

    // Get employee details for gender check
    $DB->vals = array($userID);
    $DB->types = "i";
    $DB->sql = "SELECT gender, dateOfJoining FROM " . $DB->pre . "x_admin_user WHERE userID = ?";
    $employee = $DB->dbRow();

    if (!$employee) return false;

    $gender = strtolower($employee['gender'] ?? 'all');
    $joiningDate = $employee['dateOfJoining'];
    $serviceDays = $joiningDate ? floor((time() - strtotime($joiningDate)) / 86400) : 0;

    // Get all active leave types
    $leaveTypes = getLeaveTypesConfig(true);

    foreach ($leaveTypes as $lt) {
        // Check gender applicability
        if ($lt['applicableGender'] != 'all' && $lt['applicableGender'] != $gender) {
            continue;
        }

        // Check minimum service requirement
        if ($lt['minServiceDays'] > 0 && $serviceDays < $lt['minServiceDays']) {
            continue;
        }

        // Skip non-leave types (like Late Coming Request)
        if ($lt['leaveSubType'] == 1) {
            continue;
        }

        // Check if balance already exists
        $DB->vals = array($userID, $lt['leaveTypeID'], $fy);
        $DB->types = "iis";
        $DB->sql = "SELECT balanceID FROM " . $DB->pre . "employee_leave_balance
                    WHERE userID = ? AND leaveTypeID = ? AND financialYear = ?";
        $existing = $DB->dbRow();

        if ($existing) continue;

        // Calculate initial credit based on accrual type
        $credited = 0;
        if ($lt['accrualType'] == 'yearly') {
            $credited = floatval($lt['annualEntitlement']);
        } elseif ($lt['accrualType'] == 'monthly') {
            // Calculate pro-rata for remaining months in FY
            $fyStartMonth = getHRMSSetting('leave_fy_start_month', 4);
            $currentMonth = intval(date('n'));
            $remainingMonths = ($currentMonth >= $fyStartMonth)
                ? (12 - $currentMonth + $fyStartMonth)
                : ($fyStartMonth - $currentMonth);
            $credited = round($lt['accrualRate'] * $remainingMonths, 2);
        } elseif ($lt['accrualType'] == 'on_joining') {
            $credited = floatval($lt['annualEntitlement']);
        }

        // Get carry forward from previous year (if applicable)
        $openingBalance = 0;
        if ($lt['isCarryForward']) {
            $prevFY = getPreviousFinancialYear();
            $DB->vals = array($userID, $lt['leaveTypeID'], $prevFY);
            $DB->types = "iis";
            $DB->sql = "SELECT closingBalance FROM " . $DB->pre . "employee_leave_balance
                        WHERE userID = ? AND leaveTypeID = ? AND financialYear = ?";
            $prevBalance = $DB->dbRow();

            if ($prevBalance) {
                $carryForward = floatval($prevBalance['closingBalance']);
                $openingBalance = min($carryForward, floatval($lt['maxCarryForward']));
            }
        }

        // Insert new balance record
        $closingBalance = $openingBalance + $credited;

        $DB->table = $DB->pre . "employee_leave_balance";
        $DB->data = array(
            'userID' => $userID,
            'leaveTypeID' => $lt['leaveTypeID'],
            'financialYear' => $fy,
            'openingBalance' => $openingBalance,
            'credited' => $credited,
            'used' => 0,
            'lapsed' => 0,
            'encashed' => 0,
            'adjusted' => 0,
            'closingBalance' => $closingBalance,
            'lastAccrualDate' => date('Y-m-d')
        );
        $DB->dbInsert();
    }

    return true;
}

/**
 * Deduct leave from employee balance
 * Called when leave is approved
 */
function deductLeaveBalance($userID, $leaveTypeID, $days)
{
    global $DB;

    $fy = getCurrentFinancialYear();

    // Get current balance
    $DB->vals = array($userID, $leaveTypeID, $fy);
    $DB->types = "iis";
    $DB->sql = "SELECT balanceID, closingBalance, used FROM " . $DB->pre . "employee_leave_balance
                WHERE userID = ? AND leaveTypeID = ? AND financialYear = ?";
    $balance = $DB->dbRow();

    if (!$balance) {
        // Initialize balance first
        initializeEmployeeLeaveBalance($userID, $fy);
        $balance = getEmployeeLeaveBalance($userID, $leaveTypeID);
    }

    if (!$balance) return false;

    $newUsed = floatval($balance['used']) + $days;
    $newClosing = floatval($balance['closingBalance']) - $days;

    $DB->table = $DB->pre . "employee_leave_balance";
    $DB->data = array(
        'used' => $newUsed,
        'closingBalance' => max(0, $newClosing)
    );
    $DB->dbUpdate("balanceID = ?", "i", array($balance['balanceID']));

    return true;
}

/**
 * Credit leave back to employee balance
 * Called when leave is cancelled
 */
function creditLeaveBalance($userID, $leaveTypeID, $days)
{
    global $DB;

    $fy = getCurrentFinancialYear();

    $DB->vals = array($userID, $leaveTypeID, $fy);
    $DB->types = "iis";
    $DB->sql = "SELECT balanceID, closingBalance, used FROM " . $DB->pre . "employee_leave_balance
                WHERE userID = ? AND leaveTypeID = ? AND financialYear = ?";
    $balance = $DB->dbRow();

    if (!$balance) return false;

    $newUsed = max(0, floatval($balance['used']) - $days);
    $newClosing = floatval($balance['closingBalance']) + $days;

    // Check max accumulation
    $DB->vals = array($leaveTypeID);
    $DB->types = "i";
    $DB->sql = "SELECT maxAccumulation FROM " . $DB->pre . "leave_type WHERE leaveTypeID = ?";
    $lt = $DB->dbRow();

    if ($lt && $lt['maxAccumulation'] > 0) {
        $newClosing = min($newClosing, floatval($lt['maxAccumulation']));
    }

    $DB->table = $DB->pre . "employee_leave_balance";
    $DB->data = array(
        'used' => $newUsed,
        'closingBalance' => $newClosing
    );
    $DB->dbUpdate("balanceID = ?", "i", array($balance['balanceID']));

    return true;
}

/**
 * Process monthly EL accrual for all employees
 * Should be run on 1st of every month
 */
function processMonthlyELAccrual()
{
    global $DB;

    $fy = getCurrentFinancialYear();
    $today = date('Y-m-d');
    $currentMonth = date('Y-m');

    // Get EL leave type
    $DB->vals = array('Earned Leave', 1);
    $DB->types = "si";
    $DB->sql = "SELECT leaveTypeID, accrualRate, maxAccumulation FROM " . $DB->pre . "leave_type
                WHERE leaveTypeName = ? AND status = ?";
    $elType = $DB->dbRow();

    if (!$elType) return array('err' => 1, 'msg' => 'Earned Leave type not found');

    $leaveTypeID = $elType['leaveTypeID'];
    $accrualRate = floatval($elType['accrualRate']);
    $maxAccumulation = floatval($elType['maxAccumulation']);

    // Get all active employees
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT userID FROM " . $DB->pre . "x_admin_user WHERE status = ?";
    $employees = $DB->dbRows();

    $processed = 0;
    $skipped = 0;

    foreach ($employees as $emp) {
        $userID = $emp['userID'];

        // Get current balance
        $DB->vals = array($userID, $leaveTypeID, $fy);
        $DB->types = "iis";
        $DB->sql = "SELECT balanceID, closingBalance, credited, lastAccrualDate
                    FROM " . $DB->pre . "employee_leave_balance
                    WHERE userID = ? AND leaveTypeID = ? AND financialYear = ?";
        $balance = $DB->dbRow();

        if (!$balance) {
            // Initialize balance
            initializeEmployeeLeaveBalance($userID, $fy);
            $skipped++;
            continue;
        }

        // Check if already accrued this month
        $lastAccrual = $balance['lastAccrualDate'];
        if ($lastAccrual && substr($lastAccrual, 0, 7) == $currentMonth) {
            $skipped++;
            continue;
        }

        // Check max accumulation
        $currentBalance = floatval($balance['closingBalance']);
        if ($currentBalance >= $maxAccumulation) {
            $skipped++;
            continue;
        }

        // Credit EL
        $newCredit = min($accrualRate, $maxAccumulation - $currentBalance);
        $newCredited = floatval($balance['credited']) + $newCredit;
        $newClosing = $currentBalance + $newCredit;

        $DB->table = $DB->pre . "employee_leave_balance";
        $DB->data = array(
            'credited' => $newCredited,
            'closingBalance' => $newClosing,
            'lastAccrualDate' => $today
        );
        $DB->dbUpdate("balanceID = ?", "i", array($balance['balanceID']));

        $processed++;
    }

    return array(
        'err' => 0,
        'msg' => "EL accrual completed. Processed: $processed, Skipped: $skipped"
    );
}

/**
 * Get employee's comp-off balance
 */
function getCompOffBalance($userID)
{
    global $DB;

    $DB->vals = array($userID, 'approved');
    $DB->types = "is";
    $DB->sql = "SELECT SUM(compOffDays) as balance FROM " . $DB->pre . "comp_off
                WHERE userID = ? AND compOffStatus = ? AND status = 1";
    $result = $DB->dbRow();

    return floatval($result['balance'] ?? 0);
}

/**
 * Get employee's pending comp-off requests
 */
function getCompOffHistory($userID, $status = null)
{
    global $DB;

    $sql = "SELECT co.*, u.displayName as approverName
            FROM " . $DB->pre . "comp_off co
            LEFT JOIN " . $DB->pre . "x_admin_user u ON co.approvedBy = u.userID
            WHERE co.userID = ? AND co.status = 1";

    $vals = array($userID);
    $types = "i";

    if ($status) {
        $sql .= " AND co.compOffStatus = ?";
        $vals[] = $status;
        $types .= "s";
    }

    $sql .= " ORDER BY co.workDate DESC";

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = $sql;

    return $DB->dbRows();
}

/**
 * Apply for comp-off
 */
function applyCompOff($userID, $workDate, $workReason, $hoursWorked = 8)
{
    global $DB;

    // Validate work date is a holiday or weekend
    $dayOfWeek = date('w', strtotime($workDate));

    $DB->vals = array($workDate, 1);
    $DB->types = "si";
    $DB->sql = "SELECT ahDate FROM " . $DB->pre . "attendance_holidays WHERE ahDate = ? AND status = ?";
    $isHoliday = $DB->dbRow();

    if ($dayOfWeek != 0 && !$isHoliday) {
        return array('err' => 1, 'msg' => 'Comp-off can only be applied for work on weekends or holidays');
    }

    // Check if already applied for this date
    $DB->vals = array($userID, $workDate);
    $DB->types = "is";
    $DB->sql = "SELECT compOffID FROM " . $DB->pre . "comp_off WHERE userID = ? AND workDate = ? AND status = 1";
    $existing = $DB->dbRow();

    if ($existing) {
        return array('err' => 1, 'msg' => 'Comp-off already applied for this date');
    }

    // Calculate comp-off days
    $minHours = floatval(getHRMSSetting('compoff_min_hours', 4));
    $compOffDays = ($hoursWorked >= 8) ? 1 : (($hoursWorked >= $minHours) ? 0.5 : 0);

    if ($compOffDays == 0) {
        return array('err' => 1, 'msg' => "Minimum $minHours hours required to earn comp-off");
    }

    // Calculate expiry date
    $validityDays = intval(getHRMSSetting('compoff_validity_days', 90));
    $expiryDate = date('Y-m-d', strtotime($workDate . " + $validityDays days"));

    // Insert comp-off request
    $DB->table = $DB->pre . "comp_off";
    $DB->data = array(
        'userID' => $userID,
        'workDate' => $workDate,
        'workReason' => $workReason,
        'hoursWorked' => $hoursWorked,
        'compOffDays' => $compOffDays,
        'expiryDate' => $expiryDate,
        'compOffStatus' => 'pending'
    );
    $DB->dbInsert();

    return array('err' => 0, 'msg' => 'Comp-off request submitted successfully');
}

/**
 * Approve/Reject comp-off request
 */
function processCompOffRequest($compOffID, $action, $approverID, $remarks = '')
{
    global $DB;

    $status = ($action == 'approve') ? 'approved' : 'rejected';

    $DB->table = $DB->pre . "comp_off";
    $DB->data = array(
        'compOffStatus' => $status,
        'approvedBy' => $approverID,
        'approvedDate' => date('Y-m-d H:i:s'),
        'remarks' => $remarks
    );
    $DB->dbUpdate("compOffID = ?", "i", array($compOffID));

    return array('err' => 0, 'msg' => "Comp-off request $status");
}

/**
 * Use comp-off for leave
 */
function useCompOff($userID, $compOffID, $leaveID)
{
    global $DB;

    $DB->table = $DB->pre . "comp_off";
    $DB->data = array(
        'compOffStatus' => 'used',
        'usedOn' => date('Y-m-d'),
        'usedLeaveID' => $leaveID
    );
    $DB->dbUpdate("compOffID = ? AND userID = ?", "ii", array($compOffID, $userID));

    return true;
}

/**
 * Process leave encashment request
 */
function processLeaveEncashment($userID, $leaveTypeID, $daysToEncash)
{
    global $DB;

    $fy = getCurrentFinancialYear();

    // Get leave type details
    $DB->vals = array($leaveTypeID);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM " . $DB->pre . "leave_type WHERE leaveTypeID = ?";
    $leaveType = $DB->dbRow();

    if (!$leaveType || !$leaveType['isEncashable']) {
        return array('err' => 1, 'msg' => 'This leave type cannot be encashed');
    }

    // Get current balance
    $balance = getEmployeeLeaveBalance($userID, $leaveTypeID);

    if (!$balance || floatval($balance['closingBalance']) < $daysToEncash) {
        return array('err' => 1, 'msg' => 'Insufficient leave balance');
    }

    // Get employee's basic salary
    $DB->vals = array($userID, 1, date('Y-m-d'), date('Y-m-d'));
    $DB->types = "iiss";
    $DB->sql = "SELECT basicSalary FROM " . $DB->pre . "salary_structure
                WHERE userID = ? AND status = ? AND effectiveFrom <= ?
                AND (effectiveTo IS NULL OR effectiveTo >= ?)";
    $salary = $DB->dbRow();

    if (!$salary) {
        return array('err' => 1, 'msg' => 'Salary structure not found for employee');
    }

    $basicSalary = floatval($salary['basicSalary']);
    $basicPerDay = $basicSalary / 30; // Assuming 30 days/month
    $encashmentRate = floatval($leaveType['encashmentRate']);
    $grossAmount = $basicPerDay * $daysToEncash * ($encashmentRate / 100);

    // Insert encashment record
    $DB->table = $DB->pre . "leave_encashment";
    $DB->data = array(
        'userID' => $userID,
        'leaveTypeID' => $leaveTypeID,
        'financialYear' => $fy,
        'encashmentDate' => date('Y-m-d'),
        'daysEncashed' => $daysToEncash,
        'basicSalaryPerDay' => $basicPerDay,
        'encashmentRate' => $encashmentRate,
        'grossAmount' => $grossAmount,
        'taxDeducted' => 0,
        'netAmount' => $grossAmount,
        'encashmentStatus' => 'pending'
    );
    $encashmentID = $DB->dbInsert();

    return array(
        'err' => 0,
        'msg' => 'Encashment request submitted',
        'encashmentID' => $encashmentID,
        'grossAmount' => $grossAmount
    );
}

/**
 * Approve leave encashment and deduct from balance
 */
function approveLeaveEncashment($encashmentID, $approverID)
{
    global $DB;

    // Get encashment details
    $DB->vals = array($encashmentID);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM " . $DB->pre . "leave_encashment WHERE encashmentID = ?";
    $encashment = $DB->dbRow();

    if (!$encashment) {
        return array('err' => 1, 'msg' => 'Encashment record not found');
    }

    if ($encashment['encashmentStatus'] != 'pending') {
        return array('err' => 1, 'msg' => 'Encashment already processed');
    }

    $fy = getCurrentFinancialYear();

    // Deduct from balance
    $DB->vals = array($encashment['userID'], $encashment['leaveTypeID'], $fy);
    $DB->types = "iis";
    $DB->sql = "SELECT balanceID, closingBalance, encashed FROM " . $DB->pre . "employee_leave_balance
                WHERE userID = ? AND leaveTypeID = ? AND financialYear = ?";
    $balance = $DB->dbRow();

    if ($balance) {
        $newEncashed = floatval($balance['encashed']) + floatval($encashment['daysEncashed']);
        $newClosing = floatval($balance['closingBalance']) - floatval($encashment['daysEncashed']);

        $DB->table = $DB->pre . "employee_leave_balance";
        $DB->data = array(
            'encashed' => $newEncashed,
            'closingBalance' => max(0, $newClosing)
        );
        $DB->dbUpdate("balanceID = ?", "i", array($balance['balanceID']));
    }

    // Update encashment status
    $DB->table = $DB->pre . "leave_encashment";
    $DB->data = array(
        'encashmentStatus' => 'approved',
        'approvedBy' => $approverID,
        'approvedDate' => date('Y-m-d H:i:s')
    );
    $DB->dbUpdate("encashmentID = ?", "i", array($encashmentID));

    return array('err' => 0, 'msg' => 'Encashment approved');
}

/**
 * Process yearly leave reset for all employees
 * Should be run on April 1st (or configured FY start date)
 */
function processYearlyLeaveReset($targetFY = null)
{
    global $DB;

    $newFY = $targetFY ?: getCurrentFinancialYear();
    $oldFY = getPreviousFinancialYear();

    // Get all active employees
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT userID FROM " . $DB->pre . "x_admin_user WHERE status = ?";
    $employees = $DB->dbRows();

    // Get all leave types
    $leaveTypes = getLeaveTypesConfig(true);

    $processed = 0;
    $logEntries = array();

    foreach ($employees as $emp) {
        $userID = $emp['userID'];

        foreach ($leaveTypes as $lt) {
            // Skip non-leave types
            if ($lt['leaveSubType'] == 1) continue;

            $leaveTypeID = $lt['leaveTypeID'];

            // Get previous year balance
            $DB->vals = array($userID, $leaveTypeID, $oldFY);
            $DB->types = "iis";
            $DB->sql = "SELECT closingBalance FROM " . $DB->pre . "employee_leave_balance
                        WHERE userID = ? AND leaveTypeID = ? AND financialYear = ?";
            $prevBalance = $DB->dbRow();

            $previousBalance = floatval($prevBalance['closingBalance'] ?? 0);
            $carryForward = 0;
            $lapsed = 0;

            // Calculate carry forward
            if ($lt['isCarryForward'] && $previousBalance > 0) {
                $maxCF = floatval($lt['maxCarryForward']);
                $carryForward = min($previousBalance, $maxCF);
                $lapsed = max(0, $previousBalance - $carryForward);
            } else {
                $lapsed = $previousBalance;
            }

            // Calculate new year credit
            $newYearCredit = 0;
            if ($lt['accrualType'] == 'yearly') {
                $newYearCredit = floatval($lt['annualEntitlement']);
            }

            $newOpeningBalance = $carryForward + $newYearCredit;

            // Check if new FY balance exists
            $DB->vals = array($userID, $leaveTypeID, $newFY);
            $DB->types = "iis";
            $DB->sql = "SELECT balanceID FROM " . $DB->pre . "employee_leave_balance
                        WHERE userID = ? AND leaveTypeID = ? AND financialYear = ?";
            $existing = $DB->dbRow();

            if (!$existing) {
                // Create new balance record
                $DB->table = $DB->pre . "employee_leave_balance";
                $DB->data = array(
                    'userID' => $userID,
                    'leaveTypeID' => $leaveTypeID,
                    'financialYear' => $newFY,
                    'openingBalance' => $carryForward,
                    'credited' => $newYearCredit,
                    'used' => 0,
                    'lapsed' => 0,
                    'encashed' => 0,
                    'adjusted' => 0,
                    'closingBalance' => $newOpeningBalance,
                    'lastAccrualDate' => date('Y-m-d')
                );
                $DB->dbInsert();
            }

            // Log the reset
            $DB->table = $DB->pre . "leave_yearly_reset_log";
            $DB->data = array(
                'userID' => $userID,
                'leaveTypeID' => $leaveTypeID,
                'fromFinancialYear' => $oldFY,
                'toFinancialYear' => $newFY,
                'previousBalance' => $previousBalance,
                'carryForwardAmount' => $carryForward,
                'lapsedAmount' => $lapsed,
                'encashedAmount' => 0,
                'newYearCredit' => $newYearCredit,
                'newOpeningBalance' => $newOpeningBalance
            );
            $DB->dbInsert();

            $processed++;
        }
    }

    return array(
        'err' => 0,
        'msg' => "Yearly reset completed. Processed: $processed records for " . count($employees) . " employees",
        'fromFY' => $oldFY,
        'toFY' => $newFY
    );
}

/**
 * Get leave balance summary for employee portal
 */
function getLeaveBalanceSummary($userID)
{
    global $DB;

    $fy = getCurrentFinancialYear();

    // Parse FY to get date range (e.g., "2025-26" -> Apr 2025 to Mar 2026)
    $fyParts = explode('-', $fy);
    $fyStartYear = intval($fyParts[0]);
    $fyStart = $fyStartYear . '-04-01';
    $fyEnd = ($fyStartYear + 1) . '-03-31';

    // Get all leave balances from balance table
    $balances = getEmployeeLeaveBalance($userID, null);

    // If no balances, initialize
    if (empty($balances)) {
        initializeEmployeeLeaveBalance($userID, $fy);
        $balances = getEmployeeLeaveBalance($userID, null);
    }

    // Get ACTUAL leaves taken from mx_leave table (approved only)
    $DB->vals = array($userID, $fyStart, $fyEnd);
    $DB->types = "iss";
    $DB->sql = "SELECT l.leaveType,
                       SUM(CASE WHEN ld.lType IN (2,3) THEN 0.5 ELSE 1 END) as daysTaken
                FROM " . $DB->pre . "leave l
                INNER JOIN " . $DB->pre . "leave_details ld ON l.leaveID = ld.leaveID
                WHERE l.userID = ? AND l.status = 1 AND l.leaveStatus IN ('Approved', 'Paid')
                AND ld.leaveDate >= ? AND ld.leaveDate <= ?
                AND ld.lType != -1
                GROUP BY l.leaveType";
    $actualUsed = $DB->dbRows();

    $usedByType = array();
    foreach ($actualUsed as $au) {
        $usedByType[$au['leaveType']] = floatval($au['daysTaken']);
    }

    // Get comp-off balance
    $compOffBalance = getCompOffBalance($userID);

    // Add pending leave requests
    $DB->vals = array($userID, 'Pending');
    $DB->types = "is";
    $DB->sql = "SELECT leaveType, SUM(DATEDIFF(toDate, fromDate) + 1) as pendingDays
                FROM " . $DB->pre . "leave
                WHERE userID = ? AND leaveStatus = ? AND status = 1
                GROUP BY leaveType";
    $pendingLeaves = $DB->dbRows();

    $pendingByType = array();
    foreach ($pendingLeaves as $pl) {
        $pendingByType[$pl['leaveType']] = floatval($pl['pendingDays']);
    }

    // Format response
    $summary = array(
        'financialYear' => $fy,
        'balances' => array(),
        'compOff' => array(
            'available' => $compOffBalance,
            'colorCode' => '#f39c12'
        )
    );

    foreach ($balances as $b) {
        $pending = $pendingByType[$b['leaveTypeID']] ?? 0;
        $actualUsedDays = $usedByType[$b['leaveTypeID']] ?? 0;
        $credited = floatval($b['credited']);

        // Calculate available balance (can be negative)
        $available = $credited - $actualUsedDays;

        $summary['balances'][] = array(
            'leaveTypeID' => $b['leaveTypeID'],
            'leaveTypeName' => $b['leaveTypeName'],
            'colorCode' => $b['colorCode'],
            'opening' => floatval($b['openingBalance']),
            'credited' => $credited,
            'used' => $actualUsedDays,
            'encashed' => floatval($b['encashed']),
            'adjusted' => floatval($b['adjusted']),
            'available' => $available,
            'pending' => $pending,
            'isCarryForward' => $b['isCarryForward'],
            'isEncashable' => $b['isEncashable'],
            'maxAccumulation' => floatval($b['maxAccumulation'])
        );
    }

    return $summary;
}

/**
 * Expire old comp-offs
 * Should be run daily
 */
function expireOldCompOffs()
{
    global $DB;

    $today = date('Y-m-d');

    $DB->vals = array('approved', $today);
    $DB->types = "ss";
    $DB->sql = "UPDATE " . $DB->pre . "comp_off
                SET compOffStatus = 'expired'
                WHERE compOffStatus = ? AND expiryDate < ? AND status = 1";
    $DB->dbQuery();

    return array('err' => 0, 'msg' => 'Expired comp-offs processed');
}
