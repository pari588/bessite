<?php
/**
 * Add Application Specific Motor Specifications to mx_motor_detail
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
echo "ADD APPLICATION SPECIFIC MOTOR SPECIFICATIONS\n";
echo "================================================================================\n\n";

// Application Specific Motors - All variants
$motorSpecs = array(
    // Special Application Motors (ID: 8)
    8 => array(
        array('title' => 'Special Application', 'output' => '0.37-75 kW', 'voltage' => '230/400/690V', 'frame' => 'NEMA/IEC', 'standard' => 'Custom Design'),
        array('title' => 'Customizable Design', 'output' => '3 Phase', 'voltage' => '50/60 Hz', 'frame' => 'Modular', 'standard' => 'Application-specific'),
        array('title' => 'Technical Support', 'output' => 'Engineering', 'voltage' => '', 'frame' => '', 'standard' => 'Tailored Solutions'),
        array('title' => 'Industry Solutions', 'output' => 'Various', 'voltage' => '', 'frame' => '', 'standard' => 'Flexible'),
    ),

    // Brake Motors (ID: 40)
    40 => array(
        array('title' => 'Integrated Brake', 'output' => '0.25-7.5 kW', 'voltage' => '230/400V', 'frame' => 'NEMA 56-180T', 'standard' => 'NEMA MG 1'),
        array('title' => 'Electromagnetic Brake', 'output' => '3 Phase', 'voltage' => '50/60 Hz', 'frame' => 'Brake Mounted', 'standard' => 'Spring Applied'),
        array('title' => 'Safety Critical', 'output' => 'Holding Brake', 'voltage' => '', 'frame' => '', 'standard' => 'Spring-loaded'),
        array('title' => 'Application Grade', 'output' => 'Material Handling', 'voltage' => '', 'frame' => '', 'standard' => 'Certified'),
    ),

    // Agricultural Equipment Motors (ID: 60)
    60 => array(
        array('title' => 'Agricultural Duty', 'output' => '0.37-5.5 kW', 'voltage' => '230/400V', 'frame' => 'NEMA 56-160T', 'standard' => 'Farm Duty'),
        array('title' => 'Pump Applications', 'output' => '3 Phase', 'voltage' => '50/60 Hz', 'frame' => 'Compact Design', 'standard' => 'Irrigation'),
        array('title' => 'Weather Resistant', 'output' => 'Outdoor Rated', 'voltage' => '', 'frame' => '', 'standard' => 'Corrosion Protected'),
        array('title' => 'Reliable Operation', 'output' => 'Continuous Duty', 'voltage' => '', 'frame' => '', 'standard' => 'Industrial Grade'),
    ),

    // Cooler Motors (ID: 57)
    57 => array(
        array('title' => 'Cooler Application', 'output' => '0.25-3 kW', 'voltage' => '230/400V', 'frame' => 'NEMA 48-160T', 'standard' => 'NEMA MG 1'),
        array('title' => 'Refrigeration Grade', 'output' => '1 or 3 Phase', 'voltage' => '50/60 Hz', 'frame' => 'Compact', 'standard' => 'Sealed Bearing'),
        array('title' => 'Cool Environment', 'output' => 'Low Temp Rated', 'voltage' => '', 'frame' => '', 'standard' => 'CFC Certified'),
        array('title' => 'High Reliability', 'output' => 'Premium Quality', 'voltage' => '', 'frame' => '', 'standard' => 'Food Safe'),
    ),

    // Double Cage Motor for Cement Mill (ID: 16)
    16 => array(
        array('title' => 'Double Cage Design', 'output' => '7.5-75 kW', 'voltage' => '230/400V', 'frame' => 'NEMA 160-280T', 'standard' => 'NEMA Design D'),
        array('title' => 'High Torque Rotor', 'output' => '3 Phase', 'voltage' => '50/60 Hz', 'frame' => 'Industrial Duty', 'standard' => 'Slip Ring'),
        array('title' => 'Cement Mill Grade', 'output' => 'Grinding App', 'voltage' => '', 'frame' => '', 'standard' => 'Heavy Duty'),
        array('title' => 'Superior Cooling', 'output' => 'Large Frame', 'voltage' => '', 'frame' => '', 'standard' => 'Extended Life'),
    ),

    // Double Cage Motor for Cement Mill (ID: 39) - Alternative variant
    39 => array(
        array('title' => 'Double Cage Design', 'output' => '7.5-75 kW', 'voltage' => '400/690V', 'frame' => 'NEMA 160-280T', 'standard' => 'NEMA Design D'),
        array('title' => 'Industrial Rotor', 'output' => '3 Phase High Pow', 'voltage' => '50/60 Hz', 'frame' => 'Reinforced Duty', 'standard' => 'Squirrel Cage'),
        array('title' => 'Mill Application', 'output' => 'Premium Class', 'voltage' => '', 'frame' => '', 'standard' => 'Heavy Duty'),
        array('title' => 'Extended Frame', 'output' => 'Large Capacity', 'voltage' => '', 'frame' => '', 'standard' => 'Mining Grade'),
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
