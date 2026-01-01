<script language="javascript" type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/js/x-admin-user.inc.js'); ?>"></script>
<?php
$vPass = "required,";
$vCPass = "required,";
$id = 0;

$D = array("displayName" => "", "userName" => "", "userEmail" => "", "imageName" => "", "roleID" => 0);
if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"]);
    $DB->types = "ii";
    $DB->vals = array(1, $id);
    $DB->sql = "SELECT * FROM `" . $DB->pre . $MXMOD["TBL"] . "` WHERE status=? AND " . $MXMOD["PK"] . "=?";
    $D = $DB->dbRow();
    $vPass = "";
    $vCPass = "";
}

// Get roles for dropdown
$DB->sql = "SELECT roleID,roleName,parentID FROM `" . $DB->pre . "x_admin_role` ORDER BY xOrder ASC";
$arrRole = $DB->dbRows();
$strRoleOpt = getTreeDD($arrRole, "roleID", "roleName", "parentID", ($D['roleID'] ?? ""));

// Get managers for dropdown (users with isLeaveManager = 1)
$DB->vals = array(1, 1);
$DB->types = "ii";
$DB->sql = "SELECT userID, displayName FROM `" . $DB->pre . "x_admin_user` WHERE status=? AND isLeaveManager=? ORDER BY displayName ASC";
$managers = $DB->dbRows();
$strManagerOpt = '<option value="">-- No Manager --</option>';
foreach ($managers as $mgr) {
    $selected = (isset($D['managerID']) && $D['managerID'] == $mgr['userID']) ? 'selected' : '';
    $strManagerOpt .= '<option value="' . $mgr['userID'] . '" ' . $selected . '>' . htmlspecialchars($mgr['displayName']) . '</option>';
}

// Gender dropdown
$genderOpt = '<option value="">-- Select --</option>';
$genderOpt .= '<option value="M"' . (($D['gender'] ?? '') == 'M' ? ' selected' : '') . '>Male</option>';
$genderOpt .= '<option value="F"' . (($D['gender'] ?? '') == 'F' ? ' selected' : '') . '>Female</option>';
$genderOpt .= '<option value="O"' . (($D['gender'] ?? '') == 'O' ? ' selected' : '') . '>Other</option>';

// Employment type dropdown
$empTypeOpt = '<option value="permanent"' . (($D['employmentType'] ?? 'permanent') == 'permanent' ? ' selected' : '') . '>Permanent</option>';
$empTypeOpt .= '<option value="contract"' . (($D['employmentType'] ?? '') == 'contract' ? ' selected' : '') . '>Contract</option>';
$empTypeOpt .= '<option value="probation"' . (($D['employmentType'] ?? '') == 'probation' ? ' selected' : '') . '>Probation</option>';

// Blood group dropdown
$bloodGroups = array('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-');
$bloodGroupOpt = '<option value="">-- Select --</option>';
foreach ($bloodGroups as $bg) {
    $selected = (isset($D['bloodGroup']) && $D['bloodGroup'] == $bg) ? 'selected' : '';
    $bloodGroupOpt .= '<option value="' . $bg . '" ' . $selected . '>' . $bg . '</option>';
}

// Basic Info Form
$arrForm = array(
    array("type" => "text", "name" => "displayName", "value" => ($D["displayName"] ?? ""), "title" => "Full Name", "validate" => "required,name,minlen:5"),
    array("type" => "text", "name" => "employeeCode", "value" => ($D["employeeCode"] ?? ""), "title" => "Employee Code", "validate" => ""),
    array("type" => "text", "name" => "userName", "value" => ($D["userName"] ?? ""), "title" => "Login Name", "validate" => "required,loginname"),
    array("type" => "text", "name" => "userMobile", "value" => ($D["userMobile"] ?? ""), "title" => "Mobile No", "validate" => ""),
    array("type" => "text", "name" => "userEmail", "value" => ($D["userEmail"] ?? ""), "title" => "Email", "validate" => "email"),
    array("type" => "password", "name" => "userPass", "value" => "", "title" => "Password", "validate" => $vPass . "password"),
    array("type" => "password", "name" => "userPass1", "value" => "", "title" => "Verify Password", "validate" => $vCPass . "password,equalto:userPass")
);

// Role & Photo
$arrFormS = array(
    array("type" => "select", "name" => "roleID", "value" => $strRoleOpt, "title" => "User Role", "validate" => "required"),
    array("type" => "file", "name" => "imageName", "value" => array(($D["imageName"] ?? ""), $id), "title" => "Photo", "validate" => "image"),
);

