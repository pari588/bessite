<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/js/x-salary-advance.inc.js'); ?>"></script>

<?php
// Status dropdown
$statusOptions = '
    <option value="">-- All --</option>
    <option value="pending">Pending</option>
    <option value="approved">Approved</option>
    <option value="rejected">Rejected</option>
    <option value="completed">Completed</option>
';

// Search array
$arrSearch = array(
    array("type" => "text", "name" => "displayName", "title" => "Employee", "where" => "AND U.displayName LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "select", "name" => "advanceStatus", "value" => $statusOptions, "title" => "Status", "where" => "AND SA.advanceStatus=?", "dtype" => "s"),
);

$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);

// Build query
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT SA.*, U.displayName, U.employeeCode
            FROM `" . $DB->pre . "salary_advance` AS SA
            LEFT JOIN `" . $DB->pre . "x_admin_user` AS U ON SA.userID = U.userID
            WHERE SA.status=? " . $MXFRM->where;
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
                array("#ID", "advanceID", ' width="1%" align="center"', true),
                array("Employee", "displayName", ' width="15%" align="left"'),
                array("Amount", "advanceAmount", ' width="10%" align="right"'),
                array("Date", "advanceDate", ' width="10%" align="center"'),
                array("Monthly EMI", "monthlyDeduction", ' width="10%" align="right"'),
                array("Recovered", "recovered", ' width="15%" align="center"', '', 'nosort'),
                array("Status", "advanceStatus", ' width="10%" align="center"', '', 'nosort'),
                array("Actions", "actions", ' width="15%" align="center"', '', 'nosort'),
            );

            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT SA.*, U.displayName, U.employeeCode
                        FROM `" . $DB->pre . "salary_advance` AS SA
                        LEFT JOIN `" . $DB->pre . "x_admin_user` AS U ON SA.userID = U.userID
                        WHERE SA.status=? " . $MXFRM->where . " " . mxOrderBy(" SA.advanceDate DESC ") . mxQryLimit();
            $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr><?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {
                        $originalStatus = $d['advanceStatus'];

                        // Keep original values
                        $advanceAmt = floatval($d['advanceAmount']);
                        $totalDeducted = floatval($d['totalDeducted']);

                        // Format amounts
                        $d['advanceAmount'] = '<strong>' . number_format($advanceAmt, 0) . '</strong>';
                        $d['monthlyDeduction'] = $d['monthlyDeduction'] ? number_format($d['monthlyDeduction'], 0) : '-';
                        $d['advanceDate'] = date('d M Y', strtotime($d['advanceDate']));

                        // Recovery progress
                        $percentage = $advanceAmt > 0 ? min(100, ($totalDeducted / $advanceAmt) * 100) : 0;
                        $d['recovered'] = number_format($totalDeducted, 0) . ' / ' . number_format($advanceAmt, 0) . ' (' . round($percentage) . '%)';

                        // Status badge using xadmin label classes
                        $statusClass = 'label label-default';
                        if ($originalStatus == 'pending') $statusClass = 'label label-warning';
                        elseif ($originalStatus == 'approved') $statusClass = 'label label-success';
                        elseif ($originalStatus == 'rejected') $statusClass = 'label label-danger';
                        elseif ($originalStatus == 'completed') $statusClass = 'label label-info';
                        $d['advanceStatus'] = '<span class="' . $statusClass . '">' . ucfirst($originalStatus) . '</span>';

                        // Actions
                        $actions = '';
                        if ($originalStatus == 'pending') {
                            $actions .= '<button class="btn btn-xs btn-success approve-btn" data-id="' . $d['advanceID'] . '">Approve</button> ';
                            $actions .= '<button class="btn btn-xs btn-danger reject-btn" data-id="' . $d['advanceID'] . '">Reject</button>';
                        }
                        $d['actions'] = $actions ?: '-';
                    ?>
                        <tr>
                            <?php echo getMAction("mid", $d["advanceID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2]; ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if (isset($v[3]) && $v[3] != '') {
                                        echo getViewEditUrl("id=" . $d["advanceID"], $d[$v[1]]);
                                    } else {
                                        echo $d[$v[1]] ?? "-";
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="alert alert-info">No salary advances found</p>
        <?php } ?>
    </div>
</div>
