$(document).ready(function () {
  var fromDate = ''
  var toDate = ''
  $('#fromDate,#toDate').change(function () {
    var defaultsD = {
      // params for calender
      dateFormat: 'yy-mm-dd',
      numberOfMonths: 2,
      changeMonth: true,
      changeYear: true,
    }

    if ($(this)[0].name == 'fromDate') {
      // check if fromDate changed
      fromDate = $(this).val()
      toDate = $('#toDate').val()
      if (toDate != '' && toDate < fromDate) {
        // check if fromDate is less than toDate
        $('#toDate').val('')
      }
      defaultsD.minDate = new Date(fromDate)
      $('#toDate').datepicker('destroy')
      $('#toDate').datepicker(defaultsD)
    } else {
      // check if toDate changed
      toDate = $(this).val()
      fromDate = $('#fromDate').val()
      if (toDate != '' && toDate < fromDate) {
        // check if fromDate is less than toDate
        $('#fromDate').val('')
      }
      defaultsD.maxDate = new Date(toDate)
      $('#fromDate').datepicker('destroy')
      $('#fromDate').datepicker(defaultsD)
    }
    getleaveLists(fromDate, toDate) // get leave lists for selected date range
  })

  $('.statusPopup').click(function () {
    var leaveStatus = $(this).find('span').data('leave-status')
    var leaveNote = $(this).find('span').data('leave-note')
    if (leaveNote == '' || leaveNote == undefined) {
      leaveNote = '---'
    }
    $('.leaveDetailPopup .tblData .lData').html(
      '<td align="center" width="30%">' +
        leaveStatus +
        '</td><td align="center" width="70%">' +
        leaveNote +
        '</td>'
    )
    $('.leaveDetailPopup').mxpopup()
  })

  //Start: To submit verify user pin form.
  $('form#verifyPinFrm').mxinitform({
    pcallback: isEmptyLeavePIN,
    callback: callbackVerifyUserPin,
  })
  // End.

  //Start: To leave user form.

  $('form#leaveUserFrm').mxinitform({
    pcallback: validateLeave,
    callback: callbackleaveUserFrm,
  })
  // End.

  // Pin validation and save functionality
  $('.pin').keyup(function () {
    if (this.value.length == this.maxLength) {
      $(this).closest('li').next('li').find('.pin').focus()
    } else if (this.value.length == 0) {
      $(this).closest('li').prev('li').find('.pin').focus()
    }
    var count = 0
    $('.pin').each(function () {
      var element = $(this)
      if (element.val() != '') {
        count++
      }
    })
    if (count == 4) {
      $('.e').html('')
    }
  })
  // End
})

function getleaveLists(fromDate = '', toDate = '') {
  if (fromDate != '' && toDate != '' && fromDate <= toDate) {
    $.mxajax({
      url: SITEURL + '/mod/leave/x-leave.inc.php',
      data: {
        xAction: 'getHolidays',
        fromDate: fromDate,
        toDate: toDate,
      },
      type: 'POST',
      dataType: 'json',
    }).then(function (data) {
      if (data.count == 0) {
        $('table.leave-details tr.grp-set:gt(0)').remove()
        for (var d = 0; d < data.data.length; d++) {
          $('table.leave-details a.add-set').trigger('click')
          $('table.leave-details tr.grp-set td')
            .find('#leaveDate_' + d)
            .val(data.data[d].leaveDate)

          $('table.leave-details tr.grp-set td')
            .find('#leaveDateFormat_' + d)
            .val(data.data[d].leaveDateFormat)
          $('table.leave-details tr.grp-set td')
            .find('#lType_' + d)
            .html(
              "<option value='' class='default'>--SELECT TYPE--</option>" +
                data.data[d].selectType
            )
        }
        $('table.leave-details tr.grp-set:eq(' + d + ')').remove()
      }
    })
  }
}

// Start: To submit verify user pin form callback
function callbackVerifyUserPin(response) {
  hideMxLoader()
  if (response.err == 0) {
    $('#verifyPinFrm').trigger('reset')
    setTimeout(function () {
      window.location.href = SITEURL + '/leave/apply/'
    }, 1000)
  } else {
    $('#leavePinErr').html(response.msg)
  }
}
// End

// Start: To submit leave user form callback
function callbackleaveUserFrm(response) {
  hideMxLoader()
  console.log(response)
  if (response.err == 0) {
    $('#leaveUserFrm').trigger('reset')
    $.mxalert({ msg: response.msg })
    setTimeout(function () {
      window.location.href = SITEURL + '/leave/list/'
    }, 1000)
  } else {
    $.mxalert({ msg: response.msg })
  }
}
// End

function validateLeave(frm, el, p) {
  // check leave added before 7 days of leave day and check and allow only 3 leaves applied before 7 days;
  var data = frm.serialize() + '&xAction=validateLeave'
  $.mxajax({
    url: SITEURL + '/mod/leave/x-leave.inc.php',
    data: data,
    type: 'post',
    dataType: 'json',
  }).then(function (resp) {
    if (resp.err == 0) {
      if (resp.errCode == 'leave3') {
        $confirmbox = $.mxconfirm({
          msg: resp.msg + '<br/>Are you sure, you want to proceed ?',
          buttons: {
            Yes: {
              action: function () {
                $confirmbox.hidemxdialog()
                mxSubmitForm(frm, el, p)
              },
              class: 'thm-btn',
            },
            Cancel: {
              action: function () {
                $confirmbox.hidemxdialog()
                return false
              },
              class: 'thm-btn',
            },
          },
        })
      } else {
        mxSubmitForm(frm, el, p)
      }
    } else {
      $.mxalert({ msg: resp.msg })
    }
  })
}

//Start: Precallback to check leave login pin is empty or not.
function isEmptyLeavePIN(frm, el, p) {
  var count = 0
  $('.pin').each(function () {
    var element = $(this)
    if (element.val() != '') {
      count++
    }
  })
  if (count == 4) {
    mxSubmitForm(frm, el, p)
  } else {
    $('#leavePinErr').text('Enter 4 digit PIN Code!')
  }
}
// End.
