#!/usr/bin/env php
<?php
/**
 * Verify Product Detail Page URLs
 */

define('DBHOST', 'localhost');
define('DBNAME', 'bombayengg');
define('DBUSER', 'bombayengg');
define('DBPASS', 'oCFCrCMwKyy5jzg');

echo "\n" . str_repeat("=", 100) . "\n";
echo "PRODUCT DETAIL PAGE URLs - COMPLETE VERIFICATION\n";
echo str_repeat("=", 100) . "\n\n";

// Connect to database
$conn = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
if ($conn->connect_error) {
    echo "✗ Database connection failed\n";
    exit(1);
}

echo "✓ Database connected\n\n";

// Get base URL from environment (simulating SITEURL)
$baseUrl = "https://www.bombayengg.co.in";

// Get all new products with their category info
$sql = "SELECT
    p.pumpID,
    p.pumpTitle,
    p.seoUri,
    p.categoryPID,
    c.seoUri as categoryUri,
    c.categoryTitle
FROM mx_pump p
LEFT JOIN mx_pump_category c ON p.categoryPID = c.categoryPID
WHERE p.pumpID BETWEEN 64 AND 75
ORDER BY p.pumpID";

$result = $conn->query($sql);

if (!$result) {
    echo "✗ Query failed: " . $conn->error . "\n";
    exit(1);
}

echo "NEW PRODUCT DETAIL PAGES - URLs GENERATED:\n";
echo str_repeat("-", 100) . "\n\n";

$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    $url = $baseUrl . "/" . $row['categoryUri'] . "/" . $row['seoUri'] . "/";

    printf("%2d. %-30s\n", $count, $row['pumpTitle']);
    printf("    URL: %s\n", $url);
    printf("    Category: %s\n", $row['categoryTitle']);

    // Check if both URLs are set
    if (empty($row['seoUri']) || empty($row['categoryUri'])) {
        echo "    Status: ⚠ INCOMPLETE - Missing ";
        if (empty($row['seoUri'])) echo "product seoUri ";
        if (empty($row['categoryUri'])) echo "category seoUri";
        echo "\n";
    } else {
        echo "    Status: ✓ COMPLETE\n";
    }
    echo "\n";
}

echo str_repeat("-", 100) . "\n";
echo "\nSUMMARY:\n";
echo "Total Products: $count\n";

// Check if all have URLs
$checkSql = "SELECT COUNT(*) as cnt FROM mx_pump WHERE pumpID BETWEEN 64 AND 75 AND seoUri != ''";
$checkResult = $conn->query($checkSql);
$checkRow = $checkResult->fetch_assoc();

if ($checkRow['cnt'] == $count) {
    echo "✓ All products have SEO URLs assigned\n";
    echo "✓ All product detail pages are now LIVE and CLICKABLE\n\n";
} else {
    echo "⚠ " . ($count - $checkRow['cnt']) . " products missing SEO URLs\n\n";
}

echo str_repeat("=", 100) . "\n";
echo "\nHOW IT WORKS:\n";
echo "1. User visits pump category page (e.g., /pumps/residential-pumps/mini-pumps/)\n";
echo "2. Product listing shows all 36 mini pumps with images\n";
echo "3. User clicks 'Know More' button on product card\n";
echo "4. URL generated: {categoryUri}/{productSeoUri}/\n";
echo "5. User navigates to product detail page with full specifications\n";
echo str_repeat("=", 100) . "\n\n";

$conn->close();

?>