// Personal Details
$arrPersonalForm = array(
    array("type" => "date", "name" => "dateOfBirth", "value" => ($D["dateOfBirth"] ?? ""), "title" => "Date of Birth"),
    array("type" => "select", "name" => "gender", "value" => $genderOpt, "title" => "Gender"),
    array("type" => "select", "name" => "bloodGroup", "value" => $bloodGroupOpt, "title" => "Blood Group"),
    array("type" => "text", "name" => "emergencyContactName", "value" => ($D["emergencyContactName"] ?? ""), "title" => "Emergency Contact Name"),
    array("type" => "text", "name" => "emergencyContact", "value" => ($D["emergencyContact"] ?? ""), "title" => "Emergency Contact No"),
);

// Employment Details
$arrEmploymentForm = array(
    array("type" => "date", "name" => "dateOfJoining", "value" => ($D["dateOfJoining"] ?? ""), "title" => "Date of Joining"),
    array("type" => "text", "name" => "designation", "value" => ($D["designation"] ?? ""), "title" => "Designation"),
    array("type" => "text", "name" => "department", "value" => ($D["department"] ?? ""), "title" => "Department"),
    array("type" => "select", "name" => "employmentType", "value" => $empTypeOpt, "title" => "Employment Type"),
    array("type" => "select", "name" => "managerID", "value" => $strManagerOpt, "title" => "Reporting Manager"),
    array("type" => "text", "name" => "biometricID", "value" => ($D["biometricID"] ?? ""), "title" => "Biometric ID (Camsunit)"),
);

// Bank Details
$arrBankForm = array(
    array("type" => "text", "name" => "bankName", "value" => ($D["bankName"] ?? ""), "title" => "Bank Name"),
    array("type" => "text", "name" => "bankAccountNo", "value" => ($D["bankAccountNo"] ?? ""), "title" => "Account Number"),
    array("type" => "text", "name" => "bankIFSC", "value" => ($D["bankIFSC"] ?? ""), "title" => "IFSC Code"),
);

// ID Proofs
$arrIDForm = array(
    array("type" => "text", "name" => "panNo", "value" => ($D["panNo"] ?? ""), "title" => "PAN Number"),
    array("type" => "text", "name" => "aadhaarNo", "value" => ($D["aadhaarNo"] ?? ""), "title" => "Aadhaar Number"),
);

// Address
$arrAddressForm = array(
    array("type" => "textarea", "name" => "currentAddress", "value" => ($D["currentAddress"] ?? ""), "title" => "Current Address", "attrp" => 'rows="2"'),
    array("type" => "textarea", "name" => "permanentAddress", "value" => ($D["permanentAddress"] ?? ""), "title" => "Permanent Address", "attrp" => 'rows="2"'),
);

// Leave Settings
$arrLeaveForm = array(
    array("type" => "mxstring", "value" => '<span> Unauthorized : '.($D['unauthorized'] ?? 0).'</span>', "attrp" => ' class="c2"'),
    array("type" => "mxstring", "value" => '<a href="#" data-id='.($D['userID'] ?? 0).' class="btn fa-reset resetLeaveCount o"> RESET</a>', "attrp" => ' class="c3"'),
    array("type" => "text", "name" => "totalLeaves", "value" => ($D["totalLeaves"] ?? ""), "title" => "Total Leaves/Year", "validate" => "number,min:0,max:100","attrp" => ' class="c1"'),
    array("type" => "text", "name" => "paidLeaveDays", "value" => ($D["paidLeaveDays"] ?? 12), "title" => "Paid Leave Days/Year", "validate" => "number,min:0,max:50","attrp" => ' class="c1"'),
    array("type" => "text", "name" => "casualLeaveDays", "value" => ($D["casualLeaveDays"] ?? 12), "title" => "Casual Leave Days/Year", "validate" => "number,min:0,max:50","attrp" => ' class="c1"'),
    array("type" => "text", "name" => "sickLeaveDays", "value" => ($D["sickLeaveDays"] ?? 12), "title" => "Sick Leave Days/Year", "validate" => "number,min:0,max:50","attrp" => ' class="c1"'),
    array("type" => "text", "name" => "userPin", "value" => ($D["userPin"] ?? ""), "title" => "User Pin", "validate" => "number,min:0,maxlen:4","attrp" => ' class="c1"'),
    array("type" => "checkbox", "name" => "isLeaveManager", "value" => $D["isLeaveManager"] ?? 0, "title" => "Is Leave Manager", "nolabel" => true, "attrp" => ' class="c1"'),
    array("type" => "checkbox", "name" => "techIlliterate", "value" => $D["techIlliterate"] ?? 0, "title" => "Managed Employee (No Login)", "nolabel" => true, "attrp" => ' class="c1"'),
);

// Work Timing (Employee-specific overrides)
$arrWorkTimingForm = array(
    array("type" => "time", "name" => "workStartTime", "value" => ($D["workStartTime"] ?? ""), "title" => "Work Start Time", "attrp" => ' placeholder="Leave blank to use default"'),
    array("type" => "time", "name" => "workEndTime", "value" => ($D["workEndTime"] ?? ""), "title" => "Work End Time", "attrp" => ' placeholder="Leave blank to use default"'),
    array("type" => "text", "name" => "lateGraceMinutes", "value" => ($D["lateGraceMinutes"] ?? ""), "title" => "Late Grace (minutes)", "validate" => "number,min:0,max:60", "attrp" => ' placeholder="Leave blank to use default"'),
);

