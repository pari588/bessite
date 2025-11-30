<script type="text/javascript" src="<?php echo SITEURL . '/inc/webcamjs/webcam.min.js'; ?>"></script>
<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . '/inc/js/x-lead.inc.js?'.time()); ?>"></script>

<?php

$loginForm = array(
    array("type" => "text", "name" => "pin_1", "attr" => ' class="pin" minlength="1" maxlength="1"'),
    array("type" => "text", "name" => "pin_2", "attr" => ' class="pin" minlength="1" maxlength="1"'),
    array("type" => "text", "name" => "pin_3", "attr" => ' class="pin" minlength="1" maxlength="1"'),
    array("type" => "text", "name" => "pin_4", "attr" => ' class="pin" minlength="1" maxlength="1"')
);
$leadForm = array(
    array("type" => "date", "name" => "leadDate", "title" => "Date", "validate" => "required"),
    array("type" => "text", "name" => "partyName", "title" => "Lead Title", "validate" => "required,alpha,minlen:5"),
    array("type" => "textarea", "name" => "partyAddr", "title" => "Party Address", "validate" => "required"),
    array("type" => "text", "name" => "leadLocation", "title" => "Location", "validate" => "required"),
    array("type" => "text", "name" => "contactPerson", "title" => "Contact Person",  "validate" => "required,alpha,minlen:5,maxlen:30"),
    array("type" => "text", "name" => "contactNumber", "title" => "Contact Number", "validate" => "required,number,minlen:9,maxlen:13"),
    array("type" => "text", "name" => "officeNumber", "title" => "Office Number"),
    array("type" => "textarea", "name" => "remark", "title" => "Remark", "attrp" => " class='c1'"),
    array("type" => "file", "name" => "referenceDocument", "title" => "Reference Document", "params" => array("MAXFILES" => 5, "EXT" => "jpg|jpeg|png|pdf|doc|docx"), "attrp" => " class='c1 reference-document'"),
    array("type" => "hidden", "name" => "geolocation",  "title" => "Geolocation", "attr" => " readonly",),
    array("type" => "file", "name" => "visitingCard", "title" => "Visiting Card", "params" => array("MAXFILES" => 1, "EXT" => "jpg|jpeg|png|pdf|doc|docx"), "attrp" => ' class="visiting-card"'),
    array("type" => "hidden", "name" => "cameraUpload", "value" => "")
);
$MXFRM = new mxForm();
?>
<div class="lead">
    <?php if (isset($_SESSION['LEADUSERID'])) {
        $MXFRM->xAction = "addLeadUser";
    ?>
        <!-- New Lead user form start. -->
        <div class="main-form">
            <form name="leadUserFrm" auto="false" class="leadUserFrm" id="leadUserFrm" action="" method="post" onsubmit="return false;" callback="callbackleadUserFrm" enctype="multipart/form-data">
                <ul>
                    <?php echo $MXFRM->getForm($leadForm); ?>
                    <li class="prec-btn"><button class="button take-img">Take a Photo Of Location</button></li>
                    <li class="cam-img">
                   <?php $is_android = is_android();
                    if (isset($is_android) && $is_android > 0) { 
                    ?>  
                         <!-- <li  style="display:none;"><button class="button Front" rel="Front"></button></li>     -->
                       <button class="button Front" rel="Back" style="display:none;"></button>
                    <?php  } ?> 
                        <div class="open-camera">
                      
                        </div>
                    </li>
                    <li class="prec-btn take-snap" style="display:none;"><span></span><button class="button"><?php echo "Take a snap"; ?></button></li>
                </ul>
                <?php echo $MXFRM->closeForm(); ?>
                <input type="hidden" name="pageType" id="pageType" value="add" />
                <a href="javascript:void(0)" class="fa-save thm-btn" rel="leadUserFrm"> Save </a>
            </form>
        </div>
    <?php } else {
        $MXFRM->xAction = "verifyUserPin";
    ?>
        <!-- Login form start. -->
        <div class="pin-input">
            <h2>Login with PIN</h2>
            <form name="verifyPinFrm" auto="false" class="verifyPinFrm" id="verifyPinFrm" action="" method="post" onsubmit="return false;" callback="callbackVerifyUserPin" enctype="multipart/form-data">
                <ul>
                    <?php echo $MXFRM->getForm($loginForm); ?>
                </ul>
                <?php echo $MXFRM->closeForm(); ?>
                <p class="e" id="leadPinErr"></p>
                <a href="javascript:void(0)" class="fa-save thm-btn" rel="verifyPinFrm"> Login </a>
            </form>
        </div>
        <!-- Login form end. -->
    <?php } ?>
</div>
<div class="mxdialog location-access" style="display:none">
    <div class="body">
        <p>Kindly allow your location, otherwise, you can't add leads</p>
    </div>
</div>