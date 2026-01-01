<?php
$arrSearch = array(
    array("type" => "text", "name" => "manufacturerID", "title" => "#ID", "where" => "AND manufacturerID= ? ", "dtype" => "i"),
    array("type" => "text", "name" => "name", "title" => "Name", "where" => "AND name LIKE CONCAT('%',?,'%')", "dtype" => "s"),
);
$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT A." . $MXMOD['PK'] . " FROM `" . $DB->pre . $MXMOD["TBL"] . "` AS A
            WHERE A.status=? " . $MXFRM->where . mxWhere("A.");
$DB->dbQuery();
$MXTOTREC = $DB->numRows;
if ($MXTOTREC < 1) {
    if ((!isset($MXFRM->where) || $MXFRM->where == "")) {
        $strSearch = "";
    }
}
echo $strSearch;
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <div class="wrap-data">
        <?php
        if ($MXTOTREC > 0) {
            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT A.* FROM `" . $DB->pre . $MXMOD["TBL"] . "` AS A
            WHERE A.status=?" . $MXFRM->where . mxWhere("A.") . mxOrderBy("A.sortOrder ASC, A.manufacturerID ASC") . mxQryLimit();
            $DB->dbRows();
            $MXCOLS = array(
                array("#ID", "manufacturerID", ' width="1%" align="center"', true),
                array("Logo", "logo", ' width="1%" align="center" nowrap'),
                array("Name", "name", ' align="left"'),
                array("Tagline", "tagline", ' align="left"'),
                array("Phone", "phoneNumber", ' nowrap="nowrap" align="left"'),
                array("Email", "email", ' nowrap="nowrap" align="left"'),
                array("Sort", "sortOrder", ' width="1%" align="center"'),
            );
        ?>
            <table border="0" cellspacing="0" width="100%" cellpadding="8" class="tbl-list">
                <thead>
                    <tr>
                        <?php echo getListTitle($MXCOLS); ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {
                        if ($d["logo"] != "") {
                            $d["logo"] = getFile(array("path" => "service-manufacturer/" . $d["logo"]));
                        }
                    ?>
                        <tr>
                            <?php echo getMAction("mid", $d["manufacturerID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2]; ?>>
                                    <?php if (isset($v[3]) && $v[3]) {
                                        echo getViewEditUrl("id=" . $d["manufacturerID"], $d[$v[1]]);
                                    } else {
                                        echo $d[$v[1]];
                                    } ?>
                                </td>
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
