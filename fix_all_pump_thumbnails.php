<?php
// Fix all pump product thumbnails
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("config.inc.php");

$upload_path = '/home/bombayengg/public_html/uploads/pump';

echo "=== Regenerating ALL Pump Thumbnails ===\n\n";

// Get all unique pump images from database
$DB->sql = "SELECT DISTINCT pumpImage FROM mx_pump WHERE status = 1 AND pumpImage IS NOT NULL AND pumpImage != '' ORDER BY pumpImage";
$DB->types = "";
$DB->vals = array();
$images = $DB->dbRows();

if ($DB->numRows == 0) {
    echo "❌ No pump images found in database\n";
    $DB->con->close();
    exit;
}

echo "Found " . count($images) . " unique pump images\n\n";

$fixed_530 = 0;
$fixed_235 = 0;
$failed = 0;

foreach ($images as $row) {
    $img_name = $row['pumpImage'];
    $source_file = $upload_path . '/' . $img_name;

    if (!file_exists($source_file)) {
        echo "⚠️  $img_name: Source file not found\n";
        continue;
    }

    $file_size_original = filesize($source_file);

    // Fix 530x530 thumbnail
    $thumb_530 = $upload_path . '/530_530_crop_100/' . $img_name;
    $file_size_530_before = file_exists($thumb_530) ? filesize($thumb_530) : 0;

    if (resizeWebPImage($source_file, $thumb_530, 530)) {
        $file_size_530_after = filesize($thumb_530);
        echo "✅ $img_name\n";
        echo "   Original: " . formatBytes($file_size_original) . " → 530x530: " . formatBytes($file_size_530_after) . " (was " . formatBytes($file_size_530_before) . ")\n";
        $fixed_530++;

        // Fix 235x235 thumbnail
        $thumb_235 = $upload_path . '/235_235_crop_100/' . $img_name;
        if (resizeWebPImage($source_file, $thumb_235, 235)) {
            $file_size_235 = filesize($thumb_235);
            echo "   235x235: " . formatBytes($file_size_235) . "\n";
            $fixed_235++;
        } else {
            echo "   ❌ 235x235 failed\n";
            $failed++;
        }
    } else {
        echo "❌ $img_name: Failed to create 530x530 thumbnail\n";
        $failed++;
    }
}

function resizeWebPImage($source, $dest, $size) {
    if (!file_exists($source)) return false;

    try {
        // Ensure destination directory exists
        $dest_dir = dirname($dest);
        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0755, true);
        }

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

        $cropped = imagecrop($img, array('x' => intval($x), 'y' => intval($y), 'width' => $crop_size, 'height' => $crop_size));
        if (!$cropped) {
            imagedestroy($img);
            return false;
        }

        // Resize to target size
        $thumb = imagecreatetruecolor($size, $size);
        imagecopyresampled($thumb, $cropped, 0, 0, 0, 0, $size, $size, $crop_size, $crop_size);

        // Save as WebP with good quality
        $success = imagewebp($thumb, $dest, 85);

        imagedestroy($img);
        imagedestroy($cropped);
        imagedestroy($thumb);

        return $success;
    } catch (Exception $e) {
        return false;
    }
}

function formatBytes($bytes, $precision = 1) {
    $units = array('B', 'KB', 'MB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

echo "\n=== Results ===\n";
echo "✅ Fixed 530x530: $fixed_530\n";
echo "✅ Fixed 235x235: $fixed_235\n";
echo "❌ Failed: $failed\n";
echo "\n✨ Thumbnail regeneration complete!\n";

$DB->con->close();
?>
