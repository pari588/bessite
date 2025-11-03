<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/js/x-pump-inquiry.inc.js'); ?>"></script>
<?php
// Google reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', '6LeVCf0rAAAAAG3JjibEriASu2AwVx8v-6pxZHlZ');
define('RECAPTCHA_SECRET_KEY', '6LeVCf0rAAAAABhzHVTK76BApoP66LDaXdYaUBN-');

// Application Type Dropdown
$applicationTypeArr = array("1" => "Domestic", "2" => "Industrial", "3" => "Agricultural", "4" => "Commercial", "5" => "Sewage", "6" => "HVAC", "7" => "Firefighting", "8" => "Other");
$applicationTypeDD = getArrayDD(["data" => array("data" => $applicationTypeArr), "selected" => 0]);

// Installation Type Dropdown
$installationTypeArr = array("1" => "Surface", "2" => "Submersible", "3" => "Booster", "4" => "Dewatering", "5" => "Openwell", "6" => "Borewell", "7" => "Other");
$installationTypeDD = getArrayDD(["data" => array("data" => $installationTypeArr), "selected" => 0]);

// Operating Medium Dropdown
$operatingMediumArr = array("1" => "Clean water", "2" => "Muddy water", "3" => "Sewage", "4" => "Chemical", "5" => "Hot water", "6" => "Other");
$operatingMediumDD = getArrayDD(["data" => array("data" => $operatingMediumArr), "selected" => 0]);

// Water Source Dropdown
$waterSourceArr = array("1" => "Overhead tank", "2" => "Underground tank", "3" => "Borewell", "4" => "River", "5" => "Sump", "6" => "Other");
$waterSourceDD = getArrayDD(["data" => array("data" => $waterSourceArr), "selected" => 0]);

// City Dropdown
$cityArr = array("1" => "Mumbai", "2" => "Pune", "3" => "Ahmedabad", "4" => "Other");
$cityDD = getArrayDD(["data" => array("data" => $cityArr), "selected" => 0]);

// Power Supply Dropdown
$powerSupplyArr = array("1" => "Single Phase", "2" => "Three Phase");
$powerSupplyDD = getArrayDD(["data" => array("data" => $powerSupplyArr), "selected" => 0]);

// Automation Needed Dropdown
$automationArr = array("1" => "Yes", "2" => "No");
$automationDD = getArrayDD(["data" => array("data" => $automationArr), "selected" => 0]);

// Preferred Brand Dropdown
$brandArr = array("1" => "Crompton", "2" => "CG Power", "3" => "Kirloskar", "4" => "Open to suggestion");
$brandDD = getArrayDD(["data" => array("data" => $brandArr), "selected" => 0]);

// Material Preference Dropdown
$materialArr = array("1" => "Cast Iron", "2" => "Stainless Steel", "3" => "Bronze", "4" => "Plastic", "5" => "Open to suggestion");
$materialDD = getArrayDD(["data" => array("data" => $materialArr), "selected" => 0]);

// Pump Types Checkbox array
$pumpTypesArr = array("1" => "Centrifugal", "2" => "Jet", "3" => "Submersible", "4" => "Monoblock", "5" => "Borewell", "6" => "Booster", "7" => "Self-Priming", "8" => "Others");
$contactTimeArr = array("1" => "Morning", "2" => "Afternoon", "3" => "Evening");

$MXFRM = new mxForm();
$MXFRM->xAction = "savePumpInquiry";
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
				<li>Pump Enquiry Form</li>
			</ul>
			<h1 style="color: #ffffff;">Pump Enquiry Form</h1>
		</div>
	</div>
</section>
<!--Page Header End-->

