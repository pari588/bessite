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

// Driver PWA - Use dedicated header/footer
if (strpos($TPL->pageUri, 'driver/') === 0) {
    $headerFile = SITEPATH . "/mod/driver/header-driver.php";
    $footerFile = SITEPATH . "/mod/driver/footer-driver.php";
}

// HRMS Portal - Use dedicated header/footer
if (strpos($TPL->pageUri, 'hrms/') === 0) {
    require_once(SITEPATH . "/mod/hrms/x-hrms.inc.php");
    $headerFile = SITEPATH . "/mod/hrms/header-hrms.php";
    $footerFile = SITEPATH . "/mod/hrms/footer-hrms.php";
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
        // Build product title (NO price — prices are not published on the site;
        // the site shows a "Call for Price" CTA, so schema/OG must not leak price)
        $og_title = $TPL->data['pumpTitle'];

        // Build product image URL - using 530x530 optimized images
        $og_image = !empty($TPL->data['pumpImage']) ?
                    UPLOADURL . '/pump/530_530_crop_100/' . $TPL->data['pumpImage'] :
                    SITEURL . '/images/moters.jpeg';

        // Build product description - strip HTML and limit to 160 characters
        $og_description = !empty($TPL->data['pumpFeatures']) ?
                          substr(strip_tags($TPL->data['pumpFeatures']), 0, 160) :
                          'Premium pump product from Bombay Engineering Syndicate';

        // Keyword-optimized meta: model/title + role terms (dealer/supplier) + brand + Mumbai
        $categoryName = !empty($TPL->dataM['categoryTitle']) ? $TPL->dataM['categoryTitle'] : 'Pump';
        $pt = $TPL->data['pumpTitle'];
        $catL = strtolower($categoryName);
        $TPL->metaTitle = $pt . ' – Crompton Pump Dealer in Mumbai | Bombay Engineering Syndicate';
        $TPL->metaDesc = $pt . ' from Bombay Engineering Syndicate — authorised Crompton & Kirloskar pump dealer, distributor & supplier in Fort, Mumbai. Call +91 93247 06905.';
        $TPL->metaKeyword = strtolower($pt) . ', ' . $catL . ' dealer mumbai, ' . $catL . ' supplier mumbai, crompton pump dealer mumbai, kirloskar pump distributor mumbai, ' . strtolower($pt) . ' price mumbai, authorised pump dealer mumbai';

        // Store in PHP constants for use in header.php
        define('WHATSAPP_OG_TITLE', $og_title);
        define('WHATSAPP_OG_IMAGE', $og_image);
        define('WHATSAPP_OG_DESCRIPTION', $og_description);
        define('WHATSAPP_OG_TYPE', 'product');
    }
}

// KNOWLEDGE CENTER LIST/HUB PAGE — set topical meta to avoid boilerplate fallback
if ($TPL->modName == "knowledge-center" && count($TPL->uriArr) == 1) {
    $TPL->metaTitle = 'Knowledge Center — Motor & Pump Guides, Selection Tips & Technical Articles | Bombay Engineering Syndicate';
    $TPL->metaDesc = '15 technical articles for engineers and buyers: motor nameplate reading, IE2/IE3/IE4 efficiency classes, mounting types, bearings, VFD compatibility, HP-to-kW conversion, hazardous-area motors, and Crompton pump selection guides.';
    $TPL->metaKeyword = 'motor knowledge center, pump selection guide, motor nameplate, IE3 IE4 motors, motor efficiency, motor mounting types, VFD compatibility, HP to kW conversion, bearing maintenance, hazardous area motors';
}

