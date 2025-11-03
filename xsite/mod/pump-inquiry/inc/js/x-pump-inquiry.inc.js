$(document).ready(function () {
    console.log('Pump Inquiry form script loaded');

    //Start: To submit pump inquiry form
    var frm = $("form#pumpInquiryForm");

    frm.mxinitform({
        callback: callbackPumpInquiry,
        pcallback: handlePumpInquiryFormSubmit
    });

    localStorage.removeItem(SITEURL);

    // Form submission validation - client side checks
    frm.on('submit', function() {
        console.log('Form validation started');

        // Check Full Name
        var fullName = $('input[name="fullName"]').val().trim();
        if (!fullName || fullName.length < 2) {
            $.mxalert({ msg: "Please enter Full Name (minimum 2 characters)" });
            $('input[name="fullName"]').focus();
            return false;
        }

        // Check Email
        var email = $('input[name="userEmail"]').val().trim();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email || !emailRegex.test(email)) {
            $.mxalert({ msg: "Please enter a valid email address" });
            $('input[name="userEmail"]').focus();
            return false;
        }

        // Check Mobile
        var mobile = $('input[name="userMobile"]').val().replace(/[\s\-\(\)\+]/g, '');
        if (!mobile) {
            $.mxalert({ msg: "Please enter Mobile Number" });
            $('input[name="userMobile"]').focus();
            return false;
        }
        if (mobile.length > 2 && mobile.startsWith('91')) {
            mobile = mobile.substring(2);
        }
        if (!/^[6-9]\d{9}$/.test(mobile)) {
            $.mxalert({ msg: "Please enter a valid Indian mobile number (10 digits starting with 6, 7, 8, or 9)" });
            $('input[name="userMobile"]').focus();
            return false;
        }

        // Check City
        var city = $('select[name="city"]').val();
        console.log('City value: ' + city);
        if (!city || city === '') {
            $.mxalert({ msg: "Please select City" });
            $('select[name="city"]').focus();
            return false;
        }

        // Check Application Type
        var appType = $('select[name="applicationTypeID"]').val();
        if (!appType || appType === '') {
            $.mxalert({ msg: "Please select Application Type" });
            $('select[name="applicationTypeID"]').focus();
            return false;
        }

        // Check Installation Type
        var instType = $('select[name="installationTypeID"]').val();
        if (!instType || instType === '') {
            $.mxalert({ msg: "Please select Installation Type" });
            $('select[name="installationTypeID"]').focus();
            return false;
        }

        // Check Operating Medium
        var opMedium = $('select[name="operatingMediumID"]').val();
        if (!opMedium || opMedium === '') {
            $.mxalert({ msg: "Please select Operating Medium" });
            $('select[name="operatingMediumID"]').focus();
            return false;
        }

        // Check Water Source
        var waterSource = $('select[name="waterSourceID"]').val();
        if (!waterSource || waterSource === '') {
            $.mxalert({ msg: "Please select Water Source" });
            $('select[name="waterSourceID"]').focus();
            return false;
        }

        // Check Power Supply
        var powerSupply = $('select[name="powerSupplyID"]').val();
        if (!powerSupply || powerSupply === '') {
            $.mxalert({ msg: "Please select Power Supply" });
            $('select[name="powerSupplyID"]').focus();
            return false;
        }

        // Pin Code validation if provided
        var pinCode = $('input[name="pinCode"]').val();
        if (pinCode && (!/^\d{6}$/.test(pinCode))) {
            $.mxalert({ msg: "Please enter a valid 6-digit pin code" });
            $('input[name="pinCode"]').focus();
            return false;
        }

        // File upload validation
        var fileInput = document.getElementById('uploadedFile');
        if (fileInput && fileInput.files.length > 0) {
            var file = fileInput.files[0];
            var maxSize = 5 * 1024 * 1024; // 5MB
            var allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];

            if (file.size > maxSize) {
                $.mxalert({ msg: "File size exceeds 5MB limit" });
                return false;
            }

            if (!allowedTypes.includes(file.type)) {
                $.mxalert({ msg: "Only PDF, JPG, and PNG files are allowed" });
                return false;
            }
        }

        // Check Consent
        if (!$('input[name="consentGiven"]').is(':checked')) {
            $.mxalert({ msg: "Please agree to the consent to continue" });
            return false;
        }

        console.log('All validations passed');
        return true;
    });

    // End: Form validation
});

// Handle form submission with reCAPTCHA v3
function handlePumpInquiryFormSubmit(frm, fileEl, p) {
    console.log('handlePumpInquiryFormSubmit called');

    // Show loading indicator
    showMxLoader();

    // Remove existing token field if present
    var existingToken = frm.find('input[name="g-recaptcha-response"]');
    if (existingToken.length) {
        existingToken.remove();
    }

    // Check if grecaptcha is available
    if (typeof grecaptcha === 'undefined') {
        console.log('grecaptcha undefined, proceeding without token');
        mxSubmitForm(frm, fileEl, p);
        return false;
    }

    console.log('grecaptcha is available, executing...');

    // Execute reCAPTCHA v3 to get token
    try {
        grecaptcha.execute('6LeVCf0rAAAAAG3JjibEriASu2AwVx8v-6pxZHlZ', {action: 'submit'}).then(function(token) {
            console.log('reCAPTCHA token received');

            // Update or create token field
            var tokenField = frm.find('input[name="g-recaptcha-response"]');
            if (tokenField.length) {
                tokenField.val(token);
            } else {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'g-recaptcha-response',
                    value: token
                }).appendTo(frm);
            }

            console.log('Submitting form with token');
            mxSubmitForm(frm, fileEl, p);
        }).catch(function(err) {
            console.error('grecaptcha error:', err);
            console.log('Proceeding without token due to error');
            mxSubmitForm(frm, fileEl, p);
        });
    } catch(e) {
        console.error('Exception in grecaptcha execution:', e);
        console.log('Proceeding without token due to exception');
        mxSubmitForm(frm, fileEl, p);
    }

    return false;
}

// Start: pump inquiry submit form callback
function callbackPumpInquiry(response) {
    console.log('Pump inquiry response:', response);
    hideMxLoader();

    if (response.err == 0) {
        $("#pumpInquiryForm").trigger('reset');
        $.mxalert({ msg: response.msg });
        setTimeout(function () {
            window.location.reload(1);
        }, 3000);
    } else {
        $.mxalert({ msg: response.msg });
    }
}
// End
