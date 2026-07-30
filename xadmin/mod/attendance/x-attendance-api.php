<?php
/**
 * Attendance API Handler
 * Handles AJAX requests for dashboard, reports, and exports
 */

require_once("../../../core/core.inc.php");
require_once("../../inc/site.inc.php");

header('Content-Type: application/json');

$MXRES = mxCheckRequest();
if ($MXRES["err"] != 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * Helper function to get employees on approved leave for a specific date
 * Returns array of userIDs who are on approved leave
 */
function getEmployeesOnLeave($date) {
    global $DB;

    $DB->vals = array($date);
    $DB->types = "s";
    $DB->sql = "SELECT DISTINCT ld.userID
                FROM " . $DB->pre . "leave_details ld
                JOIN " . $DB->pre . "leave l ON ld.leaveID = l.leaveID
                WHERE ld.leaveDate = ?
                AND ld.lType = 1
                AND l.leaveStatus = 'Approved'
                AND l.status = 1
                AND ld.status = 1";
    $rows = $DB->dbRows();

    return array_column($rows, 'userID');
}

/**
 * Helper function to get leave details for employees on a specific date
 * Returns associative array: userID => leave info
 */
function getLeaveDetailsForDate($date) {
    global $DB;

    $DB->vals = array($date);
    $DB->types = "s";
    $DB->sql = "SELECT ld.userID, l.reason, lt.leaveTypeName
                FROM " . $DB->pre . "leave_details ld
                JOIN " . $DB->pre . "leave l ON ld.leaveID = l.leaveID
                LEFT JOIN " . $DB->pre . "leave_type lt ON l.leaveType = lt.leaveTypeID
                WHERE ld.leaveDate = ?
                AND ld.lType = 1
                AND l.leaveStatus = 'Approved'
                AND l.status = 1";
    $rows = $DB->dbRows();

    $result = [];
    foreach ($rows as $row) {
        $result[$row['userID']] = [
            'leaveType' => $row['leaveTypeName'] ?? 'Leave',
            'reason' => $row['reason'] ?? ''
        ];
    }
    return $result;
}

switch ($action) {
    case 'getDashboardData':
        echo json_encode(getDashboardData());
        break;
    case 'getReportData':
        echo json_encode(getReportData());
        break;
    case 'getMonthlyMuster':
        echo json_encode(getMonthlyMuster());
        break;
    case 'getPayrollSummary':
        echo json_encode(getPayrollSummary());
        break;
    case 'getLateEarlyReport':
        echo json_encode(getLateEarlyReport());
        break;
    case 'getOvertimeReport':
        echo json_encode(getOvertimeReport());
        break;
    case 'getAbsenteeismReport':
        echo json_encode(getAbsenteeismReport());
        break;
    case 'getMasterReport':
        echo json_encode(getMasterReport());
        break;
    case 'getDepartmentSummary':
        echo json_encode(getDepartmentSummary());
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Get Dashboard Data with KPIs, Alerts, and Attendance List
 */
function getDashboardData()
{
    global $DB;

    $date = $_POST['date'] ?? date('Y-m-d');
    $deptID = intval($_POST['deptID'] ?? 0);
    $shiftID = intval($_POST['shiftID'] ?? 0);
    $status = $_POST['status'] ?? '';
    $employeeID = intval($_POST['employeeID'] ?? 0);

    // Build WHERE conditions
    $where = "a.status=1 AND a.attendanceDate=?";
    $vals = array($date);
    $types = "s";

    if ($deptID > 0) {
        $where .= " AND u.deptID=?";
        $vals[] = $deptID;
        $types .= "i";
    }

    if ($employeeID > 0) {
        $where .= " AND a.userID=?";
        $vals[] = $employeeID;
        $types .= "i";
    }

    if ($shiftID > 0) {
        $where .= " AND a.shiftID=?";
        $vals[] = $shiftID;
        $types .= "i";
    }

    if ($status) {
        if ($status === 'late') {
            $where .= " AND a.isLate=1";
        } else {
            $where .= " AND a.attendanceStatus=?";
            $vals[] = $status;
            $types .= "s";
        }
    }

    // Get total active employees
    $totalWhere = "u.status=1";
    $totalVals = array();
    $totalTypes = "";

    if ($deptID > 0) {
        $totalWhere .= " AND u.deptID=?";
        $totalVals[] = $deptID;
        $totalTypes .= "i";
    }

    if (empty($totalVals)) {
        $DB->sql = "SELECT COUNT(*) as cnt FROM " . $DB->pre . "x_admin_user u WHERE " . $totalWhere;
        $DB->vals = null;
        $DB->types = null;
    } else {
        $DB->sql = "SELECT COUNT(*) as cnt FROM " . $DB->pre . "x_admin_user u WHERE " . $totalWhere;
        $DB->vals = $totalVals;
        $DB->types = $totalTypes;
    }
    $totalRow = $DB->dbRow();
    $totalEmployees = $totalRow['cnt'] ?? 0;

    // Get employees on approved leave for this date
    $employeesOnLeave = getEmployeesOnLeave($date);
    $leaveCount = count($employeesOnLeave);

    // Get KPIs
    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN a.attendanceStatus='present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN a.attendanceStatus='absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN a.attendanceStatus='leave' THEN 1 ELSE 0 END) as onLeave,
                    SUM(CASE WHEN a.attendanceStatus='half_day' THEN 1 ELSE 0 END) as halfDay,
                    SUM(CASE WHEN a.isLate=1 THEN 1 ELSE 0 END) as late
                FROM " . $DB->pre . "attendance a
                INNER JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                WHERE " . $where;
    $kpiRow = $DB->dbRow();

    // Calculate absent count (total - present - on leave from leave table)
    $presentCount = intval($kpiRow['present'] ?? 0);
    $recordedLeave = intval($kpiRow['onLeave'] ?? 0);
    // Add leave count from leave_details table (approved leaves)
    $totalLeave = $leaveCount;
    $absentCount = $totalEmployees - $presentCount - $totalLeave;
    if ($absentCount < 0) $absentCount = 0;

    $kpis = [
        'total' => $totalEmployees,
        'present' => $presentCount,
        'absent' => $absentCount,
        'late' => intval($kpiRow['late'] ?? 0),
        'leave' => $totalLeave
    ];

    // Get late arrivals for alerts
    $DB->vals = array($date, 1);
    $DB->types = "si";
    $DB->sql = "SELECT a.*, u.userName as displayName, u.employeeCode as empCode, u.department as deptName
                FROM " . $DB->pre . "attendance a
                INNER JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                WHERE a.attendanceDate=? AND a.isLate=? AND a.status=1
                ORDER BY a.lateMinutes DESC
                LIMIT 10";
    $lateRows = $DB->dbRows();

    $lateAlerts = [];
    foreach ($lateRows as $row) {
        $lateAlerts[] = [
            'name' => $row['displayName'],
            'empCode' => $row['empCode'],
            'checkIn' => date('h:i A', strtotime($row['checkIn'])),
            'lateBy' => $row['lateMinutes']
        ];
    }

    // Get absent employees (no attendance record for today AND not on leave)
    $leaveUserIds = !empty($employeesOnLeave) ? implode(',', array_map('intval', $employeesOnLeave)) : '0';

    $absentQuery = "SELECT u.userID, u.userName as displayName, u.employeeCode as empCode, u.department as deptName
                    FROM " . $DB->pre . "x_admin_user u
                    WHERE u.status=1 AND u.userID NOT IN (
                        SELECT userID FROM " . $DB->pre . "attendance
                        WHERE attendanceDate=? AND status=1
                    )
                    AND u.userID NOT IN ($leaveUserIds)";

    if ($deptID > 0) {
        $absentQuery .= " AND u.department='" . mysqli_real_escape_string($DB->con, $deptID) . "'";
    }

    $absentQuery .= " LIMIT 10";

    $DB->vals = array($date);
    $DB->types = "s";
    $DB->sql = $absentQuery;
    $absentRows = $DB->dbRows();

    $absentAlerts = [];
    foreach ($absentRows as $row) {
        $absentAlerts[] = [
            'name' => $row['displayName'],
            'empCode' => $row['empCode'],
            'department' => $row['deptName']
        ];
    }

    // Get employees on leave for alerts
    $leaveDetails = getLeaveDetailsForDate($date);
    $leaveAlerts = [];
    if (!empty($employeesOnLeave)) {
        $DB->sql = "SELECT userID, userName as displayName, employeeCode as empCode, department as deptName
                    FROM " . $DB->pre . "x_admin_user
                    WHERE userID IN ($leaveUserIds) AND status=1
                    LIMIT 10";
        $DB->vals = null;
        $DB->types = null;
        $leaveRows = $DB->dbRows();

        foreach ($leaveRows as $row) {
            $leaveInfo = $leaveDetails[$row['userID']] ?? [];
            $leaveAlerts[] = [
                'name' => $row['displayName'],
                'empCode' => $row['empCode'],
                'department' => $row['deptName'],
                'leaveType' => $leaveInfo['leaveType'] ?? 'Leave',
                'reason' => $leaveInfo['reason'] ?? ''
            ];
        }
    }

    // Get pending remarks
    $DB->vals = array('pending', 1);
    $DB->types = "si";
    $DB->sql = "SELECT ar.*, a.attendanceDate, u.userName as displayName
                FROM " . $DB->pre . "attendance_remarks ar
                INNER JOIN " . $DB->pre . "attendance a ON ar.attendanceID = a.attendanceID
                INNER JOIN " . $DB->pre . "x_admin_user u ON ar.userID = u.userID
                WHERE ar.reviewStatus=? AND ar.status=?
                ORDER BY ar.submittedAt DESC
                LIMIT 10";
    $remarkRows = $DB->dbRows();

    $remarkAlerts = [];
    foreach ($remarkRows as $row) {
        $remarkAlerts[] = [
            'name' => $row['displayName'],
            'type' => ucfirst(str_replace('_', ' ', $row['remarkType'])),
            'date' => date('d M', strtotime($row['attendanceDate']))
        ];
    }

    // Get today's attendance list
    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "SELECT a.*, u.userName as displayName, u.employeeCode as empCode, u.department as deptName,
                       COALESCE(s.shiftName, 'General') as shiftName
                FROM " . $DB->pre . "attendance a
                INNER JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                LEFT JOIN " . $DB->pre . "shift_master s ON a.shiftID = s.shiftID
                WHERE " . $where . "
                ORDER BY a.checkIn DESC";
    $attendanceRows = $DB->dbRows();

    $attendance = [];
    foreach ($attendanceRows as $row) {
        $attendance[] = [
            'userID' => $row['userID'],
            'name' => $row['displayName'],
            'empCode' => $row['empCode'],
            'department' => $row['deptName'],
            'shift' => $row['shiftName'],
            'checkIn' => $row['checkIn'] ? date('h:i A', strtotime($row['checkIn'])) : null,
            'checkOut' => $row['checkOut'] ? date('h:i A', strtotime($row['checkOut'])) : null,
            'workingHours' => $row['workingHours'] ? number_format($row['workingHours'], 1) : null,
            'status' => ucfirst(str_replace('_', ' ', $row['attendanceStatus'])),
            'isLate' => $row['isLate'],
            'lateMinutes' => $row['lateMinutes']
        ];
    }

    // Add employees on leave to the attendance list
    foreach ($leaveAlerts as $leaveEmp) {
        $attendance[] = [
            'userID' => null,
            'name' => $leaveEmp['name'],
            'empCode' => $leaveEmp['empCode'],
            'department' => $leaveEmp['department'],
            'shift' => '-',
            'checkIn' => null,
            'checkOut' => null,
            'workingHours' => null,
            'status' => 'On Leave',
            'isLate' => 0,
            'lateMinutes' => 0,
            'leaveType' => $leaveEmp['leaveType'] ?? 'Leave',
            'leaveReason' => $leaveEmp['reason'] ?? ''
        ];
    }

    // Get chart data - last 7 days trend
    $chartData = getAttendanceTrendData($deptID);

    return [
        'success' => true,
        'kpis' => $kpis,
        'alerts' => [
            'late' => $lateAlerts,
            'absent' => $absentAlerts,
            'leave' => $leaveAlerts,
            'remarks' => $remarkAlerts
        ],
        'attendance' => $attendance,
        'chartData' => $chartData
    ];
}

/**
 * Get Attendance Trend Data for Charts
 */
function getAttendanceTrendData($deptID = 0)
{
    global $DB;

    $labels = [];
    $presentData = [];
    $absentData = [];
    $lateData = [];

    // Last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('D', strtotime($date));

        $where = "a.attendanceDate=? AND a.status=1";
        $vals = array($date);
        $types = "s";

        if ($deptID > 0) {
            $where .= " AND u.deptID=?";
            $vals[] = $deptID;
            $types .= "i";
        }

        $DB->vals = $vals;
        $DB->types = $types;
        $DB->sql = "SELECT
                        SUM(CASE WHEN a.attendanceStatus='present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN a.attendanceStatus='absent' THEN 1 ELSE 0 END) as absent,
                        SUM(CASE WHEN a.isLate=1 THEN 1 ELSE 0 END) as late
                    FROM " . $DB->pre . "attendance a
                    INNER JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                    WHERE " . $where;
        $row = $DB->dbRow();

        $presentData[] = intval($row['present'] ?? 0);
        $absentData[] = intval($row['absent'] ?? 0);
        $lateData[] = intval($row['late'] ?? 0);
    }

    // Today's breakdown
    $today = date('Y-m-d');
    $breakdownWhere = "a.attendanceDate=? AND a.status=1";
    $breakdownVals = array($today);
    $breakdownTypes = "s";

    if ($deptID > 0) {
        $breakdownWhere .= " AND u.deptID=?";
        $breakdownVals[] = $deptID;
        $breakdownTypes .= "i";
    }

    $DB->vals = $breakdownVals;
    $DB->types = $breakdownTypes;
    $DB->sql = "SELECT
                    SUM(CASE WHEN a.attendanceStatus='present' AND a.isLate=0 THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN a.attendanceStatus='absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN a.isLate=1 THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN a.attendanceStatus='leave' THEN 1 ELSE 0 END) as onLeave
                FROM " . $DB->pre . "attendance a
                INNER JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                WHERE " . $breakdownWhere;
    $breakdown = $DB->dbRow();

    return [
        'trend' => [
            'labels' => $labels,
            'present' => $presentData,
            'absent' => $absentData,
            'late' => $lateData
        ],
        'breakdown' => [
            'present' => intval($breakdown['present'] ?? 0),
            'absent' => intval($breakdown['absent'] ?? 0),
            'late' => intval($breakdown['late'] ?? 0),
            'leave' => intval($breakdown['onLeave'] ?? 0)
        ]
    ];
}

/**
 * Get Monthly Muster Roll Report Data
 */
function getMonthlyMuster()
{
    global $DB;

    $month = intval($_POST['month'] ?? date('n'));
    $year = intval($_POST['year'] ?? date('Y'));
    $deptID = intval($_POST['deptID'] ?? 0);
    $employeeID = intval($_POST['employeeID'] ?? 0);

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));
    $daysInMonth = date('t', strtotime($startDate));

    // Get all employees
    $empWhere = "u.status=1";
    $empVals = array();
    $empTypes = "";

    if ($deptID > 0) {
        $empWhere .= " AND u.deptID=?";
        $empVals[] = $deptID;
        $empTypes .= "i";
    }

    if ($employeeID > 0) {
        $empWhere .= " AND u.userID=?";
        $empVals[] = $employeeID;
        $empTypes .= "i";
    }

    if (empty($empVals)) {
        $DB->vals = null;
        $DB->types = null;
    } else {
        $DB->vals = $empVals;
        $DB->types = $empTypes;
    }

    $DB->sql = "SELECT u.userID, u.employeeCode as empCode, u.userName as displayName, u.department as deptName
                FROM " . $DB->pre . "x_admin_user u
                WHERE " . $empWhere . "
                ORDER BY u.department, u.userName";
    $employees = $DB->dbRows();

    // Get holidays for the month
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT holidayDate FROM " . $DB->pre . "holiday_master
                WHERE holidayDate BETWEEN ? AND ? AND status=?";
    $holidayRows = $DB->dbRows();
    $holidays = array_column($holidayRows, 'holidayDate');

    $musterData = [];

    foreach ($employees as $emp) {
        // Get attendance for this employee for the month
        $DB->vals = array($emp['userID'], $startDate, $endDate, 1);
        $DB->types = "issi";
        $DB->sql = "SELECT attendanceDate, attendanceStatus, isLate, checkIn, checkOut, workingHours
                    FROM " . $DB->pre . "attendance
                    WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=?";
        $attRows = $DB->dbRows();

        // Create lookup
        $attLookup = [];
        foreach ($attRows as $att) {
            $attLookup[$att['attendanceDate']] = $att;
        }

        // Get approved leave dates for this employee
        $DB->vals = array($emp['userID'], $startDate, $endDate, 1, 'Approved', 1);
        $DB->types = "issisi";
        $DB->sql = "SELECT ld.leaveDate
                    FROM " . $DB->pre . "leave_details ld
                    JOIN " . $DB->pre . "leave l ON ld.leaveID = l.leaveID
                    WHERE ld.userID = ?
                    AND ld.leaveDate BETWEEN ? AND ?
                    AND ld.lType = ?
                    AND l.leaveStatus = ?
                    AND l.status = ?";
        $leaveRows = $DB->dbRows();
        $leaveDates = array_column($leaveRows, 'leaveDate');

        $days = [];
        $summary = [
            'present' => 0,
            'absent' => 0,
            'leave' => 0,
            'late' => 0,
            'halfDay' => 0,
            'holiday' => 0,
            'weeklyOff' => 0,
            'totalHours' => 0
        ];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dayOfWeek = date('w', strtotime($dateStr));
            $isFuture = strtotime($dateStr) > time();

            if ($isFuture) {
                $days[$d] = '-';
            } elseif ($dayOfWeek == 0) { // Sunday
                $days[$d] = 'WO';
                $summary['weeklyOff']++;
            } elseif (in_array($dateStr, $holidays)) {
                $days[$d] = 'H';
                $summary['holiday']++;
            } elseif (isset($attLookup[$dateStr])) {
                $att = $attLookup[$dateStr];
                switch ($att['attendanceStatus']) {
                    case 'present':
                        $days[$d] = $att['isLate'] ? 'LT' : 'P';
                        $summary['present']++;
                        if ($att['isLate']) $summary['late']++;
                        break;
                    case 'absent':
                        $days[$d] = 'A';
                        $summary['absent']++;
                        break;
                    case 'leave':
                        $days[$d] = 'L';
                        $summary['leave']++;
                        break;
                    case 'half_day':
                        $days[$d] = 'HD';
                        $summary['halfDay']++;
                        break;
                    default:
                        $days[$d] = 'P';
                        $summary['present']++;
                }
                $summary['totalHours'] += floatval($att['workingHours'] ?? 0);
            } elseif (in_array($dateStr, $leaveDates)) {
                // Employee is on approved leave
                $days[$d] = 'L';
                $summary['leave']++;
            } else {
                $days[$d] = 'A';
                $summary['absent']++;
            }
        }

        // Calculate payable days
        $summary['payableDays'] = $summary['present'] + $summary['leave'] + ($summary['halfDay'] * 0.5);

        $musterData[] = [
            'userID' => $emp['userID'],
            'empCode' => $emp['empCode'],
            'name' => $emp['displayName'],
            'department' => $emp['deptName'],
            'days' => $days,
            'summary' => $summary
        ];
    }

    return [
        'success' => true,
        'month' => $month,
        'year' => $year,
        'daysInMonth' => $daysInMonth,
        'data' => $musterData
    ];
}

