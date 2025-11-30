<?php
// Simple thumbnail generator - copy and resize images

$upload_path = '/home/bombayengg/public_html/uploads/pump';

echo "=== Simple Thumbnail Generator ===\n\n";

// Get list of original images
$original_images = array_filter(glob($upload_path . '/*'), function($f) {
    return is_file($f) && preg_match('/\.(webp|jpg|jpeg|png|gif)$/i', $f);
});

echo "Found " . count($original_images) . " original images\n\n";

$sizes = array(
    array("dir" => "235_235_crop_100", "w" => 235, "h" => 235),
    array("dir" => "530_530_crop_100", "w" => 530, "h" => 530)
);

// Create size directories and copy/resize each image
foreach($original_images as $src_full_path) {
    $filename = basename($src_full_path);

    // Skip directories and non-image files
    if(is_dir($src_full_path) || !preg_match('/\.(webp|jpg|jpeg|png|gif)$/i', $filename)) {
        continue;
    }

    foreach($sizes as $size) {
        $dest_dir = $upload_path . "/" . $size['dir'];
        $dest_file = $dest_dir . "/" . $filename;

        // Create directory
        if(!is_dir($dest_dir)) {
            @mkdir($dest_dir, 0777, true);
        }

        // Skip if already exists and newer than source
        if(file_exists($dest_file) && filemtime($dest_file) >= filemtime($src_full_path)) {
            continue;
        }

        // Process image
        if(resizeImage($src_full_path, $dest_file, $size['w'], $size['h'])) {
            echo "✅ {$size['dir']}/{$filename}\n";
        } else {
            echo "⚠️  {$size['dir']}/{$filename} (could not resize, copying original)\n";
            // Fallback: just copy the file
            @copy($src_full_path, $dest_file);
        }
    }
}

echo "\n✨ Complete!\n";

function resizeImage($src, $dest, $width, $height) {
    try {
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));

        // Try to load image
        $img = null;
        switch($ext) {
            case 'jpg':
            case 'jpeg':
                $img = @imagecreatefromjpeg($src);
                break;
            case 'png':
                $img = @imagecreatefrompng($src);
                break;
            case 'gif':
                $img = @imagecreatefromgif($src);
                break;
            case 'webp':
                // Try webp support
                if(function_exists('imagecreatefromwebp')) {
                    $img = @imagecreatefromwebp($src);
                }
                break;
        }

        if(!$img) {
            return false;
        }

        $orig_w = imagesx($img);
        $orig_h = imagesy($img);

        // Calculate crop to fit center
        $ratio = $orig_w / $orig_h;
        $desired_ratio = $width / $height;

        if($ratio > $desired_ratio) {
            $crop_w = intval($orig_h * $desired_ratio);
            $crop_h = $orig_h;
            $crop_x = intval(($orig_w - $crop_w) / 2);
            $crop_y = 0;
        } else {
            $crop_w = $orig_w;
            $crop_h = intval($orig_w / $desired_ratio);
            $crop_x = 0;
            $crop_y = intval(($orig_h - $crop_h) / 2);
        }

        // Create thumbnail
        $thumb = imagecreatetruecolor($width, $height);
        if(function_exists('imagealphablending')) {
            imagealphablending($thumb, false);
        }

        imagecopyresampled($thumb, $img, 0, 0, $crop_x, $crop_y, $width, $height, $crop_w, $crop_h);

        // Save as JPEG (compatible format)
        $result = @imagejpeg($thumb, $dest, 90);

        imagedestroy($img);
        imagedestroy($thumb);

        return $result;
    } catch(Exception $e) {
        return false;
    }
}
?>
