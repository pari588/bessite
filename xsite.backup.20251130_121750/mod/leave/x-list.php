<?php 
if (!isset($_SESSION['LEAVEUSERID']) && $_SESSION['LEAVEUSERID'] <= 0){
    echo "<script>window.location.href = '" . SITEURL . "/leave/';</script>";
    exit();
}
?>
<?php
if (isset($_SESSION['LEAVEUSERID']) && $_SESSION['LEAVEUSERID'] > 0) {require_once(SITEPATH . '/inc/common.inc.php');
$resp = getLeaveList();
$leaves = '';
if(isset($resp['err']) && $resp['err'] ==0){
    $leaves = $resp['data'];
}
$userResp = getUserLeaveData($_SESSION['LEAVEUSERID'] ?? 0); // get user data and user's leaves data
if(isset($userResp['err']) && $userResp['err'] == 0){
    $userData = $userResp['data'];
    $balanceLeave = $userData['totalLeaves'] - $userData['monthlyappliedLeave'];
}
    
?>
<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/js/x-leave.inc.js?'); ?>"></script>
    <section class="Specifications">
        <div class="btn-wrapper">
            <h3>Leave List</h3>
            <a href="<?php echo SITEURL . '/leave/apply/'; ?>" class="thm-btn apply">Apply Leave</a>
            <p>Balance Leaves : <?php echo $balanceLeave ?? 0; ?> </p>
        </div>
        <div class="container">
            <?php echo $leaves['paging']??'';?>
            <div class="spec-tbl">
                <div class="body-scroll">
                    <table border="0" width="100%">
                        <thead>
                            <tr>
                                <th colspan="2">Leave Details</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($leaves['tblData']) && $leaves['tblData']!='') {
                                 echo $leaves['tblData'] ??'';
                                } else { ?>
                                <tr>
                                    <td colspan="5" align="center">No records found</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <div class="popup leaveDetailPopup mxdialog" style="display:none">
        <div class="body">
            <a href="#" class="close del rl"></a>
            <h2 class="title">Leave details</h2>
            <div class="content">
                <div class="tblData">
                    <table>
                        <thead>
                            <tr>
                                <th align="center" width="30%">Status</th>
                                <th align="center" width="70%">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="lData">
                   
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php } ?>