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
				<li>Pump Inquiry Form</li>
			</ul>
			<h2>Pump Inquiry Form</h2>
		</div>
	</div>
</section>
<!--Page Header End-->

<?php
// Initialize form framework
$MXFRM = new mxForm();
$MXFRM->xAction = "savePumpInquiry";

// ===================================================================
// SECTION 1: CUSTOMER DETAILS
// ===================================================================

// City options
$cityOptions = array(
	"1" => "Mumbai",
	"2" => "Pune",
	"3" => "Ahmedabad",
	"4" => "Other"
);

// Contact Time options
$contactTimeOptions = array(
	"1" => "Morning (6 AM - 12 PM)",
	"2" => "Afternoon (12 PM - 5 PM)",
	"3" => "Evening (5 PM - 10 PM)"
);

$arrCustomerDetails = array(
	array("type" => "text", "name" => "fullName", "title" => "Full Name *", "validate" => "required", "attr" => "placeholder='Enter your full name*'"),
	array("type" => "text", "name" => "companyName", "title" => "Company / Organization Name", "validate" => "", "attr" => "placeholder='Company or organization name'"),
	array("type" => "text", "name" => "userEmail", "title" => "Email Address *", "validate" => "required,email", "attr" => "placeholder='Enter your email*'"),
	array("type" => "text", "name" => "userMobile", "title" => "Mobile Number *", "validate" => "required", "attr" => "placeholder='Indian mobile number with +91 prefix or 10 digits*'"),
	array("type" => "textarea", "name" => "address", "title" => "Address / Installation Location", "validate" => "", "attr" => "placeholder='Complete installation address'"),
	array("type" => "select", "name" => "city", "title" => "City *", "validate" => "required", "value" => getArrayDD(array("data" => array("data" => $cityOptions), "selected" => ""))),
	array("type" => "text", "name" => "pinCode", "title" => "Pin Code", "validate" => "", "attr" => "placeholder='6-digit pin code' maxlength='6' pattern='[0-9]{6}'"),
	array("type" => "select", "name" => "preferredContactTime", "title" => "Preferred Contact Time", "validate" => "", "value" => getArrayDD(array("data" => array("data" => $contactTimeOptions), "selected" => ""))),
);

// ===================================================================
// SECTION 2: APPLICATION DETAILS
// ===================================================================

// Application Type options
$appTypeOptions = array(
	"1" => "Domestic",
	"2" => "Industrial",
	"3" => "Agricultural",
	"4" => "Commercial",
	"5" => "Sewage",
	"6" => "HVAC",
	"7" => "Firefighting",
	"8" => "Other"
);

// Installation Type options
$installTypeOptions = array(
	"1" => "Surface",
	"2" => "Submersible",
	"3" => "Booster",
	"4" => "Dewatering",
	"5" => "Openwell",
	"6" => "Borewell"
);

// Operating Medium options
$operatingMediumOptions = array(
	"1" => "Clean water",
	"2" => "Muddy water",
	"3" => "Sewage",
	"4" => "Chemical",
	"5" => "Hot water",
	"6" => "Other"
);

// Water Source options
$waterSourceOptions = array(
	"1" => "Overhead tank",
	"2" => "Underground tank",
	"3" => "Borewell",
	"4" => "River",
	"5" => "Sump",
	"6" => "Other"
);

// Power Supply options
$powerSupplyOptions = array(
	"1" => "Single Phase",
	"2" => "Three Phase"
);

// Automation options
$automationOptions = array(
	"1" => "Yes",
	"2" => "No"
);

