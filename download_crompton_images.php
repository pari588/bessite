<?php
/*
 * Download Crompton product images and convert to WebP
 * Requires: ImageMagick (convert command)
 */

$uploadDir = '/home/bombayengg/public_html/uploads/pump';
$tempDir = $uploadDir . '/crompton_images_temp';

// Create temp directory
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Product image mappings - Crompton CDN URLs
$products = array(
    // 4-Inch Borewell - Water-filled
    array(
        'filename' => '4w12bf1-5e',
        'title' => '4W12BF1.5E',
        'urls' => array(
            'https://www.crompton.co.in/cdn/shop/files/4W12BF1.5E.png',
            'https://www.crompton.co.in/cdn/shop/files/4w12bf1-5e.png',
        )
    ),
    array(
        'filename' => '4w14bf1-5e',
        'title' => '4W14BF1.5E',
        'urls' => array(
            'https://www.crompton.co.in/cdn/shop/files/4W14BF1.5E.png',
            'https://www.crompton.co.in/cdn/shop/files/4w14bf1-5e.png',
        )
    ),

    // 4-Inch Borewell - Oil-filled
    array(
        'filename' => '4vo1-7-bue-u4s',
        'title' => '4VO1/7-BUE(U4S)',
        'urls' => array(
            'https://www.crompton.co.in/cdn/shop/files/4VO1-7-BUE.png',
            'https://www.crompton.co.in/cdn/shop/files/4vo1-7-bue.png',
        )
    ),
    array(
        'filename' => '4vo1-10-bue-u4s',
        'title' => '4VO1/10-BUE(U4S)',
        'urls' => array(
            'https://www.crompton.co.in/cdn/shop/files/4VO1-10-BUE.png',
            'https://www.crompton.co.in/cdn/shop/files/4vo1-10-bue.png',
        )
    ),
    array(
        'filename' => '4vo7bu1eu',
        'title' => '4VO7BU1EU',
        'urls' => array(
            'https://www.crompton.co.in/cdn/shop/files/4VO7BU1EU.png',
            'https://www.crompton.co.in/cdn/shop/files/4vo7bu1eu.png',
        )
    ),
    array(
        'filename' => '4vo10bu1eu',
        'title' => '4VO10BU1EU',
        'urls' => array(
            'https://www.crompton.co.in/cdn/shop/files/4VO10BU1EU.png',
            'https://www.crompton.co.in/cdn/shop/files/4vo10bu1eu.png',
        )
    ),
    array(
        'filename' => '4vo1-5-12-bue-u4s',
        'title' => '4VO1.5/12-BUE(U4S)',
        'urls' => array(
            'https://www.crompton.co.in/cdn/shop/files/4VO1.5-12-BUE.png',
            'https://www.crompton.co.in/cdn/shop/files/4vo1.5-12-bue.png',
        )
    ),
    array(
        'filename' => '4vo1-5-14-bue-u4s',
        'title' => '4VO1.5/14-BUE(U4S)',
        'urls' => array(
            'https://www.crompton.co.in/cdn/shop/files/4VO1.5-14-BUE.png',
            'https://www.crompton.co.in/cdn/shop/files/4vo1.5-14-bue.png',
        )
    ),

    // Booster Pumps
    array(
        'filename' => 'cfmsmb3d0-50-v24',
        'title' => 'CFMSMB3D0.50-V24',
        'urls' => array(
            'https://www.crompton.co.in/cdn/shop/files/CFMSMB3D0.50-V24.png',
            'https://www.crompton.co.in/cdn/shop/files/cfmsmb3d0.50-v24.png',
        )
    ),
    array(
        'filename' => 'mini-force-ii',
        'title' => 'MINI FORCE II',
        'urls' => array(
            'https://www.crompton.co.in/cdn/shop/files/MINI-FORCE-II.png',
            'https://www.crompton.co.in/cdn/shop/files/mini-force-ii.png',
        )
    ),
);

echo "\n=== CROMPTON PRODUCT IMAGE DOWNLOADER ===\n\n";

$successCount = 0;
$failureCount = 0;
$placeholderCount = 0;

foreach ($products as $product) {
    $filename = $product['filename'];
    $title = $product['title'];
    $urls = $product['urls'];

    echo "Processing: $title\n";

    $downloaded = false;
    $downloadedFile = null;

    // Try to download from multiple URLs
    foreach ($urls as $url) {
        $tempFile = $tempDir . '/' . basename($filename) . '_temp.png';

        echo "  Trying: $url\n";

        // Download image with curl
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

        if ($httpCode == 200 && !empty($imageData)) {
            file_put_contents($tempFile, $imageData);

            // Verify it's a valid image
            $size = getimagesize($tempFile);
            if ($size !== false) {
                echo "    ✓ Downloaded successfully\n";
                $downloaded = true;
                $downloadedFile = $tempFile;
                break;
            } else {
                unlink($tempFile);
                echo "    ✗ Invalid image format\n";
            }
        } else {
            echo "    ✗ Download failed (HTTP $httpCode)\n";
        }
    }

    if (!$downloaded) {
        echo "  ⚠ Could not download real image, keeping placeholder\n";
        $failureCount++;
        $placeholderCount++;
        echo "\n";
        continue;
    }

    // Convert to WebP
    echo "  Converting to WebP...\n";

    $mainPath = $uploadDir . '/' . $filename . '.webp';
    $thumbDir_path = $uploadDir . '/235_235_crop_100';
    $largeDir_path = $uploadDir . '/530_530_crop_100';

    // Main image
    $cmd = "convert " . escapeshellarg($downloadedFile) . " -resize 530x530 -background white -gravity center -extent 530x530 -quality 85 " . escapeshellarg($mainPath) . " 2>&1";
    $output = shell_exec($cmd);

    if (file_exists($mainPath) && filesize($mainPath) > 0) {
        echo "    ✓ Main image created (" . filesize($mainPath) . " bytes)\n";

        // Thumbnail
        $thumbPath = $thumbDir_path . '/' . $filename . '.webp';
        $cmd = "convert " . escapeshellarg($mainPath) . " -resize 235x235 -background white -gravity center -extent 235x235 -quality 80 " . escapeshellarg($thumbPath) . " 2>&1";
        shell_exec($cmd);

        if (file_exists($thumbPath)) {
            echo "    ✓ Thumbnail created (" . filesize($thumbPath) . " bytes)\n";
        }

        // Large variant
        $largePath = $largeDir_path . '/' . $filename . '.webp';
        $cmd = "convert " . escapeshellarg($mainPath) . " -quality 85 " . escapeshellarg($largePath) . " 2>&1";
        shell_exec($cmd);

        if (file_exists($largePath)) {
            echo "    ✓ Large variant created (" . filesize($largePath) . " bytes)\n";
        }

        $successCount++;
    } else {
        echo "    ✗ Conversion failed. Output: $output\n";
        $failureCount++;
    }

    // Clean up temp file
    if (file_exists($downloadedFile)) {
        unlink($downloadedFile);
    }

    echo "\n";
}

// Cleanup temp directory
@rmdir($tempDir);

echo "\n=== SUMMARY ===\n";
echo "Successfully processed: $successCount\n";
echo "Download failures (using placeholders): $failureCount\n";
echo "Placeholders retained: $placeholderCount\n";
echo "\nImage processing complete!\n\n";
?>
