<?php
$arrSearch = array(
    array("type" => "text", "name" => "userID", "title" => "#ID", "where" => "AND userID=?", "dtype" => "i"),
    array("type" => "text", "name" => "userName", "title" => "User Name", "where" => "AND userName LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "userEmail", "title" => "User Email", "where" => "AND userEmail LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "userMobileNo", "title" => "User Mobile", "where" => "AND userMobileNo LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "userLoginOTP", "title" => "Login OTP", "where" => "AND userLoginOTP LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "userCity", "title" => "User City", "where" => "AND userCity LIKE CONCAT('%',?,'%')", "dtype" => "s")
);
$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT " . $MXMOD['PK'] . " FROM `" . $DB->pre . $MXMOD["TBL"] . "` 
            WHERE status=?" . $MXFRM->where;
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
            $MXCOLS = array(
                array("#ID", "userID", ' width="1%" align="center" title="ID"', true),
                array("Username", "userName", ' width="12%" align="left" '),
                array("User Email", "userEmail", ' width="12%" align="left" '),
                array("User Mobile", "userMobileNo", ' width="12%" align="left" '),
                array("Login OTP", "userLoginOTP", ' width="12%" align="left" '),
                array("City", "userCity", ' width="10%" align="left" '),
                array("Availability", "currentLeave", ' width="14%" align="center" ')
            );
            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            // Correlated sub-select (not a JOIN) pulls the driver's CURRENT leave, if any.
            // A JOIN would make the unqualified `userID` in the search WHERE ambiguous.
            $DB->sql = "SELECT u.userID,u.userName,u.userEmail,u.userMobileNo,u.userLoginOTP,u.userCity,u.userType,
                        (SELECT CONCAT(IFNULL(l.toDate,''), '|', IFNULL(l.leaveType,''))
                           FROM `" . $DB->pre . "driver_leave` l
                          WHERE l.userID = u.userID AND l.status = 1 AND l.isReturned = 0
                            AND l.fromDate <= CURDATE()
                            AND (l.toDate IS NULL OR l.toDate >= CURDATE())
                          ORDER BY l.fromDate DESC LIMIT 1) AS currentLeave
                        FROM `" . $DB->pre . $MXMOD["TBL"] . "` u
                        WHERE u.status=?" . $MXFRM->where . mxWhere() . mxOrderBy(" u.userID DESC ") . " LIMIT $MXOFFSET,$MXSHOWREC";
            $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="2" cellpadding="6" class="tbl-list">
                <thead>
                    <tr>
                        <?php echo getListTitle($MXCOLS); ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {
                    ?>
                        <tr>
                            <?php echo getMAction("mid", $d["userID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td<?php echo $v[2]; ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if ($v[1] === "currentLeave") {
                                        // Availability badge: amber when on leave, green when available.
                                        if (!empty($d["currentLeave"])) {
                                            list($retDate, $lvType) = array_pad(explode("|", $d["currentLeave"]), 2, "");
                                            $back = $retDate ? "back " . date("j M Y", strtotime($retDate)) : "no return date";
                                            echo '<span style="display:inline-block;padding:3px 9px;border-radius:11px;background:#fff3cd;color:#8a6100;border:1px solid #ffe08a;font-size:11px;font-weight:600;white-space:nowrap;" title="'
                                                . htmlspecialchars(trim($lvType . " — expected " . $back), ENT_QUOTES, "UTF-8") . '">On Leave</span>'
                                                . '<div style="font-size:10px;color:#8a8f96;margin-top:2px;white-space:nowrap;">exp. ' . htmlspecialchars($back, ENT_QUOTES, "UTF-8") . '</div>';
                                        } else {
                                            echo '<span style="display:inline-block;padding:3px 9px;border-radius:11px;background:#e7f6ec;color:#1c7a3e;border:1px solid #b9e3c6;font-size:11px;font-weight:600;">Available</span>';
                                        }
                                    } elseif (isset($v[3])) {
                                        echo getViewEditUrl("id=" . $d["userID"], $d[$v[1]]);
                                    } else {
                                        echo $d[$v[1]];
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