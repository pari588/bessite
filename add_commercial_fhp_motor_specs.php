<?php
/**
 * Add Commercial/FHP Motor Specifications to mx_motor_detail
 * For Fractional Horsepower Motors from CG Global
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
echo "ADD CG GLOBAL COMMERCIAL/FHP MOTOR SPECIFICATIONS\n";
echo "================================================================================\n\n";

// Commercial/FHP Motor specifications based on CG catalog standards
$motorSpecs = array(
    // Capacitor Start Motors (ID: 48)
    48 => array(
        array('title' => 'Capacitor Start Type', 'output' => '0.37-3.7 kW', 'voltage' => '230V Single Ph', 'frame' => 'NEMA 48-145T', 'standard' => 'NEMA MG 1'),
        array('title' => 'FHP Commercial', 'output' => 'Single Phase', 'voltage' => '230V/460V', 'frame' => 'Compact', 'standard' => 'Commercial Duty'),
        array('title' => 'Torque Characteristics', 'output' => 'High Starting', 'voltage' => '230V', 'frame' => '', 'standard' => 'Premium'),
    ),

    // Capacitor Run Motors (ID: 49)
    49 => array(
        array('title' => 'Capacitor Run Type', 'output' => '0.25-2.2 kW', 'voltage' => '230V Single Ph', 'frame' => 'NEMA 48-145T', 'standard' => 'NEMA MG 1'),
        array('title' => 'FHP Commercial', 'output' => 'Single Phase', 'voltage' => '230V', 'frame' => 'Compact', 'standard' => 'Commercial Duty'),
        array('title' => 'Efficiency Design', 'output' => 'Energy Efficient', 'voltage' => '', 'frame' => '', 'standard' => 'Premium'),
    ),

    // Permanent Split Capacitor Motors (ID: 50)
    50 => array(
        array('title' => 'PSC Motor Type', 'output' => '0.18-2.2 kW', 'voltage' => '230V Single Ph', 'frame' => 'NEMA 48-145T', 'standard' => 'NEMA MG 1'),
        array('title' => 'FHP Commercial', 'output' => 'Single Phase', 'voltage' => '230V', 'frame' => 'Compact', 'standard' => 'Commercial Duty'),
        array('title' => 'Continuous Duty', 'output' => 'Reliable', 'voltage' => '', 'frame' => '', 'standard' => 'Standard'),
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
