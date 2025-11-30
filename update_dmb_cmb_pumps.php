<?php
// Database credentials
$DBHOST = 'localhost';
$DBNAME = 'bombayengg';
$DBUSER = 'bombayengg';
$DBPASS = 'oCFCrCMwKyy5jzg';

// Connect to database
$conn = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// DMB-CMB Pump data extracted from Crompton website
$dmb_cmb_pumps = [
    [
        'pumpID' => 30,
        'pumpTitle' => 'CMB10NV PLUS',
        'seoUri' => 'pump/residential-pumps/dmb-cmb-pumps/cmb10nv-plus',
        'pumpImage' => 'cmb10nv-plus.webp',
        'pumpFeatures' => 'High Suction Regenerative Pump - 0.5 HP. Self priming pump with high suction capacity.',
        'kwhp' => '0.5HP/0.37',
        'supplyPhase' => 'SP',
        'deliveryPipe' => '0.5',
        'noOfStage' => '1'
    ],
    [
        'pumpID' => 31,
        'pumpTitle' => 'DMB10D PLUS',
        'seoUri' => 'pump/residential-pumps/dmb-cmb-pumps/dmb10d-plus',
        'pumpImage' => 'dmb10d-plus.webp',
        'pumpFeatures' => 'High Suction Regenerative Pump - 1 HP. Self priming pump with enhanced suction capacity.',
        'kwhp' => '1HP/0.75',
        'supplyPhase' => 'SP',
        'deliveryPipe' => '0.75',
        'noOfStage' => '1'
    ],
    [
        'pumpID' => 32,
        'pumpTitle' => 'DMB10DCSL',
        'seoUri' => 'pump/residential-pumps/dmb-cmb-pumps/dmb10dcsl',
        'pumpImage' => 'dmb10dcsl.webp',
        'pumpFeatures' => 'High Suction Regenerative Pump - 1 HP. Stainless steel construction with 1440 RPM operation.',
        'kwhp' => '1HP/0.75',
        'supplyPhase' => 'SP',
        'deliveryPipe' => '0.75',
        'noOfStage' => '1'
    ],
    [
        'pumpID' => 33,
        'pumpTitle' => 'CMB05NV PLUS',
        'seoUri' => 'pump/residential-pumps/dmb-cmb-pumps/cmb05nv-plus',
        'pumpImage' => 'cmb05nv-plus.webp',
        'pumpFeatures' => 'High Suction Regenerative Pump - 0.5 HP. Compact monoblock design with self priming capability.',
        'kwhp' => '0.5HP/0.37',
        'supplyPhase' => 'SP',
        'deliveryPipe' => '0.5',
        'noOfStage' => '1'
    ]
];

echo "=== Updating DMB-CMB Pumps ===\n\n";

foreach ($dmb_cmb_pumps as $pump) {
    $sql = "UPDATE mx_pump SET
            pumpTitle = ?,
            seoUri = ?,
            pumpImage = ?,
            pumpFeatures = ?,
            kwhp = ?,
            supplyPhase = ?,
            deliveryPipe = ?,
            noOfStage = ?
            WHERE pumpID = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssi",
        $pump['pumpTitle'],
        $pump['seoUri'],
        $pump['pumpImage'],
        $pump['pumpFeatures'],
        $pump['kwhp'],
        $pump['supplyPhase'],
        $pump['deliveryPipe'],
        $pump['noOfStage'],
        $pump['pumpID']
    );

    if ($stmt->execute()) {
        echo "✓ Updated: {$pump['pumpTitle']}\n";
        echo "  SEO URI: {$pump['seoUri']}\n";
        echo "  Image: {$pump['pumpImage']}\n";
        echo "  Specs: {$pump['kwhp']}, {$pump['supplyPhase']}, {$pump['deliveryPipe']}\n\n";
    } else {
        echo "✗ Failed to update {$pump['pumpTitle']}: " . $stmt->error . "\n\n";
    }
    $stmt->close();
}

// Verify updates
echo "\n=== Verification ===\n\n";
$result = $conn->query("SELECT pumpID, pumpTitle, seoUri, pumpImage, kwhp FROM mx_pump WHERE categoryPID = 25 ORDER BY pumpID");

while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['pumpID']} | Title: {$row['pumpTitle']}\n";
    echo "  SEO URI: {$row['seoUri']}\n";
    echo "  Image: {$row['pumpImage']}\n";
    echo "  KW/HP: {$row['kwhp']}\n\n";
}

$conn->close();
?>
