<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/js/x-pump-inquiry.inc.js'); ?>"></script>

<!--Page Header Start-->
<section class="page-header">
	<div class="page-header__bg" style="background-image: url(<?php echo SITEURL . '/images/page-header-bg.jpg' ?>);">
	</div>
	<div class="container">
		<div class="page-header__inner">
			<ul class="thm-breadcrumb list-unstyled">
				<li><a href="<?php echo SITEURL . '/' ?>">Home</a></li>
				<li><span>/</span></li>
				<li>Pump Enquiry Form</li>
			</ul>
			<h2>Pump Enquiry Form</h2>
		</div>
	</div>
</section>
<!--Page Header End-->
<?php
$arrFrom0 = array(
	array("type" => "text", "name" => "userName", "title" => "Name (Company or Personal)", "validate" => "required,alpha", "attr" => "placeholder='Company or Personal Name*'"),
	array("type" => "text", "name" => "userEmail", "title" => "Email", "validate" => "required,email", "attr" => "placeholder='Email*'"),

);

$arrFrom1 = array(

	array("type" => "text", "name" => "userMobile", "title" => "Mobile", "validate" => "required", "attr" => "placeholder='Indian Mobile Number*'"),
	array("type" => "textarea", "name" => "enquiryText", "title" => "Enquiry", "validate" => "required", "attr" => "placeholder='Enter your pump enquiry details*'"),
);



$MXFRM = new mxForm();
$MXFRM->xAction = "savePumpInquiry";
?>
<section class="inquiry-page">
	<div class="container">
		<div class="section-title text-center">
			<h2 class="section-title__title">Pump Enquiry Form</h2>
		</div>
		<form name="productInquiryForm" class="productInquiryForm" id="productInquiryForm" action="" method="post" enctype="multipart/form-data">
			<div class="inquiry-wrap">
				<div class="left">
					<ul class="form-list">
						<?php echo $MXFRM->getForm($arrFrom0); ?>
					</ul>
				</div>
				<div class="right">
					<ul class="form-list">
						<?php echo $MXFRM->getForm($arrFrom1); ?>
					</ul>
				</div>
			</div>
			<?php echo $MXFRM->closeForm(); ?>
			<input type="hidden" name="pageType" id="pageType" value="add" />
			<a href="javascript:void(0)" class="fa-save button thm-btn" rel="productInquiryForm"> Submit </a>
		</form>
	</div>
</section>