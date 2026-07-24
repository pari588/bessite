$(document).ready(function () {
    var frm = $('form#frmAddEdit')
    frm.mxinitform({
        pcallback: validateUserPin,
    });

    $("a.resetLeaveCount").click(function () {
        var userID = $(this).data("id");
        showMxLoader();
        $.mxajax({
            url: MODINCURL,
            type: "POST",
            data: {
                userID: userID,
                xAction: "resetUnauthorizedLeaves"
            },
            dataType: "json",
        }).then(function (resp) {
            hideMxLoader();
            $.mxalert({ msg: resp.msg });
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        });
        return false;
    });
});

function validateUserPin(frm, el, p) {
    if (frm.mxvalidate() !== false) {
        var userPin = $('#userPin').val();
        var userID = $('#userID').val();
        if(userPin){
            console.log(MODINCURL);
            $.mxajax({
                url: MODINCURL,
                data: { userPin: userPin, userID: userID, xAction: "validateUserPin" },
                type: 'post',
                dataType: "json"
            }).then(function (resp) {
                console.log(resp);
                if (resp.err == 0) {
                    mxSubmitForm(frm, el, p);
                } else {
                    $.mxalert({ msg: resp.msg });
                }
            });
        }else{
            mxSubmitForm(frm, el, p);
        }
    }
}

// ============ IFSC LOOKUP USING RAZORPAY API ============
function lookupIFSC() {
    var ifsc = $('#bankIFSC').val().trim().toUpperCase();
    var statusDiv = $('#ifscStatus');
    var btn = $('#ifscLookupBtn');

    // Basic validation
    if (!ifsc) {
        statusDiv.html('<span style="color:#dc2626;">Please enter an IFSC code</span>');
        return;
    }

    // IFSC format validation: 4 letters + 0 + 6 alphanumeric
    var ifscPattern = /^[A-Z]{4}0[A-Z0-9]{6}$/;
    if (!ifscPattern.test(ifsc)) {
        statusDiv.html('<span style="color:#dc2626;">Invalid IFSC format. Expected: 4 letters + 0 + 6 alphanumeric (e.g., ICIC0001234)</span>');
        return;
    }

    // Show loading
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Looking up...');
    statusDiv.html('<span style="color:#3b82f6;">Fetching bank details...</span>');

    // Call Razorpay IFSC API
    $.ajax({
        url: 'https://ifsc.razorpay.com/' + ifsc,
        type: 'GET',
        dataType: 'json',
        timeout: 10000,
        success: function(data) {
            btn.prop('disabled', false).html('<i class="fa fa-search"></i> Lookup');

            if (data && data.BANK) {
                // Populate bank details
                $('#bankName').val(data.BANK);
                $('#bankBranch').val(data.BRANCH || '');

                // Show success message with details
                var html = '<span style="color:#16a34a;"><i class="fa fa-check-circle"></i> <strong>' + data.BANK + '</strong></span>';
                html += '<br><span style="color:#64748b; font-size:11px;">';
                html += data.BRANCH + ', ' + data.CITY + ', ' + data.STATE;
                if (data.ADDRESS) {
                    html += '<br>' + data.ADDRESS;
                }
                html += '</span>';

                // Payment modes supported
                var modes = [];
                if (data.NEFT) modes.push('NEFT');
                if (data.RTGS) modes.push('RTGS');
                if (data.IMPS) modes.push('IMPS');
                if (data.UPI) modes.push('UPI');
                if (modes.length > 0) {
                    html += '<br><span style="color:#0369a1; font-size:11px;">Supports: ' + modes.join(', ') + '</span>';
                }

                statusDiv.html(html);
            } else {
                statusDiv.html('<span style="color:#dc2626;"><i class="fa fa-times-circle"></i> Invalid response from API</span>');
            }
        },
        error: function(xhr, status, error) {
            btn.prop('disabled', false).html('<i class="fa fa-search"></i> Lookup');

            if (xhr.status === 404) {
                statusDiv.html('<span style="color:#dc2626;"><i class="fa fa-times-circle"></i> IFSC code not found. Please verify the code.</span>');
            } else if (status === 'timeout') {
                statusDiv.html('<span style="color:#dc2626;"><i class="fa fa-times-circle"></i> Request timed out. Please try again.</span>');
            } else {
                statusDiv.html('<span style="color:#dc2626;"><i class="fa fa-times-circle"></i> Error looking up IFSC: ' + error + '</span>');
            }
        }
    });
}

// Auto-lookup on paste or after typing 11 characters
$(document).on('input', '#bankIFSC', function() {
    var ifsc = $(this).val().trim().toUpperCase();
    if (ifsc.length === 11) {
        // Auto-trigger lookup after a short delay
        clearTimeout(window.ifscLookupTimer);
        window.ifscLookupTimer = setTimeout(function() {
            lookupIFSC();
        }, 500);
    } else {
        $('#ifscStatus').html('');
    }
});