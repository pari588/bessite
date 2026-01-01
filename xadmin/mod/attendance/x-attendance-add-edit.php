<?php
$id = 0;
$D = array(
    "userID" => 0,
    "attendanceDate" => date('Y-m-d'),
    "checkIn" => "",
    "checkOut" => "",
    "attendanceStatus" => "present",
    "remarks" => "",
    "source" => "manual"
);

if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"] ?? 0);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT A.*, U.displayName FROM `" . $DB->pre . "attendance` A
                LEFT JOIN `" . $DB->pre . "x_admin_user` U ON A.userID = U.userID
                WHERE A.status=? AND A.attendanceID=?";
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

// Status dropdown
$statusOpts = '<option value="present"' . ($D['attendanceStatus'] == 'present' ? ' selected' : '') . '>Present</option>';
$statusOpts .= '<option value="absent"' . ($D['attendanceStatus'] == 'absent' ? ' selected' : '') . '>Absent</option>';
$statusOpts .= '<option value="half_day"' . ($D['attendanceStatus'] == 'half_day' ? ' selected' : '') . '>Half Day</option>';
$statusOpts .= '<option value="leave"' . ($D['attendanceStatus'] == 'leave' ? ' selected' : '') . '>Leave</option>';
$statusOpts .= '<option value="holiday"' . ($D['attendanceStatus'] == 'holiday' ? ' selected' : '') . '>Holiday</option>';
$statusOpts .= '<option value="weekend"' . ($D['attendanceStatus'] == 'weekend' ? ' selected' : '') . '>Weekend</option>';

// Source dropdown
$sourceOpts = '<option value="manual"' . ($D['source'] == 'manual' ? ' selected' : '') . '>Manual Entry</option>';
$sourceOpts .= '<option value="biometric"' . ($D['source'] == 'biometric' ? ' selected' : '') . '>Biometric</option>';
$sourceOpts .= '<option value="system"' . ($D['source'] == 'system' ? ' selected' : '') . '>System</option>';

// Main form
$arrForm = array(
    array("type" => "select", "name" => "userID", "value" => $employeeOpts, "title" => "Employee", "validate" => "required"),
    array("type" => "date", "name" => "attendanceDate", "value" => ($D["attendanceDate"] ?? ""), "title" => "Attendance Date", "validate" => "required"),
    array("type" => "text", "name" => "checkIn", "value" => ($D["checkIn"] ? date('H:i', strtotime($D["checkIn"])) : ""), "title" => "Check In Time (HH:MM)", "validate" => "", "attr" => 'placeholder="09:00"'),
    array("type" => "text", "name" => "checkOut", "value" => ($D["checkOut"] ? date('H:i', strtotime($D["checkOut"])) : ""), "title" => "Check Out Time (HH:MM)", "validate" => "", "attr" => 'placeholder="18:00"'),
    array("type" => "select", "name" => "attendanceStatus", "value" => $statusOpts, "title" => "Status", "validate" => "required"),
    array("type" => "select", "name" => "source", "value" => $sourceOpts, "title" => "Source"),
    array("type" => "textarea", "name" => "remarks", "value" => ($D["remarks"] ?? ""), "title" => "Remarks", "attrp" => 'rows="2"'),
);

$MXFRM = new mxForm();
?>

<style>
.attendance-info { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
.attendance-info table { width: 100%; }
.attendance-info th { text-align: left; padding: 5px 10px; color: #666; font-weight: normal; }
.attendance-info td { padding: 5px 10px; font-weight: bold; }
.badge-late { background: #dc3545; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 11px; }
.badge-early { background: #fd7e14; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 11px; }
.remarks-section { margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px; }
.remarks-section h4 { margin-top: 0; color: #856404; }
.remark-item { padding: 10px; margin-bottom: 10px; background: #fff; border-radius: 3px; border-left: 3px solid #17a2b8; }
.remark-item.approved { border-left-color: #28a745; }
.remark-item.rejected { border-left-color: #dc3545; }
.remark-item.pending { border-left-color: #ffc107; }
</style>

<div class="wrap-right">
    <?php echo getPageNav(); ?>

    <?php if ($TPL->pageType == "edit" || $TPL->pageType == "view") { ?>
    <div class="attendance-info">
        <table>
            <tr>
                <th>Employee:</th>
                <td><?php echo htmlspecialchars($D['displayName'] ?? ''); ?></td>
                <th>Date:</th>
                <td><?php echo date('d M Y (l)', strtotime($D['attendanceDate'])); ?></td>
            </tr>
            <tr>
                <th>Working Hours:</th>
                <td><?php echo $D['workingHours'] ? number_format($D['workingHours'], 2) . ' hours' : '-'; ?></td>
                <th>Synced At:</th>
                <td><?php echo $D['syncedAt'] ? date('d M Y h:i A', strtotime($D['syncedAt'])) : '-'; ?></td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <?php if ($D['isLate']) { ?>
                        <span class="badge-late">Late by <?php echo $D['lateMinutes']; ?> minutes</span>
                    <?php } ?>
                    <?php if ($D['isEarlyCheckout']) { ?>
                        <span class="badge-early">Early checkout by <?php echo $D['earlyMinutes']; ?> minutes</span>
                    <?php } ?>
                    <?php if (!$D['isLate'] && !$D['isEarlyCheckout']) { ?>
                        <span style="color: #28a745;">On Time</span>
                    <?php } ?>
                </td>
                <th>Source:</th>
                <td><?php echo ucfirst($D['source'] ?? 'manual'); ?></td>
            </tr>
        </table>
    </div>

    <?php
    // Get remarks for this attendance
    $DB->vals = array($id, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT AR.*, U.displayName as submittedByName, M.displayName as reviewedByName
                FROM `" . $DB->pre . "attendance_remarks` AR
                LEFT JOIN `" . $DB->pre . "x_admin_user` U ON AR.submittedBy = U.userID
                LEFT JOIN `" . $DB->pre . "x_admin_user` M ON AR.reviewedBy = M.userID
                WHERE AR.attendanceID=? AND AR.status=?
                ORDER BY AR.submittedAt DESC";
    $remarks = $DB->dbRows();

    if ($DB->numRows > 0) { ?>
    <div class="remarks-section">
        <h4>Employee Remarks</h4>
        <?php foreach ($remarks as $r) { ?>
        <div class="remark-item <?php echo $r['reviewStatus']; ?>">
            <strong><?php echo ucfirst(str_replace('_', ' ', $r['remarkType'])); ?>:</strong>
            <?php echo htmlspecialchars($r['reason']); ?>
            <br><small>
                Submitted by <?php echo htmlspecialchars($r['submittedByName']); ?>
                on <?php echo date('d M Y h:i A', strtotime($r['submittedAt'])); ?>
                | Status: <strong><?php echo ucfirst($r['reviewStatus']); ?></strong>
                <?php if ($r['reviewedBy']) { ?>
                    by <?php echo htmlspecialchars($r['reviewedByName']); ?>
                <?php } ?>
            </small>
            <?php if ($r['reviewNote']) { ?>
                <br><small><em>Note: <?php echo htmlspecialchars($r['reviewNote']); ?></em></small>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
    <?php } ?>
    <?php } ?>

    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data" auto="false">
        <ul class="tbl-form">
            <?php echo $MXFRM->getForm($arrForm); ?>
        </ul>
        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>