// KNOWLEDGE CENTER DETAIL PAGES
if ($TPL->modName == "knowledge-center" && $TPL->pageType != "list") {
    // This is a knowledge center detail page - load and generate dynamic OG tags
    $seoUri = $TPL->uriArr[1] ?? '';
    if (!empty($seoUri)) {
        // Query knowledge center data (include synopsis and datePublish for SEO)
        $DB->vals = array(1, $seoUri);
        $DB->types = "is";
        $DB->sql = "SELECT knowledgeCenterImage, knowledgeCenterTitle, knowledgeCenterContent, synopsis, datePublish FROM `" . $DB->pre . "knowledge_center` WHERE status=? AND seoUri=?";
        $kCenter = $DB->dbRow();

        if (!empty($kCenter) && !empty($kCenter['knowledgeCenterTitle'])) {
            // Build article title
            $og_title = $kCenter['knowledgeCenterTitle'];

            // Build article image URL
            $og_image = !empty($kCenter['knowledgeCenterImage']) ?
                        UPLOADURL . '/knowledge-center/' . $kCenter['knowledgeCenterImage'] :
                        SITEURL . '/images/moters.jpeg';

            // Build article description - prefer synopsis, fallback to content excerpt
            $og_description = !empty($kCenter['synopsis']) ?
                              $kCenter['synopsis'] :
                              (!empty($kCenter['knowledgeCenterContent']) ?
                              substr(strip_tags($kCenter['knowledgeCenterContent']), 0, 160) :
                              'Knowledge article from Bombay Engineering Syndicate');

            // Override generic title/meta with article-specific data
            $TPL->metaTitle = $kCenter['knowledgeCenterTitle'] . ' | Bombay Engineering Syndicate';
            $TPL->metaDesc = !empty($kCenter['synopsis']) ? $kCenter['synopsis'] : $og_description;

            // Store in PHP constants for use in header.php
            define('WHATSAPP_OG_TITLE', $og_title);
            define('WHATSAPP_OG_IMAGE', $og_image);
            define('WHATSAPP_OG_DESCRIPTION', $og_description);
            define('WHATSAPP_OG_TYPE', 'article');
            define('ARTICLE_PUBLISHED_DATE', $kCenter['datePublish'] ?? '');
            define('ARTICLE_IMAGE_URL', $og_image);
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

        // Keyword-optimized meta: model/title + role terms + CG Power brand + Mumbai.
        // NOTE: motors/drives are manufactured by CG Power and Industrial Solutions Ltd —
        // a SEPARATE company from Crompton Greaves Consumer Electricals Ltd ("Crompton"),
        // which makes the pumps. Do not conflate the two in motor copy. Legacy
        // "crompton greaves" terms are retained in keywords only (search-intent capture).
        $categoryName = !empty($TPL->dataM['categoryTitle']) ? $TPL->dataM['categoryTitle'] : 'Motor';
        $mt = $TPL->data['motorTitle'];
        $catL = strtolower($categoryName);
        $TPL->metaTitle = $mt . ' – CG Power Motor Dealer in Mumbai | Bombay Engineering Syndicate';
        $TPL->metaDesc = $mt . ' from Bombay Engineering Syndicate — authorised CG Power (formerly Crompton Greaves industrial) motor dealer, distributor & supplier in Fort, Mumbai. Call +91 93247 06905.';
        $TPL->metaKeyword = strtolower($mt) . ', ' . $catL . ' dealer mumbai, ' . $catL . ' supplier mumbai, CG Power motor dealer mumbai, crompton greaves motor distributor mumbai, ' . strtolower($mt) . ' price mumbai, authorised motor dealer mumbai';

        // Store in PHP constants for use in header.php
        define('WHATSAPP_OG_TITLE', $og_title);
        define('WHATSAPP_OG_IMAGE', $og_image);
        define('WHATSAPP_OG_DESCRIPTION', $og_description);
        define('WHATSAPP_OG_TYPE', 'product');
    }
}

// PUMP CATEGORY PAGES (pageType is "pumps" or "pump" for category, "x-detail" for product)
if ($TPL->modName == "pumps" && $TPL->pageType != "x-detail" && !empty($TPL->data) && !empty($TPL->data['categoryTitle'])) {
    $catTitle = $TPL->data['categoryTitle'];
    $catL = strtolower($catTitle);
    $TPL->metaTitle = $catTitle . ' Dealer & Supplier in Mumbai | Crompton Authorised | Bombay Engineering Syndicate';
    if (!empty($TPL->data['synopsis'])) {
        $TPL->metaDesc = substr(strip_tags($TPL->data['synopsis']), 0, 160);
    } else {
        $TPL->metaDesc = $catTitle . ' from Bombay Engineering Syndicate — authorised Crompton & Kirloskar pump dealer, distributor & supplier in Fort, Mumbai. Genuine products, expert support since 1957.';
    }
    $TPL->metaKeyword = $catL . ' dealer mumbai, ' . $catL . ' supplier mumbai, ' . $catL . ' distributor mumbai, crompton ' . $catL . ' mumbai, kirloskar ' . $catL . ', authorised pump dealer mumbai, ' . $catL . ' stockist mumbai';
}

// PUMP HUB PAGE (/pump/ — no specific category)
if ($TPL->modName == "pumps" && count($TPL->uriArr) == 1 && empty($TPL->metaTitle)) {
    $TPL->metaTitle = 'Industrial Pumps Supplier in Mumbai & Ahmedabad | Submersible, Borewell, Booster Pumps | Bombay Engineering Syndicate';
    $TPL->metaDesc = 'Authorised Crompton & Kirloskar pump dealer in Mumbai (Fort) and Ahmedabad since 1957. Browse 89 industrial, submersible, borewell, openwell, mini and booster pumps. Genuine products, expert installation guidance, pan-India delivery.';
}

// MOTOR HUB PAGE (/motor/)
if ($TPL->modName == "motors" && count($TPL->uriArr) == 1 && empty($TPL->metaTitle)) {
    $TPL->metaTitle = 'Industrial Motors Dealer in Mumbai & Ahmedabad | CG Power Authorised | Bombay Engineering Syndicate';
    $TPL->metaDesc = 'CG Power industrial electric motors dealer in Mumbai and Ahmedabad. 84 models across HV/LV, energy-efficient (IE2/IE3/IE4), hazardous-area, FHP and specialty motors. Expert sizing and pan-India delivery since 1957.';
}

// MOTOR CATEGORY PAGES
if ($TPL->modName == "motors" && $TPL->pageType != "x-detail" && !empty($TPL->data) && !empty($TPL->data['categoryTitle'])) {
    $catTitle = $TPL->data['categoryTitle'];
    $catL = strtolower($catTitle);
    $TPL->metaTitle = $catTitle . ' Dealer & Supplier in Mumbai | CG Power Authorised | Bombay Engineering Syndicate';
    if (!empty($TPL->data['synopsis'])) {
        $TPL->metaDesc = substr(strip_tags($TPL->data['synopsis']), 0, 160);
    } else {
        $TPL->metaDesc = $catTitle . ' from Bombay Engineering Syndicate — authorised CG Power motor dealer, distributor & supplier in Fort, Mumbai. Genuine products, expert sizing since 1957.';
    }
    $TPL->metaKeyword = $catL . ' dealer mumbai, ' . $catL . ' supplier mumbai, ' . $catL . ' distributor mumbai, CG Power ' . $catL . ' mumbai, crompton greaves ' . $catL . ', authorised motor dealer mumbai, ' . $catL . ' stockist mumbai';
}

// PAGE LANDING PAGES (pumps-in-mumbai, motors-in-mumbai, etc.)
if ($TPL->modName == "page" && !empty($TPL->data) && !empty($TPL->data['pageID'])) {
    $pageId = intval($TPL->data['pageID']);
    $DB->vals = array(1, 'page', (string)$pageId);
    $DB->types = "iss";
    $DB->sql = "SELECT metaTitle, metaDesc FROM `" . $DB->pre . "x_meta` WHERE status=? AND metaKey=? AND metaValue=?";
    $pageMeta = $DB->dbRow();
    if (!empty($pageMeta) && !empty($pageMeta['metaTitle'])) {
        define('WHATSAPP_OG_TITLE', $pageMeta['metaTitle']);
        define('WHATSAPP_OG_DESCRIPTION', !empty($pageMeta['metaDesc']) ? $pageMeta['metaDesc'] : $TPL->data['pageTitle']);
        define('WHATSAPP_OG_TYPE', 'website');
    }
}

// ────────────────────────────────────────────────────────────────────────────────

require_once($headerFile);
require_once($TPL->tplFile);
require_once($footerFile);
if (isset($DB->con))
    $DB->con->close();
