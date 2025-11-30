<?php
$conn = new mysqli('localhost', 'bombayengg', 'oCFCrCMwKyy5jzg', 'bombayengg');

// Get a category ID
$result = $conn->query("SELECT categoryPID FROM mx_pump_category WHERE categoryTitle='Mini Pumps' LIMIT 1");
$row = $result->fetch_assoc();
$cat_id = $row['categoryPID'];

echo "Using Category ID: $cat_id\n\n";

// Try a test insert with error output
$title = $conn->real_escape_string('Mini Everest Mini Pump');
$desc = $conn->real_escape_string('Compact pump for gardening, lawn sprinkling');

$sql = "INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status)
        VALUES ('$title', $cat_id, '$desc', '1.1kW', 'Single Phase', '25mm x 25mm', '', 'B.I.S. Compliant', '', 'Residential Pump', 'mini-everest-mini-pump', 1)";

echo "SQL: $sql\n\n";

if ($conn->query($sql)) {
    echo "✓ Insert successful!\n";
} else {
    echo "✗ Insert failed!\n";
    echo "Error: " . $conn->error . "\n";
    echo "Error Code: " . $conn->errno . "\n";
}

$conn->close();
