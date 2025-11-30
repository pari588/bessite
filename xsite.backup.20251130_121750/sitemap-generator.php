<?php
// Force output buffering to catch any output
ob_start();

try {
    require_once(__DIR__ . "/../core/core.inc.php");

    $siteUrl = "https://www.bombayengg.net";
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    $today = date('Y-m-d');

    // Static pages
    $staticPages = array(
        array('loc' => '', 'priority' => '0.9'),
        array('loc' => 'about-us', 'priority' => '0.8'),
        array('loc' => 'contact-us', 'priority' => '0.8'),
        array('loc' => 'knowledge-center', 'priority' => '0.8'),
    );

    foreach ($staticPages as $page) {
        $loc = $siteUrl . '/' . $page['loc'] . '/';
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
        $sitemap .= "    <changefreq>weekly</changefreq>\n";
        $sitemap .= "    <priority>" . $page['priority'] . "</priority>\n";
        $sitemap .= "  </url>\n";
    }

    // Motor categories
    $DB->dbRows("SELECT seoUri FROM `" . $DB->pre . "motor_category` WHERE status=1");
    $motorCats = $DB->rows;
    foreach ($motorCats as $cat) {
        $loc = $siteUrl . '/' . $cat['seoUri'] . '/';
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
        $sitemap .= "    <changefreq>weekly</changefreq>\n";
        $sitemap .= "    <priority>0.8</priority>\n";
        $sitemap .= "  </url>\n";
    }
    $motorCatCount = $DB->numRows;

    // Pump categories
    $DB->dbRows("SELECT seoUri FROM `" . $DB->pre . "pump_category` WHERE status=1");
    $pumpCats = $DB->rows;
    foreach ($pumpCats as $cat) {
        $loc = $siteUrl . '/' . $cat['seoUri'] . '/';
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
        $sitemap .= "    <changefreq>weekly</changefreq>\n";
        $sitemap .= "    <priority>0.8</priority>\n";
        $sitemap .= "  </url>\n";
    }
    $pumpCatCount = $DB->numRows;

    // Motor products
    $DB->dbRows("SELECT m.seoUri, c.seoUri as catSeoUri
                FROM `" . $DB->pre . "motor` m
                LEFT JOIN `" . $DB->pre . "motor_category` c ON c.categoryMID = m.categoryMID
                WHERE m.status=1");
    $motorProds = $DB->rows;
    foreach ($motorProds as $prod) {
        $loc = $siteUrl . '/' . $prod['catSeoUri'] . '/' . $prod['seoUri'] . '/';
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
        $sitemap .= "    <changefreq>monthly</changefreq>\n";
        $sitemap .= "    <priority>0.7</priority>\n";
        $sitemap .= "  </url>\n";
    }
    $motorCount = $DB->numRows;

    // Pump products
    $DB->dbRows("SELECT p.seoUri, c.seoUri as catSeoUri
                FROM `" . $DB->pre . "pump` p
                LEFT JOIN `" . $DB->pre . "pump_category` c ON c.categoryPID = p.categoryPID
                WHERE p.status=1");
    $pumpProds = $DB->rows;
    foreach ($pumpProds as $prod) {
        $loc = $siteUrl . '/' . $prod['catSeoUri'] . '/' . $prod['seoUri'] . '/';
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
        $sitemap .= "    <changefreq>monthly</changefreq>\n";
        $sitemap .= "    <priority>0.7</priority>\n";
        $sitemap .= "  </url>\n";
    }
    $pumpCount = $DB->numRows;

    // Knowledge center pages
    $DB->dbRows("SELECT seoUri FROM `" . $DB->pre . "knowledge_center` WHERE status=1");
    $kcPages = $DB->rows;
    foreach ($kcPages as $page) {
        $loc = $siteUrl . '/knowledge-center/' . $page['seoUri'] . '/';
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
        $sitemap .= "    <changefreq>monthly</changefreq>\n";
        $sitemap .= "    <priority>0.7</priority>\n";
        $sitemap .= "  </url>\n";
    }
    $kcCount = $DB->numRows;

    $sitemap .= "</urlset>\n";

    // Write to file
    $path = __DIR__ . "/sitemap.xml";
    file_put_contents($path, $sitemap);

    ob_end_clean();
    echo "Sitemap generated: " . strlen($sitemap) . " bytes\n";
    echo "Motor Products: $motorCount\n";
    echo "Pump Products: $pumpCount\n";
    echo "Motor Categories: $motorCatCount\n";
    echo "Pump Categories: $pumpCatCount\n";
    echo "Knowledge Pages: $kcCount\n";
    echo "Total URLs: " . ($motorCount + $pumpCount + $motorCatCount + $pumpCatCount + $kcCount + 4) . "\n";

} catch (Exception $e) {
    ob_end_clean();
    echo "Error: " . $e->getMessage();
}
?>
