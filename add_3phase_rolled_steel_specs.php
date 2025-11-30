<?php
/**
 * Add 3 Phase Rolled Steel Body Motors Specifications to mx_motor_detail
 * All variants from CG Global Commercial category
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
echo "ADD 3 PHASE ROLLED STEEL BODY MOTOR SPECIFICATIONS\n";
echo "================================================================================\n\n";

// 3 Phase Rolled Steel Body Motors - All variants
$motorSpecs = array(
    // Standard Duty (ID: 52)
    52 => array(
        array('title' => '3 Phase Rolled Steel', 'output' => '0.37-15 kW', 'voltage' => '230/400/690V', 'frame' => 'NEMA 56-215T', 'standard' => 'NEMA MG 1'),
        array('title' => 'Standard Duty', 'output' => 'Industrial Duty', 'voltage' => '3 Phase', 'frame' => 'Cast Iron', 'standard' => 'General Purpose'),
        array('title' => 'Squirrel Cage', 'output' => 'AC Induction', 'voltage' => '50/60 Hz', 'frame' => '', 'standard' => 'NEMA Design B'),
        array('title' => 'Standard Design', 'output' => 'Economy Class', 'voltage' => '', 'frame' => '', 'standard' => 'IEC 60034'),
    ),

    // Heavy Duty (ID: 53)
    53 => array(
        array('title' => '3 Phase Rolled Steel', 'output' => '0.37-15 kW', 'voltage' => '230/400/690V', 'frame' => 'NEMA 56-215T', 'standard' => 'NEMA MG 1'),
        array('title' => 'Heavy Duty Design', 'output' => 'Robust Construction', 'voltage' => '3 Phase', 'frame' => 'Reinforced', 'standard' => 'Industrial'),
        array('title' => 'Squirrel Cage', 'output' => 'AC Induction', 'voltage' => '50/60 Hz', 'frame' => '', 'standard' => 'NEMA Design B'),
        array('title' => 'Premium Durability', 'output' => 'Extended Life', 'voltage' => '', 'frame' => '', 'standard' => 'Heavy Duty'),
    ),

    // Premium Efficiency (ID: 54)
    54 => array(
        array('title' => '3 Phase Rolled Steel', 'output' => '0.37-15 kW', 'voltage' => '230/400/690V', 'frame' => 'NEMA 56-215T', 'standard' => 'NEMA MG 1'),
        array('title' => 'Premium Efficiency', 'output' => 'IE3 Efficiency', 'voltage' => '3 Phase', 'frame' => 'Optimized', 'standard' => 'Energy Efficient'),
        array('title' => 'Advanced Design', 'output' => 'Low Loss Core', 'voltage' => '50/60 Hz', 'frame' => '', 'standard' => 'IE3 Class'),
        array('title' => 'High Performance', 'output' => 'Superior Efficiency', 'voltage' => '', 'frame' => '', 'standard' => 'IEC 60034-30'),
    ),

    // Explosion Proof (ID: 55)
    55 => array(
        array('title' => '3 Phase Rolled Steel', 'output' => '0.37-15 kW', 'voltage' => '230/400/690V', 'frame' => 'NEMA 56-215T', 'standard' => 'NEMA MG 1'),
        array('title' => 'Explosion Proof', 'output' => 'Hazardous Area', 'voltage' => '3 Phase', 'frame' => 'Sealed Encl', 'standard' => 'ATEX Certified'),
        array('title' => 'Safety Certified', 'output' => 'Ex d Flange', 'voltage' => '50/60 Hz', 'frame' => '', 'standard' => 'IEC 60034'),
        array('title' => 'Hazardous Duty', 'output' => 'Zone Safe', 'voltage' => '', 'frame' => '', 'standard' => 'Certified'),
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
