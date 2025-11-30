<?php
/**
 * Generate Thumbnails for Pump Images
 * Creates 235x235 and 530x530 crop thumbnails for product images
 */

define('DBHOST', 'localhost');
define('DBNAME', 'bombayengg');
define('DBUSER', 'bombayengg');
define('DBPASS', 'oCFCrCMwKyy5jzg');

$ROOTPATH = __DIR__;
define('UPLOADPATH', $ROOTPATH . '/uploads');

echo "=== Pump Image Thumbnail Generator ===\n";
echo "Starting at: " . date('Y-m-d H:i:s') . "\n\n";

// Create directories if they don't exist
$thumbDirs = [
    '235_235_crop_100',
    '530_530_crop_100'
];

foreach ($thumbDirs as $dir) {
    $dirPath = UPLOADPATH . '/pump/' . $dir;
    if (!is_dir($dirPath)) {
        mkdir($dirPath, 0755, true);
        echo "✓ Created directory: $dir\n";
    }
}
echo "\n";

// Connect to database
try {
    $conn = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    echo "✓ Database connected\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Get all products with images
$result = $conn->query("SELECT pumpID, pumpImage FROM mx_pump WHERE status=1 AND pumpImage != '' ORDER BY pumpID");

$processed = 0;
$failed = 0;
$skipped = 0;

while ($row = $result->fetch_assoc()) {
    $pumpImage = $row['pumpImage'];
    $pumpID = $row['pumpID'];

    // Check if source image exists
    $sourceFile = null;

    // Try crompton_images directory first
    $path1 = UPLOADPATH . '/pump/crompton_images/' . $pumpImage;
    if (file_exists($path1)) {
        $sourceFile = $path1;
    } else {
        // Try direct pump directory
        $path2 = UPLOADPATH . '/pump/' . $pumpImage;
        if (file_exists($path2)) {
            $sourceFile = $path2;
        }
    }

    if (!$sourceFile) {
        echo "[$pumpID] $pumpImage - ⚠️ SOURCE FILE NOT FOUND\n";
        $failed++;
        continue;
    }

    // Generate thumbnails
    echo "[$pumpID] $pumpImage... ";

    $success = true;

    // Generate 235x235 thumbnail
    $thumb235 = UPLOADPATH . '/pump/235_235_crop_100/' . $pumpImage;
    if (!file_exists($thumb235)) {
        if (!generateThumbnail($sourceFile, $thumb235, 235, 235)) {
            echo "✗ 235x235 failed";
            $success = false;
        }
    }

    // Generate 530x530 thumbnail
    $thumb530 = UPLOADPATH . '/pump/530_530_crop_100/' . $pumpImage;
    if (!file_exists($thumb530)) {
        if (!generateThumbnail($sourceFile, $thumb530, 530, 530)) {
            echo "✗ 530x530 failed";
            $success = false;
        }
    }

    if ($success) {
        echo "✓ OK\n";
        $processed++;
    } else {
        $failed++;
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY:\n";
echo "Processed: $processed\n";
echo "Failed: $failed\n";
echo "Total: " . ($processed + $failed) . "\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";

$conn->close();

/**
 * Generate a thumbnail from source image using GD library
 */
function generateThumbnail($sourceFile, $destFile, $width, $height) {
    try {
        // Get image info
        $imageInfo = @getimagesize($sourceFile);
        if (!$imageInfo) {
            return false;
        }

        $mime = $imageInfo['mime'];

        // Load image based on type
        switch ($mime) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($sourceFile);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($sourceFile);
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($sourceFile);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($sourceFile);
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        // Get original dimensions
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        // Calculate crop dimensions (square)
        $size = min($origWidth, $origHeight);
        $x = ($origWidth - $size) / 2;
        $y = ($origHeight - $size) / 2;

        // Create thumbnail
        $thumb = imagecreatetruecolor($width, $height);

        // Preserve transparency for PNG
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefill($thumb, 0, 0, $transparent);
        }

        // Copy and resize
        imagecopyresampled($thumb, $image, 0, 0, $x, $y, $width, $height, $size, $size);

        // Save thumbnail
        $ext = strtolower(pathinfo($destFile, PATHINFO_EXTENSION));

        if ($ext === 'webp' && function_exists('imagewebp')) {
            imagewebp($thumb, $destFile, 85);
        } elseif ($ext === 'png') {
            imagepng($thumb, $destFile, 9);
        } else {
            imagejpeg($thumb, $destFile, 85);
        }

        imagedestroy($image);
        imagedestroy($thumb);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

?>
