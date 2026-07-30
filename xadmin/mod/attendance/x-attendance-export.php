<?php
/**
 * Attendance Report Export Handler
 * Generates Excel, PDF, and CSV exports
 */

// PhpSpreadsheet use statements (must be at top of file)
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

require_once("../../../core/core.inc.php");
require_once("../../inc/site.inc.php");

$MXRES = mxCheckRequest();
if ($MXRES["err"] != 0) {
    die('Unauthorized');
}

// Get export parameters
$format = $_GET['format'] ?? 'excel';
$type = $_GET['type'] ?? 'daily';
$fromDate = $_GET['from'] ?? date('Y-m-01');
$toDate = $_GET['to'] ?? date('Y-m-d');
$deptID = intval($_GET['dept'] ?? 0);

// Get report data based on type
$reportData = getReportData($type, $fromDate, $toDate, $deptID);

// Export based on format
switch ($format) {
    case 'excel':
        exportExcel($type, $reportData, $fromDate, $toDate);
        break;
    case 'pdf':
        exportPDF($type, $reportData, $fromDate, $toDate);
        break;
    case 'csv':
        exportCSV($type, $reportData);
        break;
    default:
        die('Invalid export format');
}

/**
 * Get Report Data
 */
function getReportData($type, $fromDate, $toDate, $deptID)
{
    global $DB;

    switch ($type) {
        case 'daily':
            return getDailyAttendance($fromDate, $deptID);
        case 'monthly':
            $month = date('n', strtotime($fromDate));
            $year = date('Y', strtotime($fromDate));
            return getMonthlyMuster($month, $year, $deptID);
        case 'payroll':
            $month = date('n', strtotime($fromDate));
            $year = date('Y', strtotime($fromDate));
            return getPayrollData($month, $year, $deptID);
        case 'late_early':
            return getLateEarlyData($fromDate, $toDate, $deptID);
        case 'overtime':
            return getOvertimeData($fromDate, $toDate, $deptID);
        case 'absenteeism':
            return getAbsenteeismData($fromDate, $toDate, $deptID);
        default:
            return [];
    }
}

/**
 * Get Daily Attendance Data
 */
function getDailyAttendance($date, $deptID)
{
    global $DB;

    $where = "a.status=1 AND a.attendanceDate=?";
    $vals = array($date);
    $types = "s";

    if ($deptID > 0) {
        $where .= " AND u.deptID=?";
        $vals[] = $deptID;
        $types .= "i";
    }

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "SELECT a.*, u.userName as displayName, u.employeeCode as empCode, u.department as deptName,
                       COALESCE(s.shiftName, 'General') as shiftName,
                       s.startTime as scheduledIn, s.endTime as scheduledOut
                FROM " . $DB->pre . "attendance a
                INNER JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                LEFT JOIN " . $DB->pre . "shift_master s ON a.shiftID = s.shiftID
                WHERE " . $where . "
                ORDER BY u.department, u.userName";

    return [
        'title' => 'Daily Attendance Report',
        'subtitle' => 'Date: ' . date('d M Y', strtotime($date)),
        'columns' => ['Emp Code', 'Employee Name', 'Department', 'Shift', 'Check In', 'Check Out', 'Working Hrs', 'Status', 'Late By', 'Early By', 'Remarks'],
        'data' => $DB->dbRows()
    ];
}

/**
 * Get Monthly Muster Roll Data
 */
