<?php
// Update sitemap with knowledge center articles
require_once('core/core.inc.php');

$siteUrl = "https://www.bombayengg.net";
$today = date('Y-m-d');

$xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xmlContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages
$staticPages = [
    ['url' => '', 'priority' => '0.9'],
    ['url' => 'about-us/', 'priority' => '0.8'],
    ['url' => 'contact-us/', 'priority' => '0.8'],
    ['url' => 'knowledge-center/', 'priority' => '0.8'],
];

foreach ($staticPages as $page) {
    $xmlContent .= "  <url>\n";
    $xmlContent .= "    <loc>" . $siteUrl . "/" . $page['url'] . "</loc>\n";
    $xmlContent .= "    <lastmod>" . $today . "</lastmod>\n";
    $xmlContent .= "    <changefreq>weekly</changefreq>\n";
    $xmlContent .= "    <priority>" . $page['priority'] . "</priority>\n";
    $xmlContent .= "  </url>\n";
}

// Get knowledge center articles
$sql = "SELECT seoUri FROM mx_knowledge_center WHERE status=1 ORDER BY knowledgeCenterID DESC";
$result = $MXDB->query($sql);

$kcCount = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $kcCount++;
        $xmlContent .= "  <url>\n";
        $xmlContent .= "    <loc>" . $siteUrl . "/knowledge-center/" . $row['seoUri'] . "/</loc>\n";
        $xmlContent .= "    <lastmod>" . $today . "</lastmod>\n";
        $xmlContent .= "    <changefreq>monthly</changefreq>\n";
        $xmlContent .= "    <priority>0.7</priority>\n";
        $xmlContent .= "  </url>\n";
    }
}

$xmlContent .= "</urlset>\n";

// Write to file
$filepath = 'xsite/sitemap.xml';
$written = file_put_contents($filepath, $xmlContent);

if ($written) {
    echo "✓ Sitemap updated successfully!\n";
    echo "Knowledge Center Articles: $kcCount\n";
    echo "File size: " . $written . " bytes\n";
    echo "File: $filepath\n";
} else {
    echo "✗ Failed to write sitemap file\n";
}
?>
