<?php
/*
 * Optimize existing shallow well pump images - Replace black background with white
 */

$uploadDir = '/home/bombayengg/public_html/uploads/pump';
$thumbDir = $uploadDir . '/235_235_crop_100';
$largeDir = $uploadDir . '/530_530_crop_100';

// Shallow well pump images to optimize
$products = array(
    array(
        'id' => 34,
        'filename' => 'pump_34',
        'title' => 'SWJ1'
    ),
    array(
        'id' => 35,
        'filename' => 'pump_35',
        'title' => 'SWJ100AT-36 PLUS'
    ),
    array(
        'id' => 36,
        'filename' => 'pump_36',
        'title' => 'SWJ50AT-30 PLUS'
    ),
);

echo "\n=== SHALLOW WELL PUMP IMAGE OPTIMIZER ===\n";
echo "Converting black backgrounds to white background\n\n";

$successCount = 0;
$failureCount = 0;

foreach ($products as $product) {
    $id = $product['id'];
    $filename = $product['filename'];
    $title = $product['title'];

    echo "Processing: $title (Pump ID: $id)\n";

    // Check if thumbnail exists
    $thumbSource = $thumbDir . '/' . $filename . '.webp';
    if (!file_exists($thumbSource)) {
        echo "  ✗ Thumbnail not found: $thumbSource\n\n";
        $failureCount++;
        continue;
    }

    // Check if large variant exists
    $largeSource = $largeDir . '/' . $filename . '.webp';
    if (!file_exists($largeSource)) {
        echo "  ✗ Large variant not found: $largeSource\n\n";
        $failureCount++;
        continue;
    }

    echo "  ✓ Source files found\n";

    // Create main image (530x530) with white background
    $mainPath = $uploadDir . '/' . $filename . '.webp';

    // Use large variant as source and add white background
    $cmd = "convert " . escapeshellarg($largeSource) . " -background white -flatten -resize 530x530 -background white -gravity center -extent 530x530 -quality 85 -strip " . escapeshellarg($mainPath) . " 2>&1";
    $output = shell_exec($cmd);

    if (file_exists($mainPath) && filesize($mainPath) > 0) {
        echo "  ✓ Main (530x530): " . filesize($mainPath) . " bytes\n";

        // Optimize thumbnail with white background
        $cmd = "convert " . escapeshellarg($mainPath) . " -background white -flatten -resize 235x235 -background white -gravity center -extent 235x235 -quality 80 -strip " . escapeshellarg($thumbSource) . " 2>&1";
        shell_exec($cmd);

        if (file_exists($thumbSource)) {
            echo "  ✓ Thumbnail (235x235): " . filesize($thumbSource) . " bytes\n";
        }

        // Optimize large variant with white background
        $cmd = "convert " . escapeshellarg($mainPath) . " -background white -flatten -quality 85 -strip " . escapeshellarg($largeSource) . " 2>&1";
        shell_exec($cmd);

        if (file_exists($largeSource)) {
            echo "  ✓ Large variant: " . filesize($largeSource) . " bytes\n";
        }

        $successCount++;
    } else {
        echo "  ✗ Main image creation failed\n";
        $failureCount++;
    }

    echo "\n";
}

echo "\n=== SUMMARY ===\n";
echo "Successfully optimized: $successCount\n";
echo "Failed: $failureCount\n";
echo "\nImage optimization complete!\n\n";
?>
