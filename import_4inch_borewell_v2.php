<?php
/**
 * Import 4-Inch Borewell Submersible Pumps from Crompton
 * Downloads PNG images and converts to WebP using GD library
 */

require_once("config.inc.php");

$upload_path = '/home/bombayengg/public_html/uploads/pump';
$temp_path = '/tmp/crompton_pump_images';

// Create temp directory
if (!is_dir($temp_path)) {
    mkdir($temp_path, 0755, true);
}

echo "=== Importing 4-Inch Borewell Submersible Pumps ===\n\n";

// Product data from Crompton website
$products = array(
    array(
        'title' => '4 inch Water filled Borewell Submersible Pump 0.75 HP',
        'model' => '4W7BU1AU',
        'kwhp' => '0.75 HP',
        'supplyPhase' => '1PH',
        'pumpType' => '4-Inch Water-Filled Borewell Submersible',
        'features' => '<p><strong>4W7BU1AU - 4 inch Water-Filled Borewell Submersible Pump</strong></p><ul><li>Power Rating: 0.75 HP</li><li>Pump Type: Water-Filled Submersible</li><li>Bore Size: 4 Inch</li><li>Single Phase Operation</li><li>Advanced motor design for deep bore wells</li><li>Thermally protected motor windings</li><li>High efficiency impeller design</li><li>Corrosion resistant body</li><li>Suitable for agricultural applications</li></ul>',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/4w-png.png',
        'image_name' => '4w7bu1au.webp'
    ),
    array(
        'title' => '4 inch Water filled Borewell Submersible Pump 1 HP',
        'model' => '4W10BU1AU',
        'kwhp' => '1 HP',
        'supplyPhase' => '1PH',
        'pumpType' => '4-Inch Water-Filled Borewell Submersible',
        'features' => '<p><strong>4W10BU1AU - 4 inch Water-Filled Borewell Submersible Pump 1 HP</strong></p><ul><li>Power Rating: 1 HP / 0.75 KW</li><li>Pump Type: Water-Filled Submersible</li><li>Bore Size: 4 Inch</li><li>Single Phase Operation</li><li>Enhanced cooling system with water-filled design</li><li>Robust construction for durability</li><li>Suitable for deep boreholes</li><li>Energy efficient motor</li><li>High discharge capacity</li></ul>',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/4w-png.png',
        'image_name' => '4w10bu1au.webp'
    ),
    array(
        'title' => '4 inch Water filled Borewell Submersible Pump 1.5 HP (BF)',
        'model' => '4W12BF1.5E',
        'kwhp' => '1.5 HP',
        'supplyPhase' => '1PH',
        'pumpType' => '4-Inch Water-Filled Borewell Submersible',
        'features' => '<p><strong>4W12BF1.5E - 4 inch Water-Filled Borewell Submersible Pump 1.5 HP</strong></p><ul><li>Power Rating: 1.5 HP / 1.1 KW</li><li>Pump Type: Water-Filled Submersible</li><li>Bore Size: 4 Inch</li><li>Single Phase Operation</li><li>Advanced water cooling mechanism</li><li>Stainless steel components</li><li>High performance impellers</li><li>Suitable for agricultural and residential applications</li><li>Extended lifespan design</li></ul>',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/4w-png.png',
        'image_name' => '4w12bf1-5e.webp'
    ),
    array(
        'title' => '4 inch Water filled Borewell Submersible Pump 1.5 HP (BF)',
        'model' => '4W14BF1.5E',
        'kwhp' => '1.5 HP',
        'supplyPhase' => '1PH',
        'pumpType' => '4-Inch Water-Filled Borewell Submersible',
        'features' => '<p><strong>4W14BF1.5E - 4 inch Water-Filled Borewell Submersible Pump</strong></p><ul><li>Power Rating: 1.5 HP / 1.1 KW</li><li>Pump Type: Water-Filled Submersible</li><li>Bore Size: 4 Inch</li><li>Single Phase Operation</li><li>Premium water-filled cooling system</li><li>High discharge capacity</li><li>Suitable for large bore wells</li><li>Enhanced motor protection</li><li>Professional grade construction</li></ul>',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/4w-png.png',
        'image_name' => '4w14bf1-5e.webp'
    ),
    array(
        'title' => '4 inch Water filled Borewell Submersible Pump 2 HP',
        'model' => '4W14BU2EU',
        'kwhp' => '2 HP',
        'supplyPhase' => '1PH',
        'pumpType' => '4-Inch Water-Filled Borewell Submersible',
        'features' => '<p><strong>4W14BU2EU - 4 inch Water-Filled Borewell Submersible Pump 2 HP</strong></p><ul><li>Power Rating: 2 HP / 1.5 KW</li><li>Pump Type: Water-Filled Submersible</li><li>Bore Size: 4 Inch</li><li>Single Phase Operation</li><li>Maximum discharge performance</li><li>Superior water cooling efficiency</li><li>Heavy-duty construction</li><li>Ideal for commercial applications</li><li>Extended warranty coverage</li></ul>',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/4w-png.png',
        'image_name' => '4w14bu2eu.webp'
    ),
    array(
        'title' => '4 inch Oil filled Borewell Submersible Pump 0.75 HP',
        'model' => '4VO7BU1EU',
        'kwhp' => '0.75 HP',
        'supplyPhase' => '1PH',
        'pumpType' => '4-Inch Oil-Filled Borewell Submersible',
        'features' => '<p><strong>4VO7BU1EU - 4 inch Oil-Filled Borewell Submersible Pump</strong></p><ul><li>Power Rating: 0.75 HP / 0.55 KW</li><li>Pump Type: Oil-Filled Submersible</li><li>Bore Size: 4 Inch</li><li>Single Phase Operation</li><li>Oil-cooled motor for extended durability</li><li>Sealed design for protection</li><li>Compact and lightweight</li><li>Economical operation</li><li>Suitable for bore wells up to 100 meters</li></ul>',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/artboard1_277b9f24-ac06-4c37-835f-ff388004917b.png',
        'image_name' => '4vo7bu1eu.webp'
    ),
    array(
        'title' => '4 inch Oil filled Borewell Submersible Pump 1 HP',
        'model' => '4VO10BU1EU',
        'kwhp' => '1 HP',
        'supplyPhase' => '1PH',
        'pumpType' => '4-Inch Oil-Filled Borewell Submersible',
        'features' => '<p><strong>4VO10BU1EU - 4 inch Oil-Filled Borewell Submersible Pump 1 HP</strong></p><ul><li>Power Rating: 1 HP / 0.75 KW</li><li>Pump Type: Oil-Filled Submersible</li><li>Bore Size: 4 Inch</li><li>Single Phase Operation</li><li>Superior oil cooling mechanism</li><li>Reliable performance in varied conditions</li><li>Long service life</li><li>Low maintenance requirements</li><li>Ideal for agricultural applications</li></ul>',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/artboard1_277b9f24-ac06-4c37-835f-ff388004917b.png',
        'image_name' => '4vo10bu1eu.webp'
    ),
    array(
        'title' => '4 inch Oil filled Borewell Submersible Pump 1 HP (U4S)',
        'model' => '4VO1/10-BUE(U4S)',
        'kwhp' => '1 HP',
        'supplyPhase' => '1PH',
        'pumpType' => '4-Inch Oil-Filled Borewell Submersible',
        'features' => '<p><strong>4VO1/10-BUE(U4S) - 4 inch Oil-Filled Borewell Submersible Pump 1 HP</strong></p><ul><li>Power Rating: 1 HP / 0.75 KW</li><li>Pump Type: Oil-Filled Submersible</li><li>Bore Size: 4 Inch</li><li>Single Phase Operation (U4S specification)</li><li>Advanced U4S motor technology</li><li>Enhanced cooling performance</li><li>Optimized for Indian soil conditions</li><li>Reliable and economical</li><li>Professional installation recommended</li></ul>',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/artboard1_277b9f24-ac06-4c37-835f-ff388004917b.png',
        'image_name' => '4vo1-10-bue-u4s.webp'
    ),
    array(
        'title' => '4 inch Oil filled Borewell Submersible Pump 1.5 HP',
        'model' => '4VO1.5/12-BUE(U4S)',
        'kwhp' => '1.5 HP',
        'supplyPhase' => '1PH',
        'pumpType' => '4-Inch Oil-Filled Borewell Submersible',
        'features' => '<p><strong>4VO1.5/12-BUE(U4S) - 4 inch Oil-Filled Borewell Submersible Pump 1.5 HP</strong></p><ul><li>Power Rating: 1.5 HP / 1.1 KW</li><li>Pump Type: Oil-Filled Submersible</li><li>Bore Size: 4 Inch</li><li>Single Phase Operation</li><li>Enhanced power delivery</li><li>Superior cooling efficiency</li><li>Heavy-duty construction</li><li>Suitable for deeper bore wells</li><li>Extended operational lifespan</li></ul>',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/artboard1_277b9f24-ac06-4c37-835f-ff388004917b.png',
        'image_name' => '4vo1-5-12-bue-u4s.webp'
    ),
    array(
        'title' => '4 inch Oil filled Borewell Submersible Pump 1.5 HP (BU)',
        'model' => '4VO1.5/14-BUE(U4S)',
        'kwhp' => '1.5 HP',
        'supplyPhase' => '1PH',
        'pumpType' => '4-Inch Oil-Filled Borewell Submersible',
        'features' => '<p><strong>4VO1.5/14-BUE(U4S) - 4 inch Oil-Filled Borewell Submersible Pump 1.5 HP</strong></p><ul><li>Power Rating: 1.5 HP / 1.1 KW</li><li>Pump Type: Oil-Filled Submersible</li><li>Bore Size: 4 Inch</li><li>Single Phase Operation</li><li>High discharge capacity</li><li>Robust motor design</li><li>Optimal cooling system</li><li>Professional grade construction</li><li>Suitable for commercial use</li></ul>',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/artboard1_277b9f24-ac06-4c37-835f-ff388004917b.png',
        'image_name' => '4vo1-5-14-bue-u4s.webp'
    ),
);

