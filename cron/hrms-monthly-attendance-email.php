<?php
/**
 * HRMS Monthly Attendance Email Cron Job
 *
 * Runs on the 1st of every month to send:
 * 1. Individual attendance reports to each employee (previous month)
 * 2. Master attendance report (PDF/Excel) to HR Admin
 *
 * Cron Command (run at 8:00 AM on 1st of every month):
 * 0 8 1 * * /usr/bin/php /home/bombayengg/public_html/cron/hrms-monthly-attendance-email.php >> /home/bombayengg/logs/hrms-email.log 2>&1
 */

// Set server variables for CLI mode
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'www.bombayengg.net';
    $_SERVER['REQUEST_URI'] = '/cron/hrms-monthly-attendance-email.php';
    $_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';
}

// Prevent browser access
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    // Allow manual trigger with secret key for testing
    if (!isset($_GET['cron_key']) || $_GET['cron_key'] !== 'BES_HRMS_2024_SECURE') {
        die('This script can only be run from command line or with valid cron key');
    }
}

// Load core files
require_once(dirname(__FILE__) . '/../core/core.inc.php');
require_once(dirname(__FILE__) . '/../xadmin/inc/site.inc.php');
require_once(dirname(__FILE__) . '/../core/brevo.inc.php');

// MPDF for PDF generation
require_once(ROOTPATH . '/vendor/autoload.php');

// Master Admin Recipients for individual PDF reports
define('MASTER_ADMIN_RECIPIENTS', [
    ['email' => 'manishbeskkc@gmail.com', 'name' => 'Manish Narvekar'],
    ['email' => 'paritosh.ajmera@gmail.com', 'name' => 'Paritosh Ajmera']
]);

// Max PDFs per email (Brevo has attachment limits)
define('MAX_ATTACHMENTS_PER_EMAIL', 10);

// Log start
logCron("=== HRMS Monthly Attendance Email Cron Started ===");
logCron("Date: " . date('Y-m-d H:i:s'));

// Calculate previous month
$prevMonth = date('n', strtotime('first day of last month'));
$prevYear = date('Y', strtotime('first day of last month'));
$prevMonthName = date('F Y', strtotime('first day of last month'));
$daysInMonth = date('t', strtotime("$prevYear-$prevMonth-01"));

logCron("Processing attendance for: $prevMonthName");

// Run the main process
try {
    $result = sendMonthlyAttendanceEmails($prevMonth, $prevYear, $daysInMonth, $prevMonthName);
    logCron("Process completed. Employees: {$result['employeesSent']}, Admin Master Report: " . ($result['adminSent'] ? 'Yes' : 'No'));
    logCron("Individual PDFs to Master Admins: {$result['masterAdminEmailsSent']} batches sent");
} catch (Exception $e) {
    logCron("ERROR: " . $e->getMessage());
}

logCron("=== HRMS Monthly Attendance Email Cron Finished ===\n");

/**
 * Main function to send monthly attendance emails
 */
function sendMonthlyAttendanceEmails($month, $year, $daysInMonth, $monthName)
{
    global $DB;

    $result = [
        'employeesSent' => 0,
        'employeesFailed' => 0,
        'adminSent' => false,
        'masterAdminEmailsSent' => 0,
        'masterAdminEmailsFailed' => 0,
        'errors' => []
    ];

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));

    // Get all active employees (include those without email for PDF generation)
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT userID, userName, displayName, userEmail, employeeCode, department, designation,
                       workStartTime, workEndTime, saturdayStartTime, saturdayEndTime
                FROM `" . $DB->pre . "x_admin_user`
                WHERE status = ?
                ORDER BY displayName";
    $employees = $DB->dbRows();

    logCron("Found " . count($employees) . " active employees");

    // Get holidays for the month
    $DB->vals = array($startDate, $endDate, 1);
    $DB->types = "ssi";
    $DB->sql = "SELECT ahDate, ahReason FROM " . $DB->pre . "attendance_holidays WHERE ahDate BETWEEN ? AND ? AND status=?";
    $holidayRows = $DB->dbRows();
    $holidays = [];
    foreach ($holidayRows as $h) {
        $holidays[$h['ahDate']] = $h['ahReason'];
    }

    // Create upload directory for individual PDFs
    $uploadDir = ROOTPATH . '/uploads/attendance-reports/' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Collect all employee data for master report
    $masterData = [];
    // Collect generated PDFs for master admin email
    $generatedPDFs = [];

    // 1. Process each employee - send individual email AND generate PDF
    foreach ($employees as $emp) {
        $userID = $emp['userID'];
        $email = trim($emp['userEmail'] ?? '');

        // Get detailed attendance data for this employee
        $attendanceData = getDetailedEmployeeAttendanceData($userID, $month, $year, $daysInMonth, $holidays, $emp);

        // Skip employees with no attendance records
        if ($attendanceData['summary']['totalRecords'] == 0 && $attendanceData['summary']['present'] == 0) {
            logCron("Skipping {$emp['displayName']} - no attendance records");
            continue;
        }

        // Store for master report
        $masterData[] = array_merge($emp, ['attendance' => $attendanceData]);

        // Generate individual PDF for master admin email
        $pdfResult = generateIndividualDetailedPDF($emp, $attendanceData, $monthName, $month, $year, $uploadDir);
        if ($pdfResult['success']) {
            $generatedPDFs[] = [
                'employee' => $emp['displayName'],
                'filepath' => $pdfResult['filepath'],
                'filename' => $pdfResult['filename']
            ];
            logCron("✓ PDF generated for {$emp['displayName']}");
        } else {
            logCron("✗ PDF failed for {$emp['displayName']}: {$pdfResult['error']}");
        }

        // Send individual email to employee (if they have valid email)
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailResult = sendEmployeeAttendanceEmail($emp, $attendanceData, $monthName, $month, $year);

            if ($emailResult['success']) {
                $result['employeesSent']++;
                logCron("✓ Email sent to {$emp['displayName']} ({$email})");
            } else {
                $result['employeesFailed']++;
                $result['errors'][] = "{$emp['displayName']}: {$emailResult['error']}";
                logCron("✗ Email failed for {$emp['displayName']}: {$emailResult['error']}");
            }

            // Small delay to avoid rate limiting
            usleep(200000); // 200ms
        }
    }

    // 2. Generate and send master attendance report to HR Admin
    if (!empty($masterData)) {
        $adminResult = sendAdminMasterReport($masterData, $monthName, $month, $year, $daysInMonth);
        $result['adminSent'] = $adminResult['success'];
        if (!$adminResult['success']) {
            $result['errors'][] = "Admin report: {$adminResult['error']}";
        }
    }

    // 3. Send individual PDFs to master admins in batches
    if (!empty($generatedPDFs)) {
        logCron("Sending " . count($generatedPDFs) . " individual PDFs to master admins...");
        $batches = array_chunk($generatedPDFs, MAX_ATTACHMENTS_PER_EMAIL);
        $batchNum = 1;
        $totalBatches = count($batches);

        foreach ($batches as $batch) {
            $batchResult = sendMasterAdminBatchEmail($batch, $monthName, $batchNum, $totalBatches);

            if ($batchResult['success']) {
                $result['masterAdminEmailsSent']++;
                logCron("✓ Master admin batch $batchNum/$totalBatches sent successfully");
            } else {
                $result['masterAdminEmailsFailed']++;
                $result['errors'][] = "Master admin batch $batchNum: {$batchResult['error']}";
                logCron("✗ Master admin batch $batchNum failed: {$batchResult['error']}");
            }

            $batchNum++;
            usleep(500000); // 500ms delay between batches
        }
    }

    return $result;
}

