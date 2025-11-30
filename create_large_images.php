<?php
/*
 * Create large image variants (530x530) for 4-inch borewell pumps
 */

$uploadDir = '/home/bombayengg/public_html/uploads/pump';
$largeDir = $uploadDir . '/530_530_crop_100';

// Ensure directory exists
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

echo "Creating large image variants (530x530)...\n";

foreach ($products as $filename) {
    // Create a simple placeholder image using PHP GD
    $largeImagePath = $largeDir . '/' . $filename . '.webp';

    // Create true color image for better WebP support
    $img = imagecreatetruecolor(530, 530);

    // Colors (RGB)
    $bgColor = imagecolorallocate($img, 220, 220, 220);
    $textColor = imagecolorallocate($img, 50, 50, 50);
    $accentColor = imagecolorallocate($img, 100, 150, 200);

    // Fill background
    imagefill($img, 0, 0, $bgColor);

    // Add border
    imagerectangle($img, 5, 5, 524, 524, $accentColor);
    imagerectangle($img, 10, 10, 519, 519, $textColor);

    // Add text
    $text = strtoupper(str_replace(array('-'), array(' '), $filename));
    imagestring($img, 5, 150, 240, $text, $textColor);
    imagestring($img, 2, 180, 260, "4-Inch Borewell Pump", $textColor);
    imagestring($img, 2, 180, 275, "(Placeholder - Replace with actual image)", $textColor);

    // Save as WebP with better quality
    if (function_exists('imagewebp')) {
        imagewebp($img, $largeImagePath, 85);
        echo "  ✓ Created: $largeImagePath\n";
    } else {
        echo "  ✗ GD WebP not available: $largeImagePath\n";
    }

    imagedestroy($img);
}

echo "\nLarge image variants created successfully!\n";
?>
