#!/usr/bin/env php
<?php
/**
 * Convert Downloaded Shallow Well Pump Images to WebP
 */

echo "\n" . str_repeat("=", 90) . "\n";
echo "CONVERTING SHALLOW WELL PUMP IMAGES TO WebP\n";
echo str_repeat("=", 90) . "\n\n";

$sourceDir = '/home/bombayengg/public_html/uploads/pump/crompton_images/';

$products = [
    'swj100ap-36-plus',
    'swj100a-36-plus',
    'swj50ap-30-plus',
    'swj50a-30-plus'
];

$convertedCount = 0;

foreach ($products as $product) {
    $jpgFile = $sourceDir . $product . '.jpg';
    $webpFile = $sourceDir . $product . '.webp';
    
    if (!file_exists($jpgFile)) {
        echo "✗ Source file not found: $jpgFile\n";
        continue;
    }
    
    // Load JPG image
    $image = @imagecreatefromjpeg($jpgFile);
    
    if (!$image) {
        echo "✗ Failed to load JPG: $product.jpg\n";
        continue;
    }
    
    // Convert to WebP with 90% quality for better quality
    imagewebp($image, $webpFile, 90);
    imagedestroy($image);
    
    if (file_exists($webpFile)) {
        $jpgSize = filesize($jpgFile);
        $webpSize = filesize($webpFile);
        $compression = round(($webpSize / $jpgSize) * 100);
        
        echo "✓ Converted: $product.webp (" . number_format($webpSize) . " bytes, {$compression}% of JPG size)\n";
        
        // Optional: Remove JPG to save space
        // unlink($jpgFile);
        
        $convertedCount++;
    } else {
        echo "✗ Failed to convert: $product\n";
    }
}

echo "\n" . str_repeat("=", 90) . "\n";
echo "CONVERSION COMPLETE:\n";
echo "Converted: $convertedCount/4\n";
echo "Location: $sourceDir\n";
echo str_repeat("=", 90) . "\n\n";

?>
