<?php
// Driver dropdown for the search bar
$driverOptions = array("" => "All Drivers");
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT userID, userName FROM `" . $DB->pre . "user` WHERE status=? ORDER BY userName";
$drv = $DB->dbRows();
if (is_array($drv)) {
    foreach ($drv as $v) $driverOptions[$v["userID"]] = $v["userName"];
}
$driverDD = getArrayDD(array("data" => array("data" => $driverOptions), "selected" => ($_GET["userID"] ?? "")));

$statusOptions = array("" => "All", "0" => "Currently on leave", "1" => "Returned");
$statusDD = getArrayDD(array("data" => array("data" => $statusOptions), "selected" => ($_GET["isReturned"] ?? "")));

// NOTE: every WHERE fragment is qualified with the `dl` alias because the queries
// below alias the table — an unqualified column would break once aliased.
$arrSearch = array(
    array("type" => "select", "name" => "userID",     "value" => $driverDD, "title" => "Driver",  "where" => "AND dl.userID=?",     "dtype" => "i"),
    array("type" => "select", "name" => "isReturned", "value" => $statusDD, "title" => "Status",  "where" => "AND dl.isReturned=?", "dtype" => "i"),
);
$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);

$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT dl.driverLeaveID FROM `" . $DB->pre . $MXMOD["TBL"] . "` dl
            WHERE dl.status=?" . $MXFRM->where;
$DB->dbQuery();
$MXTOTREC = $DB->numRows;
if ($MXTOTREC < 1 && (!isset($MXFRM->where) || $MXFRM->where == "")) $strSearch = "";
echo $strSearch;
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <div class="wrap-data">
        <?php
        if ($MXTOTREC > 0) {
            $MXCOLS = array(
                array("Driver", "userName", ' width="20%" align="left"', true),
                array("From", "fromDate", ' width="13%" align="center"'),
                array("Expected Return", "toDate", ' width="15%" align="center"'),
                array("Type", "leaveType", ' width="15%" align="left"'),
                array("Status", "isReturned", ' width="14%" align="center"'),
                array("Remarks", "remarks", ' width="23%" align="left"'),
            );
            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT dl.driverLeaveID, dl.fromDate, dl.toDate, dl.leaveType, dl.remarks, dl.isReturned, u.userName
                        FROM `" . $DB->pre . $MXMOD["TBL"] . "` dl
                        LEFT JOIN `" . $DB->pre . "user` u ON u.userID = dl.userID
                        WHERE dl.status=?" . $MXFRM->where . mxOrderBy(" dl.fromDate DESC ") . " LIMIT $MXOFFSET,$MXSHOWREC";
            $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="2" cellpadding="6" class="tbl-list">
                <thead>
                    <tr><?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php foreach ($DB->rows as $d) { ?>
                        <tr>
                            <?php echo getMAction("mid", $d["driverLeaveID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td<?php echo $v[2]; ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if ($v[1] === "isReturned") {
                                        if ($d["isReturned"]) {
                                            echo '<span style="display:inline-block;padding:3px 9px;border-radius:11px;background:#e7f6ec;color:#1c7a3e;border:1px solid #b9e3c6;font-size:11px;font-weight:600;">Returned</span>';
                                        } else {
                                            echo '<span style="display:inline-block;padding:3px 9px;border-radius:11px;background:#fff3cd;color:#8a6100;border:1px solid #ffe08a;font-size:11px;font-weight:600;">On Leave</span>';
                                        }
                                    } elseif ($v[1] === "fromDate" || $v[1] === "toDate") {
                                        echo !empty($d[$v[1]]) ? date("j M Y", strtotime($d[$v[1]])) : '<span style="color:#9aa2ab;">&mdash;</span>';
                                    } elseif (isset($v[3])) {
                                        echo getViewEditUrl("id=" . $d["driverLeaveID"], $d[$v[1]]);
                                    } else {
                                        echo htmlspecialchars((string)($d[$v[1]] ?? ""), ENT_QUOTES, 'UTF-8');
                                    }
                                    ?>
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
