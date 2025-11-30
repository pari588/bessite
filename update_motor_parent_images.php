<?php
/**
 * Update Parent Motor Category Images
 * Maps parent categories to representative images
 */

$host = 'localhost';
$user = 'bombayengg';
$pass = 'oCFCrCMwKyy5jzg';
$db = 'bombayengg';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "Updating Parent Motor Category Images\n";
echo "=====================================\n\n";

// Update parent categories with representative images
$parentUpdates = array(
    array('categoryMID' => 100, 'categoryTitle' => 'Motor', 'imageName' => 'HV-Motors-High-Voltage-Bombay-Engineering.png'),
    // Categories under Motor (parentID = 100)
    array('categoryMID' => 1, 'categoryTitle' => 'High / Low Voltage AC & DC Motors', 'imageName' => 'HV-Motors-High-Voltage-Bombay-Engineering.png'),
    array('categoryMID' => 101, 'categoryTitle' => 'FHP / Commercial Motors', 'imageName' => 'emotron-afe-drives.png'),
    array('categoryMID' => 105, 'categoryTitle' => 'Drives and Automation', 'imageName' => 'emotron-flowdrive.png'),

    // Update missing sub-categories
    array('categoryMID' => 104, 'categoryTitle' => 'Application Specific Motors', 'imageName' => 'application-specific-1.png'),
    array('categoryMID' => 102, 'categoryTitle' => 'Single Phase Motors', 'imageName' => 'single-phase-motors-2.png'),
    array('categoryMID' => 106, 'categoryTitle' => 'Industrial Drives', 'imageName' => 'emotron-flowdrive.png'),
    array('categoryMID' => 107, 'categoryTitle' => 'Soft Starters', 'imageName' => 'emotron-tsa-softstarter.png'),
);

$updateCount = 0;
$errorCount = 0;

foreach ($parentUpdates as $update) {
    $categoryMID = intval($update['categoryMID']);
    $imageName = $conn->real_escape_string($update['imageName']);
    $categoryTitle = $conn->real_escape_string($update['categoryTitle']);

    $sql = "UPDATE mx_motor_category SET imageName = '$imageName' WHERE categoryMID = $categoryMID";

    if ($conn->query($sql) === TRUE) {
        $affected = $conn->affected_rows;
        if ($affected > 0) {
            echo "✓ Updated: {$update['categoryTitle']} (ID: $categoryMID) → {$update['imageName']}\n";
            $updateCount += $affected;
        }
    } else {
        echo "✗ Error: " . $conn->error . "\n";
        $errorCount++;
    }
}

echo "\n=====================================\n";
echo "Summary: Updated $updateCount records, $errorCount errors\n\n";

// Verify all categories have images
echo "Verification - All Motor Categories with Images:\n";
echo "=================================================\n";

$result = $conn->query("SELECT categoryMID, parentID, categoryTitle, imageName FROM mx_motor_category WHERE status = 1 AND imageName IS NOT NULL ORDER BY parentID, categoryTitle");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $parent = $row['parentID'] == 0 ? "[ROOT]" : "[Parent: {$row['parentID']}]";
        echo "ID:{$row['categoryMID']} {$parent} {$row['categoryTitle']}\n";
        echo "  → {$row['imageName']}\n";
    }
} else {
    echo "No records found.\n";
}

echo "\n✅ Parent category update complete!\n";

$conn->close();
?>