/**
 * Get Payroll Summary Report
 */
function getPayrollSummary()
{
    global $DB;

    $month = intval($_POST['month'] ?? date('n'));
    $year = intval($_POST['year'] ?? date('Y'));
    $deptID = intval($_POST['deptID'] ?? 0);
    $employeeID = intval($_POST['employeeID'] ?? 0);

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));
    $daysInMonth = date('t', strtotime($startDate));

    // Calculate working days (excluding Sundays and holidays)
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT COUNT(*) as cnt FROM " . $DB->pre . "holiday_master
                WHERE holidayDate BETWEEN ? AND ? AND status=?";
    $holidayCount = $DB->dbRow()['cnt'] ?? 0;

    // Count Sundays
    $sundays = 0;
    $currentDate = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    while ($currentDate <= $endTimestamp) {
        if (date('w', $currentDate) == 0) $sundays++;
        $currentDate = strtotime('+1 day', $currentDate);
    }

    $workingDays = $daysInMonth - $sundays - $holidayCount;

    // Get employees with attendance summary
    $empWhere = "u.status=1";
    $empVals = array();
    $empTypes = "";

    if ($deptID > 0) {
        $empWhere .= " AND u.deptID=?";
        $empVals[] = $deptID;
        $empTypes .= "i";
    }

    if ($employeeID > 0) {
        $empWhere .= " AND u.userID=?";
        $empVals[] = $employeeID;
        $empTypes .= "i";
    }

    if (empty($empVals)) {
        $DB->vals = null;
        $DB->types = null;
    } else {
        $DB->vals = $empVals;
        $DB->types = $empTypes;
    }

    $DB->sql = "SELECT u.userID, u.employeeCode as empCode, u.userName as displayName, u.department as deptName
                FROM " . $DB->pre . "x_admin_user u
                WHERE " . $empWhere . "
                ORDER BY u.department, u.userName";
    $employees = $DB->dbRows();

    $payrollData = [];

    foreach ($employees as $emp) {
        $DB->vals = array($emp['userID'], $startDate, $endDate, 1);
        $DB->types = "issi";
        $DB->sql = "SELECT
                        COUNT(*) as totalRecords,
                        SUM(CASE WHEN attendanceStatus='present' THEN 1 ELSE 0 END) as presentDays,
                        SUM(CASE WHEN attendanceStatus='absent' THEN 1 ELSE 0 END) as absentDays,
                        SUM(CASE WHEN attendanceStatus='leave' THEN 1 ELSE 0 END) as leaveDays,
                        SUM(CASE WHEN attendanceStatus='half_day' THEN 1 ELSE 0 END) as halfDays,
                        SUM(CASE WHEN isLate=1 THEN 1 ELSE 0 END) as lateDays,
                        SUM(COALESCE(lateMinutes, 0)) as totalLateMinutes,
                        SUM(COALESCE(earlyMinutes, 0)) as totalEarlyMinutes,
                        SUM(COALESCE(workingHours, 0)) as totalHours,
                        SUM(COALESCE(overtimeHours, 0)) as totalOTHours,
                        SUM(CASE WHEN overtimeApproved=1 THEN COALESCE(overtimeHours, 0) ELSE 0 END) as approvedOTHours
                    FROM " . $DB->pre . "attendance
                    WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=?";
        $summary = $DB->dbRow();

        // Get approved leave days from leave_details table
        $DB->vals = array($emp['userID'], $startDate, $endDate, 1, 'Approved', 1);
        $DB->types = "issisi";
        $DB->sql = "SELECT COUNT(*) as approvedLeaveDays
                    FROM " . $DB->pre . "leave_details ld
                    JOIN " . $DB->pre . "leave l ON ld.leaveID = l.leaveID
                    WHERE ld.userID = ?
                    AND ld.leaveDate BETWEEN ? AND ?
                    AND ld.lType = ?
                    AND l.leaveStatus = ?
                    AND l.status = ?";
        $leaveRow = $DB->dbRow();
        $approvedLeaveDays = intval($leaveRow['approvedLeaveDays'] ?? 0);

        $presentDays = intval($summary['presentDays'] ?? 0);
        // Use approved leave from leave_details instead of attendance table
        $leaveDays = $approvedLeaveDays;
        $absentDays = $workingDays - $presentDays - $leaveDays - (intval($summary['halfDays'] ?? 0) * 0.5);
        if ($absentDays < 0) $absentDays = 0;

        $payrollData[] = [
            'empCode' => $emp['empCode'],
            'name' => $emp['displayName'],
            'department' => $emp['deptName'],
            'workingDays' => $workingDays,
            'presentDays' => $presentDays,
            'absentDays' => $absentDays,
            'leaveDays' => $leaveDays,
            'paidLeave' => $leaveDays,
            'unpaidLeave' => $absentDays,
            'halfDays' => intval($summary['halfDays'] ?? 0),
            'lateDays' => intval($summary['lateDays'] ?? 0),
            'totalLateMinutes' => intval($summary['totalLateMinutes'] ?? 0),
            'totalHours' => round(floatval($summary['totalHours'] ?? 0), 1),
            'overtimeHours' => round(floatval($summary['approvedOTHours'] ?? 0), 1),
            'payableDays' => $presentDays + $leaveDays + (intval($summary['halfDays'] ?? 0) * 0.5),
            'deductionDays' => $absentDays
        ];
    }

    return [
        'success' => true,
        'month' => $month,
        'year' => $year,
        'workingDays' => $workingDays,
        'calendarDays' => $daysInMonth,
        'holidays' => $holidayCount,
        'weeklyOffs' => $sundays,
        'data' => $payrollData
    ];
}

