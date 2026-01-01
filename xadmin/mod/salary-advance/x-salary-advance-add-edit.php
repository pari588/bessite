<?php
$id = 0;
$D = array(
    "userID" => 0,
    "advanceAmount" => 0,
    "advanceDate" => date('Y-m-d'),
    "reason" => "",
    "deductFromMonth" => date('m'),
    "deductFromYear" => date('Y'),
    "monthlyDeduction" => 0,
    "advanceStatus" => "pending"
);

if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"] ?? 0);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT SA.*, U.displayName, A.displayName as approverName
                FROM `" . $DB->pre . "salary_advance` SA
                LEFT JOIN `" . $DB->pre . "x_admin_user` U ON SA.userID = U.userID
                LEFT JOIN `" . $DB->pre . "x_admin_user` A ON SA.approvedBy = A.userID
                WHERE SA.status=? AND SA.advanceID=?";
    $D = $DB->dbRow();
}

// Get employees dropdown
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT userID, displayName, employeeCode FROM `" . $DB->pre . "x_admin_user` WHERE status=? ORDER BY displayName ASC";
$employees = $DB->dbRows();

$employeeOpts = '<option value="">-- Select Employee --</option>';
foreach ($employees as $emp) {
    $selected = ($D['userID'] == $emp['userID']) ? 'selected' : '';
    $label = $emp['displayName'];
    if ($emp['employeeCode']) {
        $label .= ' (' . $emp['employeeCode'] . ')';
    }
    $employeeOpts .= '<option value="' . $emp['userID'] . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
}

// Month dropdown
$monthOptions = '';
for ($m = 1; $m <= 12; $m++) {
    $selected = ($m == ($D['deductFromMonth'] ?? date('m'))) ? 'selected' : '';
    $monthOptions .= '<option value="' . $m . '" ' . $selected . '>' . date('F', mktime(0, 0, 0, $m, 1)) . '</option>';
}

// Year dropdown
$yearOptions = '';
for ($y = date('Y'); $y <= date('Y') + 2; $y++) {
    $selected = ($y == ($D['deductFromYear'] ?? date('Y'))) ? 'selected' : '';
    $yearOptions .= '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
}

$MXFRM = new mxForm();
?>

<style>
.advance-info { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
.advance-info table { width: 100%; }
.advance-info th { text-align: left; padding: 5px 10px; color: #666; font-weight: normal; width: 150px; }
.advance-info td { padding: 5px 10px; font-weight: bold; }
.status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
.status-pending { background: #ffc107; color: #000; }
.status-approved { background: #28a745; color: #fff; }
.status-rejected { background: #dc3545; color: #fff; }
.status-completed { background: #17a2b8; color: #fff; }
.recovery-section { background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
.recovery-section h4 { margin-top: 0; color: #1565c0; }
</style>

<div class="wrap-right">
    <?php echo getPageNav(); ?>

    <?php if ($TPL->pageType == "edit" || $TPL->pageType == "view") { ?>
    <div class="advance-info">
        <table>
            <tr>
                <th>Employee:</th>
                <td><?php echo htmlspecialchars($D['displayName'] ?? ''); ?></td>
                <th>Status:</th>
                <td><span class="status-badge status-<?php echo $D['advanceStatus']; ?>"><?php echo ucfirst($D['advanceStatus']); ?></span></td>
            </tr>
            <tr>
                <th>Advance Amount:</th>
                <td>₹<?php echo number_format($D['advanceAmount'], 2); ?></td>
                <th>Advance Date:</th>
                <td><?php echo date('d M Y', strtotime($D['advanceDate'])); ?></td>
            </tr>
            <?php if ($D['approvedBy']) { ?>
            <tr>
                <th>Approved By:</th>
                <td><?php echo htmlspecialchars($D['approverName'] ?? ''); ?></td>
                <th>Approved On:</th>
                <td><?php echo $D['approvedAt'] ? date('d M Y h:i A', strtotime($D['approvedAt'])) : '-'; ?></td>
            </tr>
            <?php } ?>
        </table>
    </div>

    <?php if ($D['advanceStatus'] == 'approved' || $D['advanceStatus'] == 'completed') { ?>
    <div class="recovery-section">
        <h4>Recovery Status</h4>
        <table>
            <tr>
                <td><strong>Total Amount:</strong> ₹<?php echo number_format($D['advanceAmount'], 2); ?></td>
                <td><strong>Monthly EMI:</strong> ₹<?php echo number_format($D['monthlyDeduction'], 2); ?></td>
                <td><strong>Total Recovered:</strong> ₹<?php echo number_format($D['totalDeducted'], 2); ?></td>
                <td><strong>Remaining:</strong> ₹<?php echo number_format($D['remainingAmount'], 2); ?></td>
            </tr>
            <tr>
                <td colspan="4">
                    <strong>Deduction Start:</strong>
                    <?php echo date('F', mktime(0, 0, 0, $D['deductFromMonth'], 1)) . ' ' . $D['deductFromYear']; ?>
                </td>
            </tr>
        </table>
    </div>
    <?php } ?>
    <?php } ?>

    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data" auto="false">
        <fieldset style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">
            <legend style="font-weight: bold; padding: 0 10px;">Advance Details</legend>
            <ul class="tbl-form">
                <?php if ($TPL->pageType != "edit") { ?>
                <li>
                    <label>Employee <span class="required">*</span></label>
                    <select name="userID" required><?php echo $employeeOpts; ?></select>
                </li>
                <?php } ?>
                <li>
                    <label>Advance Amount (₹) <span class="required">*</span></label>
                    <input type="number" name="advanceAmount" value="<?php echo $D['advanceAmount']; ?>" required min="0" step="0.01">
                </li>
                <li>
                    <label>Advance Date <span class="required">*</span></label>
                    <input type="date" name="advanceDate" value="<?php echo $D['advanceDate']; ?>" required>
                </li>
                <li>
                    <label>Reason</label>
                    <textarea name="reason" rows="2"><?php echo htmlspecialchars($D['reason'] ?? ''); ?></textarea>
                </li>
            </ul>
        </fieldset>

        <fieldset style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">
            <legend style="font-weight: bold; padding: 0 10px;">Deduction Settings</legend>
            <ul class="tbl-form">
                <li>
                    <label>Start Deduction From</label>
                    <select name="deductFromMonth" style="width: auto; margin-right: 10px;"><?php echo $monthOptions; ?></select>
                    <select name="deductFromYear" style="width: auto;"><?php echo $yearOptions; ?></select>
                </li>
                <li>
                    <label>Monthly Deduction Amount (₹)</label>
                    <input type="number" name="monthlyDeduction" value="<?php echo $D['monthlyDeduction']; ?>" min="0" step="0.01">
                    <small style="display: block; color: #666;">Leave 0 for full amount deduction in first month</small>
                </li>
            </ul>
        </fieldset>

        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>
