$(document).ready(function () {
  $(".contact-us").click(function () {
    // assign value to hidden input categoryTitle and productTitle.
    var categoryTitle = $("#mCategoryTitle,#pCategoryTitle,#aCategoryTitle").val();
    $("#categoryTitle").val(categoryTitle);

    var productTitle = $("#motorTitle,#pumpTitle,#automationTitle").val();
    $("#productTitle").val(productTitle);
    var modType = $("#modTypeID").val();
    $("#modType").val(modType);
    // End 

    $(".product-contact-Frm").show();
    });
    $(".del").click(function(){
      $(".product-contact-Frm").hide();
    });
  
  //Start: To save contact us form.
  var frm = $("form#frmPopupEnquiry");
  frm.mxinitform({
    callback: callbackcontactUsForm,
    url: SITEURL+"/inc/site.inc.php",
 });
  localStorage.removeItem(SITEURL);
    // End.
  
  //Start: Lead Logout Functionality.
  $('.user-Logout').click(function () {
    var page = '/lead/';
    if (window.location.href.includes('/leave')){ //check current url
      var page = '/leave/';
    }
    showMxLoader();
    $.mxajax({
      url: SITEURL + "/inc/site.inc.php",
      data: { xAction: "logout" },
      type: 'post',
      dataType: "json"
    }).then(function (resp) {
      if (resp.err == 0) {
        hideMxLoader();
        window.location = SITEURL + page; 
      } else {
        $.mxalert({ msg: resp.msg });
      }
    });
  });
  // End.
});

$('.dropdown a').click( function(){
  if ( $(this).parent().hasClass('active') ) {
      $(this).parent().removeClass('active');
  } else {
      $('.dropdown').addClass('current');    
  }
});
// Start: Save contact us form callback.
function callbackcontactUsForm(response) {
  console.log(response);
  if (response.err == 0) {
    hideMxLoader();
    $(".product-contact-Frm").hide();
    $.mxalert({ msg: response.msg });
    $("#frmPopupEnquiry").trigger('reset');
  } else {
    $.mxalert({ msg: response.msg });
  }
}
// End

function isOneDigitNumber(event) {
  var max = 1;
  var currentVal = $(event.target).val();
  currentVal = currentVal.replace(/\D/g, "");
  $(event.target).val(currentVal);
  if ($(event.target).val().length > max) {
    $(event.target).val($(event.target).val().substr(0, max));
  }
}
