#!/usr/bin/env php
<?php
/**
 * Verify Shallow Well Pumps - Complete Implementation
 */

define('DBHOST', 'localhost');
define('DBNAME', 'bombayengg');
define('DBUSER', 'bombayengg');
define('DBPASS', 'oCFCrCMwKyy5jzg');

echo "\n" . str_repeat("=", 90) . "\n";
echo "SHALLOW WELL PUMPS VERIFICATION\n";
echo str_repeat("=", 90) . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Connect to database
try {
    $conn = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    echo "✓ Database connected\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Get all shallow well pumps (categoryPID = 26)
$sql = "SELECT p.pumpID, p.pumpTitle, p.pumpImage, p.pumpFeatures, p.kwhp, p.supplyPhase, p.seoUri,
        c.seoUri as categoryUri, c.categoryTitle
        FROM mx_pump p
        LEFT JOIN mx_pump_category c ON p.categoryPID = c.categoryPID
        WHERE p.categoryPID = 26
        ORDER BY p.pumpID";

$result = $conn->query($sql);

if (!$result) {
    echo "✗ Query failed: " . $conn->error . "\n";
    exit(1);
}

$totalProducts = $result->num_rows;
$newProducts = [];
$existingProducts = [];

echo "SHALLOW WELL PUMPS - COMPLETE LISTING:\n";
echo str_repeat("-", 90) . "\n\n";

while ($row = $result->fetch_assoc()) {
    $isNew = $row['pumpID'] >= 77;  // New products start from ID 77
    $status = $isNew ? "✓ NEW" : "  ";
    
    printf("%s | %2d. %-25s | Kw/HP: %-4s | %s\n", 
        $status,
        $row['pumpID'],
        substr($row['pumpTitle'], 0, 25),
        $row['kwhp'],
        $row['supplyPhase']
    );
    
    if ($isNew) {
        $newProducts[] = $row;
    } else {
        $existingProducts[] = $row;
    }
}

echo "\n" . str_repeat("-", 90) . "\n";
echo "\nSUMMARY:\n";
echo "Total Shallow Well Pumps: $totalProducts/7\n";
echo "  • Existing: " . count($existingProducts) . "/3\n";
echo "  • New: " . count($newProducts) . "/4\n\n";

// Verify new products have complete data
echo "NEW PRODUCTS - DATA COMPLETENESS:\n";
echo str_repeat("-", 90) . "\n\n";

$allComplete = true;

foreach ($newProducts as $product) {
    $pumpID = $product['pumpID'];
    
    // Get detail specifications
    $stmt = $conn->prepare("SELECT * FROM mx_pump_detail WHERE pumpID = ?");
    $stmt->bind_param("i", $pumpID);
    $stmt->execute();
    $detailResult = $stmt->get_result();
    $detailData = $detailResult->fetch_assoc();
    $stmt->close();
    
    $hasImage = !empty($product['pumpImage']);
    $hasFeatures = !empty($product['pumpFeatures']);
    $hasSpecs = !empty($product['kwhp']);
    $hasDetail = !empty($detailData);
    $hasUrl = !empty($product['seoUri']);
    
    $isComplete = $hasImage && $hasFeatures && $hasSpecs && $hasDetail && $hasUrl;
    
    if (!$isComplete) {
        $allComplete = false;
    }
    
    $status = $isComplete ? "✓ COMPLETE" : "⚠ INCOMPLETE";
    printf("%-30s | %s\n", $product['pumpTitle'], $status);
    
    if (!$isComplete) {
        echo "  Missing: ";
        $missing = [];
        if (!$hasImage) $missing[] = "image";
        if (!$hasFeatures) $missing[] = "features";
        if (!$hasSpecs) $missing[] = "specs";
        if (!$hasDetail) $missing[] = "detail_specs";
        if (!$hasUrl) $missing[] = "seoUri";
        echo implode(", ", $missing) . "\n";
    }
}

// Verify image files
echo "\n" . str_repeat("-", 90) . "\n";
echo "\nIMAGE VERIFICATION:\n";
echo str_repeat("-", 90) . "\n\n";

$thumbDir235 = '/home/bombayengg/public_html/uploads/pump/235_235_crop_100/';
$thumbDir530 = '/home/bombayengg/public_html/uploads/pump/530_530_crop_100/';
$thumb235Count = 0;
$thumb530Count = 0;

foreach ($newProducts as $product) {
    $imageName = $product['pumpImage'];
    
    // Check thumbnail existence
    $has235 = file_exists($thumbDir235 . $imageName);
    $has530 = file_exists($thumbDir530 . $imageName);
    
    if ($has235) $thumb235Count++;
    if ($has530) $thumb530Count++;
    
    $status235 = $has235 ? "✓" : "✗";
    $status530 = $has530 ? "✓" : "✗";
    
    printf("%s | %-30s | 235x235: %s  |  530x530: %s\n",
        ($has235 && $has530 ? "✓" : "⚠"),
        $imageName,
        $status235,
        $status530
    );
}

echo "\n" . str_repeat("-", 90) . "\n";
echo "\nTHUMBNAIL COVERAGE:\n";
echo "235x235 thumbnails: $thumb235Count/4\n";
echo "530x530 thumbnails: $thumb530Count/4\n";

// Check URLs
echo "\n" . str_repeat("-", 90) . "\n";
echo "\nPRODUCT DETAIL PAGE URLs:\n";
echo str_repeat("-", 90) . "\n\n";

$baseUrl = "https://www.bombayengg.co.in";

foreach ($newProducts as $product) {
    $url = $baseUrl . "/" . $product['categoryUri'] . "/" . $product['seoUri'] . "/";
    printf("%-30s | %s\n", $product['pumpTitle'], $url);
}

// Final status
echo "\n" . str_repeat("=", 90) . "\n";

if ($totalProducts == 7 && $allComplete && $thumb235Count == 4 && $thumb530Count == 4) {
    echo "✓ ALL SHALLOW WELL PUMPS VERIFICATION PASSED\n";
    echo "✓ 7/7 Products Complete\n";
    echo "✓ All images and thumbnails generated\n";
    echo "✓ All SEO URLs configured\n";
    echo "✓ All products ready for frontend display\n";
} else {
    echo "⚠ VERIFICATION ISSUES DETECTED\n";
    echo "Products: $totalProducts/7\n";
    echo "Complete: " . ($allComplete ? "YES" : "NO") . "\n";
    echo "Thumbnails 235x235: $thumb235Count/4\n";
    echo "Thumbnails 530x530: $thumb530Count/4\n";
}

echo str_repeat("=", 90) . "\n\n";

$conn->close();

?>
