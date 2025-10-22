$(document).ready(function () {
    //Start: To save contact us form.
    var frm = $("form#contactUsForm");
    frm.mxinitform({ callback: callbackcontactUs });
    localStorage.removeItem(SITEURL);
    // End.
});
// Start: Save contact us form callback.
function callbackcontactUs(response) {
    console.log(response);
    hideMxLoader();
    if (response.err == 0) {
        $("form#contactUsForm")[0].reset();
        $.mxalert({ msg: response.msg });
    } else {
        $.mxalert({ msg: response.msg });
    }
}
// End