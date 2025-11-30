<?php
// Direct image processing - skip full app loading
$db_host = 'localhost';
$db_user = 'bombayengg';
$db_pass = 'oCFCrCMwKyy5jzg';
$db_name = 'bombayengg';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$upload_path = '/home/bombayengg/public_html/uploads/pump';

echo "=== Pump Image Processor ===\n\n";

// Get all active pumps
$result = $conn->query("SELECT pumpID, pumpTitle, pumpImage FROM mx_pump WHERE status=1 ORDER BY pumpID");
$pumps = $result->fetch_all(MYSQLI_ASSOC);

echo "Total pumps: " . count($pumps) . "\n\n";

$sizes = array(
    array("dir" => "235_235_crop_100", "w" => 235, "h" => 235),
    array("dir" => "530_530_crop_100", "w" => 530, "h" => 530)
);

$success = 0;
$error = 0;

foreach($pumps as $pump) {
    if(empty($pump['pumpImage'])) {
        echo "⚠️  Skip ID {$pump['pumpID']}: No image\n";
        continue;
    }

    $images = array_map('trim', explode(",", $pump['pumpImage']));

    foreach($images as $img) {
        $img = trim($img);
        if(empty($img)) continue;

        $src = $upload_path . "/" . $img;

        if(!file_exists($src)) {
            echo "❌ ID {$pump['pumpID']}: Source not found: $img\n";
            $error++;
            continue;
        }

        $processed = true;
        foreach($sizes as $size) {
            $dir = $upload_path . "/" . $size['dir'];
            $dest = $dir . "/" . $img;

            if(!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            if(!processImage($src, $dest, $size['w'], $size['h'])) {
                echo "❌ ID {$pump['pumpID']}: Failed to create {$size['dir']}/{$img}\n";
                $error++;
                $processed = false;
                break;
            }
        }

        if($processed) {
            echo "✅ ID {$pump['pumpID']}: {$pump['pumpTitle']} - {$img}\n";
            $success++;
        }
    }
}

echo "\n=== Results ===\n";
echo "✅ Processed: $success\n";
echo "❌ Errors: $error\n";

function processImage($src, $dest, $width, $height) {
    try {
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));

        // Load image based on type
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
                $img = @imagecreatefromwebp($src);
                break;
            default:
                return false;
        }

        if(!$img) return false;

        $orig_w = imagesx($img);
        $orig_h = imagesy($img);

        // Calculate crop to center
        $ratio = $orig_w / $orig_h;
        $desired_ratio = $width / $height;

        if($ratio > $desired_ratio) {
            // wider - crop width
            $crop_w = intval($orig_h * $desired_ratio);
            $crop_h = $orig_h;
            $crop_x = intval(($orig_w - $crop_w) / 2);
            $crop_y = 0;
        } else {
            // taller - crop height
            $crop_w = $orig_w;
            $crop_h = intval($orig_w / $desired_ratio);
            $crop_x = 0;
            $crop_y = intval(($orig_h - $crop_h) / 2);
        }

        $cropped = @imagecrop($img, ['x' => $crop_x, 'y' => $crop_y, 'width' => $crop_w, 'height' => $crop_h]);
        if(!$cropped) $cropped = $img;

        $thumb = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumb, $cropped, 0, 0, 0, 0, $width, $height, $crop_w, $crop_h);

        $dest_ext = strtolower(pathinfo($dest, PATHINFO_EXTENSION));
        $saved = false;

        switch($dest_ext) {
            case 'jpg':
            case 'jpeg':
                $saved = @imagejpeg($thumb, $dest, 90);
                break;
            case 'png':
                $saved = @imagepng($thumb, $dest, 9);
                break;
            case 'gif':
                $saved = @imagegif($thumb, $dest);
                break;
            case 'webp':
                $saved = @imagewebp($thumb, $dest, 90);
                break;
        }

        imagedestroy($img);
        if($cropped !== $img) imagedestroy($cropped);
        imagedestroy($thumb);

        return $saved;
    } catch(Exception $e) {
        return false;
    }
}

$conn->close();
echo "\n✨ Complete!\n";
?>
