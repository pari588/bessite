<?php
// Fix mini pump thumbnails by properly resizing them
ini_set('display_errors', 1);
error_reporting(E_ALL);

$upload_path = '/home/bombayengg/public_html/uploads/pump';

echo "=== Fixing Mini Pump Thumbnails ===\n\n";

// Mini pump images that need proper resizing
$mini_pump_images = array(
    'mini-master-ii.webp',
    'champ-plus-ii.webp',
    'mini-masterplus-ii.webp',
    'mini-marvel-ii.webp',
    'flomax-plus-ii.webp'
);

$fixed = 0;

foreach ($mini_pump_images as $img_name) {
    $source_file = $upload_path . '/' . $img_name;

    if (!file_exists($source_file)) {
        echo "❌ $img_name: Source not found\n";
        continue;
    }

    // Fix 530x530 thumbnail
    $thumb_530 = $upload_path . '/530_530_crop_100/' . $img_name;
    if (resizeWebPImage($source_file, $thumb_530, 530)) {
        echo "✅ $img_name → 530x530 resized\n";
        $fixed++;
    } else {
        echo "❌ $img_name → 530x530 failed\n";
    }

    // Fix 235x235 thumbnail
    $thumb_235 = $upload_path . '/235_235_crop_100/' . $img_name;
    if (resizeWebPImage($source_file, $thumb_235, 235)) {
        echo "   ✅ → 235x235 resized\n";
    } else {
        echo "   ❌ → 235x235 failed\n";
    }
}

function resizeWebPImage($source, $dest, $size) {
    if (!file_exists($source)) return false;

    try {
        // Load source WebP
        $img = imagecreatefromwebp($source);
        if (!$img) return false;

        $w = imagesx($img);
        $h = imagesy($img);

        // Center crop to square
        if ($w > $h) {
            $crop_size = $h;
            $x = ($w - $crop_size) / 2;
            $y = 0;
        } else {
            $crop_size = $w;
            $x = 0;
            $y = ($h - $crop_size) / 2;
        }

        $cropped = imagecrop($img, array('x' => $x, 'y' => $y, 'width' => $crop_size, 'height' => $crop_size));
        if (!$cropped) {
            imagedestroy($img);
            return false;
        }

        // Resize to target size
        $thumb = imagecreatetruecolor($size, $size);
        imagecopyresampled($thumb, $cropped, 0, 0, 0, 0, $size, $size, $crop_size, $crop_size);

        // Save as WebP
        $success = imagewebp($thumb, $dest, 85);

        imagedestroy($img);
        imagedestroy($cropped);
        imagedestroy($thumb);

        return $success;
    } catch (Exception $e) {
        return false;
    }
}

echo "\n=== Done ===\n";
echo "Fixed: $fixed images\n";
?>
