var PRESPHOTOFLG = 0
var GEOLOC_PERMISSION = ''
$(document).ready(function () {
  // To get geolocation permissions status
  navigator.permissions
    .query({ name: 'geolocation' })
    .then((permissionStatus) => {
      GEOLOC_PERMISSION = permissionStatus.state
      locationPermission(GEOLOC_PERMISSION)
      permissionStatus.onchange = () => {
        GEOLOC_PERMISSION = permissionStatus.state
        locationPermission(GEOLOC_PERMISSION)
      }
    })
  // end

  //To get geolocation address
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(showLocation, showError)
  } else {
    $('#geolocation').html('Geolocation is not supported by this browser.')
  }
  //End.

  //Start: To submit verify user pin form.
  var frm = $('form#verifyPinFrm')
  frm.mxinitform({
    pcallback: isEmptyLeadPIN,
    callback: callbackVerifyUserPin,
  })
  localStorage.removeItem(SITEURL)
  // End.

  //Start: To lead user form.
  var frm = $('form#leadUserFrm')
  frm.mxinitform({
    pcallback: uploadLocationimage,
    callback: callbackleadUserFrm,
  })
  localStorage.removeItem(SITEURL)
  // End.

  // Prescription form functionality.
  $('.take-img').click(async function () {
    $('.take-snap').show()
    await configure()
  })

  $('.take-snap').click(function () {
    take_snapshot()
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

  // Start: To Show the front End Camera 
  $(".button.Front").click(async function () {
    var configurationStr = $(this).attr("rel");
    if (configurationStr == 'Front') {
      $('.take-snap').show()
      await configure()
    }
    else {
      $('.take-snap').show()
      await configureBack()
    }
  })

  // $(".button.Back").click(async function()
  // {
  //   $('.take-snap').show()
  //   await configureBack()
  // })

})
// Start: To submit verify user pin form callback
function callbackVerifyUserPin(response) {
  hideMxLoader()
  if (response.err == 0) {
    $('#verifyPinFrm').trigger('reset')
    setTimeout(function () {
      window.location.reload(1)
    }, 1000)
  } else {
    $('#leadPinErr').html(response.msg)
  }
}
// End
// Start: To submit lead user form callback
function callbackleadUserFrm(response) {
  hideMxLoader()
  if (response.err == 0) {
    $('#leadUserFrm').trigger('reset')
    $.mxalert({ msg: response.msg })
    setTimeout(function () {
      window.location.reload(1)
    }, 1000)
  } else {
    $.mxalert({ msg: response.msg })
  }
}
// End
// Start:  To find Geolocation.
function showLocation(position) {
  var latitude = position.coords.latitude
  var longitude = position.coords.longitude
  var latLong = latitude + latitude
  var aUrl = SITEURL + '/mod/lead/x-lead.inc.php'
  $.mxajax({
    type: 'POST',
    url: aUrl,
    data: { xAction: 'getLocation', latitude: latitude, longitude: longitude },
    dataType: 'json',
  }).then(function (data) {
    hideMxLoader()
    if (data.streetAddress != '') {
      $('#geolocation').val(data.streetAddress)
    } else {
      $('#geolocation').val(latLong)
    }
  })
}
// End.
// Start: To  show location regarding errors.
function showError(error) {
  switch (error.code) {
    case error.POSITION_UNAVAILABLE:
      $.mxalert({ msg: 'Location information is unavailable.' })
      break
    case error.TIMEOUT:
      $.mxalert({ msg: 'The request to get user location timed out.' })
      break
    case error.UNKNOWN_ERROR:
      $.mxalert({ msg: 'An unknown error occurred.' })
      break
  }
}
// End.
// To get geolocation permissions status.
function locationPermission(GEOLOC_PERMISSION) {
  if (GEOLOC_PERMISSION == 'denied') {
    $('.lead').hide()
    $('.location-access').show()
  } else {
    $('.lead').show()
    $('.location-access').hide()
  }
}
//End.
// Prescription functionality
function configure() {
  Webcam.set({
    width: 350,
    height: 280,
    image_format: 'jpeg',
    jpeg_quality: 110,
    constraints: {
      facingMode: 'user',
    },
  })
  Webcam.attach('.open-camera')
}
function configureBack() {
  Webcam.set({
    width: 350,
    height: 280,
    image_format: 'jpeg',
    jpeg_quality: 110,
    constraints: {
      facingMode: 'environment',
    },
  })
  Webcam.attach('.open-camera')
}
//Start: To take snapshot of camera uploade image.
function take_snapshot() {
  Webcam.snap(function (data_uri) {
    Webcam.reset()
    $('div.open-camera').html('<img id="imageprev" width="200px" height="200px" src="' + data_uri + '"/>')
    $('.take-snap').hide()
    PRESPHOTOFLG = 1
  })
}
// End.
//Start: To moved camera upload files in folder.
function uploadLocationimage(frm, el, p) {
  var cameraUpload = $('#cameraUpload').val()
  if (geolocation != '') {
    var pUrl = SITEURL + '/mod/lead/x-lead.inc.php?xAction=uploadLocationImg'
    if (PRESPHOTOFLG == 1) {
      var base64image = document.getElementById('imageprev').src
      Webcam.upload(base64image, pUrl, function (code, response) {
        if (typeof response == 'string') {
          response = $.parseJSON(response)
        }
        if (response.err == 0) {
          $('input#cameraUpload').val(response.filename)
          mxSubmitForm(frm, el, p)
          return false
        }
      })
    } else {
      if (cameraUpload != '') {
        mxSubmitForm(frm, el, p)
        return false
      } else {
        $.mxalert({ msg: 'Please Take a Photo Of Location' })
        return false
      }
    }
  }
}
// End.
//Start: Precallback to check lead login pin is empty or not.
function isEmptyLeadPIN(frm, el, p) {
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
    $('#leadPinErr').text('Enter 4 Digit PIN Code!')
  }
}
// End.
