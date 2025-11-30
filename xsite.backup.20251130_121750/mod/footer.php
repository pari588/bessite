<?php
// To fetch header user info data.
$siteSettingInfo = getSiteInfo();
// End.
// Start: Countact us form
$arrForm = array(
    array("type" => "text", "name" => "userName", "title" => "First Name", "validate" => "required", "attr" => "placeholder='First Name*'"),
    array("type" => "text", "name" => "userLastName", "title" => "Last Name", "validate" => "required", "attr" => "placeholder='Last Name*'"),
    array("type" => "text", "name" => "userEmail", "title" => "Email", "validate" => "required,email", "attr" => "placeholder='Email*'"),
    array("type" => "text", "name" => "userSubject", "title" => "Subject", "validate" => "required", "attr" => "placeholder='Subject*'"),
    array("type" => "textarea", "name" => "userMessage", "title" => "Message", "attrp" => ' class="w-100"', "validate" => "required", "attr" => "placeholder='Message*'"),
    array("type" => "checkbox", "name" => "termsAndCondition", "title" => "I agree with the Terms of Use and Privacy Policy and I declare that I have read the information that is required in accordance with Article 13 of GDPR.", "attrp" => ' class="chek"', "validate" => "required", "attr" => 'class="required"'),
    array("type" => "hidden", "id" => "modType", "name" => "modType", "value" => ""),
    array("type" => "hidden", "id" => "categoryTitle", "name" => "categoryTitle", "value" => ""),
    array("type" => "hidden", "id" => "productTitle", "name" => "productTitle", "value" => "")
);

$MXFRM = new mxForm();
$MXFRM->xAction = "saveProductContactFrm";
?>
<div class="mxdialog product-contact-Frm" style="display:none">
    <div class="body">
        <a href="javascript:volid(0)" class="close del rl close-que"></a>
        <div class="section-title">
            <h2 class="section-title__title">Contact us</h2>
        </div>
        <div class="form-wrap">
            <form name="frmPopupEnquiry" id="frmPopupEnquiry" class="frmPopupEnquiry" action="" method="post" enctype="multipart/form-data">
                <ul class="form-list">
                    <?php echo $MXFRM->getForm($arrForm); ?>
                </ul>
                <?php echo $MXFRM->closeForm(); ?>
                <a href="javascript:void(0)" class="fa-save button thm-btn" rel="frmPopupEnquiry">Send a message</a>
            </form>
        </div>
    </div>
</div>
<?php
$footerStyle = "";
if (isset($TPL->uriArr[0]) && $TPL->uriArr[0] == "driver") {
    $footerStyle = "style=display:none";
}

?>
<!-- Contact Us form End. -->
<footer class="site-footer" <?php echo $footerStyle; ?>>
    <div class="site-footer__shape-1 float-bob-x">
        <img src="<?php echo SITEURL . '/images/footer-shape-1.png' ?>" alt="Decorative shape - footer section background">
    </div>
    <div class="site-footer__shape-2 float-bob-y">
        <img src="<?php echo SITEURL . '/images/footer-shape-2.png' ?>" alt="Decorative shape - footer section accent">
    </div>
    <div class="container">
        <?php echo $siteSettingInfo["siteFooterInfo"]; ?>
    </div>
</footer>
<a href="#" data-target="html" class="scroll-to-target scroll-to-top"><i class="fa fa-angle-up"></i></a>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/jarallax/jarallax.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/jquery-ajaxchimp/jquery.ajaxchimp.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/jquery-appear/jquery.appear.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/jquery-circle-progress/jquery.circle-progress.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/jquery-magnific-popup/jquery.magnific-popup.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/jquery-validate/jquery.validate.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/nouislider/nouislider.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/odometer/odometer.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/tiny-slider/tiny-slider.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/wnumb/wNumb.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/wow/wow.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/isotope/isotope.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/countdown/countdown.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/owl-carousel/owl.carousel.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/bxslider/jquery.bxslider.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/bootstrap-select/js/bootstrap-select.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/jquery-ui/jquery-ui.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/timepicker/timePicker.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/circleType/jquery.circleType.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/circleType/jquery.lettering.min.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/sidebar-content/jquery-sidebar-content.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/twenty-twenty/twentytwenty.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/vendors/twenty-twenty/jquery.event.move.js'); ?>"></script>
<!-- template js -->
<script src="<?php echo mxGetUrl(SITEURL . '/inc/js/mellis.js'); ?>"></script>
<script src="<?php echo mxGetUrl(SITEURL . '/inc/js/x-site.inc.js'); ?>"></script>
</body>

</html>