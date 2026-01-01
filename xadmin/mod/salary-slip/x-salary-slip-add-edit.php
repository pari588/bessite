<?php
$id = 0;
$D = array();

if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"] ?? 0);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT SS.*, U.displayName, U.employeeCode, U.designation, U.department
                FROM `" . $DB->pre . "salary_slip` SS
                LEFT JOIN `" . $DB->pre . "x_admin_user` U ON SS.userID = U.userID
                WHERE SS.status=? AND SS.slipID=?";
    $D = $DB->dbRow();
}

// For new slip generation
if ($TPL->pageType == "add") {
    // Get employees dropdown
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT U.userID, U.displayName, U.employeeCode
                FROM `" . $DB->pre . "x_admin_user` U
                INNER JOIN `" . $DB->pre . "salary_structure` SS ON U.userID = SS.userID
                WHERE U.status=? AND SS.status=1 AND (U.dateOfExit IS NULL OR U.dateOfExit > CURDATE())
                GROUP BY U.userID ORDER BY U.displayName ASC";
    $employees = $DB->dbRows();

    $employeeOpts = '<option value="">-- Select Employee --</option>';
    foreach ($employees as $emp) {
        $label = $emp['displayName'];
        if ($emp['employeeCode']) {
            $label .= ' (' . $emp['employeeCode'] . ')';
        }
        $employeeOpts .= '<option value="' . $emp['userID'] . '">' . htmlspecialchars($label) . '</option>';
    }

    // Month dropdown
    $monthOptions = '';
    for ($m = 1; $m <= 12; $m++) {
        $selected = ($m == date('m')) ? 'selected' : '';
        $monthOptions .= '<option value="' . $m . '" ' . $selected . '>' . date('F', mktime(0, 0, 0, $m, 1)) . '</option>';
    }

    // Year dropdown
    $yearOptions = '';
    for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++) {
        $selected = ($y == date('Y')) ? 'selected' : '';
        $yearOptions .= '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
    }

    $arrForm = array(
        array("type" => "select", "name" => "userID", "value" => $employeeOpts, "title" => "Employee", "validate" => "required"),
        array("type" => "select", "name" => "salaryMonth", "value" => $monthOptions, "title" => "Month", "validate" => "required"),
        array("type" => "select", "name" => "salaryYear", "value" => $yearOptions, "title" => "Year", "validate" => "required"),
    );

    $MXFRM = new mxForm();
    ?>

    <div class="wrap-right">
        <?php echo getPageNav(); ?>
        <div class="wrap-data">
            <p style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <strong>Note:</strong> The salary slip will be automatically calculated based on the employee's salary structure and attendance records for the selected month.
            </p>
            <form name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data" auto="false">
                <ul class="tbl-form">
                    <?php echo $MXFRM->getForm($arrForm); ?>
                </ul>
                <?php echo $MXFRM->closeForm(); ?>
            </form>
        </div>
    </div>

    <?php
} else {
    // View/Edit mode - show slip details
    $monthName = date('F', mktime(0, 0, 0, $D['salaryMonth'], 1));
    ?>

    <style>
    .slip-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
    .slip-header h2 { margin: 0; }
    .slip-header .period { font-size: 24px; margin-top: 5px; }
    .slip-section { background: #fff; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; }
    .slip-section h3 { background: #f5f5f5; margin: 0; padding: 10px 15px; border-bottom: 1px solid #ddd; font-size: 14px; }
    .slip-section .content { padding: 15px; }
    .slip-section table { width: 100%; }
    .slip-section table td { padding: 8px 0; border-bottom: 1px solid #eee; }
    .slip-section table td:last-child { text-align: right; font-weight: bold; }
    .earnings-total { background: #e8f5e9 !important; }
    .deductions-total { background: #ffebee !important; }
    .net-total { background: #1976d2 !important; color: #fff; font-size: 18px; }
    .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
    .status-pending { background: #ffc107; color: #000; }
    .status-paid { background: #28a745; color: #fff; }
    .status-slip_generated { background: #17a2b8; color: #fff; }
    .status-emailed { background: #6f42c1; color: #fff; }
    .action-buttons { margin-top: 20px; text-align: center; }
    .action-buttons button { margin: 0 10px; padding: 10px 30px; font-size: 14px; }
    </style>

    <div class="wrap-right">
        <?php echo getPageNav(); ?>

        <div class="slip-header">
            <h2><?php echo htmlspecialchars($D['displayName']); ?></h2>
            <div><?php echo htmlspecialchars($D['designation'] ?? ''); ?> | <?php echo htmlspecialchars($D['department'] ?? ''); ?></div>
            <div class="period"><?php echo $monthName . ' ' . $D['salaryYear']; ?></div>
            <div style="margin-top: 10px;">
                <span class="status-badge status-<?php echo $D['slipStatus']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $D['slipStatus'])); ?>
                </span>
            </div>
        </div>

        <div style="display: flex; gap: 20px;">
            <!-- Earnings -->
            <div class="slip-section" style="flex: 1;">
                <h3>EARNINGS</h3>
                <div class="content">
                    <table>
                        <tr><td>Basic Salary</td><td>₹<?php echo number_format($D['basicSalary'], 2); ?></td></tr>
                        <tr><td>HRA</td><td>₹<?php echo number_format($D['hra'], 2); ?></td></tr>
                        <tr><td>Conveyance Allowance</td><td>₹<?php echo number_format($D['conveyanceAllowance'], 2); ?></td></tr>
                        <tr><td>Medical Allowance</td><td>₹<?php echo number_format($D['medicalAllowance'], 2); ?></td></tr>
                        <tr><td>Special Allowance</td><td>₹<?php echo number_format($D['specialAllowance'], 2); ?></td></tr>
                        <tr><td>Other Allowance</td><td>₹<?php echo number_format($D['otherAllowance'], 2); ?></td></tr>
                        <tr class="earnings-total"><td><strong>Total Earnings</strong></td><td>₹<?php echo number_format($D['totalEarnings'], 2); ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Deductions -->
            <div class="slip-section" style="flex: 1;">
                <h3>DEDUCTIONS</h3>
                <div class="content">
                    <table>
                        <tr><td>Leave Deduction (<?php echo $D['leavesDeducted']; ?> days)</td><td>₹<?php echo number_format($D['leaveDeductionAmount'], 2); ?></td></tr>
                        <tr><td>Advance Deduction</td><td>₹<?php echo number_format($D['advanceDeduction'], 2); ?></td></tr>
                        <tr><td>Other Deductions</td><td>₹<?php echo number_format($D['otherDeduction'] ?? 0, 2); ?></td></tr>
                        <tr class="deductions-total"><td><strong>Total Deductions</strong></td><td>₹<?php echo number_format($D['totalDeductions'], 2); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Net Salary -->
        <div class="slip-section">
            <div class="content net-total" style="text-align: center; padding: 20px;">
                NET SALARY: ₹<?php echo number_format($D['netSalary'], 2); ?>
                <?php if ($D['amountPaid']) { ?>
                    <br><span style="font-size: 14px;">Amount Paid: ₹<?php echo number_format($D['amountPaid'], 2); ?></span>
                <?php } ?>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="slip-section">
            <h3>ATTENDANCE SUMMARY</h3>
            <div class="content">
                <table style="width: auto;">
                    <tr>
                        <td style="padding-right: 30px;">Working Days: <strong><?php echo $D['workingDays']; ?></strong></td>
                        <td style="padding-right: 30px;">Present: <strong><?php echo $D['presentDays']; ?></strong></td>
                        <td style="padding-right: 30px;">Absent: <strong><?php echo $D['absentDays']; ?></strong></td>
                        <td style="padding-right: 30px;">Leaves: <strong><?php echo $D['leavesTaken']; ?></strong></td>
                        <td style="padding-right: 30px;">Late Days: <strong><?php echo $D['lateDays']; ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment Details (if paid) -->
        <?php if ($D['paidOn']) { ?>
        <div class="slip-section">
            <h3>PAYMENT DETAILS</h3>
            <div class="content">
                <table style="width: auto;">
                    <tr>
                        <td style="padding-right: 30px;">Paid On: <strong><?php echo date('d M Y', strtotime($D['paidOn'])); ?></strong></td>
                        <td style="padding-right: 30px;">Mode: <strong><?php echo ucfirst(str_replace('_', ' ', $D['paymentMode'] ?? '')); ?></strong></td>
                        <td style="padding-right: 30px;">Ref: <strong><?php echo htmlspecialchars($D['transactionRef'] ?? '-'); ?></strong></td>
                    </tr>
                </table>
                <?php if ($D['paymentRemarks']) { ?>
                <p style="margin-top: 10px;"><em><?php echo htmlspecialchars($D['paymentRemarks']); ?></em></p>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <?php if ($D['slipStatus'] == 'pending') { ?>
                <button class="btn btn-success mark-paid-btn" data-id="<?php echo $D['slipID']; ?>" data-net="<?php echo $D['netSalary']; ?>">Mark as Paid</button>
            <?php } ?>

            <?php if ($D['slipStatus'] == 'paid' || $D['slipStatus'] == 'slip_generated') { ?>
                <button class="btn btn-info gen-pdf-btn" data-id="<?php echo $D['slipID']; ?>">Generate PDF</button>
            <?php } ?>

            <?php if ($D['slipPDF']) { ?>
                <a href="<?php echo SITEURL; ?>/uploads/salary-slips/<?php echo $D['slipPDF']; ?>" target="_blank" class="btn btn-primary">View PDF</a>
            <?php } ?>
        </div>

        <!-- Edit Deductions Form (only for pending) -->
        <?php if ($D['slipStatus'] == 'pending') { ?>
        <div class="slip-section" style="margin-top: 20px;">
            <h3>ADJUST DEDUCTIONS</h3>
            <div class="content">
                <form name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data" auto="false">
                    <ul class="tbl-form">
                        <li>
                            <label>Other Deduction (₹)</label>
                            <input type="text" name="otherDeduction" value="<?php echo $D['otherDeduction'] ?? 0; ?>">
                        </li>
                        <li>
                            <label>Deduction Remarks</label>
                            <textarea name="deductionRemarks" rows="2"><?php echo htmlspecialchars($D['deductionRemarks'] ?? ''); ?></textarea>
                        </li>
                    </ul>
                    <?php
                    $MXFRM = new mxForm();
                    echo $MXFRM->closeForm();
                    ?>
                </form>
            </div>
        </div>
        <?php } ?>
    </div>

    <script>
    $(document).ready(function() {
        $('.mark-paid-btn').click(function() {
            var slipID = $(this).data('id');
            var netSalary = $(this).data('net');
            $('#paySlipID').val(slipID);
            $('#amountPaid').val(netSalary);
            $('.payment-popup').show();
        });

        $('.gen-pdf-btn').click(function() {
            var slipID = $(this).data('id');
            generatePDF(slipID);
        });
    });

    function generatePDF(slipID) {
        $.ajax({
            url: '<?php echo $TPL->modUrl; ?>/x-salary-slip.inc.php',
            type: 'POST',
            data: {
                xAction: 'generatePDF',
                slipID: slipID,
                token: $('input[name="token"]').val()
            },
            success: function(response) {
                var res = JSON.parse(response);
                if (res.err == 0) {
                    alert(res.msg);
                    location.reload();
                } else {
                    alert(res.msg || 'Error generating PDF');
                }
            }
        });
    }
    </script>

    <?php
}
?>