$arrApplicationDetails = array(
	array("type" => "select", "name" => "applicationTypeID", "title" => "Type of Application *", "validate" => "required", "value" => getArrayDD(array("data" => array("data" => $appTypeOptions), "selected" => ""))),
	array("type" => "textarea", "name" => "purposeOfPump", "title" => "Purpose of Pump", "validate" => "", "attr" => "placeholder='Explain the purpose of pump'"),
	array("type" => "select", "name" => "installationTypeID", "title" => "Installation Type *", "validate" => "required", "value" => getArrayDD(array("data" => array("data" => $installTypeOptions), "selected" => ""))),
	array("type" => "select", "name" => "operatingMediumID", "title" => "Operating Medium *", "validate" => "required", "value" => getArrayDD(array("data" => array("data" => $operatingMediumOptions), "selected" => ""))),
	array("type" => "select", "name" => "waterSourceID", "title" => "Water Source *", "validate" => "required", "value" => getArrayDD(array("data" => array("data" => $waterSourceOptions), "selected" => ""))),
	array("type" => "text", "name" => "requiredHead", "title" => "Required Head (meters)", "validate" => "", "attr" => "placeholder='Head in meters' inputmode='decimal'"),
	array("type" => "text", "name" => "requiredDischarge", "title" => "Required Discharge (LPM or m³/hr)", "validate" => "", "attr" => "placeholder='e.g., 100 LPM or 6 m³/hr'"),
	array("type" => "text", "name" => "pumpingDistance", "title" => "Total Pumping Distance (m)", "validate" => "", "attr" => "placeholder='Distance in meters' inputmode='decimal'"),
	array("type" => "text", "name" => "heightDifference", "title" => "Height Difference (m)", "validate" => "", "attr" => "placeholder='Height in meters' inputmode='decimal'"),
	array("type" => "text", "name" => "pipeSize", "title" => "Pipe Size (inches)", "validate" => "", "attr" => "placeholder='Pipe size in inches'"),
	array("type" => "select", "name" => "powerSupplyID", "title" => "Power Supply Available *", "validate" => "required", "value" => getArrayDD(array("data" => array("data" => $powerSupplyOptions), "selected" => ""))),
	array("type" => "text", "name" => "operatingHours", "title" => "Operating Hours per Day", "validate" => "", "attr" => "placeholder='Hours per day (0-24)' inputmode='decimal'"),
	array("type" => "select", "name" => "automationNeeded", "title" => "Automation Needed", "validate" => "", "value" => getArrayDD(array("data" => array("data" => $automationOptions), "selected" => ""))),
	array("type" => "text", "name" => "existingPumpModel", "title" => "Existing Pump Model", "validate" => "", "attr" => "placeholder='Model name/number if replacing existing pump'"),
	array("type" => "file", "name" => "uploadedFile", "title" => "Upload Photos/Documents", "validate" => "", "attr" => "accept='.jpg,.jpeg,.png,.pdf' placeholder='Select file'"),
);

// ===================================================================
// SECTION 3: PRODUCT PREFERENCES
// ===================================================================

// Brand options
$brandOptions = array(
	"1" => "Crompton",
	"2" => "CG Power",
	"3" => "Kirloskar",
	"4" => "Open to suggestion"
);

// Pump Types options for checkbox
$pumpTypeOptions = array(
	"1" => "Centrifugal",
	"2" => "Jet",
	"3" => "Submersible",
	"4" => "Monoblock",
	"5" => "Borewell",
	"6" => "Booster",
	"7" => "Self-Priming",
	"8" => "Others"
);

// Material options
$materialOptions = array(
	"1" => "Cast Iron",
	"2" => "Stainless Steel",
	"3" => "Bronze",
	"4" => "Plastic",
	"5" => "Open to suggestion"
);

$arrProductPreferences = array(
	array("type" => "select", "name" => "preferredBrand", "title" => "Preferred Brand", "validate" => "", "value" => getArrayDD(array("data" => array("data" => $brandOptions), "selected" => ""))),
	array("type" => "checkbox", "name" => "pumpTypesInterested", "title" => "Pump Type Interested In", "validate" => "", "value" => array($pumpTypeOptions)),
	array("type" => "select", "name" => "materialPreference", "title" => "Material Preference", "validate" => "", "value" => getArrayDD(array("data" => array("data" => $materialOptions), "selected" => ""))),
	array("type" => "text", "name" => "motorRating", "title" => "Motor HP/kW", "validate" => "", "attr" => "placeholder='Motor rating in HP or kW'"),
	array("type" => "text", "name" => "quantityRequired", "title" => "Quantity Required", "validate" => "", "attr" => "placeholder='Number of pumps' inputmode='numeric'"),
);
?>

<section class="inquiry-page">
	<div class="container">
		<div class="section-title text-center">
			<h2 class="section-title__title">Pump Inquiry Form</h2>
			<p class="section-title__text">Please fill in all required fields (*) to submit your inquiry</p>
		</div>

		<form name="pumpInquiryForm" class="pumpInquiryForm" id="pumpInquiryForm" action="" method="post" enctype="multipart/form-data" auto="false">
			<!-- reCAPTCHA v3 Token (hidden field) -->
			<input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">

			<!-- ========== SECTION 1: CUSTOMER DETAILS ========== -->
			<div class="inquiry-section">
				<div class="section-header">
					<h3>Section 1: Customer Details</h3>
					<p>Please provide your contact information</p>
				</div>

				<div class="form-grid">
					<?php echo $MXFRM->getForm($arrCustomerDetails); ?>
				</div>
			</div>

			<!-- ========== SECTION 2: APPLICATION DETAILS ========== -->
			<div class="inquiry-section">
				<div class="section-header">
					<h3>Section 2: Application Details</h3>
					<p>Tell us about your pump application and requirements</p>
				</div>

				<div class="form-grid">
					<?php echo $MXFRM->getForm($arrApplicationDetails); ?>
				</div>
			</div>

			<!-- ========== SECTION 3: PRODUCT PREFERENCES ========== -->
			<div class="inquiry-section">
				<div class="section-header">
					<h3>Section 3: Product Preferences</h3>
					<p>Let us know your product preferences</p>
				</div>

				<div class="form-grid">
					<?php echo $MXFRM->getForm($arrProductPreferences); ?>
				</div>
			</div>

			<!-- ========== SECTION 4: CONSENT & SUBMISSION ========== -->
			<div class="inquiry-section">
				<div class="section-header">
					<h3>Section 4: Consent & Submission</h3>
				</div>

				<div class="form-consent">
					<label class="consent-checkbox">
						<input type="checkbox" name="consentGiven" id="consentGiven" value="1" required>
						<span class="checkmark"></span>
						<span class="consent-text">I authorize Bombay Engineering Syndicate to contact me regarding this inquiry.</span>
					</label>
					<small class="form-help">This consent is required to proceed with your inquiry.</small>
				</div>
			</div>

			<!-- Hidden fields -->
			<?php echo $MXFRM->closeForm(); ?>
			<input type="hidden" name="pageType" id="pageType" value="add" />

			<!-- Submit Button -->
			<div class="form-actions">
				<button type="button" class="fa-save button thm-btn" rel="pumpInquiryForm"> Submit Inquiry </button>
				<p class="form-info">We will review your inquiry and get back to you shortly.</p>
			</div>
		</form>
	</div>
