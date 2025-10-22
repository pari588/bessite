<?php
// Mod type dropdown for searching.
$ModTypeArr = ["1" => "Motor", "2" => "Pump", "4" => "Other"];
//$modTypeDD = getArrayDD($ModTypeArr, $_GET["modType"] ?? 0);
$modTypeDD = getArrayDD(["data" => array("data" => $ModTypeArr), "selected" => ($_GET["modType"] ?? 0)]);

// End. 
$arrSearch = array(
    array("type" => "text", "name" => "userID",  "title" => "#ID", "where" => "AND userID=?", "dtype" => "i"),
    array("type" => "select", "name" => "modType", "value" => $modTypeDD, "title" => "Select Mod", "where" => "AND modType=?", "dtype" => "i"),
    array("type" => "text", "name" => "categoryTitle",  "title" => "category Title", "where" => "AND categoryTitle LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "productTitle",  "title" => "Product Title", "where" => "AND productTitle LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "userName",  "title" => "User Name", "where" => "AND userName LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "userLastName",  "title" => "User Last Name", "where" => "AND userLastName LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "userEmail",  "title" => "User Email", "where" => "AND userEmail LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "userSubject",  "title" => "Subject", "where" => "AND userSubject LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    //array("type" => "date", "name" => "fromDate", "title" => "From Date", "where" => "AND DATE(dateAdded) >=?", "dtype" => "s", "attr" => "style='width:160px;'"),
    //array("type" => "date", "name" => "toDate", "title" => "To Date", "where" => "AND DATE(dateAdded) <=?", "dtype" => "s", "attr" => "style='width:140px;'")
);

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
    <?php echo getPageNav('', '', array("add")); ?>
    <div class="wrap-data">
        <?php
        if ($MXTOTREC > 0) {
            $MXCOLS = array(
                array("#ID", "userID", ' width="4%" align="center"'),
                array("Mod Type", "modType", ' width="16%" nowrap align="left"'),
                array("Category Title", "categoryTitle", ' width="16%" nowrap align="left"'),
                array("Product Title", "productTitle", ' width="16%" nowrap align="left"'),
                array("User Name", "userName", '  width="16%" align="left"'),
                array("User Last Name", "userLastName", ' width="16%"  nowrap align="left"'),
                array("Subject", "userSubject", ' width="16%"  nowrap align="left"'),
                array("Message", "userMessage", ' width="16%" nowrap align="left"'),
                array("User Email", "userEmail", ' width="16%"  nowrap align="left"'),
                //array("Date Added", "dateAdded", ' width="16%"  nowrap align="left"')
            );
            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT * FROM `" . $DB->pre . $MXMOD["TBL"] . "`WHERE status=? " . $MXFRM->where . mxOrderBy("userID DESC ") . mxQryLimit();
            $rt =  $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr> <?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {
                        $d["modType"] = $ModTypeArr[$d["modType"]] ?? "";
                    ?>
                        <tr> <?php echo getMAction("mid", $d["userID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2];
                                    ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if (isset($v[3])) {
                                        echo getViewEditUrl("id=" . $d["userID"], $d[$v[1]]);
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