$(document).ready(function () {
    //Start: To submit pump inquiry form
    var frm = $("form#pumpInquiryForm");
    frm.mxinitform({
        callback: callbackPumpInquiry,
        url: SITEURL + "/xsite/mod/pump-inquiry/x-pump-inquiry-inc.php"
    });
    localStorage.removeItem(SITEURL);

    // Add comprehensive form validation before submission
    frm.on('submit', function (e) {
        // Execute reCAPTCHA v3 token and wait for it
        var self = this;

        grecaptcha.ready(function() {
            grecaptcha.execute('6LeVCf0rAAAAAG3JjibEriASu2AwVx8v-6pxZHlZ', {action: 'pump_inquiry'}).then(function(token) {
                document.getElementById('g-recaptcha-response').value = token;
            });
        });
        // ========== SECTION 1: CUSTOMER DETAILS VALIDATION ==========

        // Full Name validation
        var fullName = $('input[name="fullName"]').val().trim();
        if (!fullName) {
            $.mxalert({ msg: "Please enter your full name" });
            $('input[name="fullName"]').focus();
            return false;
        }
        if (fullName.length < 3) {
            $.mxalert({ msg: "Full name must be at least 3 characters" });
            $('input[name="fullName"]').focus();
            return false;
        }
        var nameRegex = /^[a-zA-Z\s.,'&()\-]{3,100}$/;
        if (!nameRegex.test(fullName)) {
            $.mxalert({ msg: "Full name contains invalid characters" });
            $('input[name="fullName"]').focus();
            return false;
        }

        // Email validation
        var email = $('input[name="userEmail"]').val().trim();
        if (!email) {
            $.mxalert({ msg: "Please enter your email address" });
            $('input[name="userEmail"]').focus();
            return false;
        }
        var emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        if (!emailRegex.test(email)) {
            $.mxalert({ msg: "Please enter a valid email address" });
            $('input[name="userEmail"]').focus();
            return false;
        }

        // Mobile number validation
        var mobile = $('input[name="userMobile"]').val().replace(/[\s\-\(\)]/g, '');
        if (!mobile) {
            $.mxalert({ msg: "Please enter your mobile number" });
            $('input[name="userMobile"]').focus();
            return false;
        }

        // Remove country code if present
        if (mobile.startsWith("+91")) {
            mobile = mobile.substring(3);
        } else if (mobile.startsWith("0091")) {
            mobile = mobile.substring(4);
        } else if (mobile.startsWith("91") && mobile.length > 10) {
            mobile = mobile.substring(2);
        }

        // Validate the 10-digit number (Indian mobile starts with 6,7,8,9)
        var indianMobileRegex = /^[6-9]\d{9}$/;
        if (!indianMobileRegex.test(mobile)) {
            $.mxalert({ msg: "Please enter a valid Indian mobile number (10 digits starting with 6, 7, 8, or 9)" });
            $('input[name="userMobile"]').focus();
            return false;
        }

        // City validation
        var city = $('select[name="city"]').val();
        if (!city) {
            $.mxalert({ msg: "Please select a city" });
            $('select[name="city"]').focus();
            return false;
        }

        // Pin Code validation (if provided)
        var pinCode = $('input[name="pinCode"]').val().trim();
        if (pinCode && !preg_match(/^[0-9]{6}$/, pinCode)) {
            $.mxalert({ msg: "Pin code must be exactly 6 digits" });
            $('input[name="pinCode"]').focus();
            return false;
        }

        // ========== SECTION 2: APPLICATION DETAILS VALIDATION ==========

        // Application Type validation
        var applicationType = $('select[name="applicationTypeID"]').val();
        if (!applicationType) {
            $.mxalert({ msg: "Please select type of application" });
            $('select[name="applicationTypeID"]').focus();
            return false;
        }

        // Installation Type validation
        var installationType = $('select[name="installationTypeID"]').val();
        if (!installationType) {
            $.mxalert({ msg: "Please select installation type" });
            $('select[name="installationTypeID"]').focus();
            return false;
        }

        // Operating Medium validation
        var operatingMedium = $('select[name="operatingMediumID"]').val();
        if (!operatingMedium) {
            $.mxalert({ msg: "Please select operating medium" });
            $('select[name="operatingMediumID"]').focus();
            return false;
        }

        // Water Source validation
        var waterSource = $('select[name="waterSourceID"]').val();
        if (!waterSource) {
            $.mxalert({ msg: "Please select water source" });
            $('select[name="waterSourceID"]').focus();
            return false;
        }

        // Power Supply validation
        var powerSupply = $('select[name="powerSupplyID"]').val();
        if (!powerSupply) {
            $.mxalert({ msg: "Please select power supply type" });
            $('select[name="powerSupplyID"]').focus();
            return false;
        }

        // Numeric fields validation (if provided)
        var requiredHead = $('input[name="requiredHead"]').val().trim();
        if (requiredHead && (isNaN(requiredHead) || requiredHead < 0)) {
            $.mxalert({ msg: "Required head must be a positive number" });
            $('input[name="requiredHead"]').focus();
            return false;
        }

        var pumpingDistance = $('input[name="pumpingDistance"]').val().trim();
        if (pumpingDistance && (isNaN(pumpingDistance) || pumpingDistance < 0)) {
            $.mxalert({ msg: "Pumping distance must be a positive number" });
            $('input[name="pumpingDistance"]').focus();
            return false;
        }

        var heightDifference = $('input[name="heightDifference"]').val().trim();
        if (heightDifference && (isNaN(heightDifference) || heightDifference < 0)) {
            $.mxalert({ msg: "Height difference must be a positive number" });
            $('input[name="heightDifference"]').focus();
            return false;
        }

        var operatingHours = $('input[name="operatingHours"]').val().trim();
        if (operatingHours && (isNaN(operatingHours) || operatingHours < 0 || operatingHours > 24)) {
            $.mxalert({ msg: "Operating hours must be between 0 and 24" });
            $('input[name="operatingHours"]').focus();
            return false;
        }

        var quantityRequired = $('input[name="quantityRequired"]').val().trim();
        if (quantityRequired && (isNaN(quantityRequired) || quantityRequired < 1)) {
            $.mxalert({ msg: "Quantity required must be at least 1" });
            $('input[name="quantityRequired"]').focus();
            return false;
        }

        // ========== SECTION 3: PRODUCT PREFERENCES VALIDATION ==========
        // (No specific validation needed - all are optional)

        // ========== SECTION 4: CONSENT VALIDATION ==========
        var consent = $('input[name="consentGiven"]').is(':checked');
        if (!consent) {
            $.mxalert({ msg: "You must give consent to proceed with your inquiry" });
            $('input[name="consentGiven"]').focus();
            return false;
        }

        // ========== FILE UPLOAD VALIDATION ==========
        var fileInput = $('input[name="uploadedFile"]')[0];
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            var file = fileInput.files[0];
            var allowedExtensions = /(\.jpg|\.jpeg|\.png|\.pdf)$/i;

            if (!allowedExtensions.exec(file.name)) {
                $.mxalert({ msg: "File must be JPG, PNG, or PDF format" });
                $(fileInput).focus();
                return false;
            }

            // Check file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                $.mxalert({ msg: "File size must not exceed 5MB" });
                $(fileInput).focus();
                return false;
            }
        }

        return true;
    });
});

