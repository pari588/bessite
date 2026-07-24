<?php
// Search array
$arrSearch = array(
    array("type" => "text", "name" => "displayName", "title" => "Employee Name", "where" => "AND U.displayName LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "text", "name" => "employeeCode", "title" => "Employee Code", "where" => "AND U.employeeCode LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "select", "name" => "activeOnly", "value" => '
        <option value="1" selected>Active Structures Only</option>
        <option value="0">All Structures</option>
    ', "title" => "Status", "where" => "", "dtype" => "i"),
);

$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);

// Build condition for active only
$activeCnd = '';
if (!isset($_GET['activeOnly']) || $_GET['activeOnly'] == '1') {
    $activeCnd = ' AND (SS.effectiveTo IS NULL OR SS.effectiveTo >= CURDATE())';
}

// Build query
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT SS.*, U.displayName, U.employeeCode, U.department, U.designation
            FROM `" . $DB->pre . "salary_structure` AS SS
            LEFT JOIN `" . $DB->pre . "x_admin_user` AS U ON SS.userID = U.userID
            WHERE SS.status=? " . $MXFRM->where . $activeCnd;
$DB->dbQuery();
$MXTOTREC = $DB->numRows;

if (!$MXFRM->where && $MXTOTREC < 1)
    $strSearch = "";

echo $strSearch;
?>

<style>
.salary-amount { font-weight: bold; color: #28a745; }
.salary-component { color: #666; font-size: 12px; }
.structure-active { background: #d4edda; }
.structure-inactive { background: #f8d7da; opacity: 0.7; }
</style>

<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <div class="wrap-data">
        <?php
        if ($MXTOTREC > 0) {
            $MXCOLS = array(
                array("#ID", "structureID", ' width="1%" align="center"', true),
                array("Employee", "displayName", ' width="20%" align="left"'),
                array("Designation", "designation", ' width="15%" align="left"'),
                array("Effective From", "effectiveFrom", ' width="10%" align="center"'),
                array("Effective To", "effectiveTo", ' width="10%" align="center"'),
                array("Basic", "basicSalary", ' width="10%" align="right"'),
                array("HRA", "hra", ' width="8%" align="right"'),
                array("Gross Salary", "grossSalary", ' width="12%" align="right"'),
            );

            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT SS.*, U.displayName, U.employeeCode, U.department, U.designation
                        FROM `" . $DB->pre . "salary_structure` AS SS
                        LEFT JOIN `" . $DB->pre . "x_admin_user` AS U ON SS.userID = U.userID
                        WHERE SS.status=? " . $MXFRM->where . $activeCnd . " " . mxOrderBy(" SS.effectiveFrom DESC, U.displayName ASC ") . mxQryLimit();
            $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr><?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {
                        // Check if active
                        $isActive = ($d['effectiveTo'] === null || strtotime($d['effectiveTo']) >= strtotime(date('Y-m-d')));
                        $rowClass = $isActive ? 'structure-active' : 'structure-inactive';

                        // Format dates
                        $d['effectiveFrom'] = date('d M Y', strtotime($d['effectiveFrom']));
                        $d['effectiveTo'] = $d['effectiveTo'] ? date('d M Y', strtotime($d['effectiveTo'])) : 'Current';

                        // Format amounts
                        $d['basicSalary'] = '<span class="salary-component">₹' . number_format($d['basicSalary'], 0) . '</span>';
                        $d['hra'] = '<span class="salary-component">₹' . number_format($d['hra'], 0) . '</span>';
                        $d['grossSalary'] = '<span class="salary-amount">₹' . number_format($d['grossSalary'], 0) . '</span>';
                    ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <?php echo getMAction("mid", $d["structureID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2]; ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if (isset($v[3]) && $v[3] != '') {
                                        echo getViewEditUrl("id=" . $d["structureID"], $d[$v[1]]);
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
            <div class="no-records">No salary structures found</div>
        <?php } ?>
    </div>
</div>
