<?php
/**
 * Add CG Global Motor Specifications to existing motors
 * Inserts into mx_motor_specification table for products already in the system
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
echo "ADD CG GLOBAL MOTOR SPECIFICATIONS TO EXISTING PRODUCTS\n";
echo "================================================================================\n\n";

// CG Motor specifications mapped to existing motor IDs
$motorSpecs = array(
    // Air Cooled Induction Motors (ID: 15)
    15 => array(
        array('title' => 'Output Power', 'output' => '100-5000 kW', 'voltage' => '3-11 kV', 'frame' => 'IMB3: 315-1400mm', 'standard' => 'IEC 60034'),
        array('title' => 'Frame Type', 'output' => 'Squirrel Cage', 'voltage' => '3-11 kV', 'frame' => 'Fabricated', 'standard' => 'IS 325'),
        array('title' => 'Cooling Type', 'output' => 'Air Cooled', 'voltage' => '3-11 kV', 'frame' => '', 'standard' => 'IC 6A1A1'),
    ),
    // Water Cooled Induction Motors (ID: 17)
    17 => array(
        array('title' => 'Cooling Type', 'output' => 'Up to 5000 kW', 'voltage' => '3-11 kV', 'frame' => '740-2500', 'standard' => 'IC 8A1W7 (CACW)'),
        array('title' => 'Cooling Method', 'output' => 'Water Cooled', 'voltage' => '3-11 kV', 'frame' => '', 'standard' => 'IEC 60034'),
    ),
    // DC Motors (ID: 7)
    7 => array(
        array('title' => 'Output Range', 'output' => '2.2-1500 kW', 'voltage' => 'DC', 'frame' => 'IEC 100-630', 'standard' => 'IEC'),
        array('title' => 'Construction', 'output' => 'Laminated Yoke', 'voltage' => 'DC', 'frame' => '', 'standard' => 'IEC'),
        array('title' => 'Application', 'output' => 'Industrial', 'voltage' => 'DC', 'frame' => '', 'standard' => ''),
    ),
    // Large DC Machines (ID: 35)
    35 => array(
        array('title' => 'Output Range', 'output' => 'Up to 2000 kW', 'voltage' => 'DC Custom', 'frame' => 'Up to 710', 'standard' => 'Custom Design'),
        array('title' => 'Design Type', 'output' => 'Large DC Machines', 'voltage' => 'DC', 'frame' => '', 'standard' => 'Custom'),
    ),
    // Energy Efficient Motors HV (ID: 82 - if exists as main motor)
    82 => array(
        array('title' => 'Efficiency Class', 'output' => 'High Efficiency', 'voltage' => '3-11 kV', 'frame' => '', 'standard' => 'N Series'),
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

        // Check if specs already exist
        $check_stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM mx_motor_specification WHERE motorID = ?");
        $check_stmt->bind_param("i", $motorID);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();

        if ($check_row['cnt'] > 0) {
            echo "  ℹ Specifications already exist (" . $check_row['cnt'] . " records)\n\n";
            $skippedCount++;
            continue;
        }

        // Add specifications
        foreach ($specs as $spec) {
            $stmt = $mysqli->prepare("INSERT INTO mx_motor_specification
                                     (motorID, specTitle, specOutput, specVoltage, specFrameSize, specStandard, status)
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
