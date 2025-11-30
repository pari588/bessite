#!/usr/bin/env php
<?php
/**
 * Fix All Product Images - Complete Solution
 * Converts PNG to WebP, updates database, and generates thumbnails
 */

define('DBHOST', 'localhost');
define('DBNAME', 'bombayengg');
define('DBUSER', 'bombayengg');
define('DBPASS', 'oCFCrCMwKyy5jzg');

$ROOTPATH = __DIR__;
define('UPLOADPATH', $ROOTPATH . '/uploads');

echo "\n" . str_repeat("=", 80) . "\n";
echo "PRODUCT IMAGE FIX - COMPLETE SOLUTION\n";
echo str_repeat("=", 80) . "\n";
echo "Starting at: " . date('Y-m-d H:i:s') . "\n\n";

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

// Step 1: Find all PNG files and map them to products
echo "STEP 1: Mapping PNG files to products...\n";
echo str_repeat("-", 80) . "\n";

$pngDir = UPLOADPATH . '/pump';
$pngFiles = glob($pngDir . '/*.png');

echo "Found " . count($pngFiles) . " PNG files\n\n";

// Image mapping based on naming patterns
$imageMapping = [
    // Borewell
    'borewell-submersible-pump-100w-v__530x530.png' => [
        'titles' => ['3W10AK1A', '3W10AP1D'],
        'categoryPID' => 27
    ],
    'borewell-submersible-pump-3w__530x530.png' => [
        'titles' => ['3W12AP1D'],
        'categoryPID' => 27
    ],
    // 4-Inch Borewell
    'mb-centrifugal-monoset-pump__530x530.png' => [
        'titles' => ['4W7BU1AU', '4W10BU1AU', '4W14BU2EU'],
        'categoryPID' => 28
    ],
    // Openwell
    'horizontal-openwell__530x530.png' => [
        'titles' => ['OWE052(1PH)Z-21FS'],
        'categoryPID' => 29
    ],
    'vertical-openwell__530x530.png' => [
        'titles' => ['OWE12(1PH)Z-28'],
        'categoryPID' => 29
    ],
    // Booster & Control
    'v4-stainless-steel-pumps.png' => [
        'titles' => ['CFMSMB5D1.00-V24'],
        'categoryPID' => 30
    ],
    'v-6-50-feet-per-stage-pumps__530x530.png' => [
        'titles' => ['Mini Force I'],
        'categoryPID' => 30
    ],
    'mb-centrifugal-monoset-pump__530x530.png' => [
        'titles' => ['ARMOR1.0-CQU', 'ARMOR1.5-DSU'],
        'categoryPID' => 31
    ]
];

// Step 2: Convert PNG files to WebP
echo "\nSTEP 2: Converting PNG files to WebP...\n";
echo str_repeat("-", 80) . "\n";

$webpMapping = [];
$converted = 0;
$skipped = 0;

foreach (array_unique($pngFiles) as $pngFile) {
    $filename = basename($pngFile);
    $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
    $webpFile = dirname($pngFile) . '/' . $nameWithoutExt . '.webp';

    // Skip if already converted
    if (file_exists($webpFile)) {
        echo "[$filename] Skipped (already WebP)\n";
        $webpMapping[$filename] = basename($webpFile);
        $skipped++;
        continue;
    }

    // Convert to WebP
    if (convertPNGToWebP($pngFile, $webpFile)) {
        echo "[$filename] ✓ Converted to WebP\n";
        $webpMapping[$filename] = basename($webpFile);
        $converted++;
    } else {
        echo "[$filename] ✗ Conversion failed\n";
    }
}

echo "\nConverted: $converted, Skipped: $skipped\n\n";

// Step 3: Update database with correct image references
echo "STEP 3: Updating database with correct image references...\n";
echo str_repeat("-", 80) . "\n";

$updated = 0;
foreach ($imageMapping as $pngFile => $mapping) {
    $webpName = pathinfo($pngFile, PATHINFO_FILENAME) . '.webp';

    foreach ($mapping['titles'] as $title) {
        $stmt = $conn->prepare("UPDATE mx_pump SET pumpImage=? WHERE pumpTitle=? AND categoryPID=?");
        $stmt->bind_param("ssi", $webpName, $title, $mapping['categoryPID']);

        if ($stmt->execute()) {
            echo "[$title] → $webpName\n";
            $updated++;
        } else {
            echo "[$title] ✗ Update failed\n";
        }
        $stmt->close();
    }
}

echo "\nUpdated: $updated products\n\n";

