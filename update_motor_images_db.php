<?php
/**
 * Update Motor Category Images in Database
 * Maps category titles to enhanced image filenames
 */

// Include database connection
include_once('config.inc.php');
include_once('tds/lib/db.php');

// Create mapping of category titles to image filenames
$imageMapping = array(
    'High Voltage Motors' => 'HV-Motors-High-Voltage-Bombay-Engineering.png',
    'Low Voltage Motors' => 'LV-Motors-Low-Voltage-Bombay-Engineering.png',
    'Energy Efficient Motors' => 'Energy-Efficient-Motors-IE3-IE4-Bombay.png',
    'Motors for Hazardous Area (LV)' => 'Safety-Motors-Hazardous-Area-LV-Bombay.png',
    'DC Motors' => 'DC-Motors-Industrial-Machine-Bombay.png',
    'Motors for Hazardous Areas (HV)' => 'Flame-Proof-Motors-HV-Hazardous-Bombay.png',
    'Special Application Motors' => 'Special-Application-Motors-Cement-Mill-Bombay.png',
    'High / Low Voltage AC & DC Motors' => 'HV-Motors-High-Voltage-Bombay-Engineering.png',
    'Safe Area Single Phase AC Motor' => 'single-phase-motors-1.png',
    'Hazardous Area Single Phase AC Motor' => 'non-sparking-motor-lv.png',
    'Laminated Yoke DC Motor' => 'dc_motor_24093013-1.png',
    'Solid Yoke DC Motor' => 'dc_motor_24093013-2.png',
    'Squirrel Cage Motor IE2 Efficiency' => 'EE-IE3-Apex-Series.png',
    'Squirrel Cage Motor IE3 Efficiency' => 'EE-IE4-Apex-Series.png',
    'Squirrel Cage Motor IE4 Efficiency' => 'EE-NG-Series.png',
    'Slip Ring Induction Motors' => 'slip-ring-motors-lv.png',
    'Safe Area High Voltage AC Motor' => 'HV-Open-Air-Motors.png',
    'Hazardous Area High Voltage AC Motor' => 'HV-Water-Cooled-Motors.png',
    'Safe Area Squirrel Cage IE2 Motors' => 'LV-Cast-Iron-Motors.png',
    'AC Motor1 third leve' => 'LV-Aluminum-Motors.png',
    'Electric' => 'emotron-vsr-solar-drive.png',
    'Application Specific Motors' => 'application-specific-1.png',
    'Drives and Automation' => 'mv-drives.png',
    'FHP / Commercial Motors' => 'emotron-afe-drives.png',
    'Industrial Drives' => 'emotron-flowdrive.png',
    'Single Phase Motors' => 'single-phase-motors-2.png',
    'Soft Starters' => 'emotron-tsa-softstarter.png',
);

// Connect to database
$db = new Database();

$updateCount = 0;
$skipCount = 0;
$errorCount = 0;

echo "Updating Motor Category Images in Database\n";
echo "==========================================\n\n";

foreach ($imageMapping as $categoryTitle => $imageName) {
    // Escape the category title for SQL
    $safeCategoryTitle = $db->realEscapeString($categoryTitle);
    $safeImageName = $db->realEscapeString($imageName);

    // First, check if this category exists
    $checkQuery = "SELECT categoryMID FROM mx_motor_category WHERE categoryTitle = '$safeCategoryTitle' AND status = 1 LIMIT 1";
    $result = $db->query($checkQuery);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $categoryMID = $row['categoryMID'];

        // Update the image name
        $updateQuery = "UPDATE mx_motor_category SET imageName = '$safeImageName' WHERE categoryMID = $categoryMID";

        if ($db->query($updateQuery)) {
            echo "✓ Updated: $categoryTitle -> $imageName\n";
            $updateCount++;
        } else {
            echo "✗ Error updating $categoryTitle: " . $db->error . "\n";
            $errorCount++;
        }
    } else {
        echo "⊘ Skipped: Category not found - $categoryTitle\n";
        $skipCount++;
    }
}

echo "\n==========================================\n";
echo "Update Summary:\n";
echo "  Updated: $updateCount\n";
echo "  Skipped: $skipCount\n";
echo "  Errors: $errorCount\n";
echo "==========================================\n\n";

// Verify the updates
echo "Verification - Top 10 Categories with Images:\n";
echo "=============================================\n";

$verifyQuery = "SELECT categoryMID, categoryTitle, imageName FROM mx_motor_category WHERE status = 1 AND imageName IS NOT NULL AND imageName != 'unnamed.jpg' ORDER BY categoryTitle LIMIT 10";
$result = $db->query($verifyQuery);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['categoryMID']} | Title: {$row['categoryTitle']} | Image: {$row['imageName']}\n";
    }
} else {
    echo "No updated records found.\n";
}

echo "\n✅ Database update complete!\n";
?>