echo "Products to import: " . count($products) . "\n";
echo "Downloading and converting images...\n\n";

$downloaded = 0;
$failed = 0;
$image_map = array(); // Track unique URLs to avoid re-downloading

// Download and convert images
foreach ($products as $product) {
    $image_url = $product['image_url'];
    $local_image = $upload_path . '/' . $product['image_name'];
    $temp_image = $temp_path . '/' . md5($image_url) . '.png';

    echo "Processing: {$product['image_name']}... ";

    // Skip if already downloaded from this URL
    if (!isset($image_map[$image_url])) {
        // Download image using curl
        $download_cmd = "curl -s -L -A 'Mozilla/5.0' '{$image_url}' -o '{$temp_image}' 2>&1";
        exec($download_cmd, $output, $return_code);

        if (file_exists($temp_image) && filesize($temp_image) > 500) {
            $image_map[$image_url] = $temp_image;
            echo "(Downloaded) ";
        } else {
            echo "❌ Download failed\n";
            $failed++;
            continue;
        }
    } else {
        $temp_image = $image_map[$image_url];
        echo "(Cached) ";
    }

    // Convert PNG to WebP using GD
    if (convertPNGtoWebP($temp_image, $local_image)) {
        echo "✅ Converted\n";
        $downloaded++;
    } else {
        echo "❌ Conversion failed\n";
        $failed++;
    }
}

