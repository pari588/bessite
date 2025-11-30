<?php
/**
 * Import Missing Crompton Products and Images
 * This script extracts missing products from Crompton website and imports them with images
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.inc.php';

// Create directories
$uploadDir = UPLOADPATH . '/pump';
$imageDir = $uploadDir . '/crompton_images';
if (!is_dir($imageDir)) {
    mkdir($imageDir, 0755, true);
}

// Products data extracted from Crompton website
// https://www.crompton.co.in/collections/pumps (Mini Self-Priming Regenerative Pumps)
$productsData = [
    [
        'title' => 'MINI MASTER II',
        'model' => 'MINI MASTER II',
        'kwhp' => '0.5',
        'categoryPID' => 24,  // Mini Pumps
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MINI_MASTERPLUS_II_a51e57a3-3b7e-4cf5-9d80-5f01a6c4fd41.png'
    ],
    [
        'title' => 'CHAMP PLUS II',
        'model' => 'CHAMP PLUS II',
        'kwhp' => '0.5',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/CHAMP_PLUS_I_58f96b15-dcf3-4c13-8ea8-b79e0ad83c33.png'
    ],
    [
        'title' => 'MINI MASTERPLUS II',
        'model' => 'MINI MASTERPLUS II',
        'kwhp' => '0.5',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MINI_MASTERPLUS_II_a51e57a3-3b7e-4cf5-9d80-5f01a6c4fd41.png'
    ],
    [
        'title' => 'MINI MARVEL II',
        'model' => 'MINI MARVEL II',
        'kwhp' => '0.5',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MINI_MARVEL_I_dfe93c01-b0a8-47ce-b6f4-37f5c30d28b2.png'
    ],
    [
        'title' => 'MINI CREST II',
        'model' => 'MINI CREST II',
        'kwhp' => '0.5',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MINI_CREST_I_eb82cb52-f008-49d6-a3c4-3dd33f7bf1e4.png'
    ],
    [
        'title' => 'AQUAGOLD 50-30',
        'model' => 'AQUAGOLD 50-30',
        'kwhp' => '0.5',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/AQUAGOLD_50_30_7ad0d4d2-f6d2-414e-9ea9-37c6fef76e23.png'
    ],
    [
        'title' => 'AQUAGOLD 100-33',
        'model' => 'AQUAGOLD 100-33',
        'kwhp' => '1.0',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/AQUAGOLD_100_33_6c8d3af0-0bab-4c5d-8d62-a9a69e5ed0ab.png'
    ],
    [
        'title' => 'AQUAGOLD 150',
        'model' => 'AQUAGOLD 150',
        'kwhp' => '1.5',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/AQUAGOLD_150_94c07c71-09c6-4dba-a3fe-e35da7c996a9.png'
    ],
    [
        'title' => 'FLOMAX PLUS II',
        'model' => 'FLOMAX PLUS II',
        'kwhp' => '0.5',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/FLOMAX_PLUS_I_e10c81cb-d1f2-4dba-b8d2-99c9c7cd9aac.png'
    ],
    [
        'title' => 'MASTER DURA II',
        'model' => 'MASTER DURA II',
        'kwhp' => '0.5',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MASTER_DURA_I_2c969d3b-79b0-4bae-bd13-5ccb2c5f4cdc.png'
    ],
    [
        'title' => 'MASTER PLUS II',
        'model' => 'MASTER PLUS II',
        'kwhp' => '0.5',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MASTER_PLUS_I_c5883c93-5a2e-49c2-b8b1-32dc37dae8d5.png'
    ],
    [
        'title' => 'STAR PLUS II',
        'model' => 'STAR PLUS II',
        'kwhp' => '0.5',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/STAR_PLUS_I_6e0aeacb-5e37-4c06-8d41-46c86fc4c5a5.png'
    ],
    [
        'title' => 'CHAMP DURA II',
        'model' => 'CHAMP DURA II',
        'kwhp' => '0.5',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/CHAMP_DURA_I_98dd4aa4-7a95-4533-8d7e-b49a2f73a2d8.png'
    ]
];

// Function to sanitize filename
function sanitizeFilename($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

// Function to download image
function downloadImage($imageUrl, $filename) {
    global $imageDir;

    if (empty($imageUrl)) {
        return false;
    }

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

// Function to convert image to WebP
function convertToWebP($sourcePath, $filename) {
    $webpPath = dirname($sourcePath) . '/' . $filename . '.webp';

    if (!file_exists($sourcePath)) {
        return false;
    }

    try {
        if (extension_loaded('imagick')) {
            $image = new Imagick($sourcePath);
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(85);
            $image->writeImage($webpPath);
            $image->destroy();
        } else {
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
                unlink($sourcePath);
                return false;
            }

            imagewebp($image, $webpPath, 85);
            imagedestroy($image);
        }

        unlink($sourcePath);
        return $webpPath;
    } catch (Exception $e) {
        return false;
    }
}

// Function to check if product exists
function productExists($title, $categoryPID) {
    global $DB;

    $DB->vals = array(1, $title, $categoryPID);
    $DB->types = "isi";
    $DB->sql = "SELECT pumpID FROM mx_pump WHERE status=? AND pumpTitle=? AND categoryPID=?";
    $result = $DB->dbRow();

    return !empty($result) ? $result['pumpID'] : false;
}

// Main import process
$output = "=== Crompton Products Import Report ===\n";
$output .= date('Y-m-d H:i:s') . "\n";
$output .= "Total Products to Process: " . count($productsData) . "\n\n";

$imported = 0;
$skipped = 0;
$failed = 0;

error_log("Starting import process: " . count($productsData) . " products");

foreach ($productsData as $product) {
    $output .= "Processing: {$product['title']}... ";

    // Check if product already exists
    if (productExists($product['title'], $product['categoryPID'])) {
        $output .= "SKIPPED (already exists)\n";
        $skipped++;
        continue;
    }

    // Download and convert image
    $imageName = sanitizeFilename($product['title']);
    $imageFile = downloadImage($product['image_url'], $imageName);

    if ($imageFile) {
        $webpFile = convertToWebP($imageFile, $imageName);
        if ($webpFile) {
            $product['image_file'] = 'crompton_images/' . basename($webpFile);
        } else {
            $product['image_file'] = '';
            $output .= "IMAGE CONVERSION FAILED";
        }
    } else {
        $product['image_file'] = '';
        $output .= "IMAGE DOWNLOAD FAILED";
    }

    // Insert into database
    try {
        $DB->vals = array(
            $product['categoryPID'],
            $product['title'],
            '',  // seoUri - will be generated by system
            $product['image_file'],
            '',  // pumpFeatures
            $product['kwhp'],
            '',  // supplyPhase
            '',  // deliveryPipe
            '',  // noOfStage
            '',  // isi
            '',  // mnre
            $product['pumpType'],
            1    // status
        );

        $DB->types = "issssssssssi";
        $DB->sql = "INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, status)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $DB->dbQuery();

        $output .= "✓ IMPORTED\n";
        $imported++;
    } catch (Exception $e) {
        $output .= "✗ FAILED TO INSERT\n";
        $failed++;
    }
}

$output .= "\n=== Summary ===\n";
$output .= "Imported: $imported\n";
$output .= "Skipped: $skipped\n";
$output .= "Failed: $failed\n";
$output .= "Total: " . (count($productsData)) . "\n";

echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Save log
file_put_contents($uploadDir . '/import_log_' . date('YmdHis') . '.txt', $output);

?>
