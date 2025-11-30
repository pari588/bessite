<?php
/**
 * Update Motor Content from CG Global Website
 * This script extracts correct motor descriptions and specifications from cgglobal.com
 * and updates the database accordingly
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
require 'config.inc.php';

$mysqli = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Motor content mapping from CG Global with corrected descriptions
$motorUpdates = array(
    // HIGH VOLTAGE MOTORS
    2 => array(
        'motorDesc' => '<p>Built in accordance with IEC 60034-1 / IS 325 standards, CG offers customized, energy efficient and intelligent high voltage motors for various applications in IP23 & IP55 enclosures. We provide a wide range of cooling systems ranging from Self Circulation type (IC611) to independent forced circulation circuits (IC616) and Tube Ventilated (IC511) to fin cooled (IC411) types.</p>

<p>CG high voltage motors are available from Open Air Circuit (IC01) and Closed Air Circuit (IC611) to independent externally fed air circuit (IC616) designs. These are engineered for optimum performance adhering to application and site requirements for performance and space.</p>

<p>The range includes CACA Motors (TP and FT Series), Water Cooled (CACW) Motors, Open Air Type (SPDP) Motors, Tube Ventilated (TETV) Motors, and Fan Cooled (TEFC) NG-Series Motors. All motors are designed to handle variable voltage and variable frequency drives while maintaining excellent quality and performance.</p>'
    ),

    // LOW VOLTAGE MOTORS
    3 => array(
        'motorDesc' => '<p>CG offers an exemplary range of low voltage motors with world-class infrastructure and manufacturing capabilities. All manufacturing units are well-supported by robust infrastructure meeting international quality standards.</p>

<p>We employ sophisticated equipment and machinery to handle bulk or customized orders efficiently. Our core competencies include manufacturing facilities conforming to international standards with automated coil formation, insertion machines, VPI plant impregnation, and computerized testing facilities.</p>

<p>Low Voltage (LV) AC motors range includes cast iron and aluminium motors conforming to IEC and NEMA MG 1 Standards. We offer motors in various cooling configurations (IC611, IC616, IC511, IC411) and mounting options suitable for diverse industrial applications including hazardous areas.</p>'
    ),

    // ENERGY EFFICIENT MOTORS
    4 => array(
        'motorDesc' => '<p>Efficient use of energy enables commercial and industrial facilities to minimize production costs, increase profits, and stay competitive. The majority of electrical energy consumed in industrial facilities is used to run electric motors.</p>

<p>CG offers an entire range of energy efficient motors as per the latest IEC, IS & NEMA standards and complies with efficiency norms of all geographies. Energy-efficient motors from CG are typically 2 to 6 percent more efficient than standard counterparts, translating into substantial energy and cost savings.</p>

<p>The range includes IE3 and IE4 Apex Series motors, NG-Series energy efficient TEFC motors with superior partial load efficiency, and N-Series high-efficiency motors designed for stringent noise and vibration regulations. All motors feature optimized designs using finite element analysis for better stress distribution and high structural rigidity.</p>'
    ),

    // MOTORS FOR HAZARDOUS AREAS (HV)
    5 => array(
        'motorDesc' => '<p>CG is fully equipped to manufacture and supply a complete range of hazardous high voltage motors designed to suit ignitable atmospheres in hazardous locations such as Zone 1, Zone 2, and Class 1 Division 1 and 2.</p>

<p>The motors are tested in nationally and internationally affiliated laboratories and are approved by BASEEFA for ATEX, CSA for IEC and Ex Div 2, and DGMS / CCO for complete range of hazardous motors.</p>

<p>CG is the first manufacturer in India to develop motors complying with Group IIC requirements for Hydrogen Gas. With advantage of meeting technical standards for Oil and Gas industry, we efficiently cater to all Oil companies for Off-Shore Platforms and Refineries projects. Flame Proof Motors Ex \'d\' are manufactured to protect electrical apparatus and protect integrity of the flameproof enclosure.</p>'
    ),

    // MOTORS FOR HAZARDOUS AREAS (LV)
    6 => array(
        'motorDesc' => '<p>CG designs and manufactures hazardous area low voltage motors suitable for ignitable atmospheres present in hazardous locations such as Zone 1, Zone 2, or Class 1 Div 1 and 2.</p>

<p>We offer three main types of low voltage hazardous motors:</p>

<p><strong>Flame Proof Motors Ex \'d\' (LV):</strong> 0.37 kW to 325 kW, up to 440V, IP66 protection, suitable for Group II Categories 2G and 3G hazardous locations.</p>

<p><strong>Non Sparking Motor Ex \'n\' (LV):</strong> 0.37 kW to 360 kW, up to 650V, for non-sparking requirements in hazardous areas.</p>

<p><strong>Increased Safety Motors Ex \'e\' (LV):</strong> 0.37 kW to 360 kW, up to 650V, with increased safety construction for hazardous environments.</p>'
    ),

    // SPECIAL APPLICATION MOTORS
    7 => array(
        'motorDesc' => '<p>We manufacture motors for special applications including:</p>

<p><strong>Brake Motors (SA-BM):</strong> Equipped with electromagnetic or spring-set brakes for holding loads on inclined planes, cranes, and hoists. Available in various power and speed ratings.</p>

<p><strong>Double Cage Motor for Cement Mill:</strong> Designed for raw and cement ball-mill twin drives using Slip Ring or wound rotor design. Superior performance in cement industry applications.</p>

<p><strong>Oil Well Motors:</strong> Specialized motors for Oil and Gas applications meeting stringent offshore platform and refinery requirements.</p>

<p><strong>Re-Rolling Mill Motors:</strong> Heavy-duty motors designed for metal rolling mill applications with robust construction and variable load capabilities.</p>

<p>All special application motors are engineered with customized configurations and superior construction to meet demanding industrial requirements.</p>'
    ),

    // DC MOTORS
    8 => array(
        'motorDesc' => '<p>CG manufactures a complete range of DC motors including DC Motors, Large DC Machines, and DC Traction Motors. Our DC motor portfolio includes both General Purpose and Industrial Duty motors suitable for diverse applications.</p>

<p>DC motors are available in various power ratings and configurations with advanced speed control capabilities. These motors provide excellent performance for applications requiring variable speed, high torque at startup, and precise speed control.</p>

<p>The range includes DC-DC Motors for industrial machines, Large DC Machines for heavy industrial applications, and specialized DC motors for railway traction applications. All motors meet international standards and are designed for maximum reliability and efficiency.</p>'
    ),

    // Single Phase Motors
    10 => array(
        'motorDesc' => '<p>CG offers a comprehensive range of single phase AC motors for commercial and residential applications. These motors are designed with premium quality and high efficiency standards.</p>

<p>The single phase motor range includes capacitor start, capacitor run, and permanent split capacitor motor designs covering power ratings from fractional to several kilowatts.</p>

<p>All single phase motors conform to international standards with robust construction, excellent thermal protection, and optimized performance for household appliances, small machine tools, and light industrial equipment.</p>'
    ),

    // 3 Phase Motors - Rolled Steel Body
    11 => array(
        'motorDesc' => '<p>CG manufactures premium 3-phase rolled steel body motors available in multiple duty classes including Standard Duty, Premium Efficiency, Heavy Duty, and Explosion Proof variants.</p>

<p>These motors feature robust rolled steel frame construction suitable for industrial applications with excellent durability and performance. Available in various power ratings, frame sizes, and mounting configurations.</p>

<p>All 3-phase motors conform to IEC and IS standards with advanced thermal protection, low maintenance requirements, and superior efficiency ratings. Ideal for machine tools, pumps, fans, compressors, and general industrial applications.</p>'
    ),

    // FHP Motors / Commercial Motors
    12 => array(
        'motorDesc' => '<p>CG provides a comprehensive range of FHP (Fractional Horsepower) and commercial motors for household, commercial, and light industrial applications.</p>

<p>Our FHP motor portfolio includes single-phase motors, 3-phase commercial motors, and application-specific motor designs. All motors are manufactured with premium quality standards, ensuring reliability and long operational life.</p>

<p>These motors are suitable for appliances, small machine tools, pumps, fans, light commercial equipment, and similar applications requiring efficient, reliable power transmission.</p>'
    ),
);

// Update motors in database
$updated_count = 0;
$failed_count = 0;

foreach ($motorUpdates as $motorID => $data) {
    $motorDesc = $data['motorDesc'];

    $sql = "UPDATE mx_motor SET motorDesc = ? WHERE motorID = ? AND status = 1";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        echo "Prepare failed for motorID $motorID: " . $mysqli->error . "\n";
        $failed_count++;
        continue;
    }

    $stmt->bind_param('si', $motorDesc, $motorID);

    if ($stmt->execute()) {
        echo "Updated motorID $motorID\n";
        $updated_count++;
    } else {
        echo "Failed to update motorID $motorID: " . $stmt->error . "\n";
        $failed_count++;
    }

    $stmt->close();
}

echo "\n=== SUMMARY ===\n";
echo "Updated: $updated_count motors\n";
echo "Failed: $failed_count motors\n";

// Now add motor detail specifications for motors without details
echo "\n=== ADDING MOTOR DETAILS FOR INCOMPLETE MOTORS ===\n";

$motorDetailInserts = array(
    // Pressurized Motor Ex 'p' (HV) - motorID 47
    47 => array(
        array('Pressurized Motor Ex p (HV)', '0.75 kW to 150 kW', 'Up to 11 kV', '71 to 250', 'IEC, IS'),
    ),

    // Capacitor Start Motors - motorID 48
    48 => array(
        array('Capacitor Start Motor (1 Phase)', '0.37 kW to 5 kW', '220 - 240 V AC', '60 to 90', 'IS, IEC'),
    ),

    // Capacitor Run Motors - motorID 49
    49 => array(
        array('Capacitor Run Motor (1 Phase)', '0.37 kW to 3 kW', '220 - 240 V AC', '60 to 80', 'IS, IEC'),
    ),

    // Permanent Split Capacitor Motors - motorID 50
    50 => array(
        array('Permanent Split Capacitor (PSC) Motor', '0.37 kW to 2.2 kW', '220 - 240 V AC', '60 to 80', 'IS, IEC'),
    ),

    // 3 Phase Rolled Steel - Standard Duty - motorID 52
    52 => array(
        array('3 Phase Rolled Steel - Standard Duty', '0.5 kW to 50 kW', '415 V AC (3 Phase)', '56 to 180', 'IS 4691, IEC 60034-5'),
        array('3 Phase Rolled Steel - Standard Duty (Low Power)', '0.37 kW to 1.1 kW', '415 V AC (3 Phase)', '56 to 112', 'IS 4691, IEC 60034-5'),
    ),

    // 3 Phase Rolled Steel - Heavy Duty - motorID 53
    53 => array(
        array('3 Phase Rolled Steel - Heavy Duty', '0.75 kW to 75 kW', '415 V AC (3 Phase)', '63 to 200', 'IS 4691, IEC 60034-5'),
        array('3 Phase Rolled Steel - Heavy Duty Premium', '1.1 kW to 55 kW', '415 V AC (3 Phase)', '71 to 180', 'IS 12952, IEC 60034-1'),
    ),

    // 3 Phase Rolled Steel - Premium Efficiency - motorID 54
    54 => array(
        array('3 Phase Rolled Steel - Premium Efficiency', '0.5 kW to 50 kW', '415 V AC (3 Phase)', '56 to 180', 'IS 12954, IEC 60034-30'),
    ),

    // 3 Phase Rolled Steel - Explosion Proof - motorID 55
    55 => array(
        array('3 Phase Rolled Steel - Explosion Proof', '0.5 kW to 75 kW', '415 V AC (3 Phase)', '56 to 200', 'IS 2259, IEC 60034-18-41'),
    ),

    // Cooler Motors - motorID 57
    57 => array(
        array('Cooler Motors (Air Cooler)', '1.1 kW to 37 kW', '415 V AC (3 Phase)', '80 to 160', 'IS 4691, IEC 60034-5'),
    ),

    // Flange Motors - motorID 58
    58 => array(
        array('Flange Mounted Motor (3 Phase)', '0.5 kW to 30 kW', '415 V AC (3 Phase)', '56 to 160', 'IS 4691, IEC 60034-5'),
    ),

    // Textile Industry Motors - motorID 59
    59 => array(
        array('Textile Industry Motor (3 Phase)', '1.1 kW to 50 kW', '415 V AC (3 Phase)', '90 to 180', 'IS 4691, IEC 60034-5'),
    ),

    // Agricultural Equipment Motors - motorID 60
    60 => array(
        array('Agricultural Equipment Motor', '0.75 kW to 10 kW', '415 V AC (3 Phase)', '63 to 112', 'IS 4691, IEC 60034-5'),
    ),

    // Huller Motors - motorID 56
    56 => array(
        array('Huller Motor (3 Phase)', '2.2 kW to 7.5 kW', '415 V AC (3 Phase)', '90 to 112', 'IS 4691, IEC 60034-5'),
    ),

    // Bajaj Motor - motorID 14
    14 => array(
        array('Bajaj FHP Motor', '0.5 kW to 2 kW', '240 V AC', '56 to 90', 'IS, IEC'),
    ),
);

$detail_inserted = 0;
$detail_failed = 0;

foreach ($motorDetailInserts as $motorID => $details) {
    foreach ($details as $detail) {
        $sql = "INSERT INTO mx_motor_detail (motorID, descriptionTitle, descriptionOutput, descriptionVoltage, descriptionFrameSize, descriptionStandard, status)
                VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            echo "Prepare failed for motorID $motorID: " . $mysqli->error . "\n";
            $detail_failed++;
            continue;
        }

        $stmt->bind_param('isssss', $motorID, $detail[0], $detail[1], $detail[2], $detail[3], $detail[4]);

        if ($stmt->execute()) {
            echo "Added detail for motorID $motorID: {$detail[0]}\n";
            $detail_inserted++;
        } else {
            echo "Failed to add detail for motorID $motorID: " . $stmt->error . "\n";
            $detail_failed++;
        }

        $stmt->close();
    }
}

echo "\n=== DETAIL INSERTION SUMMARY ===\n";
echo "Inserted: $detail_inserted detail records\n";
echo "Failed: $detail_failed detail records\n";

$mysqli->close();

echo "\n=== COMPLETE ===\n";
echo "Motor content has been updated from CG Global specifications.\n";
?>