/**
 * Get Late/Early Report
 */
function getLateEarlyReport()
{
    global $DB;

    $fromDate = $_POST['fromDate'] ?? date('Y-m-01');
    $toDate = $_POST['toDate'] ?? date('Y-m-d');
    $deptID = intval($_POST['deptID'] ?? 0);
    $employeeID = intval($_POST['employeeID'] ?? 0);

    $where = "a.status=1 AND a.attendanceDate BETWEEN ? AND ? AND (a.isLate=1 OR a.isEarlyCheckout=1)";
    $vals = array($fromDate, $toDate);
    $types = "ss";

    if ($deptID > 0) {
        $where .= " AND u.deptID=?";
        $vals[] = $deptID;
        $types .= "i";
    }

    if ($employeeID > 0) {
        $where .= " AND a.userID=?";
        $vals[] = $employeeID;
        $types .= "i";
    }

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "SELECT a.*, u.userName as displayName, u.employeeCode as empCode, u.department as deptName,
                       s.startTime as scheduledIn, s.endTime as scheduledOut,
                       ar.reason, ar.reviewStatus
                FROM " . $DB->pre . "attendance a
                INNER JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                LEFT JOIN " . $DB->pre . "shift_master s ON a.shiftID = s.shiftID
                LEFT JOIN " . $DB->pre . "attendance_remarks ar ON a.attendanceID = ar.attendanceID
                WHERE " . $where . "
                ORDER BY a.attendanceDate DESC, u.userName";
    $rows = $DB->dbRows();

    $data = [];
    foreach ($rows as $row) {
        $data[] = [
            'date' => date('d M Y', strtotime($row['attendanceDate'])),
            'empCode' => $row['empCode'],
            'name' => $row['displayName'],
            'department' => $row['deptName'],
            'scheduledIn' => $row['scheduledIn'] ? date('h:i A', strtotime($row['scheduledIn'])) : '09:00 AM',
            'actualIn' => $row['checkIn'] ? date('h:i A', strtotime($row['checkIn'])) : '-',
            'lateMinutes' => $row['lateMinutes'] ?: 0,
            'scheduledOut' => $row['scheduledOut'] ? date('h:i A', strtotime($row['scheduledOut'])) : '06:00 PM',
            'actualOut' => $row['checkOut'] ? date('h:i A', strtotime($row['checkOut'])) : '-',
            'earlyMinutes' => $row['earlyMinutes'] ?: 0,
            'reason' => $row['reason'] ?: '-',
            'status' => $row['reviewStatus'] ? ucfirst($row['reviewStatus']) : 'No Remark'
        ];
    }

    return [
        'success' => true,
        'fromDate' => $fromDate,
        'toDate' => $toDate,
        'count' => count($data),
        'data' => $data
    ];
}

