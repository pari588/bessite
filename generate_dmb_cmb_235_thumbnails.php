<?php
// Generate 235x235 thumbnails for DMB-CMB pump images (for listing pages)

$sourceDir = __DIR__ . '/uploads/pump/';
$thumbDir = __DIR__ . '/uploads/pump/235_235_crop_100/';

// Ensure thumbnail directory exists
if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
    echo "Created thumbnail directory: $thumbDir\n";
}

$images = [
    'cmb10nv-plus.webp',
    'dmb10d-plus.webp',
    'dmb10dcsl.webp',
    'cmb05nv-plus.webp'
];

echo "=== Generating 235x235 Thumbnails for Listing Pages ===\n\n";

foreach ($images as $image) {
    $sourcePath = $sourceDir . $image;
    $thumbPath = $thumbDir . $image;

    if (file_exists($sourcePath)) {
        echo "Processing: $image\n";

        // Create image from WebP
        $img = imagecreatefromwebp($sourcePath);

        if ($img) {
            // Get original dimensions
            $origWidth = imagesx($img);
            $origHeight = imagesy($img);

            echo "  Original size: {$origWidth}x{$origHeight}\n";

            // Create 235x235 thumbnail
            $thumbnail = imagecreatetruecolor(235, 235);

            // Calculate aspect ratio and crop coordinates
            if ($origWidth > $origHeight) {
                // Landscape
                $size = $origHeight;
                $x = ($origWidth - $size) / 2;
                $y = 0;
            } else {
                // Portrait or square
                $size = $origWidth;
                $x = 0;
                $y = ($origHeight - $size) / 2;
            }

            // Copy and resize
            imagecopyresampled(
                $thumbnail,
                $img,
                0,
                0,
                (int)$x,
                (int)$y,
                235,
                235,
                (int)$size,
                (int)$size
            );

            // Save as WebP
            imagewebp($thumbnail, $thumbPath, 90);

            imagedestroy($img);
            imagedestroy($thumbnail);

            $thumbSize = filesize($thumbPath);
            echo "  ✓ Created thumbnail: {$image} (" . round($thumbSize / 1024, 2) . " KB)\n\n";
        } else {
            echo "  ✗ Failed to create image from: $image\n\n";
        }
    } else {
        echo "  ✗ Source file not found: $image\n\n";
    }
}

echo "=== Verification ===\n\n";
echo "Thumbnails in uploads/pump/235_235_crop_100/:\n";

foreach ($images as $image) {
    $thumbPath = $thumbDir . $image;
    if (file_exists($thumbPath)) {
        $size = filesize($thumbPath);
        echo "✓ $image (" . round($size / 1024, 2) . " KB)\n";
    } else {
        echo "✗ $image (NOT FOUND)\n";
    }
}

?>
