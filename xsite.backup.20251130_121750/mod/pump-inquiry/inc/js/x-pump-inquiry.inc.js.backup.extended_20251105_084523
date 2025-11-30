$(document).ready(function () {
    //Start: To submit pump inquiry form
    var frm = $("form#productInquiryForm");
    frm.mxinitform({
        callback: callbackPumpInquiry,
        url: SITEURL + "/xsite/mod/pump-inquiry/x-pump-inquiry-inc.php"
    });
    localStorage.removeItem(SITEURL);

    // Add basic form validation before submission
    frm.on('submit', function () {
        // Required fields validation
        var requiredFields = [
            { name: 'userName', label: 'Name (Company or Personal)' },
            { name: 'userEmail', label: 'Email' },
            { name: 'userMobile', label: 'Mobile Number' },
            { name: 'enquiryText', label: 'Enquiry Details' }
        ];

        for (var i = 0; i < requiredFields.length; i++) {
            var field = $('input[name="' + requiredFields[i].name + '"], textarea[name="' + requiredFields[i].name + '"]');
            if (field.length && !field.val().trim()) {
                $.mxalert({ msg: "Please enter " + requiredFields[i].label });
                field.focus();
                return false;
            }
        }

        // Name validation (allowing both company and personal names)
        var name = $('input[name="userName"]').val().trim();
        // Allow letters, spaces, dots, commas, hyphens, and apostrophes for names
        // var nameRegex = /^[a-zA-Z\s.,'-&()]{3,100}$/;
        var nameRegex = /^[a-zA-Z\s.,'&()\-]{3,100}$/;
        if (name && !nameRegex.test(name)) {
            $.mxalert({ msg: "Please enter a valid name with at least 3 characters (allowed: letters, numbers, spaces, and basic punctuation)" });
            $('input[name="userName"]').focus();
            return false;
        }

        // Email format validation - stronger pattern
        var email = $('input[name="userEmail"]').val().trim();
        var emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        if (email && !emailRegex.test(email)) {
            $.mxalert({ msg: "Please enter a valid email address" });
            $('input[name="userEmail"]').focus();
            return false;
        }

        // Indian mobile number validation
        var mobile = $('input[name="userMobile"]').val().replace(/[\s\-\(\)]/g, '');

        // Check if number has +91 or 0091 prefix and remove it
        if (mobile.startsWith("+91")) {
            mobile = mobile.substring(3);
        } else if (mobile.startsWith("0091")) {
            mobile = mobile.substring(4);
        } else if (mobile.startsWith("91") && mobile.length > 10) {
            mobile = mobile.substring(2);
        }

        // Now validate the 10-digit number (Indian mobile starts with 6,7,8,9)
        var indianMobileRegex = /^[6-9]\d{9}$/;
        if (!indianMobileRegex.test(mobile)) {
            $.mxalert({ msg: "Please enter a valid Indian mobile number (10 digits starting with 6, 7, 8, or 9)" });
            $('input[name="userMobile"]').focus();
            return false;
        }

        return true;
    });
});

// Start: pump inquiry submit form callback
function callbackPumpInquiry(response) {
    console.log(response);
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