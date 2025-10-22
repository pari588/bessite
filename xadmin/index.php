<?php
//echo "HELLLO"; exit;
require_once("../core/core.inc.php");
if (isset($_GET["xAction"]) && trim($_GET["xAction"]) == "xLogout") {
    if (isset($_SESSION[SITEURL])) {
        unset($_SESSION[SITEURL]);
        if (isset($_SESSION) && count($_SESSION) < 1)
            session_destroy();
    }
    header("location:" . ADMINURL . "/login/", true, 301);
    exit;
}

$MXSHOWREC = 20;
if (isset($_REQUEST["showRec"]) && $_REQUEST["showRec"] > 0)
    $MXSHOWREC = intval($_REQUEST["showRec"]);

require_once("core-admin/settings.inc.php");
require_once("core-admin/common.inc.php");
require_once("core-admin/tpl.class.inc.php");
require_once("inc/site.inc.php");


if (isset($MXSET["MULTILINGUAL"]) && $MXSET["MULTILINGUAL"] == 1) {
    $MXLANGS = getLanguages();
}

$TPL = new manageTemplate();
$TPL->tplFile = ADMINPATH . "/core-admin/x-login.php";
$TPL->requestUri = $_SERVER["REQUEST_URI"];
$TPL->tplDefault = $MXSET["DEFAULTPAGE"] . "-list";
$TPL->tplTitle = "";
$TPL->modName = "";
$TPL->pageType = "";
$TPL->setPage();

$MXOFFSET = 0;
$MXSTATUS = 1;

if (isset($_REQUEST["offset"]) && $_REQUEST["offset"] >= 0)
    $MXOFFSET = intval($_REQUEST["offset"]);
if ($TPL->pageType == "trash")
    $MXSTATUS = 0;

if ($TPL->pageUri == "login") {
    require_once(COREPATH . "/form.inc.php");
    require_once($TPL->tplFile);
} else {
    require_once(COREPATH . "/form.inc.php");
    if ($TPL->tplInc) {
        require_once($TPL->tplInc);
        mxSetLogIcon();
    }

    require_once("core-admin/header.php");
    require_once($TPL->tplFile);
    require_once("core-admin/footer.php");
}
if (isset($DB->con))
    $DB->con->close();