/**
 * Get Overtime Report
 */
function getOvertimeReport()
{
    global $DB;

    $fromDate = $_POST['fromDate'] ?? date('Y-m-01');
    $toDate = $_POST['toDate'] ?? date('Y-m-d');
    $deptID = intval($_POST['deptID'] ?? 0);
    $employeeID = intval($_POST['employeeID'] ?? 0);

    $where = "a.status=1 AND a.attendanceDate BETWEEN ? AND ? AND a.overtimeHours > 0";
    $vals = array($fromDate, $toDate);
    $types = "ss";

    if ($deptID > 0) {
        $where .= " AND u.deptID=?";
        $vals[] = $deptID;
        $types .= "i";
    }

    if ($employeeID > 0) {
        $where .= " AND a.userID=?";
        $vals[] = $employeeID;
        $types .= "i";
    }

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "SELECT a.*, u.userName as displayName, u.employeeCode as empCode, u.department as deptName,
                       s.endTime as shiftEnd,
                       approver.userName as approverName
                FROM " . $DB->pre . "attendance a
                INNER JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                LEFT JOIN " . $DB->pre . "shift_master s ON a.shiftID = s.shiftID
                LEFT JOIN " . $DB->pre . "x_admin_user approver ON a.overtimeApprovedBy = approver.userID
                WHERE " . $where . "
                ORDER BY a.attendanceDate DESC, u.userName";
    $rows = $DB->dbRows();

    $data = [];
    $totalOTHours = 0;
    $approvedOTHours = 0;

    foreach ($rows as $row) {
        $otHours = floatval($row['overtimeHours']);
        $totalOTHours += $otHours;
        if ($row['overtimeApproved']) $approvedOTHours += $otHours;

        // Calculate OT rate based on type
        $otRate = 1.5; // Default weekday
        if ($row['overtimeType'] == 'weekend') $otRate = 2.0;
        if ($row['overtimeType'] == 'holiday') $otRate = 2.5;

        $data[] = [
            'date' => date('d M Y', strtotime($row['attendanceDate'])),
            'empCode' => $row['empCode'],
            'name' => $row['displayName'],
            'department' => $row['deptName'],
            'shiftEnd' => $row['shiftEnd'] ? date('h:i A', strtotime($row['shiftEnd'])) : '06:00 PM',
            'actualOut' => $row['checkOut'] ? date('h:i A', strtotime($row['checkOut'])) : '-',
            'otHours' => number_format($otHours, 1),
            'otType' => ucfirst($row['overtimeType'] ?: 'weekday'),
            'otRate' => $otRate . 'x',
            'approved' => $row['overtimeApproved'] ? 'Yes' : 'Pending',
            'approvedBy' => $row['approverName'] ?: '-'
        ];
    }

    return [
        'success' => true,
        'fromDate' => $fromDate,
        'toDate' => $toDate,
        'totalOTHours' => round($totalOTHours, 1),
        'approvedOTHours' => round($approvedOTHours, 1),
        'pendingOTHours' => round($totalOTHours - $approvedOTHours, 1),
        'count' => count($data),
        'data' => $data
    ];
}

