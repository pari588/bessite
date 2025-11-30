<?php
// Complete sitemap generator with all knowledge center articles
require_once('core/core.inc.php');

$siteUrl = "https://www.bombayengg.net";
$today = date('Y-m-d');

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages
$staticPages = [
    ['url' => '', 'priority' => '0.9'],
    ['url' => 'about-us/', 'priority' => '0.8'],
    ['url' => 'contact-us/', 'priority' => '0.8'],
    ['url' => 'knowledge-center/', 'priority' => '0.8'],
];

foreach ($staticPages as $page) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . $siteUrl . "/" . $page['url'] . "</loc>\n";
    $xml .= "    <lastmod>" . $today . "</lastmod>\n";
    $xml .= "    <changefreq>weekly</changefreq>\n";
    $xml .= "    <priority>" . $page['priority'] . "</priority>\n";
    $xml .= "  </url>\n";
}

// Get knowledge center articles - ALL 15 articles
$articles = [
    'use-of-vfd-with-flame-proof-motors-implications-and-mitigation-1',
    'hp-to-kw-conversion-made-easy-a-practical-guide-for-motor-users-1',
    'hazardous-area-motors-a-deep-dive-into-gas-groups-iia-iib',
    'motor-efficiency-classes-ie1-to-ie4-explained-1',
    'how-to-choose-the-best-crompton-pump-for-your-home-0-5-1-hp-1',
    'how-to-read-a-motor-nameplate',
    'motor-cooling-methods-ic-codes-explained-1',
    'common-motor-failures-and-how-to-prevent-them',
    'choosing-the-right-motor-for-pump-applications-1',
    'bearing-types-in-electric-motors-selection-maintenance-1',
    'energy-savings-by-upgrading-to-ie3ie4-motors',
    'insulation-classes-b-f-h-in-motors-explained',
    'understanding-motor-duty-cycles-s1-s9',
    'synchronous-vs-induction-motors-key-differences-explained',
    'motor-mounting-types-explained-b3-b5-b14-v1-etc',
];

foreach ($articles as $seoUri) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . $siteUrl . "/knowledge-center/" . $seoUri . "/</loc>\n";
    $xml .= "    <lastmod>" . $today . "</lastmod>\n";
    $xml .= "    <changefreq>monthly</changefreq>\n";
    $xml .= "    <priority>0.7</priority>\n";
    $xml .= "  </url>\n";
}

$xml .= "</urlset>\n";

// Write to file
$filepath = 'xsite/sitemap.xml';
$written = file_put_contents($filepath, $xml);

if ($written) {
    echo "✓ Sitemap updated successfully!\n";
    echo "Knowledge Center Articles: " . count($articles) . "\n";
    echo "Static Pages: 4\n";
    echo "Total URLs: " . (count($articles) + 4) . "\n";
    echo "File size: " . $written . " bytes\n";
    echo "File: $filepath\n";
} else {
    echo "✗ Failed to write sitemap file\n";
}
?>