/**
 * Get attendance data for an employee
 */
function getEmployeeAttendanceData($userID, $month, $year, $daysInMonth, $holidays)
{
    global $DB;

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));

    // Get attendance records
    $DB->vals = array($userID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT attendanceDate, attendanceStatus, isLate, isEarlyCheckout,
                       checkIn, checkOut, workingHours, lateMinutes, earlyMinutes
                FROM " . $DB->pre . "attendance
                WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=?
                ORDER BY attendanceDate";
    $attRows = $DB->dbRows();

    $attLookup = [];
    foreach ($attRows as $att) {
        $attLookup[$att['attendanceDate']] = $att;
    }

    // Build day-by-day data
    $days = [];
    $summary = [
        'totalRecords' => count($attRows),
        'present' => 0,
        'absent' => 0,
        'leave' => 0,
        'halfDay' => 0,
        'late' => 0,
        'earlyCheckout' => 0,
        'holiday' => 0,
        'weeklyOff' => 0,
        'totalHours' => 0,
        'totalLateMinutes' => 0,
        'totalEarlyMinutes' => 0
    ];

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dayOfWeek = date('w', strtotime($dateStr)); // 0 = Sunday
        $dayName = date('D', strtotime($dateStr));

        $dayData = [
            'date' => $d,
            'day' => $dayName,
            'fullDate' => $dateStr,
            'status' => '-',
            'checkIn' => '-',
            'checkOut' => '-',
            'workingHours' => '-',
            'remarks' => ''
        ];

        if ($dayOfWeek == 0) {
            // Sunday - Weekly Off
            $dayData['status'] = 'WO';
            $dayData['remarks'] = 'Weekly Off';
            $summary['weeklyOff']++;
        } elseif (in_array($dateStr, $holidays)) {
            // Holiday
            $dayData['status'] = 'H';
            $dayData['remarks'] = 'Holiday';
            $summary['holiday']++;
        } elseif (isset($attLookup[$dateStr])) {
            // Has attendance record
            $att = $attLookup[$dateStr];

            switch ($att['attendanceStatus']) {
                case 'present':
                    $dayData['status'] = $att['isLate'] ? 'LT' : 'P';
                    $summary['present']++;
                    if ($att['isLate']) {
                        $summary['late']++;
                        $summary['totalLateMinutes'] += intval($att['lateMinutes'] ?? 0);
                    }
                    if ($att['isEarlyCheckout']) {
                        $summary['earlyCheckout']++;
                        $summary['totalEarlyMinutes'] += intval($att['earlyMinutes'] ?? 0);
                    }
                    break;
                case 'absent':
                    $dayData['status'] = 'A';
                    $summary['absent']++;
                    break;
                case 'leave':
                    $dayData['status'] = 'L';
                    $summary['leave']++;
                    break;
                case 'half_day':
                    $dayData['status'] = 'HD';
                    $summary['halfDay']++;
                    break;
                default:
                    $dayData['status'] = 'P';
                    $summary['present']++;
            }

            $dayData['checkIn'] = $att['checkIn'] ? date('h:i A', strtotime($att['checkIn'])) : '-';
            $dayData['checkOut'] = $att['checkOut'] ? date('h:i A', strtotime($att['checkOut'])) : '-';
            $dayData['workingHours'] = $att['workingHours'] ? number_format($att['workingHours'], 1) . ' hrs' : '-';
            $summary['totalHours'] += floatval($att['workingHours'] ?? 0);

            // Add late/early remarks
            if ($att['isLate'] && $att['lateMinutes'] > 0) {
                $dayData['remarks'] = "Late by {$att['lateMinutes']} min";
            }
            if ($att['isEarlyCheckout'] && $att['earlyMinutes'] > 0) {
                $dayData['remarks'] .= ($dayData['remarks'] ? ', ' : '') . "Left early by {$att['earlyMinutes']} min";
            }
        } else {
            // No record - mark as absent (if past date)
            if (strtotime($dateStr) < strtotime(date('Y-m-d'))) {
                $dayData['status'] = 'A';
                $summary['absent']++;
            }
        }

        $days[$d] = $dayData;
    }

    // Calculate working days
    $summary['workingDays'] = $daysInMonth - $summary['weeklyOff'] - $summary['holiday'];
    $summary['totalHours'] = round($summary['totalHours'], 1);

    return [
        'days' => $days,
        'summary' => $summary
    ];
}

/**
 * Send attendance email to individual employee
 */
function sendEmployeeAttendanceEmail($employee, $attendanceData, $monthName, $month, $year)
{
    global $DB;

    $brevo = getBrevoService();
    if (!$brevo || !$brevo->isConfigured()) {
        return ['success' => false, 'error' => 'Email service not configured'];
    }

    // Build email HTML
    $html = buildEmployeeAttendanceEmailHTML($employee, $attendanceData, $monthName);

    $subject = "Your Attendance Report - $monthName";

    $emailParams = [
        'to' => [['email' => $employee['userEmail'], 'name' => $employee['displayName']]],
        'subject' => $subject,
        'htmlContent' => $html,
        'tags' => ['hrms', 'attendance-report', 'monthly']
    ];

    $result = $brevo->sendEmail($emailParams);

    // Log to database
    logEmailToDatabase('attendance_summary', $employee['userID'], $month, $year,
                       $employee['userEmail'], $subject, '',
                       $result['success'] ? 'sent' : 'failed',
                       $result['success'] ? null : ($result['error'] ?? 'Unknown error'));

    return $result;
}

/**
 * Build HTML email content for employee attendance report
 */
