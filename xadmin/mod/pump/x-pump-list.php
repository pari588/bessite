<?php
// START : search array
$arrSearch = array(
    array("type" => "text", "name" => "pumpID",  "title" => "#ID", "where" => "AND pumpID=?", "dtype" => "i"),
    array("type" => "text", "name" => "pumpTitle",  "title" => "Pump title", "where" => "AND pumpTitle LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "kwhp",  "title" => "KWHP", "where" => "AND kwhp LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "supplyPhase",  "title" => "Supply Phase", "where" => "AND supplyPhase LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "deliveryPipe",  "title" => "Delivery Pipe", "where" => "AND deliveryPipe LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "noOfStage",  "title" => "No Of Stage", "where" => "AND noOfStage LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "isi",  "title" => "ISI", "where" => "AND isi LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "mnre",  "title" => "MNRE", "where" => "AND mnre LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "pumpType",  "title" => "Pump Type", "where" => "AND pumpType LIKE CONCAT('%',?,'%')", "dtype" => "s")
);
// END
$categoryWhr = array("sql" => "status=? ", "types" => "i", "vals" => array(1));
//$categoryArr = getDataArray($DB->pre . "pump_category", "categoryPID", "categoryTitle", $categoryWhr);

$params = ["table" => $DB->pre . "pump_category", "key" => "categoryPID", "val" => "categoryTitle", "where" => $categoryWhr];
$categoryArr  = getDataArray($params);

$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);

$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT " . $MXMOD["PK"] . " FROM `" . $DB->pre . $MXMOD["TBL"] . "`  WHERE status=?" . $MXFRM->where;
$DB->dbQuery();
$MXTOTREC = $DB->numRows;

if (!$MXFRM->where && $MXTOTREC < 1)
    $strSearch = "";

echo $strSearch;
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <div class="wrap-data">
        <?php
        if ($MXTOTREC > 0) {
            $MXCOLS = array(
                array("#ID", "pumpID", ' width="1%" align="center"', true),
                array("Image", "pumpImage", '  width="1%" align="left"',"","nosort"),
                array("Pump Title", "pumpTitle", ' width="1%"  nowrap align="left"'),
                array("Category Name", "categoryPID", '  nowrap align="left"'),
                array("KWHP", "kwhp", ' width="2%" nowrap align="center"'),
                array("Supply Phase", "supplyPhase", ' width="2%" nowrap align="center"'),
                array("Delivery Pipe", "deliveryPipe", ' width="2%" nowrap align="center"'),
                array("No of Stage", "noOfStage", ' width="2%" nowrap align="center"'),
                array("ISI", "isi", ' width="2%" nowrap align="center"'),
                array("MNRE", "mnre", ' width="2%" nowrap align="center"'),
                array("Residential Pump Type", "pumpType", ' width="2%" nowrap align="center"'),

            );
            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT * FROM `" . $DB->pre . $MXMOD["TBL"] . "`WHERE status=? " . $MXFRM->where . mxOrderBy("pumpID DESC ") . mxQryLimit();
            $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr> <?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {
                        $d["categoryPID"] = $categoryArr['data'][$d["categoryPID"]]??"";
                        if ($d["pumpImage"] != "") {
                            $arrFile = explode(",", $d["pumpImage"]);
                            $d["pumpImage"] = getFile(array("path" => "pump/" . $arrFile[0], "title" => $d["pumpImage"]));
                        }
                    ?>
                        <tr> <?php echo getMAction("mid", $d["pumpID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2];
                                    ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if (isset($v[3])&& $v[3] != "") {
                                        echo getViewEditUrl("id=" . $d["pumpID"], $d[$v[1]]);
                                    } else {
                                        echo $d[$v[1]] ?? "";
                                    }
                                    ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        <?php } else { ?>
            <div class="no-records">No records found</div>
        <?php } ?>
    </div>
</div>