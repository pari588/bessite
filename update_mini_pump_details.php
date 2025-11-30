<?php
$db_host = 'localhost';
$db_user = 'bombayengg';
$db_pass = 'oCFCrCMwKyy5jzg';
$db_name = 'bombayengg';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Updating Mini Pump Details ===\n\n";

// Detailed features for each product
$product_details = array(
    'MINI MASTER II' => array(
        'features' => '<p><strong>MINI MASTER II - Self-Priming Mini Pump</strong></p><ul><li>Power: 0.5 HP / 0.37 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Compact design ideal for domestic applications</li><li>Easy installation and maintenance</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '25 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
    'CHAMP PLUS II' => array(
        'features' => '<p><strong>CHAMP PLUS II - Self-Priming Mini Pump</strong></p><ul><li>Power: 0.5 HP / 0.37 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Robust construction for heavy-duty applications</li><li>Self-priming capability up to 8 meters</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '25 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
    'MINI MASTERPLUS II' => array(
        'features' => '<p><strong>MINI MASTERPLUS II - Self-Priming Mini Pump</strong></p><ul><li>Power: 0.5 HP / 0.37 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Premium quality construction</li><li>Smooth operation with minimal vibration</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '25 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
    'MINI MARVEL II' => array(
        'features' => '<p><strong>MINI MARVEL II - Self-Priming Mini Pump</strong></p><ul><li>Power: 0.5 HP / 0.37 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Lightweight and portable design</li><li>Ideal for water transfer and circulation</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '25 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
    'MINI CREST II' => array(
        'features' => '<p><strong>MINI CREST II - Self-Priming Mini Pump</strong></p><ul><li>Power: 0.5 HP / 0.37 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Reliable performance for continuous duty</li><li>Energy efficient operation</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '25 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
    'AQUAGOLD 50-30' => array(
        'features' => '<p><strong>AQUAGOLD 50-30 - Self-Priming Mini Pump</strong></p><ul><li>Power: 0.5 HP / 0.37 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Compact size with high performance</li><li>Ideal for water supply and circulation</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '25 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
    'AQUAGOLD 100-33' => array(
        'features' => '<p><strong>AQUAGOLD 100-33 - Self-Priming Mini Pump</strong></p><ul><li>Power: 1.0 HP / 0.75 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Higher capacity for increased flow rate</li><li>Reliable for domestic and light commercial applications</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '32 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
    'FLOMAX PLUS II' => array(
        'features' => '<p><strong>FLOMAX PLUS II - Self-Priming Mini Pump</strong></p><ul><li>Power: 0.5 HP / 0.37 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Maximum flow capacity for its class</li><li>Efficient water circulation and transfer</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '25 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
    'MASTER DURA II' => array(
        'features' => '<p><strong>MASTER DURA II - Self-Priming Mini Pump</strong></p><ul><li>Power: 0.5 HP / 0.37 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Heavy-duty construction for prolonged use</li><li>Durable mechanical seal for long service life</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '25 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
    'MASTER PLUS II' => array(
        'features' => '<p><strong>MASTER PLUS II - Self-Priming Mini Pump</strong></p><ul><li>Power: 0.5 HP / 0.37 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Premium design with enhanced performance</li><li>Quiet operation with smooth water flow</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '25 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
    'STAR PLUS II' => array(
        'features' => '<p><strong>STAR PLUS II - Self-Priming Mini Pump</strong></p><ul><li>Power: 0.5 HP / 0.37 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Star rating for best-in-class performance</li><li>Reliable self-priming capability</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '25 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
    'CHAMP DURA II' => array(
        'features' => '<p><strong>CHAMP DURA II - Self-Priming Mini Pump</strong></p><ul><li>Power: 0.5 HP / 0.37 KW</li><li>Advanced electrical stamping with Hy-Flo Max technology</li><li>Brass impellers with stainless-steel components for durability</li><li>IP 55 protection and F-Class insulation for safety</li><li>Single Phase operation</li><li>Durable construction for extended lifespan</li><li>Champion performance in its category</li><li>Warranty: 12 Months</li></ul>',
        'deliveryPipe' => '25 mm',
        'noOfStage' => '1',
        'isi' => 'Yes',
        'mnre' => 'No'
    ),
);

$updated = 0;
foreach($product_details as $title => $details) {
    $query = $conn->prepare("
        UPDATE mx_pump 
        SET pumpFeatures = ?, deliveryPipe = ?, noOfStage = ?, isi = ?, mnre = ?
        WHERE pumpTitle = ? AND status = 1
    ");
    
    $query->bind_param("ssssss", 
        $details['features'],
        $details['deliveryPipe'],
        $details['noOfStage'],
        $details['isi'],
        $details['mnre'],
        $title
    );
    
    if($query->execute()) {
        echo "✅ Updated: $title\n";
        $updated++;
    } else {
        echo "❌ Failed: $title\n";
    }
    $query->close();
}

echo "\n=== Results ===\n✅ Updated: $updated products\n";
$conn->close();
?>