function getMonthlyMuster($month, $year, $deptID)
{
    global $DB;

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));
    $daysInMonth = date('t', strtotime($startDate));
    $monthName = date('F Y', strtotime($startDate));

    // Get employees
    $empWhere = "u.status=1";
    $empVals = array();
    $empTypes = "";
    if ($deptID > 0) {
        $empWhere .= " AND u.department=?";
        $empVals[] = $deptID;
        $empTypes .= "s";
    }

    $DB->sql = "SELECT u.userID, u.employeeCode as empCode, u.userName as displayName, u.department as deptName
                FROM " . $DB->pre . "x_admin_user u
                WHERE " . $empWhere . "
                ORDER BY u.department, u.userName";
    $DB->vals = empty($empVals) ? null : $empVals;
    $DB->types = empty($empTypes) ? null : $empTypes;
    $employees = $DB->dbRows();

    // Get holidays
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT holidayDate FROM " . $DB->pre . "holiday_master WHERE holidayDate BETWEEN ? AND ? AND status=?";
    $holidayRows = $DB->dbRows();
    $holidays = array_column($holidayRows, 'holidayDate');

    $musterData = [];

    foreach ($employees as $emp) {
        $DB->vals = array($emp['userID'], $startDate, $endDate, 1);
        $DB->types = "issi";
        $DB->sql = "SELECT attendanceDate, attendanceStatus, isLate FROM " . $DB->pre . "attendance WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=?";
        $attRows = $DB->dbRows();

        $attLookup = [];
        foreach ($attRows as $att) {
            $attLookup[$att['attendanceDate']] = $att;
        }

        $row = [
            'empCode' => $emp['empCode'],
            'name' => $emp['displayName'],
            'department' => $emp['deptName'],
            'days' => []
        ];

        $summary = ['P' => 0, 'A' => 0, 'L' => 0, 'H' => 0, 'WO' => 0, 'HD' => 0, 'LT' => 0];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dayOfWeek = date('w', strtotime($dateStr));
            $isFuture = strtotime($dateStr) > time();

            if ($isFuture) {
                $row['days'][$d] = '-';
            } elseif ($dayOfWeek == 0) {
                $row['days'][$d] = 'WO';
                $summary['WO']++;
            } elseif (in_array($dateStr, $holidays)) {
                $row['days'][$d] = 'H';
                $summary['H']++;
            } elseif (isset($attLookup[$dateStr])) {
                $att = $attLookup[$dateStr];
                switch ($att['attendanceStatus']) {
                    case 'present':
                        $row['days'][$d] = $att['isLate'] ? 'LT' : 'P';
                        $summary['P']++;
                        if ($att['isLate']) $summary['LT']++;
                        break;
                    case 'absent':
                        $row['days'][$d] = 'A';
                        $summary['A']++;
                        break;
                    case 'leave':
                        $row['days'][$d] = 'L';
                        $summary['L']++;
                        break;
                    case 'half_day':
                        $row['days'][$d] = 'HD';
                        $summary['HD']++;
                        break;
                    default:
                        $row['days'][$d] = 'P';
                        $summary['P']++;
                }
            } else {
                $row['days'][$d] = 'A';
                $summary['A']++;
            }
        }

        $row['summary'] = $summary;
        $row['payable'] = $summary['P'] + $summary['L'] + ($summary['HD'] * 0.5);
        $musterData[] = $row;
    }

    return [
        'title' => 'Monthly Attendance Register (Muster Roll)',
        'subtitle' => $monthName,
        'daysInMonth' => $daysInMonth,
        'data' => $musterData
    ];
}

/**
 * Get Payroll Summary Data
 */
