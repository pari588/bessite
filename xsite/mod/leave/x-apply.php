<?php
    if (!isset($_SESSION['LEAVEUSERID']) && $_SESSION['LEAVEUSERID'] <= 0){
        echo "<script>window.location.href = '" . SITEURL . "/leave/';</script>";
        exit();
    }
?>
<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/js/x-leave.inc.js?' . time()); ?>"></script>
<?php
    require_once(SITEPATH . '/inc/common.inc.php');
    $selectType = json_decode(SELECTTYPE, 1);
    // Preparing leave type select's dropdown.
    $arrWhere    = array("sql" => "status=? AND leaveTypeID IN (?,?)", "types" => "iii", "vals" => array(1, 1, 6));
    $params = ["table" => $DB->pre . "leave_type", "key" => "leaveTypeID", "val" => "leaveTypeName", "where" => $arrWhere];
    $arrLeaveType  = getDataArray($params);
    $leaveTypeDD = getArrayDD(["data" => array("data" => $arrLeaveType['data']), "selected" => 0]); // getArrayDD($dutyArr, 0);

    $leaveForm1 = array(
        array("type" => "date", "name" => "fromDate", "title" => "From Date", "validate" => "required","msg"=>"From date is mandatory"),
        array("type" => "date", "name" => "toDate", "title" => "To Date", "validate" => "required","msg"=>"To date is mandatory"),
    );

    // user dropdown for leave manager
    if(isset($_SESSION['ISLEAVEMANAGER']) && $_SESSION['ISLEAVEMANAGER'] == 1){
        $whr = " AND (techIlliterate = 1 OR userID= ".$_SESSION['LEAVEUSERID'].")";
        $userNameWhere = array("sql" => "status = ?".$whr, "types" => "i", "vals" => array(1), "ORDER BY ASC");
        $userNameArr =  getTableDD(["table" => $DB->pre . "x_admin_user", "key" => "userID", "val" => "displayName", "selected" => ($_SESSION['LEAVEUSERID'] ?? ""), "where" =>  $userNameWhere]);
        array_unshift($leaveForm1,array("type" => "select", "name" => "userID",  "value" => $userNameArr, "title" => "Name", "validate" => "required", "prop" => ' class="text" disabled', "attr" => ' readonly="readonly"'));
    }

    $leaveForm2 = array(
        array("type" => "select", "name" => "leaveType", "value" => $leaveTypeDD, "title" => "Leave Type", "validate" => "required","msg"=>"Leave type is mandatory"),
        array("type" => "textarea", "name" => "reason", "title" => "Reason", "validate" => "required,maxlen:500"),
    );

    $leaveDetailForm = array(
        array("type" => "hidden", "name" => "leaveDetailID"),
        array("type" => "text", "name" => "leaveDateFormat", "title" => "Date"),
        array("type" => "hidden", "name" => "leaveDate", "title" => "Date"),
        array("type" => "select", "name" => "lType", "value" => getArrayDD(["data" => array("data" => $selectType ?? ''), "selected" => (0)]), "title" => " Leave Type", "validate" => "required","msg"=>"Leave type is mandatory"),
    );
    $MXFRM = new mxForm();
?>

<div class="lead">
        <div class="btn-wrapper">
            <h3>Apply Leave</h3>
            <a href="<?php echo SITEURL . '/leave/list/'; ?>" class="thm-btn list">Leave List</a>
        </div>
    <?php  $MXFRM->xAction = "addLeaveUser";
    ?>
        <!-- New Leave user form start. -->
        <div class="main-form">
            <form name="leaveUserFrm" auto="false" class="leaveUserFrm" id="leaveUserFrm" action="" method="post" onsubmit="return false;" >
                <ul>
                    <?php echo $MXFRM->getForm($leaveForm1); ?>
                    <li>
                        <label class="form-head">Leave Details</label>
                        <?php
                            echo $MXFRM->getFormG(array("flds" => $leaveDetailForm, "vals" => [], "type" => 0, "add" => true, "del" => false, "class" => " small leave-details"));
                        ?>
                    </li>
                    <?php echo $MXFRM->getForm($leaveForm2); ?>
                </ul>
                <?php echo $MXFRM->closeForm(); ?>
                <input type="hidden" name="pageType" id="pageType" value="add" />
                <button type="button" class="fa-save thm-btn" rel="leaveUserFrm"> Save </button>
            </form>
        </div>
</div>
