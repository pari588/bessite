<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/js/x-salary-slip.inc.js'); ?>"></script>
<?php
// Current month/year defaults
$currentMonth = date('m');
$currentYear = date('Y');

// Month dropdown
$monthOptions = '';
for ($m = 1; $m <= 12; $m++) {
    $selected = ($m == $currentMonth) ? 'selected' : '';
    $monthOptions .= '<option value="' . $m . '" ' . $selected . '>' . date('F', mktime(0, 0, 0, $m, 1)) . '</option>';
}

// Year dropdown
$yearOptions = '';
for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++) {
    $selected = ($y == $currentYear) ? 'selected' : '';
    $yearOptions .= '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
}

// Status dropdown
$statusOptions = '
    <option value="">-- All --</option>
    <option value="pending">Pending</option>
    <option value="paid">Paid</option>
    <option value="slip_generated">Slip Generated</option>
    <option value="emailed">Emailed</option>
';

// Search array
$arrSearch = array(
    array("type" => "text", "name" => "displayName", "title" => "Employee", "where" => "AND U.displayName LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "select", "name" => "salaryMonth", "value" => $monthOptions, "title" => "Month", "where" => "AND SS.salaryMonth=?", "dtype" => "i", "default" => $currentMonth),
    array("type" => "select", "name" => "salaryYear", "value" => $yearOptions, "title" => "Year", "where" => "AND SS.salaryYear=?", "dtype" => "i", "default" => $currentYear),
    array("type" => "select", "name" => "slipStatus", "value" => $statusOptions, "title" => "Status", "where" => "AND SS.slipStatus=?", "dtype" => "s"),
);

$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);

// Build query
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT SS.*, U.displayName, U.employeeCode, U.designation
            FROM `" . $DB->pre . "salary_slip` AS SS
            LEFT JOIN `" . $DB->pre . "x_admin_user` AS U ON SS.userID = U.userID
            WHERE SS.status=? " . $MXFRM->where;
$DB->dbQuery();
$MXTOTREC = $DB->numRows;

echo $strSearch;
?>

<div class="wrap-right">
    <?php echo getPageNav(); ?>

    <div class="wrap-data">
        <div class="form-inline" style="margin-bottom:15px;">
            <strong>Bulk Actions:</strong>
            <button class="btn btn-primary" onclick="bulkGenerateSlips()">Generate All Pending Slips</button>
            <span style="margin-left: 20px;">
                <label>Month:</label>
                <select id="bulkMonth"><?php echo $monthOptions; ?></select>
                <label>Year:</label>
                <select id="bulkYear"><?php echo $yearOptions; ?></select>
            </span>
        </div>

        <?php
        if ($MXTOTREC > 0) {
            $MXCOLS = array(
                array("#ID", "slipID", ' width="1%" align="center"', true),
                array("Employee", "displayName", ' width="15%" align="left"'),
                array("Month/Year", "period", ' width="10%" align="center"'),
                array("Gross", "totalEarnings", ' width="10%" align="right"'),
                array("Deductions", "totalDeductions", ' width="10%" align="right"'),
                array("Net Salary", "netSalary", ' width="10%" align="right"'),
                array("Paid", "amountPaid", ' width="10%" align="right"'),
                array("Status", "slipStatus", ' width="10%" align="center"'),
                array("Actions", "actions", ' width="15%" align="center"'),
            );

            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT SS.*, U.displayName, U.employeeCode, U.designation
                        FROM `" . $DB->pre . "salary_slip` AS SS
                        LEFT JOIN `" . $DB->pre . "x_admin_user` AS U ON SS.userID = U.userID
                        WHERE SS.status=? " . $MXFRM->where . " " . mxOrderBy(" SS.salaryYear DESC, SS.salaryMonth DESC, U.displayName ASC ") . mxQryLimit();
            $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr><?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $row) {
                        // Keep original values
                        $originalStatus = $row['slipStatus'];
                        $originalNetSalary = $row['netSalary'];
                        $originalSlipPDF = $row['slipPDF'] ?? '';

                        // Build display row
                        $d = $row;

                        // Format period
                        $d['period'] = date('M', mktime(0, 0, 0, $row['salaryMonth'], 1)) . ' ' . $row['salaryYear'];

                        // Format amounts
                        $d['totalEarnings'] = number_format($row['totalEarnings'], 0);
                        $d['totalDeductions'] = number_format($row['totalDeductions'], 0);
                        $d['netSalary'] = '<strong>' . number_format($row['netSalary'], 0) . '</strong>';
                        $d['amountPaid'] = $row['amountPaid'] ? number_format($row['amountPaid'], 0) : '-';

                        // Status badge
                        $statusLabel = ucfirst(str_replace('_', ' ', $originalStatus));
                        $statusClass = '';
                        if ($originalStatus == 'pending') $statusClass = 'label label-warning';
                        elseif ($originalStatus == 'paid') $statusClass = 'label label-success';
                        elseif ($originalStatus == 'slip_generated') $statusClass = 'label label-info';
                        elseif ($originalStatus == 'emailed') $statusClass = 'label label-primary';
                        $d['slipStatus'] = '<span class="' . $statusClass . '">' . $statusLabel . '</span>';

                        // Actions based on status
                        $actions = '';
                        if ($originalStatus == 'pending') {
                            $actions .= '<button class="btn btn-xs btn-success mark-paid" data-id="' . $row['slipID'] . '" data-net="' . $originalNetSalary . '">Mark Paid</button> ';
                        }
                        if ($originalStatus == 'paid' || $originalStatus == 'slip_generated') {
                            $actions .= '<button class="btn btn-xs btn-info gen-pdf" data-id="' . $row['slipID'] . '">Generate PDF</button> ';
                        }
                        if (!empty($originalSlipPDF)) {
                            $actions .= '<a href="' . SITEURL . '/uploads/salary-slips/' . $originalSlipPDF . '" target="_blank" class="btn btn-xs">View PDF</a>';
                        }
                        $d['actions'] = $actions ?: '-';
                    ?>
                        <tr>
                            <?php echo getMAction("mid", $d["slipID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2]; ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if (isset($v[3]) && $v[3] != '') {
                                        echo getViewEditUrl("id=" . $d["slipID"], $d[$v[1]]);
                                    } else {
                                        echo $d[$v[1]] ?? "-";
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="alert alert-info">No salary slips found for selected criteria</p>
        <?php } ?>
    </div>
</div>

<!-- Mark as Paid Popup -->
<div class="popup payment-popup mxdialog" style="display:none">
    <div class="body">
        <a href="#" class="close del rl" onclick="closePaymentPopup()"></a>
        <h2 class="title">Mark Salary as Paid</h2>
        <div class="content">
            <form id="paymentForm">
                <input type="hidden" name="slipID" id="paySlipID">
                <ul class="tbl-form">
                    <li>
                        <label>Amount Paid (₹)</label>
                        <input type="text" name="amountPaid" id="amountPaid" required>
                    </li>
                    <li>
                        <label>Payment Mode</label>
                        <select name="paymentMode">
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="upi">UPI</option>
                        </select>
                    </li>
                    <li>
                        <label>Transaction Ref</label>
                        <input type="text" name="transactionRef" placeholder="Transaction ID / Cheque No">
                    </li>
                    <li>
                        <label>Remarks</label>
                        <textarea name="paymentRemarks" rows="2"></textarea>
                    </li>
                </ul>
                <div class="mx-btn">
                    <button type="submit" class="btn btn-success">Confirm Payment</button>
                    <button type="button" class="btn" onclick="closePaymentPopup()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
