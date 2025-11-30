<?php
/**
 * CLI Import Script for Missing Crompton Products
 * Run from command line: php import_products_cli.php
 */

// Define constants directly for CLI
define('DBHOST', 'localhost');
define('DBNAME', 'bombayengg');
define('DBUSER', 'bombayengg');
define('DBPASS', 'oCFCrCMwKyy5jzg');

$ROOTPATH = __DIR__;
define('UPLOADPATH', $ROOTPATH . '/uploads');
define('UPLOADURL', 'https://www.crompton.co.in/uploads');

echo "=== Crompton Products Import Script ===\n";
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
    echo "✓ Created upload directory\n";
}

if (!is_dir($imageDir)) {
    mkdir($imageDir, 0755, true);
    echo "✓ Created image directory\n";
}

// Products data
$productsData = [
    [
        'title' => 'MINI MASTER II',
        'model' => 'MINI MASTER II',
        'kwhp' => '0.5',
        'categoryPID' => 24,
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
        CURLOPT_USERAGENT => 'Mozilla/5.0'
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
        // Use GD library (no Imagick available)
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

echo "\nProcessing " . count($productsData) . " products...\n";
echo str_repeat("-", 80) . "\n";

$imported = 0;
$skipped = 0;
$failed = 0;

foreach ($productsData as $idx => $product) {
    $num = $idx + 1;
    echo "[$num/" . count($productsData) . "] {$product['title']}... ";

    // Check if exists
    $stmt = $conn->prepare("SELECT pumpID FROM mx_pump WHERE status=1 AND pumpTitle=? AND categoryPID=?");
    $stmt->bind_param("si", $product['title'], $product['categoryPID']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "SKIPPED (exists)\n";
        $skipped++;
        $stmt->close();
        continue;
    }
    $stmt->close();

    // Download image
    $imageName = sanitizeFilename($product['title']);
    $imageFile = downloadImage($product['image_url'], $imageName, $imageDir);

    if ($imageFile) {
        $webpFile = convertToWebP($imageFile, $imageName, $imageDir);
        if ($webpFile) {
            $imageRelPath = 'crompton_images/' . basename($webpFile);
        } else {
            $imageRelPath = '';
        }
    } else {
        $imageRelPath = '';
    }

    // Insert product
    $stmt = $conn->prepare(
        "INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $seoUri = '';
    $pumpFeatures = '';
    $supplyPhase = '';
    $deliveryPipe = '';
    $noOfStage = '';
    $isi = '';
    $mnre = '';
    $status = 1;

    $stmt->bind_param(
        "isssssssssssi",
        $product['categoryPID'],
        $product['title'],
        $seoUri,
        $imageRelPath,
        $pumpFeatures,
        $product['kwhp'],
        $supplyPhase,
        $deliveryPipe,
        $noOfStage,
        $isi,
        $mnre,
        $product['pumpType'],
        $status
    );

    if ($stmt->execute()) {
        echo "✓ IMPORTED\n";
        $imported++;
    } else {
        echo "✗ FAILED (" . $stmt->error . ")\n";
        $failed++;
    }

    $stmt->close();
}

echo str_repeat("-", 80) . "\n";
echo "\n=== Import Summary ===\n";
echo "Imported: $imported\n";
echo "Skipped: $skipped\n";
echo "Failed: $failed\n";
echo "Total Processed: " . count($productsData) . "\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";

$conn->close();

?>
