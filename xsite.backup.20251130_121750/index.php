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

// Load module-specific includes first (contains getPDetail and other functions)
if ($TPL->tplInc)
    require_once($TPL->tplInc);

// ────────────────────────────────────────────────────────────────────────────────
// WhatsApp Link Preview - Generate Dynamic OG Meta Tags for Detail Pages
// This must run BEFORE header.php is included so constants are defined
// ────────────────────────────────────────────────────────────────────────────────

// PUMP DETAIL PAGES
if ($TPL->modName == "pumps" && $TPL->pageType != "list") {
    // This is a pump detail page - generate dynamic OG tags
    if (!empty($TPL->data) && !empty($TPL->data['pumpTitle'])) {
        // Get pump detail data for price
        $pumsDetailArr = getPDetail($TPL->data['pumpID']);

        // Build product title with price if available
        $og_title = $TPL->data['pumpTitle'];

        // Add price if available from detail record
        if (!empty($pumsDetailArr) && !empty($pumsDetailArr[0]['mrp'])) {
            $mrp_clean = str_replace(['₹', ',', ' '], '', $pumsDetailArr[0]['mrp']);
            $og_title .= ' - ₹' . $mrp_clean;
        }

        // Build product image URL - using 530x530 optimized images
        $og_image = !empty($TPL->data['pumpImage']) ?
                    UPLOADURL . '/pump/530_530_crop_100/' . $TPL->data['pumpImage'] :
                    SITEURL . '/images/moters.jpeg';

        // Build product description - strip HTML and limit to 160 characters
        $og_description = !empty($TPL->data['pumpFeatures']) ?
                          substr(strip_tags($TPL->data['pumpFeatures']), 0, 160) :
                          'Premium pump product from Bombay Engineering Syndicate';

        // Store in PHP constants for use in header.php
        define('WHATSAPP_OG_TITLE', $og_title);
        define('WHATSAPP_OG_IMAGE', $og_image);
        define('WHATSAPP_OG_DESCRIPTION', $og_description);
        define('WHATSAPP_OG_TYPE', 'product');
    }
}

// KNOWLEDGE CENTER DETAIL PAGES
if ($TPL->modName == "knowledge-center" && $TPL->pageType != "list") {
    // This is a knowledge center detail page - load and generate dynamic OG tags
    $seoUri = $TPL->uriArr[1] ?? '';
    if (!empty($seoUri)) {
        // Query knowledge center data
        $DB->vals = array(1, $seoUri);
        $DB->types = "is";
        $DB->sql = "SELECT knowledgeCenterImage, knowledgeCenterTitle, knowledgeCenterContent FROM `" . $DB->pre . "knowledge_center` WHERE status=? AND seoUri=?";
        $kCenter = $DB->dbRow();

        if (!empty($kCenter) && !empty($kCenter['knowledgeCenterTitle'])) {
            // Build article title
            $og_title = $kCenter['knowledgeCenterTitle'];

            // Build article image URL
            $og_image = !empty($kCenter['knowledgeCenterImage']) ?
                        UPLOADURL . '/knowledge-center/' . $kCenter['knowledgeCenterImage'] :
                        SITEURL . '/images/moters.jpeg';

            // Build article description - strip HTML and limit to 160 characters
            $og_description = !empty($kCenter['knowledgeCenterContent']) ?
                              substr(strip_tags($kCenter['knowledgeCenterContent']), 0, 160) :
                              'Knowledge article from Bombay Engineering Syndicate';

            // Store in PHP constants for use in header.php
            define('WHATSAPP_OG_TITLE', $og_title);
            define('WHATSAPP_OG_IMAGE', $og_image);
            define('WHATSAPP_OG_DESCRIPTION', $og_description);
            define('WHATSAPP_OG_TYPE', 'article');
        }
    }
}

// MOTOR DETAIL PAGES
if ($TPL->modName == "motors" && $TPL->pageType != "list") {
    // This is a motor detail page - generate dynamic OG tags
    if (!empty($TPL->data) && !empty($TPL->data['motorTitle'])) {
        // Build product title
        $og_title = $TPL->data['motorTitle'];

        // Add subtitle if available
        if (!empty($TPL->data['motorSubTitle'])) {
            $og_title .= ' - ' . $TPL->data['motorSubTitle'];
        }

        // Build product image URL - using 530x530 optimized images
        $og_image = !empty($TPL->data['motorImage']) ?
                    UPLOADURL . '/motor/530_530_crop_100/' . $TPL->data['motorImage'] :
                    SITEURL . '/images/moters.jpeg';

        // Build product description - strip HTML and limit to 160 characters
        $og_description = !empty($TPL->data['motorDesc']) ?
                          substr(strip_tags($TPL->data['motorDesc']), 0, 160) :
                          'Premium motor product from Bombay Engineering Syndicate';

        // Store in PHP constants for use in header.php
        define('WHATSAPP_OG_TITLE', $og_title);
        define('WHATSAPP_OG_IMAGE', $og_image);
        define('WHATSAPP_OG_DESCRIPTION', $og_description);
        define('WHATSAPP_OG_TYPE', 'product');
    }
}

// ────────────────────────────────────────────────────────────────────────────────

require_once($headerFile);
require_once($TPL->tplFile);
require_once($footerFile);
if (isset($DB->con))
    $DB->con->close();
