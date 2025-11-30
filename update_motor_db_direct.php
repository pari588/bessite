<?php
/**
 * Direct MySQL Update for Motor Category Images
 */

// Database credentials
$host = 'localhost';
$user = 'bombayengg';
$pass = 'oCFCrCMwKyy5jzg';
$db = 'bombayengg';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

echo "Connected to database successfully\n\n";

// Image mapping
$updates = array(
    array('categoryTitle' => 'High Voltage Motors', 'imageName' => 'HV-Motors-High-Voltage-Bombay-Engineering.png'),
    array('categoryTitle' => 'Low Voltage Motors', 'imageName' => 'LV-Motors-Low-Voltage-Bombay-Engineering.png'),
    array('categoryTitle' => 'Energy Efficient Motors', 'imageName' => 'Energy-Efficient-Motors-IE3-IE4-Bombay.png'),
    array('categoryTitle' => 'Motors for Hazardous Area (LV)', 'imageName' => 'Safety-Motors-Hazardous-Area-LV-Bombay.png'),
    array('categoryTitle' => 'DC Motors', 'imageName' => 'DC-Motors-Industrial-Machine-Bombay.png'),
    array('categoryTitle' => 'Motors for Hazardous Areas (HV)', 'imageName' => 'Flame-Proof-Motors-HV-Hazardous-Bombay.png'),
    array('categoryTitle' => 'Special Application Motors', 'imageName' => 'Special-Application-Motors-Cement-Mill-Bombay.png'),
    array('categoryTitle' => 'High / Low Voltage AC & DC Motors', 'imageName' => 'HV-Motors-High-Voltage-Bombay-Engineering.png'),
    array('categoryTitle' => 'Safe Area Single Phase AC Motor', 'imageName' => 'single-phase-motors-1.png'),
    array('categoryTitle' => 'Hazardous Area Single Phase AC Motor', 'imageName' => 'non-sparking-motor-lv.png'),
    array('categoryTitle' => 'Laminated Yoke DC Motor', 'imageName' => 'dc_motor_24093013-1.png'),
    array('categoryTitle' => 'Solid Yoke DC Motor', 'imageName' => 'dc_motor_24093013-2.png'),
    array('categoryTitle' => 'Squirrel Cage Motor IE2 Efficiency', 'imageName' => 'EE-IE3-Apex-Series.png'),
    array('categoryTitle' => 'Squirrel Cage Motor IE3 Efficiency', 'imageName' => 'EE-IE4-Apex-Series.png'),
    array('categoryTitle' => 'Squirrel Cage Motor IE4 Efficiency', 'imageName' => 'EE-NG-Series.png'),
    array('categoryTitle' => 'Slip Ring Induction Motors', 'imageName' => 'slip-ring-motors-lv.png'),
    array('categoryTitle' => 'Safe Area High Voltage AC Motor', 'imageName' => 'HV-Open-Air-Motors.png'),
    array('categoryTitle' => 'Hazardous Area High Voltage AC Motor', 'imageName' => 'HV-Water-Cooled-Motors.png'),
    array('categoryTitle' => 'Safe Area Squirrel Cage IE2 Motors', 'imageName' => 'LV-Cast-Iron-Motors.png'),
    array('categoryTitle' => 'AC Motor1 third leve', 'imageName' => 'LV-Aluminum-Motors.png'),
    array('categoryTitle' => 'Electric', 'imageName' => 'emotron-vsr-solar-drive.png'),
);

$updateCount = 0;
$errorCount = 0;

echo "Updating Motor Category Images\n";
echo "==============================\n\n";

foreach ($updates as $update) {
    $categoryTitle = $conn->real_escape_string($update['categoryTitle']);
    $imageName = $conn->real_escape_string($update['imageName']);

    $sql = "UPDATE mx_motor_category SET imageName = '$imageName' WHERE categoryTitle = '$categoryTitle' AND status = 1";

    if ($conn->query($sql) === TRUE) {
        $affected = $conn->affected_rows;
        if ($affected > 0) {
            echo "✓ Updated ($affected row): {$update['categoryTitle']} -> {$update['imageName']}\n";
            $updateCount += $affected;
        }
    } else {
        echo "✗ Error updating {$update['categoryTitle']}: " . $conn->error . "\n";
        $errorCount++;
    }
}

echo "\n==============================\n";
echo "Summary: Updated $updateCount records, $errorCount errors\n\n";

// Verify
echo "Verification - Updated Categories:\n";
echo "===================================\n";

$result = $conn->query("SELECT categoryMID, categoryTitle, imageName FROM mx_motor_category WHERE status = 1 AND imageName NOT LIKE 'unnamed.jpg' AND imageName IS NOT NULL ORDER BY categoryTitle");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "• {$row['categoryTitle']}\n  → {$row['imageName']}\n";
    }
} else {
    echo "No records found.\n";
}

echo "\n✅ Database update complete!\n";

$conn->close();
?>
