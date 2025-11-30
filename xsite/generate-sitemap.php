<?php
// Database credentials
$host = 'localhost';
$user = 'bombayengg';
$pass = 'oCFCrCMwKyy5jzg';
$db = 'bombayengg';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$siteUrl = "https://www.bombayengg.net";
$today = date('Y-m-d');
$sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages
$staticPages = array(
    array('url' => '', 'priority' => '0.9'),
    array('url' => 'about-us/', 'priority' => '0.8'),
    array('url' => 'contact-us/', 'priority' => '0.8'),
    array('url' => 'knowledge-center/', 'priority' => '0.8'),
);

foreach ($staticPages as $page) {
    $sitemap .= "  <url>\n";
    $sitemap .= "    <loc>" . $siteUrl . "/" . $page['url'] . "</loc>\n";
    $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
    $sitemap .= "    <changefreq>weekly</changefreq>\n";
    $sitemap .= "    <priority>" . $page['priority'] . "</priority>\n";
    $sitemap .= "  </url>\n";
}

// Motor categories
$result = $conn->query("SELECT seoUri FROM mx_motor_category WHERE status=1");
while ($row = $result->fetch_assoc()) {
    $sitemap .= "  <url>\n";
    $sitemap .= "    <loc>" . $siteUrl . "/" . $row['seoUri'] . "/</loc>\n";
    $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
    $sitemap .= "    <changefreq>weekly</changefreq>\n";
    $sitemap .= "    <priority>0.8</priority>\n";
    $sitemap .= "  </url>\n";
}

// Pump categories
$result = $conn->query("SELECT seoUri FROM mx_pump_category WHERE status=1");
while ($row = $result->fetch_assoc()) {
    $sitemap .= "  <url>\n";
    $sitemap .= "    <loc>" . $siteUrl . "/" . $row['seoUri'] . "/</loc>\n";
    $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
    $sitemap .= "    <changefreq>weekly</changefreq>\n";
    $sitemap .= "    <priority>0.8</priority>\n";
    $sitemap .= "  </url>\n";
}

// Motor products
$result = $conn->query("SELECT m.seoUri, c.seoUri as catSeoUri FROM mx_motor m LEFT JOIN mx_motor_category c ON c.categoryMID = m.categoryMID WHERE m.status=1");
$motorCount = $result->num_rows;
while ($row = $result->fetch_assoc()) {
    $url = $row['catSeoUri'] ? $row['catSeoUri'] . '/' . $row['seoUri'] : 'motor/' . $row['seoUri'];
    $sitemap .= "  <url>\n";
    $sitemap .= "    <loc>" . $siteUrl . "/" . $url . "/</loc>\n";
    $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
    $sitemap .= "    <changefreq>monthly</changefreq>\n";
    $sitemap .= "    <priority>0.7</priority>\n";
    $sitemap .= "  </url>\n";
}

// Pump products
$result = $conn->query("SELECT p.seoUri, c.seoUri as catSeoUri FROM mx_pump p LEFT JOIN mx_pump_category c ON c.categoryPID = p.categoryPID WHERE p.status=1");
$pumpCount = $result->num_rows;
while ($row = $result->fetch_assoc()) {
    $url = $row['catSeoUri'] ? $row['catSeoUri'] . '/' . $row['seoUri'] : 'pump/' . $row['seoUri'];
    $sitemap .= "  <url>\n";
    $sitemap .= "    <loc>" . $siteUrl . "/" . $url . "/</loc>\n";
    $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
    $sitemap .= "    <changefreq>monthly</changefreq>\n";
    $sitemap .= "    <priority>0.7</priority>\n";
    $sitemap .= "  </url>\n";
}

// Knowledge center pages
$result = $conn->query("SELECT seoUri FROM mx_knowledge_center WHERE status=1");
while ($row = $result->fetch_assoc()) {
    $sitemap .= "  <url>\n";
    $sitemap .= "    <loc>" . $siteUrl . "/knowledge-center/" . $row['seoUri'] . "/</loc>\n";
    $sitemap .= "    <lastmod>" . $today . "</lastmod>\n";
    $sitemap .= "    <changefreq>monthly</changefreq>\n";
    $sitemap .= "    <priority>0.7</priority>\n";
    $sitemap .= "  </url>\n";
}

$sitemap .= "</urlset>\n";

// Write to file
file_put_contents("/home/bombayengg/public_html/xsite/sitemap.xml", $sitemap);

echo "✓ Sitemap generated successfully!\n";
echo "  Motor Products: $motorCount\n";
echo "  Pump Products: $pumpCount\n";
echo "  File: /home/bombayengg/public_html/xsite/sitemap.xml\n";

$conn->close();
?>
