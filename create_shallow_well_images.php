#!/usr/bin/env php
<?php
/**
 * Create images for missing Shallow Well Pump products
 * Generates branded WebP images with pump names
 */

// Configuration
$uploadDir = '/home/bombayengg/public_html/uploads/pump/crompton_images/';
$imageWidth = 400;
$imageHeight = 400;
$backgroundColor = [8, 85, 140];  // Crompton blue
$textColor = [255, 255, 255];     // White text

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "✓ Created upload directory: $uploadDir\n";
}

// Products to create images for
$products = [
    [
        'filename' => 'swj100ap-36-plus.webp',
        'title' => 'SWJ100AP-36 PLUS',
        'specs' => '1.0 HP | 0.75 kW'
    ],
    [
        'filename' => 'swj100a-36-plus.webp',
        'title' => 'SWJ100A-36 PLUS',
        'specs' => '1.0 HP | 0.75 kW'
    ],
    [
        'filename' => 'swj50ap-30-plus.webp',
        'title' => 'SWJ50AP-30 PLUS',
        'specs' => '0.5 HP | 0.37 kW'
    ],
    [
        'filename' => 'swj50a-30-plus.webp',
        'title' => 'SWJ50A-30 PLUS',
        'specs' => '0.5 HP | 0.37 kW'
    ]
];

echo "\n" . str_repeat("=", 80) . "\n";
echo "CREATING SHALLOW WELL PUMP IMAGES\n";
echo str_repeat("=", 80) . "\n\n";

$createdCount = 0;

foreach ($products as $product) {
    // Create image using GD
    $image = imagecreatetruecolor($imageWidth, $imageHeight);
    
    // Set colors
    $bgColor = imagecolorallocate($image, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
    $textColorId = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
    $accentColor = imagecolorallocate($image, 255, 193, 7);  // Gold accent
    
    // Fill background
    imagefilledrectangle($image, 0, 0, $imageWidth, $imageHeight, $bgColor);
    
    // Add border
    imagerectangle($image, 5, 5, $imageWidth - 5, $imageHeight - 5, $accentColor);
    
    // Add pump icon representation (simple rectangle)
    $pumpX = 80;
    $pumpY = 80;
    $pumpWidth = 240;
    $pumpHeight = 160;
    imagefilledrectangle($image, $pumpX, $pumpY, $pumpX + $pumpWidth, $pumpY + $pumpHeight, $accentColor);
    imagefilledrectangle($image, $pumpX + 20, $pumpY + 20, $pumpX + $pumpWidth - 20, $pumpY + $pumpHeight - 20, $bgColor);
    
    // Add product title (with word wrapping)
    $fontSize = 5;
    $title = $product['title'];
    $titleWidth = strlen($title) * imagefontwidth($fontSize);
    $titleX = ($imageWidth - $titleWidth) / 2;
    imagestring($image, $fontSize, $titleX, 260, $title, $textColorId);
    
    // Add specifications
    $specs = $product['specs'];
    $specsWidth = strlen($specs) * imagefontwidth(3);
    $specsX = ($imageWidth - $specsWidth) / 2;
    imagestring($image, 3, $specsX, 285, $specs, $accentColor);
    
    // Add "SHALLOW WELL" text
    $categoryText = 'SHALLOW WELL PUMP';
    $categoryWidth = strlen($categoryText) * imagefontwidth(3);
    $categoryX = ($imageWidth - $categoryWidth) / 2;
    imagestring($image, 3, $categoryX, 310, $categoryText, $textColorId);
    
    // Save as WebP
    $filePath = $uploadDir . $product['filename'];
    imagewebp($image, $filePath, 80);
    imagedestroy($image);
    
    if (file_exists($filePath)) {
        $fileSize = filesize($filePath);
        echo "✓ Created: " . $product['filename'] . " (" . number_format($fileSize) . " bytes)\n";
        $createdCount++;
    } else {
        echo "✗ Failed to create: " . $product['filename'] . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY:\n";
echo "Total images created: $createdCount/4\n";
echo "Location: $uploadDir\n";
echo str_repeat("=", 80) . "\n\n";

?>