function getPayrollData($month, $year, $deptID)
{
    global $DB;

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));
    $daysInMonth = date('t', strtotime($startDate));
    $monthName = date('F Y', strtotime($startDate));

    // Calculate working days
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT COUNT(*) as cnt FROM " . $DB->pre . "holiday_master WHERE holidayDate BETWEEN ? AND ? AND status=?";
    $holidayCount = $DB->dbRow()['cnt'] ?? 0;

    $sundays = 0;
    $currentDate = strtotime($startDate);
    while ($currentDate <= strtotime($endDate)) {
        if (date('w', $currentDate) == 0) $sundays++;
        $currentDate = strtotime('+1 day', $currentDate);
    }
    $workingDays = $daysInMonth - $sundays - $holidayCount;

    // Get employees
    $empWhere = "u.status=1";
    $empVals = array();
    $empTypes = "";
    if ($deptID > 0) {
        $empWhere .= " AND u.department=?";
        $empVals[] = $deptID;
        $empTypes .= "s";
    }

    $DB->sql = "SELECT u.userID, u.employeeCode as empCode, u.userName as displayName, u.department as deptName FROM " . $DB->pre . "x_admin_user u WHERE " . $empWhere . " ORDER BY u.department, u.userName";
    $DB->vals = empty($empVals) ? null : $empVals;
    $DB->types = empty($empTypes) ? null : $empTypes;
    $employees = $DB->dbRows();

    $payrollData = [];

    foreach ($employees as $emp) {
        $DB->vals = array($emp['userID'], $startDate, $endDate, 1);
        $DB->types = "issi";
        $DB->sql = "SELECT
                        SUM(CASE WHEN attendanceStatus='present' THEN 1 ELSE 0 END) as presentDays,
                        SUM(CASE WHEN attendanceStatus='leave' THEN 1 ELSE 0 END) as leaveDays,
                        SUM(CASE WHEN attendanceStatus='half_day' THEN 1 ELSE 0 END) as halfDays,
                        SUM(CASE WHEN isLate=1 THEN 1 ELSE 0 END) as lateDays,
                        SUM(COALESCE(lateMinutes, 0)) as totalLateMinutes,
                        SUM(COALESCE(workingHours, 0)) as totalHours,
                        SUM(CASE WHEN overtimeApproved=1 THEN COALESCE(overtimeHours, 0) ELSE 0 END) as otHours
                    FROM " . $DB->pre . "attendance WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=?";
        $summary = $DB->dbRow();

        $presentDays = intval($summary['presentDays'] ?? 0);
        $leaveDays = intval($summary['leaveDays'] ?? 0);
        $halfDays = intval($summary['halfDays'] ?? 0);
        $absentDays = $workingDays - $presentDays - $leaveDays - ($halfDays * 0.5);
        if ($absentDays < 0) $absentDays = 0;

        $payrollData[] = [
            'empCode' => $emp['empCode'],
            'name' => $emp['displayName'],
            'department' => $emp['deptName'],
            'workingDays' => $workingDays,
            'presentDays' => $presentDays,
            'absentDays' => $absentDays,
            'leaveDays' => $leaveDays,
            'halfDays' => $halfDays,
            'lateDays' => intval($summary['lateDays'] ?? 0),
            'lateMinutes' => intval($summary['totalLateMinutes'] ?? 0),
            'totalHours' => round(floatval($summary['totalHours'] ?? 0), 1),
            'otHours' => round(floatval($summary['otHours'] ?? 0), 1),
            'payableDays' => $presentDays + $leaveDays + ($halfDays * 0.5),
            'deductionDays' => $absentDays
        ];
    }

    return [
        'title' => 'Attendance Summary for Payroll',
        'subtitle' => $monthName,
        'workingDays' => $workingDays,
        'columns' => ['Emp Code', 'Employee Name', 'Department', 'Working Days', 'Present', 'Absent', 'Leave', 'Half Day', 'Late Count', 'Late Mins', 'Total Hrs', 'OT Hrs', 'Payable Days', 'Deduction Days'],
        'data' => $payrollData
    ];
}

/**
 * Get Late/Early Data
 */
function getLateEarlyData($fromDate, $toDate, $deptID)
{
    global $DB;

    $where = "a.status=1 AND a.attendanceDate BETWEEN ? AND ? AND (a.isLate=1 OR a.isEarlyCheckout=1)";
    $vals = array($fromDate, $toDate);
    $types = "ss";

    if ($deptID > 0) {
        $where .= " AND u.deptID=?";
        $vals[] = $deptID;
        $types .= "i";
    }

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "SELECT a.*, u.userName as displayName, u.employeeCode as empCode, u.department as deptName, ar.reason, ar.reviewStatus
                FROM " . $DB->pre . "attendance a
                INNER JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                LEFT JOIN " . $DB->pre . "attendance_remarks ar ON a.attendanceID = ar.attendanceID
                WHERE " . $where . " ORDER BY a.attendanceDate DESC, u.userName";

    return [
        'title' => 'Late Arrival / Early Checkout Report',
        'subtitle' => date('d M Y', strtotime($fromDate)) . ' to ' . date('d M Y', strtotime($toDate)),
        'columns' => ['Date', 'Emp Code', 'Employee Name', 'Department', 'Check In', 'Late (mins)', 'Check Out', 'Early (mins)', 'Reason', 'Status'],
        'data' => $DB->dbRows()
    ];
}

/**
 * Get Overtime Data
 */
