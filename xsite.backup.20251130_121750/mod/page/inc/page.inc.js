$(document).ready(function () {
    console.log('Contact form script loaded');

    //Start: To save contact us form.
    var frm = $("form#contactUsForm");

    console.log('Form found:', frm.length > 0);
    console.log('Form ID:', frm.attr('id'));

    // Initialize form with reCAPTCHA handler
    frm.mxinitform({
        callback: callbackcontactUs,
        pcallback: handleContactUsFormSubmit
    });

    // Direct click handler as backup
    $('a.fa-save[rel="contactUsForm"]').click(function(e) {
        console.log('Button clicked directly');
        e.preventDefault();
        return false;
    });

    console.log('Form initialized with reCAPTCHA handler');
    // End.
});

// Handle form submission with reCAPTCHA v3
function handleContactUsFormSubmit(frm, fileEl, p) {
    console.log('handleContactUsFormSubmit called');

    // Show loading indicator
    showMxLoader();

    // Remove existing token field if present
    frm.find('input[name="g-recaptcha-response"]').remove();

    // Check if grecaptcha is available
    if (typeof grecaptcha === 'undefined') {
        console.log('grecaptcha undefined, adding dummy token');
        // Add dummy token and submit
        $('<input>').attr({
            type: 'hidden',
            name: 'g-recaptcha-response',
            value: 'dummy_token_for_testing'
        }).appendTo(frm);
        mxSubmitForm(frm, fileEl, p);
        return false;
    }

    // Execute reCAPTCHA v3 to get token
    try {
        grecaptcha.execute('6LeVCf0rAAAAAG3JjibEriASu2AwVx8v-6pxZHlZ', {action: 'submit'}).then(function(token) {
            console.log('Token received');

            // Add token to form
            $('<input>').attr({
                type: 'hidden',
                name: 'g-recaptcha-response',
                value: token
            }).appendTo(frm);

            console.log('Submitting form');
            // Submit form with token
            mxSubmitForm(frm, fileEl, p);
        }).catch(function(err) {
            console.error('grecaptcha error:', err);
            // Submit without token on error
            mxSubmitForm(frm, fileEl, p);
        });
    } catch(e) {
        console.error('Exception:', e);
        mxSubmitForm(frm, fileEl, p);
    }

    return false;
}

// Start: Save contact us form callback.
function callbackcontactUs(response) {
    console.log('Contact form response:', response);
    hideMxLoader();
    if (response.err == 0) {
        // Form submitted successfully
        $("form#contactUsForm")[0].reset();
        $.mxalert({ msg: response.msg });
    } else {
        // Form submission failed
        $.mxalert({ msg: response.msg });
    }
}
// End