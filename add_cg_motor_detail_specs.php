<?php
/**
 * Add CG Motor Specifications to mx_motor_detail table
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
echo "ADD CG MOTOR SPECIFICATIONS TO mx_motor_detail\n";
echo "================================================================================\n\n";

// Motor specifications based on CG catalog
$specifications = array(
    // High Voltage Motors
    array(
        'motorTitle' => 'Air Cooled Induction Motors IC 6A1A1 IC 6A1A6 IC 6A6A6 CACA',
        'specs' => array(
            array('title' => 'Output Power', 'output' => '100-5000 kW', 'voltage' => '3-11 kV', 'frame' => 'IMB3: 315-1400mm', 'standard' => 'IEC 60034'),
            array('title' => 'Frame Type', 'output' => 'Squirrel Cage', 'voltage' => '3-11 kV', 'frame' => 'Fabricated', 'standard' => 'IS 325'),
        )
    ),
    array(
        'motorTitle' => 'Water Cooled Induction Motors IC 8A1W7 CACW',
        'specs' => array(
            array('title' => 'Cooling Type', 'output' => 'Up to 5000 kW', 'voltage' => '3-11 kV', 'frame' => '740 to 2500', 'standard' => 'IC 8A1W7 (CACW)'),
        )
    ),
    array(
        'motorTitle' => 'Open Air Type Induction Motor IC 0A1 IC 0A6 SPDP',
        'specs' => array(
            array('title' => 'Output Power', 'output' => 'Up to 5000 kW', 'voltage' => '3-11 kV', 'frame' => 'Various', 'standard' => 'SPDP (Screen Protected Drip Proof)'),
        )
    ),
    array(
        'motorTitle' => 'Tube Ventilated Induction Motor IC 5A1A1 IC 5A1A6 TETV',
        'specs' => array(
            array('title' => 'Cooling Type', 'output' => 'Up to 5000 kW', 'voltage' => '3-11 kV', 'frame' => 'Various', 'standard' => 'TETV (Totally Enclosed Tube Ventilated)'),
        )
    ),
    array(
        'motorTitle' => 'Fan Cooled Induction Motor IC 4A1A1 IC 4A1A6 TEFC',
        'specs' => array(
            array('title' => 'Output Power', 'output' => 'Up to 5000 kW', 'voltage' => '3-11 kV', 'frame' => 'Various', 'standard' => 'TEFC (Totally Enclosed Fan Cooled)'),
            array('title' => 'Series', 'output' => '', 'voltage' => '', 'frame' => '', 'standard' => 'Global Series'),
        )
    ),
    array(
        'motorTitle' => 'Energy Efficient Motors HV N Series',
        'specs' => array(
            array('title' => 'Efficiency Class', 'output' => 'Up to 5000 kW', 'voltage' => '3-11 kV', 'frame' => 'Various', 'standard' => 'N Series (High Efficiency)'),
        )
    ),
    array(
        'motorTitle' => 'Cast Iron enclosure motors Safe Area',
        'specs' => array(
            array('title' => 'Output Range', 'output' => '0.37 kW to 710 kW', 'voltage' => '400V, 690V', 'frame' => 'Frame 63 to 500', 'standard' => 'IEC'),
        )
    ),
    array(
        'motorTitle' => 'Aluminum enclosure motors',
        'specs' => array(
            array('title' => 'Output Range', 'output' => '0.50 Hp to 20 Hp', 'voltage' => '230V / 460V', 'frame' => 'NEMA 56 to 256', 'standard' => 'NEMA MG 1'),
        )
    ),
    array(
        'motorTitle' => 'Aluminium enclosure motors Safe area',
        'specs' => array(
            array('title' => 'Output Range', 'output' => '0.18 kW to 11 kW', 'voltage' => '400V', 'frame' => 'IEC 63 to 160', 'standard' => 'IEC'),
        )
    ),
    array(
        'motorTitle' => 'Slip Ring Motors LV',
        'specs' => array(
            array('title' => 'Output Range', 'output' => '3.7 kW to 350 kW', 'voltage' => '400V', 'frame' => 'Various', 'standard' => 'IEC'),
        )
    ),
    array(
        'motorTitle' => 'Super Premium IE4 Efficiency Apex Series',
        'specs' => array(
            array('title' => 'Efficiency', 'output' => 'Various', 'voltage' => '400V, 690V', 'frame' => 'Various', 'standard' => 'IE4 (Super Premium)'),
        )
    ),
    array(
        'motorTitle' => 'International Efficiency IE2 IE3 Apex series',
        'specs' => array(
            array('title' => 'Efficiency', 'output' => 'Various', 'voltage' => '400V, 690V', 'frame' => 'Various', 'standard' => 'IE2 / IE3'),
        )
    ),
    array(
        'motorTitle' => 'Totally Enclosed Fan Cooled Induction Motor NG Series',
        'specs' => array(
            array('title' => 'Cooling Type', 'output' => 'Various', 'voltage' => '400V', 'frame' => 'Various', 'standard' => 'TEFC / NG Series'),
        )
    ),
    array(
        'motorTitle' => 'DC Motors',
        'specs' => array(
            array('title' => 'Output Range', 'output' => '2.2 kW to 1500 kW', 'voltage' => 'DC (Various)', 'frame' => 'IEC 100 to 630', 'standard' => 'IEC'),
            array('title' => 'Construction', 'output' => 'Laminated Yoke', 'voltage' => 'DC', 'frame' => 'Various', 'standard' => 'IEC'),
        )
    ),
    array(
        'motorTitle' => 'Large DC Machines',
        'specs' => array(
            array('title' => 'Output Range', 'output' => 'Up to 2000 kW', 'voltage' => 'DC (Custom)', 'frame' => 'Up to 710', 'standard' => 'Custom Design'),
        )
    ),
);

$addedCount = 0;
$skippedCount = 0;
$errorCount = 0;

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
        $check_stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM mx_motor_detail WHERE motorID = ?");
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
        echo "✗ Motor not found: $motorTitle\n\n";
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