/**
 * Get Absenteeism Report
 */
function getAbsenteeismReport()
{
    global $DB;

    $fromDate = $_POST['fromDate'] ?? date('Y-m-01');
    $toDate = $_POST['toDate'] ?? date('Y-m-d');
    $deptID = intval($_POST['deptID'] ?? 0);
    $employeeID = intval($_POST['employeeID'] ?? 0);

    // Get employees with absence summary
    $empWhere = "u.status=1";
    $empVals = array();
    $empTypes = "";

    if ($deptID > 0) {
        $empWhere .= " AND u.deptID=?";
        $empVals[] = $deptID;
        $empTypes .= "i";
    }

    if ($employeeID > 0) {
        $empWhere .= " AND u.userID=?";
        $empVals[] = $employeeID;
        $empTypes .= "i";
    }

    if (empty($empVals)) {
        $DB->vals = null;
        $DB->types = null;
    } else {
        $DB->vals = $empVals;
        $DB->types = $empTypes;
    }

    $DB->sql = "SELECT u.userID, u.employeeCode as empCode, u.userName as displayName, u.department as deptName
                FROM " . $DB->pre . "x_admin_user u
                WHERE " . $empWhere . "
                ORDER BY u.department, u.userName";
    $employees = $DB->dbRows();

    $data = [];

    foreach ($employees as $emp) {
        // Get absence count
        $DB->vals = array($emp['userID'], $fromDate, $toDate, 1, 'absent');
        $DB->types = "issis";
        $DB->sql = "SELECT COUNT(*) as absentCount,
                           GROUP_CONCAT(attendanceDate ORDER BY attendanceDate) as absentDates
                    FROM " . $DB->pre . "attendance
                    WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=? AND attendanceStatus=?";
        $absRow = $DB->dbRow();

        $absentCount = intval($absRow['absentCount'] ?? 0);
        if ($absentCount == 0) continue;

        // Calculate consecutive absences
        $absentDates = $absRow['absentDates'] ? explode(',', $absRow['absentDates']) : [];
        $maxConsecutive = 0;
        $currentConsecutive = 1;

        for ($i = 1; $i < count($absentDates); $i++) {
            $prevDate = strtotime($absentDates[$i - 1]);
            $currDate = strtotime($absentDates[$i]);
            $diff = ($currDate - $prevDate) / 86400;

            if ($diff == 1) {
                $currentConsecutive++;
            } else {
                $maxConsecutive = max($maxConsecutive, $currentConsecutive);
                $currentConsecutive = 1;
            }
        }
        $maxConsecutive = max($maxConsecutive, $currentConsecutive);

        // Day of week pattern
        $dayPattern = array_fill(0, 7, 0);
        foreach ($absentDates as $d) {
            $dow = date('w', strtotime($d));
            $dayPattern[$dow]++;
        }

        $data[] = [
            'empCode' => $emp['empCode'],
            'name' => $emp['displayName'],
            'department' => $emp['deptName'],
            'totalAbsent' => $absentCount,
            'consecutiveAbsent' => $maxConsecutive,
            'absentDates' => implode(', ', array_map(function($d) {
                return date('d M', strtotime($d));
            }, array_slice($absentDates, 0, 5))) . (count($absentDates) > 5 ? '...' : ''),
            'pattern' => implode('-', $dayPattern)
        ];
    }

    // Sort by total absent descending
    usort($data, function($a, $b) {
        return $b['totalAbsent'] - $a['totalAbsent'];
    });

    return [
        'success' => true,
        'fromDate' => $fromDate,
        'toDate' => $toDate,
        'count' => count($data),
        'data' => $data
    ];
}