function buildEmployeeAttendanceEmailHTML($employee, $attendanceData, $monthName)
{
    $summary = $attendanceData['summary'];
    $days = $attendanceData['days'];

    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); padding: 30px; border-radius: 8px 8px 0 0;">
                            <table width="100%">
                                <tr>
                                    <td>
                                        <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Bombay Engineering Syndicate</h1>
                                        <p style="color: #a8c5e2; margin: 5px 0 0 0; font-size: 14px;">Monthly Attendance Report</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Employee Info -->
                    <tr>
                        <td style="padding: 25px 30px 15px 30px;">
                            <p style="margin: 0; color: #666; font-size: 14px;">Hello,</p>
                            <h2 style="margin: 10px 0; color: #1e3a5f; font-size: 20px;">' . htmlspecialchars($employee['displayName']) . '</h2>
                            <p style="margin: 0; color: #888; font-size: 13px;">
                                ' . ($employee['employeeCode'] ? "Emp Code: {$employee['employeeCode']} | " : '') . '
                                ' . ($employee['department'] ? "{$employee['department']}" : '') . '
                                ' . ($employee['designation'] ? " - {$employee['designation']}" : '') . '
                            </p>
                            <p style="margin: 15px 0 0 0; color: #333; font-size: 14px;">
                                Here is your attendance summary for <strong>' . $monthName . '</strong>:
                            </p>
                        </td>
                    </tr>

                    <!-- Summary Cards -->
                    <tr>
                        <td style="padding: 0 30px 20px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="25%" style="padding: 10px;">
                                        <div style="background: #e8f5e9; border-radius: 8px; padding: 15px; text-align: center;">
                                            <div style="font-size: 28px; font-weight: bold; color: #2e7d32;">' . $summary['present'] . '</div>
                                            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Present</div>
                                        </div>
                                    </td>
                                    <td width="25%" style="padding: 10px;">
                                        <div style="background: #ffebee; border-radius: 8px; padding: 15px; text-align: center;">
                                            <div style="font-size: 28px; font-weight: bold; color: #c62828;">' . $summary['absent'] . '</div>
                                            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Absent</div>
                                        </div>
                                    </td>
                                    <td width="25%" style="padding: 10px;">
                                        <div style="background: #e3f2fd; border-radius: 8px; padding: 15px; text-align: center;">
                                            <div style="font-size: 28px; font-weight: bold; color: #1565c0;">' . $summary['leave'] . '</div>
                                            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Leave</div>
                                        </div>
                                    </td>
                                    <td width="25%" style="padding: 10px;">
                                        <div style="background: #fff3e0; border-radius: 8px; padding: 15px; text-align: center;">
                                            <div style="font-size: 28px; font-weight: bold; color: #e65100;">' . $summary['late'] . '</div>
                                            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Late</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Additional Summary -->
                    <tr>
                        <td style="padding: 0 30px 20px 30px;">
                            <table width="100%" cellpadding="8" cellspacing="0" style="background: #fafafa; border-radius: 8px;">
                                <tr>
                                    <td style="border-bottom: 1px solid #eee; color: #666; font-size: 13px;">Working Days</td>
                                    <td style="border-bottom: 1px solid #eee; text-align: right; font-weight: bold; color: #333;">' . $summary['workingDays'] . '</td>
                                    <td style="border-bottom: 1px solid #eee; color: #666; font-size: 13px;">Half Days</td>
                                    <td style="border-bottom: 1px solid #eee; text-align: right; font-weight: bold; color: #333;">' . $summary['halfDay'] . '</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #eee; color: #666; font-size: 13px;">Weekly Off</td>
                                    <td style="border-bottom: 1px solid #eee; text-align: right; font-weight: bold; color: #333;">' . $summary['weeklyOff'] . '</td>
                                    <td style="border-bottom: 1px solid #eee; color: #666; font-size: 13px;">Holidays</td>
                                    <td style="border-bottom: 1px solid #eee; text-align: right; font-weight: bold; color: #333;">' . $summary['holiday'] . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 13px;">Total Hours Worked</td>
                                    <td style="text-align: right; font-weight: bold; color: #1565c0;">' . $summary['totalHours'] . ' hrs</td>
                                    <td style="color: #666; font-size: 13px;">Early Checkouts</td>
                                    <td style="text-align: right; font-weight: bold; color: #333;">' . $summary['earlyCheckout'] . '</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Detailed Day-wise Table -->
                    <tr>
                        <td style="padding: 0 30px 20px 30px;">
                            <h3 style="margin: 0 0 15px 0; color: #1e3a5f; font-size: 16px;">Day-wise Attendance</h3>
                            <table width="100%" cellpadding="8" cellspacing="0" style="border: 1px solid #e0e0e0; border-radius: 8px; border-collapse: collapse; font-size: 12px;">
                                <thead>
                                    <tr style="background: #1e3a5f;">
                                        <th style="color: #fff; text-align: left; padding: 10px 8px; border: 1px solid #e0e0e0;">Date</th>
                                        <th style="color: #fff; text-align: left; padding: 10px 8px; border: 1px solid #e0e0e0;">Day</th>
                                        <th style="color: #fff; text-align: center; padding: 10px 8px; border: 1px solid #e0e0e0;">Status</th>
                                        <th style="color: #fff; text-align: center; padding: 10px 8px; border: 1px solid #e0e0e0;">Check In</th>
                                        <th style="color: #fff; text-align: center; padding: 10px 8px; border: 1px solid #e0e0e0;">Check Out</th>
                                        <th style="color: #fff; text-align: center; padding: 10px 8px; border: 1px solid #e0e0e0;">Hours</th>
                                    </tr>
                                </thead>
                                <tbody>';

    foreach ($days as $day) {
        $statusColor = getStatusColor($day['status']);
        $rowBg = ($day['status'] == 'WO' || $day['status'] == 'H') ? '#f9f9f9' : '#fff';

        $html .= '
                                    <tr style="background: ' . $rowBg . ';">
                                        <td style="padding: 8px; border: 1px solid #e0e0e0;">' . $day['date'] . '</td>
                                        <td style="padding: 8px; border: 1px solid #e0e0e0;">' . $day['day'] . '</td>
                                        <td style="padding: 8px; border: 1px solid #e0e0e0; text-align: center;">
                                            <span style="display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; background: ' . $statusColor['bg'] . '; color: ' . $statusColor['text'] . ';">' . $day['status'] . '</span>
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #e0e0e0; text-align: center;">' . $day['checkIn'] . '</td>
                                        <td style="padding: 8px; border: 1px solid #e0e0e0; text-align: center;">' . $day['checkOut'] . '</td>
                                        <td style="padding: 8px; border: 1px solid #e0e0e0; text-align: center;">' . $day['workingHours'] . '</td>
                                    </tr>';
    }

    $html .= '
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <!-- Legend -->
                    <tr>
                        <td style="padding: 0 30px 20px 30px;">
                            <p style="margin: 0; font-size: 11px; color: #888;">
                                <strong>Legend:</strong> P = Present, A = Absent, L = Leave, HD = Half Day, LT = Late, WO = Weekly Off, H = Holiday
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: #f5f5f5; padding: 20px 30px; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="margin: 0; color: #888; font-size: 12px;">
                                This is an automated email from the HRMS system.<br>
                                For any queries, please contact HR department.
                            </p>
                            <p style="margin: 10px 0 0 0; color: #aaa; font-size: 11px;">
                                © ' . date('Y') . ' Bombay Engineering Syndicate. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

    return $html;
}

/**
 * Get status color for email display
 */
function getStatusColor($status)
{
    $colors = [
        'P'  => ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
        'A'  => ['bg' => '#ffebee', 'text' => '#c62828'],
        'L'  => ['bg' => '#e3f2fd', 'text' => '#1565c0'],
        'HD' => ['bg' => '#fff3e0', 'text' => '#e65100'],
        'LT' => ['bg' => '#fff8e1', 'text' => '#f57c00'],
        'WO' => ['bg' => '#f5f5f5', 'text' => '#757575'],
        'H'  => ['bg' => '#fce4ec', 'text' => '#ad1457'],
        '-'  => ['bg' => '#fafafa', 'text' => '#bdbdbd']
    ];

    return $colors[$status] ?? ['bg' => '#fafafa', 'text' => '#666666'];
}

