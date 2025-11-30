<?php
/*
 * Create placeholder WebP images for 4-inch borewell pumps
 * These will be replaced with actual images once downloaded
 */

$uploadDir = '/home/bombayengg/public_html/uploads/pump';
$thumbDir = $uploadDir . '/235_235_crop_100';
$largeDir = $uploadDir . '/530_530_crop_100';

// Ensure directories exist
if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);
if (!is_dir($largeDir)) mkdir($largeDir, 0755, true);

$products = array(
    '4w12bf1-5e',
    '4w14bf1-5e',
    '4vo1-7-bue-u4s',
    '4vo1-10-bue-u4s',
    '4vo7bu1eu',
    '4vo10bu1eu',
    '4vo1-5-12-bue-u4s',
    '4vo1-5-14-bue-u4s'
);

echo "Creating placeholder images...\n";

foreach ($products as $filename) {
    // Create a simple placeholder image using PHP GD

    // Main image (530x530)
    $mainPath = $uploadDir . '/' . $filename . '.webp';
    $img = imagecreate(530, 530);

    // Colors
    $bgColor = imagecolorallocate($img, 200, 200, 200);
    $textColor = imagecolorallocate($img, 50, 50, 50);

    // Fill background
    imagefill($img, 0, 0, $bgColor);

    // Add text
    $text = strtoupper(str_replace(array('-'), array(' '), $filename));
    imagestring($img, 5, 150, 240, $text, $textColor);
    imagestring($img, 2, 180, 260, "4-Inch Borewell Pump", $textColor);

    // Save as WebP
    if (function_exists('imagewebp')) {
        imagewebp($img, $mainPath, 80);
        echo "  ✓ Created: $mainPath\n";
    } else {
        echo "  ✗ GD WebP not available for: $mainPath\n";
    }

    imagedestroy($img);

    // Thumbnail image (235x235)
    $thumbPath = $thumbDir . '/' . $filename . '.webp';
    $img = imagecreate(235, 235);

    // Colors
    $bgColor = imagecolorallocate($img, 200, 200, 200);
    $textColor = imagecolorallocate($img, 50, 50, 50);

    // Fill background
    imagefill($img, 0, 0, $bgColor);

    // Add text
    imagestring($img, 3, 40, 105, $text, $textColor);

    // Save as WebP
    if (function_exists('imagewebp')) {
        imagewebp($img, $thumbPath, 80);
        echo "  ✓ Created: $thumbPath\n";
    }

    imagedestroy($img);
}

echo "\nPlaceholder images created successfully!\n";
?>