function convertPNGtoWebP($png_file, $webp_file) {
    if (!file_exists($png_file) || filesize($png_file) < 100) {
        return false;
    }

    try {
        // Load PNG
        $img = imagecreatefrompng($png_file);
        if (!$img) {
            return false;
        }

        // Save as WebP
        $success = imagewebp($img, $webp_file, 85);
        imagedestroy($img);

        return $success && file_exists($webp_file);
    } catch (Exception $e) {
        return false;
    }
}

echo "\n=== Adding Products to Database ===\n\n";

// Add products to database
$categoryPID = 28; // 4-Inch Borewell category
$added = 0;
$db_failed = 0;

foreach ($products as $product) {
    // Check if product already exists
    $DB->vals = array($product['title'], 1);
    $DB->types = "si";
    $DB->sql = "SELECT pumpID FROM mx_pump WHERE pumpTitle = ? AND status = ? LIMIT 1";
    $DB->dbRow();

    if ($DB->numRows > 0) {
        echo "⏭️  {$product['title']} (already exists)\n";
        continue;
    }

    // Generate SEO URI
    $seo_uri = strtolower(trim(preg_replace('/[^a-z0-9\-]/', '-', str_replace(' ', '-', $product['title'])), '-'));

    // Verify image exists
    $local_image = $upload_path . '/' . $product['image_name'];
    if (!file_exists($local_image)) {
        echo "❌ {$product['title']}: Image not found\n";
        $db_failed++;
        continue;
    }

    // Insert into database
    $DB->vals = array(
        $categoryPID,
        $product['title'],
        $seo_uri,
        $product['image_name'],
        $product['features'],
        $product['kwhp'],
        $product['supplyPhase'],
        $product['pumpType'],
        1
    );
    $DB->types = "isssssssi";
    $DB->sql = "INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($DB->dbInsert()) {
        $pump_id = $DB->con->insert_id;
        echo "✅ {$product['title']} (ID: {$pump_id})\n";
        $added++;
    } else {
        echo "❌ {$product['title']}: Database error\n";
        $db_failed++;
    }
}

