<?php
require_once(__DIR__ . "/core/core.inc.php");

$siteUrl = "https://www.bombayengg.net";
$sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// 1. Add static pages
$staticPages = array(
    array('loc' => '/', 'priority' => '0.9', 'changefreq' => 'weekly'),
    array('loc' => '/about-us/', 'priority' => '0.8', 'changefreq' => 'monthly'),
    array('loc' => '/contact-us/', 'priority' => '0.8', 'changefreq' => 'weekly'),
    array('loc' => '/knowledge-center/', 'priority' => '0.8', 'changefreq' => 'weekly'),
);

$today = date('Y-m-d');

foreach ($staticPages as $page) {
    $sitemap .= "  <url>\n";
    $sitemap .= "    <loc>" . $siteUrl . $page['loc'] . "</loc>\n";
    $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
    $sitemap .= "    <changefreq>" . $page['changefreq'] . "</changefreq>\n";
    $sitemap .= "    <priority>" . $page['priority'] . "</priority>\n";
    $sitemap .= "  </url>\n";
}

// 2. Add motor category pages
$DB->sql = "SELECT seoUri, UNIX_TIMESTAMP(lastUpdate) as updateTime FROM `" . $DB->pre . "motor_category` WHERE status=1 ORDER BY categoryMID ASC";
$motorCategories = $DB->dbRows();
if ($DB->numRows > 0) {
    foreach ($motorCategories as $cat) {
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>" . $siteUrl . "/" . $cat['seoUri'] . "/</loc>\n";
        $sitemap .= "    <lastmod>" . date('Y-m-d', $cat['updateTime']) . "</lastmod>\n";
        $sitemap .= "    <changefreq>weekly</changefreq>\n";
        $sitemap .= "    <priority>0.8</priority>\n";
        $sitemap .= "  </url>\n";
    }
}

// 3. Add pump category pages
$DB->sql = "SELECT seoUri, UNIX_TIMESTAMP(lastUpdate) as updateTime FROM `" . $DB->pre . "pump_category` WHERE status=1 ORDER BY categoryPID ASC";
$pumpCategories = $DB->dbRows();
if ($DB->numRows > 0) {
    foreach ($pumpCategories as $cat) {
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>" . $siteUrl . "/" . $cat['seoUri'] . "/</loc>\n";
        $sitemap .= "    <lastmod>" . date('Y-m-d', $cat['updateTime']) . "</lastmod>\n";
        $sitemap .= "    <changefreq>weekly</changefreq>\n";
        $sitemap .= "    <priority>0.8</priority>\n";
        $sitemap .= "  </url>\n";
    }
}

// 4. Add motor product pages (CRITICAL FOR INDEXATION)
$DB->sql = "SELECT m.seoUri, c.seoUri as catSeoUri, UNIX_TIMESTAMP(m.lastUpdate) as updateTime
            FROM `" . $DB->pre . "motor` m
            LEFT JOIN `" . $DB->pre . "motor_category` c ON c.categoryMID = m.categoryMID
            WHERE m.status=1
            ORDER BY m.motorID ASC";
$DB->dbRows();
$motorProducts = $DB->rows;
$motorCount = $DB->numRows;
if ($motorCount > 0) {
    foreach ($motorProducts as $product) {
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>" . $siteUrl . "/" . $product['catSeoUri'] . "/" . $product['seoUri'] . "/</loc>\n";
        $sitemap .= "    <lastmod>" . date('Y-m-d', $product['updateTime']) . "</lastmod>\n";
        $sitemap .= "    <changefreq>monthly</changefreq>\n";
        $sitemap .= "    <priority>0.7</priority>\n";
        $sitemap .= "  </url>\n";
    }
}

// 5. Add pump product pages (CRITICAL FOR INDEXATION)
$DB->sql = "SELECT p.seoUri, c.seoUri as catSeoUri, UNIX_TIMESTAMP(p.lastUpdate) as updateTime
            FROM `" . $DB->pre . "pump` p
            LEFT JOIN `" . $DB->pre . "pump_category` c ON c.categoryPID = p.categoryPID
            WHERE p.status=1
            ORDER BY p.pumpID ASC";
$DB->dbRows();
$pumpProducts = $DB->rows;
$pumpCount = $DB->numRows;
if ($pumpCount > 0) {
    foreach ($pumpProducts as $product) {
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>" . $siteUrl . "/" . $product['catSeoUri'] . "/" . $product['seoUri'] . "/</loc>\n";
        $sitemap .= "    <lastmod>" . date('Y-m-d', $product['updateTime']) . "</lastmod>\n";
        $sitemap .= "    <changefreq>monthly</changefreq>\n";
        $sitemap .= "    <priority>0.7</priority>\n";
        $sitemap .= "  </url>\n";
    }
}

// 6. Add knowledge center pages
$DB->sql = "SELECT seoUri, UNIX_TIMESTAMP(lastUpdate) as updateTime FROM `" . $DB->pre . "knowledge_center` WHERE status=1 ORDER BY knowledgeCenterID ASC";
$kcPages = $DB->dbRows();
if ($DB->numRows > 0) {
    foreach ($kcPages as $page) {
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>" . $siteUrl . "/knowledge-center/" . $page['seoUri'] . "/</loc>\n";
        $sitemap .= "    <lastmod>" . date('Y-m-d', $page['updateTime']) . "</lastmod>\n";
        $sitemap .= "    <changefreq>monthly</changefreq>\n";
        $sitemap .= "    <priority>0.7</priority>\n";
        $sitemap .= "  </url>\n";
    }
}

$sitemap .= "</urlset>\n";

// Write sitemap to file
$sitemapPath = __DIR__ . "/xsite/sitemap.xml";
error_log("Attempting to write sitemap to: " . $sitemapPath);
error_log("Sitemap size: " . strlen($sitemap) . " bytes");
error_log("Motor count: " . $motorCount . ", Pump count: " . $pumpCount);

if (file_put_contents($sitemapPath, $sitemap)) {
    $output = "✓ Sitemap generated successfully!\n";
    $output .= "  Location: " . $sitemapPath . "\n";
    $output .= "  Total URLs: " . ($motorCount + $pumpCount + count($staticPages) + count($motorCategories) + count($pumpCategories)) . "\n";
    $output .= "  Motor Products: " . $motorCount . "\n";
    $output .= "  Pump Products: " . $pumpCount . "\n";
    $output .= "  Motor Categories: " . count($motorCategories) . "\n";
    $output .= "  Pump Categories: " . count($pumpCategories) . "\n";
    $output .= "  Static Pages: " . count($staticPages) . "\n";
    echo $output;
    error_log($output);
} else {
    echo "✗ Error writing sitemap file\n";
    error_log("Error writing sitemap file");
}

?>
