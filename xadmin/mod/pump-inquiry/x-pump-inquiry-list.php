<?php
$arrSearch = array(
    array("type" => "text", "name" => "pumpInquiryID",  "title" => "#ID", "where" => "AND pumpInquiryID=?", "dtype" => "i", "attr" => "style='width:50px;'"),
    array("type" => "text", "name" => "userName",  "title" => "Name", "where" => "AND userName LIKE CONCAT('%',?,'%')", "dtype" => "s", "attr" => "style='width:110px;'"),
    array("type" => "text", "name" => "userEmail",  "title" => "Email", "where" => "AND userEmail LIKE CONCAT('%',?,'%')", "dtype" => "s", "attr" => "style='width:110px;'"),
    array("type" => "text", "name" => "userMobile",  "title" => "Mobile", "where" => "AND userMobile LIKE CONCAT('%',?,'%')", "dtype" => "s", "attr" => "style='width:110px;'"),
    array("type" => "date", "name" => "fromDate", "title" => "From Date", "where" => "AND DATE(createdDate) >=?", "dtype" => "s", "attr" => "style='width:110px;'"),
    array("type" => "date", "name" => "toDate", "title" => "To Date", "where" => "AND DATE(createdDate) <=?", "dtype" => "s", "attr" => "style='width:110px;'")
);
// END
$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT " . $MXMOD["PK"] . " FROM `" . $DB->pre . $MXMOD["TBL"] . "`  WHERE status=?"  . $MXFRM->where;
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
                array("#ID", "pumpInquiryID", ' width="1%" align="center"'),
                array("Name", "userName", ' align="left"'),
                array("Email", "userEmail", ' align="left"'),
                array("Mobile", "userMobile", ' align="left"'),
                array("Enquiry", "enquiryText", ' align="left"'),
                array("Date", "createdDate", ' align="left"')
            );
            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT *  FROM `" . $DB->pre . $MXMOD["TBL"] . "`WHERE status=? "  . $MXFRM->where . mxOrderBy(" pumpInquiryID DESC ") . mxQryLimit();
            $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr> <?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {  ?>
                        <tr> <?php echo getMAction("mid", $d["pumpInquiryID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2];
                                    ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if (isset($v[3])) {
                                        echo getViewEditUrl("id=" . $d["pumpInquiryID"], $d[$v[1]]);
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