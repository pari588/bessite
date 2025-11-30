$(document).ready(function () {
    //Start: To submit product inquiry form
    var frm = $("form#productInquiryForm");
    frm.mxinitform({ callback: callbackProductInquiry });
    localStorage.removeItem(SITEURL);

    // Add reCAPTCHA v3 token execution on form submission
    frm.on('submit', function (e) {
        grecaptcha.ready(function() {
            grecaptcha.execute('6LeVCf0rAAAAAG3JjibEriASu2AwVx8v-6pxZHlZ', {action: 'product_inquiry'}).then(function(token) {
                document.getElementById('g-recaptcha-response').value = token;
            });
        });
    });

    // Add numeric input validation for KW, HP, RPM fields
    $('input[name="kw"], input[name="hp"], input[name="rpm"], input[name="kwD"], input[name="hpD"], input[name="rpmD"]').on('keypress', function(e) {
        // Allow: backspace, delete, tab, escape, enter, decimal point
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode == 65 && e.ctrlKey === true) ||
            (e.keyCode == 67 && e.ctrlKey === true) ||
            (e.keyCode == 86 && e.ctrlKey === true) ||
            (e.keyCode == 88 && e.ctrlKey === true) ||
            // Allow: home, end, left, right
            (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
        }
        // Stop the keypress if it's not a number
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
    
    // Add basic form validation before submission
    frm.on('submit', function() {
        // Required fields validation
        var requiredFields = [
            { name: 'companyName', label: 'Company Name' },
            { name: 'userName', label: 'Name' },
            { name: 'userEmail', label: 'Email' },
            { name: 'userMobile', label: 'Mobile Number' }
        ];
        
        for (var i = 0; i < requiredFields.length; i++) {
            var field = $('input[name="' + requiredFields[i].name + '"]');
            if (field.length && !field.val().trim()) {
                $.mxalert({ msg: "Please enter " + requiredFields[i].label });
                field.focus();
                return false;
            }
        }
        
        // Email format validation
        var email = $('input[name="userEmail"]').val();
        var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (email && !emailRegex.test(email)) {
            $.mxalert({ msg: "Please enter a valid email address" });
            $('input[name="userEmail"]').focus();
            return false;
        }
        
        // Mobile number validation (at least 10 digits)
        var mobile = $('input[name="userMobile"]').val().replace(/[\s\-\(\)]/g, '');
        if (mobile && (!/^\d+$/.test(mobile) || mobile.length < 10 || mobile.length > 15)) {
            $.mxalert({ msg: "Please enter a valid mobile number (10-15 digits)" });
            $('input[name="userMobile"]').focus();
            return false;
        }
        
        // Validate required "Other" fields
        var otherFields = [
            { select: 'dutyID', other: 'dutyOther', label: 'Duty' },
            { select: 'mountingID', other: 'mountingOther', label: 'Mounting' },
            { select: 'typeOfMotorID', other: 'typeOfMotorOther', label: 'Type of Motor' },
            { select: 'voltageID', other: 'voltageOther', label: 'Voltage' },
            { select: 'shaftExtensionID', other: 'shaftExtensionOther', label: 'Shaft Extension' },
            { select: 'expectedDeliveryTimeID', other: 'expectedDeliveryTimeOther', label: 'Expected Delivery Time' }
        ];
        
        for (var i = 0; i < otherFields.length; i++) {
            var select = $('#' + otherFields[i].select);
            var otherInput = $('input[name="' + otherFields[i].other + '"]');
            
            if (select.find('option:selected').text() === 'Other' && otherInput.length && !otherInput.val().trim()) {
                $.mxalert({ msg: "Please specify " + otherFields[i].label + " details" });
                otherInput.focus();
                return false;
            }
        }
        
        // File size validation (5MB limit)
        var fileInputs = ['uploadFile', 'uploadFileD'];
        for (var i = 0; i < fileInputs.length; i++) {
            var fileInput = document.getElementById(fileInputs[i]);
            if (fileInput && fileInput.files.length > 0) {
                if (fileInput.files[0].size > 5 * 1024 * 1024) {
                    $.mxalert({ msg: "File size exceeds 5MB limit. Please select a smaller file." });
                    return false;
                }
            }
        }
        
        return true;
    });
    
    // Start: To show and hide on requirement replacement value.
    $(".requirement-replacement input[type='radio']").click(function () {
        if ($(this).val() == 1) {
            $('.motor-details').show();
        } else {
            $('.motor-details').hide();
        }
    });
    // End.
    //Start: To show and hide hidden other input.
    $('#dutyID,#mountingID,#typeOfMotorID,#voltageID,#shaftExtensionID,#expectedDeliveryTimeID').change(function () {
        var parentLi = $(this).closest("li.other");
        var classNm = parentLi.attr("otherName");
        var text = $(this).find("option:selected").text();
        
        if (text == "Other") {
            $('.' + classNm ).show();
        } else {
            $('.' + classNm ).hide();
        }
    });
    // End.
});
// Start: product inquiry submit form callback
function callbackProductInquiry(response) {
    console.log(response);
    hideMxLoader();
    if (response.err == 0) {
        $("#productInquiryForm").trigger('reset');
        // Hide any visible "Other" fields after reset
        $('.duty-other, .mounting-other, .typeOfMotor-other, .voltage-other, .shaft-extension-other, .expect-delivery-time, .motor-details').hide();
        
        $.mxalert({ msg: response.msg });
        setTimeout(function () {
            window.location.reload(1);
        }, 3000);
    } else {
        $.mxalert({ msg: response.msg });
    }
}
// End