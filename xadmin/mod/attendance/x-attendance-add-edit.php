<?php
$id = 0;
$D = array();
if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"]);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . $MXMOD["TBL"] . "` WHERE status=? AND `" . $MXMOD["PK"] . "` =?";
    $D = $DB->dbRow();
}

// Get employees dropdown
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT userID, displayName FROM `" . $DB->pre . "x_admin_user` WHERE status=? ORDER BY displayName ASC";
$empRows = $DB->dbRows();
$empOpts = '<option value="">-- Select Employee --</option>';
foreach ($empRows as $e) {
    $sel = (isset($D['userID']) && $D['userID'] == $e['userID']) ? ' selected' : '';
    $empOpts .= '<option value="' . $e['userID'] . '"' . $sel . '>' . htmlspecialchars($e['displayName']) . '</option>';
}

// Status options
$statOpts = '';
$statuses = array('present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half Day', 'leave' => 'Leave');
foreach ($statuses as $k => $v) {
    $sel = (isset($D['attendanceStatus']) && $D['attendanceStatus'] == $k) ? ' selected' : '';
    $statOpts .= '<option value="' . $k . '"' . $sel . '>' . $v . '</option>';
}

// Source options
$srcOpts = '';
$sources = array('manual' => 'Manual', 'biometric' => 'Biometric', 'system' => 'System');
foreach ($sources as $k => $v) {
    $sel = (isset($D['source']) && $D['source'] == $k) ? ' selected' : '';
    $srcOpts .= '<option value="' . $k . '"' . $sel . '>' . $v . '</option>';
}

// Format times
$checkInVal = isset($D["checkIn"]) && $D["checkIn"] ? date('H:i', strtotime($D["checkIn"])) : "";
$checkOutVal = isset($D["checkOut"]) && $D["checkOut"] ? date('H:i', strtotime($D["checkOut"])) : "";

$arrForm = array(
    array("type" => "select", "name" => "userID", "value" => $empOpts, "title" => "Employee", "validate" => "required"),
    array("type" => "date", "name" => "attendanceDate", "value" => ($D["attendanceDate"] ?? ""), "title" => "Attendance Date", "validate" => "required"),
    array("type" => "text", "name" => "checkIn", "value" => $checkInVal, "title" => "Check In (HH:MM)"),
    array("type" => "text", "name" => "checkOut", "value" => $checkOutVal, "title" => "Check Out (HH:MM)"),
    array("type" => "select", "name" => "attendanceStatus", "value" => $statOpts, "title" => "Status", "validate" => "required"),
    array("type" => "select", "name" => "source", "value" => $srcOpts, "title" => "Source"),
    array("type" => "textarea", "name" => "remarks", "value" => ($D["remarks"] ?? ""), "title" => "Remarks"),
);
$MXFRM = new mxForm();
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data">
        <div class="wrap-form f50">
            <ul class="tbl-form">
                <?php
                echo $MXFRM->getForm($arrForm);
                ?>
            </ul>
        </div>
        <div class="wrap-form f50">
        </div>
        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>
