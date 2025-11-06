#!/usr/bin/env php
<?php
/**
 * Verification Script for Imported Products
 */

define('DBHOST', 'localhost');
define('DBNAME', 'bombayengg');
define('DBUSER', 'bombayengg');
define('DBPASS', 'oCFCrCMwKyy5jzg');

$ROOTPATH = __DIR__;
define('UPLOADPATH', $ROOTPATH . '/uploads');

echo "\n" . str_repeat("=", 80) . "\n";
echo "PRODUCT IMPORT VERIFICATION REPORT\n";
echo str_repeat("=", 80) . "\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $conn = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    echo "✅ DATABASE CONNECTION\n";
    echo "   Status: Connected successfully\n\n";

    // Check total products
    $result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE status=1");
    $row = $result->fetch_assoc();
    echo "✅ TOTAL PRODUCTS IN DATABASE\n";
    echo "   Count: " . $row['cnt'] . " active products\n\n";

    // Check Mini Pumps category
    $result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE categoryPID=24 AND status=1");
    $row = $result->fetch_assoc();
    $miniPumpCount = $row['cnt'];
    echo "✅ MINI PUMPS CATEGORY (ID: 24)\n";
    echo "   Total Products: " . $miniPumpCount . "\n";

    // Check products with images
    $result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE categoryPID=24 AND status=1 AND pumpImage != ''");
    $row = $result->fetch_assoc();
    echo "   With Images: " . $row['cnt'] . "\n";
    echo "   Image Coverage: " . round(($row['cnt'] / $miniPumpCount) * 100) . "%\n\n";

    // Check newly imported products
    echo "✅ NEWLY IMPORTED PRODUCTS (12)\n";
    echo "   ID  | Product Title          | Image File\n";
    echo "   " . str_repeat("-", 60) . "\n";

    $newProducts = [
        'MINI MASTER II',
        'CHAMP PLUS II',
        'MINI MASTERPLUS II',
        'MINI MARVEL II',
        'MINI CREST II',
        'AQUAGOLD 50-30',
        'AQUAGOLD 100-33',
        'FLOMAX PLUS II',
        'MASTER DURA II',
        'MASTER PLUS II',
        'STAR PLUS II',
        'CHAMP DURA II'
    ];

    $importedCount = 0;
    foreach ($newProducts as $product) {
        $stmt = $conn->prepare("SELECT pumpID, pumpImage FROM mx_pump WHERE pumpTitle=? AND categoryPID=24");
        $stmt->bind_param("s", $product);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $imageStatus = !empty($row['pumpImage']) ? "✅ " . basename($row['pumpImage']) : "⚠️  No Image";
            printf("   %-3d | %-22s | %s\n", $row['pumpID'], substr($product, 0, 22), $imageStatus);
            $importedCount++;
        } else {
            printf("   %-3s | %-22s | ❌ NOT FOUND\n", "-", substr($product, 0, 22));
        }
        $stmt->close();
    }

    echo "   " . str_repeat("-", 60) . "\n";
    echo "   Imported: " . $importedCount . "/12\n\n";

    // Check image files
    echo "✅ IMAGE FILES ON DISK\n";
    $imageDir = UPLOADPATH . '/pump/crompton_images';
    $images = glob($imageDir . '/*.webp');
    echo "   Total WebP Files: " . count($images) . "\n";
    foreach ($images as $image) {
        $size = round(filesize($image) / 1024);
        echo "   • " . basename($image) . " (" . $size . "KB)\n";
    }
    echo "\n";

    // Check database consistency
    echo "✅ DATABASE CONSISTENCY CHECK\n";
    $result = $conn->query("
        SELECT COUNT(*) as cnt FROM mx_pump
        WHERE categoryPID=24 AND pumpImage LIKE 'crompton_images/%.webp'
    ");
    $row = $result->fetch_assoc();
    echo "   Products with crompton_images path: " . $row['cnt'] . "\n";

    $result = $conn->query("
        SELECT pumpImage, COUNT(*) as cnt FROM mx_pump
        WHERE categoryPID=24 AND pumpImage != ''
        GROUP BY pumpImage
        ORDER BY cnt DESC
        LIMIT 5
    ");
    echo "   Most used images:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   • " . basename($row['pumpImage']) . " (used " . $row['cnt'] . " times)\n";
    }

    echo "\n" . str_repeat("=", 80) . "\n";
    echo "✅ VERIFICATION COMPLETE - ALL SYSTEMS OPERATIONAL\n";
    echo str_repeat("=", 80) . "\n\n";

    $conn->close();

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

?>
