<?php
/**
 * Add CG Global Motor Specifications to mx_motor_detail table
 * For existing motors: Air Cooled (15), Water Cooled (17), DC Motors (7), Large DC Machines (35)
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
echo "ADD CG GLOBAL MOTOR SPECIFICATIONS TO mx_motor_detail\n";
echo "================================================================================\n\n";

// CG Motor specifications for existing motors
$motorSpecs = array(
    // Air Cooled Induction Motors (ID: 15)
    15 => array(
        array('title' => 'High Voltage AC Motors', 'output' => '100-5000 kW', 'voltage' => '3-11 kV', 'frame' => 'IMB3: 315-1400mm', 'standard' => 'IEC 60034'),
        array('title' => 'Squirrel Cage Rotor', 'output' => 'Standard Design', 'voltage' => '3-11 kV', 'frame' => 'Fabricated Steel', 'standard' => 'IS 325'),
        array('title' => 'IC 6A1A1 IC 6A1A6', 'output' => 'Air Cooled', 'voltage' => '3-11 kV', 'frame' => '', 'standard' => 'CACA'),
    ),
    // Water Cooled Induction Motors (ID: 17)
    17 => array(
        array('title' => 'IC 8A1W7 CACW', 'output' => 'Up to 5000 kW', 'voltage' => '3-11 kV', 'frame' => '740-2500', 'standard' => 'Water Cooled'),
        array('title' => 'High Power Ratings', 'output' => 'Various', 'voltage' => '3-11 kV', 'frame' => '740-2500', 'standard' => 'IEC 60034'),
    ),
    // DC Motors (ID: 7)
    7 => array(
        array('title' => 'DC Power Output', 'output' => '2.2-1500 kW', 'voltage' => 'DC', 'frame' => 'IEC 100-630', 'standard' => 'IEC'),
        array('title' => 'Laminated Yoke', 'output' => 'Field Coils', 'voltage' => 'DC', 'frame' => '', 'standard' => 'Standard Design'),
    ),
    // Large DC Machines (ID: 35)
    35 => array(
        array('title' => 'Large Format', 'output' => 'Up to 2000 kW', 'voltage' => 'DC', 'frame' => 'Up to 710', 'standard' => 'Custom'),
        array('title' => 'Industrial DC', 'output' => 'Heavy Duty', 'voltage' => 'DC', 'frame' => 'Up to 710', 'standard' => 'Special Design'),
    ),
);

$addedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($motorSpecs as $motorID => $specs) {
    // Verify motor exists
    $stmt = $mysqli->prepare("SELECT motorTitle FROM mx_motor WHERE motorID = ?");
    $stmt->bind_param("i", $motorID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $motor = $result->fetch_assoc();
        echo "Motor: {$motor['motorTitle']} (ID: $motorID)\n";

        // Add specifications to mx_motor_detail (alongside existing ones)
        foreach ($specs as $spec) {
            $stmt = $mysqli->prepare("INSERT INTO mx_motor_detail
                                     (motorID, descriptionTitle, descriptionOutput, descriptionVoltage, descriptionFrameSize, descriptionStandard, status)
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
echo "Specifications Added: $addedCount\n";
echo "Errors: $errorCount\n";
echo "Skipped: $skippedCount\n";
echo "================================================================================\n";

$mysqli->close();

?>
