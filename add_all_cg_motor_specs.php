<?php
/**
 * Add CG Global Motor Specifications to mx_motor_detail
 * For all motors under High/Low Voltage AC & DC Motors categories
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
echo "ADD CG GLOBAL MOTOR SPECIFICATIONS - ALL CATEGORIES\n";
echo "================================================================================\n\n";

// Comprehensive CG Motor specifications for all related motor types
$motorSpecs = array(
    // High Voltage Motors
    15 => array( // Air Cooled Induction Motors
        array('title' => 'High Voltage AC Motors', 'output' => '100-5000 kW', 'voltage' => '3-11 kV', 'frame' => 'IMB3: 315-1400mm', 'standard' => 'IEC 60034'),
        array('title' => 'Squirrel Cage Rotor', 'output' => 'Standard Design', 'voltage' => '3-11 kV', 'frame' => 'Fabricated Steel', 'standard' => 'IS 325'),
        array('title' => 'IC 6A1A1 IC 6A1A6', 'output' => 'Air Cooled', 'voltage' => '3-11 kV', 'frame' => '', 'standard' => 'CACA'),
    ),
    17 => array( // Water Cooled Induction Motors
        array('title' => 'IC 8A1W7 CACW', 'output' => 'Up to 5000 kW', 'voltage' => '3-11 kV', 'frame' => '740-2500', 'standard' => 'Water Cooled'),
        array('title' => 'High Power Ratings', 'output' => 'Various', 'voltage' => '3-11 kV', 'frame' => '740-2500', 'standard' => 'IEC 60034'),
    ),
    18 => array( // Open Air Type Induction Motor
        array('title' => 'Open Air Type', 'output' => 'Various', 'voltage' => '3-11 kV', 'frame' => 'Variable', 'standard' => 'SPDP'),
        array('title' => 'Screen Protected DP', 'output' => 'Industrial', 'voltage' => '3-11 kV', 'frame' => '', 'standard' => 'IC 0A1/0A6'),
    ),
    19 => array( // Tube Ventilated Induction Motor
        array('title' => 'Tube Ventilated Type', 'output' => 'Various', 'voltage' => '3-11 kV', 'frame' => 'Variable', 'standard' => 'TETV'),
        array('title' => 'IC 5A1A1 IC 5A1A6', 'output' => 'Industrial', 'voltage' => '3-11 kV', 'frame' => '', 'standard' => 'IEC 60034'),
    ),
    20 => array( // Fan Cooled Induction Motor
        array('title' => 'Fan Cooled TEFC', 'output' => 'Various', 'voltage' => '3-11 kV', 'frame' => 'Variable', 'standard' => 'IEC'),
        array('title' => 'Totally Enclosed', 'output' => 'Industrial Grade', 'voltage' => '3-11 kV', 'frame' => '', 'standard' => 'Global Series'),
    ),

    // Low Voltage Motors
    27 => array( // Cast Iron Enclosure Motors
        array('title' => 'Cast Iron Body', 'output' => '0.37-710 kW', 'voltage' => '400V, 690V', 'frame' => 'Frame 63-500', 'standard' => 'IEC'),
        array('title' => 'Squirrel Cage Type', 'output' => 'Standard Duty', 'voltage' => '400-690V', 'frame' => '', 'standard' => 'IS 1231'),
    ),
    28 => array( // Aluminum Enclosure Motors
        array('title' => 'Aluminum Body', 'output' => '0.5-20 Hp', 'voltage' => '230/460V', 'frame' => 'NEMA 56-256', 'standard' => 'NEMA MG 1'),
        array('title' => 'Premium Efficiency', 'output' => 'Light Duty', 'voltage' => 'Single/Three Phase', 'frame' => '', 'standard' => 'NEMA'),
    ),
    29 => array( // Slip Ring Motors (LV)
        array('title' => 'Slip Ring Rotor', 'output' => '3.7-350 kW', 'voltage' => '400V', 'frame' => 'Variable', 'standard' => 'IEC'),
        array('title' => 'Variable Speed', 'output' => 'Wound Rotor', 'voltage' => '400V', 'frame' => '', 'standard' => 'Industrial'),
    ),
    12 => array( // Slip Ring Induction Motors
        array('title' => 'Slip Ring Type', 'output' => 'Various', 'voltage' => 'Various', 'frame' => 'Variable', 'standard' => 'IEC'),
        array('title' => 'Wound Rotor Design', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => ''),
    ),

    // Energy Efficient Motors
    4 => array( // Energy Efficient Motors
        array('title' => 'Super Premium IE4', 'output' => 'Various', 'voltage' => '400-690V', 'frame' => 'Various', 'standard' => 'IE4 Class'),
        array('title' => 'Apex Series', 'output' => 'High Efficiency', 'voltage' => '', 'frame' => '', 'standard' => 'Premium'),
    ),
    9 => array( // Energy Efficient Motors
        array('title' => 'IE3 Energy Efficient', 'output' => 'Various', 'voltage' => '400V', 'frame' => 'Various', 'standard' => 'IE3 Class'),
        array('title' => 'Premium Design', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => ''),
    ),
    21 => array( // Energy Efficient Motors HV - N Series
        array('title' => 'N Series HV', 'output' => 'Various', 'voltage' => '3-11 kV', 'frame' => 'Various', 'standard' => 'High Efficiency'),
        array('title' => 'Energy Optimized', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => 'IE3/IE4'),
    ),

    // Hazardous Area Motors (LV)
    6 => array( // Motors for Hazardous Area (LV)
        array('title' => 'Flame Proof Ex d', 'output' => 'Various', 'voltage' => '400V', 'frame' => 'Variable', 'standard' => 'IEC 60034'),
        array('title' => 'Hazardous Duty', 'output' => 'Industrial', 'voltage' => 'Single/Three Phase', 'frame' => '', 'standard' => 'Certified'),
    ),
    10 => array( // Motors for Hazardous Area (LV)
        array('title' => 'Hazardous Zone', 'output' => 'Various', 'voltage' => '400V', 'frame' => 'Variable', 'standard' => 'ATEX/IEC'),
        array('title' => 'Safety Certified', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => ''),
    ),
    23 => array( // Flame Proof Motors Ex db (LV)
        array('title' => 'Flame Proof Ex db', 'output' => 'Various', 'voltage' => '400V', 'frame' => 'Variable', 'standard' => 'Increased Safety'),
        array('title' => 'Dust Hazardous', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => 'Certified'),
    ),
    33 => array( // Flame Proof Motors Ex db (LV)
        array('title' => 'Increased Safety', 'output' => 'Various', 'voltage' => '400V', 'frame' => 'Variable', 'standard' => 'Category 3'),
        array('title' => 'Zone Classification', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => ''),
    ),
    89 => array( // Flame Proof Motors Ex db LV
        array('title' => 'Ex db Certified', 'output' => 'Various', 'voltage' => '400V', 'frame' => 'Variable', 'standard' => 'ATEX II 2G'),
        array('title' => 'Dust Proof Design', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => ''),
    ),

    // Hazardous Area Motors (HV)
    5 => array( // Motors for Hazardous Areas (HV)
        array('title' => 'HV Flame Proof', 'output' => 'Various', 'voltage' => '3-11 kV', 'frame' => 'Variable', 'standard' => 'IEC 60034'),
        array('title' => 'High Voltage Duty', 'output' => 'Industrial', 'voltage' => '3-11 kV', 'frame' => '', 'standard' => 'Certified'),
    ),
    37 => array( // Flame Proof Motors HV
        array('title' => 'HV Flame Proof Ex d', 'output' => 'Various', 'voltage' => '3-11 kV', 'frame' => 'Variable', 'standard' => 'ATEX Certified'),
        array('title' => 'High Voltage Zone', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => ''),
    ),
    97 => array( // Flame Proof Large Motors Ex d HV
        array('title' => 'Large Format HV', 'output' => 'High Power', 'voltage' => '3-11 kV', 'frame' => 'Large Frame', 'standard' => 'Ex d'),
        array('title' => 'Industrial Heavy', 'output' => 'Various', 'voltage' => '', 'frame' => '', 'standard' => 'Certified'),
    ),
    94 => array( // Flame Proof Motors Ex d LV
        array('title' => 'LV Flame Proof', 'output' => 'Various', 'voltage' => '400V', 'frame' => 'Variable', 'standard' => 'Ex d Certified'),
        array('title' => 'Low Voltage Zone', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => ''),
    ),

    // DC Motors
    7 => array( // DC Motors
        array('title' => 'DC Power Output', 'output' => '2.2-1500 kW', 'voltage' => 'DC', 'frame' => 'IEC 100-630', 'standard' => 'IEC'),
        array('title' => 'Laminated Yoke', 'output' => 'Field Coils', 'voltage' => 'DC', 'frame' => '', 'standard' => 'Standard Design'),
    ),
    11 => array( // Laminated Yoke DC Motor
        array('title' => 'Laminated Yoke', 'output' => 'Various', 'voltage' => 'DC', 'frame' => 'Variable', 'standard' => 'IEC'),
        array('title' => 'DC Industrial', 'output' => 'Heavy Duty', 'voltage' => '', 'frame' => '', 'standard' => ''),
    ),
    36 => array( // DC Motors
        array('title' => 'DC Industrial Motors', 'output' => 'Various', 'voltage' => 'DC', 'frame' => 'Variable', 'standard' => 'IEC'),
        array('title' => 'Standard Duty', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => ''),
    ),
    35 => array( // Large DC Machines
        array('title' => 'Large Format', 'output' => 'Up to 2000 kW', 'voltage' => 'DC', 'frame' => 'Up to 710', 'standard' => 'Custom'),
        array('title' => 'Industrial Heavy', 'output' => 'Heavy Duty', 'voltage' => '', 'frame' => '', 'standard' => 'Special Design'),
    ),

    // Special Application Motors
    16 => array( // Double Cage Motor for Cement Mill
        array('title' => 'Double Cage Design', 'output' => 'Various', 'voltage' => '3-11 kV', 'frame' => 'Variable', 'standard' => 'IEC'),
        array('title' => 'Cement Mill', 'output' => 'Heavy Duty', 'voltage' => '', 'frame' => '', 'standard' => 'Industrial'),
    ),
    39 => array( // Double Cage Motor for Cement Mill
        array('title' => 'Double Cage Type', 'output' => 'High Torque', 'voltage' => 'Various', 'frame' => 'Variable', 'standard' => ''),
        array('title' => 'Cement Mill App', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => ''),
    ),
    32 => array( // Totally Enclosed Fan Cooled Induction Motor - NG Series
        array('title' => 'NG Series TEFC', 'output' => 'Various', 'voltage' => '400V', 'frame' => 'Variable', 'standard' => 'Global Series'),
        array('title' => 'Fan Cooled Type', 'output' => 'Industrial', 'voltage' => '', 'frame' => '', 'standard' => 'IEC 60034'),
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
echo "Total Motors Processed: $totalMotors\n";
echo "Specifications Added: $addedCount\n";
echo "Errors: $errorCount\n";
echo "Skipped: $skippedCount\n";
echo "================================================================================\n";

$mysqli->close();

?>
