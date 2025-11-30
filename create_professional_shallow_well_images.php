#!/usr/bin/env php
<?php
/**
 * Create Professional Shallow Well Pump Images
 * Uses higher quality GD rendering with pump design
 */

echo "\n" . str_repeat("=", 90) . "\n";
echo "CREATING PROFESSIONAL SHALLOW WELL PUMP IMAGES\n";
echo str_repeat("=", 90) . "\n\n";

$uploadDir = '/home/bombayengg/public_html/uploads/pump/crompton_images/';

// Clean up old placeholder images
$oldImages = glob($uploadDir . 'swj*.webp');
foreach ($oldImages as $file) {
    unlink($file);
    echo "Removed old placeholder: " . basename($file) . "\n";
}

echo "\n";

// Create professional pump images
$products = [
    [
        'filename' => 'swj100ap-36-plus.webp',
        'title' => 'SWJ100AP-36 PLUS',
        'specs' => '1.0 HP | 0.75 kW',
        'colorR' => 40, 'colorG' => 120, 'colorB' => 160  // Teal blue
    ],
    [
        'filename' => 'swj100a-36-plus.webp',
        'title' => 'SWJ100A-36 PLUS',
        'specs' => '1.0 HP | 0.75 kW',
        'colorR' => 45, 'colorG' => 125, 'colorB' => 165
    ],
    [
        'filename' => 'swj50ap-30-plus.webp',
        'title' => 'SWJ50AP-30 PLUS',
        'specs' => '0.5 HP | 0.37 kW',
        'colorR' => 50, 'colorG' => 130, 'colorB' => 170
    ],
    [
        'filename' => 'swj50a-30-plus.webp',
        'title' => 'SWJ50A-30 PLUS',
        'specs' => '0.5 HP | 0.37 kW',
        'colorR' => 55, 'colorG' => 135, 'colorB' => 175
    ]
];

$createdCount = 0;

foreach ($products as $product) {
    // Create larger image (600x600 for high quality)
    $width = 600;
    $height = 600;
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $bgColor = imagecolorallocate($image, 240, 245, 250);  // Light background
    $mainColor = imagecolorallocate($image, $product['colorR'], $product['colorG'], $product['colorB']);
    $textColor = imagecolorallocate($image, 255, 255, 255);  // White
    $accentColor = imagecolorallocate($image, 255, 193, 7);  // Gold
    $darkColor = imagecolorallocate($image, 30, 30, 30);     // Dark gray
    
    // Fill background
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    
    // Draw decorative header bar
    imagefilledrectangle($image, 0, 0, $width, 120, $mainColor);
    
    // Draw pump body (stylized)
    $bodyX = 100;
    $bodyY = 150;
    $bodyW = 400;
    $bodyH = 300;
    
    // Main pump container
    imagefilledrectangle($image, $bodyX, $bodyY, $bodyX + $bodyW, $bodyY + $bodyH, $mainColor);
    imagefilledrectangle($image, $bodyX + 20, $bodyY + 20, $bodyX + $bodyW - 20, $bodyY + $bodyH - 20, $bgColor);
    
    // Pump inlet (left circle)
    $cx = $bodyX + 80;
    $cy = $bodyY + $bodyH / 2;
    imagefilledellipse($image, $cx, $cy, 50, 50, $accentColor);
    imageellipse($image, $cx, $cy, 50, 50, $mainColor);
    
    // Pump motor (center)
    $motorX = $bodyX + $bodyW / 2;
    $motorY = $bodyY + $bodyH / 2;
    imagefilledellipse($image, $motorX, $motorY, 100, 100, $mainColor);
    imagefilledellipse($image, $motorX, $motorY, 80, 80, $accentColor);
    imageellipse($image, $motorX, $motorY, 100, 100, $textColor);
    
    // Pump outlet (right circle)
    $ox = $bodyX + $bodyW - 80;
    $oy = $bodyY + $bodyH / 2;
    imagefilledellipse($image, $ox, $oy, 50, 50, $accentColor);
    imageellipse($image, $ox, $oy, 50, 50, $mainColor);
    
    // Connecting lines
    imageline($image, $cx, $cy, $motorX - 50, $motorY, $mainColor);
    imageline($image, $motorX + 50, $motorY, $ox, $oy, $mainColor);
    imageline($image, $cx, $cy, $motorX - 50, $motorY + 10, $mainColor);
    imageline($image, $motorX + 50, $motorY + 10, $ox, $oy + 10, $mainColor);
    
    // Add title text at top
    $font = 5;
    $titleLength = strlen($product['title']) * imagefontwidth($font);
    $titleX = ($width - $titleLength) / 2;
    imagestring($image, $font, $titleX, 35, $product['title'], $textColor);
    
    // Add specs text
    $specsFont = 3;
    $specsLength = strlen($product['specs']) * imagefontwidth($specsFont);
    $specsX = ($width - $specsLength) / 2;
    imagestring($image, $specsFont, $specsX, 75, $product['specs'], $accentColor);
    
    // Add category label at bottom
    $categoryText = 'SHALLOW WELL JET PUMP';
    $categoryLength = strlen($categoryText) * imagefontwidth(3);
    $categoryX = ($width - $categoryLength) / 2;
    imagestring($image, 3, $categoryX, 520, $categoryText, $darkColor);
    
    // Add Crompton branding
    $brandText = 'Crompton';
    $brandLength = strlen($brandText) * imagefontwidth(4);
    $brandX = ($width - $brandLength) / 2;
    imagestring($image, 4, $brandX, 560, $brandText, $mainColor);
    
    // Save as WebP with high quality
    $filepath = $uploadDir . $product['filename'];
    imagewebp($image, $filepath, 90);
    imagedestroy($image);
    
    if (file_exists($filepath)) {
        $fileSize = filesize($filepath);
        echo "✓ Created: " . $product['filename'] . " (" . number_format($fileSize) . " bytes)\n";
        $createdCount++;
    } else {
        echo "✗ Failed: " . $product['filename'] . "\n";
    }
}

echo "\n" . str_repeat("=", 90) . "\n";
echo "SUMMARY:\n";
echo "Professional images created: $createdCount/4\n";
echo "Quality: 90% WebP compression\n";
echo "Size: 600x600 pixels (high resolution)\n";
echo "Location: $uploadDir\n";
echo str_repeat("=", 90) . "\n\n";

?>
