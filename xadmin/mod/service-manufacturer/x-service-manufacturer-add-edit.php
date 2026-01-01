<?php
$D = array(
    "name" => "",
    "tagline" => "",
    "description" => "",
    "logo" => "",
    "cardColor" => "#003566",
    "phoneNumber" => "",
    "whatsappNumber" => "",
    "email" => "",
    "website" => "",
    "address" => "",
    "sortOrder" => 0
);
$id = 0;
if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"] ?? 0);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . $MXMOD["TBL"] . "` WHERE status=? AND manufacturerID=?";
    $D = $DB->dbRow();
}

$arrForm1 = array(
    array("type" => "text", "name" => "name", "value" => $D["name"] ?? "", "title" => "Manufacturer Name", "validate" => "required", "attrp" => " class='c1'", "attr" => "placeholder='e.g., Crompton, CG Power'"),
    array("type" => "text", "name" => "tagline", "value" => $D["tagline"] ?? "", "title" => "Tagline", "attr" => "placeholder='e.g., Consumer Electricals, Industrial Solutions'"),
    array("type" => "textarea", "name" => "description", "value" => $D["description"] ?? "", "title" => "Description", "attr" => 'class="text" rows="4" placeholder="Brief description of the manufacturer and their products..."'),
);

$arrForm2 = array(
    array("type" => "file", "name" => "logo", "value" => array($D["logo"] ?? "", $id), "title" => "Logo"),
    array("type" => "text", "name" => "cardColor", "value" => $D["cardColor"] ?? "#003566", "title" => "Card Color (Hex)", "attr" => "placeholder='#003566'"),
    array("type" => "text", "name" => "sortOrder", "value" => $D["sortOrder"] ?? 0, "title" => "Sort Order", "attr" => "placeholder='0'"),
);

$arrFormContact = array(
    array("type" => "text", "name" => "phoneNumber", "value" => $D["phoneNumber"] ?? "", "title" => "Phone / Helpline", "attr" => "placeholder='e.g., 9228880505 or 022-67592439'"),
    array("type" => "text", "name" => "whatsappNumber", "value" => $D["whatsappNumber"] ?? "", "title" => "WhatsApp Number", "attr" => "placeholder='e.g., +91 7428713838'"),
    array("type" => "text", "name" => "email", "value" => $D["email"] ?? "", "title" => "Email", "attr" => "placeholder='e.g., support@example.com'"),
    array("type" => "text", "name" => "website", "value" => $D["website"] ?? "", "title" => "Website URL", "attr" => "placeholder='https://www.example.com'"),
    array("type" => "textarea", "name" => "address", "value" => $D["address"] ?? "", "title" => "Corporate Address", "attr" => 'class="text" rows="3" placeholder="Full corporate office address..."'),
);

$MXFRM = new mxForm();
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data">
        <div class="wrap-form f60">
            <h3 style="margin: 0 0 15px; padding: 10px 15px; background: #f5f5f5; border-radius: 4px;">Basic Information</h3>
            <ul class="tbl-form">
                <?php echo $MXFRM->getForm($arrForm1); ?>
            </ul>

            <h3 style="margin: 20px 0 15px; padding: 10px 15px; background: #f5f5f5; border-radius: 4px;">Contact Information</h3>
            <ul class="tbl-form">
                <?php echo $MXFRM->getForm($arrFormContact); ?>
            </ul>
        </div>
        <div class="wrap-form f40">
            <h3 style="margin: 0 0 15px; padding: 10px 15px; background: #f5f5f5; border-radius: 4px;">Display Settings</h3>
            <ul class="tbl-form">
                <?php echo $MXFRM->getForm($arrForm2); ?>
            </ul>
        </div>
        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>
