<?php
/**
 * Sitemap generator for bombayengg.net
 *
 * Generates xsite/sitemap.xml and xsite/sitemap_index.xml from the live database.
 * Usage:  php /home/bombayengg/public_html/cron/generate-sitemap.php
 *
 * Run on any content change (new product, KC article, category move).
 */

$_SERVER['HTTP_HOST']      = 'www.bombayengg.net';
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['SERVER_PORT']    = '443';
$_SERVER['HTTPS']          = 'on';
$_SERVER['DOCUMENT_ROOT']  = '/home/bombayengg/public_html';

require_once('/home/bombayengg/public_html/config.inc.php');
require_once('/home/bombayengg/public_html/core/core.inc.php');

$base       = 'https://www.bombayengg.net';
$today      = date('Y-m-d');
$urls       = [];

// Homepage
$urls[] = ['loc' => $base . '/', 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '1.0'];

// Static pages (from DB - mx_page where status=1)
$DB->vals = [1];
$DB->types = "i";
$DB->sql = "SELECT seoUri FROM mx_page WHERE status=? ORDER BY pageID";
$pages = $DB->dbRows();
foreach ($pages as $p) {
    if (empty($p['seoUri'])) continue;
    $urls[] = ['loc' => $base . '/' . $p['seoUri'] . '/', 'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.8'];
}

// Knowledge Center hub + articles
$urls[] = ['loc' => $base . '/knowledge-center/', 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.8'];
$DB->vals = [1];
$DB->types = "i";
$DB->sql = "SELECT seoUri, datePublish FROM mx_knowledge_center WHERE status=? ORDER BY knowledgeCenterID";
$articles = $DB->dbRows();
foreach ($articles as $a) {
    if (empty($a['seoUri'])) continue;
    $lastmod = !empty($a['datePublish']) ? date('Y-m-d', strtotime($a['datePublish'])) : $today;
    $urls[] = ['loc' => $base . '/knowledge-center/' . $a['seoUri'] . '/', 'lastmod' => $lastmod, 'changefreq' => 'monthly', 'priority' => '0.7'];
}

// Pump categories
$DB->vals = [1];
$DB->types = "i";
$DB->sql = "SELECT seoUri FROM mx_pump_category WHERE status=? ORDER BY categoryPID";
$pumpCats = $DB->dbRows();
foreach ($pumpCats as $c) {
    if (empty($c['seoUri'])) continue;
    $urls[] = ['loc' => $base . '/' . $c['seoUri'] . '/', 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.8'];
}

// Pump products
$DB->vals = [1, 1];
$DB->types = "ii";
$DB->sql = "SELECT P.seoUri AS pumpSlug, PC.seoUri AS catSlug
            FROM mx_pump P
            LEFT JOIN mx_pump_category PC ON P.categoryPID = PC.categoryPID
            WHERE P.status=? AND PC.status=?";
$pumps = $DB->dbRows();
foreach ($pumps as $p) {
    if (empty($p['pumpSlug']) || empty($p['catSlug'])) continue;
    $urls[] = ['loc' => $base . '/' . $p['catSlug'] . '/' . $p['pumpSlug'] . '/', 'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.6'];
}

// Motor categories
$DB->vals = [1];
$DB->types = "i";
$DB->sql = "SELECT seoUri FROM mx_motor_category WHERE status=? ORDER BY categoryMID";
$motorCats = $DB->dbRows();
foreach ($motorCats as $c) {
    if (empty($c['seoUri'])) continue;
    $urls[] = ['loc' => $base . '/' . $c['seoUri'] . '/', 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.8'];
}

// Motor products
$DB->vals = [1, 1];
$DB->types = "ii";
$DB->sql = "SELECT M.seoUri AS motorSlug, MC.seoUri AS catSlug
            FROM mx_motor M
            LEFT JOIN mx_motor_category MC ON M.categoryMID = MC.categoryMID
            WHERE M.status=? AND MC.status=?";
$motors = $DB->dbRows();
foreach ($motors as $m) {
    if (empty($m['motorSlug']) || empty($m['catSlug'])) continue;
    $urls[] = ['loc' => $base . '/' . $m['catSlug'] . '/' . $m['motorSlug'] . '/', 'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.6'];
}

// Deduplicate by loc (in case of overlapping paths)
$seen = [];
$unique = [];
foreach ($urls as $u) {
    if (isset($seen[$u['loc']])) continue;
    $seen[$u['loc']] = true;
    $unique[] = $u;
}

// Write sitemap.xml
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($unique as $u) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
    $xml .= "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
    $xml .= "    <changefreq>" . $u['changefreq'] . "</changefreq>\n";
    $xml .= "    <priority>" . $u['priority'] . "</priority>\n";
    $xml .= "  </url>\n";
}
$xml .= "</urlset>\n";
file_put_contents('/home/bombayengg/public_html/xsite/sitemap.xml', $xml);

// Write sitemap_index.xml
$idx = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$idx .= "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
$idx .= "  <sitemap>\n";
$idx .= "    <loc>$base/sitemap.xml</loc>\n";
$idx .= "    <lastmod>$today</lastmod>\n";
$idx .= "  </sitemap>\n";
$idx .= "</sitemapindex>\n";
file_put_contents('/home/bombayengg/public_html/xsite/sitemap_index.xml', $idx);

echo "Sitemap regenerated: " . count($unique) . " URLs\n";
echo "  - " . count($pages) . " static pages\n";
echo "  - " . count($articles) . " knowledge-center articles\n";
echo "  - " . count($pumpCats) . " pump categories + " . count($pumps) . " pump products\n";
echo "  - " . count($motorCats) . " motor categories + " . count($motors) . " motor products\n";
