<?php
/*
 * Download Crompton Missing Shallow Well Pump images and convert to WebP
 */

$uploadDir = '/home/bombayengg/public_html/uploads/pump';
$tempDir = $uploadDir . '/crompton_images_temp';

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Missing shallow well pump image URLs from Crompton CDN
$products = array(
    // SWJ100AP-36 PLUS - 1 HP
    array(
        'filename' => 'swj100ap-36-plus',
        'title' => 'SWJ100AP-36 PLUS (1 HP Shallow Well)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/SWJPLUSPL1_a7ff07a8-c288-4f10-98f9-26fd9c7b74a0.png?v=1730290468'
    ),
    // SWJ100A-36 PLUS - 1 HP
    array(
        'filename' => 'swj100a-36-plus',
        'title' => 'SWJ100A-36 PLUS (1 HP Shallow Well)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/SWJPLUSPL1_a7ff07a8-c288-4f10-98f9-26fd9c7b74a0.png?v=1730290468'
    ),
    // SWJ50AP-30 PLUS - 0.5 HP
    array(
        'filename' => 'swj50ap-30-plus',
        'title' => 'SWJ50AP-30 PLUS (0.5 HP Shallow Well)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/SWJPLUSPL1_a7ff07a8-c288-4f10-98f9-26fd9c7b74a0.png?v=1730290468'
    ),
    // SWJ50A-30 PLUS - 0.5 HP
    array(
        'filename' => 'swj50a-30-plus',
        'title' => 'SWJ50A-30 PLUS (0.5 HP Shallow Well)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/SWJPLUSPL1_a7ff07a8-c288-4f10-98f9-26fd9c7b74a0.png?v=1730290468'
    ),
);

echo "\n=== CROMPTON MISSING SHALLOW WELL PUMP IMAGE DOWNLOADER ===\n\n";

$successCount = 0;
$failureCount = 0;

foreach ($products as $product) {
    $filename = $product['filename'];
    $title = $product['title'];
    $url = $product['url'];

    echo "Processing: $title\n";

    $tempFile = $tempDir . '/' . basename($filename) . '_temp.png';

    // Download with curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200 || empty($imageData)) {
        echo "  ✗ Download failed (HTTP $httpCode)\n\n";
        $failureCount++;
        continue;
    }

    file_put_contents($tempFile, $imageData);

    // Verify it's a valid image
    $size = getimagesize($tempFile);
    if ($size === false) {
        unlink($tempFile);
        echo "  ✗ Invalid image format\n\n";
        $failureCount++;
        continue;
    }

    echo "  ✓ Downloaded (" . filesize($tempFile) . " bytes, " . $size[0] . "x" . $size[1] . ")\n";

    // Convert to WebP
    echo "  Converting to WebP...\n";

    $mainPath = $uploadDir . '/' . $filename . '.webp';
    $thumbDir_path = $uploadDir . '/235_235_crop_100';
    $largeDir_path = $uploadDir . '/530_530_crop_100';

    // Main image - resize to 530x530
    $cmd = "convert " . escapeshellarg($tempFile) . " -resize 530x530 -background white -gravity center -extent 530x530 -quality 85 -strip " . escapeshellarg($mainPath) . " 2>&1";
    $output = shell_exec($cmd);

    if (file_exists($mainPath) && filesize($mainPath) > 0) {
        echo "    ✓ Main: " . filesize($mainPath) . " bytes\n";

        // Thumbnail
        $thumbPath = $thumbDir_path . '/' . $filename . '.webp';
        $cmd = "convert " . escapeshellarg($mainPath) . " -resize 235x235 -background white -gravity center -extent 235x235 -quality 80 -strip " . escapeshellarg($thumbPath) . " 2>&1";
        shell_exec($cmd);

        if (file_exists($thumbPath)) {
            echo "    ✓ Thumbnail: " . filesize($thumbPath) . " bytes\n";
        }

        // Large variant
        $largePath = $largeDir_path . '/' . $filename . '.webp';
        $cmd = "convert " . escapeshellarg($mainPath) . " -quality 85 -strip " . escapeshellarg($largePath) . " 2>&1";
        shell_exec($cmd);

        if (file_exists($largePath)) {
            echo "    ✓ Large variant: " . filesize($largePath) . " bytes\n";
        }

        $successCount++;
    } else {
        echo "    ✗ Conversion failed\n";
        $failureCount++;
    }

    // Clean up temp file
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }

    echo "\n";
}

// Cleanup temp directory
@rmdir($tempDir);

echo "\n=== SUMMARY ===\n";
echo "Successfully processed: $successCount\n";
echo "Failed: $failureCount\n";
echo "\nImage processing complete!\n\n";
?>