function getOvertimeData($fromDate, $toDate, $deptID)
{
    global $DB;

    $where = "a.status=1 AND a.attendanceDate BETWEEN ? AND ? AND a.overtimeHours > 0";
    $vals = array($fromDate, $toDate);
    $types = "ss";

    if ($deptID > 0) {
        $where .= " AND u.deptID=?";
        $vals[] = $deptID;
        $types .= "i";
    }

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "SELECT a.*, u.userName as displayName, u.employeeCode as empCode, u.department as deptName, approver.userName as approverName
                FROM " . $DB->pre . "attendance a
                INNER JOIN " . $DB->pre . "x_admin_user u ON a.userID = u.userID
                LEFT JOIN " . $DB->pre . "x_admin_user approver ON a.overtimeApprovedBy = approver.userID
                WHERE " . $where . " ORDER BY a.attendanceDate DESC, u.userName";

    return [
        'title' => 'Overtime Report',
        'subtitle' => date('d M Y', strtotime($fromDate)) . ' to ' . date('d M Y', strtotime($toDate)),
        'columns' => ['Date', 'Emp Code', 'Employee Name', 'Department', 'Check Out', 'OT Hours', 'OT Type', 'Approved', 'Approved By'],
        'data' => $DB->dbRows()
    ];
}

/**
 * Get Absenteeism Data
 */
function getAbsenteeismData($fromDate, $toDate, $deptID)
{
    global $DB;

    $empWhere = "u.status=1";
    $empVals = array();
    $empTypes = "";
    if ($deptID > 0) {
        $empWhere .= " AND u.department=?";
        $empVals[] = $deptID;
        $empTypes .= "s";
    }

    $DB->sql = "SELECT u.userID, u.employeeCode as empCode, u.userName as displayName, u.department as deptName FROM " . $DB->pre . "x_admin_user u WHERE " . $empWhere . " ORDER BY u.department, u.userName";
    $DB->vals = empty($empVals) ? null : $empVals;
    $DB->types = empty($empTypes) ? null : $empTypes;
    $employees = $DB->dbRows();

    $data = [];

    foreach ($employees as $emp) {
        $DB->vals = array($emp['userID'], $fromDate, $toDate, 1, 'absent');
        $DB->types = "issis";
        $DB->sql = "SELECT COUNT(*) as absentCount, GROUP_CONCAT(attendanceDate ORDER BY attendanceDate) as absentDates FROM " . $DB->pre . "attendance WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=? AND attendanceStatus=?";
        $absRow = $DB->dbRow();

        $absentCount = intval($absRow['absentCount'] ?? 0);
        if ($absentCount == 0) continue;

        $data[] = [
            'empCode' => $emp['empCode'],
            'name' => $emp['displayName'],
            'department' => $emp['deptName'],
            'absentCount' => $absentCount,
            'absentDates' => $absRow['absentDates']
        ];
    }

    usort($data, function($a, $b) { return $b['absentCount'] - $a['absentCount']; });

    return [
        'title' => 'Absenteeism Report',
        'subtitle' => date('d M Y', strtotime($fromDate)) . ' to ' . date('d M Y', strtotime($toDate)),
        'columns' => ['Emp Code', 'Employee Name', 'Department', 'Total Absent Days', 'Absent Dates'],
        'data' => $data
    ];
}

/**
 * Export to Excel
 */
function exportExcel($type, $reportData, $fromDate, $toDate)
{
    // Check if PhpSpreadsheet is available
    $spreadsheetPath = dirname(__FILE__) . '/../../../vendor/autoload.php';
    if (file_exists($spreadsheetPath)) {
        require_once $spreadsheetPath;
        exportExcelPhpSpreadsheet($type, $reportData);
    } else {
        // Fallback to simple CSV with Excel-friendly formatting
        exportExcelSimple($type, $reportData);
    }
}

/**
 * Export Excel using PhpSpreadsheet
 */
