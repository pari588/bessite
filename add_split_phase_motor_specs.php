<?php
/**
 * Add Split Phase Motor Specifications to mx_motor_detail
 * Single Phase Motor - Child category of Single Phase Motors
 */

$DBHOST = 'localhost';
$DBNAME = 'bombayengg';
$DBUSER = 'bombayengg';
$DBPASS = 'oCFCrCMwKyy5jzg';

$mysqli = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "================================================================================\n";
echo "ADD SPLIT PHASE MOTOR SPECIFICATIONS\n";
echo "================================================================================\n\n";

// Split Phase Motors specifications based on CG catalog
$motorSpecs = array(
    // Split Phase Motors (ID: 51)
    51 => array(
        array('title' => 'Split Phase Type', 'output' => '0.18-1.5 kW', 'voltage' => '230V Single Ph', 'frame' => 'NEMA 48-132T', 'standard' => 'NEMA MG 1'),
        array('title' => 'FHP Commercial', 'output' => 'Single Phase', 'voltage' => '230V', 'frame' => 'Compact', 'standard' => 'Commercial Duty'),
        array('title' => 'Auxiliary Winding', 'output' => 'Centrifugal Switch', 'voltage' => '', 'frame' => '', 'standard' => 'Standard'),
        array('title' => 'Low Cost Design', 'output' => 'Economy Class', 'voltage' => '', 'frame' => '', 'standard' => 'Budget Friendly'),
        array('title' => 'General Purpose', 'output' => 'Light Duty', 'voltage' => '', 'frame' => '', 'standard' => 'Non-Critical Apps'),
    ),
);

$addedCount = 0;
$skippedCount = 0;
$errorCount = 0;
$totalMotors = 0;

foreach ($motorSpecs as $motorID => $specs) {
    // Verify motor exists
    $stmt = $mysqli->prepare("SELECT motorTitle FROM mx_motor WHERE motorID = ?");
    $stmt->bind_param("i", $motorID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $motor = $result->fetch_assoc();
        echo "Motor: {$motor['motorTitle']} (ID: $motorID)\n";
        $totalMotors++;

        // Add specifications to mx_motor_detail
        foreach ($specs as $spec) {
            $stmt = $mysqli->prepare("INSERT INTO mx_motor_detail
                                     (motorID, descriptionTitle, descriptionOutput,
                                      descriptionVoltage, descriptionFrameSize,
                                      descriptionStandard, status)
                                     VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("isssss",
                $motorID,
                $spec['title'],
                $spec['output'],
                $spec['voltage'],
                $spec['frame'],
                $spec['standard']
            );

            if ($stmt->execute()) {
                echo "  ✓ Added: {$spec['title']}\n";
                $addedCount++;
            } else {
                echo "  ✗ Failed: {$spec['title']} - " . $mysqli->error . "\n";
                $errorCount++;
            }
        }
        echo "\n";
    } else {
        echo "✗ Motor not found: ID $motorID\n\n";
        $skippedCount++;
    }
}

echo "\n================================================================================\n";
echo "COMPLETE\n";
echo "================================================================================\n";
echo "Total Motors Processed: $totalMotors\n";
echo "Specifications Added: $addedCount\n";
echo "Errors: $errorCount\n";
echo "Skipped: $skippedCount\n";
echo "================================================================================\n";

$mysqli->close();

?>
