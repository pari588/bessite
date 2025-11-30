<?php
/**
 * SWJ Shallow Well Pump Image Processor - FIXED VERSION
 * - Fetches original images from Crompton
 * - Preserves transparency (no flattening!)
 * - Resizes to required dimensions
 * - Converts to WebP format with transparency
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define paths
$uploadDir = __DIR__ . '/uploads/pump';
$imageMagickBin = '/usr/bin/convert'; // ImageMagick convert binary

// Product mapping
$products = [
    [
        'pumpID' => 34,
        'title' => 'SWJ1',
        'seoUri' => 'swj1',
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/png_5f397647-de7d-467d-a322-ef766ac2e551.png?v=1730289678',
        'filename' => 'swj1'
    ],
    [
        'pumpID' => 35,
        'title' => 'SWJ100AT-36 PLUS',
        'seoUri' => 'swj100at-36-plus',
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/SWJPLUSPL1.png?v=1730203688',
        'filename' => 'swj100at-36-plus'
    ],
    [
        'pumpID' => 36,
        'title' => 'SWJ50AT-30 PLUS',
        'seoUri' => 'swj50at-30-plus',
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/SWJPLUSPL1.png?v=1730203688',
        'filename' => 'swj50at-30-plus'
    ]
];

echo "=== SWJ IMAGE PROCESSING SCRIPT V2 (WITH TRANSPARENCY) ===\n\n";

foreach ($products as $product) {
    echo "Processing: {$product['title']} (ID: {$product['pumpID']})\n";
    echo "-------------------------------------------\n";

    // Step 1: Download image
    echo "1. Downloading image from Crompton...\n";
    $tempFile = tempnam(sys_get_temp_dir(), 'swj_');

    $ch = curl_init($product['imageUrl']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $imageData) {
        file_put_contents($tempFile, $imageData);
        echo "   ✓ Image downloaded successfully (" . filesize($tempFile) . " bytes)\n";
    } else {
        echo "   ✗ Failed to download image (HTTP: $httpCode)\n";
        unlink($tempFile);
        continue;
    }

    // Step 2: Identify image type
    echo "2. Identifying image format...\n";
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tempFile);
    finfo_close($finfo);
    echo "   Detected MIME: $mimeType\n";

    // Step 3: Resize to 530x530 for large image (preserving transparency)
    echo "3. Resizing to 530x530 and converting to WebP (preserving transparency)...\n";

    $mainWebp = $uploadDir . '/530_530_crop_100/' . $product['filename'] . '.webp';

    // ImageMagick command to resize and convert to WebP WITHOUT flattening
    $cmd = "$imageMagickBin '$tempFile' " .
           "-resize 530x530 " .
           "-background none " .
           "-gravity center " .
           "-extent 530x530 " .
           "-quality 85 " .
           "$mainWebp 2>&1";

    $output = shell_exec($cmd);

    if (file_exists($mainWebp)) {
        $webpSize = filesize($mainWebp);
        echo "   ✓ Created 530x530 WebP with transparency ($webpSize bytes)\n";
    } else {
        echo "   ✗ Failed to create main WebP\n";
        echo "   Error: $output\n";
        unlink($tempFile);
        continue;
    }

    // Step 4: Resize to 235x235 for thumbnail (preserving transparency)
    echo "4. Resizing to 235x235 and converting to WebP (preserving transparency)...\n";

    $thumbWebp = $uploadDir . '/235_235_crop_100/' . $product['filename'] . '.webp';

    $cmd = "$imageMagickBin '$tempFile' " .
           "-resize 235x235 " .
           "-background none " .
           "-gravity center " .
           "-extent 235x235 " .
           "-quality 85 " .
           "$thumbWebp 2>&1";

    $output = shell_exec($cmd);

    if (file_exists($thumbWebp)) {
        $thumbSize = filesize($thumbWebp);
        echo "   ✓ Created 235x235 WebP with transparency ($thumbSize bytes)\n";
    } else {
        echo "   ✗ Failed to create thumbnail WebP\n";
        echo "   Error: $output\n";
        unlink($tempFile);
        unlink($mainWebp);
        continue;
    }

    // Step 5: Create base WebP (without size prefix)
    echo "5. Creating base WebP image...\n";

    $baseWebp = $uploadDir . '/' . $product['filename'] . '.webp';

    $cmd = "$imageMagickBin '$tempFile' " .
           "-resize 530x530 " .
           "-background none " .
           "-gravity center " .
           "-extent 530x530 " .
           "-quality 85 " .
           "$baseWebp 2>&1";

    $output = shell_exec($cmd);

    if (file_exists($baseWebp)) {
        $baseSize = filesize($baseWebp);
        echo "   ✓ Created base WebP with transparency ($baseSize bytes)\n";
    } else {
        echo "   ✗ Failed to create base WebP\n";
    }

    // Cleanup
    unlink($tempFile);

    echo "✓ Completed: {$product['title']}\n\n";
}

echo "\n=== PROCESSING COMPLETE ===\n";
echo "Images have been fetched, resized, and converted with transparency preserved.\n";
echo "All old WebP files will be replaced.\n";
?>
