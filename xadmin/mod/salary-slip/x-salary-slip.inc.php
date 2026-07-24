<?php
/**
 * Salary Slip Module Controller
 * Handles salary calculation, payment marking, PDF generation
 * Workflow: pending → paid → slip_generated → emailed
 */

// Generate salary slip for a user/month
function generateSalarySlip()
{
    global $DB;

    $userID = intval($_POST["userID"]);
    $month = intval($_POST["salaryMonth"]);
    $year = intval($_POST["salaryYear"]);

    // Check if slip already exists
    $DB->vals = array($userID, $month, $year, 1);
    $DB->types = "iiii";
    $DB->sql = "SELECT slipID FROM " . $DB->pre . "salary_slip WHERE userID=? AND salaryMonth=? AND salaryYear=? AND status=?";
    $existing = $DB->dbRow();

    if ($existing) {
        setResponse(array("err" => 1, "msg" => "Salary slip already exists for this month"));
        return;
    }

    // Get salary structure
    $effectiveDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $DB->vals = array($userID, $effectiveDate, $effectiveDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT * FROM " . $DB->pre . "salary_structure
                WHERE userID=? AND effectiveFrom <= ?
                AND (effectiveTo IS NULL OR effectiveTo >= ?) AND status=?
                ORDER BY effectiveFrom DESC LIMIT 1";
    $structure = $DB->dbRow();

    if (!$structure) {
        setResponse(array("err" => 1, "msg" => "No salary structure found for this employee"));
        return;
    }

    // Get attendance summary
    $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $endDate = date('Y-m-t', strtotime($startDate));

    $DB->vals = array($userID, $startDate, $endDate, 1);
    $DB->types = "issi";
    $DB->sql = "SELECT
                    COUNT(*) as totalRecords,
                    SUM(CASE WHEN attendanceStatus='present' THEN 1 ELSE 0 END) as presentDays,
                    SUM(CASE WHEN attendanceStatus='absent' THEN 1 ELSE 0 END) as absentDays,
                    SUM(CASE WHEN attendanceStatus='half_day' THEN 0.5 ELSE 0 END) as halfDays,
                    SUM(CASE WHEN attendanceStatus='leave' THEN 1 ELSE 0 END) as leaveDays,
                    SUM(CASE WHEN isLate=1 THEN 1 ELSE 0 END) as lateDays,
                    SUM(CASE WHEN isEarlyCheckout=1 THEN 1 ELSE 0 END) as earlyCheckoutDays
                FROM " . $DB->pre . "attendance
                WHERE userID=? AND attendanceDate>=? AND attendanceDate<=? AND status=?";
    $attendance = $DB->dbRow();

    // Get working days setting
    $DB->vals = array('working_days_per_month', 1);
    $DB->types = "si";
    $DB->sql = "SELECT settingValue FROM " . $DB->pre . "hrms_settings WHERE settingKey=? AND status=?";
    $workingDaysSetting = $DB->dbRow();
    $workingDays = intval($workingDaysSetting['settingValue'] ?? 26);

    // Calculate per day salary
    $perDaySalary = $structure['grossSalary'] / $workingDays;

    // Calculate deductions
    $leavesDeducted = intval($attendance['absentDays'] ?? 0);
    $leaveDeductionAmount = $leavesDeducted * $perDaySalary;

    // Get any pending advance deductions
    $DB->vals = array($userID, $month, $year, 'approved', 1);
    $DB->types = "iiisi";
    $DB->sql = "SELECT SUM(monthlyDeduction) as advanceDeduction FROM " . $DB->pre . "salary_advance
                WHERE userID=? AND deductFromMonth=? AND deductFromYear=? AND advanceStatus=? AND status=?
                AND remainingAmount > 0";
    $advance = $DB->dbRow();
    $advanceDeduction = floatval($advance['advanceDeduction'] ?? 0);

    // Calculate totals
    $totalEarnings = $structure['grossSalary'];
    $totalDeductions = $leaveDeductionAmount + $advanceDeduction;
    $netSalary = $totalEarnings - $totalDeductions;

    // Create slip
    $slipData = array(
        "userID" => $userID,
        "salaryMonth" => $month,
        "salaryYear" => $year,
        "structureID" => $structure['structureID'],
        "basicSalary" => $structure['basicSalary'],
        "hra" => $structure['hra'],
        "conveyanceAllowance" => $structure['conveyanceAllowance'],
        "medicalAllowance" => $structure['medicalAllowance'],
        "specialAllowance" => $structure['specialAllowance'],
        "otherAllowance" => $structure['otherAllowance'],
        "totalEarnings" => $totalEarnings,
        "leavesDeducted" => $leavesDeducted,
        "leaveDeductionAmount" => $leaveDeductionAmount,
        "advanceDeduction" => $advanceDeduction,
        "totalDeductions" => $totalDeductions,
        "netSalary" => $netSalary,
        "workingDays" => $workingDays,
        "presentDays" => intval($attendance['presentDays'] ?? 0),
        "absentDays" => intval($attendance['absentDays'] ?? 0),
        "leavesTaken" => intval($attendance['leaveDays'] ?? 0),
        "lateDays" => intval($attendance['lateDays'] ?? 0),
        "earlyCheckoutDays" => intval($attendance['earlyCheckoutDays'] ?? 0),
        "slipStatus" => "pending"
    );

    $DB->table = $DB->pre . "salary_slip";
    $DB->data = $slipData;
    if ($DB->dbInsert()) {
        $slipID = $DB->insertID;
        setResponse(array("err" => 0, "param" => "id=$slipID", "alert" => "Salary slip generated successfully"));
    } else {
        setResponse(array("err" => 1));
    }
}

// Update salary slip
function updateSalarySlip()
{
    global $DB;

    $slipID = intval($_POST["slipID"]);

    // Sanitize editable fields
    if (isset($_POST["otherDeduction"])) $_POST["otherDeduction"] = floatval($_POST["otherDeduction"]);
    if (isset($_POST["deductionRemarks"])) $_POST["deductionRemarks"] = cleanTitle($_POST["deductionRemarks"]);
    if (isset($_POST["paymentRemarks"])) $_POST["paymentRemarks"] = cleanTitle($_POST["paymentRemarks"]);

    // Recalculate totals if deductions changed
    if (isset($_POST["otherDeduction"])) {
        $DB->vals = array($slipID, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT * FROM " . $DB->pre . "salary_slip WHERE slipID=? AND status=?";
        $slip = $DB->dbRow();

        $_POST["totalDeductions"] = floatval($slip['leaveDeductionAmount']) + floatval($slip['advanceDeduction']) + floatval($_POST["otherDeduction"]);
        $_POST["netSalary"] = floatval($slip['totalEarnings']) - $_POST["totalDeductions"];
    }

    $DB->table = $DB->pre . "salary_slip";
    $DB->data = $_POST;
    if ($DB->dbUpdate("slipID=?", "i", array($slipID))) {
        setResponse(array("err" => 0, "param" => "id=$slipID"));
    } else {
        setResponse(array("err" => 1));
    }
}

// Mark salary as paid
function markAsPaid()
{
    global $DB;

    $slipID = intval($_POST["slipID"]);
    $amountPaid = floatval($_POST["amountPaid"]);
    $paymentMode = cleanTitle($_POST["paymentMode"] ?? "bank_transfer");
    $transactionRef = cleanTitle($_POST["transactionRef"] ?? "");
    $paymentRemarks = cleanTitle($_POST["paymentRemarks"] ?? "");

    $DB->table = $DB->pre . "salary_slip";
    $DB->data = array(
        "slipStatus" => "paid",
        "amountPaid" => $amountPaid,
        "paidOn" => date('Y-m-d'),
        "paidBy" => $_SESSION[SITEURL]["MXID"],
        "paymentMode" => $paymentMode,
        "transactionRef" => $transactionRef,
        "paymentRemarks" => $paymentRemarks
    );

    if ($DB->dbUpdate("slipID=?", "i", array($slipID))) {
        // Update any salary advances
        $DB->vals = array($slipID, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT userID, salaryMonth, salaryYear FROM " . $DB->pre . "salary_slip WHERE slipID=? AND status=?";
        $slip = $DB->dbRow();

        if ($slip) {
            // Deduct from salary advance
            updateSalaryAdvanceDeductions($slip['userID'], $slip['salaryMonth'], $slip['salaryYear']);
        }

        setResponse(array("err" => 0, "alert" => "Salary marked as paid", "param" => "id=$slipID"));
    } else {
        setResponse(array("err" => 1, "msg" => "Failed to update payment status"));
    }
}

// Update salary advance deductions after payment
function updateSalaryAdvanceDeductions($userID, $month, $year)
{
    global $DB;

    $DB->vals = array($userID, $month, $year, 'approved', 1);
    $DB->types = "iiisi";
    $DB->sql = "SELECT advanceID, monthlyDeduction, totalDeducted, remainingAmount FROM " . $DB->pre . "salary_advance
                WHERE userID=? AND deductFromMonth<=? AND deductFromYear<=? AND advanceStatus=? AND status=?
                AND remainingAmount > 0";
    $advances = $DB->dbRows();

    foreach ($advances as $adv) {
        $newTotalDeducted = floatval($adv['totalDeducted']) + floatval($adv['monthlyDeduction']);
        $newRemaining = floatval($adv['remainingAmount']) - floatval($adv['monthlyDeduction']);

        $updateData = array(
            "totalDeducted" => $newTotalDeducted,
            "remainingAmount" => max(0, $newRemaining)
        );

        if ($newRemaining <= 0) {
            $updateData["advanceStatus"] = "completed";
        }

        $DB->table = $DB->pre . "salary_advance";
        $DB->data = $updateData;
        $DB->dbUpdate("advanceID=?", "i", array($adv['advanceID']));
    }
}

// Generate PDF slip (only after payment)
function generatePDF()
{
    global $DB;

    $slipID = intval($_POST["slipID"]);

    // Check if paid
    $DB->vals = array($slipID, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT SS.*, U.displayName, U.employeeCode, U.designation, U.department, U.bankName, U.bankAccountNo, U.bankIFSC, U.panNo
                FROM " . $DB->pre . "salary_slip SS
                LEFT JOIN " . $DB->pre . "x_admin_user U ON SS.userID = U.userID
                WHERE SS.slipID=? AND SS.status=?";
    $slip = $DB->dbRow();

    if (!$slip) {
        return array("err" => 1, "msg" => "Slip not found");
    }

    if ($slip['slipStatus'] != 'paid' && $slip['slipStatus'] != 'slip_generated') {
        return array("err" => 1, "msg" => "Salary must be marked as paid before generating PDF");
    }

    // Generate PDF using MPDF
    require_once(ROOTPATH . '/vendor/autoload.php');

    $monthName = date('F', mktime(0, 0, 0, $slip['salaryMonth'], 1));
    $filename = "salary_slip_" . $slip['userID'] . "_" . $slip['salaryYear'] . "_" . str_pad($slip['salaryMonth'], 2, '0', STR_PAD_LEFT) . ".pdf";
    $filepath = ROOTPATH . "/uploads/salary-slips/" . $filename;

    // Create directory if not exists
    if (!is_dir(ROOTPATH . "/uploads/salary-slips/")) {
        mkdir(ROOTPATH . "/uploads/salary-slips/", 0755, true);
    }

    $html = getSalarySlipHTML($slip);

    try {
        $mpdf = new \Mpdf\Mpdf(['tempDir' => '/tmp', 'format' => 'A4']);
        $mpdf->WriteHTML($html);
        $mpdf->Output($filepath, 'F');

        // Update slip status
        $DB->table = $DB->pre . "salary_slip";
        $DB->data = array(
            "slipPDF" => $filename,
            "slipStatus" => "slip_generated",
            "generatedAt" => date('Y-m-d H:i:s')
        );
        $DB->dbUpdate("slipID=?", "i", array($slipID));

        return array("err" => 0, "msg" => "PDF generated successfully", "file" => $filename);
    } catch (Exception $e) {
        return array("err" => 1, "msg" => "PDF generation failed: " . $e->getMessage());
    }
}

// Generate HTML for salary slip
function getSalarySlipHTML($slip)
{
    $monthName = date('F', mktime(0, 0, 0, $slip['salaryMonth'], 1));
    $html = '
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 5px 0; font-size: 14px; color: #666; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; }
        .salary-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .salary-table th, .salary-table td { border: 1px solid #ddd; padding: 8px; }
        .salary-table th { background: #f5f5f5; text-align: left; }
        .earnings { background: #e8f5e9; }
        .deductions { background: #ffebee; }
        .net-row { background: #1976d2; color: #fff; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #666; }
    </style>

    <div class="header">
        <h1>BOMBAY ENGINEERING SYNDICATE</h1>
        <h2>Salary Slip - ' . $monthName . ' ' . $slip['salaryYear'] . '</h2>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Employee Name:</strong> ' . htmlspecialchars($slip['displayName']) . '</td>
            <td><strong>Employee Code:</strong> ' . htmlspecialchars($slip['employeeCode'] ?? '-') . '</td>
        </tr>
        <tr>
            <td><strong>Designation:</strong> ' . htmlspecialchars($slip['designation'] ?? '-') . '</td>
            <td><strong>Department:</strong> ' . htmlspecialchars($slip['department'] ?? '-') . '</td>
        </tr>
        <tr>
            <td><strong>Bank:</strong> ' . htmlspecialchars($slip['bankName'] ?? '-') . '</td>
            <td><strong>Account No:</strong> ' . htmlspecialchars($slip['bankAccountNo'] ?? '-') . '</td>
        </tr>
        <tr>
            <td><strong>PAN:</strong> ' . htmlspecialchars($slip['panNo'] ?? '-') . '</td>
            <td><strong>Payment Date:</strong> ' . ($slip['paidOn'] ? date('d M Y', strtotime($slip['paidOn'])) : '-') . '</td>
        </tr>
    </table>

    <table class="salary-table">
        <tr>
            <th colspan="2" class="earnings">EARNINGS</th>
            <th colspan="2" class="deductions">DEDUCTIONS</th>
        </tr>
        <tr>
            <td>Basic Salary</td>
            <td align="right">₹' . number_format($slip['basicSalary'], 2) . '</td>
            <td>Leave Deduction (' . $slip['leavesDeducted'] . ' days)</td>
            <td align="right">₹' . number_format($slip['leaveDeductionAmount'], 2) . '</td>
        </tr>
        <tr>
            <td>HRA</td>
            <td align="right">₹' . number_format($slip['hra'], 2) . '</td>
            <td>Advance Deduction</td>
            <td align="right">₹' . number_format($slip['advanceDeduction'], 2) . '</td>
        </tr>
        <tr>
            <td>Conveyance Allowance</td>
            <td align="right">₹' . number_format($slip['conveyanceAllowance'], 2) . '</td>
            <td>Other Deductions</td>
            <td align="right">₹' . number_format($slip['otherDeduction'] ?? 0, 2) . '</td>
        </tr>
        <tr>
            <td>Medical Allowance</td>
            <td align="right">₹' . number_format($slip['medicalAllowance'], 2) . '</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Special Allowance</td>
            <td align="right">₹' . number_format($slip['specialAllowance'], 2) . '</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Other Allowance</td>
            <td align="right">₹' . number_format($slip['otherAllowance'], 2) . '</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td><strong>Total Earnings</strong></td>
            <td align="right"><strong>₹' . number_format($slip['totalEarnings'], 2) . '</strong></td>
            <td><strong>Total Deductions</strong></td>
            <td align="right"><strong>₹' . number_format($slip['totalDeductions'], 2) . '</strong></td>
        </tr>
        <tr class="net-row">
            <td colspan="2"><strong>NET SALARY PAYABLE</strong></td>
            <td colspan="2" align="right"><strong>₹' . number_format($slip['netSalary'], 2) . '</strong></td>
        </tr>
        <tr>
            <td colspan="2"><strong>Amount Paid</strong></td>
            <td colspan="2" align="right"><strong>₹' . number_format($slip['amountPaid'] ?? $slip['netSalary'], 2) . '</strong></td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td><strong>Working Days:</strong> ' . $slip['workingDays'] . '</td>
            <td><strong>Present Days:</strong> ' . $slip['presentDays'] . '</td>
            <td><strong>Absent Days:</strong> ' . $slip['absentDays'] . '</td>
            <td><strong>Late Days:</strong> ' . $slip['lateDays'] . '</td>
        </tr>
    </table>

    <div class="footer">
        This is a computer-generated document. No signature required.<br>
        Generated on ' . date('d M Y h:i A') . '
    </div>';

    return $html;
}

// Bulk generate slips for a month
function bulkGenerateSlips()
{
    global $DB;

    $month = intval($_POST["salaryMonth"]);
    $year = intval($_POST["salaryYear"]);

    // Get all active employees with salary structure
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT DISTINCT U.userID FROM " . $DB->pre . "x_admin_user U
                INNER JOIN " . $DB->pre . "salary_structure SS ON U.userID = SS.userID
                WHERE U.status=? AND SS.status=1
                AND (U.dateOfExit IS NULL OR U.dateOfExit > CURDATE())
                AND SS.effectiveFrom <= '" . $year . "-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01'
                AND (SS.effectiveTo IS NULL OR SS.effectiveTo >= '" . $year . "-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01')";
    $employees = $DB->dbRows();

    $generated = 0;
    $skipped = 0;

    foreach ($employees as $emp) {
        $_POST["userID"] = $emp['userID'];
        $_POST["salaryMonth"] = $month;
        $_POST["salaryYear"] = $year;

        // Check if already exists
        $DB->vals = array($emp['userID'], $month, $year, 1);
        $DB->types = "iiii";
        $DB->sql = "SELECT slipID FROM " . $DB->pre . "salary_slip WHERE userID=? AND salaryMonth=? AND salaryYear=? AND status=?";
        $existing = $DB->dbRow();

        if (!$existing) {
            generateSalarySlip();
            $generated++;
        } else {
            $skipped++;
        }
    }

    return array("err" => 0, "msg" => "Generated: $generated slips, Skipped: $skipped (already exist)");
}

// Router
if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    require_once("../../inc/site.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                generateSalarySlip();
                break;
            case "UPDATE":
                updateSalarySlip();
                break;
            case "markAsPaid":
                markAsPaid();
                break;
            case "generatePDF":
                $MXRES = generatePDF();
                break;
            case "bulkGenerate":
                $MXRES = bulkGenerateSlips();
                break;
            case "mxDelFile":
                $param = array("dir" => "salary-slip", "tbl" => "salary_slip", "pk" => "slipID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "salary_slip", "PK" => "slipID", "UDIR" => array()));
}