/**
 * Get Department-wise Summary
 */
function getDepartmentSummary()
{
    global $DB;

    $date = $_POST['date'] ?? date('Y-m-d');

    // Get unique departments from users
    $DB->vals = array($date, 1);
    $DB->types = "si";
    $DB->sql = "SELECT u.department as deptName,
                       COUNT(DISTINCT u.userID) as totalEmployees,
                       SUM(CASE WHEN a.attendanceStatus='present' THEN 1 ELSE 0 END) as present,
                       SUM(CASE WHEN a.attendanceStatus='absent' THEN 1 ELSE 0 END) as absent,
                       SUM(CASE WHEN a.isLate=1 THEN 1 ELSE 0 END) as late,
                       SUM(CASE WHEN a.attendanceStatus='leave' THEN 1 ELSE 0 END) as onLeave
                FROM " . $DB->pre . "x_admin_user u
                LEFT JOIN " . $DB->pre . "attendance a ON u.userID = a.userID AND a.attendanceDate=? AND a.status=?
                WHERE u.status=1 AND u.department IS NOT NULL AND u.department != ''
                GROUP BY u.department
                ORDER BY u.department";
    $rows = $DB->dbRows();

    $data = [];
    foreach ($rows as $row) {
        $total = intval($row['totalEmployees']);
        $present = intval($row['present']);
        $attendanceRate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

        $data[] = [
            'deptID' => 0, // No separate dept table, using department field
            'department' => $row['deptName'],
            'total' => $total,
            'present' => $present,
            'absent' => $total - $present - intval($row['onLeave']),
            'late' => intval($row['late']),
            'leave' => intval($row['onLeave']),
            'attendanceRate' => $attendanceRate
        ];
    }

    return [
        'success' => true,
        'date' => $date,
        'data' => $data
    ];
}

