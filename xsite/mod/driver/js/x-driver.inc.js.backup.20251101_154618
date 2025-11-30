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
        showMxLoader();
        var driverManagementID = $(this).attr("rel");
        $.mxajax({
            url: SITEURL + "/mod/driver/x-driver.inc.php",
            data: { xAction: "markOut", "driverManagementID": driverManagementID },
            type: 'post',
            dataType: "json"
        }).then(function (resp) {  
            hideMxLoader();
            $.mxalert({ msg: resp.msg });
            setTimeout(function () { window.location.href = SITEURL + "/driver/home/"; }, 1000);
        });
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