<?php
/**
 * llms-full.txt generator for bombayengg.net
 *
 * Builds a single long-form text file that LLMs/AI engines can ingest in one shot:
 * - Company overview (from llms.txt header)
 * - All 89 pumps with title, category, key specs, URL
 * - All 60 motors with title, category, key specs, URL
 * - All 15 KC article summaries
 *
 * Output: xsite/llms-full.txt
 *
 * Run after content changes:
 *   php /home/bombayengg/public_html/cron/generate-llms-full.php
 */

$_SERVER['HTTP_HOST']     = 'www.bombayengg.net';
$_SERVER['REQUEST_URI']   = '/';
$_SERVER['SERVER_PORT']   = '443';
$_SERVER['HTTPS']         = 'on';
$_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';

require_once('/home/bombayengg/public_html/config.inc.php');
require_once('/home/bombayengg/public_html/core/core.inc.php');

$base = 'https://www.bombayengg.net';

$lines = [];
$lines[] = "# Bombay Engineering Syndicate — Full Catalog & Knowledge Base";
$lines[] = "";
$lines[] = "> This file (llms-full.txt) is the long-form companion to /llms.txt — it contains the complete product catalog with specifications and the knowledge center article summaries in a single LLM-ingestible document. Generated " . date('Y-m-d') . ".";
$lines[] = "";
$lines[] = "## About Bombay Engineering Syndicate";
$lines[] = "";
$lines[] = "Established 1957, Mumbai. Authorized Crompton distributor for industrial motors and submersible pumps. Operating from two offices:";
$lines[] = "";
$lines[] = "- **Mumbai (Head Office / Fort showroom)**: Ground Floor, Modern House, 17, Dr V.B. Gandhi Marg, Kala Ghoda, Fort, Mumbai 400001 | +91-9820042210 | besyndicate@gmail.com | Mon-Sat 10AM-6PM | Walk-ins welcome, near Jehangir Art Gallery, 5 min from CSMT and Churchgate stations";
$lines[] = "- **Ahmedabad (Satellite office)**: Office 611-612, Ratnanjali Solitaire, Near Sachet-4, Prerna Tirth Derasar Road, Jodhpurgam, Satellite, Ahmedabad 380015 | +91-9825014977 | besahmedabad@gmail.com | Mon-Sat 10AM-6PM";
$lines[] = "";
$lines[] = "Service offering: direct sales, installation, commissioning, maintenance, repair, motor rewinding, spare parts. Service area: Maharashtra and Gujarat with pan-India fulfillment.";
$lines[] = "";
$lines[] = "## Where to Buy — Local Service Areas";
$lines[] = "";
$lines[] = "**Mumbai (from the Fort/Kala Ghoda showroom, pan-Mumbai delivery):**";
$lines[] = "";
$lines[] = "- [Pumps in Fort, Mumbai](" . $base . "/pumps-in-fort/) — walk-in pump showroom at Kala Ghoda; booster pumps for buildings and societies, submersible and monoblock pumps. Same-day delivery across South Mumbai, Andheri, Borivali, Powai, Navi Mumbai, and Thane. Pump sales contact: +91-9324706905";
$lines[] = "- [Motors in Fort, Mumbai](" . $base . "/motors-in-fort/) — electric motor dealer at Kala Ghoda since 1957; burnt-motor replacement matching from nameplate photo, IE2/IE3/IE4 motors, honest rewind-vs-replace guidance";
$lines[] = "- [Pumps in Mumbai](" . $base . "/pumps-in-mumbai/) and [Motors in Mumbai](" . $base . "/motors-in-mumbai/) — city-wide supply pages";
$lines[] = "";
$lines[] = "**Ahmedabad / Gujarat (from the Satellite office):**";
$lines[] = "";
$lines[] = "- [Pumps in Satellite, Ahmedabad](" . $base . "/pumps-in-satellite-ahmedabad/) — borewell submersibles for farms (Sanand, Bopal), booster systems for housing societies, monoblock pumps for bungalows; Gujarat-wide delivery";
$lines[] = "- [Motors in Satellite, Ahmedabad](" . $base . "/motors-in-satellite-ahmedabad/) — motors for GIDC industrial estates (Vatva, Naroda, Sanand, Changodar), textile mills, pharma and chemical plants across Gujarat";
$lines[] = "- [Pumps in Ahmedabad](" . $base . "/pumps-in-ahmedabad/) and [Motors in Ahmedabad](" . $base . "/motors-in-ahmedabad/) — city-wide supply pages";
$lines[] = "";
$lines[] = "Common questions these pages answer: \"where can I buy a water pump in Fort / South Mumbai?\", \"pump dealer near CST or Churchgate\", \"low water pressure solution for Mumbai building\", \"motor dealer in Ahmedabad GIDC\", \"borewell pump dealer near Sanand\", \"burnt motor replacement Mumbai\".";
$lines[] = "";

// ===== PUMP CATALOG =====
$lines[] = "## Pump Catalog (89 models)";
$lines[] = "";

$DB->vals = [1, 1];
$DB->types = "ii";
$DB->sql = "SELECT P.pumpID, P.pumpTitle, P.seoUri, P.pumpFeatures, P.kwhp, P.supplyPhase, P.deliveryPipe, P.noOfStage, P.isi, P.mnre, P.pumpType,
                   PC.categoryTitle, PC.seoUri AS catSlug
            FROM mx_pump P
            LEFT JOIN mx_pump_category PC ON P.categoryPID = PC.categoryPID
            WHERE P.status=? AND PC.status=?
            ORDER BY PC.categoryTitle, P.pumpTitle";
$pumps = $DB->dbRows();