// Exit Details (only show for edit mode)
$arrExitForm = array();
if ($TPL->pageType == "edit") {
    $arrExitForm = array(
        array("type" => "date", "name" => "dateOfExit", "value" => ($D["dateOfExit"] ?? ""), "title" => "Date of Exit"),
        array("type" => "textarea", "name" => "exitReason", "value" => ($D["exitReason"] ?? ""), "title" => "Exit Reason", "attrp" => 'rows="2"'),
    );
}

$MXFRM = new mxForm();
?>
<style>
.hr-section { margin-bottom: 20px; }
.hr-section fieldset { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #fafafa; }
.hr-section fieldset p { font-weight: bold; color: #333; margin-bottom: 10px; font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 8px; }
.wrap-form.f50 { width: 49%; float: left; margin-right: 1%; }
.wrap-form.f50:nth-child(2n) { margin-right: 0; margin-left: 1%; }
@media (max-width: 768px) { .wrap-form.f50 { width: 100%; margin: 0 0 15px 0; } }
</style>

<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data" auto="false">

        <!-- Row 1: Basic Info & Role -->
        <div class="hr-section" style="overflow: hidden;">
            <div class="wrap-form f50">
                <fieldset>
                    <p>LOGIN DETAILS</p>
                    <ul class="tbl-form">
                        <?php echo $MXFRM->getForm($arrForm); ?>
                    </ul>
                </fieldset>
            </div>
            <div class="wrap-form f50">
                <fieldset>
                    <p>ROLE & PHOTO</p>
                    <ul class="tbl-form">
                        <?php echo $MXFRM->getForm($arrFormS); ?>
                    </ul>
                </fieldset>
                <fieldset>
                    <p>LEAVE SETTINGS</p>
                    <ul class="tbl-form">
                        <?php echo $MXFRM->getForm($arrLeaveForm); ?>
                    </ul>
                </fieldset>
                <fieldset>
                    <p>WORK TIMING (Employee Override)</p>
                    <ul class="tbl-form">
                        <?php echo $MXFRM->getForm($arrWorkTimingForm); ?>
                    </ul>
                    <small style="color:#666; display:block; margin-top:5px;">Leave blank to use system defaults</small>
                </fieldset>
            </div>
        </div>

        <!-- Row 2: Personal & Employment -->
        <div class="hr-section" style="overflow: hidden;">
            <div class="wrap-form f50">
                <fieldset>
                    <p>PERSONAL DETAILS</p>
                    <ul class="tbl-form">
                        <?php echo $MXFRM->getForm($arrPersonalForm); ?>
                    </ul>
                </fieldset>
            </div>
            <div class="wrap-form f50">
                <fieldset>
                    <p>EMPLOYMENT DETAILS</p>
                    <ul class="tbl-form">
                        <?php echo $MXFRM->getForm($arrEmploymentForm); ?>
                    </ul>
                </fieldset>
            </div>
        </div>

        <!-- Row 3: Bank & ID -->
        <div class="hr-section" style="overflow: hidden;">
            <div class="wrap-form f50">
                <fieldset>
                    <p>BANK DETAILS</p>
                    <ul class="tbl-form">
                        <?php echo $MXFRM->getForm($arrBankForm); ?>
                    </ul>
                </fieldset>
            </div>
            <div class="wrap-form f50">
                <fieldset>
                    <p>ID PROOFS</p>
                    <ul class="tbl-form">
                        <?php echo $MXFRM->getForm($arrIDForm); ?>
                    </ul>
                </fieldset>
            </div>
        </div>

        <!-- Row 4: Address -->
        <div class="hr-section" style="overflow: hidden;">
            <div class="wrap-form" style="width: 100%;">
                <fieldset>
                    <p>ADDRESS</p>
                    <ul class="tbl-form">
                        <?php echo $MXFRM->getForm($arrAddressForm); ?>
                    </ul>
                </fieldset>
            </div>
        </div>

        <?php if (!empty($arrExitForm)) { ?>
        <!-- Row 5: Exit Details (Edit only) -->
        <div class="hr-section" style="overflow: hidden;">
            <div class="wrap-form" style="width: 100%;">
                <fieldset style="background: #fff5f5; border-color: #ffcccc;">
                    <p style="color: #cc0000;">EXIT DETAILS</p>
                    <ul class="tbl-form">
                        <?php echo $MXFRM->getForm($arrExitForm); ?>
                    </ul>
                </fieldset>
            </div>
        </div>
        <?php } ?>

        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>
