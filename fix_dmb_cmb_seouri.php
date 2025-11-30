<?php
// Fix DMB-CMB pump seoUri to only include product name, not full category path

$DBHOST = 'localhost';
$DBNAME = 'bombayengg';
$DBUSER = 'bombayengg';
$DBPASS = 'oCFCrCMwKyy5jzg';

$conn = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// DMB-CMB Pump data - corrected seoUri (just the product name)
$dmb_cmb_pumps = [
    [
        'pumpID' => 30,
        'pumpTitle' => 'CMB10NV PLUS',
        'seoUri' => 'cmb10nv-plus',  // Fixed: just the product name
    ],
    [
        'pumpID' => 31,
        'pumpTitle' => 'DMB10D PLUS',
        'seoUri' => 'dmb10d-plus',  // Fixed: just the product name
    ],
    [
        'pumpID' => 32,
        'pumpTitle' => 'DMB10DCSL',
        'seoUri' => 'dmb10dcsl',  // Fixed: just the product name
    ],
    [
        'pumpID' => 33,
        'pumpTitle' => 'CMB05NV PLUS',
        'seoUri' => 'cmb05nv-plus',  // Fixed: just the product name
    ]
];

echo "=== Fixing DMB-CMB Pump seoUri ===\n\n";

foreach ($dmb_cmb_pumps as $pump) {
    $sql = "UPDATE mx_pump SET seoUri = ? WHERE pumpID = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $pump['seoUri'], $pump['pumpID']);

    if ($stmt->execute()) {
        echo "✓ Fixed seoUri for {$pump['pumpTitle']}\n";
        echo "  New seoUri: {$pump['seoUri']}\n\n";
    } else {
        echo "✗ Failed to fix {$pump['pumpTitle']}: " . $stmt->error . "\n\n";
    }
    $stmt->close();
}

// Verify updates
echo "=== Verification ===\n\n";
$result = $conn->query("SELECT pumpID, pumpTitle, seoUri FROM mx_pump WHERE categoryPID = 25 ORDER BY pumpID");

while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['pumpID']} | Title: {$row['pumpTitle']}\n";
    echo "  seoUri: {$row['seoUri']}\n";
    echo "  Full URL: /pump/residential-pumps/dmb-cmb-pumps/{$row['seoUri']}/\n\n";
}

$conn->close();
?>
