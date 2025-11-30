<?php
/**
 * Update Remaining Motor Content - Complete All Motors
 */

require 'config.inc.php';

$mysqli = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Complete motor descriptions for all remaining motors
$motorUpdates = array(
    // Bajaj Motor
    14 => '<p>Bajaj FHP (Fractional Horsepower) motors are reliable single-phase and three-phase motors designed for household, commercial, and light industrial applications. These motors offer excellent performance with low power consumption.</p>

<p>Available in various power ratings with robust construction, Bajaj motors are widely used in ceiling fans, water pumps, and small machine tools. All motors comply with Indian Standards and are manufactured with premium quality materials ensuring long operational life and reliability.</p>',

    // Pressurized Motor Ex \'p\' (HV)
    47 => '<p>Pressurized Motor Ex \'p\' (HV) is a type of equipment protection by pressurization (Ex p). The enclosure of the pressurizing apparatus is designed such that, when a flammable gas or vapor-air mixture enters, the average pressure inside is always higher than the surrounding atmosphere.</p>

<p>These motors are suitable for hazardous areas Zone 1, Zone 2, and Class 1 Division 1 and 2 applications. The pressurized construction provides effective protection against ignition sources while maintaining high operational efficiency. Available in power ratings from 0.75 kW to 150 kW with voltages up to 11 kV.</p>',

    // Cooler Motors
    57 => '<p>Cooler Motors are specially designed for air cooler and refrigeration applications with enhanced thermal management. These 3-phase motors feature robust cooling fins and superior heat dissipation for continuous operation in cooling equipment.</p>

<p>Manufactured to withstand demanding operational conditions in air cooling systems, these motors provide reliable performance with power ratings from 1.1 kW to 37 kW. All cooler motors meet IS 4691 and IEC 60034-5 standards with excellent durability and minimal maintenance requirements.</p>',

    // Huller Motors
    56 => '<p>Huller Motors are specialized 3-phase motors designed specifically for rice milling and grain processing equipment. These motors are engineered to handle the continuous load demands of huller machines with robust construction and high torque characteristics.</p>

<p>Available in power ratings from 2.2 kW to 7.5 kW with frame sizes 90 to 112. These motors feature reinforced shafts and superior mechanical protection suitable for dusty milling environments. All huller motors comply with IS 4691 and IEC 60034-5 standards.</p>',

    // Flange Motors
    58 => '<p>Flange Mounted Motors are 3-phase motors with integral flange coupling for direct connection to driven equipment like pumps, blowers, and compressors. These motors eliminate the need for separate couplings, reducing installation space and complexity.</p>

<p>Available in power ratings from 0.5 kW to 30 kW with frame sizes 56 to 160, flange motors are ideal for industrial applications requiring compact design and efficient power transmission. All flange motors meet IS 4691 and IEC 60034-5 standards with superior mechanical strength.</p>',

    // Textile Industry Motors
    59 => '<p>Textile Industry Motors are specialized 3-phase motors engineered for spinning mills, weaving looms, and other textile machinery. These motors feature precise speed control and low vibration characteristics essential for textile operations.</p>

<p>With power ratings from 1.1 kW to 50 kW and frame sizes 90 to 180, textile motors are designed to withstand the demanding conditions of textile mills including exposure to lint and dust. All motors comply with IS 4691 and IEC 60034-5 standards with excellent thermal stability and long operational life.</p>',

    // Agricultural Equipment Motors
    60 => '<p>Agricultural Equipment Motors are 3-phase motors designed for agricultural machinery including pumps, threshers, and grain grinding equipment. These motors are engineered for reliable operation in farm environments with varying load conditions.</p>

<p>Available in power ratings from 0.75 kW to 10 kW, agricultural motors feature robust construction with excellent mechanical protection against dust and moisture. All motors meet IS 4691 and IEC 60034-5 standards and are manufactured to withstand the demanding operational requirements of agricultural applications.</p>',

    // Emotron Drives - FDU - AC Drives
    61 => '<p>Emotron FDU - AC Drives are advanced variable frequency drives designed for controlled speed and torque management of AC motors. These drives provide smooth acceleration, energy savings, and precise motor control suitable for industrial applications.</p>

<p>The FDU series offers advanced features including ramp function, automatic restart, and multiple control modes. These drives are particularly effective for pump, fan, and compressor applications where energy savings and smooth operation are critical. Emotron drives deliver efficient motor speed control with minimal electrical losses.</p>',

    // Emotron VFX - AC Drives
    62 => '<p>Emotron VFX - AC Drives are high-performance variable frequency drives featuring advanced motor control technology. These drives are designed for precise speed regulation, torque control, and energy optimization of 3-phase AC motors.</p>

<p>The VFX series incorporates modern power electronics technology with user-friendly programming interfaces. These drives are suitable for centrifugal and positive displacement pump applications, fan and blower systems, and general industrial machinery. Emotron VFX drives provide superior performance with reliable operation and energy efficiency.</p>',

    // Emotron AFE - Active Front End Drives
    63 => '<p>Emotron AFE (Active Front End) Drives are regenerative variable frequency drives capable of returning energy to the power grid. These advanced drives are ideal for applications with frequent braking or overhauling loads.</p>

<p>AFE drives provide four-quadrant operation, allowing energy recovery during regenerative braking. These drives are particularly suitable for cranes, hoists, lifts, and other applications requiring frequent deceleration. Emotron AFE drives deliver maximum energy efficiency while maintaining precise motor control and superior performance.</p>',

    // Emotron CDU / CDX - Motor-Mounted Drives
    64 => '<p>Emotron CDU / CDX - Motor-Mounted Drives are compact variable frequency drives designed to mount directly on motor terminal boxes. These integrated drives eliminate separate drive installations and reduce cabling complexity.</p>

<p>The motor-mounted configuration provides space savings and simplified installation, particularly beneficial for applications with space constraints. CDU / CDX drives offer full variable frequency drive functionality in a compact motor-integral package. These drives are ideal for pumps, fans, and general industrial motor applications.</p>',

    // Emotron DSV / GSV - Lift Drives
    65 => '<p>Emotron DSV / GSV - Lift Drives are specialized variable frequency drives engineered for lift and hoisting applications. These drives feature advanced safety functions, smooth acceleration/deceleration, and emergency lowering capabilities.</p>

<p>The DSV / GSV series is designed to meet stringent lift and hoist safety requirements with features including load monitoring and load-sensitive control. These drives provide superior performance for elevator systems, construction hoists, and industrial lifting equipment. Emotron lift drives ensure safe, reliable operation with energy-efficient motor control.</p>',

    // Emotron FlowDrive
    66 => '<p>Emotron FlowDrive is a specialized variable frequency drive designed for pump applications with automatic flow control. FlowDrive continuously adjusts motor speed to match system flow requirements, eliminating throttling losses.</p>

<p>This innovative drive technology delivers exceptional energy savings for pumping systems by eliminating unnecessary pressure drops and reducing motor speed during partial load conditions. FlowDrive is ideal for water supply systems, irrigation, and industrial fluid handling applications requiring optimized energy consumption.</p>',

    // Emotron VS10 & VS30 Drives
    67 => '<p>Emotron VS10 & VS30 Drives are premium variable frequency drives offering advanced motor control with multi-motor capability. These drives feature sophisticated control algorithms suitable for complex industrial applications.</p>

<p>The VS10 and VS30 series incorporates programmable logic functions, multiple communication interfaces, and extensive application software. These drives are ideal for complex process control applications, multiple motor coordination, and systems requiring advanced automation capabilities. Emotron VS series drives deliver superior performance and flexibility for demanding industrial requirements.</p>',

    // Emotron VSS Drives
    68 => '<p>Emotron VSS Drives are robust variable frequency drives designed for demanding industrial environments. These drives feature compact design with enhanced cooling for operation in high-temperature areas.</p>

<p>VSS drives incorporate advanced diagnostics and protection functions for reliable operation in harsh environments. These drives are suitable for industrial machinery, heavy-duty pump applications, and systems requiring maximum reliability and minimal downtime. Emotron VSS drives deliver superior performance and durability in challenging operational conditions.</p>',

    // Emotron VSX Drives
    69 => '<p>Emotron VSX Drives are high-specification variable frequency drives featuring advanced power electronics and control technology. These drives offer excellent efficiency and precise motor speed control for industrial applications.</p>

<p>The VSX series incorporates modern harmonic filtering technology, power factor correction, and advanced motor protection. These drives are ideal for precision machinery, sensitive process control, and applications requiring superior power quality. Emotron VSX drives deliver maximum performance with excellent electrical compatibility.</p>',

    // Emotron VSM Drives
    70 => '<p>Emotron VSM Drives are medium-power variable frequency drives designed for general industrial motor applications. These drives offer reliable motor speed control with energy-saving capabilities suitable for pumps, fans, and machinery.</p>

<p>The VSM series combines reliability, user-friendly programming, and compact design for straightforward industrial applications. These drives are suitable for standard industrial machinery, centrifugal pump applications, and fan speed control. Emotron VSM drives provide consistent performance with minimal maintenance requirements.</p>',

    // Emotron VSR Solar Drive
    71 => '<p>Emotron VSR Solar Drive is a specialized variable frequency drive designed for solar-powered pump systems. This drive optimizes pump operation for solar photovoltaic power input with automatic adaptation to variable solar power availability.</p>

<p>VSR Solar Drive features maximum power point tracking and smooth power transition capabilities for reliable solar pump operation. The drive is ideal for remote water supply systems, agricultural irrigation, and off-grid pumping applications. Emotron VSR delivers efficient, reliable pump operation powered by renewable solar energy.</p>',

    // DC Drive
    72 => '<p>DC Drive is a variable speed drive for controlling DC motors in industrial applications. DC drives provide smooth acceleration, precise speed control, and efficient power management for direct current motor systems.</p>

<p>DC drives are suitable for applications requiring variable speed, high starting torque, and precise speed regulation such as cranes, hoists, traction systems, and industrial machinery. These drives deliver superior performance with excellent controllability and reliability for demanding DC motor applications.</p>',

    // MV Drives - Medium Voltage
    73 => '<p>MV Drives - Medium Voltage variable frequency drives are engineered for medium voltage motor applications (typically 1 kV to 6.6 kV). These drives feature advanced power electronics technology for industrial applications requiring high-power motor control.</p>

<p>Medium voltage drives are suitable for large industrial motors in power plants, petrochemical facilities, mining operations, and heavy industrial applications. MV drives deliver efficient motor speed control with superior power quality and reliability for demanding high-voltage applications.</p>',

    // Emotron MSF 2.0 Softstarter
    74 => '<p>Emotron MSF 2.0 Softstarter is a soft-starting device designed to reduce mechanical and electrical stress during motor startup. This intelligent starter provides smooth acceleration of AC motors while reducing inrush current and mechanical shock.</p>

<p>The MSF 2.0 softstarter features energy-saving operation by adjusting motor speed to match load requirements. This device is ideal for pump, fan, and conveyor applications where smooth startup and energy efficiency are important. Emotron MSF softstarters deliver reliable motor starting with extended equipment life and reduced maintenance.</p>',

    // Emotron TSA Softstarter
    75 => '<p>Emotron TSA Softstarter is a compact soft-starting device for controlling AC motor startup and speed. This intelligent softstarter reduces mechanical stress and electrical inrush current while enabling smooth acceleration.</p>

<p>The TSA series incorporates advanced control features for optimized motor startup and energy-efficient operation. These starters are suitable for centrifugal pumps, fans, conveyor systems, and general industrial machinery. Emotron TSA softstarters provide reliable, smooth motor starting with minimal mechanical stress and electrical disturbances.</p>',

    // Variable Frequency Starter (VFS)
    76 => '<p>Variable Frequency Starter (VFS) is an advanced motor control device combining soft-starting and variable frequency capabilities. This integrated solution provides smooth motor startup with energy-efficient speed control for diverse applications.</p>

<p>VFS technology offers the benefits of reduced inrush current, smooth acceleration, and optimized speed control in a single integrated package. This solution is suitable for pump systems, fan applications, compressors, and industrial machinery requiring both controlled startup and energy-efficient operation. VFS delivers superior motor control performance with simplified installation and operation.</p>',
);