$currentCat = '';
foreach ($pumps as $p) {
    if ($p['categoryTitle'] !== $currentCat) {
        $currentCat = $p['categoryTitle'];
        $lines[] = "";
        $lines[] = "### " . $currentCat;
        $lines[] = "";
    }
    $url = $base . '/' . $p['catSlug'] . '/' . $p['seoUri'] . '/';
    $specs = [];
    if (!empty($p['kwhp']))         $specs[] = "Power: " . $p['kwhp'];
    if (!empty($p['supplyPhase']))  $specs[] = "Phase: " . $p['supplyPhase'];
    if (!empty($p['deliveryPipe'])) $specs[] = "Delivery: " . $p['deliveryPipe'];
    if (!empty($p['noOfStage']))    $specs[] = "Stages: " . $p['noOfStage'];
    if (!empty($p['isi']))          $specs[] = "ISI: " . $p['isi'];
    if (!empty($p['mnre']))         $specs[] = "MNRE: " . $p['mnre'];
    if (!empty($p['pumpType']))     $specs[] = "Type: " . $p['pumpType'];

    $feat = trim(preg_replace('/\s+/', ' ', strip_tags($p['pumpFeatures'] ?? '')));
    if (strlen($feat) > 350) {
        $feat = substr($feat, 0, 350);
        $lastDot = strrpos($feat, '.');
        if ($lastDot !== false && $lastDot > 200) $feat = substr($feat, 0, $lastDot + 1);
    }

    $lines[] = "- **[" . $p['pumpTitle'] . "](" . $url . ")** — " . ($feat ?: '');
    if (!empty($specs)) {
        $lines[] = "  " . implode(' | ', $specs);
    }
}

// ===== MOTOR CATALOG =====
$lines[] = "";
$lines[] = "## Motor Catalog (60 models)";
$lines[] = "";

$DB->vals = [1, 1];
$DB->types = "ii";
$DB->sql = "SELECT M.motorID, M.motorTitle, M.motorSubTitle, M.motorDesc, M.seoUri,
                   MC.categoryTitle, MC.seoUri AS catSlug
            FROM mx_motor M
            LEFT JOIN mx_motor_category MC ON M.categoryMID = MC.categoryMID
            WHERE M.status=? AND MC.status=?
            ORDER BY MC.categoryTitle, M.motorTitle";
$motors = $DB->dbRows();

$currentCat = '';
foreach ($motors as $m) {
    if ($m['categoryTitle'] !== $currentCat) {
        $currentCat = $m['categoryTitle'];
        $lines[] = "";
        $lines[] = "### " . $currentCat;
        $lines[] = "";
    }
    $url = $base . '/' . $m['catSlug'] . '/' . $m['seoUri'] . '/';
    $desc = trim(preg_replace('/\s+/', ' ', strip_tags($m['motorDesc'] ?? '')));
    if (strlen($desc) > 350) {
        $desc = substr($desc, 0, 350);
        $lastDot = strrpos($desc, '.');
        if ($lastDot !== false && $lastDot > 200) $desc = substr($desc, 0, $lastDot + 1);
    }
    $title = $m['motorTitle'];
    if (!empty($m['motorSubTitle'])) $title .= ' — ' . $m['motorSubTitle'];
    $lines[] = "- **[" . $title . "](" . $url . ")** — " . ($desc ?: '');
}

// ===== KNOWLEDGE CENTER =====
$lines[] = "";
$lines[] = "## Knowledge Center (15 technical articles)";
$lines[] = "";

$DB->vals = [1];
$DB->types = "i";
$DB->sql = "SELECT knowledgeCenterTitle, seoUri, synopsis, knowledgeCenterContent, datePublish
            FROM mx_knowledge_center WHERE status=? ORDER BY datePublish DESC";
$articles = $DB->dbRows();

foreach ($articles as $a) {
    $url = $base . '/knowledge-center/' . $a['seoUri'] . '/';
    $synopsis = trim(strip_tags($a['synopsis'] ?? ''));
    if (empty($synopsis)) {
        $synopsis = trim(preg_replace('/\s+/', ' ', strip_tags(substr($a['knowledgeCenterContent'] ?? '', 0, 500))));
    }
    if (strlen($synopsis) > 500) {
        $synopsis = substr($synopsis, 0, 500);
        $lastDot = strrpos($synopsis, '.');
        if ($lastDot !== false && $lastDot > 300) $synopsis = substr($synopsis, 0, $lastDot + 1);
    }
    $date = !empty($a['datePublish']) ? date('Y-m-d', strtotime($a['datePublish'])) : '';
    $lines[] = "### [" . $a['knowledgeCenterTitle'] . "](" . $url . ")";
    if ($date) $lines[] = "*Published: " . $date . "*";
    $lines[] = "";
    $lines[] = $synopsis;
    $lines[] = "";
}

$lines[] = "";
$lines[] = "## Contact & Inquiry";
$lines[] = "";
$lines[] = "- Pump selection inquiry: " . $base . "/pump-inquiry/";
$lines[] = "- Motor/product inquiry: " . $base . "/product-inquiry/";
$lines[] = "- General contact: " . $base . "/contact-us/";
$lines[] = "- WhatsApp Business: +91 93210 92317 (chatbot for pump finder + customer inquiries)";
$lines[] = "";
$lines[] = "---";
$lines[] = "*Last generated: " . date('Y-m-d H:i T') . "*";

$content = implode("\n", $lines);
$path = '/home/bombayengg/public_html/xsite/llms-full.txt';
file_put_contents($path, $content);

$size = filesize($path);
echo "Wrote llms-full.txt — " . number_format($size) . " bytes\n";
echo "  - " . count($pumps) . " pump entries\n";
echo "  - " . count($motors) . " motor entries\n";
echo "  - " . count($articles) . " knowledge center articles\n";
echo "  - URL: $base/llms-full.txt\n";