function exportExcelPhpSpreadsheet($type, $reportData)
{
    // Note: use statements moved to top of file
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Title
    $sheet->setCellValue('A1', $reportData['title']);
    $sheet->setCellValue('A2', $reportData['subtitle']);
    $sheet->mergeCells('A1:' . chr(64 + count($reportData['columns'])) . '1');
    $sheet->mergeCells('A2:' . chr(64 + count($reportData['columns'])) . '2');

    // Style title
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A2')->getFont()->setItalic(true);

    // Headers
    $col = 'A';
    $row = 4;
    foreach ($reportData['columns'] as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $col++;
    }

    // Data
    $row = 5;
    foreach ($reportData['data'] as $dataRow) {
        $col = 'A';
        foreach ($dataRow as $value) {
            if (!is_array($value)) {
                $sheet->setCellValue($col . $row, $value);
            }
            $col++;
        }
        $row++;
    }

    // Auto-size columns
    foreach (range('A', chr(64 + count($reportData['columns']))) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Output
    $filename = 'attendance_' . $type . '_' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Export Excel Simple (HTML table that Excel can open)
 */
function exportExcelSimple($type, $reportData)
{
    $filename = 'attendance_' . $type . '_' . date('Y-m-d') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';

    // Title
    echo '<table>';
    echo '<tr><td colspan="' . count($reportData['columns']) . '" style="font-size:18px;font-weight:bold;">' . htmlspecialchars($reportData['title']) . '</td></tr>';
    echo '<tr><td colspan="' . count($reportData['columns']) . '" style="font-style:italic;">' . htmlspecialchars($reportData['subtitle']) . '</td></tr>';
    echo '<tr><td></td></tr>';

    // Handle different report types
    if ($type === 'monthly') {
        // Monthly Muster Roll has special structure
        $daysInMonth = $reportData['daysInMonth'];

        // Headers
        echo '<tr style="background:#e2e8f0;font-weight:bold;">';
        echo '<td>Emp Code</td><td>Name</td><td>Dept</td>';
        for ($d = 1; $d <= $daysInMonth; $d++) {
            echo '<td>' . $d . '</td>';
        }
        echo '<td>P</td><td>A</td><td>L</td><td>H</td><td>WO</td><td>Payable</td>';
        echo '</tr>';

        // Data
        foreach ($reportData['data'] as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['empCode']) . '</td>';
            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['department']) . '</td>';

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $status = $row['days'][$d] ?? '-';
                $bgColor = '';
                if ($status === 'P') $bgColor = 'background:#dcfce7;';
                elseif ($status === 'A') $bgColor = 'background:#fee2e2;';
                elseif ($status === 'L') $bgColor = 'background:#dbeafe;';
                elseif ($status === 'LT') $bgColor = 'background:#fef3c7;';
                elseif ($status === 'H' || $status === 'WO') $bgColor = 'background:#f1f5f9;';

                echo '<td style="' . $bgColor . 'text-align:center;">' . $status . '</td>';
            }

            echo '<td>' . $row['summary']['P'] . '</td>';
            echo '<td>' . $row['summary']['A'] . '</td>';
            echo '<td>' . $row['summary']['L'] . '</td>';
            echo '<td>' . $row['summary']['H'] . '</td>';
            echo '<td>' . $row['summary']['WO'] . '</td>';
            echo '<td style="font-weight:bold;">' . $row['payable'] . '</td>';
            echo '</tr>';
        }
    } else {
        // Standard report format
        // Headers
        echo '<tr style="background:#e2e8f0;font-weight:bold;">';
        foreach ($reportData['columns'] as $header) {
            echo '<td>' . htmlspecialchars($header) . '</td>';
        }
        echo '</tr>';

        // Data
        foreach ($reportData['data'] as $row) {
            echo '<tr>';
            if ($type === 'daily') {
                echo '<td>' . htmlspecialchars($row['empCode']) . '</td>';
                echo '<td>' . htmlspecialchars($row['displayName']) . '</td>';
                echo '<td>' . htmlspecialchars($row['deptName']) . '</td>';
                echo '<td>' . htmlspecialchars($row['shiftName']) . '</td>';
                echo '<td>' . ($row['checkIn'] ? date('h:i A', strtotime($row['checkIn'])) : '-') . '</td>';
                echo '<td>' . ($row['checkOut'] ? date('h:i A', strtotime($row['checkOut'])) : '-') . '</td>';
                echo '<td>' . ($row['workingHours'] ?: '-') . '</td>';
                echo '<td>' . ucfirst($row['attendanceStatus']) . '</td>';
                echo '<td>' . ($row['lateMinutes'] ?: '-') . '</td>';
                echo '<td>' . ($row['earlyMinutes'] ?: '-') . '</td>';
                echo '<td>' . htmlspecialchars($row['remarks'] ?? '') . '</td>';
            } elseif ($type === 'payroll') {
                echo '<td>' . htmlspecialchars($row['empCode']) . '</td>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department']) . '</td>';
                echo '<td>' . $row['workingDays'] . '</td>';
                echo '<td>' . $row['presentDays'] . '</td>';
                echo '<td>' . $row['absentDays'] . '</td>';
                echo '<td>' . $row['leaveDays'] . '</td>';
                echo '<td>' . $row['halfDays'] . '</td>';
                echo '<td>' . $row['lateDays'] . '</td>';
                echo '<td>' . $row['lateMinutes'] . '</td>';
                echo '<td>' . $row['totalHours'] . '</td>';
                echo '<td>' . $row['otHours'] . '</td>';
                echo '<td style="font-weight:bold;">' . $row['payableDays'] . '</td>';
                echo '<td style="color:red;">' . $row['deductionDays'] . '</td>';
            } elseif ($type === 'late_early') {
                echo '<td>' . date('d M Y', strtotime($row['attendanceDate'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['empCode']) . '</td>';
                echo '<td>' . htmlspecialchars($row['displayName']) . '</td>';
                echo '<td>' . htmlspecialchars($row['deptName']) . '</td>';
                echo '<td>' . ($row['checkIn'] ? date('h:i A', strtotime($row['checkIn'])) : '-') . '</td>';
                echo '<td>' . ($row['lateMinutes'] ?: '-') . '</td>';
                echo '<td>' . ($row['checkOut'] ? date('h:i A', strtotime($row['checkOut'])) : '-') . '</td>';
                echo '<td>' . ($row['earlyMinutes'] ?: '-') . '</td>';
                echo '<td>' . htmlspecialchars($row['reason'] ?? '-') . '</td>';
                echo '<td>' . ucfirst($row['reviewStatus'] ?? 'No Remark') . '</td>';
            } elseif ($type === 'overtime') {
                $otRate = $row['overtimeType'] == 'weekend' ? '2x' : ($row['overtimeType'] == 'holiday' ? '2.5x' : '1.5x');
                echo '<td>' . date('d M Y', strtotime($row['attendanceDate'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['empCode']) . '</td>';
                echo '<td>' . htmlspecialchars($row['displayName']) . '</td>';
                echo '<td>' . htmlspecialchars($row['deptName']) . '</td>';
                echo '<td>' . ($row['checkOut'] ? date('h:i A', strtotime($row['checkOut'])) : '-') . '</td>';
                echo '<td>' . number_format($row['overtimeHours'], 1) . '</td>';
                echo '<td>' . ucfirst($row['overtimeType'] ?? 'weekday') . ' (' . $otRate . ')</td>';
                echo '<td>' . ($row['overtimeApproved'] ? 'Yes' : 'Pending') . '</td>';
                echo '<td>' . htmlspecialchars($row['approverName'] ?? '-') . '</td>';
            } elseif ($type === 'absenteeism') {
                echo '<td>' . htmlspecialchars($row['empCode']) . '</td>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department']) . '</td>';
                echo '<td>' . $row['absentCount'] . '</td>';
                echo '<td>' . htmlspecialchars($row['absentDates'] ?? '') . '</td>';
            }
            echo '</tr>';
        }
    }

    echo '</table>';
    echo '</body></html>';
    exit;
}

