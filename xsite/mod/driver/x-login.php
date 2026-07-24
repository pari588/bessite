<?php
if (isset($_SESSION['DRIVER_LOGIN_OTP'])) {
    echo "<script>window.location.href = SITEURL+'/driver/home/'; </script>";
    exit;
}
?>

<script type="text/javascript" src="<?php echo $TPL->modUrl; ?>/js/x-driver.inc.js"></script>
<?php
$arrFrom = array(
    array("type" => "text", "name" => "loginOtp1", "attr" => 'minlength="1" maxlength="1" class="pin pin-input" inputmode="numeric" pattern="[0-9]*" autocomplete="off"'),
    array("type" => "text", "name" => "loginOtp2", "attr" => 'minlength="1" maxlength="1" class="pin pin-input" inputmode="numeric" pattern="[0-9]*" autocomplete="off"'),
    array("type" => "text", "name" => "loginOtp3", "attr" => 'minlength="1" maxlength="1" class="pin pin-input" inputmode="numeric" pattern="[0-9]*" autocomplete="off"'),
    array("type" => "text", "name" => "loginOtp4", "attr" => 'minlength="1" maxlength="1" class="pin pin-input" inputmode="numeric" pattern="[0-9]*" autocomplete="off"'),
    array("type" => "hidden", "name" => "xAction", "value" => "driverLogin"),
);

$MXFRM = new mxForm();
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Inter:wght@300;400;500;600&display=swap');

:root {
    --primary: #1a1a1a;
    --accent: #157bba;
    --accent-dark: #0e5a8a;
    --accent-light: #e8f4fc;
    --surface: #fafafa;
    --surface-elevated: #ffffff;
    --text-primary: #1a1a1a;
    --text-secondary: #6b7280;
    --text-muted: #9ca3af;
    --border: #e5e7eb;
    --border-light: #f3f4f6;
}

.driver-login-wrapper * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.driver-login-wrapper {
    min-height: 100vh;
    min-height: 100dvh;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    position: relative;
    overflow: hidden;
}

/* Subtle geometric background pattern */
.driver-login-wrapper::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 80%;
    height: 200%;
    background: linear-gradient(45deg, transparent 40%, rgba(21, 123, 186, 0.03) 40%, rgba(21, 123, 186, 0.03) 60%, transparent 60%);
    transform: rotate(-12deg);
    pointer-events: none;
}

.driver-login-wrapper::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--accent-dark));
}

.login-card {
    width: 100%;
    max-width: 340px;
    background: var(--surface-elevated);
    border-radius: 20px;
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.05),
        0 10px 15px -3px rgba(0, 0, 0, 0.08),
        0 20px 25px -5px rgba(0, 0, 0, 0.05);
    padding: 28px 24px;
    position: relative;
    z-index: 1;
    animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Accent bar at top of card */
.login-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--accent-dark));
    border-radius: 0 0 4px 4px;
}

.brand-header {
    text-align: center;
    margin-bottom: 20px;
}

.brand-logo {
    width: 110px;
    height: auto;
    margin-bottom: 8px;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

.brand-title {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    letter-spacing: 2px;
    text-transform: uppercase;
    margin: 0;
}

.car-icon {
    display: flex;
    justify-content: center;
    margin-top: 12px;
}

.car-icon svg {
    width: 48px;
    height: 48px;
    color: var(--accent);
}

.section-label {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-secondary);
    letter-spacing: 1.5px;
    text-transform: uppercase;
    text-align: center;
    margin-bottom: 16px;
}

/* PIN Container - Horizontal Layout */
.driver-login-wrapper .tbl-form,
.driver-login-wrapper .pin-container {
    display: flex !important;
    flex-direction: row !important;
    justify-content: center !important;
    align-items: center !important;
    gap: 10px !important;
    margin-bottom: 24px !important;
    list-style: none !important;
    padding: 0 !important;
}

.driver-login-wrapper .tbl-form li,
.driver-login-wrapper .pin-container li {
    list-style: none !important;
    display: inline-block !important;
    float: none !important;
    width: auto !important;
    margin: 0 !important;
    padding: 0 !important;
}

.driver-login-wrapper .pin-input {
    width: 56px !important;
    height: 60px !important;
    border: 2px solid var(--border) !important;
    border-radius: 12px !important;
    background: var(--surface) !important;
    font-family: 'Inter', sans-serif !important;
    font-size: 26px !important;
    font-weight: 600 !important;
    line-height: 56px !important;
    text-align: center !important;
    color: var(--text-primary) !important;
    transition: all 0.25s ease !important;
    outline: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: textfield !important;
    display: block !important;
    float: none !important;
    padding: 0 !important;
}

