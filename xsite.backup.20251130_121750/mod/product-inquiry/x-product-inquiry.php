<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/js/x-product-inquiry.inc.js'); ?>"></script>
<?php
// Preparing duty select's dropdown.
$dutyArr = array("1" => "S1", "2" => "S2", "3" => "S3", "4" => "S4", "5" => "Other");
$dutyDD = getArrayDD(["data" => array("data" => $dutyArr), "selected" => ($D["dutyID"] ?? 0)]); // getArrayDD($dutyArr, 0);

// End.

// Preparing Mounting select's dropdown.
$MountingArr = array("1" => "B3 - FOOT", "2" => "B5 - FLANGE", "3" => "B35 - FOOT CUM FLANGE", "4" => "V1 - VERTICAL FLANGE", "5" => "B14 - FACE MOUNTED", "6" => "Other");
$MountingDD = getArrayDD(["data" => array("data" => $MountingArr), "selected" => ($D["mountingID"] ?? 0)]); // getArrayDD($MountingArr, 0);
// End.

// Preparing type of motor select's dropdown.
$typeOfMotorArr = array("1" => "TEFC - SAFE AREA STANDARD", "2" => "FLAME PROOF - GAS GROUP IIA/IIB", "3" => "FLAME PROOF - GAS GROUP IIC", "4" => "INCREASED SAFETY - Ex'e'", "5" => "NON SPARKING - Ex'n'", "6" => "Other");
$typeOfMotorDD = getArrayDD(["data" => array("data" => $typeOfMotorArr), "selected" => ($D["typeOfMotorID"] ?? 0)]); //getArrayDD($typeOfMotorArr, 0);
// End.

// Preparing rotor Type select's dropdown.
$rotorTypeArr = array("1" => "SQUIRREL CAGE", "2" => "SLIP RING");
$rotorTypeDD = getArrayDD(["data" => array("data" => $rotorTypeArr), "selected" => 0]); //getArrayDD($rotorTypeArr, 0);
// End.

// Preparing voltage select's dropdown.
$voltageArr = array("1" => "415", "2" => "380", "3" => "440", "4" => "460", "4" => "480", "5" => "Other");
$voltageDD = getArrayDD(["data" => array("data" => $voltageArr), "selected" => ($D["voltageID"] ?? 0)]); //getArrayDD($voltageArr, 0);
// End.

// Preparing frequency select's dropdown.
$frequencyArr = array("1" => "50", "2" => "60");
$frequencyDD = getArrayDD(["data" => array("data" => $frequencyArr), "selected" => 0]); //getArrayDD($frequencyArr, 0);
// End.

// Preparing shaft extension select's dropdown.
$shaftExtensionArr = array("1" => "SINGLE", "2" => "DOUBLE", "3" => "Other");
$shaftExtensionDD = getArrayDD(["data" => array("data" => $shaftExtensionArr), "selected" => ($D["shaftExtensionID"] ?? 0)]); //getArrayDD($shaftExtensionArr, 0);
// End.

// Preparing expected delivery time select's dropdown.
$expectedDeliveryTimeArr = array("1" => "EX.STOCK", "2" => "1-4 WEEKS", "3" => "4-8 WEEKS", "4" => "MORE THAN 8 WEEKS", "5" => "Other");
$expectedDeliveryTimeDD = getArrayDD(["data" => array("data" => $expectedDeliveryTimeArr), "selected" => ($D["expectedDeliveryTimeID"] ?? 0)]); //getArrayDD($expectedDeliveryTimeArr, 0);
// End.

// Preparing shaft extension checkbox arr and requirement is for replacement arr.
$offerRequirementIsArr = array("1" => "Estimated", "2" => "Firm");
$requirementForRplcArr = array("1" => "Yes", "2" => "No");
// End.

// Preparing pole select's dropdown.
$poleArr = array("1" => "2", "2" => "4", "3" => "6", "4" => "8");
$poleDD = getArrayDD(["data" => array("data" => $poleArr), "selected" => 0]); //getArrayDD($poleArr, 0);
// End.
?>
<!--Page Header Start-->
<section class="page-header">
	<div class="page-header__bg" style="background-image: url(<?php echo SITEURL . '/images/page-header-bg.jpg' ?>);">
	</div>
	<div class="container">
		<div class="page-header__inner">
			<ul class="thm-breadcrumb list-unstyled">
				<li><a href="<?php echo SITEURL . '/' ?>">Home</a></li>
				<li><span>/</span></li>
				<li>Enquiry Form</li>
			</ul>
			<h2>Enquiry Form</h2>
		</div>
	</div>
