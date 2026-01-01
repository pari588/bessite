$(document).ready(function () {
    $("form#frmLogin").mxinitform({
        callback: callbackDoctorLogin
    });

    // Start: MarkIn Functionality
    $('#mark-in').click(function () {
        showMxLoader();
        $.mxajax({
            url: SITEURL + "/mod/driver/x-driver.inc.php",
            data: { xAction: "markIn" },
            type: 'post',
            dataType: "json"
        }).then(function (resp) {  
            hideMxLoader();
            $.mxalert({ msg: resp.msg });
            setTimeout(function () { window.location.href = SITEURL + "/driver/home/"; }, 1000);
            return false;
        });
    });
    // End.

    // Start: MarkOut Functionality
    $('#mark-out').click(function () {
        var $btn = $(this);

        // Check if button is disabled
        if ($btn.hasClass('disabled')) {
            return false;
        }

        showMxLoader();
        var driverManagementID = $btn.attr("rel");
        $.mxajax({
            url: SITEURL + "/mod/driver/x-driver.inc.php",
            data: { xAction: "markOut", "driverManagementID": driverManagementID },
            type: 'post',
            dataType: "json"
        }).then(function (resp) {
            hideMxLoader();
            $.mxalert({ msg: resp.msg });

            if (resp.err == 0) {
                // Disable button for 60 seconds
                $btn.addClass('disabled');
                $btn.find('h4').text('Please wait...');
                $btn.find('.hindi-text').text('60 seconds');

                var countdown = 60;
                var countdownInterval = setInterval(function() {
                    countdown--;
                    $btn.find('.hindi-text').text(countdown + ' seconds');

                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        window.location.href = SITEURL + "/driver/home/";
                    }
                }, 1000);
            } else {
                setTimeout(function () { window.location.href = SITEURL + "/driver/home/"; }, 1000);
            }
        });
    });
    // End.

    // Start: Logout Functionality
    $('#driver-logout').click(function () {
        if (confirm('Are you sure you want to logout?')) {
            showMxLoader();
            $.mxajax({
                url: SITEURL + "/mod/driver/x-driver.inc.php",
                data: { xAction: "driverLogout" },
                type: 'post',
                dataType: "json"
            }).then(function (resp) {
                hideMxLoader();
                $.mxalert({ msg: resp.msg });
                setTimeout(function () { window.location.href = SITEURL + "/driver/login/"; }, 1000);
            });
        }
    });
    // End.
});

function callbackDoctorLogin(response) {
    hideMxLoader();
    if (response.err == 0) {
        $.mxalert({ msg: response.msg });
        setTimeout(function () { window.location.href = SITEURL + "/driver/home/"; }, 1000);
        return false;
    } else {
        $.mxalert({ msg: response.msg });
        return false;
    }
}