<?php
echo "IMPORTING ALL MINI PUMP PRODUCTS FROM CROMPTON\n";
echo "==============================================\n\n";

$conn = new mysqli('localhost', 'bombayengg', 'oCFCrCMwKyy5jzg', 'bombayengg');

// All 24 Mini Pump products from Crompton
$mini_pumps = array(
    array('title' => 'Mini Everest Mini Pump', 'power' => '1.1kW', 'phase' => 'Single Phase', 'features' => 'Premium mini pump with high performance and durability. Ideal for solar water systems, gardens, and fountains.'),
    array('title' => 'AQUAGOLD DURA 150', 'power' => '1.5HP/1.1kW', 'phase' => 'Single Phase', 'features' => 'Heavy-duty mini pump with brass impeller and stainless-steel components for corrosion resistance.'),
    array('title' => 'AQUAGOLD 150', 'power' => '1.5HP/1.1kW', 'phase' => 'Single Phase', 'features' => 'Reliable mini pump suitable for residential water supply and booster systems.'),
    array('title' => 'WIN PLUS I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Self-priming regenerative pump for solar water systems and residential applications.'),
    array('title' => 'ULTIMO II', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Advanced mini pump with enhanced efficiency and reliability.'),
    array('title' => 'ULTIMO I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Premium quality mini pump for various residential applications.'),
    array('title' => 'STAR PLUS I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Robust mini pump with excellent performance characteristics.'),
    array('title' => 'STAR DURA I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Durable mini pump designed for long-term reliability.'),
    array('title' => 'PRIMO I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Quality mini pump for residential and light commercial use.'),
    array('title' => 'NILE PLUS I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Efficient mini pump with superior performance.'),
    array('title' => 'NILE DURA I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Heavy-duty mini pump for demanding applications.'),
    array('title' => 'MINI SUMO I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Compact and powerful mini pump for tight spaces.'),
    array('title' => 'MINI MASTERPLUS I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Premium mini pump with advanced features and robust construction.'),
    array('title' => 'MINI MASTER I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Professional-grade mini pump for serious applications.'),
    array('title' => 'MINI MARVEL I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Innovative mini pump with exceptional performance.'),
    array('title' => 'GLORY PLUS I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'High-quality mini pump for reliable water supply.'),
    array('title' => 'MINI CREST I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Premium mini pump with superior engineering.'),
    array('title' => 'CHAMP PLUS I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Champion-grade mini pump for exceptional performance.'),
    array('title' => 'MASTER PLUS I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Master-class mini pump with premium features.'),
    array('title' => 'MASTER DURA I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Durable master-class mini pump for long service life.'),
    array('title' => 'GLIDE PLUS II', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Smooth-operating mini pump with advanced design.'),
    array('title' => 'GLIDE PLUS I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Efficient mini pump with smooth operation.'),
    array('title' => 'FLOMAX PLUS I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Maximum flow mini pump for high-demand applications.'),
    array('title' => 'CHAMP DURA I', 'power' => '1.0HP/0.75kW', 'phase' => 'Single Phase', 'features' => 'Durable champion-grade mini pump for reliable performance.'),
);

// Category ID for Mini Pumps
$category_id = 24;

// Get existing products to avoid duplicates
$existing = array();
$result = $conn->query("SELECT pumpTitle FROM mx_pump WHERE categoryPID=$category_id AND status=1");
while ($row = $result->fetch_assoc()) {
    $existing[$row['pumpTitle']] = true;
}

echo "Found " . count($existing) . " existing products in Mini Pumps category\n";
echo "Total products to process: " . count($mini_pumps) . "\n\n";

$inserted = 0;
$skipped = 0;
$failed = 0;

foreach ($mini_pumps as $pump) {
    $title = $pump['title'];
    
    // Check if already exists
    if (isset($existing[$title])) {
        echo "⊘ Skipping: $title (already exists)\n";
        $skipped++;
        continue;
    }
    
    echo "Adding: $title\n";
    
    // Create seoUri
    $seoUri = strtolower(str_replace(array(' ', '/'), array('-', ''), $title));
    $seoUri = preg_replace('/[^a-z0-9-]/', '', $seoUri);
    $seoUri = preg_replace('/-+/', '-', $seoUri);
    $seoUri = trim($seoUri, '-');
    
    // Check if seoUri already exists
    $check = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE seoUri='$seoUri'");
    $crow = $check->fetch_assoc();
    if ($crow['cnt'] > 0) {
        $seoUri .= '-' . time();
    }
    
    // Prepare insert
    $pumpTitle = $conn->real_escape_string($pump['title']);
    $pumpFeatures = $conn->real_escape_string($pump['features']);
    $kwhp = $conn->real_escape_string($pump['power']);
    $supplyPhase = $conn->real_escape_string($pump['phase']);
    
    $sql = "INSERT INTO mx_pump (
        categoryPID, pumpTitle, seoUri, pumpFeatures, kwhp, supplyPhase, 
        deliveryPipe, noOfStage, isi, mnre, pumpType, status, pumpImage
    ) VALUES (
        $category_id, '$pumpTitle', '$seoUri', '$pumpFeatures', '$kwhp', '$supplyPhase',
        '', '', '', '', 'Residential', 1, ''
    )";
    
    if ($conn->query($sql)) {
        $new_id = $conn->insert_id;
        echo "  ✓ Inserted (ID: $new_id, seoUri: $seoUri)\n";
        $inserted++;
    } else {
        echo "  ✗ Failed: " . $conn->error . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "IMPORT SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✓ Inserted: $inserted\n";
echo "⊘ Skipped: $skipped\n";
echo "✗ Failed: $failed\n";
echo "Total: " . ($inserted + $skipped + $failed) . "\n\n";

// Verify
$result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE categoryPID=$category_id AND status=1");
$row = $result->fetch_assoc();
echo "✓ Total Mini Pumps now in database: " . $row['cnt'] . "\n";

if ($inserted > 0) {
    echo "\nNext step: Create images for new products...\n";
}

$conn->close();
?>
