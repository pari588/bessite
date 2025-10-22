<?php
require_once("../core/core.inc.php");
require_once("core-site/tpl.class.inc.php");
require_once("core-site/common.inc.php");
require_once("../" . COREDIR . "/form.inc.php");
require_once("../" . COREDIR . "/validate.inc.php");
require_once("inc/site.inc.php");

$MXOFFSET = 0;
if (isset($_REQUEST["offset"]))
    $MXOFFSET = intval($_REQUEST["offset"]);

if (isset($MXSET["MULTILINGUAL"]) && $MXSET["MULTILINGUAL"] == 1) {
    $MXLANGS = getLanguages();
}

$TPL = new manageTemplate();
$TPL->setTemplate();
if ($TPL->pageType == "404") {
    http_response_code(404);
}

$headerFile = $TPL->modDir . "/header.php";
$footerFile = $TPL->modDir . "/footer.php";

if ($TPL->modName == "lead" || $TPL->modName == "leave" || $TPL->pageUri == "leave/list" || $TPL->pageUri == "leave/apply") {
    $headerFile = $TPL->modDir . "/header-webapp.php";
    $footerFile = $TPL->modDir . "/footer-webapp.php";
}



if ($TPL->tplInc)
    require_once($TPL->tplInc);

require_once($headerFile);
require_once($TPL->tplFile);
require_once($footerFile);
if (isset($DB->con))
    $DB->con->close();