</section>
<!--Page Header End-->
<?php
$arrFrom = array(
	array("type" => "text", "name" => "companyName", "title" => "Company Name", "validate" => "required,alpha", "attr" => "placeholder='Company Name*'"),
	array("type" => "text", "name" => "userName", "title" => "Name", "validate" => "required,alpha", "attr" => "placeholder='Name*'"),
	array("type" => "text", "name" => "userEmail", "title" => "Email", "validate" => "required,email", "attr" => "placeholder='Email*'"),
	array("type" => "text", "name" => "userMobile", "title" => "Mobile", "validate" => "required", "attr" => "placeholder='Mobile*'"),
	array("type" => "text", "name" => "makeOfMotor", "title" => "Make of Motor"),
	array("type" => "text", "name" => "kw", "title" => "KW"),
	array("type" => "text", "name" => "hp", "title" => "HP"),
	array("type" => "select", "name" => "dutyID", "value" => $dutyDD, "title" => "Duty", "attrp" => ' class="other" otherName="duty-other"'),
	array("type" => "text", "name" => "dutyOther", "attrp" => ' class="duty-other"  style="display:none"'),
	array("type" => "text", "name" => "rpm", "title" => "RPM")

);
$arrForm3 = array(
	array("type" => "select", "name" => "mountingID", "value" => $MountingDD, "title" => "Mounting", "attrp" => ' class="other" otherName="mounting-other"'),
	array("type" => "text", "name" => "mountingOther", "attrp" => ' class="mounting-other" style="display:none"'),
	array("type" => "select", "name" => "typeOfMotorID", "value" => $typeOfMotorDD, "title" => "Type of Motor", "attrp" => ' class="other" otherName="typeOfMotor-other"'),
	array("type" => "text", "name" => "typeOfMotorOther", "attrp" => ' class="typeOfMotor-other" style="display:none"'),
	array("type" => "select", "name" => "rotorTypeID", "value" => $rotorTypeDD, "title" => "Rotor Type"),
	array("type" => "select", "name" => "voltageID", "value" => $voltageDD, "title" => "Voltage", "attrp" => ' class="other" otherName="voltage-other"'),
	array("type" => "text", "name" => "voltageOther", "attrp" => ' class="voltage-other" style="display:none"'),
	array("type" => "select", "name" => "frequencyID", "value" => $frequencyDD, "title" => "Frequency"),
	array("type" => "select", "name" => "shaftExtensionID", "value" => $shaftExtensionDD, "title" => "Shaft Extension", "attrp" => ' class="other" otherName="shaft-extension-other"'),
	array("type" => "text", "name" => "shaftExtensionOther", "attrp" => ' class="shaft-extension-other" style="display:none"'),
	array("type" => "select", "name" => "expectedDeliveryTimeID", "value" => $expectedDeliveryTimeDD, "title" => "Expected Delivery Time", "attrp" => ' class="other" otherName="expect-delivery-time"'),
	array("type" => "text", "name" => "expectedDeliveryTimeOther", "attrp" => ' class="expect-delivery-time" style="display:none"'),
	array("type" => "checkbox", "name" => "offerRequirementIs", "value" => array($offerRequirementIsArr), "title" => "Offer Requirement Is", "attrp" => ' class="Requirement"'),
	array("type" => "file", "name" => "uploadFile", "title" => "upload File"),
	array("type" => "radio", "name" => "requirementIsForRplc", "value" => array($requirementForRplcArr, 0), "title" => "Requirement Is For Replacement:", "attrp" => ' class="requirement-replacement"')
);

$arrFrom1 = array(
	array("type" => "text", "name" => "makeOfMotorD", "title" => "Make of Motor:"),
	array("type" => "text", "name" => "kwD", "title" => "KW:"),
	array("type" => "text", "name" => "hpD", "title" => "HP:"),
	array("type" => "text", "name" => "rpmD", "title" => "RPM:"),
	array("type" => "text", "name" => "mounting", "title" => "Mounting:"),
	array("type" => "select", "name" => "poleID", "value" => $poleDD, "title" => "Pole:"),
	array("type" => "text", "name" => "application", "title" => "Application:"),
	array("type" => "file", "name" => "uploadFileD", "title" => "upload File")
);
$arrFrom2 = array(
	array("type" => "text", "name" => "otherSpec", "title" => "Any Other Specification:")
);

$MXFRM = new mxForm();
$MXFRM->xAction = "saveProductInquiry";
?>
<section class="inquiry-page">
	<div class="container">
		<div class="section-title text-center">
			<h2 class="section-title__title">AC Motor Form</h2>
		</div>
		<form name="productInquiryForm" auto="false" class="productInquiryForm" id="productInquiryForm" action="" method="post" enctype="multipart/form-data">
		<!-- reCAPTCHA v3 Token (hidden field) -->
		<input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">
			<div class="inquiry-wrap">
				<div class="left">
					<ul class="form-list">
						<?php echo $MXFRM->getForm($arrFrom); ?>
					</ul>
				</div>
				<div class="right">
					<ul class="form-list">
						<?php echo $MXFRM->getForm($arrForm3); ?>
					</ul>
				</div>
				<div class="w-100">
					<div class="motor-details" style='display:none'>
						<h4>Provide Existing Motor details</h4>
						<ul class="form-list">
							<?php echo $MXFRM->getForm($arrFrom1); ?>
						</ul>
					</div>
					<ul class="form-list">
						<?php echo $MXFRM->getForm($arrFrom2); ?>
					</ul>
				</div>
			</div>
			<?php echo $MXFRM->closeForm(); ?>
			<input type="hidden" name="pageType" id="pageType" value="add" />
			<a href="javascript:void(0)" class="fa-save button thm-btn" rel="productInquiryForm"> save </a>
		</form>
	</div>
</section>