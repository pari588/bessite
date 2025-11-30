<?php
/**
 * Add Missing Crompton Mini Pump Series II Products
 * These are the missing products from the Crompton website
 */

$db_host = 'localhost';
$db_user = 'bombayengg';
$db_pass = 'oCFCrCMwKyy5jzg';
$db_name = 'bombayengg';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$upload_path = '/home/bombayengg/public_html/uploads/pump';

echo "=== Adding Missing Crompton Mini Pumps Series II ===\n\n";

// Products that are missing from your website
$missing_products = array(
    array(
        'title' => 'MINI MASTER II',
        'model' => 'MINI MASTER II',
        'kwhp' => '0.5 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,  // Mini Pumps
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MINI_MASTERPLUS_II_a51e57a3-3b7e-4cf5-9d80-5f01a6c4fd41.png'
    ),
    array(
        'title' => 'CHAMP PLUS II',
        'model' => 'CHAMP PLUS II',
        'kwhp' => '0.5 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/CHAMP_PLUS_I_58f96b15-dcf3-4c13-8ea8-b79e0ad83c33.png'
    ),
    array(
        'title' => 'MINI MASTERPLUS II',
        'model' => 'MINI MASTERPLUS II',
        'kwhp' => '0.5 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MINI_MASTERPLUS_II_a51e57a3-3b7e-4cf5-9d80-5f01a6c4fd41.png'
    ),
    array(
        'title' => 'MINI MARVEL II',
        'model' => 'MINI MARVEL II',
        'kwhp' => '0.5 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MINI_MARVEL_I_dfe93c01-b0a8-47ce-b6f4-37f5c30d28b2.png'
    ),
    array(
        'title' => 'MINI CREST II',
        'model' => 'MINI CREST II',
        'kwhp' => '0.5 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MINI_CREST_I_eb82cb52-f008-49d6-a3c4-3dd33f7bf1e4.png'
    ),
    array(
        'title' => 'AQUAGOLD 50-30',
        'model' => 'AQUAGOLD 50-30',
        'kwhp' => '0.5 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/AQUAGOLD_50_30_7ad0d4d2-f6d2-414e-9ea9-37c6fef76e23.png'
    ),
    array(
        'title' => 'AQUAGOLD 100-33',
        'model' => 'AQUAGOLD 100-33',
        'kwhp' => '1.0 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/AQUAGOLD_100_33_6c8d3af0-0bab-4c5d-8d62-a9a69e5ed0ab.png'
    ),
    array(
        'title' => 'FLOMAX PLUS II',
        'model' => 'FLOMAX PLUS II',
        'kwhp' => '0.5 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/FLOMAX_PLUS_I_e10c81cb-d1f2-4dba-b8d2-99c9c7cd9aac.png'
    ),
    array(
        'title' => 'MASTER DURA II',
        'model' => 'MASTER DURA II',
        'kwhp' => '0.5 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MASTER_DURA_I_2c969d3b-79b0-4bae-bd13-5ccb2c5f4cdc.png'
    ),
    array(
        'title' => 'MASTER PLUS II',
        'model' => 'MASTER PLUS II',
        'kwhp' => '0.5 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/MASTER_PLUS_I_c5883c93-5a2e-49c2-b8b1-32dc37dae8d5.png'
    ),
    array(
        'title' => 'STAR PLUS II',
        'model' => 'STAR PLUS II',
        'kwhp' => '0.5 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/STAR_PLUS_I_6e0aeacb-5e37-4c06-8d41-46c86fc4c5a5.png'
    ),
    array(
        'title' => 'CHAMP DURA II',
        'model' => 'CHAMP DURA II',
        'kwhp' => '0.5 HP',
        'supplyPhase' => 'Single Phase',
        'categoryPID' => 24,
        'pumpType' => 'Mini Self-Priming',
        'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.',
        'image_url' => 'https://www.crompton.co.in/cdn/shop/files/CHAMP_DURA_I_98dd4aa4-7a95-4533-8d7e-b49a2f73a2d8.png'
    ),
);

echo "Missing products identified: " . count($missing_products) . "\n";
echo "Downloading images and adding to database...\n\n";

$added = 0;
$errors = array();
$crompton_dir = $upload_path . '/crompton_images';

// Create crompton_images directory if it doesn't exist
if(!is_dir($crompton_dir)) {
    @mkdir($crompton_dir, 0777, true);
}

foreach($missing_products as $product) {
    // Check if product already exists
    $result = $conn->query("SELECT pumpID FROM mx_pump WHERE pumpTitle = '{$product['title']}' AND status=1 LIMIT 1");
    if($result->num_rows > 0) {
        echo "⏭️  SKIP: {$product['title']} (already exists)\n";
        continue;
    }

    // Generate image filename
    $image_filename = strtolower(str_replace(' ', '-', $product['title'])) . '.webp';
    $image_path = $crompton_dir . '/' . $image_filename;

    // Download image from Crompton website
    echo "📥 Downloading image for {$product['title']}...\n";
    $image_data = @file_get_contents($product['image_url']);

    if($image_data === false) {
        echo "   ❌ Failed to download image\n";
        $errors[] = "{$product['title']}: Failed to download image";
        continue;
    }

    // Save image
    if(!file_put_contents($image_path, $image_data)) {
        echo "   ❌ Failed to save image\n";
        $errors[] = "{$product['title']}: Failed to save image";
        continue;
    }

    echo "   ✅ Image downloaded and saved\n";

    // Generate SEO URI
    $seo_uri = strtolower(trim(preg_replace('/[^a-z0-9\-]/', '-', str_replace(' ', '-', $product['title'])), '-'));

    // Insert into database
    $query = $conn->prepare("
        INSERT INTO mx_pump
        (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");

    $query->bind_param(
        "isssss ss",
        $product['categoryPID'],
        $product['title'],
        $seo_uri,
        $image_filename,
        $product['features'],
        $product['kwhp'],
        $product['supplyPhase'],
        $product['pumpType']
    );

    if($query->execute()) {
        echo "✅ {$product['title']} added to database\n";
        echo "   ID: {$conn->insert_id}, Image: {$image_filename}\n\n";
        $added++;
    } else {
        echo "❌ {$product['title']} - Database insert failed\n";
        $errors[] = "{$product['title']}: " . $query->error;
    }
    $query->close();
}

echo "=== Results ===\n";
echo "✅ Added: $added\n";
echo "❌ Errors: " . count($errors) . "\n";

if(count($errors) > 0) {
    echo "\nError Details:\n";
    foreach($errors as $err) {
        echo "  - $err\n";
    }
}

// Verify total mini pumps now
$result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE categoryPID=24 AND status=1");
$total = $result->fetch_assoc()['cnt'];

echo "\n=== Total Mini Pumps Now: $total ===\n";
echo "\n✨ Import complete!\n";

$conn->close();
?>
