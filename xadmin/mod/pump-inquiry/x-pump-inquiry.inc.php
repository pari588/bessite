<?php

if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "pump_inquiry", "PK" => "pumpInquiryID"));
}
