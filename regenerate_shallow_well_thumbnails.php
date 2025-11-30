#!/usr/bin/env php
<?php
/**
 * Regenerate Thumbnails from Professional Shallow Well Pump Images
 */

$sourceDir = '/home/bombayengg/public_html/uploads/pump/crompton_images/';
$thumbDir235 = '/home/bombayengg/public_html/uploads/pump/235_235_crop_100/';
$thumbDir530 = '/home/bombayengg/public_html/uploads/pump/530_530_crop_100/';

echo "\n" . str_repeat("=", 90) . "\n";
echo "REGENERATING THUMBNAILS FROM PROFESSIONAL IMAGES\n";
echo str_repeat("=", 90) . "\n\n";

$products = [
    'swj100ap-36-plus.webp',
    'swj100a-36-plus.webp',
    'swj50ap-30-plus.webp',
    'swj50a-30-plus.webp'
];

$count235 = 0;
$count530 = 0;

foreach ($products as $filename) {
    $sourcePath = $sourceDir . $filename;
    
    if (!file_exists($sourcePath)) {
        echo "✗ Source not found: $filename\n";
        continue;
    }
    
    // Load source image
    $sourceImage = imagecreatefromwebp($sourcePath);
    if (!$sourceImage) {
        echo "✗ Failed to load: $filename\n";
        continue;
    }
    
    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    
    // Generate 235x235 thumbnail
    $thumb235 = imagecreatetruecolor(235, 235);
    imagecopyresampled($thumb235, $sourceImage, 0, 0, 0, 0, 235, 235, $sourceWidth, $sourceHeight);
    $path235 = $thumbDir235 . $filename;
    imagewebp($thumb235, $path235, 85);
    imagedestroy($thumb235);
    
    if (file_exists($path235)) {
        echo "✓ 235x235: $filename\n";
        $count235++;
    }
    
    // Generate 530x530 thumbnail
    $thumb530 = imagecreatetruecolor(530, 530);
    imagecopyresampled($thumb530, $sourceImage, 0, 0, 0, 0, 530, 530, $sourceWidth, $sourceHeight);
    $path530 = $thumbDir530 . $filename;
    imagewebp($thumb530, $path530, 85);
    imagedestroy($thumb530);
    
    if (file_exists($path530)) {
        echo "✓ 530x530: $filename\n";
        $count530++;
    }
    
    imagedestroy($sourceImage);
}

echo "\n" . str_repeat("=", 90) . "\n";
echo "RESULTS:\n";
echo "235x235 thumbnails: $count235/4\n";
echo "530x530 thumbnails: $count530/4\n";
echo "Total: " . ($count235 + $count530) . "/8\n";
echo str_repeat("=", 90) . "\n\n";

?>