/**
 * Export to PDF
 */
function exportPDF($type, $reportData, $fromDate, $toDate)
{
    // Check if MPDF is available
    $mpdfPath = dirname(__FILE__) . '/../../../vendor/autoload.php';
    if (file_exists($mpdfPath)) {
        require_once $mpdfPath;
        exportPDFMpdf($type, $reportData);
    } else {
        // Fallback - generate HTML and let browser print to PDF
        exportPDFHtml($type, $reportData);
    }
}

/**
 * Export PDF using MPDF
 */
function exportPDFMpdf($type, $reportData)
{
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => $type === 'monthly' ? 'A4-L' : 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10
    ]);

    $html = generatePDFHtml($type, $reportData);
    $mpdf->WriteHTML($html);

    $filename = 'attendance_' . $type . '_' . date('Y-m-d') . '.pdf';
    $mpdf->Output($filename, 'D');
    exit;
}

/**
 * Export PDF as HTML (fallback)
 */
function exportPDFHtml($type, $reportData)
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
    echo '<title>' . htmlspecialchars($reportData['title']) . '</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #e2e8f0; font-weight: bold; }
        h1 { font-size: 18px; margin: 0; }
        .subtitle { color: #666; font-style: italic; margin-bottom: 20px; }
        .print-btn { margin: 20px 0; }
        @media print { .print-btn { display: none; } }
    </style>';
    echo '</head><body>';
    echo '<button class="print-btn" onclick="window.print()">Print / Save as PDF</button>';
    echo generatePDFHtml($type, $reportData);
    echo '</body></html>';
    exit;
}

