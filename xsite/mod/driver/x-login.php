<?php
if (isset($_SESSION['DRIVER_LOGIN_OTP'])) {
    echo "<script>window.location.href = SITEURL+'/driver/home/'; </script>";
    exit;
}
?>

<script type="text/javascript" src="<?php echo $TPL->modUrl; ?>/js/x-driver.inc.js"></script>
<?php
$arrFrom = array(
    array("type" => "text", "name" => "loginOtp1", "attr" => 'minlength="1" maxlength="1" class="pin" inputmode="numeric" pattern="[0-9]*" autocomplete="off"'),
    array("type" => "text", "name" => "loginOtp2", "attr" => 'minlength="1" maxlength="1" class="pin" inputmode="numeric" pattern="[0-9]*" autocomplete="off"'),
    array("type" => "text", "name" => "loginOtp3", "attr" => 'minlength="1" maxlength="1" class="pin" inputmode="numeric" pattern="[0-9]*" autocomplete="off"'),
    array("type" => "text", "name" => "loginOtp4", "attr" => 'minlength="1" maxlength="1" class="pin" inputmode="numeric" pattern="[0-9]*" autocomplete="off"'),
    array("type" => "hidden", "name" => "xAction", "value" => "driverLogin"),
);

$MXFRM = new mxForm();
?>

<div class="mobile-view">
    <div class="login-page">
        <div class="container">
            <div class="login-logo">
                <img src="<?php echo SITEURL ?>/images/logo.png" alt="Bombay Engineering Syndicate logo">
                <h4>Driver Attendance System</h4>
            </div>
            <div class="img-box">
                <img src="<?php echo SITEURL ?>/images/car.png" alt="Driver attendance system vehicle icon">
            </div>
            <h3>Enter Pin</h3>
            <form class="wrap-data" name="frmLogin" id="frmLogin" action="" auto="false" method="post" enctype="multipart/form-data">
                <div class="wrap-form f70">
                    <ul class="tbl-form">
                        <?php echo $MXFRM->getForm($arrFrom); ?>
                    </ul>
                </div>
                <a href="javascript:void(0);" class="btn1 fa-save" rel="frmLogin"><span>LOGIN</span></a>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Focus on first input
    $('input#loginOtp1').focus();

    // Restrict to one digit and handle navigation
    $('.pin').on('input', function(e) {
        var value = $(this).val();
        if (value.length > 1) {
            $(this).val(value.slice(0, 1)); // Limit to 1 character
        }
        if (/^[0-9]$/.test(value)) { // Only allow numbers
            if (value.length === 1) {
                $(this).closest('li').next('li').find('.pin').focus();
            }
        } else {
            $(this).val(''); // Clear non-numeric input
        }
    });

    // Handle backspace and navigation
    $('.pin').on('keydown', function(e) {
        if (e.key === 'Backspace' && $(this).val() === '') {
            $(this).closest('li').prev('li').find('.pin').focus();
        }
    });

    // Handle paste functionality
    $('.pin').on('paste', function(e) {
        e.preventDefault();
        var pastedData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
        if (/^\d{4}$/.test(pastedData)) { // Check if pasted data is 4 digits
            var digits = pastedData.split('');
            $('.pin').each(function(index) {
                $(this).val(digits[index]);
            });
            // Auto-submit if all fields are filled (optional)
            if ($('.pin').filter(function() { return this.value !== ''; }).length === 4) {
                $('a[rel="frmLogin"]').trigger('click');
            }
        }
    });

    // Check if all fields are filled
    $('.pin').on('input', function() {
        var count = $('.pin').filter(function() { return this.value !== ''; }).length;
        if (count === 4) {
            $('.e').html(''); // Clear any error messages
        }
    });
});
</script>