<?php
/**
 * Manually add CG Motor Specifications based on catalog data
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
echo "ADD CG MOTOR SPECIFICATIONS FROM CATALOG\n";
echo "================================================================================\n\n";

// Motor specifications based on CG catalog
$specifications = array(
    // High Voltage Motors
    array(
        'motorTitle' => 'Air Cooled Induction Motors IC 6A1A1 IC 6A1A6 IC 6A6A6 CACA',
        'specs' => array(
            array('title' => 'Output Power', 'output' => '100 kW to 5000 kW', 'voltage' => '', 'frame' => '', 'standard' => ''),
            array('title' => 'Frame Size IMB3', 'output' => '', 'voltage' => '', 'frame' => '315 to 1400 mm', 'standard' => ''),
            array('title' => 'Frame Size IMV1', 'output' => '', 'voltage' => '', 'frame' => '740 to 2500', 'standard' => ''),
            array('title' => 'Standards', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IEC 60034 / IS 325'),
        )
    ),
    array(
        'motorTitle' => 'Water Cooled Induction Motors IC 8A1W7 CACW',
        'specs' => array(
            array('title' => 'Cooling Type', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IC 8A1W7 (CACW)'),
            array('title' => 'Standards', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IEC 60034 / IS 325'),
        )
    ),
    array(
        'motorTitle' => 'Open Air Type Induction Motor IC 0A1 IC 0A6 SPDP',
        'specs' => array(
            array('title' => 'Cooling Type', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IC 0A1, IC 0A6 (SPDP)'),
            array('title' => 'Standards', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IEC 60034 / IS 325'),
        )
    ),
    array(
        'motorTitle' => 'Tube Ventilated Induction Motor IC 5A1A1 IC 5A1A6 TETV',
        'specs' => array(
            array('title' => 'Cooling Type', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IC 5A1A1, IC 5A1A6 (TETV)'),
            array('title' => 'Standards', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IEC 60034 / IS 325'),
        )
    ),
    array(
        'motorTitle' => 'Fan Cooled Induction Motor IC 4A1A1 IC 4A1A6 TEFC',
        'specs' => array(
            array('title' => 'Cooling Type', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IC 4A1A1, IC 4A1A6 (TEFC)'),
            array('title' => 'Series', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'Global Series'),
        )
    ),
    array(
        'motorTitle' => 'Energy Efficient Motors HV N Series',
        'specs' => array(
            array('title' => 'Series', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'N Series'),
            array('title' => 'Type', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'High Voltage Energy Efficient'),
        )
    ),

    // Low Voltage Motors
    array(
        'motorTitle' => 'Cast Iron enclosure motors Safe Area',
        'specs' => array(
            array('title' => 'Output Range', 'output' => '0.37 kW to 710 kW', 'voltage' => '', 'frame' => '', 'standard' => ''),
            array('title' => 'Frame Sizes', 'output' => '', 'voltage' => '', 'frame' => '63 to 500', 'standard' => ''),
            array('title' => 'Standard', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IEC'),
        )
    ),
    array(
        'motorTitle' => 'Aluminum enclosure motors',
        'specs' => array(
            array('title' => 'Output Range', 'output' => '0.50 Hp to 20 Hp', 'voltage' => '', 'frame' => '', 'standard' => ''),
            array('title' => 'Frame Sizes', 'output' => '', 'voltage' => '', 'frame' => 'NEMA 56 to 256', 'standard' => ''),
            array('title' => 'Standard', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'NEMA'),
        )
    ),
    array(
        'motorTitle' => 'Aluminium enclosure motors Safe area',
        'specs' => array(
            array('title' => 'Output Range', 'output' => '0.18 kW to 11 kW', 'voltage' => '', 'frame' => '', 'standard' => ''),
            array('title' => 'Frame Sizes', 'output' => '', 'voltage' => '', 'frame' => 'IEC 63 to 160', 'standard' => ''),
            array('title' => 'Standard', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IEC'),
        )
    ),
    array(
        'motorTitle' => 'Slip Ring Motors LV',
        'specs' => array(
            array('title' => 'Output Range', 'output' => '3.7 kW to 350 kW', 'voltage' => '', 'frame' => '', 'standard' => ''),
            array('title' => 'Protection', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'TEFC (IP55) & Drip Proof (IP23)'),
        )
    ),

    // Energy Efficient
    array(
        'motorTitle' => 'Super Premium IE4 Efficiency Apex Series',
        'specs' => array(
            array('title' => 'Efficiency Class', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IE4 (Super Premium)'),
            array('title' => 'Series', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'Apex Series'),
        )
    ),
    array(
        'motorTitle' => 'International Efficiency IE2 IE3 Apex series',
        'specs' => array(
            array('title' => 'Efficiency Class', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'IE2 / IE3'),
            array('title' => 'Series', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'Apex Series'),
        )
    ),
    array(
        'motorTitle' => 'Totally Enclosed Fan Cooled Induction Motor NG Series',
        'specs' => array(
            array('title' => 'Cooling Type', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'TEFC'),
            array('title' => 'Series', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'NG Series / Global Series'),
        )
    ),

    // DC Motors
    array(
        'motorTitle' => 'DC Motors',
        'specs' => array(
            array('title' => 'Output Range', 'output' => '2.2 kW to 1500 kW', 'voltage' => '', 'frame' => '', 'standard' => ''),
            array('title' => 'Frame Sizes', 'output' => '', 'voltage' => '', 'frame' => 'IEC 100 to 630', 'standard' => ''),
            array('title' => 'Construction', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'Laminated Yoke'),
        )
    ),
    array(
        'motorTitle' => 'Large DC Machines',
        'specs' => array(
            array('title' => 'Output Range', 'output' => 'Up to 2000 kW', 'voltage' => '', 'frame' => '', 'standard' => ''),
            array('title' => 'Frame Size', 'output' => '', 'voltage' => '', 'frame' => 'Up to 710', 'standard' => ''),
        )
    ),
);

$addedCount = 0;
$skippedCount = 0;

foreach ($specifications as $spec_data) {
    $motorTitle = $spec_data['motorTitle'];
    $specs = $spec_data['specs'];

    // Find motor ID
    $stmt = $mysqli->prepare("SELECT motorID FROM mx_motor WHERE motorTitle LIKE ?");
    $searchTerm = '%' . $motorTitle . '%';
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $motorID = $row['motorID'];

        // Check if specs already exist
        $check_stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM mx_motor_specification WHERE motorID = ?");
        $check_stmt->bind_param("i", $motorID);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();

        if ($check_row['cnt'] > 0) {
            echo "Skipped: $motorTitle (ID: $motorID) - Specs already exist\n";
            $skippedCount++;
            continue;
        }

        echo "Adding specs for: $motorTitle (ID: $motorID)\n";

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
            }
        }
        echo "\n";
    } else {
        echo "✗ Motor not found: $motorTitle\n\n";
        $skippedCount++;
    }
}

echo "\n================================================================================\n";
echo "COMPLETE\n";
echo "================================================================================\n";
echo "Specifications Added: $addedCount\n";
echo "Skipped: $skippedCount\n";
echo "================================================================================\n";

$mysqli->close();

?>
