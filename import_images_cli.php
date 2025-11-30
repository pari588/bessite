<?php
/**
 * Download and Update Product Images
 * Run from command line: php import_images_cli.php
 */

// Define constants directly for CLI
define('DBHOST', 'localhost');
define('DBNAME', 'bombayengg');
define('DBUSER', 'bombayengg');
define('DBPASS', 'oCFCrCMwKyy5jzg');

$ROOTPATH = __DIR__;
define('UPLOADPATH', $ROOTPATH . '/uploads');

echo "=== Crompton Product Images Download Script ===\n";
echo "Starting at: " . date('Y-m-d H:i:s') . "\n\n";

// Connect to database
try {
    $conn = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    echo "✓ Database connected\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Create upload directory
$uploadDir = UPLOADPATH . '/pump';
$imageDir = $uploadDir . '/crompton_images';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!is_dir($imageDir)) {
    mkdir($imageDir, 0755, true);
}

// Image URL mapping for newly imported products
$imageUpdates = [
    'MINI MASTER II' => 'https://www.crompton.co.in/cdn/shop/files/Untitled-1_84df6464-2ee1-43d6-94c9-3a8615377bc3.png?v=1694613950',
    'CHAMP PLUS II' => 'https://www.crompton.co.in/cdn/shop/files/Untitled-1_ecb93f8f-9284-4569-9286-ea889cecd861.png?v=1694613709',
    'MINI MASTERPLUS II' => 'https://www.crompton.co.in/cdn/shop/files/Untitled-1_8c989372-2653-48a9-9e9c-5abb66b0cc53.png?v=1694613449',
    'MINI MARVEL II' => 'https://www.crompton.co.in/cdn/shop/files/MiniMarvel_1_955242ef-ded0-42ed-a751-2dcccf6f75d6.png?v=1693488472',
    'MINI CREST II' => 'https://www.crompton.co.in/cdn/shop/files/MINI_CREST_I_eb82cb52-f008-49d6-a3c4-3dd33f7bf1e4.png',
    'AQUAGOLD 50-30' => 'https://www.crompton.co.in/cdn/shop/files/Aquagold_6dd9b04b-5619-495a-80bc-ee16d558fed9.png?v=1693487590',
    'AQUAGOLD 100-33' => 'https://www.crompton.co.in/cdn/shop/files/Untitled-1_6c8d3af0-0bab-4c5d-8d62-a9a69e5ed0ab.png',
    'FLOMAX PLUS II' => 'https://www.crompton.co.in/cdn/shop/files/Untitled-1_de0db784-3ec4-4ebd-9dcd-92a4e5f786cc.png?v=1694613824',
    'MASTER DURA II' => 'https://www.crompton.co.in/cdn/shop/files/MASTER_DURA_I_2c969d3b-79b0-4bae-bd13-5ccb2c5f4cdc.png',
    'MASTER PLUS II' => 'https://www.crompton.co.in/cdn/shop/files/MASTER_PLUS_I_c5883c93-5a2e-49c2-b8b1-32dc37dae8d5.png',
    'STAR PLUS II' => 'https://www.crompton.co.in/cdn/shop/files/STAR_PLUS_I_6e0aeacb-5e37-4c06-8d41-46c86fc4c5a5.png',
    'CHAMP DURA II' => 'https://www.crompton.co.in/cdn/shop/files/CHAMP_DURA_I_98dd4aa4-7a95-4533-8d7e-b49a2f73a2d8.png',
];

function sanitizeFilename($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function downloadImage($imageUrl, $filename, $imageDir) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $imageUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200 || empty($imageData)) {
        return false;
    }

    $filepath = $imageDir . '/' . $filename . '.png';
    return file_put_contents($filepath, $imageData) ? $filepath : false;
}

function convertToWebP($sourcePath, $filename, $imageDir) {
    $webpPath = $imageDir . '/' . $filename . '.webp';

    if (!file_exists($sourcePath)) {
        return false;
    }

    try {
        // Use GD library
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $mime = $imageInfo['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        if (!$image || !function_exists('imagewebp')) {
            @unlink($sourcePath);
            return false;
        }

        imagewebp($image, $webpPath, 85);
        imagedestroy($image);
        @unlink($sourcePath);
        return $webpPath;
    } catch (Exception $e) {
        return false;
    }
}

echo "\nDownloading images for " . count($imageUpdates) . " products...\n";
echo str_repeat("-", 80) . "\n";

$updated = 0;
$failed = 0;

foreach ($imageUpdates as $productTitle => $imageUrl) {
    echo "[$productTitle]... ";

    // Download image
    $imageName = sanitizeFilename($productTitle);
    $imageFile = downloadImage($imageUrl, $imageName, $imageDir);

    if (!$imageFile) {
        echo "✗ DOWNLOAD FAILED\n";
        $failed++;
        continue;
    }

    // Convert to WebP
    $webpFile = convertToWebP($imageFile, $imageName, $imageDir);

    if (!$webpFile) {
        echo "✗ CONVERSION FAILED\n";
        $failed++;
        continue;
    }

    // Update database
    $imageRelPath = 'crompton_images/' . basename($webpFile);

    $stmt = $conn->prepare("UPDATE mx_pump SET pumpImage=? WHERE pumpTitle=? AND categoryPID=24");
    $stmt->bind_param("ss", $imageRelPath, $productTitle);

    if ($stmt->execute()) {
        echo "✓ UPDATED\n";
        $updated++;
    } else {
        echo "✗ DB UPDATE FAILED\n";
        $failed++;
    }

    $stmt->close();
}

echo str_repeat("-", 80) . "\n";
echo "\n=== Summary ===\n";
echo "Updated: $updated\n";
echo "Failed: $failed\n";
echo "Total: " . count($imageUpdates) . "\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";

$conn->close();

?>