<!--Pump Inquiry Form Section Start-->
<section class="pump-inquiry-section">
	<div class="container">
		<div class="section-title text-center">
			<h2 class="section-title__title">Pump Enquiry Form</h2>
			<p class="form-description">Please fill out the form below with your pump requirements. Our team will contact you shortly.</p>
		</div>

		<form name="pumpInquiryForm" class="pumpInquiryForm" id="pumpInquiryForm" action="" method="post" enctype="multipart/form-data">

			<!-- SECTION 1: CUSTOMER DETAILS -->
			<div class="form-section">
				<h3 class="form-section__title">1. Customer Details</h3>
				<div class="form-grid">
					<div class="form-group">
						<label for="fullName">Full Name <span class="required">*</span></label>
						<input type="text" id="fullName" name="fullName" class="form-input" placeholder="Enter your full name" required>
						<span class="error-msg" style="display:none;"></span>
					</div>
					<div class="form-group">
						<label for="companyName">Company/Organization Name</label>
						<input type="text" id="companyName" name="companyName" class="form-input" placeholder="Enter company name">
					</div>
					<div class="form-group">
						<label for="userEmail">Email Address <span class="required">*</span></label>
						<input type="email" id="userEmail" name="userEmail" class="form-input" placeholder="Enter your email" required>
						<span class="error-msg" style="display:none;"></span>
					</div>
					<div class="form-group">
						<label for="userMobile">Mobile Number <span class="required">*</span></label>
						<input type="tel" id="userMobile" name="userMobile" class="form-input" placeholder="+91 Mobile number" required>
						<span class="error-msg" style="display:none;"></span>
					</div>
					<div class="form-group">
						<label for="phoneNumber">Phone Number (Optional)</label>
						<input type="tel" id="phoneNumber" name="phoneNumber" class="form-input" placeholder="Landline number">
					</div>
					<div class="form-group form-group--full">
						<label for="address">Address / Location of Installation</label>
						<textarea id="address" name="address" class="form-input form-textarea" placeholder="Enter installation address" rows="3"></textarea>
					</div>
					<div class="form-group">
						<label for="city">City <span class="required">*</span></label>
						<select id="city" name="city" class="form-input form-select" required>
							<option value="">Select City</option>
							<?php foreach($cityArr as $k => $v) { ?>
								<option value="<?php echo $v; ?>"><?php echo $v; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<label for="pinCode">Pin Code</label>
						<input type="number" id="pinCode" name="pinCode" class="form-input" placeholder="6-digit pin code">
					</div>
					<div class="form-group">
						<label for="preferredContactTime">Preferred Contact Time</label>
						<select id="preferredContactTime" name="preferredContactTime" class="form-input form-select">
							<option value="">Select Time</option>
							<?php foreach($contactTimeArr as $k => $v) { ?>
								<option value="<?php echo $v; ?>"><?php echo $v; ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>

			<!-- SECTION 2: APPLICATION DETAILS -->
			<div class="form-section">
				<h3 class="form-section__title">2. Application Details</h3>
				<div class="form-grid">
					<div class="form-group">
						<label for="applicationTypeID">Type of Application <span class="required">*</span></label>
						<select id="applicationTypeID" name="applicationTypeID" class="form-input form-select" required>
							<option value="">Select Application Type</option>
							<?php foreach($applicationTypeArr as $k => $v) { ?>
								<option value="<?php echo $v; ?>"><?php echo $v; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group form-group--full">
						<label for="purposeOfPump">Purpose of Pump</label>
						<input type="text" id="purposeOfPump" name="purposeOfPump" class="form-input" placeholder="e.g., Water supply, Irrigation, etc.">
					</div>
					<div class="form-group">
						<label for="installationTypeID">Installation Type <span class="required">*</span></label>
						<select id="installationTypeID" name="installationTypeID" class="form-input form-select" required>
							<option value="">Select Installation Type</option>
							<?php foreach($installationTypeArr as $k => $v) { ?>
								<option value="<?php echo $v; ?>"><?php echo $v; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<label for="operatingMediumID">Operating Medium <span class="required">*</span></label>
						<select id="operatingMediumID" name="operatingMediumID" class="form-input form-select" required>
							<option value="">Select Operating Medium</option>
							<?php foreach($operatingMediumArr as $k => $v) { ?>
								<option value="<?php echo $v; ?>"><?php echo $v; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<label for="waterSourceID">Water Source <span class="required">*</span></label>
						<select id="waterSourceID" name="waterSourceID" class="form-input form-select" required>
							<option value="">Select Water Source</option>
							<?php foreach($waterSourceArr as $k => $v) { ?>
								<option value="<?php echo $v; ?>"><?php echo $v; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<label for="requiredHead">Required Head (meters)</label>
						<input type="number" id="requiredHead" name="requiredHead" class="form-input" placeholder="e.g., 10" step="0.01">
					</div>
					<div class="form-group">
						<label for="requiredDischarge">Required Discharge (LPM or m³/hr)</label>
						<input type="text" id="requiredDischarge" name="requiredDischarge" class="form-input" placeholder="e.g., 100 LPM">
					</div>
					<div class="form-group">
						<label for="pumpingDistance">Total Pumping Distance (meters)</label>
						<input type="number" id="pumpingDistance" name="pumpingDistance" class="form-input" placeholder="e.g., 50" step="0.01">
					</div>
					<div class="form-group">
						<label for="heightDifference">Height Difference (meters)</label>
						<input type="number" id="heightDifference" name="heightDifference" class="form-input" placeholder="e.g., 5" step="0.01">
					</div>
					<div class="form-group">
						<label for="pipeSize">Pipe Size (inches)</label>
						<input type="text" id="pipeSize" name="pipeSize" class="form-input" placeholder="e.g., 2 inch">
					</div>
					<div class="form-group">
						<label for="powerSupplyID">Power Supply Available <span class="required">*</span></label>
						<select id="powerSupplyID" name="powerSupplyID" class="form-input form-select" required>
							<option value="">Select Power Supply</option>
							<?php foreach($powerSupplyArr as $k => $v) { ?>
								<option value="<?php echo $v; ?>"><?php echo $v; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<label for="operatingHours">Operating Hours per Day</label>
						<input type="number" id="operatingHours" name="operatingHours" class="form-input" placeholder="e.g., 8" min="0" max="24">
					</div>
					<div class="form-group">
						<label for="automationNeededID">Automation Needed</label>
						<select id="automationNeededID" name="automationNeededID" class="form-input form-select">
							<option value="">Select Option</option>
							<?php foreach($automationArr as $k => $v) { ?>
								<option value="<?php echo $v; ?>"><?php echo $v; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<label for="existingPumpModel">Existing Pump Model</label>
						<input type="text" id="existingPumpModel" name="existingPumpModel" class="form-input" placeholder="e.g., ABC Model XYZ">
					</div>
					<div class="form-group form-group--full">
						<label for="uploadedFile">Upload Photos/Documents (PDF, JPG, PNG - Max 5MB)</label>
						<input type="file" id="uploadedFile" name="uploadedFile" class="form-input form-file" accept=".pdf,.jpg,.jpeg,.png">
						<small class="file-hint">Accepted formats: PDF, JPG, PNG | Max size: 5MB</small>
					</div>
				</div>
			</div>

			<!-- SECTION 3: PRODUCT PREFERENCES -->
			<div class="form-section">
				<h3 class="form-section__title">3. Product Preferences</h3>
				<div class="form-grid">
					<div class="form-group">
						<label for="preferredBrandID">Preferred Brand</label>
						<select id="preferredBrandID" name="preferredBrandID" class="form-input form-select">
							<option value="">Select Brand</option>
							<?php foreach($brandArr as $k => $v) { ?>
								<option value="<?php echo $v; ?>"><?php echo $v; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group form-group--full">
						<label>Pump Type Interested In</label>
						<div class="checkbox-group">
							<?php foreach($pumpTypesArr as $k => $v) { ?>
								<div class="checkbox-item">
									<input type="checkbox" id="pumpType_<?php echo $k; ?>" name="pumpTypesInterested[]" value="<?php echo $v; ?>">
									<label for="pumpType_<?php echo $k; ?>" class="checkbox-label"><?php echo $v; ?></label>
								</div>
							<?php } ?>
						</div>
					</div>
					<div class="form-group">
						<label for="materialPreferenceID">Material Preference</label>
						<select id="materialPreferenceID" name="materialPreferenceID" class="form-input form-select">
							<option value="">Select Material</option>
							<?php foreach($materialArr as $k => $v) { ?>
								<option value="<?php echo $v; ?>"><?php echo $v; ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group">
						<label for="motorRating">Motor HP/kW</label>
						<input type="text" id="motorRating" name="motorRating" class="form-input" placeholder="e.g., 2 HP or 1.5 KW">
					</div>
					<div class="form-group">
						<label for="quantityRequired">Quantity Required</label>
						<input type="number" id="quantityRequired" name="quantityRequired" class="form-input" placeholder="e.g., 5" min="1">
					</div>
				</div>
			</div>

			<!-- SECTION 4: CONSENT & SUBMISSION -->
			<div class="form-section">
				<div class="form-group form-group--full">
					<div class="consent-checkbox">
						<input type="checkbox" id="consentGiven" name="consentGiven" value="1" required>
						<label for="consentGiven" class="consent-label">
							I authorize Bombay Engineering Syndicate to contact me regarding this inquiry. <span class="required">*</span>
						</label>
					</div>
					<span class="error-msg" style="display:none;"></span>
				</div>
			</div>

			<!-- Hidden Fields -->
			<?php echo $MXFRM->closeForm(); ?>
			<input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">

			<!-- Submit Button -->
			<div class="form-submit">
				<button type="submit" class="submit-btn thm-btn">Submit Inquiry</button>
				<p class="form-note">* Required fields</p>
			</div>
		</form>
	</div>
</section>
<!--Pump Inquiry Form Section End-->

<!-- Load Google reCAPTCHA API -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<script>
	// Initialize reCAPTCHA v3
	console.log('Pump Inquiry reCAPTCHA script initializing...');
	if (typeof grecaptcha !== 'undefined') {
		console.log('grecaptcha object found, reCAPTCHA API loaded successfully');
	}
	window.addEventListener('load', function() {
		var badge = document.querySelector('.grecaptcha-badge');
		if (badge) {
			console.log('reCAPTCHA badge found');
		}
	});
</script>

<!-- Pump Inquiry Form Styles -->
<style>
/* ========================================
   Pump Inquiry Form - Responsive Design
   ======================================== */

.pump-inquiry-section {
	padding: 60px 15px;
	background-color: #ffffff;
}

.form-description {
	color: #666;
	font-size: 16px;
	margin-bottom: 40px;
}

/* Form Structure */
.pumpInquiryForm {
	max-width: 900px;
	margin: 0 auto;
}

/* Form Sections */
.form-section {
	margin-bottom: 40px;
	padding: 30px;
	background-color: #f9f9f9;
	border-radius: 8px;
	border-left: 4px solid #157bba;
}

.form-section__title {
	font-family: 'Libre Baskerville', serif;
	font-size: 20px;
	font-weight: 700;
	color: #27252a;
	margin-bottom: 25px;
	padding-bottom: 15px;
	border-bottom: 1px solid #ddd;
}

/* Grid Layout */
.form-grid {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 20px;
}

.form-group--full {
	grid-column: 1 / -1;
}

/* Form Groups */
.form-group {
	display: flex;
	flex-direction: column;
}

.form-group label {
	font-family: 'Manrope', sans-serif;
	font-size: 14px;
	font-weight: 600;
	color: #27252a;
	margin-bottom: 8px;
	display: flex;
	align-items: center;
	gap: 4px;
}

.required {
	color: #e74c3c;
	font-weight: bold;
}

/* Form Inputs */
.form-input {
	font-family: 'Manrope', sans-serif;
	font-size: 14px;
	padding: 12px 15px;
	border: 1px solid #ddd;
	border-radius: 5px;
	background-color: #ffffff;
	transition: all 0.3s ease;
	color: #333;
}

.form-input:focus {
	outline: none;
	border-color: #157bba;
	box-shadow: 0 0 0 3px rgba(21, 123, 186, 0.1);
	background-color: #fafafa;
}

.form-input::placeholder {
	color: #999;
}

.form-textarea {
	resize: vertical;
	min-height: 80px;
	font-family: 'Manrope', sans-serif;
}

.form-select {
	appearance: none;
	background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
	background-repeat: no-repeat;
	background-position: right 10px center;
	background-size: 20px;
	padding-right: 40px;
	cursor: pointer;
}

.form-file {
	padding: 8px;
	cursor: pointer;
}

.form-file::file-selector-button {
	font-family: 'Manrope', sans-serif;
	padding: 8px 15px;
	background-color: #157bba;
	color: #ffffff;
	border: none;
	border-radius: 4px;
	cursor: pointer;
	font-weight: 600;
	transition: background-color 0.3s ease;
	margin-right: 10px;
}

.form-file::file-selector-button:hover {
	background-color: #0f5a8f;
}

.file-hint {
	font-size: 12px;
	color: #666;
	margin-top: 5px;
}

/* Checkbox Group */
.checkbox-group {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
	gap: 15px;
	margin-top: 10px;
}

.checkbox-item {
	display: flex;
	align-items: center;
	gap: 8px;
}

.checkbox-item input[type="checkbox"] {
	width: 18px;
	height: 18px;
	cursor: pointer;
	accent-color: #157bba;
}

.checkbox-label {
	font-family: 'Manrope', sans-serif;
	font-size: 14px;
	color: #333;
	cursor: pointer;
	margin: 0;
}

/* Consent Checkbox */
.consent-checkbox {
	display: flex;
	align-items: flex-start;
	gap: 10px;
	padding: 15px;
	background-color: #f0f8ff;
	border-radius: 5px;
	margin-top: 10px;
}

.consent-checkbox input[type="checkbox"] {
	width: 20px;
	height: 20px;
	margin-top: 2px;
	cursor: pointer;
	accent-color: #157bba;
	flex-shrink: 0;
}

.consent-label {
	font-family: 'Manrope', sans-serif;
	font-size: 14px;
	color: #333;
	cursor: pointer;
	line-height: 1.5;
	margin: 0;
}

/* Error Messages */
.error-msg {
	font-size: 12px;
	color: #e74c3c;
	margin-top: 5px;
	font-weight: 500;
}

.form-input.error {
	border-color: #e74c3c;
	background-color: #fee;
}

/* Submit Section */
.form-submit {
	text-align: center;
	margin-top: 40px;
	padding-top: 30px;
	border-top: 2px solid #ddd;
}

.submit-btn {
	font-family: 'Manrope', sans-serif;
	font-size: 16px;
	font-weight: 700;
	padding: 15px 50px;
	background-color: #157bba;
	color: #ffffff;
	border: none;
	border-radius: 5px;
	cursor: pointer;
	transition: all 0.3s ease;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.submit-btn:hover {
	background-color: #0f5a8f;
	box-shadow: 0 5px 15px rgba(21, 123, 186, 0.3);
	transform: translateY(-2px);
}

.submit-btn:active {
	transform: translateY(0);
}

.form-note {
	font-size: 12px;
	color: #666;
	margin-top: 10px;
}

/* Responsive Design */
@media (max-width: 768px) {
	.pump-inquiry-section {
		padding: 40px 15px;
	}

	.form-grid {
		grid-template-columns: 1fr;
	}

	.form-section {
		padding: 20px;
		margin-bottom: 25px;
	}

	.form-section__title {
		font-size: 18px;
		margin-bottom: 20px;
	}

	.checkbox-group {
		grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
	}

	.submit-btn {
		width: 100%;
		padding: 12px 20px;
	}
}

@media (max-width: 480px) {
	.pump-inquiry-section {
		padding: 20px 10px;
	}

	.form-section {
		padding: 15px;
		border-left-width: 3px;
	}

	.form-section__title {
		font-size: 16px;
		margin-bottom: 15px;
	}

	.form-group label {
		font-size: 13px;
	}

	.form-input {
		font-size: 13px;
		padding: 10px 12px;
	}

	.checkbox-group {
		grid-template-columns: 1fr;
		gap: 12px;
	}

	.form-submit {
		margin-top: 30px;
		padding-top: 20px;
	}
}
</style>