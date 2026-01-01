<?php
// Google reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', '6LeVCf0rAAAAAG3JjibEriASu2AwVx8v-6pxZHlZ');
define('RECAPTCHA_SECRET_KEY', '6LeVCf0rAAAAABhzHVTK76BApoP66LDaXdYaUBN-');

$arrForm = array(
    array("type" => "text", "name" => "userName", "title" => "First Name", "validate" => "required,minlen:2,maxlen:50,name", "attr" => "placeholder='First Name*'"),
    array("type" => "text", "name" => "userLastName", "title" => "Last Name", "validate" => "required,minlen:2,maxlen:50,name", "attr" => "placeholder='Last Name*'"),
    array("type" => "text", "name" => "userEmail", "title" => "Email", "validate" => "required,email", "attr" => "placeholder='Email*'"),
    array("type" => "text", "name" => "userMobile", "title" => "Mobile Number", "validate" => "required,indianmobile", "attr" => "placeholder='10-digit Indian mobile*'"),
    array("type" => "textarea", "name" => "userMessage", "title" => "Message", "attrp" => ' class="w-100"', "validate" => "required,minlen:10,maxlen:5000", "attr" => "placeholder='Message*'"),
    array("type" => "checkbox", "name" => "termsAndCondition", "value" => $D["isActive"] ?? 0, "title" => "I agree with the Terms of Use and Privacy Policy", "attrp" => ' class="chek"', "validate" => "required", "attr" => 'class="required"'),
    array("type" => "hidden", "id" => "modType", "name" => "modType", "value" => "4"),
    array("type" => "hidden", "name" => "g-recaptcha-response", "id" => "g-recaptcha-response", "value" => "")
);

$MXFRM = new mxForm();
$MXFRM->xAction = "saveContactUsInfo";

?>
<!--Page Header Start-->
<section class="page-header">
    <div class="page-header__bg" style="background-image: url(<?php echo SITEURL . '/images/page-header-bg.jpg' ?>);">
    </div>
    <div class="container">
        <div class="page-header__inner">
            <ul class="thm-breadcrumb list-unstyled form-list">
                <li><a href="<?php echo SITEURL . '/' ?>">Home</a></li>
                <li><span>/</span></li>
                <li><?php echo $TPL->data["pageTitle"] ?></li>
            </ul>
            <h2><?php echo $TPL->data["pageTitle"] ?></h2>
        </div>
    </div>
</section>
<!--Page Header End-->

<!--Spa Center Three Start-->
<section class="spa-center-three">
    <?php echo $TPL->data["pageContent"] ?>
</section>
<!--Spa Center Three End-->

<!--Contact Page Start-->
<section class="contact-page">
    <div class="container">
        <div class="row">
            <?php echo $TPL->data["synopsis"] ?>
            <div class="col-xl-8 col-lg-7">
                <div class="contact-page__right">
                    <div class="contact-page__content">
                        <form name="contactUsForm" class="contact-page__form contact-form-validated contactUsForm" id="contactUsForm" action="" method="post" enctype="multipart/form-data">
                            <ul class="contact-page__form-input-box list-unstyled">
                                <?php echo $MXFRM->getForm($arrForm); ?>
                                <!-- Google reCAPTCHA v3 - Invisible -->
                                <li style="margin-bottom: 15px;">
                                    <div id="recaptcha-container" class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>" data-size="invisible"></div>
                                </li>
                            </ul>
                            <?php echo $MXFRM->closeForm(); ?>
                            <a href="#" class="fa-save button thm-btn" rel="contactUsForm" role="button" onclick="return false;">Send a message</a>
                        </form>
                        <div class="result"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!--Contact Page End-->

<script>
    // Initialize reCAPTCHA v3 - Invisible (runs in background)
    console.log('reCAPTCHA script initializing...');

    // Ensure grecaptcha is ready before using it
    if (typeof grecaptcha !== 'undefined') {
        console.log('grecaptcha object found, reCAPTCHA API loaded successfully');
    } else {
        console.error('grecaptcha object not found - reCAPTCHA API may not have loaded');
    }

    // For debugging - show reCAPTCHA badge visibility
    window.addEventListener('load', function() {
        console.log('Page fully loaded');

        // Check for reCAPTCHA badge
        var badge = document.querySelector('.grecaptcha-badge');
        if (badge) {
            console.log('reCAPTCHA badge found - Badge should be visible in bottom-right corner');
            console.log('Badge display:', window.getComputedStyle(badge).display);
        } else {
            console.log('reCAPTCHA badge not found in DOM');
        }
    });
</script>

<!-- Load Google reCAPTCHA API after page is ready -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<!-- Load Contact Form Handler Script -->
<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/page.inc.js'); ?>"></script>