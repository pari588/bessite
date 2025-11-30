<?php
// Simple verification that specs are in database and retrievable

$mysqli = new mysqli("localhost", "bombayengg", "oCFCrCMwKyy5jzg", "bombayengg");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "MOTOR SPECIFICATIONS VERIFICATION\n";
echo "==================================\n\n";

// Get motor info
$result = $mysqli->query("SELECT motorID, motorTitle FROM mx_motor WHERE motorID = 55 LIMIT 1");
$motor = $result->fetch_assoc();

echo "Motor: " . $motor['motorTitle'] . " (ID: " . $motor['motorID'] . ")\n\n";

// Get specifications
$result = $mysqli->query("
    SELECT
        motorSpecID,
        specTitle as descriptionTitle,
        specOutput as descriptionOutput,
        specVoltage as descriptionVoltage,
        specFrameSize as descriptionFrameSize,
        specStandard as descriptionStandard
    FROM mx_motor_specification
    WHERE motorID = 55 AND status = 1
    ORDER BY specOutput
");

echo "Specifications Table Format:\n";
echo str_repeat("-", 130) . "\n";
printf("%-35s | %-15s | %-20s | %-15s | %-35s\n",
    "Description Title",
    "Output",
    "Voltage",
    "Frame Size",
    "Standards"
);
echo str_repeat("-", 130) . "\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        printf("%-35s | %-15s | %-20s | %-15s | %-35s\n",
            substr($row['descriptionTitle'], 0, 34),
            substr($row['descriptionOutput'], 0, 14),
            substr($row['descriptionVoltage'], 0, 19),
            substr($row['descriptionFrameSize'], 0, 14),
            substr($row['descriptionStandard'], 0, 34)
        );
    }
    echo str_repeat("-", 130) . "\n";
    echo "\n✓ SPECIFICATIONS ARE DISPLAYING CORRECTLY\n";
    echo "✓ All " . $result->num_rows . " specifications retrieved successfully\n";
} else {
    echo "✗ NO SPECIFICATIONS FOUND\n";
}

// Summary stats
echo "\n\nSUMMARY STATISTICS:\n";
echo "===================\n";

$result = $mysqli->query("SELECT COUNT(*) as total FROM mx_motor_specification WHERE status = 1");
$row = $result->fetch_assoc();
echo "Total specifications in database: " . $row['total'] . "\n";

$result = $mysqli->query("
    SELECT COUNT(DISTINCT motorID) as unique_motors
    FROM mx_motor_specification
    WHERE status = 1
");
$row = $result->fetch_assoc();
echo "Motors with specifications: " . $row['unique_motors'] . "\n";

// Check each product
echo "\nSpecifications per product:\n";
$result = $mysqli->query("
    SELECT m.motorID, m.motorTitle, COUNT(s.motorSpecID) as spec_count
    FROM mx_motor m
    LEFT JOIN mx_motor_specification s ON m.motorID = s.motorID AND s.status = 1
    WHERE m.categoryMID IN (102, 103, 104)
    GROUP BY m.motorID, m.motorTitle
    ORDER BY m.motorID
");

while ($row = $result->fetch_assoc()) {
    echo "  Motor " . str_pad($row['motorID'], 2, "0", STR_PAD_LEFT) . ": " . str_pad($row['spec_count'], 2) . " specs - " . substr($row['motorTitle'], 0, 50) . "\n";
}

$mysqli->close();
?>
