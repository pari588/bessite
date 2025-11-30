<?php
require_once("config.inc.php");
require_once("core/core.inc.php");

echo "=== Pump Image Thumbnail Generator ===\n\n";

// Get all active pumps
$DB->sql = "SELECT pumpID, pumpTitle, pumpImage FROM mx_pump WHERE status=1 ORDER BY pumpID";
$pumps = $DB->dbRows();

echo "Total pumps to process: " . count($pumps) . "\n\n";

$sizes = array(
    array("dir" => "235_235_crop_100", "w" => 235, "h" => 235),
    array("dir" => "530_530_crop_100", "w" => 530, "h" => 530)
);

$success_count = 0;
$error_count = 0;
$errors = array();

foreach($pumps as $pump) {
    if(empty($pump['pumpImage'])) {
        echo "⚠️  SKIP - ID {$pump['pumpID']}: {$pump['pumpTitle']} - NO IMAGE FILE\n";
        continue;
    }

    // Handle multiple images (comma-separated)
    $images = array_map('trim', explode(",", $pump['pumpImage']));

    foreach($images as $img) {
        $img = trim($img);
        if(empty($img)) continue;

        $src_file = UPLOADPATH . "/pump/" . $img;

        // Check if source file exists
        if(!file_exists($src_file)) {
            $error_count++;
            $errors[] = "ID {$pump['pumpID']}: Source file not found: {$img}";
            echo "❌ ERROR - ID {$pump['pumpID']}: {$pump['pumpTitle']} - SOURCE FILE NOT FOUND: {$img}\n";
            continue;
        }

        // Generate thumbnails
        $all_success = true;
        foreach($sizes as $size) {
            $dest_dir = UPLOADPATH . "/pump/" . $size['dir'];
            $dest_file = $dest_dir . "/" . $img;

            // Create directory if it doesn't exist
            if(!is_dir($dest_dir)) {
                @mkdir($dest_dir, 0777, true);
            }

            // Skip if already exists and is newer than source
            if(file_exists($dest_file)) {
                $src_time = filemtime($src_file);
                $dest_time = filemtime($dest_file);
                if($dest_time >= $src_time) {
                    echo "  ✓ {$size['dir']}/{$img} (already exists)\n";
                    continue;
                }
            }

            // Try to create thumbnail
            if(generateThumbnail($src_file, $dest_file, $size['w'], $size['h'])) {
                echo "  ✓ {$size['dir']}/{$img}\n";
            } else {
                $all_success = false;
                $error_count++;
                $errors[] = "ID {$pump['pumpID']}: Failed to create {$size['dir']}/{$img}";
                echo "  ❌ {$size['dir']}/{$img} - FAILED\n";
            }
        }

        if($all_success) {
            echo "✅ ID {$pump['pumpID']}: {$pump['pumpTitle']}\n";
            $success_count++;
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Successfully processed: $success_count\n";
echo "Errors: $error_count\n";

if(count($errors) > 0) {
    echo "\nError Details:\n";
    foreach($errors as $err) {
        echo "  - $err\n";
    }
}

// Function to generate thumbnail using GD library
function generateThumbnail($src, $dest, $width, $height) {
    try {
        // Determine image type
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));

        // Load image
        $img = null;
        switch($ext) {
            case 'jpg':
            case 'jpeg':
                if(!function_exists('imagecreatefromjpeg')) return false;
                $img = imagecreatefromjpeg($src);
                break;
            case 'png':
                if(!function_exists('imagecreatefrompng')) return false;
                $img = imagecreatefrompng($src);
                break;
            case 'gif':
                if(!function_exists('imagecreatefromgif')) return false;
                $img = imagecreatefromgif($src);
                break;
            case 'webp':
                if(!function_exists('imagecreatefromwebp')) return false;
                $img = imagecreatefromwebp($src);
                break;
            default:
                return false;
        }

        if(!$img) return false;

        // Get image dimensions
        $orig_width = imagesx($img);
        $orig_height = imagesy($img);

        // Calculate crop dimensions (crop to fit center)
        if($orig_width / $orig_height > $width / $height) {
            // Image is wider than desired ratio
            $crop_width = intval($orig_height * $width / $height);
            $crop_height = $orig_height;
            $crop_x = intval(($orig_width - $crop_width) / 2);
            $crop_y = 0;
        } else {
            // Image is taller than desired ratio
            $crop_width = $orig_width;
            $crop_height = intval($orig_width * $height / $width);
            $crop_x = 0;
            $crop_y = intval(($orig_height - $crop_height) / 2);
        }

        // Create cropped image
        $cropped = imagecrop($img, array(
            'x' => $crop_x,
            'y' => $crop_y,
            'width' => $crop_width,
            'height' => $crop_height
        ));

        if(!$cropped) return false;

        // Resize to final dimensions
        $thumb = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumb, $cropped, 0, 0, 0, 0, $width, $height, $crop_width, $crop_height);

        // Save thumbnail
        $dest_ext = strtolower(pathinfo($dest, PATHINFO_EXTENSION));
        $success = false;

        switch($dest_ext) {
            case 'jpg':
            case 'jpeg':
                $success = imagejpeg($thumb, $dest, 90);
                break;
            case 'png':
                $success = imagepng($thumb, $dest, 9);
                break;
            case 'gif':
                $success = imagegif($thumb, $dest);
                break;
            case 'webp':
                $success = imagewebp($thumb, $dest, 90);
                break;
            default:
                // Default to JPEG
                $success = imagejpeg($thumb, $dest, 90);
        }

        // Cleanup
        imagedestroy($img);
        imagedestroy($cropped);
        imagedestroy($thumb);

        return $success;
    } catch(Exception $e) {
        return false;
    }
}

echo "\n✨ Thumbnail generation complete!\n";
?>
