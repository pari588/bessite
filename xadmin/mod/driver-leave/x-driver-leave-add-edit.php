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

// Driver dropdown (mx_user = driver roster)
$driverOpts = '<option value="">-- Select Driver --</option>';
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT userID, userName FROM `" . $DB->pre . "user` WHERE status=? ORDER BY userName";
$drivers = $DB->dbRows();
if (is_array($drivers)) {
    foreach ($drivers as $dv) {
        $sel = (isset($D["userID"]) && $D["userID"] == $dv["userID"]) ? ' selected="selected"' : '';
        $driverOpts .= '<option value="' . $dv["userID"] . '"' . $sel . '>' . htmlspecialchars($dv["userName"], ENT_QUOTES, 'UTF-8') . '</option>';
    }
}

// Leave type dropdown
$typeOpts = '<option value="">-- Select Type --</option>';
foreach (array("Extended Leave", "Medical Leave", "Personal Leave", "Unpaid Leave", "Other") as $t) {
    $sel = (isset($D["leaveType"]) && $D["leaveType"] == $t) ? ' selected="selected"' : '';
    $typeOpts .= '<option value="' . $t . '"' . $sel . '>' . $t . '</option>';
}

// Returned flag — leave stays active until this is set to Yes
$retOpts = '';
foreach (array("0" => "No — still on leave", "1" => "Yes — has returned") as $k => $v) {
    $sel = (isset($D["isReturned"]) && (string)$D["isReturned"] === (string)$k) ? ' selected="selected"' : '';
    $retOpts .= '<option value="' . $k . '"' . $sel . '>' . $v . '</option>';
}

$arrForm = array(
    array("type" => "select",   "name" => "userID",     "value" => $driverOpts,             "title" => "Driver",                 "validate" => "required"),
    array("type" => "date",     "name" => "fromDate",   "value" => ($D["fromDate"] ?? ""),  "title" => "Leave From",             "validate" => "required"),
    array("type" => "date",     "name" => "toDate",     "value" => ($D["toDate"] ?? ""),    "title" => "Expected Return (approx)"),
    array("type" => "select",   "name" => "leaveType",  "value" => $typeOpts,               "title" => "Leave Type"),
    array("type" => "select",   "name" => "isReturned", "value" => $retOpts,                "title" => "Has Returned?"),
    array("type" => "textarea", "name" => "remarks",    "value" => ($D["remarks"] ?? ""),   "title" => "Remarks"),
);
$MXFRM = new mxForm();
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data">
        <div class="wrap-form f50">
            <ul class="tbl-form">
                <?php echo $MXFRM->getForm($arrForm); ?>
            </ul>
        </div>
        <div class="wrap-form f50">
            <div style="margin:10px 0 0;padding:14px 16px;background:#f7f9fb;border:1px solid #e6ebf0;border-left:3px solid #157bba;border-radius:6px;font-size:12px;line-height:1.7;color:#5a6672;">
                <strong style="color:#31404e;">How this is used</strong><br>
                While a driver is on leave, the nightly auto mark-in will <strong>not</strong> create a duty row for them, so the overtime report stays clean.<br><br>
                <strong>Expected Return</strong> is only an estimate — the driver stays marked "On Leave" until you set <strong>Has Returned</strong> to <em>Yes</em>. Leaving it blank means open-ended leave.
            </div>
        </div>
        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>
