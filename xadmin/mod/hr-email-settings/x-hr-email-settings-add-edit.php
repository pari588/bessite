<?php
$id = 0;
$D = array(
    "recipientName" => "",
    "recipientEmail" => "",
    "emailTypes" => "hr_master"
);

if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"] ?? 0);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "hr_email_recipients` WHERE status=? AND recipientID=?";
    $D = $DB->dbRow();
}

$selectedTypes = explode(',', $D['emailTypes'] ?? '');

// Build email type checkboxes HTML
$emailTypeOptions = '';
$emailTypes = array(
    'individual_slip' => array('label' => 'Individual Salary Slips', 'desc' => 'Receive copy of each employee\'s salary slip when emailed'),
    'hr_master' => array('label' => 'HR Master Report', 'desc' => 'Monthly consolidated salary report with all employees'),
    'attendance_summary' => array('label' => 'Attendance Summary', 'desc' => 'Monthly attendance summary report')
);
foreach ($emailTypes as $key => $info) {
    $checked = in_array($key, $selectedTypes) ? 'checked' : '';
    $emailTypeOptions .= '<label style="display:block;margin:5px 0;"><input type="checkbox" name="emailTypes[]" value="' . $key . '" ' . $checked . '> ' . $info['label'] . '</label>';
}

// Build form arrays
$arrForm = array();
$arrForm[] = array("type" => "text", "name" => "recipientName", "value" => ($D["recipientName"] ?? ""), "title" => "Recipient Name", "validate" => "required");
$arrForm[] = array("type" => "text", "name" => "recipientEmail", "value" => ($D["recipientEmail"] ?? ""), "title" => "Email Address", "validate" => "required,email");
$arrForm[] = array("type" => "html", "name" => "emailTypes", "value" => $emailTypeOptions, "title" => "Email Types to Receive");

$MXFRM = new mxForm();
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data" auto="false">
        <div class="wrap-form f100">
            <ul class="tbl-form">
                <?php echo $MXFRM->getForm($arrForm); ?>
            </ul>
        </div>
        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>
