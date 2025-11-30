#!/usr/bin/env php
<?php
/**
 * Convert Correct Crompton Images to WebP
 */

echo "\n" . str_repeat("=", 90) . "\n";
echo "CONVERTING CORRECT CROMPTON IMAGES TO WebP\n";
echo str_repeat("=", 90) . "\n\n";

$sourceDir = '/home/bombayengg/public_html/uploads/pump/crompton_images/';

$products = [
    'swj100ap-36-plus.png',
    'swj100a-36-plus.png',
    'swj50ap-30-plus.png',
    'swj50a-30-plus.png'
];

$convertedCount = 0;

foreach ($products as $pngFile) {
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

?>
