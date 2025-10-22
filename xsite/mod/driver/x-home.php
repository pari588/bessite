<?php
if (!isset($_SESSION['DRIVER_LOGIN_OTP'])) {
    echo "<script>window.location.href = SITEURL+'/driver/login/'; </script>";
    exit;
}

$DB->vals = array(1, $_SESSION['USER_ID']);
$DB->types = "ii";
$DB->sql = "SELECT * FROM `" . $DB->pre . "user` WHERE status=? AND userID=?";
$userData = $DB->dbRow();

$DB->vals = array(1, $_SESSION['USER_ID']);
$DB->types = "ii";
$DB->sql = "SELECT * FROM `" . $DB->pre . "driver_management` WHERE status=? AND userID=? ORDER BY driverManagementID DESC";
$driverManagement = $DB->dbRow();
$driverNumRows = $DB->numRows;
$driverManagementID = intval($driverManagement['driverManagementID'] ?? 0);
?>

<script type="text/javascript" src="<?php echo $TPL->modUrl; ?>/js/x-driver.inc.js"></script>
<div class="mobile-view">

    <div class="page home">
        <div class="container">
            <div class="login-logo">

                <img src="<?php echo SITEURL; ?>/images/logo.png" alt="">
                <h4>Driver Attendance</h4>
            </div>
            <div class="info">
                <!--  <h2>Driver Attendance</h2> -->
                <!-- <p>Monday, February 28, 2022 14:30</p> -->
                <p><?php echo date("l, F d, Y H:i"); ?></p>
                <div class="profile">
                    <span><img src="<?php echo SITEURL; ?>/images/img_avatar.png" alt=""></span>
                    <h2><?php echo $userData['userName']; ?></h2>
                    <p><?php echo $userData['userCity']; ?></p>
                </div>
            </div>
            <ul class="tab-list">
                <?php if ($driverNumRows > 0 && $driverManagement["toTime"] == "") { ?>
                    <li>
                        <a href="javascript:void(0);" class="btn1" id="mark-out" rel="<?php echo $driverManagementID; ?>">
                            <h4>ओवर टाइम मार्क-आउट करे</h4>
                        </a>
                    </li>
                <?php } else { ?>
                    <li>
                        <a href="javascript:void(0);" class="btn1" id="mark-in">
                            <h4>ओवर टाइम मार्क-इन करे</h4>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="footer-logo"><img src="<?php echo SITEURL; ?>/images/logo-2.png" alt=""></div>
</div>