<?php
$id = 0;
$D = array();
$arrDD = array();
if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"] ?? 0);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . $MXMOD["TBL"] . "` WHERE status=? AND `" . $MXMOD["PK"] . "` =?";
    $D = $DB->dbRow();

    //Getting details data
    $DB->vals = array($id);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM " . $DB->pre . "motor_detail  WHERE " . $MXMOD["PK"] . "=?";
    $data = $DB->dbRows();
    foreach ($data as $k => $v) {
        $arrDD[$k] = $v;
    }
    //End.
}
if (count($arrDD) < 1) {
    $v = array();
    $arrDD[] = $v;
}

//showing category of motor
$DB->sql = "SELECT categoryMID,categoryTitle,parentID FROM `" . $DB->pre . "motor_category` where status=1 ORDER BY categoryTitle";
$arrCats = $DB->dbRows();
$strOpt = getTreeDD($arrCats, "categoryMID", "categoryTitle", "parentID", $D['categoryMID'] ?? 0, array(0));
//$whrArr = array("sql" => "status=?", "types" => "i", "vals" => array("1"));
//$strOpt = getTableDD(["table" => $DB->pre . "motor_category", "key" => "categoryMID", "val" => "categoryTitle", "selected" => ($D['categoryMID'] ?? ""), "where" =>  $whrArr]);


$arrForm = array(
    array("type" => "text", "name" => "motorTitle", "value" => $D["motorTitle"] ?? "", "title" => "Motor Title", "validate" => "required"),
    array("type" => "text", "name" => "motorSubTitle", "value" => $D["motorSubTitle"] ?? "", "title" => "Motor  Sub Title"),
    array("type" => "file", "name" => "motorImage", "value" => array($D["motorImage"] ?? "", $id ?? 0), "title" => "Image", "params" => array("EXT" => "jpg|jpeg|png|gif")),
    array("type" => "hidden", "name" => "oldProductTitle", "value" => $D["motorTitle"] ?? "")
);
$arrForm1 = array(
    array("type" => "select", "name" => "categoryMID", "value" => $strOpt, "title" => "Select Category", "validate" => "required"),
    array("type" => "editor", "name" => "motorDesc", "value" => $D["motorDesc"] ?? "", "title" => "Motor Description", "params" => array("toolbar" => "basic", "height" => 150), "attrp" => ' width="40%"'),
);
$arrForm2 = array(
    array("type" => "hidden", "name" => "motorDID"),
    array("type" => "text", "name" => "descriptionTitle", "title" => "Description Title"),
    array("type" => "text", "name" => "descriptionOutput", "title" => "Output"),
    array("type" => "text", "name" => "descriptionVoltage", "title" => "Voltage"),
    array("type" => "text", "name" => "descriptionFrameSize", "title" => "Frame Size"),
    array("type" => "text", "name" => "descriptionStandard", "title" => "Standard")
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
            <ul class="tbl-form">
                <?php
                echo $MXFRM->getForm($arrForm1);
                ?>
            </ul>
        </div>
        <div class="wrap-form">
            <h2 class="form-head">Motor Details</h2>
            <?php
            echo $MXFRM->getFormG(array("flds" => $arrForm2, "vals" => $arrDD, "type" => 0, "addDel" => true, "class" => " small"));
            ?>
        </div>
        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>