$updated = 0;
$failed = 0;

foreach ($motorUpdates as $motorID => $description) {
    $sql = "UPDATE mx_motor SET motorDesc = ? WHERE motorID = ? AND status = 1";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        echo "Error preparing statement for motorID $motorID\n";
        $failed++;
        continue;
    }

    $stmt->bind_param('si', $description, $motorID);

    if ($stmt->execute()) {
        echo "✓ Updated motorID $motorID\n";
        $updated++;
    } else {
        echo "✗ Failed to update motorID $motorID: " . $stmt->error . "\n";
        $failed++;
    }

    $stmt->close();
}

echo "\n=== SUMMARY ===\n";
echo "Updated: $updated motors\n";
echo "Failed: $failed motors\n";

// Add details for motors still missing them
echo "\n=== ADDING DETAILS FOR EMOTRON DRIVES ===\n";

$driveDetails = array(
    61 => array(
        array('Emotron FDU - AC Drive', '0.37 kW to 315 kW', 'Three Phase', 'Modular', 'IEC 61800-3'),
    ),
    62 => array(
        array('Emotron VFX - AC Drive', '0.37 kW to 250 kW', 'Three Phase', 'Compact to Frame', 'IEC 61800-3'),
    ),
    63 => array(
        array('Emotron AFE - Active Front End Drive', '1.1 kW to 1000 kW', 'Three Phase', 'Industrial', 'IEC 61800-3'),
    ),
    64 => array(
        array('Emotron CDU - Motor-Mounted Drive', '0.37 kW to 15 kW', 'Three Phase', 'Motor-Mounted', 'IEC 61800-3'),
    ),
    65 => array(
        array('Emotron DSV / GSV - Lift Drive', '0.37 kW to 200 kW', 'Three Phase', 'Industrial', 'IEC 61800-3'),
    ),
    66 => array(
        array('Emotron FlowDrive - Pump Control', '0.37 kW to 315 kW', 'Three Phase', 'Pump Specific', 'IEC 61800-3'),
    ),
    67 => array(
        array('Emotron VS10 / VS30 Drive', '0.37 kW to 400 kW', 'Three Phase', 'Compact to NEMA', 'IEC 61800-3'),
    ),
    68 => array(
        array('Emotron VSS Drive', '0.37 kW to 315 kW', 'Three Phase', 'Compact', 'IEC 61800-3'),
    ),
    69 => array(
        array('Emotron VSX Drive', '0.37 kW to 250 kW', 'Three Phase', 'Compact', 'IEC 61800-3'),
    ),
    70 => array(
        array('Emotron VSM Drive', '0.37 kW to 160 kW', 'Three Phase', 'Compact', 'IEC 61800-3'),
    ),
    71 => array(
        array('Emotron VSR - Solar Drive', '0.37 kW to 100 kW', 'Three Phase', 'Solar Specific', 'IEC 61800-3'),
    ),
    72 => array(
        array('DC Drive - DC Motor Control', '0.37 kW to 1000 kW', 'DC', 'Industrial', 'IEC 61800-2'),
    ),
    73 => array(
        array('MV Drives - Medium Voltage', '37 kW to 10000 kW', 'Medium Voltage', 'Industrial', 'IEC 61800-3'),
    ),
    74 => array(
        array('Emotron MSF 2.0 - Softstarter', '5.5 kW to 280 kW', 'Three Phase', 'Panel Mount', 'IEC 61800-4'),
    ),
    75 => array(
        array('Emotron TSA - Softstarter', '1.1 kW to 160 kW', 'Three Phase', 'Compact', 'IEC 61800-4'),
    ),
    76 => array(
        array('Variable Frequency Starter (VFS)', '0.37 kW to 315 kW', 'Three Phase', 'Integrated', 'IEC 61800-3 & 4'),
    ),
    14 => array(
        array('Bajaj FHP Motor', '0.5 kW to 2 kW', '240 V AC', '56 to 90', 'IS, IEC'),
    ),
    47 => array(
        array('Pressurized Motor Ex p (HV)', '0.75 kW to 150 kW', 'Up to 11 kV', '71 to 250', 'IEC, IS'),
    ),
    56 => array(
        array('Huller Motor (3 Phase)', '2.2 kW to 7.5 kW', '415 V AC', '90 to 112', 'IS 4691, IEC 60034-5'),
    ),
    57 => array(
        array('Cooler Motor (3 Phase)', '1.1 kW to 37 kW', '415 V AC', '80 to 160', 'IS 4691, IEC 60034-5'),
    ),
    58 => array(
        array('Flange Motor (3 Phase)', '0.5 kW to 30 kW', '415 V AC', '56 to 160', 'IS 4691, IEC 60034-5'),
    ),
    59 => array(
        array('Textile Industry Motor (3 Phase)', '1.1 kW to 50 kW', '415 V AC', '90 to 180', 'IS 4691, IEC 60034-5'),
    ),
    60 => array(
        array('Agricultural Equipment Motor (3 Phase)', '0.75 kW to 10 kW', '415 V AC', '63 to 112', 'IS 4691, IEC 60034-5'),
    ),
);

$detail_inserted = 0;

foreach ($driveDetails as $motorID => $details) {
    // First check if this motor already has details
    $check_sql = "SELECT COUNT(*) as cnt FROM mx_motor_detail WHERE motorID = ? AND status = 1";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param('i', $motorID);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $check_stmt->close();

    if ($row['cnt'] == 0) {
        foreach ($details as $detail) {
            $sql = "INSERT INTO mx_motor_detail (motorID, descriptionTitle, descriptionOutput, descriptionVoltage, descriptionFrameSize, descriptionStandard, status)
                    VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmt = $mysqli->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('isssss', $motorID, $detail[0], $detail[1], $detail[2], $detail[3], $detail[4]);
                if ($stmt->execute()) {
                    echo "✓ Added detail for motorID $motorID\n";
                    $detail_inserted++;
                }
                $stmt->close();
            }
        }
    }
}

echo "\nDetail records inserted: $detail_inserted\n";

$mysqli->close();
echo "\n✓ All motor content updated successfully!\n";
?>
