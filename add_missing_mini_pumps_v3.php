<?php
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

$missing_products = array(
    array('title' => 'MINI MASTER II', 'kwhp' => '0.5 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'mini-master-ii.webp'),
    array('title' => 'CHAMP PLUS II', 'kwhp' => '0.5 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'champ-plus-ii.webp'),
    array('title' => 'MINI MASTERPLUS II', 'kwhp' => '0.5 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'mini-masterplus-ii.webp'),
    array('title' => 'MINI MARVEL II', 'kwhp' => '0.5 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'mini-marvel-ii.webp'),
    array('title' => 'MINI CREST II', 'kwhp' => '0.5 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'mini-master-ii.webp'),
    array('title' => 'AQUAGOLD 50-30', 'kwhp' => '0.5 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'aquagold-50-30.webp'),
    array('title' => 'AQUAGOLD 100-33', 'kwhp' => '1.0 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'aquagold-50-30.webp'),
    array('title' => 'FLOMAX PLUS II', 'kwhp' => '0.5 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'flomax-plus-ii.webp'),
    array('title' => 'MASTER DURA II', 'kwhp' => '0.5 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'mini-master-ii.webp'),
    array('title' => 'MASTER PLUS II', 'kwhp' => '0.5 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'mini-masterplus-ii.webp'),
    array('title' => 'STAR PLUS II', 'kwhp' => '0.5 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'mini-masterplus-ii.webp'),
    array('title' => 'CHAMP DURA II', 'kwhp' => '0.5 HP', 'supplyPhase' => '1PH', 'pumpType' => 'Mini Self-Priming', 'features' => 'Advanced electrical stamping with Hy-Flo Max technology. Brass impellers with stainless-steel components. IP 55 protection and F-Class insulation.', 'image' => 'champ-plus-ii.webp'),
);

echo "Missing products: " . count($missing_products) . "\n\n";

$added = 0;
foreach($missing_products as $product) {
    $result = $conn->query("SELECT pumpID FROM mx_pump WHERE pumpTitle = '{$product['title']}' AND status=1 LIMIT 1");
    if($result->num_rows > 0) {
        echo "⏭️  {$product['title']} (exists)\n";
        continue;
    }

    $image_path = $upload_path . '/' . $product['image'];
    if(!file_exists($image_path)) {
        echo "❌ {$product['title']}: Image not found\n";
        continue;
    }

    $seo_uri = strtolower(trim(preg_replace('/[^a-z0-9\-]/', '-', str_replace(' ', '-', $product['title'])), '-'));
    $categoryPID = 24;

    $query = $conn->prepare("INSERT INTO mx_pump (categoryPID, pumpTitle, seoUri, pumpImage, pumpFeatures, kwhp, supplyPhase, pumpType, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $query->bind_param("isssssss", $categoryPID, $product['title'], $seo_uri, $product['image'], $product['features'], $product['kwhp'], $product['supplyPhase'], $product['pumpType']);

    if($query->execute()) {
        echo "✅ {$product['title']} (ID: {$conn->insert_id})\n";
        $added++;
    } else {
        echo "❌ {$product['title']}: {$query->error}\n";
    }
    $query->close();
}

$result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE categoryPID=24 AND status=1");
$total = $result->fetch_assoc()['cnt'];

echo "\n=== Results ===\n✅ Added: $added\n📊 Total Mini Pumps: $total\n";
$conn->close();
?>
