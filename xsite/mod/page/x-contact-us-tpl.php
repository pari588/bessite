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
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ContactPage",
    "name": "Contact Bombay Engineering Syndicate",
    "url": "<?php echo SITEURL; ?>/contact-us/",
    "description": "Get in touch with Bombay Engineering Syndicate — industrial motors and submersible pumps supplier since 1957. Mumbai head office and Ahmedabad branch. Email, phone, and address details for sales and service.",
    "inLanguage": "en-IN",
    "isPartOf": {
        "@type": "WebSite",
        "name": "Bombay Engineering Syndicate",
        "url": "<?php echo SITEURL; ?>/"
    },
    "mainEntity": {
        "@type": "Organization",
        "name": "Bombay Engineering Syndicate",
        "@id": "<?php echo SITEURL; ?>/#organization",
        "contactPoint": [
            {
                "@type": "ContactPoint",
                "telephone": "+919324706905",
                "contactType": "Customer Service",
                "areaServed": ["Maharashtra", "Mumbai"],
                "availableLanguage": ["en", "hi"]
            },
            {
                "@type": "ContactPoint",
                "telephone": "+919825014977",
                "contactType": "Customer Service",
                "areaServed": ["Gujarat", "Ahmedabad"],
                "availableLanguage": ["en", "hi", "gu"]
            }
        ]
    }
}
</script>
<style>
.page-header .page-header__inner h1 {
    color: #ffffff !important;
}
</style>
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
            <h1><?php echo $TPL->data["pageTitle"] ?></h1>
        </div>
    </div>
</section>
<!--Page Header End-->

<!--Spa Center Three Start-->
<section class="spa-center-three">
    <?php echo $TPL->data["pageContent"] ?>
</section>
<!--Spa Center Three End-->

<!--Google Maps - Office Locations Start-->
<section class="contact-maps">
    <div class="container">
        <div class="section-title text-center" style="margin-bottom: 30px;">
            <h2 class="section-title__title">Our Office Locations</h2>
            <p class="section-title__text">Visit us at our Mumbai or Ahmedabad office</p>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <div class="map-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Mumbai Office</h3>
                    <p>2nd Floor, Modern House, Dr. V.B. Gandhi Marg, Kala Ghoda, Fort, Mumbai, Maharashtra - 400001</p>
                    <p><a href="tel:+919324706905"><i class="fas fa-phone-alt"></i> +91 93247 06905</a></p>
                    <div class="map-embed">
                        <iframe src="https://www.google.com/maps?q=Bombay+Engineering+Syndicate,+Modern+House,+17+Dr+V.B.+Gandhi+Marg,+Kala+Ghoda,+Fort,+Mumbai+400001&z=17&output=embed" width="100%" height="300" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Bombay Engineering Syndicate Mumbai Office"></iframe>
                    </div>
                    <p style="margin-top: 12px;"><a href="<?php echo SITEURL; ?>/mumbai/" style="font-weight: 600; color: #157bba;"><i class="fas fa-arrow-right"></i> View Full Mumbai Office Details</a></p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="map-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Ahmedabad Office</h3>
                    <p>Office No. 611-612, Ratnanjali Solitaire, Satellite, Ahmedabad - 380015</p>
                    <p><a href="tel:+919825014977"><i class="fas fa-phone-alt"></i> +91 98250 14977</a></p>
                    <div class="map-embed">
                        <iframe src="https://www.google.com/maps?q=Bombay+Engineering+Syndicate,+611-612+Ratnanjali+Solitaire,+Satellite,+Ahmedabad+380015&z=17&output=embed" width="100%" height="300" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Bombay Engineering Syndicate Ahmedabad Office"></iframe>
                    </div>
                    <p style="margin-top: 12px;"><a href="<?php echo SITEURL; ?>/ahmedabad/" style="font-weight: 600; color: #157bba;"><i class="fas fa-arrow-right"></i> View Full Ahmedabad Office Details</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
<style>
.contact-maps { padding: 50px 0; background: #f8fafb; }
.map-card { background: #fff; border-radius: 10px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.map-card h3 { font-size: 20px; color: #1a1a2e; margin-bottom: 10px; }
.map-card h3 i { color: #157bba; margin-right: 8px; }
.map-card p { font-size: 14px; color: #555; margin-bottom: 8px; }
.map-card a { color: #157bba; text-decoration: none; }
.map-card a i { margin-right: 6px; }
.map-embed { margin-top: 16px; }
</style>
<!--Google Maps - Office Locations End-->

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