/**
 * Get Master Report - Detailed attendance for all employees in a date range
 */
function getMasterReport()
{
    global $DB;

    $fromDate = $_POST['fromDate'] ?? date('Y-m-01');
    $toDate = $_POST['toDate'] ?? date('Y-m-d');
    $deptID = intval($_POST['deptID'] ?? 0);
    $employeeID = intval($_POST['employeeID'] ?? 0);

    // Build WHERE conditions
    $where = "a.status=1 AND a.attendanceDate BETWEEN ? AND ?";
    $vals = array($fromDate, $toDate);
    $types = "ss";

    if ($deptID > 0) {
        $where .= " AND u.deptID=?";
        $vals[] = $deptID;
        $types .= "i";
    }

    if ($employeeID > 0) {
        $where .= " AND a.userID=?";
        $vals[] = $employeeID;
        $types .= "i";
    }

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "SELECT a.*,
                       u.displayName, u.empCode, u.department,
                       DATE_FORMAT(a.attendanceDate, '%d %b %Y') as dateFormatted,
                       DAYNAME(a.attendanceDate) as dayName,
                       DATE_FORMAT(a.checkIn, '%h:%i %p') as checkInFormatted,
                       DATE_FORMAT(a.checkOut, '%h:%i %p') as checkOutFormatted,
                       DATE_FORMAT(a.scheduledIn, '%h:%i %p') as scheduledInFormatted,
                       DATE_FORMAT(a.scheduledOut, '%h:%i %p') as scheduledOutFormatted
                FROM " . $DB->pre . "attendance a
                JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                WHERE $where AND u.status=1
                ORDER BY a.attendanceDate DESC, u.displayName ASC";
    $rows = $DB->dbRows();

    // Build data
    $data = [];
    $totalPresent = 0;
    $totalAbsent = 0;
    $totalLate = 0;
    $totalLeave = 0;
    $totalHours = 0;

    // Track which employee-dates have attendance records
    $attendanceDates = [];

    foreach ($rows as $row) {
        $status = ucfirst($row['attendanceStatus']);
        if ($row['attendanceStatus'] === 'present') $totalPresent++;
        if ($row['attendanceStatus'] === 'absent') $totalAbsent++;
        if ($row['attendanceStatus'] === 'leave') $totalLeave++;
        if ($row['isLate'] == 1) $totalLate++;
        $totalHours += floatval($row['workingHours'] ?? 0);

        // Track this attendance record
        $attendanceDates[$row['userID'] . '_' . $row['attendanceDate']] = true;

        $data[] = [
            'date' => $row['dateFormatted'],
            'dayName' => $row['dayName'],
            'empCode' => $row['empCode'] ?: 'EMP' . str_pad($row['userID'], 4, '0', STR_PAD_LEFT),
            'name' => $row['displayName'],
            'department' => $row['department'] ?: '-',
            'checkIn' => $row['checkInFormatted'] ?: '-',
            'checkOut' => $row['checkOutFormatted'] ?: '-',
            'scheduledIn' => $row['scheduledInFormatted'] ?: '-',
            'scheduledOut' => $row['scheduledOutFormatted'] ?: '-',
            'lateMinutes' => intval($row['lateMinutes'] ?? 0),
            'earlyMinutes' => intval($row['earlyMinutes'] ?? 0),
            'workingHours' => $row['workingHours'] ? number_format($row['workingHours'], 2) : '-',
            'status' => $status,
            'source' => ucfirst($row['source'] ?? 'manual'),
            'remarks' => $row['remarks'] ?: ''
        ];
    }

    // Get approved leave records from leave_details table (for dates without attendance records)
    $leaveWhere = "ld.leaveDate BETWEEN ? AND ? AND ld.lType = 1 AND l.leaveStatus = 'Approved' AND l.status = 1";
    $leaveVals = array($fromDate, $toDate);
    $leaveTypes = "ss";

    if ($employeeID > 0) {
        $leaveWhere .= " AND ld.userID=?";
        $leaveVals[] = $employeeID;
        $leaveTypes .= "i";
    }

    $DB->vals = $leaveVals;
    $DB->types = $leaveTypes;
    $DB->sql = "SELECT ld.leaveDate, ld.userID, l.reason,
                       u.displayName, u.employeeCode as empCode, u.department,
                       DATE_FORMAT(ld.leaveDate, '%d %b %Y') as dateFormatted,
                       DAYNAME(ld.leaveDate) as dayName,
                       lt.leaveTypeName
                FROM " . $DB->pre . "leave_details ld
                JOIN " . $DB->pre . "leave l ON ld.leaveID = l.leaveID
                JOIN " . $DB->pre . "x_admin_user u ON ld.userID = u.userID
                LEFT JOIN " . $DB->pre . "leave_type lt ON l.leaveType = lt.leaveTypeID
                WHERE $leaveWhere AND u.status=1
                ORDER BY ld.leaveDate DESC, u.displayName ASC";
    $leaveRows = $DB->dbRows();

    // Add leave records that don't have corresponding attendance records
    foreach ($leaveRows as $row) {
        $key = $row['userID'] . '_' . $row['leaveDate'];
        if (!isset($attendanceDates[$key])) {
            $totalLeave++;
            $data[] = [
                'date' => $row['dateFormatted'],
                'dayName' => $row['dayName'],
                'empCode' => $row['empCode'] ?: 'EMP' . str_pad($row['userID'], 4, '0', STR_PAD_LEFT),
                'name' => $row['displayName'],
                'department' => $row['department'] ?: '-',
                'checkIn' => '-',
                'checkOut' => '-',
                'scheduledIn' => '-',
                'scheduledOut' => '-',
                'lateMinutes' => 0,
                'earlyMinutes' => 0,
                'workingHours' => '-',
                'status' => 'On Leave',
                'source' => 'Leave',
                'remarks' => ($row['leaveTypeName'] ?? 'Leave') . ($row['reason'] ? ': ' . $row['reason'] : '')
            ];
        }
    }

    // Sort by date DESC, then name ASC
    usort($data, function($a, $b) {
        $dateCompare = strcmp($b['date'], $a['date']);
        if ($dateCompare !== 0) return $dateCompare;
        return strcmp($a['name'], $b['name']);
    });

    return [
        'success' => true,
        'fromDate' => $fromDate,
        'toDate' => $toDate,
        'totalRecords' => count($data),
        'totalPresent' => $totalPresent,
        'totalAbsent' => $totalAbsent,
        'totalLate' => $totalLate,
        'totalLeave' => $totalLeave,
        'totalHours' => number_format($totalHours, 1),
        'data' => $data
    ];
}
