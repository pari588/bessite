<?php
$id = 0;
$D = array(
    "userID" => 0,
    "documentType" => "other",
    "documentName" => "",
    "fileName" => "",
    "validUpto" => "",
    "remarks" => ""
);

if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"] ?? 0);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT D.*, U.displayName FROM `" . $DB->pre . "employee_document` D
                LEFT JOIN `" . $DB->pre . "x_admin_user` U ON D.userID = U.userID
                WHERE D.status=? AND D.documentID=?";
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

// Document type dropdown
$docTypes = array(
    'aadhaar' => 'Aadhaar Card',
    'pan' => 'PAN Card',
    'passport' => 'Passport',
    'photo' => 'Photo',
    'appointment_letter' => 'Appointment Letter',
    'increment_letter' => 'Increment Letter',
    'exit_letter' => 'Exit Letter',
    'experience_letter' => 'Experience Letter',
    'policy' => 'Policy Document',
    'training_cert' => 'Training Certificate',
    'other' => 'Other'
);

$docTypeOpts = '';
foreach ($docTypes as $key => $label) {
    $selected = ($D['documentType'] == $key) ? 'selected' : '';
    $docTypeOpts .= '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
}

$arrForm = array(
    array("type" => "select", "name" => "userID", "value" => $employeeOpts, "title" => "Employee", "validate" => "required"),
    array("type" => "select", "name" => "documentType", "value" => $docTypeOpts, "title" => "Document Type", "validate" => "required"),
    array("type" => "text", "name" => "documentName", "value" => ($D["documentName"] ?? ""), "title" => "Document Name/Number", "validate" => "required"),
    array("type" => "file", "name" => "fileName", "value" => array(($D["fileName"] ?? ""), $id), "title" => "Document File"),
    array("type" => "date", "name" => "validUpto", "value" => ($D["validUpto"] ?? ""), "title" => "Valid Upto (if applicable)"),
    array("type" => "textarea", "name" => "remarks", "value" => ($D["remarks"] ?? ""), "title" => "Remarks", "attrp" => 'rows="2"'),
);

$MXFRM = new mxForm();
?>

<div class="wrap-right">
    <?php echo getPageNav(); ?>

    <?php if ($TPL->pageType == "edit" || $TPL->pageType == "view") { ?>
    <div style="background: #f8f9fa; padding: 10px 15px; margin-bottom: 15px; border-radius: 5px;">
        <strong>Employee:</strong> <?php echo htmlspecialchars($D['displayName'] ?? ''); ?>
        <?php if ($D['createdAt']) { ?>
        | <strong>Uploaded:</strong> <?php echo date('d M Y h:i A', strtotime($D['createdAt'])); ?>
        <?php } ?>
    </div>
    <?php } ?>

    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data" auto="false">
        <ul class="tbl-form">
            <?php echo $MXFRM->getForm($arrForm); ?>
        </ul>
        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>
