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

    $DB->vals = array($id);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM " . $DB->pre . "pump_detail  WHERE " . $MXMOD["PK"] . "=?";
    $data = $DB->dbRows();

    foreach ($data as $k => $v) {
        $arrDD[$k] = $v;
    }
}

if (count($arrDD) < 1) {
    $v = array();
    $arrDD[] = $v;
}

//showing category of Pumps
$DB->sql = "SELECT categoryPID,categoryTitle,parentID FROM `" . $DB->pre . "pump_category` where status=1 ORDER BY categoryTitle ";

$arrCats = $DB->dbRows();
$strOpt = getTreeDD($arrCats, "categoryPID", "categoryTitle", "parentID", $D['categoryPID'] ?? 0, array(0));

//$whrArr = array("sql" => "status=?", "types" => "i", "vals" => array("1"));
//$strOpt = getTableDD(["table" => $DB->pre . "pump_category", "key" => "categoryPID", "val" => "categoryTitle", "selected" => ($D['categoryPID'] ?? ""), "where" =>  $whrArr]);


$arrForm = array(
    array("type" => "text", "name" => "pumpTitle", "value" => $D["pumpTitle"] ?? "", "title" => "Pump Title", "validate" => "required"),
    array("type" => "select", "name" => "categoryPID", "value" => $strOpt, "title" => "Select Category", "validate" => "required"),
    array("type" => "editor", "name" => "pumpFeatures", "value" => $D["pumpFeatures"] ?? "", "title" => "Features", "params" => array("toolbar" => "basic", "height" => 150), "attrp" => ' width="40%"'),
    array("type" => "file", "name" => "pumpImage", "value" => array($D["pumpImage"] ?? "", $id ?? 0), "title" => "Image", "params" => array("EXT" => "jpg|jpeg|png|gif"), "attrp" => ' class="c1"'),
    array("type" => "hidden", "name" => "oldProductTitle", "value" => $D["pumpTitle"] ?? "")
);
$arrForm1 = array(
    array("type" => "text", "name" => "kwhp", "value" => $D["kwhp"] ?? "", "title" => "Kwhp"),
    array("type" => "text", "name" => "supplyPhase", "value" => $D["supplyPhase"] ?? "", "title" => "Supply Phase"),
    array("type" => "text", "name" => "deliveryPipe", "value" => $D["deliveryPipe"] ?? "", "title" => "Delivery Pipe"),
    array("type" => "text", "name" => "noOfStage", "value" => $D["noOfStage"] ?? "", "title" => "No of Stage"),
    array("type" => "text", "name" => "isi", "value" => $D["isi"] ?? "", "title" => "ISI"),
    array("type" => "text", "name" => "mnre", "value" => $D["mnre"] ?? "", "title" => "MNRE"),
    array("type" => "text", "name" => "pumpType", "value" => $D["pumpType"] ?? "", "title" => "Pump Type"),
);
$arrForm2 = array(
    array("type" => "hidden", "name" => "pumpDID"),
    array("type" => "text", "name" => "categoryref", "title" => "Catref"),
    array("type" => "text", "name" => "powerKw", "title" => "Power (Kw)"),
    array("type" => "text", "name" => "powerHp", "title" => "Power (HP)"),
    array("type" => "text", "name" => "supplyPhaseD", "title" => "Supply Phase"),
    array("type" => "text", "name" => "pipePhase", "title" => "Pipe Size (mm)"),
    array("type" => "text", "name" => "noOfStageD", "title" => "No. of Stages"),
    array("type" => "text", "name" => "headRange", "title" => "Head Range (m)"),
    array("type" => "text", "name" => "dischargeRange", "title" => "Discharge Range"),
    array("type" => "text", "name" => "mrp", "title" => "MRP (INR)"),
    array("type" => "text", "name" => "warrenty", "title" => "Warranty"),

);

$MXFRM = new mxForm();
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data">
        <div class="wrap-form f50">
            <h2 class="form-head">Basic Information</h2>
            <ul class="tbl-form">
                <?php
                echo $MXFRM->getForm($arrForm);
                ?>
            </ul>
        </div>
        <div class="wrap-form f50">
            <h2 class="form-head">Additional Information</h2>
            <ul class="tbl-form">
                <?php
                echo $MXFRM->getForm($arrForm1);
                ?>
            </ul>
        </div>
        <div class="wrap-form">
            <h2 class="form-head">Specification</h2>
            <?php
            echo $MXFRM->getFormG(array("flds" => $arrForm2, "vals" => $arrDD, "type" => 0, "addDel" => true, "class" => " small"));
            ?>
        </div>
        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>