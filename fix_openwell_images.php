<?php
/**
 * Residential Openwell Pump Image Processor
 * - Fetches original images from Crompton
 * - Preserves transparency
 * - Resizes to required dimensions
 * - Converts to WebP format with transparency
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define paths
$uploadDir = __DIR__ . '/uploads/pump';
$imageMagickBin = '/usr/bin/convert';

// Product mapping for openwell pumps
$products = [
    [
        'pumpID' => 43,
        'title' => 'OWE12(1PH)Z-28',
        'seoUri' => 'owe12-1ph-z-28',
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/0we12-1-hp.png?v=1729768034&width=533',
        'filename' => 'owe12-1ph-z-28'
    ],
    [
        'pumpID' => 44,
        'title' => 'OWE052(1PH)Z-21FS',
        'seoUri' => 'owe052-1ph-z-21fs',
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/OWE1.52_1PH_B-38FS.png?v=1702386166&width=533',
        'filename' => 'owe052-1ph-z-21fs'
    ]
];

echo "=== RESIDENTIAL OPENWELL PUMP IMAGE PROCESSOR ===\n\n";

foreach ($products as $product) {
    echo "Processing: {$product['title']} (ID: {$product['pumpID']})\n";
    echo "-------------------------------------------\n";

    // Step 1: Download image
    echo "1. Downloading image from Crompton...\n";
    $tempFile = tempnam(sys_get_temp_dir(), 'openwell_');

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
    echo "3. Resizing to 530x530 and converting to WebP...\n";

    $mainWebp = $uploadDir . '/530_530_crop_100/' . $product['filename'] . '.webp';

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
        echo "   ✓ Created 530x530 WebP ($webpSize bytes)\n";
    } else {
        echo "   ✗ Failed to create main WebP\n";
        echo "   Error: $output\n";
        unlink($tempFile);
        continue;
    }

    // Step 4: Resize to 235x235 for thumbnail (preserving transparency)
    echo "4. Resizing to 235x235 and converting to WebP...\n";

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
        echo "   ✓ Created 235x235 WebP ($thumbSize bytes)\n";
    } else {
        echo "   ✗ Failed to create thumbnail WebP\n";
        echo "   Error: $output\n";
        unlink($tempFile);
        unlink($mainWebp);
        continue;
    }

    // Step 5: Create base WebP
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
        echo "   ✓ Created base WebP ($baseSize bytes)\n";
    } else {
        echo "   ✗ Failed to create base WebP\n";
    }

    // Cleanup
    unlink($tempFile);

    echo "✓ Completed: {$product['title']}\n\n";
}

echo "\n=== PROCESSING COMPLETE ===\n";
echo "Openwell images have been processed with transparency.\n";
echo "Database needs to be updated with new image filenames.\n";
?>