echo "\n=== Generating Thumbnails ===\n\n";

// Generate thumbnails
$sizes = array(235, 530);
$thumb_count = 0;

foreach ($products as $product) {
    $image_file = $upload_path . '/' . $product['image_name'];

    if (!file_exists($image_file)) {
        continue;
    }

    foreach ($sizes as $size) {
        $thumb_path = $upload_path . '/' . $size . '_' . $size . '_crop_100/' . $product['image_name'];
        $thumb_dir = dirname($thumb_path);

        if (!is_dir($thumb_dir)) {
            mkdir($thumb_dir, 0755, true);
        }

        if (createThumbnail($image_file, $thumb_path, $size)) {
            $thumb_count++;
        }
    }
}

function createThumbnail($source, $dest, $size) {
    try {
        $img = imagecreatefromwebp($source);
        if (!$img) return false;

        $w = imagesx($img);
        $h = imagesy($img);

        // Create white canvas
        $canvas = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        // Calculate scale
        $scale = min($size / $w, $size / $h);
        $new_w = intval($w * $scale);
        $new_h = intval($h * $scale);
        $x = intval(($size - $new_w) / 2);
        $y = intval(($size - $new_h) / 2);

        // Copy and resize
        imagecopyresampled($canvas, $img, $x, $y, 0, 0, $new_w, $new_h, $w, $h);

        // Save
        $success = imagewebp($canvas, $dest, 85);
        imagedestroy($img);
        imagedestroy($canvas);

        return $success;
    } catch (Exception $e) {
        return false;
    }
}

echo "✅ Generated $thumb_count thumbnails\n";

echo "\n=== Final Results ===\n";
echo "✅ Downloaded/Converted: $downloaded images\n";
echo "✅ Products Added: $added\n";
echo "❌ Download Failures: $failed\n";
echo "❌ Database Failures: $db_failed\n";

// Verify total
$DB->vals = array(28, 1);
$DB->types = "ii";
$DB->sql = "SELECT COUNT(*) as cnt FROM mx_pump WHERE categoryPID = ? AND status = ?";
$result = $DB->dbRow();
$total = $result['cnt'];

echo "\n📊 Total 4-Inch Borewell Pumps: $total\n";

// Cleanup
exec("rm -rf {$temp_path}");
$DB->con->close();

echo "\n✨ Import complete!\n";
?>
