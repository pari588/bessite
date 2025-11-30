<?php
// Remove black background from DMB-CMB images using PHP GD
// Uses ImageMagick if available for better quality

$uploadDir = '/home/bombayengg/public_html/uploads/pump/';
$images = ['cmb10nv-plus.webp', 'dmb10d-plus.webp', 'dmb10dcsl.webp', 'cmb05nv-plus.webp'];

echo "=== Removing Black Background (PHP GD with Transparency) ===\n\n";

foreach ($images as $image) {
    $sourcePath = $uploadDir . $image;

    if (!file_exists($sourcePath)) {
        echo "✗ $image - NOT FOUND\n";
        continue;
    }

    echo "Processing: $image\n";

    // Create image from WebP
    $img = imagecreatefromwebp($sourcePath);

    if (!$img) {
        echo "  ✗ Failed to load image\n";
        continue;
    }

    // Get dimensions
    $width = imagesx($img);
    $height = imagesy($img);

    // Create new image with transparency
    $newImg = imagecreatetruecolor($width, $height);

    // Enable alpha channel
    imagealphablending($newImg, false);
    imagesavealpha($newImg, true);

    // Create transparent color
    $transparent = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
    imagefill($newImg, 0, 0, $transparent);

    // Process pixel by pixel
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $pixelColor = imagecolorat($img, $x, $y);
            $rgb = imagecolorsforindex($img, $pixelColor);

            // Get RGB values
            $r = $rgb['red'];
            $g = $rgb['green'];
            $b = $rgb['blue'];

            // Check if pixel is black or very dark (0-50 on all channels)
            // Adjust threshold if needed
            if ($r < 50 && $g < 50 && $b < 50) {
                // Make it transparent
                $newPixel = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
            } else {
                // Keep the original color
                $newPixel = imagecolorallocatealpha($newImg, $r, $g, $b, $rgb['alpha']);
            }

            imagesetpixel($newImg, $x, $y, $newPixel);
        }
    }

    // Save as WebP with quality
    imagewebp($newImg, $sourcePath, 90);
    imagedestroy($img);
    imagedestroy($newImg);

    $newSize = filesize($sourcePath);
    echo "  ✓ Black background removed\n";
    echo "  Size: " . round($newSize / 1024, 2) . " KB\n\n";
}

echo "=== Verification ===\n";
foreach ($images as $image) {
    $path = $uploadDir . $image;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "✓ $image (" . round($size / 1024, 2) . " KB)\n";
    }
}

echo "\nNow regenerating thumbnails...\n";

// Regenerate 235x235 thumbnails
echo "\n=== Regenerating 235x235 Thumbnails ===\n\n";
$thumb235Dir = $uploadDir . '235_235_crop_100/';

foreach ($images as $image) {
    $sourcePath = $uploadDir . $image;
    $thumbPath = $thumb235Dir . $image;

    echo "Creating thumbnail: $image\n";

    $img = imagecreatefromwebp($sourcePath);
    if (!$img) {
        echo "  ✗ Failed to load source image\n";
        continue;
    }

    // Create thumbnail
    $thumbnail = imagecreatetruecolor(235, 235);
    imagealphablending($thumbnail, false);
    imagesavealpha($thumbnail, true);

    $origWidth = imagesx($img);
    $origHeight = imagesy($img);

    // Calculate crop to maintain aspect ratio
    $size = min($origWidth, $origHeight);
    $x = ($origWidth - $size) / 2;
    $y = ($origHeight - $size) / 2;

    imagecopyresampled(
        $thumbnail,
        $img,
        0, 0,
        (int)$x, (int)$y,
        235, 235,
        (int)$size, (int)$size
    );

    imagewebp($thumbnail, $thumbPath, 90);
    imagedestroy($img);
    imagedestroy($thumbnail);

    echo "  ✓ Created\n";
}

// Regenerate 530x530 thumbnails
echo "\n=== Regenerating 530x530 Thumbnails ===\n\n";
$thumb530Dir = $uploadDir . '530_530_crop_100/';

foreach ($images as $image) {
    $sourcePath = $uploadDir . $image;
    $thumbPath = $thumb530Dir . $image;

    echo "Creating thumbnail: $image\n";

    $img = imagecreatefromwebp($sourcePath);
    if (!$img) {
        echo "  ✗ Failed to load source image\n";
        continue;
    }

    // Create thumbnail
    $thumbnail = imagecreatetruecolor(530, 530);
    imagealphablending($thumbnail, false);
    imagesavealpha($thumbnail, true);

    $origWidth = imagesx($img);
    $origHeight = imagesy($img);

    // Calculate crop to maintain aspect ratio
    $size = min($origWidth, $origHeight);
    $x = ($origWidth - $size) / 2;
    $y = ($origHeight - $size) / 2;

    imagecopyresampled(
        $thumbnail,
        $img,
        0, 0,
        (int)$x, (int)$y,
        530, 530,
        (int)$size, (int)$size
    );

    imagewebp($thumbnail, $thumbPath, 90);
    imagedestroy($img);
    imagedestroy($thumbnail);

    echo "  ✓ Created\n";
}

echo "\n=== All Complete ===\n";
echo "✅ Black backgrounds removed\n";
echo "✅ Thumbnails regenerated\n";

?>
