<?php
/*
 * Download Crompton product images and convert to WebP
 * Using actual CDN URLs extracted from Crompton website
 */

$uploadDir = '/home/bombayengg/public_html/uploads/pump';
$tempDir = $uploadDir . '/crompton_images_temp';

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Actual product image URLs from Crompton CDN
$products = array(
    // 4-Inch Borewell - Water-filled (all use same image)
    array(
        'filename' => '4w12bf1-5e',
        'title' => '4W12BF1.5E (Water-filled 1.5 HP)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/4w-png.png?v=1729769845'
    ),
    array(
        'filename' => '4w14bf1-5e',
        'title' => '4W14BF1.5E (Water-filled 1.5 HP)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/4w-png.png?v=1729769845'
    ),

    // 4-Inch Borewell - Oil-filled (all use same image)
    array(
        'filename' => '4vo1-7-bue-u4s',
        'title' => '4VO1/7-BUE(U4S) (Oil-filled 1 HP)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/artboard1_277b9f24-ac06-4c37-835f-ff388004917b.png?v=1730186857'
    ),
    array(
        'filename' => '4vo1-10-bue-u4s',
        'title' => '4VO1/10-BUE(U4S) (Oil-filled 1 HP)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/artboard1_277b9f24-ac06-4c37-835f-ff388004917b.png?v=1730186857'
    ),
    array(
        'filename' => '4vo7bu1eu',
        'title' => '4VO7BU1EU (Oil-filled 1 HP)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/artboard1_277b9f24-ac06-4c37-835f-ff388004917b.png?v=1730186857'
    ),
    array(
        'filename' => '4vo10bu1eu',
        'title' => '4VO10BU1EU (Oil-filled 1 HP)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/artboard1_277b9f24-ac06-4c37-835f-ff388004917b.png?v=1730186857'
    ),
    array(
        'filename' => '4vo1-5-12-bue-u4s',
        'title' => '4VO1.5/12-BUE(U4S) (Oil-filled 1.5 HP)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/artboard1_277b9f24-ac06-4c37-835f-ff388004917b.png?v=1730186857'
    ),
    array(
        'filename' => '4vo1-5-14-bue-u4s',
        'title' => '4VO1.5/14-BUE(U4S) (Oil-filled 1.5 HP)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/artboard1_277b9f24-ac06-4c37-835f-ff388004917b.png?v=1730186857'
    ),
);

echo "\n=== CROMPTON PRODUCT IMAGE DOWNLOADER V2 ===\n\n";

$successCount = 0;
$failureCount = 0;

foreach ($products as $product) {
    $filename = $product['filename'];
    $title = $product['title'];
    $url = $product['url'];

    echo "Processing: $title\n";
    echo "  URL: $url\n";

    $tempFile = $tempDir . '/' . basename($filename) . '_temp.png';

    // Download with curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

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