/**
 * Generate HTML for PDF
 */
function generatePDFHtml($type, $reportData)
{
    $html = '<h1>' . htmlspecialchars($reportData['title']) . '</h1>';
    $html .= '<div class="subtitle">' . htmlspecialchars($reportData['subtitle']) . '</div>';
    $html .= '<table>';

    // Similar structure as Excel export
    if ($type === 'monthly') {
        $daysInMonth = $reportData['daysInMonth'];
        $html .= '<tr><th>Code</th><th>Name</th><th>Dept</th>';
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $html .= '<th style="width:20px;text-align:center;">' . $d . '</th>';
        }
        $html .= '<th>P</th><th>A</th><th>L</th><th>Pay</th></tr>';

        foreach ($reportData['data'] as $row) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['empCode']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['department']) . '</td>';

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $status = $row['days'][$d] ?? '-';
                $bgColor = '';
                if ($status === 'P') $bgColor = 'background:#dcfce7;';
                elseif ($status === 'A') $bgColor = 'background:#fee2e2;';
                elseif ($status === 'L') $bgColor = 'background:#dbeafe;';
                elseif ($status === 'LT') $bgColor = 'background:#fef3c7;';
                $html .= '<td style="' . $bgColor . 'text-align:center;font-size:10px;">' . $status . '</td>';
            }

            $html .= '<td>' . $row['summary']['P'] . '</td>';
            $html .= '<td>' . $row['summary']['A'] . '</td>';
            $html .= '<td>' . $row['summary']['L'] . '</td>';
            $html .= '<td><strong>' . $row['payable'] . '</strong></td>';
            $html .= '</tr>';
        }
    } else {
        // Headers
        $html .= '<tr>';
        foreach ($reportData['columns'] as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr>';

        // Data - simplified for PDF
        foreach ($reportData['data'] as $row) {
            $html .= '<tr>';
            foreach ($row as $key => $value) {
                if (!is_array($value)) {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
            }
            $html .= '</tr>';
        }
    }

    $html .= '</table>';
    $html .= '<div style="margin-top:30px;font-size:10px;color:#666;">Generated on ' . date('d M Y h:i A') . '</div>';

    return $html;
}

/**
 * Export to CSV
 */
function exportCSV($type, $reportData)
{
    $filename = 'attendance_' . $type . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Title rows
    fputcsv($output, [$reportData['title']]);
    fputcsv($output, [$reportData['subtitle']]);
    fputcsv($output, []);

    if ($type === 'monthly') {
        $daysInMonth = $reportData['daysInMonth'];

        // Headers
        $headers = ['Emp Code', 'Name', 'Department'];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $headers[] = $d;
        }
        $headers = array_merge($headers, ['Present', 'Absent', 'Leave', 'Holiday', 'WeeklyOff', 'Payable']);
        fputcsv($output, $headers);

        // Data
        foreach ($reportData['data'] as $row) {
            $csvRow = [$row['empCode'], $row['name'], $row['department']];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $csvRow[] = $row['days'][$d] ?? '-';
            }
            $csvRow[] = $row['summary']['P'];
            $csvRow[] = $row['summary']['A'];
            $csvRow[] = $row['summary']['L'];
            $csvRow[] = $row['summary']['H'];
            $csvRow[] = $row['summary']['WO'];
            $csvRow[] = $row['payable'];
            fputcsv($output, $csvRow);
        }
    } else {
        // Headers
        fputcsv($output, $reportData['columns']);

        // Data
        foreach ($reportData['data'] as $row) {
            $csvRow = [];
            foreach ($row as $value) {
                if (!is_array($value)) {
                    $csvRow[] = $value;
                }
            }
            fputcsv($output, $csvRow);
        }
    }

    fclose($output);
    exit;
}