</section>

<!-- Inline Styles for Extended Form -->
<style>
.inquiry-page {
	padding: 40px 0;
	background: #f9f9f9;
}

.inquiry-page .container {
	background: white;
	padding: 40px;
	border-radius: 8px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.inquiry-section {
	margin-bottom: 40px;
	padding-bottom: 30px;
	border-bottom: 1px solid #e0e0e0;
}

.inquiry-section:last-child {
	border-bottom: none;
}

.section-header {
	margin-bottom: 25px;
}

.section-header h3 {
	font-size: 24px;
	color: #157bba;
	margin-bottom: 8px;
	font-weight: 600;
}

.section-header p {
	color: #666;
	font-size: 14px;
	margin: 0;
}

.form-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
}

@media (max-width: 768px) {
	.form-grid {
		grid-template-columns: 1fr;
	}

	.inquiry-page .container {
		padding: 20px;
	}
}

.form-consent {
	margin: 20px 0;
	padding: 20px;
	background: #f5f5f5;
	border-left: 4px solid #157bba;
	border-radius: 4px;
}

.consent-checkbox {
	display: flex;
	align-items: flex-start;
	cursor: pointer;
	user-select: none;
}

.consent-checkbox input {
	margin-right: 12px;
	margin-top: 2px;
	cursor: pointer;
}

.consent-text {
	color: #333;
	font-size: 14px;
	line-height: 1.5;
}

.form-help {
	display: block;
	color: #999;
	font-size: 12px;
	margin-top: 8px;
	margin-left: 26px;
}

.form-actions {
	margin-top: 40px;
	text-align: center;
}

.btn {
	display: inline-block;
	padding: 12px 40px;
	border: none;
	border-radius: 4px;
	font-size: 16px;
	cursor: pointer;
	transition: all 0.3s ease;
	font-weight: 600;
}

.btn-primary {
	background: #157bba;
	color: white;
}

.btn-primary:hover {
	background: #0f5a8f;
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(21, 123, 186, 0.3);
}

.btn-primary:disabled {
	background: #ccc;
	cursor: not-allowed;
	transform: none;
}

.btn-loader {
	display: inline-block;
	margin-left: 8px;
}

.form-info {
	color: #666;
	font-size: 14px;
	margin-top: 15px;
	margin-bottom: 0;
}

.form-list {
	list-style: none;
	padding: 0;
	margin: 0;
}

.form-list li {
	margin-bottom: 20px;
}

.form-list label {
	display: block;
	margin-bottom: 8px;
	color: #333;
	font-weight: 500;
	font-size: 14px;
}

.form-list .required::after {
	content: ' *';
	color: #e74c3c;
}

.form-list input[type="text"],
.form-list input[type="email"],
.form-list input[type="number"],
.form-list input[type="file"],
.form-list textarea,
.form-list select {
	width: 100%;
	padding: 10px;
	border: 1px solid #ddd;
	border-radius: 4px;
	font-size: 14px;
	font-family: inherit;
	transition: border-color 0.3s ease;
}

.form-list input[type="text"]:focus,
.form-list input[type="email"]:focus,
.form-list input[type="number"]:focus,
.form-list input[type="file"]:focus,
.form-list textarea:focus,
.form-list select:focus {
	border-color: #157bba;
	outline: none;
	box-shadow: 0 0 0 3px rgba(21, 123, 186, 0.1);
}

.form-list textarea {
	min-height: 100px;
	resize: vertical;
}

.form-list .checkbox-group {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.form-list .checkbox-item {
	display: flex;
	align-items: center;
}

.form-list .checkbox-item input[type="checkbox"] {
	margin-right: 10px;
	cursor: pointer;
}

.form-list .checkbox-item label {
	margin-bottom: 0;
	cursor: pointer;
	font-weight: normal;
}

.form-error {
	color: #e74c3c;
	font-size: 12px;
	margin-top: 4px;
}
</style>
