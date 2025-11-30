#!/usr/bin/env php
<?php
/**
 * Convert Crompton PNG Images to WebP
 */

echo "\n" . str_repeat("=", 90) . "\n";
echo "CONVERTING CROMPTON PRODUCT IMAGES TO WebP\n";
echo str_repeat("=", 90) . "\n\n";

$sourceDir = '/home/bombayengg/public_html/uploads/pump/crompton_images/';

// Remove old placeholder WebP files first
$oldPlaceholders = [
    'swj100ap-36-plus.webp',
    'swj100a-36-plus.webp',
    'swj50ap-30-plus.webp',
    'swj50a-30-plus.webp'
];

echo "Removing old placeholder images...\n";
foreach ($oldPlaceholders as $file) {
    $path = $sourceDir . $file;
    if (file_exists($path)) {
        unlink($path);
        echo "  ✓ Removed: $file\n";
    }
}

echo "\nConverting PNG to WebP...\n\n";

// Convert PNG files to WebP
$pngFiles = [
    'swj100ap-36-plus.png',
    'swj100a-36-plus.png',
    'swj50ap-30-plus.png',
    'swj50a-30-plus.png'
];

$convertedCount = 0;

foreach ($pngFiles as $pngFile) {
    $pngPath = $sourceDir . $pngFile;
    $webpFile = str_replace('.png', '.webp', $pngFile);
    $webpPath = $sourceDir . $webpFile;
    
    if (!file_exists($pngPath)) {
        echo "✗ PNG not found: $pngFile\n";
        continue;
    }
    
    // Load PNG image
    $image = @imagecreatefrompng($pngPath);
    
    if (!$image) {
        echo "✗ Failed to load PNG: $pngFile\n";
        continue;
    }
    
    // Get image info
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Convert to WebP with high quality
    imagewebp($image, $webpPath, 85);
    imagedestroy($image);
    
    if (file_exists($webpPath)) {
        $pngSize = filesize($pngPath);
        $webpSize = filesize($webpPath);
        $reduction = round((1 - ($webpSize / $pngSize)) * 100);
        
        echo "✓ Converted: $webpFile\n";
        echo "  Original: " . number_format($pngSize) . " bytes (PNG)\n";
        echo "  Converted: " . number_format($webpSize) . " bytes (WebP)\n";
        echo "  Size reduction: {$reduction}%\n";
        echo "  Image size: {$width}×{$height}px\n\n";
        
        $convertedCount++;
    } else {
        echo "✗ Failed to convert: $pngFile\n\n";
    }
}

echo str_repeat("=", 90) . "\n";
echo "CONVERSION SUMMARY:\n";
echo "Successfully converted: $convertedCount/4\n";
echo "Location: $sourceDir\n";
echo str_repeat("=", 90) . "\n\n";

// Delete old PNG files to save space
echo "Cleaning up PNG files...\n";
foreach ($pngFiles as $pngFile) {
    $path = $sourceDir . $pngFile;
    if (file_exists($path)) {
        $size = filesize($path);
        unlink($path);
        echo "  ✓ Deleted: $pngFile (" . number_format($size) . " bytes freed)\n";
    }
}

echo "\n✓ Done! Using actual Crompton product images.\n\n";

?>
