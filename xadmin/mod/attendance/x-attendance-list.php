<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/js/x-attendance.inc.js'); ?>"></script>

<?php
// Access control - similar to leave module
$arradmin = array("0" => "Admin", "1" => "Leave Manager");
$adminIDArr = getRoleIDS($arradmin);

$cnd = '';
if ($_SESSION[SITEURL]['MXROLE'] != 'SUPER' && !in_array($_SESSION[SITEURL]['MXROLE'], $adminIDArr)) {
    // Regular employee - only see own attendance
    $cnd = ' AND A.userID = ' . $_SESSION[SITEURL]['MXID'];
} else if (isset($_SESSION[SITEURL]['MXROLEKEY']) && $_SESSION[SITEURL]['MXROLEKEY'] == "leaveManager") {
    // Leave Manager - see own + managed employees
    $cnd = ' AND (U.techIlliterate = 1 AND U.managerID = ' . $_SESSION[SITEURL]['MXID'] . ' OR A.userID = ' . $_SESSION[SITEURL]['MXID'] . ')';
}

// Get current month/year defaults
$currentMonth = date('m');
$currentYear = date('Y');

// Prepare month dropdown
$monthOptions = '';
for ($m = 1; $m <= 12; $m++) {
    $selected = ($m == $currentMonth) ? 'selected' : '';
    $monthOptions .= '<option value="' . $m . '" ' . $selected . '>' . date('F', mktime(0, 0, 0, $m, 1)) . '</option>';
}

// Prepare year dropdown
$yearOptions = '';
for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++) {
    $selected = ($y == $currentYear) ? 'selected' : '';
    $yearOptions .= '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
}

// Search array
$arrSearch = array(
    array("type" => "text", "name" => "displayName", "title" => "Employee Name", "where" => "AND U.displayName LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "select", "name" => "month", "value" => $monthOptions, "title" => "Month", "where" => "AND MONTH(A.attendanceDate)=?", "dtype" => "i", "default" => $currentMonth),
    array("type" => "select", "name" => "year", "value" => $yearOptions, "title" => "Year", "where" => "AND YEAR(A.attendanceDate)=?", "dtype" => "i", "default" => $currentYear),
    array("type" => "select", "name" => "attendanceStatus", "value" => '
        <option value="">-- All --</option>
        <option value="present">Present</option>
        <option value="absent">Absent</option>
        <option value="half_day">Half Day</option>
        <option value="leave">Leave</option>
        <option value="holiday">Holiday</option>
    ', "title" => "Status", "where" => "AND A.attendanceStatus=?", "dtype" => "s"),
    array("type" => "select", "name" => "isLate", "value" => '
        <option value="">-- All --</option>
        <option value="1">Late Only</option>
        <option value="0">On Time</option>
    ', "title" => "Late", "where" => "AND A.isLate=?", "dtype" => "i"),
);

$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);

// Build query
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT A.*, U.displayName, U.employeeCode, U.department
            FROM `" . $DB->pre . "attendance` AS A
            LEFT JOIN `" . $DB->pre . "x_admin_user` AS U ON A.userID = U.userID
            WHERE A.status=? " . $MXFRM->where . $cnd;
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
                array("#ID", "attendanceID", ' width="1%" align="center"', true),
                array("Employee", "displayName", ' width="15%" align="left"'),
                array("Emp Code", "employeeCode", ' width="8%" align="center"'),
                array("Date", "attendanceDate", ' width="10%" align="center"'),
                array("Check In", "checkIn", ' width="10%" align="center"'),
                array("Check Out", "checkOut", ' width="10%" align="center"'),
                array("Hours", "workingHours", ' width="5%" align="center"'),
                array("Status", "attendanceStatus", ' width="10%" align="center"', '', 'nosort'),
                array("Late/Early", "lateEarly", ' width="10%" align="center"', '', 'nosort'),
                array("Remarks", "hasRemark", ' width="10%" align="center"', '', 'nosort'),
            );

            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT A.*, U.displayName, U.employeeCode, U.department,
                        (SELECT COUNT(*) FROM " . $DB->pre . "attendance_remarks WHERE attendanceID = A.attendanceID AND status=1) as remarkCount
                        FROM `" . $DB->pre . "attendance` AS A
                        LEFT JOIN `" . $DB->pre . "x_admin_user` AS U ON A.userID = U.userID
                        WHERE A.status=? " . $MXFRM->where . $cnd . " " . mxOrderBy(" A.attendanceDate DESC, U.displayName ASC ") . mxQryLimit();
            $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr><?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {
                        // Format times
                        $d['checkIn'] = $d['checkIn'] ? date('h:i A', strtotime($d['checkIn'])) : '-';
                        $d['checkOut'] = $d['checkOut'] ? date('h:i A', strtotime($d['checkOut'])) : '-';
                        $d['workingHours'] = $d['workingHours'] ? number_format($d['workingHours'], 1) . 'h' : '-';
                        $d['attendanceDate'] = date('d M Y', strtotime($d['attendanceDate']));

                        // Status badge using xadmin label classes
                        $statusClass = 'label label-default';
                        $origStatus = $d['attendanceStatus'];
                        if ($origStatus == 'present') $statusClass = 'label label-success';
                        elseif ($origStatus == 'absent') $statusClass = 'label label-danger';
                        elseif ($origStatus == 'half_day') $statusClass = 'label label-warning';
                        elseif ($origStatus == 'leave') $statusClass = 'label label-info';
                        elseif ($origStatus == 'holiday') $statusClass = 'label label-default';
                        $d['attendanceStatus'] = '<span class="' . $statusClass . '">' . ucfirst(str_replace('_', ' ', $origStatus)) . '</span>';

                        // Late/Early badges
                        $lateEarly = '';
                        if ($d['isLate']) {
                            $lateEarly .= '<span class="label label-danger">Late ' . $d['lateMinutes'] . 'm</span> ';
                        }
                        if ($d['isEarlyCheckout']) {
                            $lateEarly .= '<span class="label label-warning">Early ' . $d['earlyMinutes'] . 'm</span>';
                        }
                        $d['lateEarly'] = $lateEarly ?: '-';

                        // Remarks indicator
                        if ($d['remarkCount'] > 0) {
                            $d['hasRemark'] = '<a href="#" class="view-remarks" data-id="' . $d['attendanceID'] . '"><i class="fa fa-comment"></i> ' . $d['remarkCount'] . '</a>';
                        } else {
                            $d['hasRemark'] = '-';
                        }
                    ?>
                        <tr>
                            <?php echo getMAction("mid", $d["attendanceID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2]; ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if (isset($v[3]) && $v[3] != '') {
                                        echo getViewEditUrl("id=" . $d["attendanceID"], $d[$v[1]]);
                                    } else {
                                        echo $d[$v[1]] ?? "";
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="alert alert-info">No attendance records found</p>
        <?php } ?>
    </div>
</div>

<!-- Remarks Popup -->
<div class="popup remarks-popup mxdialog" style="display:none">
    <div class="body">
        <a href="#" class="close del rl" onclick="closeRemarksPopup()"></a>
        <h2 class="title">Attendance Remarks</h2>
        <div class="content">
            <div class="remarks-list"></div>
        </div>
    </div>
</div>
