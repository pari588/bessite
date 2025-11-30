<?php
/**
 * Generate Comprehensive Sitemap.xml for Google Search Console
 * Includes all pages and pump products
 */

$DBHOST = 'localhost';
$DBNAME = 'bombayengg';
$DBUSER = 'bombayengg';
$DBPASS = 'oCFCrCMwKyy5jzg';

$conn = mysqli_connect($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

$base_url = 'https://www.bombayengg.net';

// Start XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// 1. Add static pages
$static_pages = array(
    '/' => '2025-11-08',
    '/about-us/' => '2025-11-08',
    '/contact-us/' => '2025-11-08',
    '/knowledge-center/' => '2025-11-08',
);

foreach ($static_pages as $page => $date) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($base_url . $page) . "</loc>\n";
    $xml .= "    <lastmod>$date</lastmod>\n";
    $xml .= "    <changefreq>weekly</changefreq>\n";
    $xml .= "    <priority>0.9</priority>\n";
    $xml .= "  </url>\n";
}

// 2. Get pump categories
$query_pump_categories = "
SELECT DISTINCT categoryPID, categoryTitle, seoUri
FROM mx_pump_category
WHERE status = 1 AND seoUri LIKE 'pump%'
ORDER BY categoryPID";

$result_pump_categories = mysqli_query($conn, $query_pump_categories);

$pump_category_urls = array();
if ($result_pump_categories) {
    while ($cat = mysqli_fetch_assoc($result_pump_categories)) {
        $pump_category_urls[$cat['categoryPID']] = array(
            'seoUri' => $cat['seoUri'],
            'title' => $cat['categoryTitle']
        );
    }
}

// 2b. Get motor categories
$query_motor_categories = "
SELECT DISTINCT categoryMID, categoryTitle, seoUri
FROM mx_motor_category
WHERE status = 1
ORDER BY categoryMID";

$result_motor_categories = mysqli_query($conn, $query_motor_categories);

$motor_category_urls = array();
if ($result_motor_categories) {
    while ($cat = mysqli_fetch_assoc($result_motor_categories)) {
        $motor_category_urls[$cat['categoryMID']] = array(
            'seoUri' => $cat['seoUri'],
            'title' => $cat['categoryTitle']
        );
    }
}

// Add Motor main page
$xml .= "  <url>\n";
$xml .= "    <loc>" . htmlspecialchars($base_url . '/motor/') . "</loc>\n";
$xml .= "    <lastmod>2025-11-08</lastmod>\n";
$xml .= "    <changefreq>weekly</changefreq>\n";
$xml .= "    <priority>0.8</priority>\n";
$xml .= "  </url>\n";

// 3. Add pump category pages
foreach ($pump_category_urls as $cat_id => $cat_data) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($base_url . '/' . $cat_data['seoUri'] . '/') . "</loc>\n";
    $xml .= "    <lastmod>2025-11-08</lastmod>\n";
    $xml .= "    <changefreq>weekly</changefreq>\n";
    $xml .= "    <priority>0.8</priority>\n";
    $xml .= "  </url>\n";
}

// 3b. Add motor category pages
foreach ($motor_category_urls as $cat_id => $cat_data) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($base_url . '/' . $cat_data['seoUri'] . '/') . "</loc>\n";
    $xml .= "    <lastmod>2025-11-08</lastmod>\n";
    $xml .= "    <changefreq>weekly</changefreq>\n";
    $xml .= "    <priority>0.8</priority>\n";
    $xml .= "  </url>\n";
}

// 4. Get all pump products
$query_pumps = "
SELECT p.pumpID, p.pumpTitle, p.seoUri, p.categoryPID, c.seoUri as cat_seoUri
FROM mx_pump p
LEFT JOIN mx_pump_category c ON p.categoryPID = c.categoryPID
WHERE p.status = 1
ORDER BY p.pumpTitle";

$result_pumps = mysqli_query($conn, $query_pumps);

$pump_count = 0;
if ($result_pumps) {
    while ($pump = mysqli_fetch_assoc($result_pumps)) {
        $pump_url = $base_url . '/' . $pump['cat_seoUri'] . '/' . $pump['seoUri'] . '/';

        $xml .= "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($pump_url) . "</loc>\n";
        $xml .= "    <lastmod>2025-11-08</lastmod>\n";
        $xml .= "    <changefreq>monthly</changefreq>\n";
        $xml .= "    <priority>0.7</priority>\n";
        $xml .= "  </url>\n";

        $pump_count++;
    }
}

// 4b. Get all motor products
$query_motors = "
SELECT m.motorID, m.motorTitle, m.seoUri, m.categoryMID, c.seoUri as cat_seoUri
FROM mx_motor m
LEFT JOIN mx_motor_category c ON m.categoryMID = c.categoryMID
WHERE m.status = 1
ORDER BY m.motorTitle";

$result_motors = mysqli_query($conn, $query_motors);

$motor_count = 0;
if ($result_motors) {
    while ($motor = mysqli_fetch_assoc($result_motors)) {
        $motor_url = $base_url . '/' . $motor['cat_seoUri'] . '/' . $motor['seoUri'] . '/';

        $xml .= "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($motor_url) . "</loc>\n";
        $xml .= "    <lastmod>2025-11-08</lastmod>\n";
        $xml .= "    <changefreq>monthly</changefreq>\n";
        $xml .= "    <priority>0.7</priority>\n";
        $xml .= "  </url>\n";

        $motor_count++;
    }
}

// Close XML
$xml .= '</urlset>' . "\n";

// Save sitemap.xml
$sitemap_file = '/home/bombayengg/public_html/sitemap.xml';
file_put_contents($sitemap_file, $xml);

// Also create sitemap index for better organization
$sitemap_index = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$sitemap_index .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
$sitemap_index .= "  <sitemap>\n";
$sitemap_index .= "    <loc>https://www.bombayengg.net/sitemap.xml</loc>\n";
$sitemap_index .= "    <lastmod>2025-11-08</lastmod>\n";
$sitemap_index .= "  </sitemap>\n";
$sitemap_index .= '</sitemapindex>' . "\n";

$sitemap_index_file = '/home/bombayengg/public_html/sitemap_index.xml';
file_put_contents($sitemap_index_file, $sitemap_index);

// Get file size
$sitemap_size = filesize($sitemap_file);
$line_count = count(file($sitemap_file));

echo "Sitemap Generation Complete!\n";
echo "=====================================\n";
echo "Sitemap file: sitemap.xml\n";
echo "Location: https://www.bombayengg.net/sitemap.xml\n\n";
echo "Statistics:\n";
echo "  Static Pages: " . count($static_pages) . "\n";
echo "  Pump Categories: " . count($pump_category_urls) . "\n";
echo "  Pump Products: " . $pump_count . "\n";
echo "  Motor Categories: " . count($motor_category_urls) . "\n";
echo "  Motor Products: " . $motor_count . "\n";
echo "  Total URLs: " . ($pump_count + $motor_count + count($pump_category_urls) + count($motor_category_urls) + count($static_pages) + 1) . "\n\n";
echo "File Size: " . number_format($sitemap_size / 1024, 2) . " KB\n";
echo "File Lines: " . $line_count . "\n";
echo "\nSitemap Index: sitemap_index.xml\n";

mysqli_close($conn);
?>
