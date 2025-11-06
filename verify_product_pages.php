#!/usr/bin/env php
<?php
/**
 * Verify Product Detail Pages - Complete Content Check
 */

define('DBHOST', 'localhost');
define('DBNAME', 'bombayengg');
define('DBUSER', 'bombayengg');
define('DBPASS', 'oCFCrCMwKyy5jzg');

echo "\n" . str_repeat("=", 90) . "\n";
echo "PRODUCT DETAIL PAGES VERIFICATION\n";
echo str_repeat("=", 90) . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

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

// Check all 12 new products
$products = [
    ['id' => 64, 'title' => 'MINI MASTER II'],
    ['id' => 65, 'title' => 'CHAMP PLUS II'],
    ['id' => 66, 'title' => 'MINI MASTERPLUS II'],
    ['id' => 67, 'title' => 'MINI MARVEL II'],
    ['id' => 68, 'title' => 'MINI CREST II'],
    ['id' => 69, 'title' => 'AQUAGOLD 50-30'],
    ['id' => 70, 'title' => 'AQUAGOLD 100-33'],
    ['id' => 71, 'title' => 'FLOMAX PLUS II'],
    ['id' => 72, 'title' => 'MASTER DURA II'],
    ['id' => 73, 'title' => 'MASTER PLUS II'],
    ['id' => 74, 'title' => 'STAR PLUS II'],
    ['id' => 75, 'title' => 'CHAMP DURA II']
];

echo "NEWLY ADDED PRODUCTS - DETAIL PAGE COMPLETENESS:\n";
echo str_repeat("-", 90) . "\n";

$allComplete = true;

foreach ($products as $product) {
    $pumpID = $product['id'];
    $title = $product['title'];

    // Get main product info
    $stmt = $conn->prepare(
        "SELECT pumpID, pumpTitle, pumpImage, pumpFeatures, kwhp, supplyPhase,
                deliveryPipe, noOfStage, isi, mnre, pumpType
         FROM mx_pump WHERE pumpID = ?"
    );
    $stmt->bind_param("i", $pumpID);
    $stmt->execute();
    $result = $stmt->get_result();
    $mainData = $result->fetch_assoc();
    $stmt->close();

    // Get detail specifications
    $stmt = $conn->prepare(
        "SELECT pumpDID, categoryref, powerKw, powerHp, headRange,
                dischargeRange, mrp, warrenty FROM mx_pump_detail WHERE pumpID = ?"
    );
    $stmt->bind_param("i", $pumpID);
    $stmt->execute();
    $result = $stmt->get_result();
    $detailData = $result->fetch_assoc();
    $stmt->close();

    // Check completeness
    $hasImage = !empty($mainData['pumpImage']);
    $hasFeatures = !empty($mainData['pumpFeatures']);
    $hasBasicSpecs = !empty($mainData['kwhp']) && !empty($mainData['supplyPhase']);
    $hasDetailSpecs = !empty($detailData);
    $allFields = $hasImage && $hasFeatures && $hasBasicSpecs && $hasDetailSpecs;

    if (!$allFields) {
        $allComplete = false;
    }

    $status = $allFields ? "✓ COMPLETE" : "⚠ INCOMPLETE";
    printf("%-25s | %s\n", $title, $status);

    if (!$allFields) {
        echo "  Details:\n";
        echo "  • Image: " . ($hasImage ? "✓" : "✗ Missing") . "\n";
        echo "  • Features: " . ($hasFeatures ? "✓ " . strlen($mainData['pumpFeatures']) . " chars" : "✗ Missing") . "\n";
        echo "  • Basic Specs: " . ($hasBasicSpecs ? "✓" : "✗ Missing") . "\n";
        echo "  • Detail Specs: " . ($hasDetailSpecs ? "✓" : "✗ Missing") . "\n\n";
    }
}

echo "\n" . str_repeat("-", 90) . "\n";

// Get sample product details for display
echo "\nSAMPLE PRODUCT DETAIL PAGE DATA:\n";
echo "Example: MINI MASTER II (ID: 64)\n";
echo str_repeat("-", 90) . "\n";

$stmt = $conn->prepare(
    "SELECT pumpID, pumpTitle, pumpImage, pumpFeatures, kwhp, supplyPhase,
            deliveryPipe, noOfStage, isi, mnre, pumpType
     FROM mx_pump WHERE pumpID = 64"
);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "Title: " . $data['pumpTitle'] . "\n";
echo "Image: " . ($data['pumpImage'] ? $data['pumpImage'] : "Not Set") . "\n";
echo "Features:\n" . wordwrap($data['pumpFeatures'], 80, "\n  ", true) . "\n\n";
echo "Basic Specifications:\n";
echo "  • Power (KWHP): " . $data['kwhp'] . "\n";
echo "  • Supply Phase: " . $data['supplyPhase'] . "\n";
echo "  • Delivery Pipe: " . $data['deliveryPipe'] . "\n";
echo "  • No. of Stages: " . $data['noOfStage'] . "\n";
echo "  • ISI Certified: " . $data['isi'] . "\n";
echo "  • Pump Type: " . $data['pumpType'] . "\n\n";

// Get detail specs
$stmt = $conn->prepare(
    "SELECT categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD,
            headRange, dischargeRange, mrp, warrenty
     FROM mx_pump_detail WHERE pumpID = 64"
);
$stmt->execute();
$detail = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($detail) {
    echo "Detailed Specifications (from mx_pump_detail):\n";
    echo "  • Category Ref: " . $detail['categoryref'] . "\n";
    echo "  • Power (kW): " . $detail['powerKw'] . "\n";
    echo "  • Power (HP): " . $detail['powerHp'] . "\n";
    echo "  • Supply Phase: " . ($detail['supplyPhaseD'] == 1 ? "Single Phase (1PH)" : "Three Phase (3PH)") . "\n";
    echo "  • Pipe Phase: " . $detail['pipePhase'] . "mm\n";
    echo "  • No. of Stages: " . $detail['noOfStageD'] . "\n";
    echo "  • Head Range: " . $detail['headRange'] . "m\n";
    echo "  • Discharge Range: " . $detail['dischargeRange'] . "\n";
    echo "  • MRP: ₹" . $detail['mrp'] . "\n";
    echo "  • Warranty: " . $detail['warrenty'] . "\n";
}

echo "\n" . str_repeat("=", 90) . "\n";

if ($allComplete) {
    echo "✓ ALL PRODUCT DETAIL PAGES ARE COMPLETE AND READY FOR FRONTEND DISPLAY\n";
} else {
    echo "⚠ SOME PRODUCTS HAVE MISSING DATA\n";
}

echo str_repeat("=", 90) . "\n\n";

$conn->close();

?>
