<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/page.inc.js'); ?>"></script>
<?php
$arrForm = array(
    array("type" => "text", "name" => "userName", "title" => "First Name", "validate" => "required", "attr" => "placeholder='First Name*'"),
    array("type" => "text", "name" => "userLastName", "title" => "Last Name", "validate" => "required", "attr" => "placeholder='Last Name*'"),
    array("type" => "text", "name" => "userEmail", "title" => "Email", "validate" => "required,email", "attr" => "placeholder='Email*'"),
    array("type" => "text", "name" => "userSubject", "title" => "Subject", "validate" => "required", "attr" => "placeholder='Subject*'"),
    array("type" => "textarea", "name" => "userMessage", "title" => "Message", "attrp" => ' class="w-100"', "validate" => "required", "attr" => "placeholder='Message*'"),
    array("type" => "checkbox", "name" => "termsAndCondition", "value" => $D["isActive"] ?? 0, "title" => "I agree with the Terms of Use and Privacy Policy and I declare that I have read the information that is required in accordance with Article 13 of GDPR.", "attrp" => ' class="chek"', "validate" => "required", "attr" => 'class="required"'),
    array("type" => "hidden", "id" => "modType", "name" => "modType", "value" => "4")
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
                            </ul>
                            <?php echo $MXFRM->closeForm(); ?>
                            <a href="javascript:void(0)" class="fa-save button thm-btn" rel="contactUsForm">Send a message</a>
                        </form>
                        <div class="result"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!--Contact Page End-->