// Helper function for regex validation
function preg_match(pattern, subject) {
    return pattern.test(subject);
}

// Start: pump inquiry submit form callback
function callbackPumpInquiry(response) {
    console.log("Pump Inquiry Response:", response);
    hideMxLoader();

    if (response.err == 0) {
        // Success
        $("#pumpInquiryForm").trigger('reset');
        $.mxalert({ msg: response.msg, type: 'success' });

        // Reload page after delay
        setTimeout(function () {
            window.location.reload(true);
        }, 3000);
    } else {
        // Error
        $.mxalert({ msg: response.msg, type: 'error' });
    }
}
// End

// ========== UTILITY FUNCTIONS ==========

/**
 * Format Indian mobile number as user types
 * Usage: Apply to mobile number input field
 */
function formatIndianMobile(input) {
    let value = input.value.replace(/\D/g, '');

    if (value.length > 0) {
        if (value.startsWith('91') && value.length > 2) {
            value = value.substring(2);
        }
        value = value.substring(0, 10);

        if (value.length > 0) {
            if (value.length <= 5) {
                input.value = value;
            } else if (value.length <= 10) {
                input.value = value.substring(0, 5) + ' ' + value.substring(5);
            }
        }
    } else {
        input.value = '';
    }
}

/**
 * Show field error message
 */
function showFieldError(fieldName, message) {
    var field = $('input[name="' + fieldName + '"], textarea[name="' + fieldName + '"], select[name="' + fieldName + '"]');

    // Remove existing error
    field.siblings('.form-error').remove();

    // Add error class and message
    field.addClass('field-error');
    field.after('<span class="form-error">' + message + '</span>');
}

/**
 * Clear field error message
 */
function clearFieldError(fieldName) {
    var field = $('input[name="' + fieldName + '"], textarea[name="' + fieldName + '"], select[name="' + fieldName + '"]');
    field.removeClass('field-error');
    field.siblings('.form-error').remove();
}

// ========== REAL-TIME VALIDATION ==========

// Mobile number input - auto-format as user types
$('input[name="userMobile"]').on('input', function() {
    formatIndianMobile(this);
});

// Pin code - allow only numbers
$('input[name="pinCode"]').on('input', function() {
    $(this).val($(this).val().replace(/[^\d]/g, '').substring(0, 6));
});

// Numeric fields - allow only numbers and decimal point
$('input[name="requiredHead"], input[name="pumpingDistance"], input[name="heightDifference"], input[name="operatingHours"], input[name="quantityRequired"]').on('input', function() {
    var value = $(this).val();
    value = value.replace(/[^\d.]/g, '');

    // Prevent multiple decimal points
    if ((value.match(/\./g) || []).length > 1) {
        value = value.replace(/\.+$/, '');
    }

    $(this).val(value);
});

// Clear errors when user starts typing
$('input, textarea, select').on('focus', function() {
    clearFieldError($(this).attr('name'));
});
