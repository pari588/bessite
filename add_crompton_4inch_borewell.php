<?php
/*
 * Script to add missing Crompton 4-inch Borewell Submersible Pumps
 * Category: 4-Inch Borewell (categoryPID: 28)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Change to script directory
chdir(__DIR__);

require_once(__DIR__ . "/config.inc.php");
require_once(__DIR__ . "/core/core.inc.php");

// Check if ImageMagick is available
$convert_available = shell_exec("which convert");
if (!$convert_available) {
    die("ERROR: ImageMagick (convert) not installed. Please install it first.\n");
}

// Products to add - Missing from existing 3 pumps
$products = array(
    // Water-filled pumps
    array(
        'pumpTitle' => '4W12BF1.5E',
        'pumpType' => 'Water-filled',
        'supplyPhase' => '1-Phase',
        'kwhp' => '1.5 HP',
        'categoryPID' => 28,
        'pumpFeatures' => '4 Inch Water-filled Borewell Submersible Pump. 1.5 HP capacity with voltage fluctuation handling. Suitable for residential and agricultural applications. Requires routine maintenance every 6-12 months.',
        'specifications' => array(
            array(
                'categoryref' => '4W12BF1.5E',
                'powerKw' => 1.1,
                'powerHp' => 1.5,
                'supplyPhaseD' => 1,
                'pipePhase' => 100,
                'noOfStageD' => 5,
                'headRange' => 60,
                'dischargeRange' => '1000-1200 LPH',
                'mrp' => '17,700/-',
                'warrenty' => '12 months'
            )
        ),
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/4W12BF1.5E.png'
    ),
    array(
        'pumpTitle' => '4W14BF1.5E',
        'pumpType' => 'Water-filled',
        'supplyPhase' => '1-Phase',
        'kwhp' => '1.5 HP',
        'categoryPID' => 28,
        'pumpFeatures' => '4 Inch Water-filled Borewell Submersible Pump. 1.5 HP capacity with extended head range. Voltage fluctuation tolerant design. Ideal for deeper borewells.',
        'specifications' => array(
            array(
                'categoryref' => '4W14BF1.5E',
                'powerKw' => 1.1,
                'powerHp' => 1.5,
                'supplyPhaseD' => 1,
                'pipePhase' => 100,
                'noOfStageD' => 7,
                'headRange' => 85,
                'dischargeRange' => '900-1100 LPH',
                'mrp' => '19,750/-',
                'warrenty' => '12 months'
            )
        ),
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/4W14BF1.5E.png'
    ),

    // Oil-filled pumps
    array(
        'pumpTitle' => '4VO1/7-BUE(U4S)',
        'pumpType' => 'Oil-filled',
        'supplyPhase' => '1-Phase',
        'kwhp' => '1 HP',
        'categoryPID' => 28,
        'pumpFeatures' => '4 Inch Oil-filled Borewell Submersible Pump. 1 HP capacity with superior longevity. Oil-filled design provides excellent voltage fluctuation handling and extended operational life. Black & silver finish.',
        'specifications' => array(
            array(
                'categoryref' => '4VO1/7-BUE(U4S)',
                'powerKw' => 0.75,
                'powerHp' => 1,
                'supplyPhaseD' => 1,
                'pipePhase' => 100,
                'noOfStageD' => 4,
                'headRange' => 50,
                'dischargeRange' => '800-1000 LPH',
                'mrp' => '12,850/-',
                'warrenty' => '12 months'
            )
        ),
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/4VO1-7-BUE.png'
    ),
    array(
        'pumpTitle' => '4VO1/10-BUE(U4S)',
        'pumpType' => 'Oil-filled',
        'supplyPhase' => '1-Phase',
        'kwhp' => '1 HP',
        'categoryPID' => 28,
        'pumpFeatures' => '4 Inch Oil-filled Borewell Submersible Pump. 1 HP capacity with extended head range. Robust oil-filled construction for improved durability and performance in challenging conditions.',
        'specifications' => array(
            array(
                'categoryref' => '4VO1/10-BUE(U4S)',
                'powerKw' => 0.75,
                'powerHp' => 1,
                'supplyPhaseD' => 1,
                'pipePhase' => 100,
                'noOfStageD' => 5,
                'headRange' => 65,
                'dischargeRange' => '700-900 LPH',
                'mrp' => '13,650/-',
                'warrenty' => '12 months'
            )
        ),
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/4VO1-10-BUE.png'
    ),
    array(
        'pumpTitle' => '4VO7BU1EU',
        'pumpType' => 'Oil-filled',
        'supplyPhase' => '1-Phase',
        'kwhp' => '1 HP',
        'categoryPID' => 28,
        'pumpFeatures' => '4 Inch Oil-filled Borewell Submersible Pump. 1 HP variant with compact 7-inch housing. Oil-filled design ensures excellent voltage fluctuation tolerance and long operational life.',
        'specifications' => array(
            array(
                'categoryref' => '4VO7BU1EU',
                'powerKw' => 0.75,
                'powerHp' => 1,
                'supplyPhaseD' => 1,
                'pipePhase' => 100,
                'noOfStageD' => 4,
                'headRange' => 50,
                'dischargeRange' => '800-1000 LPH',
                'mrp' => '12,850/-',
                'warrenty' => '12 months'
            )
        ),
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/4VO7BU1EU.png'
    ),
    array(
        'pumpTitle' => '4VO10BU1EU',
        'pumpType' => 'Oil-filled',
        'supplyPhase' => '1-Phase',
        'kwhp' => '1 HP',
        'categoryPID' => 28,
        'pumpFeatures' => '4 Inch Oil-filled Borewell Submersible Pump. 1 HP with 10-inch configuration. Premium oil-filled construction for superior durability in residential and agricultural applications.',
        'specifications' => array(
            array(
                'categoryref' => '4VO10BU1EU',
                'powerKw' => 0.75,
                'powerHp' => 1,
                'supplyPhaseD' => 1,
                'pipePhase' => 100,
                'noOfStageD' => 5,
                'headRange' => 65,
                'dischargeRange' => '700-900 LPH',
                'mrp' => '13,650/-',
                'warrenty' => '12 months'
            )
        ),
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/4VO10BU1EU.png'
    ),
    array(
        'pumpTitle' => '4VO1.5/12-BUE(U4S)',
        'pumpType' => 'Oil-filled',
        'supplyPhase' => '1-Phase',
        'kwhp' => '1.5 HP',
        'categoryPID' => 28,
        'pumpFeatures' => '4 Inch Oil-filled Borewell Submersible Pump. 1.5 HP capacity with 12-inch configuration. Excellent for deeper borewells with consistent voltage fluctuation handling.',
        'specifications' => array(
            array(
                'categoryref' => '4VO1.5/12-BUE(U4S)',
                'powerKw' => 1.1,
                'powerHp' => 1.5,
                'supplyPhaseD' => 1,
                'pipePhase' => 100,
                'noOfStageD' => 6,
                'headRange' => 75,
                'dischargeRange' => '1000-1200 LPH',
                'mrp' => '16,450/-',
                'warrenty' => '12 months'
            )
        ),
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/4VO1.5-12-BUE.png'
    ),
    array(
        'pumpTitle' => '4VO1.5/14-BUE(U4S)',
        'pumpType' => 'Oil-filled',
        'supplyPhase' => '1-Phase',
        'kwhp' => '1.5 HP',
        'categoryPID' => 28,
        'pumpFeatures' => '4 Inch Oil-filled Borewell Submersible Pump. 1.5 HP with 14-inch configuration for extended head range. Ideal for challenging borewell conditions with superior durability.',
        'specifications' => array(
            array(
                'categoryref' => '4VO1.5/14-BUE(U4S)',
                'powerKw' => 1.1,
                'powerHp' => 1.5,
                'supplyPhaseD' => 1,
                'pipePhase' => 100,
                'noOfStageD' => 7,
                'headRange' => 90,
                'dischargeRange' => '900-1100 LPH',
                'mrp' => '17,200/-',
                'warrenty' => '12 months'
            )
        ),
        'imageUrl' => 'https://www.crompton.co.in/cdn/shop/files/4VO1.5-14-BUE.png'
    )
);

// Function to download and convert image
function downloadAndConvertImage($imageUrl, $filename, $uploadDir) {
    $tempFile = $uploadDir . '/temp_' . $filename;
    $finalFile = $uploadDir . '/' . $filename;

    // Download image
    echo "Downloading image: $filename\n";
    $imageData = @file_get_contents($imageUrl);
    if (!$imageData) {
        echo "  WARNING: Could not download from URL: $imageUrl\n";
        // Create a placeholder image instead
        return createPlaceholderImage($finalFile);
    }

    // Save temp file
    file_put_contents($tempFile, $imageData);

    // Get original extension
    $pathInfo = pathinfo($imageUrl);
    $originalExt = $pathInfo['extension'] ?? 'png';
    $originalFile = $uploadDir . '/original_' . $filename . '.' . $originalExt;

    // Save original
    if (!file_exists($originalFile)) {
        copy($tempFile, $originalFile);
    }

    // Convert to WebP using ImageMagick
    echo "  Converting to WebP: $filename\n";
    $cmd = "convert " . escapeshellarg($tempFile) . " -quality 85 " . escapeshellarg($finalFile . ".webp") . " 2>&1";
    $output = shell_exec($cmd);

    if (file_exists($finalFile . ".webp")) {
        echo "  Success: {$filename}.webp created\n";
        unlink($tempFile);
        return $filename . ".webp";
    } else {
        echo "  ERROR converting image. Output: $output\n";
        return false;
    }
}

// Function to create placeholder image
function createPlaceholderImage($filePath) {
    $filename = basename($filePath);
    // Simple 530x530 gray placeholder image
    $cmd = "convert -size 530x530 xc:#cccccc -pointsize 24 -fill black -gravity center -annotate +0+0 '4-Inch Borewell\n{$filename}' " . escapeshellarg($filePath . ".webp") . " 2>&1";
    $output = shell_exec($cmd);

    if (file_exists($filePath . ".webp")) {
        echo "  Created placeholder image\n";
        return $filename . ".webp";
    }
    return false;
}

// Function to generate thumbnail
function generateThumbnail($sourceFile, $filename, $uploadDir) {
    $thumbDir = $uploadDir . '/235_235_crop_100';
    $largeDir = $uploadDir . '/530_530_crop_100';

    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }
    if (!is_dir($largeDir)) {
        mkdir($largeDir, 0755, true);
    }

    $baseName = pathinfo($filename, PATHINFO_FILENAME);

    // Generate 235x235 thumbnail
    $thumbFile = $thumbDir . '/' . $baseName . '.webp';
    if (!file_exists($thumbFile)) {
        $cmd = "convert " . escapeshellarg($sourceFile) . " -resize 235x235 -quality 90 " . escapeshellarg($thumbFile) . " 2>&1";
        shell_exec($cmd);
        if (file_exists($thumbFile)) {
            echo "  Thumbnail created: {$baseName}.webp (235x235)\n";
        }
    }

    // Generate 530x530 variant
    $largeFile = $largeDir . '/' . $baseName . '.webp';
    if (!file_exists($largeFile)) {
        $cmd = "convert " . escapeshellarg($sourceFile) . " -resize 530x530 -quality 90 " . escapeshellarg($largeFile) . " 2>&1";
        shell_exec($cmd);
        if (file_exists($largeFile)) {
            echo "  Large image created: {$baseName}.webp (530x530)\n";
        }
    }
}

// Main execution
echo "\n=== Crompton 4-Inch Borewell Submersibles - Batch Import ===\n\n";

$uploadDir = UPLOADPATH . "/pump";
$addedCount = 0;
$skipCount = 0;

foreach ($products as $product) {
    echo "\nProcessing: " . $product['pumpTitle'] . "\n";

    // Check if product already exists
    $DB->vals = array($product['pumpTitle']);
    $DB->types = "s";
    $DB->sql = "SELECT pumpID FROM " . $DB->pre . "pump WHERE pumpTitle = ?";
    $existing = $DB->dbRow();

    if ($existing) {
        echo "  SKIP: Product already exists (ID: {$existing['pumpID']})\n";
        $skipCount++;
        continue;
    }

    // Generate SEO-friendly filename
    $seoFilename = strtolower(str_replace(array('/', '(', ')', ' '), array('-', '', '', '-'), $product['pumpTitle']));
    $seoFilename = preg_replace('/-+/', '-', $seoFilename);
    $seoFilename = trim($seoFilename, '-');

    // Download and convert image
    $imageFilename = $seoFilename . '.webp';
    $downloadedImage = downloadAndConvertImage($product['imageUrl'], $imageFilename, $uploadDir);

    if (!$downloadedImage) {
        echo "  ERROR: Could not process image. Skipping product.\n";
        continue;
    }

    // Generate thumbnails if main image exists
    $sourceFile = $uploadDir . '/' . $downloadedImage;
    if (file_exists($sourceFile)) {
        generateThumbnail($sourceFile, $imageFilename, $uploadDir);
    }

    // Insert pump into database
    $seoUri = makeSeoUri($product['pumpTitle']);

    $insertData = array(
        'pumpTitle' => $product['pumpTitle'],
        'categoryPID' => $product['categoryPID'],
        'pumpImage' => $downloadedImage,
        'pumpFeatures' => $product['pumpFeatures'],
        'kwhp' => $product['kwhp'],
        'supplyPhase' => $product['supplyPhase'],
        'pumpType' => $product['pumpType'],
        'seoUri' => $seoUri,
        'status' => 1
    );

    $DB->table = $DB->pre . "pump";
    $DB->data = $insertData;

    if ($DB->dbInsert()) {
        $pumpID = $DB->insertID;
        echo "  ADDED: Pump inserted with ID: $pumpID\n";

        // Add specifications
        foreach ($product['specifications'] as $spec) {
            $spec['pumpID'] = $pumpID;
            $DB->table = $DB->pre . "pump_detail";
            $DB->data = $spec;
            if ($DB->dbInsert()) {
                echo "    - Specification added: {$spec['categoryref']}\n";
            }
        }

        $addedCount++;
    } else {
        echo "  ERROR: Could not insert pump into database\n";
    }
}

echo "\n=== Import Summary ===\n";
echo "Total Added: $addedCount\n";
echo "Total Skipped: $skipCount\n";
echo "Import completed!\n\n";
?>
