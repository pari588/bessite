<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/js/x-leave.inc.js?' . time()); ?>"></script>
<?php
$loginForm = array(
    array("type" => "text", "name" => "pin_1", "attr" => ' class="pin" minlength="1" maxlength="1"'),
    array("type" => "text", "name" => "pin_2", "attr" => ' class="pin" minlength="1" maxlength="1"'),
    array("type" => "text", "name" => "pin_3", "attr" => ' class="pin" minlength="1" maxlength="1"'),
    array("type" => "text", "name" => "pin_4", "attr" => ' class="pin" minlength="1" maxlength="1"')
);

$MXFRM = new mxForm();
$MXFRM->xAction = "verifyUserPin";
?>

<div class="lead">
        <!-- Login form start. -->
        <div class="pin-input">
            <h2>Login with PIN</h2>
            <form name="verifyPinFrm" auto="false" class="verifyPinFrm" id="verifyPinFrm" action="" method="post" onsubmit="return false;" callback="callbackVerifyUserPin" >
                <ul>
                    <?php echo $MXFRM->getForm($loginForm); ?>
                </ul>
                <?php echo $MXFRM->closeForm(); ?>
                <p class="e" id="leavePinErr"></p>
                <a href="javascript:void(0)" class="fa-save thm-btn" rel="verifyPinFrm"> Login </a>
            </form>
        </div>
</div>