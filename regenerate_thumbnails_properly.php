<?php
// Regenerate thumbnails properly with GD library
require_once("config.inc.php");

$upload_path = '/home/bombayengg/public_html/uploads/pump';
$sizes = array(
    '235_235_crop_100' => 235,
    '530_530_crop_100' => 530
);

echo "=== Regenerating Product Thumbnails ===\n\n";

// Get all original pump images
$original_images = glob($upload_path . '/*.webp');

if (empty($original_images)) {
    echo "❌ No original WebP images found\n";
    exit;
}

echo "Found " . count($original_images) . " original images\n\n";

$processed = 0;
$failed = 0;

foreach ($original_images as $image_file) {
    $filename = basename($image_file);

    foreach ($sizes as $folder => $size) {
        $thumbnail_path = $upload_path . '/' . $folder . '/' . $filename;

        // Skip if thumbnail doesn't need regenerating (check modification time)
        if (file_exists($thumbnail_path)) {
            $original_time = filemtime($image_file);
            $thumb_time = filemtime($thumbnail_path);

            // If thumbnail is newer and has content, skip it
            if ($thumb_time > $original_time && filesize($thumbnail_path) > 1000) {
                continue;
            }
        }

        // Create thumbnail
        if (createWebPThumbnail($image_file, $thumbnail_path, $size)) {
            echo "✅ $filename → $folder ($size x $size)\n";
            $processed++;
        } else {
            echo "❌ Failed: $filename → $folder\n";
            $failed++;
        }
    }
}

function createWebPThumbnail($source_path, $dest_path, $size = 530) {
    if (!file_exists($source_path)) {
        return false;
    }

    // Create destination directory if it doesn't exist
    $dest_dir = dirname($dest_path);
    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }

    try {
        // Check if source is a valid WebP file
        $image_info = getimagesize($source_path);
        if ($image_info === false) {
            return false;
        }

        // Load the WebP image
        $source_image = imagecreatefromwebp($source_path);
        if ($source_image === false) {
            return false;
        }

        $source_width = imagesx($source_image);
        $source_height = imagesy($source_image);

        // Calculate crop dimensions (center crop)
        $aspect_ratio = $source_width / $source_height;

        if ($aspect_ratio > 1) {
            // Wider than tall - crop width
            $crop_width = (int)($source_height * 1);
            $crop_height = $source_height;
            $crop_x = (int)(($source_width - $crop_width) / 2);
            $crop_y = 0;
        } else {
            // Taller than wide or square - crop height
            $crop_width = $source_width;
            $crop_height = (int)($source_width / 1);
            $crop_x = 0;
            $crop_y = (int)(($source_height - $crop_height) / 2);
        }

        // Create cropped image
        $cropped_image = imagecrop($source_image, array(
            'x' => $crop_x,
            'y' => $crop_y,
            'width' => $crop_width,
            'height' => $crop_height
        ));

        if ($cropped_image === false) {
            imagedestroy($source_image);
            return false;
        }

        // Create thumbnail
        $thumbnail_image = imagecreatetruecolor($size, $size);
        imagecopyresampled($thumbnail_image, $cropped_image, 0, 0, 0, 0, $size, $size, $crop_width, $crop_height);

        // Save as WebP with quality 85
        $success = imagewebp($thumbnail_image, $dest_path, 85);

        // Cleanup
        imagedestroy($source_image);
        imagedestroy($cropped_image);
        imagedestroy($thumbnail_image);

        return $success;
    } catch (Exception $e) {
        return false;
    }
}

echo "\n=== Results ===\n";
echo "✅ Processed: $processed\n";
echo "❌ Failed: $failed\n";
echo "\n✨ Thumbnail regeneration complete!\n";
?>