/**
 * Send master attendance report to HR Admin
 */
function sendAdminMasterReport($masterData, $monthName, $month, $year, $daysInMonth)
{
    global $DB;

    $brevo = getBrevoService();
    if (!$brevo || !$brevo->isConfigured()) {
        return ['success' => false, 'error' => 'Email service not configured'];
    }

    // Get HR Admin recipients
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT recipientEmail, recipientName FROM " . $DB->pre . "hr_email_recipients
                WHERE status = ? AND FIND_IN_SET('hr_master', emailTypes)";
    $recipients = $DB->dbRows();

    // Also get users with isHRAdmin = 1
    $DB->vals = array(1, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT userEmail, displayName FROM " . $DB->pre . "x_admin_user
                WHERE status = ? AND isHRAdmin = ? AND userEmail IS NOT NULL AND userEmail != ''";
    $hrAdmins = $DB->dbRows();

    // Merge recipients
    $allRecipients = [];
    foreach ($recipients as $r) {
        $allRecipients[$r['recipientEmail']] = $r['recipientName'];
    }
    foreach ($hrAdmins as $r) {
        $allRecipients[$r['userEmail']] = $r['displayName'];
    }

    if (empty($allRecipients)) {
        logCron("No HR Admin recipients found for master report");
        return ['success' => false, 'error' => 'No HR Admin recipients configured'];
    }

    // Generate PDF report
    $pdfResult = generateMasterAttendancePDF($masterData, $monthName, $month, $year, $daysInMonth);

    if (!$pdfResult['success']) {
        return ['success' => false, 'error' => 'Failed to generate PDF: ' . $pdfResult['error']];
    }

    // Generate Excel report
    $excelResult = generateMasterAttendanceExcel($masterData, $monthName, $month, $year, $daysInMonth);

    // Build email
    $html = buildAdminMasterEmailHTML($masterData, $monthName);
    $subject = "Master Attendance Report - $monthName";

    // Prepare attachments
    $attachments = [];

    if ($pdfResult['success'] && file_exists($pdfResult['filepath'])) {
        $attachments[] = [
            'content' => base64_encode(file_get_contents($pdfResult['filepath'])),
            'name' => $pdfResult['filename']
        ];
    }

    if ($excelResult['success'] && file_exists($excelResult['filepath'])) {
        $attachments[] = [
            'content' => base64_encode(file_get_contents($excelResult['filepath'])),
            'name' => $excelResult['filename']
        ];
    }

    // Send to all recipients
    $toList = [];
    foreach ($allRecipients as $email => $name) {
        $toList[] = ['email' => $email, 'name' => $name];
    }

    $emailParams = [
        'to' => $toList,
        'subject' => $subject,
        'htmlContent' => $html,
        'tags' => ['hrms', 'master-report', 'monthly'],
        'attachment' => $attachments
    ];

    $result = $brevo->sendEmail($emailParams);

    // Log to database
    foreach ($allRecipients as $email => $name) {
        logEmailToDatabase('hr_master', null, $month, $year,
                           $email, $subject, $pdfResult['filepath'] ?? '',
                           $result['success'] ? 'sent' : 'failed',
                           $result['success'] ? null : ($result['error'] ?? 'Unknown error'));
    }

    // Log success
    if ($result['success']) {
        logCron("✓ Master report sent to " . count($allRecipients) . " HR Admin(s)");
    }

    return $result;
}

/**
 * Generate master attendance PDF report
 */
function generateMasterAttendancePDF($masterData, $monthName, $month, $year, $daysInMonth)
{
    try {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L', // Landscape for more columns
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 15,
            'margin_bottom' => 15
        ]);

        $mpdf->SetTitle("Master Attendance Report - $monthName");
        $mpdf->SetAuthor("Bombay Engineering Syndicate HRMS");

        // Build HTML
        $html = '
        <style>
            body { font-family: Arial, sans-serif; font-size: 9px; }
            .header { text-align: center; margin-bottom: 15px; }
            .header h1 { color: #1e3a5f; margin: 0; font-size: 18px; }
            .header p { color: #666; margin: 5px 0 0 0; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th { background: #1e3a5f; color: #fff; padding: 6px 4px; text-align: center; font-size: 8px; }
            td { padding: 5px 3px; border: 1px solid #ddd; text-align: center; font-size: 8px; }
            .emp-name { text-align: left; font-weight: bold; white-space: nowrap; }
            .status-p { background: #e8f5e9; color: #2e7d32; }
            .status-a { background: #ffebee; color: #c62828; }
            .status-l { background: #e3f2fd; color: #1565c0; }
            .status-lt { background: #fff8e1; color: #f57c00; }
            .status-hd { background: #fff3e0; color: #e65100; }
            .status-wo { background: #f5f5f5; color: #757575; }
            .status-h { background: #fce4ec; color: #ad1457; }
            .summary-row { background: #f9f9f9; font-weight: bold; }
            .footer { text-align: center; font-size: 10px; color: #888; margin-top: 20px; }
        </style>

        <div class="header">
            <h1>Bombay Engineering Syndicate</h1>
            <p>Master Attendance Report - ' . $monthName . '</p>
            <p style="font-size: 10px; color: #888;">Generated on ' . date('d M Y, h:i A') . '</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 20px;">#</th>
                    <th style="width: 100px; text-align: left;">Employee</th>';

        // Day headers
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $html .= '<th>' . $d . '</th>';
        }

        $html .= '
                    <th>P</th>
                    <th>A</th>
                    <th>L</th>
                    <th>LT</th>
                    <th>HD</th>
                    <th>Hrs</th>
                </tr>
            </thead>
            <tbody>';

        $totalPresent = 0;
        $totalAbsent = 0;
        $totalLeave = 0;
        $totalLate = 0;
        $totalHalfDay = 0;
        $totalHours = 0;

        foreach ($masterData as $idx => $emp) {
            $att = $emp['attendance'];
            $summary = $att['summary'];

            $html .= '<tr>
                <td>' . ($idx + 1) . '</td>
                <td class="emp-name">' . htmlspecialchars($emp['displayName']) . '</td>';

            // Day-wise status
            foreach ($att['days'] as $day) {
                $statusClass = 'status-' . strtolower($day['status']);
                $html .= '<td class="' . $statusClass . '">' . $day['status'] . '</td>';
            }

            // Summary columns
            $html .= '
                <td class="status-p">' . $summary['present'] . '</td>
                <td class="status-a">' . $summary['absent'] . '</td>
                <td class="status-l">' . $summary['leave'] . '</td>
                <td class="status-lt">' . $summary['late'] . '</td>
                <td class="status-hd">' . $summary['halfDay'] . '</td>
                <td>' . $summary['totalHours'] . '</td>
            </tr>';

            $totalPresent += $summary['present'];
            $totalAbsent += $summary['absent'];
            $totalLeave += $summary['leave'];
            $totalLate += $summary['late'];
            $totalHalfDay += $summary['halfDay'];
            $totalHours += $summary['totalHours'];
        }

        // Totals row
        $html .= '
            <tr class="summary-row">
                <td colspan="2" style="text-align: right;">TOTALS:</td>';
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $html .= '<td></td>';
        }
        $html .= '
                <td>' . $totalPresent . '</td>
                <td>' . $totalAbsent . '</td>
                <td>' . $totalLeave . '</td>
                <td>' . $totalLate . '</td>
                <td>' . $totalHalfDay . '</td>
                <td>' . round($totalHours, 1) . '</td>
            </tr>';

        $html .= '
            </tbody>
        </table>

        <p style="font-size: 9px; color: #666;">
            <strong>Legend:</strong> P = Present, A = Absent, L = Leave, HD = Half Day, LT = Late, WO = Weekly Off, H = Holiday
        </p>

        <div class="footer">
            <p>This report is auto-generated by the HRMS system. Total Employees: ' . count($masterData) . '</p>
        </div>';

        $mpdf->WriteHTML($html);

        // Save file
        $uploadDir = ROOTPATH . '/uploads/attendance-reports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = "Master_Attendance_{$year}_{$month}.pdf";
        $filepath = $uploadDir . $filename;

        $mpdf->Output($filepath, 'F');

        return [
            'success' => true,
            'filepath' => $filepath,
            'filename' => $filename
        ];

    } catch (Exception $e) {
        logCron("PDF Generation Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Generate master attendance Excel report
 */
function generateMasterAttendanceExcel($masterData, $monthName, $month, $year, $daysInMonth)
{
    try {
        $uploadDir = ROOTPATH . '/uploads/attendance-reports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = "Master_Attendance_{$year}_{$month}.csv";
        $filepath = $uploadDir . $filename;

        $fp = fopen($filepath, 'w');

        // Header row
        $header = ['#', 'Employee Code', 'Employee Name', 'Department', 'Designation'];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $header[] = $d;
        }
        $header = array_merge($header, ['Present', 'Absent', 'Leave', 'Late', 'Half Day', 'Weekly Off', 'Holiday', 'Total Hours']);

        fputcsv($fp, $header);

        // Data rows
        foreach ($masterData as $idx => $emp) {
            $att = $emp['attendance'];
            $summary = $att['summary'];

            $row = [
                $idx + 1,
                $emp['employeeCode'] ?? '',
                $emp['displayName'],
                $emp['department'] ?? '',
                $emp['designation'] ?? ''
            ];

            // Day-wise status
            foreach ($att['days'] as $day) {
                $row[] = $day['status'];
            }

            // Summary
            $row[] = $summary['present'];
            $row[] = $summary['absent'];
            $row[] = $summary['leave'];
            $row[] = $summary['late'];
            $row[] = $summary['halfDay'];
            $row[] = $summary['weeklyOff'];
            $row[] = $summary['holiday'];
            $row[] = $summary['totalHours'];

            fputcsv($fp, $row);
        }

        fclose($fp);

        return [
            'success' => true,
            'filepath' => $filepath,
            'filename' => $filename
        ];

    } catch (Exception $e) {
        logCron("Excel Generation Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Build admin master report email HTML
 */
function buildAdminMasterEmailHTML($masterData, $monthName)
{
    // Calculate totals
    $totalEmployees = count($masterData);
    $totalPresent = 0;
    $totalAbsent = 0;
    $totalLeave = 0;
    $totalLate = 0;

    foreach ($masterData as $emp) {
        $summary = $emp['attendance']['summary'];
        $totalPresent += $summary['present'];
        $totalAbsent += $summary['absent'];
        $totalLeave += $summary['leave'];
        $totalLate += $summary['late'];
    }

    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Master Attendance Report</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); padding: 30px; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Master Attendance Report</h1>
                            <p style="color: #a8c5e2; margin: 5px 0 0 0; font-size: 14px;">' . $monthName . '</p>
                        </td>
                    </tr>

                    <!-- Summary -->
                    <tr>
                        <td style="padding: 25px 30px;">
                            <p style="margin: 0 0 20px 0; color: #333; font-size: 14px;">
                                Please find attached the master attendance report for <strong>' . $monthName . '</strong>.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="25%" style="padding: 10px;">
                                        <div style="background: #e3f2fd; border-radius: 8px; padding: 15px; text-align: center;">
                                            <div style="font-size: 28px; font-weight: bold; color: #1565c0;">' . $totalEmployees . '</div>
                                            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Employees</div>
                                        </div>
                                    </td>
                                    <td width="25%" style="padding: 10px;">
                                        <div style="background: #e8f5e9; border-radius: 8px; padding: 15px; text-align: center;">
                                            <div style="font-size: 28px; font-weight: bold; color: #2e7d32;">' . $totalPresent . '</div>
                                            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Total Present</div>
                                        </div>
                                    </td>
                                    <td width="25%" style="padding: 10px;">
                                        <div style="background: #ffebee; border-radius: 8px; padding: 15px; text-align: center;">
                                            <div style="font-size: 28px; font-weight: bold; color: #c62828;">' . $totalAbsent . '</div>
                                            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Total Absent</div>
                                        </div>
                                    </td>
                                    <td width="25%" style="padding: 10px;">
                                        <div style="background: #fff3e0; border-radius: 8px; padding: 15px; text-align: center;">
                                            <div style="font-size: 28px; font-weight: bold; color: #e65100;">' . $totalLate . '</div>
                                            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Total Late</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Attachments Note -->
                    <tr>
                        <td style="padding: 0 30px 25px 30px;">
                            <div style="background: #f5f5f5; border-radius: 8px; padding: 15px;">
                                <p style="margin: 0; font-size: 13px; color: #666;">
                                    <strong>📎 Attachments:</strong><br>
                                    • Master_Attendance_' . date('Y_m', strtotime($monthName)) . '.pdf - Detailed PDF Report<br>
                                    • Master_Attendance_' . date('Y_m', strtotime($monthName)) . '.csv - Excel/CSV Data
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: #f5f5f5; padding: 20px 30px; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="margin: 0; color: #888; font-size: 12px;">
                                This is an automated email from the HRMS system.<br>
                                Generated on ' . date('d M Y, h:i A') . '
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

    return $html;
}

/**
 * Log email to database
 */
function logEmailToDatabase($emailType, $userID, $month, $year, $recipientEmail, $subject, $attachmentPath, $status, $error = null)
{
    global $DB;

    $DB->table = $DB->pre . "hr_email_log";
    $DB->data = array(
        'emailType' => $emailType,
        'userID' => $userID,
        'salaryMonth' => $month,
        'salaryYear' => $year,
        'recipientEmail' => $recipientEmail,
        'emailSubject' => $subject,
        'attachmentPath' => $attachmentPath,
        'emailStatus' => $status,
        'errorMessage' => $error,
        'sentAt' => date('Y-m-d H:i:s')
    );
    $DB->dbInsert();
}

/**
 * Log cron activity
 */
function logCron($message)
{
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";

    // Also log to file
    $logFile = ROOTPATH . '/logs/hrms-cron.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

/**
 * Get detailed attendance data for an employee (for PDF generation)
 */
function getDetailedEmployeeAttendanceData($userID, $month, $year, $daysInMonth, $holidays, $employee)
{
    global $DB;

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));

    // Get attendance records
    $DB->vals = array($userID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT attendanceDate, attendanceStatus, isLate, isEarlyCheckout,
                       checkIn, checkOut, workingHours, lateMinutes, earlyMinutes, remarks
                FROM " . $DB->pre . "attendance
                WHERE userID=? AND attendanceDate BETWEEN ? AND ? AND status=?
                ORDER BY attendanceDate";
    $attRows = $DB->dbRows();

    $attLookup = [];
    foreach ($attRows as $att) {
        $attLookup[$att['attendanceDate']] = $att;
    }

    // Get leave records for this period
    $DB->vals = array($userID, $startDate, $endDate, 'Approved');
    $DB->types = "isss";
    $DB->sql = "SELECT leaveDate, leaveType, isHalfDay, halfDayPeriod
                FROM " . $DB->pre . "employee_leave
                WHERE userID=? AND leaveDate BETWEEN ? AND ? AND leaveStatus=?";
    $leaveRows = $DB->dbRows();

    $leaveLookup = [];
    foreach ($leaveRows as $lv) {
        $leaveLookup[$lv['leaveDate']] = $lv;
    }

    // Employee scheduled times
    $scheduledIn = $employee['workStartTime'] ?: '09:00:00';
    $scheduledOut = $employee['workEndTime'] ?: '18:00:00';
    $satScheduledIn = $employee['saturdayStartTime'] ?: $scheduledIn;
    $satScheduledOut = $employee['saturdayEndTime'] ?: '16:00:00';

    // Build day-by-day data
    $days = [];
    $summary = [
        'totalRecords' => count($attRows),
        'present' => 0,
        'absent' => 0,
        'leave' => 0,
        'halfDay' => 0,
        'late' => 0,
        'earlyCheckout' => 0,
        'holiday' => 0,
        'weeklyOff' => 0,
        'totalHours' => 0,
        'totalLateMinutes' => 0,
        'totalEarlyMinutes' => 0,
        'workingDays' => 0
    ];

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dayOfWeek = date('w', strtotime($dateStr)); // 0 = Sunday, 6 = Saturday
        $dayName = date('D', strtotime($dateStr));
        $isSaturday = ($dayOfWeek == 6);

        // Determine scheduled times for this day
        $dayScheduledIn = $isSaturday ? $satScheduledIn : $scheduledIn;
        $dayScheduledOut = $isSaturday ? $satScheduledOut : $scheduledOut;

        $dayData = [
            'date' => $d,
            'day' => $dayName,
            'fullDate' => $dateStr,
            'status' => '-',
            'checkIn' => '-',
            'checkOut' => '-',
            'workingHours' => '-',
            'lateMinutes' => 0,
            'earlyMinutes' => 0,
            'remarks' => '',
            'scheduledIn' => date('h:i A', strtotime($dayScheduledIn)),
            'scheduledOut' => date('h:i A', strtotime($dayScheduledOut))
        ];

        if ($dayOfWeek == 0) {
            // Sunday - Weekly Off
            $dayData['status'] = 'WO';
            $dayData['remarks'] = 'Weekly Off';
            $summary['weeklyOff']++;
        } elseif (isset($holidays[$dateStr])) {
            // Holiday
            $dayData['status'] = 'H';
            $dayData['remarks'] = $holidays[$dateStr];
            $summary['holiday']++;
        } elseif (isset($leaveLookup[$dateStr])) {
            // On Leave
            $leave = $leaveLookup[$dateStr];
            if ($leave['isHalfDay']) {
                $dayData['status'] = 'HD';
                $dayData['remarks'] = $leave['leaveType'] . ' (Half Day - ' . $leave['halfDayPeriod'] . ')';
                $summary['halfDay']++;
            } else {
                $dayData['status'] = 'L';
                $dayData['remarks'] = $leave['leaveType'];
                $summary['leave']++;
            }
        } elseif (isset($attLookup[$dateStr])) {
            // Has attendance record
            $att = $attLookup[$dateStr];

            switch ($att['attendanceStatus']) {
                case 'present':
                    $dayData['status'] = $att['isLate'] ? 'LT' : 'P';
                    $summary['present']++;
                    if ($att['isLate']) {
                        $summary['late']++;
                        $summary['totalLateMinutes'] += intval($att['lateMinutes'] ?? 0);
                        $dayData['lateMinutes'] = intval($att['lateMinutes'] ?? 0);
                    }
                    if ($att['isEarlyCheckout']) {
                        $summary['earlyCheckout']++;
                        $summary['totalEarlyMinutes'] += intval($att['earlyMinutes'] ?? 0);
                        $dayData['earlyMinutes'] = intval($att['earlyMinutes'] ?? 0);
                    }
                    break;
                case 'absent':
                    $dayData['status'] = 'A';
                    $summary['absent']++;
                    break;
                case 'leave':
                    $dayData['status'] = 'L';
                    $summary['leave']++;
                    break;
                case 'half_day':
                    $dayData['status'] = 'HD';
                    $summary['halfDay']++;
                    break;
                default:
                    $dayData['status'] = 'P';
                    $summary['present']++;
            }

            $dayData['checkIn'] = $att['checkIn'] ? date('h:i A', strtotime($att['checkIn'])) : '-';
            $dayData['checkOut'] = $att['checkOut'] ? date('h:i A', strtotime($att['checkOut'])) : '-';
            $dayData['workingHours'] = $att['workingHours'] ? number_format($att['workingHours'], 1) . ' hrs' : '-';
            $summary['totalHours'] += floatval($att['workingHours'] ?? 0);

            // Build remarks
            $remarkParts = [];
            if ($att['isLate'] && $att['lateMinutes'] > 0) {
                $remarkParts[] = "Late by {$att['lateMinutes']} min";
            }
            if ($att['isEarlyCheckout'] && $att['earlyMinutes'] > 0) {
                $remarkParts[] = "Left early by {$att['earlyMinutes']} min";
            }
            if (!empty($att['remarks'])) {
                $remarkParts[] = $att['remarks'];
            }
            $dayData['remarks'] = implode(', ', $remarkParts);
        } else {
            // No record - mark as absent (if past date)
            if (strtotime($dateStr) < strtotime(date('Y-m-d'))) {
                $dayData['status'] = 'A';
                $summary['absent']++;
            }
        }

        $days[$d] = $dayData;
    }

    // Calculate working days
    $summary['workingDays'] = $daysInMonth - $summary['weeklyOff'] - $summary['holiday'];
    $summary['totalHours'] = round($summary['totalHours'], 1);

    return [
        'days' => $days,
        'summary' => $summary
    ];
}

/**
 * Generate individual detailed PDF report for an employee
 */
function generateIndividualDetailedPDF($employee, $attendanceData, $monthName, $month, $year, $uploadDir)
{
    try {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 10,
            'margin_bottom' => 10
        ]);

        $empName = $employee['displayName'];
        $mpdf->SetTitle("Detailed Attendance - $empName - $monthName");
        $mpdf->SetAuthor("Bombay Engineering Syndicate HRMS");

        $summary = $attendanceData['summary'];
        $days = $attendanceData['days'];

        // Beautiful HTML template
        $html = '
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }

            body {
                font-family: Arial, sans-serif;
                font-size: 9px;
                color: #1f2937;
                line-height: 1.4;
            }

            .report-header {
                background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
                color: white;
                padding: 16px 20px;
                margin-bottom: 0;
            }

            .header-table {
                width: 100%;
                border-collapse: collapse;
            }

            .header-table td {
                vertical-align: middle;
                padding: 0;
            }

            .company-logo {
                width: 45px;
                height: 45px;
                background: white;
                border-radius: 8px;
                padding: 4px;
            }

            .company-logo img {
                width: 100%;
                height: 100%;
            }

            .company-name {
                font-size: 15px;
                font-weight: 700;
                color: white;
            }

            .company-tagline {
                font-size: 8px;
                color: rgba(255,255,255,0.8);
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .report-type {
                font-size: 9px;
                background: rgba(255,255,255,0.2);
                padding: 4px 10px;
                border-radius: 20px;
                display: inline-block;
            }

            .report-date {
                font-size: 8px;
                color: rgba(255,255,255,0.9);
                margin-top: 4px;
            }

            .employee-bar {
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                padding: 12px 20px;
            }

            .employee-bar-table {
                width: 100%;
                border-collapse: collapse;
            }

            .employee-bar-table td {
                vertical-align: middle;
            }

            .employee-name {
                font-size: 14px;
                font-weight: 700;
                color: #1e40af;
            }

            .employee-details {
                font-size: 9px;
                color: #64748b;
            }

            .period-badge {
                background: linear-gradient(135deg, #dbeafe, #eff6ff);
                border: 1px solid #bfdbfe;
                padding: 6px 12px;
                border-radius: 6px;
                font-size: 10px;
                font-weight: 600;
                color: #1e40af;
            }

            .content {
                padding: 16px 20px;
            }

            .summary-cards-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 8px 0;
                margin-bottom: 16px;
            }

            .summary-card {
                background: linear-gradient(145deg, #ffffff, #f8fafc);
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 10px;
                text-align: center;
                width: 16.66%;
            }

            .card-present { border-top: 3px solid #22c55e; }
            .card-absent { border-top: 3px solid #ef4444; }
            .card-leave { border-top: 3px solid #2563eb; }
            .card-late { border-top: 3px solid #f59e0b; }
            .card-hours { border-top: 3px solid #06b6d4; }
            .card-working { border-top: 3px solid #8b5cf6; }

            .summary-value {
                font-size: 20px;
                font-weight: 700;
                line-height: 1;
            }

            .value-present { color: #16a34a; }
            .value-absent { color: #dc2626; }
            .value-leave { color: #2563eb; }
            .value-late { color: #d97706; }
            .value-hours { color: #0891b2; }
            .value-working { color: #7c3aed; }

            .summary-label {
                font-size: 7px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                color: #64748b;
                margin-top: 2px;
            }

            .data-table {
                width: 100%;
                border-collapse: collapse;
                border-radius: 8px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                font-size: 8px;
            }

            .data-table th {
                background: linear-gradient(180deg, #1e40af, #1d4ed8);
                color: white;
                padding: 8px 4px;
                text-align: center;
                font-size: 7px;
                font-weight: 600;
                text-transform: uppercase;
            }

            .data-table th.left { text-align: left; padding-left: 8px; }

            .data-table td {
                padding: 6px 4px;
                border-bottom: 1px solid #f1f5f9;
                text-align: center;
            }

            .data-table td.left { text-align: left; padding-left: 8px; }

            .data-table tbody tr:nth-child(even) {
                background: #f8fafc;
            }

            .status-badge {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 7px;
                font-weight: 700;
            }

            .status-P { background: #dcfce7; color: #166534; }
            .status-A { background: #fee2e2; color: #991b1b; }
            .status-L { background: #dbeafe; color: #1e40af; }
            .status-LT { background: #fef3c7; color: #92400e; }
            .status-H { background: #f3e8ff; color: #6b21a8; }
            .status-WO { background: #f1f5f9; color: #475569; }
            .status-HD { background: #fce7f3; color: #9d174d; }

            .late-badge {
                font-size: 6px;
                color: #dc2626;
                display: block;
            }

            .early-badge {
                font-size: 6px;
                color: #ea580c;
                display: block;
            }

            .legend {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 8px 12px;
                margin-top: 12px;
                font-size: 7px;
                color: #64748b;
            }

            .footer {
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                padding: 10px 20px;
                text-align: center;
                margin-top: 12px;
            }

            .footer-text {
                font-size: 7px;
                color: #94a3b8;
            }
        </style>

        <!-- Header -->
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 50px;">
                        <div class="company-logo">
                            <img src="' . SITEURL . '/xsite/images/logo.png" alt="BES">
                        </div>
                    </td>
                    <td style="padding-left: 12px;">
                        <div class="company-name">Bombay Engineering Syndicate</div>
                        <div class="company-tagline">Detailed Attendance Report</div>
                    </td>
                    <td style="text-align: right;">
                        <div class="report-type">Monthly Report</div>
                        <div class="report-date">Generated: ' . date('d M Y, h:i A') . '</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Employee Bar -->
        <div class="employee-bar">
            <table class="employee-bar-table">
                <tr>
                    <td>
                        <div class="employee-name">' . htmlspecialchars($empName) . '</div>
                        <div class="employee-details">
                            ' . ($employee['employeeCode'] ? "Code: {$employee['employeeCode']} | " : '') . '
                            ' . ($employee['department'] ? "{$employee['department']}" : '') . '
                            ' . ($employee['designation'] ? " - {$employee['designation']}" : '') . '
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <span class="period-badge">' . htmlspecialchars($monthName) . '</span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Summary Cards -->
            <table class="summary-cards-table">
                <tr>
                    <td class="summary-card card-present">
                        <div class="summary-value value-present">' . $summary['present'] . '</div>
                        <div class="summary-label">Present</div>
                    </td>
                    <td class="summary-card card-absent">
                        <div class="summary-value value-absent">' . $summary['absent'] . '</div>
                        <div class="summary-label">Absent</div>
                    </td>
                    <td class="summary-card card-leave">
                        <div class="summary-value value-leave">' . $summary['leave'] . '</div>
                        <div class="summary-label">Leave</div>
                    </td>
                    <td class="summary-card card-late">
                        <div class="summary-value value-late">' . $summary['late'] . '</div>
                        <div class="summary-label">Late</div>
                    </td>
                    <td class="summary-card card-hours">
                        <div class="summary-value value-hours">' . $summary['totalHours'] . '</div>
                        <div class="summary-label">Total Hours</div>
                    </td>
                    <td class="summary-card card-working">
                        <div class="summary-value value-working">' . $summary['workingDays'] . '</div>
                        <div class="summary-label">Working Days</div>
                    </td>
                </tr>
            </table>

            <!-- Detailed Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 30px;">Date</th>
                        <th style="width: 30px;">Day</th>
                        <th style="width: 40px;">Status</th>
                        <th style="width: 50px;">Scheduled In</th>
                        <th style="width: 50px;">Check In</th>
                        <th style="width: 50px;">Scheduled Out</th>
                        <th style="width: 50px;">Check Out</th>
                        <th style="width: 45px;">Hours</th>
                        <th class="left">Remarks</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($days as $day) {
            $statusClass = 'status-' . $day['status'];
            $rowStyle = ($day['status'] == 'WO' || $day['status'] == 'H') ? 'background: #fafafa;' : '';

            $html .= '
                    <tr style="' . $rowStyle . '">
                        <td>' . $day['date'] . '</td>
                        <td>' . $day['day'] . '</td>
                        <td><span class="status-badge ' . $statusClass . '">' . $day['status'] . '</span>';

            // Add late/early indicators
            if ($day['lateMinutes'] > 0) {
                $html .= '<span class="late-badge">+' . $day['lateMinutes'] . 'm late</span>';
            }
            if ($day['earlyMinutes'] > 0) {
                $html .= '<span class="early-badge">-' . $day['earlyMinutes'] . 'm early</span>';
            }

            $html .= '</td>
                        <td>' . $day['scheduledIn'] . '</td>
                        <td>' . $day['checkIn'] . '</td>
                        <td>' . $day['scheduledOut'] . '</td>
                        <td>' . $day['checkOut'] . '</td>
                        <td>' . $day['workingHours'] . '</td>
                        <td class="left">' . htmlspecialchars($day['remarks']) . '</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <!-- Legend -->
            <div class="legend">
                <strong>Legend:</strong> P = Present, A = Absent, L = Leave, HD = Half Day, LT = Late Arrival, WO = Weekly Off, H = Holiday |
                <strong>Late Minutes:</strong> ' . $summary['totalLateMinutes'] . ' min |
                <strong>Early Checkout:</strong> ' . $summary['totalEarlyMinutes'] . ' min |
                <strong>Half Days:</strong> ' . $summary['halfDay'] . ' |
                <strong>Weekly Offs:</strong> ' . $summary['weeklyOff'] . ' |
                <strong>Holidays:</strong> ' . $summary['holiday'] . '
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">This report is auto-generated by HRMS | Bombay Engineering Syndicate | ' . date('Y') . '</div>
        </div>';

        $mpdf->WriteHTML($html);

        // Generate filename (sanitize employee name)
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $empName);
        $filename = "{$safeName}_Attendance_{$year}_{$month}.pdf";
        $filepath = $uploadDir . $filename;

        $mpdf->Output($filepath, 'F');

        return [
            'success' => true,
            'filepath' => $filepath,
            'filename' => $filename
        ];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send batched individual PDFs to master admins
 */
function sendMasterAdminBatchEmail($pdfBatch, $monthName, $batchNum, $totalBatches)
{
    $brevo = getBrevoService();
    if (!$brevo || !$brevo->isConfigured()) {
        return ['success' => false, 'error' => 'Email service not configured'];
    }

    // Prepare attachments
    $attachments = [];
    $employeeNames = [];

    foreach ($pdfBatch as $pdf) {
        if (file_exists($pdf['filepath'])) {
            $attachments[] = [
                'content' => base64_encode(file_get_contents($pdf['filepath'])),
                'name' => $pdf['filename']
            ];
            $employeeNames[] = $pdf['employee'];
        }
    }

    if (empty($attachments)) {
        return ['success' => false, 'error' => 'No valid PDF files to attach'];
    }

    // Build subject
    $subject = "Individual Attendance Reports - $monthName";
    if ($totalBatches > 1) {
        $subject .= " (Batch $batchNum of $totalBatches)";
    }

    // Build HTML content
    $html = buildMasterAdminBatchEmailHTML($employeeNames, $monthName, $batchNum, $totalBatches, count($attachments));

    $emailParams = [
        'to' => MASTER_ADMIN_RECIPIENTS,
        'subject' => $subject,
        'htmlContent' => $html,
        'tags' => ['hrms', 'individual-reports', 'monthly'],
        'attachment' => $attachments
    ];

    return $brevo->sendEmail($emailParams);
}

/**
 * Build HTML email content for master admin batch report
 */
function buildMasterAdminBatchEmailHTML($employeeNames, $monthName, $batchNum, $totalBatches, $attachmentCount)
{
    $employeeList = '';
    foreach ($employeeNames as $idx => $name) {
        $employeeList .= '<tr><td style="padding: 6px 10px; border-bottom: 1px solid #eee; font-size: 13px;">' . ($idx + 1) . '. ' . htmlspecialchars($name) . '</td></tr>';
    }

    $batchInfo = '';
    if ($totalBatches > 1) {
        $batchInfo = " (Batch $batchNum of $totalBatches)";
    }

    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Individual Attendance Reports</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%); padding: 30px; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 22px;">Individual Attendance Reports</h1>
                            <p style="color: #93c5fd; margin: 8px 0 0 0; font-size: 14px;">' . $monthName . $batchInfo . '</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 25px 30px;">
                            <p style="margin: 0 0 20px 0; color: #333; font-size: 14px; line-height: 1.6;">
                                Please find attached the detailed individual attendance reports for <strong>' . $monthName . '</strong>.
                                This email contains <strong>' . $attachmentCount . ' PDF reports</strong>.
                            </p>

                            <div style="background: #f8fafc; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                                <h3 style="margin: 0 0 10px 0; color: #1e40af; font-size: 14px;">Included Reports:</h3>
                                <table width="100%" cellpadding="0" cellspacing="0" style="background: white; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    ' . $employeeList . '
                                </table>
                            </div>

                            <p style="margin: 0; color: #666; font-size: 13px;">
                                Each PDF contains the complete detailed attendance breakdown including:
                            </p>
                            <ul style="color: #666; font-size: 13px; margin: 10px 0 0 0; padding-left: 20px;">
                                <li>Daily attendance status (Present, Absent, Leave, Holiday, Weekly Off)</li>
                                <li>Scheduled and actual check-in/check-out times</li>
                                <li>Working hours per day</li>
                                <li>Late arrival and early departure details</li>
                                <li>Monthly summary statistics</li>
                            </ul>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: #f8fafc; padding: 20px 30px; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="margin: 0; color: #888; font-size: 12px;">
                                This is an automated email from the HRMS system.<br>
                                Generated on ' . date('d M Y, h:i A') . '
                            </p>
                            <p style="margin: 10px 0 0 0; color: #aaa; font-size: 11px;">
                                Bombay Engineering Syndicate | HRMS Portal
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

    return $html;
}