.driver-login-wrapper .pin-input::-webkit-outer-spin-button,
.driver-login-wrapper .pin-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.driver-login-wrapper .pin-input:focus {
    border-color: var(--accent) !important;
    background: #fff !important;
    box-shadow: 0 0 0 4px rgba(21, 123, 186, 0.1) !important;
    transform: translateY(-2px);
}

.driver-login-wrapper .pin-input:not(:placeholder-shown) {
    border-color: var(--accent) !important;
    background: #fff !important;
}

/* Login Button - styled version of btn1 */
.driver-login-wrapper .btn1,
.driver-login-wrapper .login-btn {
    display: block !important;
    width: 100% !important;
    padding: 16px 24px !important;
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%) !important;
    color: #fff !important;
    font-family: 'Inter', sans-serif !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    letter-spacing: 1px !important;
    text-transform: uppercase !important;
    text-decoration: none !important;
    text-align: center !important;
    border: none !important;
    border-radius: 12px !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 14px rgba(21, 123, 186, 0.3) !important;
    position: relative !important;
    overflow: hidden !important;
}

.driver-login-wrapper .btn1:hover,
.driver-login-wrapper .login-btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(21, 123, 186, 0.4) !important;
}

.driver-login-wrapper .btn1:active,
.driver-login-wrapper .login-btn:active {
    transform: translateY(0) !important;
}

.driver-login-wrapper .btn1 span,
.driver-login-wrapper .login-btn span {
    position: relative;
    z-index: 1;
}

/* Error message styling */
.driver-login-wrapper .e {
    text-align: center;
    margin-top: 16px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: #dc2626;
    min-height: 20px;
}

/* Footer branding */
.login-footer {
    text-align: center;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--border-light);
}

.login-footer p {
    font-family: 'Inter', sans-serif;
    font-size: 10px;
    color: var(--text-muted);
    letter-spacing: 0.5px;
}

/* Hide default form styling from framework */
.driver-login-wrapper .wrap-form {
    display: block;
}

/* Responsive adjustments */
@media (max-width: 420px) {
    .login-card {
        padding: 24px 20px;
        border-radius: 16px;
    }

    .driver-login-wrapper .pin-input {
        width: 52px !important;
        height: 56px !important;
        font-size: 24px !important;
        line-height: 52px !important;
    }

    .driver-login-wrapper .tbl-form,
    .driver-login-wrapper .pin-container {
        gap: 8px !important;
    }

    .brand-logo {
        width: 100px;
    }

    .brand-header {
        margin-bottom: 16px;
    }
}
</style>

<div class="driver-login-wrapper">
    <div class="login-card">
        <div class="brand-header">
            <img src="<?php echo SITEURL ?>/images/logo.png" alt="Bombay Engineering Syndicate" class="brand-logo">
            <h4 class="brand-title">Driver Portal</h4>
            <div class="car-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                </svg>
            </div>
        </div>

        <p class="section-label">Enter Your PIN</p>

        <form class="wrap-data" name="frmLogin" id="frmLogin" action="" auto="false" method="post" enctype="multipart/form-data">
            <div class="wrap-form f70">
                <ul class="tbl-form pin-container">
                    <?php echo $MXFRM->getForm($arrFrom); ?>
                </ul>
            </div>
            <a href="javascript:void(0);" class="btn1 fa-save" rel="frmLogin"><span>Sign In</span></a>
        </form>

        <div class="login-footer">
            <p>Bombay Engineering Syndicate &copy; <?php echo date('Y'); ?></p>
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
            $(this).val(value.slice(0, 1));
        }
        if (/^[0-9]$/.test(value)) {
            if (value.length === 1) {
                $(this).closest('li').next('li').find('.pin').focus();
            }
        } else {
            $(this).val('');
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
        if (/^\d{4}$/.test(pastedData)) {
            var digits = pastedData.split('');
            $('.pin').each(function(index) {
                $(this).val(digits[index]);
            });
            if ($('.pin').filter(function() { return this.value !== ''; }).length === 4) {
                $('a[rel="frmLogin"]').trigger('click');
            }
        }
    });

    // Check if all fields are filled
    $('.pin').on('input', function() {
        var count = $('.pin').filter(function() { return this.value !== ''; }).length;
        if (count === 4) {
            $('.e').html('');
        }
    });
});
</script>
