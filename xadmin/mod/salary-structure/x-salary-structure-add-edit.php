<?php
$id = 0;
$D = array(
    "userID" => 0,
    "effectiveFrom" => date('Y-m-d'),
    "effectiveTo" => "",
    "basicSalary" => 0,
    "hra" => 0,
    "conveyanceAllowance" => 0,
    "medicalAllowance" => 0,
    "specialAllowance" => 0,
    "otherAllowance" => 0,
    "grossSalary" => 0,
    "remarks" => ""
);

if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"] ?? 0);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT SS.*, U.displayName FROM `" . $DB->pre . "salary_structure` SS
                LEFT JOIN `" . $DB->pre . "x_admin_user` U ON SS.userID = U.userID
                WHERE SS.status=? AND SS.structureID=?";
    $D = $DB->dbRow();
}

// Get employees dropdown
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT userID, displayName, employeeCode, designation FROM `" . $DB->pre . "x_admin_user` WHERE status=? ORDER BY displayName ASC";
$employees = $DB->dbRows();

$employeeOpts = '<option value="">-- Select Employee --</option>';
foreach ($employees as $emp) {
    $selected = (isset($D['userID']) && $D['userID'] == $emp['userID']) ? 'selected' : '';
    $label = $emp['displayName'];
    if ($emp['employeeCode']) {
        $label .= ' (' . $emp['employeeCode'] . ')';
    }
    $employeeOpts .= '<option value="' . $emp['userID'] . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
}

// Build form arrays
$arrForm = array();

// Employee selection (only for add mode)
if ($TPL->pageType != "edit") {
    $arrForm[] = array("type" => "select", "name" => "userID", "value" => $employeeOpts, "title" => "Employee", "validate" => "required");
}

// Add all fields
$arrForm[] = array("type" => "date", "name" => "effectiveFrom", "value" => ($D["effectiveFrom"] ?? ""), "title" => "Effective From", "validate" => "required");
$arrForm[] = array("type" => "date", "name" => "effectiveTo", "value" => ($D["effectiveTo"] ?? ""), "title" => "Effective To");
$arrForm[] = array("type" => "text", "name" => "basicSalary", "value" => ($D["basicSalary"] ?? 0), "title" => "Basic Salary", "validate" => "required,number");
$arrForm[] = array("type" => "text", "name" => "hra", "value" => ($D["hra"] ?? 0), "title" => "HRA", "validate" => "number");
$arrForm[] = array("type" => "text", "name" => "conveyanceAllowance", "value" => ($D["conveyanceAllowance"] ?? 0), "title" => "Conveyance Allowance", "validate" => "number");
$arrForm[] = array("type" => "text", "name" => "medicalAllowance", "value" => ($D["medicalAllowance"] ?? 0), "title" => "Medical Allowance", "validate" => "number");
$arrForm[] = array("type" => "text", "name" => "specialAllowance", "value" => ($D["specialAllowance"] ?? 0), "title" => "Special Allowance", "validate" => "number");
$arrForm[] = array("type" => "text", "name" => "otherAllowance", "value" => ($D["otherAllowance"] ?? 0), "title" => "Other Allowance", "validate" => "number");
$arrForm[] = array("type" => "textarea", "name" => "remarks", "value" => ($D["remarks"] ?? ""), "title" => "Remarks");

$MXFRM = new mxForm();
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data" auto="false">
        <div class="wrap-form f100">
            <?php if ($TPL->pageType == "edit" || $TPL->pageType == "view") { ?>
            <h2 class="form-head">Employee: <?php echo htmlspecialchars($D['displayName'] ?? ''); ?></h2>
            <?php } ?>
            <ul class="tbl-form">
                <?php echo $MXFRM->getForm($arrForm); ?>
            </ul>
        </div>
        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>
