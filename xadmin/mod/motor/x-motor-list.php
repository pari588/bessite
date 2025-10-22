<?php
// START : search array
$arrSearch = array(
    array("type" => "text", "name" => "motorID",  "title" => "#ID", "where" => "AND motorID=?", "dtype" => "i"),
    array("type" => "text", "name" => "motorTitle",  "title" => "Motors title", "where" => "AND motorTitle LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "motorSubTitle",  "title" => "Motors Sub title", "where" => "AND motorSubTitle LIKE CONCAT('%',?,'%')", "dtype" => "s")

);
// END
$categoryWhr = array("sql" => "status=? ", "types" => "i", "vals" => array(1));
//$categoryArr = getDataArray($DB->pre . "motor_category", "categoryMID", "categoryTitle", $categoryWhr);
$params = ["table" => $DB->pre . "motor_category", "key" => "categoryMID", "val" => "categoryTitle", "where" => $categoryWhr];
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
                array("#ID", "motorID", ' width="1%" align="center"', true),
                array("Image", "motorImage", ' width="1%" align="center"', "", "nosort"),
                array("Motor Title", "motorTitle", ' width="20%" nowrap align="left"'),
                array("Category Name", "categoryMID", ' width="20%" align="left"'),
                array("Motor Sub Title", "motorSubTitle", ' width="36%" align="left"'),
            );
            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT motorID,motorTitle,motorSubTitle,motorImage,categoryMID  FROM `" . $DB->pre . $MXMOD["TBL"] . "`WHERE status=? " . $MXFRM->where . mxOrderBy(" motorID DESC ") . mxQryLimit();
            $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr> <?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {
                        $d["categoryMID"] = $categoryArr['data'][$d["categoryMID"]] ?? "";
                        if ($d["motorImage"] != "") {
                            $arrFile = explode(",", $d["motorImage"]);
                            $d["motorImage"] = getFile(array("path" => "motor/" . $arrFile[0], "title" => $d["motorImage"]));
                        }
                    ?>
                        <tr> <?php echo getMAction("mid", $d["motorID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2];
                                    ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if (isset($v[3])&& $v[3] != "") {
                                        echo getViewEditUrl("id=" . $d["motorID"], $d[$v[1]]);
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