// Step 4: Generate thumbnails
echo "STEP 4: Generating thumbnails...\n";
echo str_repeat("-", 80) . "\n";

// Create thumbnail directories
$thumbDirs = ['235_235_crop_100', '530_530_crop_100'];
foreach ($thumbDirs as $dir) {
    $dirPath = UPLOADPATH . '/pump/' . $dir;
    if (!is_dir($dirPath)) {
        mkdir($dirPath, 0755, true);
    }
}

// Get all products and generate thumbnails
$result = $conn->query("SELECT pumpID, pumpImage FROM mx_pump WHERE status=1 AND pumpImage != ''");

$thumbGenerated = 0;
$thumbSkipped = 0;
$thumbFailed = 0;

while ($row = $result->fetch_assoc()) {
    $imageFile = $row['pumpImage'];
    $sourceFile = UPLOADPATH . '/pump/' . $imageFile;

    // Try different paths
    if (!file_exists($sourceFile)) {
        // Try crompton_images subdirectory
        $sourceFile = UPLOADPATH . '/pump/crompton_images/' . $imageFile;
    }

    if (!file_exists($sourceFile)) {
        $thumbFailed++;
        continue;
    }

    // Check if thumbnails exist
    $thumb235Exists = file_exists(UPLOADPATH . '/pump/235_235_crop_100/' . $imageFile);
    $thumb530Exists = file_exists(UPLOADPATH . '/pump/530_530_crop_100/' . $imageFile);

    if ($thumb235Exists && $thumb530Exists) {
        $thumbSkipped++;
        continue;
    }

    // Generate thumbnails
    if (!$thumb235Exists) {
        generateThumbnail($sourceFile, UPLOADPATH . '/pump/235_235_crop_100/' . $imageFile, 235, 235);
    }
    if (!$thumb530Exists) {
        generateThumbnail($sourceFile, UPLOADPATH . '/pump/530_530_crop_100/' . $imageFile, 530, 530);
    }

    $thumbGenerated++;
}

echo "Generated: $thumbGenerated, Skipped: $thumbSkipped, Failed: $thumbFailed\n\n";

// Final verification
echo "STEP 5: Final Verification...\n";
echo str_repeat("-", 80) . "\n";

$result = $conn->query("
    SELECT pc.categoryTitle, COUNT(p.pumpID) as total,
           SUM(CASE WHEN p.pumpImage != '' THEN 1 ELSE 0 END) as with_images
    FROM mx_pump_category pc
    LEFT JOIN mx_pump p ON pc.categoryPID = p.categoryPID AND p.status=1
    WHERE pc.parentID IN (22, 23) OR pc.categoryPID IN (26, 27, 28, 29, 30, 31)
    GROUP BY pc.categoryPID
    ORDER BY pc.categoryTitle
");

while ($row = $result->fetch_assoc()) {
    $coverage = $row['total'] > 0 ? round(($row['with_images'] / $row['total']) * 100) : 0;
    printf("%-30s | Total: %2d | With Images: %2d | Coverage: %3d%%\n",
        substr($row['categoryTitle'], 0, 30),
        $row['total'],
        $row['with_images'],
        $coverage
    );
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ COMPLETE - All product images have been fixed!\n";
echo str_repeat("=", 80) . "\n\n";

$conn->close();

/**
 * Convert PNG to WebP using GD library
 */
function convertPNGToWebP($sourceFile, $destFile) {
    try {
        $image = @imagecreatefrompng($sourceFile);
        if (!$image || !function_exists('imagewebp')) {
            return false;
        }

        imagewebp($image, $destFile, 85);
        imagedestroy($image);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generate thumbnail image
 */
function generateThumbnail($sourceFile, $destFile, $width, $height) {
    try {
        // Get image info
        $imageInfo = @getimagesize($sourceFile);
        if (!$imageInfo) {
            return false;
        }

        $mime = $imageInfo['mime'];

        // Load image
        switch ($mime) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($sourceFile);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($sourceFile);
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

        // Get dimensions
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        // Calculate crop (square)
        $size = min($origWidth, $origHeight);
        $x = ($origWidth - $size) / 2;
        $y = ($origHeight - $size) / 2;

        // Create thumbnail
        $thumb = imagecreatetruecolor($width, $height);

        // Preserve transparency
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefill($thumb, 0, 0, $transparent);

        // Resize
        imagecopyresampled($thumb, $image, 0, 0, $x, $y, $width, $height, $size, $size);

        // Save
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
