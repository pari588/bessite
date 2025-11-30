<?php
// Add missing hazardous area motors
include 'config.inc.php';

// Products to add based on CG Global catalog
$productsToAdd = [
    // Hazardous Area (LV) - Category 23
    [
        'categoryMID' => 23,
        'motorTitle' => 'Increased Safety Motors Ex \'e\' (LV)',
        'motorSubTitle' => 'ATEX Certified Enhanced Safety LV Motors for Hazardous Zones',
        'motorDesc' => 'Increased Safety Motors designed for hazardous areas with Ex \'e\' certification. These motors feature reinforced insulation and enhanced cooling systems suitable for Zone 1 and Zone 2 classified areas. Ideal for chemical plants, refineries, and explosive atmospheres. Equipped with temperature monitoring and overload protection.',
        'motorImage' => 'increased-safety-lv-ex-e.webp'
    ],
    [
        'categoryMID' => 23,
        'motorTitle' => 'Non Sparking Motor Ex \'n\' (LV)',
        'motorSubTitle' => 'Spark-Proof Low Voltage Motors for Hazardous Environments',
        'motorDesc' => 'Non-sparking motors with Ex \'n\' certification for hazardous area applications. Features elimination of hot surfaces and mechanical sparks with reliable safety standards. Perfect for chemical industries, mining operations, and volatile organic compound storage areas. Designed for continuous operation in classified zones.',
        'motorImage' => 'non-sparking-lv-ex-n.webp'
    ],
    
    // Hazardous Areas (HV) - Category 25
    [
        'categoryMID' => 25,
        'motorTitle' => 'Increased Safety Motors Ex \'e\' (HV)',
        'motorSubTitle' => 'ATEX Approved High Voltage Safety Motors for Industrial Hazardous Areas',
        'motorDesc' => 'High voltage increased safety motors with Ex \'e\' certification for demanding industrial applications. Engineered for operation in hazardous areas with enhanced electrical and thermal safety measures. Suitable for large-scale chemical production, petroleum refineries, and power generation facilities. Features robust construction and advanced protection systems.',
        'motorImage' => 'increased-safety-hv-ex-e.webp'
    ],
    [
        'categoryMID' => 25,
        'motorTitle' => 'Non Sparking Motor Ex \'n\' (HV)',
        'motorSubTitle' => 'High Voltage Non-Sparking Motors for Explosive Atmosphere Protection',
        'motorDesc' => 'High voltage non-sparking motors certified to Ex \'n\' standard for hazardous area installations. These motors eliminate spark generation through mechanical design and sealed construction. Ideal for large industrial plants requiring safe operation in volatile environments. Provides reliable performance with minimal maintenance requirements.',
        'motorImage' => 'non-sparking-hv-ex-n.webp'
    ],
    [
        'categoryMID' => 25,
        'motorTitle' => 'Pressurized Motor Ex \'p\' (HV)',
        'motorSubTitle' => 'Pressure-Enclosed High Voltage Motors for Classified Hazardous Zones',
        'motorDesc' => 'Pressurized motors with Ex \'p\' certification provide protection through internal pressurization. High voltage design suitable for complex industrial processes in hazardous areas with continuous monitoring and gas displacement safety features. Engineered for major industrial complexes, petrochemical plants, and offshore platforms.',
        'motorImage' => 'pressurized-motor-hv-ex-p.webp'
    ]
];

$mysqli = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$added = 0;
$errors = [];

foreach ($productsToAdd as $product) {
    // Check if product already exists
    $checkStmt = $mysqli->prepare("SELECT motorID FROM mx_motor WHERE motorTitle = ? AND categoryMID = ?");
    $checkStmt->bind_param("si", $product['motorTitle'], $product['categoryMID']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Product '{$product['motorTitle']}' already exists in category {$product['categoryMID']}";
        continue;
    }
    
    // Generate SEO URI
    $seoUri = strtolower(str_replace(
        [' ', "'", '"', '&', '(', ')', '/', '\\'],
        ['-', '', '', '', '', '', '-', '-'],
        $product['motorTitle']
    ));
    $seoUri = preg_replace('/-+/', '-', $seoUri);
    $seoUri = trim($seoUri, '-');
    
    // Insert the product
    $stmt = $mysqli->prepare("
        INSERT INTO mx_motor (
            categoryMID, motorTitle, seoUri, motorSubTitle, motorDesc, motorImage, status
        ) VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->bind_param(
        "isssss",
        $product['categoryMID'],
        $product['motorTitle'],
        $seoUri,
        $product['motorSubTitle'],
        $product['motorDesc'],
        $product['motorImage']
    );
    
    if ($stmt->execute()) {
        $added++;
        echo "✓ Added: {$product['motorTitle']}\n";
    } else {
        $errors[] = "Error adding '{$product['motorTitle']}': " . $stmt->error;
    }
}

echo "\n=== Summary ===\n";
echo "Products added: $added\n";
if (!empty($errors)) {
    echo "Errors:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}

$mysqli->close();
?>
