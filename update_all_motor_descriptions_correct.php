<?php
/**
 * Update ALL Motor Descriptions with CORRECT Content from CG Global
 * This replaces SHORT generic descriptions with PROPER SEO-optimized descriptions
 */

require 'config.inc.php';

$mysqli = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if ($mysqli->connect_error) {
    echo "Connection failed: " . $mysqli->connect_error;
    exit;
}

// CORRECT motor descriptions from CG Global - replaces all short/generic ones
$correctMotorDescriptions = array(
    // DC Motors and Hazardous Area Motors
    43 => '<p>Increased Safety Motors Ex \'e\' (LV) are specially engineered for hazardous areas with enhanced electrical and mechanical safety features. These motors incorporate reinforced insulation systems, temperature sensors, and advanced thermal protection suitable for Zone 1, Zone 2, and Class 1 Division 1 and 2 environments.</p>

<p>The Ex \'e\' construction ensures increased safety through component design preventing ignition of surrounding hazardous atmospheres. Available in power ratings from 0.37 kW to 360 kW with voltages up to 650 V. All motors meet IEC 61800, IS and ATEX certification standards with superior reliability and performance.</p>',

    45 => '<p>Increased Safety Motors Ex \'e\' (HV) are high-voltage motors designed with increased safety construction for hazardous area applications. These motors feature reinforced enclosures, enhanced insulation systems, and temperature monitoring suitable for Oil and Gas industry, petrochemical refineries, and mining operations.</p>

<p>The Ex \'e\' (increased safety) design prevents ignition sources in Group IIC hazardous atmospheres. Available in power ratings from 0.75 kW to 2000 kW with voltages up to 11 kV. All motors are tested and certified by BASEEFA for ATEX compliance with excellent durability and operational reliability.</p>',

    46 => '<p>Non Sparking Motor Ex \'n\' (HV) features non-sparking construction suitable for Group IIC hazardous areas including hydrogen environments. The Ex \'n\' design incorporates special air-gap geometry and insulation systems preventing sparking during normal motor operation.</p>

<p>These high-voltage motors are ideal for explosive atmospheres in chemical plants, refineries, and gas processing facilities. Available in power ratings from 0.75 kW to 2000 kW with voltages up to 11 kV. All motors meet international ATEX and IEC standards with proven reliability in the most demanding hazardous environments.</p>',

    // Single Phase and Capacitor Motors
    48 => '<p>Capacitor Start Motors are single-phase motors incorporating a starting capacitor for high starting torque applications. These motors feature robust construction with excellent performance for intermittent-duty applications like air compressors, power tools, and industrial equipment requiring strong starting torque.</p>

<p>Available in power ratings from 0.37 kW to 5 kW with standard supply voltage 220-240V AC. All capacitor start motors include built-in thermal protection and are manufactured according to IS and IEC standards. The starting capacitor enables efficient startup with minimal mechanical stress.</p>',

    49 => '<p>Capacitor Run Motors are single-phase motors with permanent capacitor connection for improved efficiency and smoother operation. These motors are ideal for continuous-duty applications like fans, blowers, and light machinery requiring energy-efficient performance.</p>

<p>Available in power ratings from 0.37 kW to 3 kW with standard supply voltage 220-240V AC. Capacitor run motors provide better power factor and lower operating costs compared to standard single-phase motors. All motors conform to IS and IEC standards with excellent thermal stability and long operational life.</p>',

    50 => '<p>Permanent Split Capacitor (PSC) Motors are single-phase motors with permanently connected capacitor for optimized efficiency in continuous operation. These motors are ideal for fan and blower applications where energy efficiency and reliability are critical requirements.</p>

<p>Available in power ratings from 0.37 kW to 2.2 kW with standard supply voltage 220-240V AC. PSC motors provide consistent performance with excellent power factor and minimal noise characteristics. All motors meet IS and IEC standards with superior thermal protection and dependable operation.</p>',

    51 => '<p>Split Phase Motors are single-phase induction motors with separate starting and running windings providing reliable starting torque for direct-on-line applications. These motors are widely used in household appliances, small machine tools, and commercial equipment.</p>

<p>Available in power ratings from 0.37 kW to 3.7 kW with standard supply voltage 220-240V AC. Split phase motors feature simple, rugged construction with low maintenance requirements. All motors comply with IS and IEC standards ensuring reliable performance in residential and commercial applications.</p>',

    // 3 Phase Rolled Steel Motors
    52 => '<p>3 Phase Rolled Steel Body Motors - Standard Duty are affordable, reliable motors suitable for general industrial applications. These motors feature robust rolled steel frame construction with excellent mechanical durability and consistent performance across diverse load conditions.</p>

<p>Available in power ratings from 0.5 kW to 50 kW with standard 415V AC 3-phase supply and frame sizes 56 to 180. These motors meet IS 4691 and IEC 60034-5 standards with excellent thermal stability. Ideal for pumps, fans, conveyors, and general machinery requiring cost-effective, dependable motor performance.</p>',

    53 => '<p>3 Phase Rolled Steel Body Motors - Heavy Duty are engineered for demanding industrial applications with high mechanical stress and continuous operation. These motors feature reinforced frame construction, superior insulation systems, and advanced thermal protection.</p>

<p>Available in power ratings from 0.75 kW to 75 kW with frame sizes 63 to 200 suitable for heavy-duty machinery. All heavy-duty motors meet IS 4691 and IEC 60034-5 standards with exceptional durability. Perfect for cement mills, crushers, compressors, and other demanding industrial equipment requiring maximum reliability.</p>',

    54 => '<p>3 Phase Rolled Steel Body Motors - Premium Efficiency delivers superior energy efficiency with reduced operating costs. These motors incorporate optimized magnetic circuit design, premium insulation materials, and advanced thermal management systems.</p>

<p>Available in power ratings from 0.5 kW to 50 kW with frame sizes 56 to 180 suitable for continuous industrial applications. All premium efficiency motors meet IS 12954 and IEC 60034-30 efficiency standards. Delivering long-term cost savings through reduced energy consumption, these motors are ideal for energy-conscious industrial facilities.</p>',

    // Hazardous Area Motors
    22 => '<p>AXELERA Process Performance Motors are specially engineered for demanding industrial process applications requiring exceptional reliability and precision. These motors incorporate advanced control systems, superior thermal management, and robust construction for extreme operational conditions.</p>

<p>Available in various power ratings with customizable configurations to match specific process requirements. AXELERA motors feature integrated monitoring and protection systems ensuring continuous safe operation. Ideal for critical industrial processes requiring maximum uptime, reliability, and process control capability.</p>',

    // Emotron Drives
    61 => '<p>Emotron FDU - AC Drives are advanced variable frequency drives designed for controlled speed management of AC motors. These modular drives provide smooth acceleration, energy savings, and precise motor control suitable for pumps, fans, compressors, and industrial machinery.</p>

<p>The FDU series features intelligent ramp functions, automatic restart capabilities, and multiple control modes with power ratings from 0.37 kW to 315 kW. These drives deliver efficient motor speed control with minimal electrical losses and superior performance in energy-critical applications.</p>',

    62 => '<p>Emotron VFX - AC Drives are high-performance variable frequency drives featuring advanced motor control technology for precise speed regulation and torque management. These drives are particularly effective for pump, fan, and compressor applications where energy savings and smooth operation are critical.</p>

<p>Available from 0.37 kW to 250 kW in compact to standard frame configurations. VFX drives incorporate modern power electronics with user-friendly programming interfaces. These drives deliver superior performance and reliable operation with minimal maintenance requirements.</p>',

    63 => '<p>Emotron AFE - Active Front End Drives are regenerative variable frequency drives capable of returning energy to the power grid during braking. These advanced four-quadrant drives are ideal for applications with frequent braking like cranes, hoists, lifts, and energy recovery systems.</p>

<p>Available in power ratings from 1.1 kW to 1000 kW with industrial-grade construction. AFE drives provide maximum energy efficiency through energy recovery while maintaining precise motor control. Perfect for demanding applications requiring both smooth operation and energy optimization.</p>',

    64 => '<p>Emotron CDU / CDX - Motor-Mounted Drives are compact variable frequency drives designed to mount directly on motor terminal boxes. This integral configuration eliminates separate drive installations, reducing cabling complexity and space requirements.</p>

<p>Available in power ratings from 0.37 kW to 15 kW with motor-mounted design. These drives offer full variable frequency drive functionality in a compact, integrated package. Ideal for space-constrained applications requiring simplified installation and efficient motor control.</p>',

    65 => '<p>Emotron DSV / GSV - Lift Drives are specialized variable frequency drives engineered for lift, hoist, and elevator applications. These drives incorporate advanced safety functions, smooth acceleration/deceleration, and emergency lowering capabilities meeting stringent safety requirements.</p>

<p>Available in power ratings from 0.37 kW to 200 kW with load monitoring and load-sensitive control features. DSV / GSV drives provide superior safety and reliability for vertical lifting systems. Ideal for elevators, construction hoists, and industrial lifting equipment.</p>',

    66 => '<p>Emotron FlowDrive is a specialized variable frequency drive designed for pump applications with automatic flow control. FlowDrive continuously adjusts motor speed to match system flow requirements, eliminating throttling losses and reducing unnecessary pressure drops.</p>

<p>Available in power ratings from 0.37 kW to 315 kW. FlowDrive delivers exceptional energy savings for pumping systems by optimizing motor speed during partial load conditions. Perfect for water supply systems, irrigation, and industrial fluid handling applications.</p>',

    67 => '<p>Emotron VS10 & VS30 Drives are premium variable frequency drives offering advanced motor control with multi-motor capability. These drives feature sophisticated control algorithms, programmable logic functions, and extensive application software.</p>

<p>Available in power ratings from 0.37 kW to 400 kW in compact to NEMA frame configurations. VS10 and VS30 series delivers superior performance and flexibility for complex industrial applications requiring advanced automation capabilities and multiple motor coordination.</p>',

    68 => '<p>Emotron VSS Drives are robust variable frequency drives designed for demanding industrial environments. These drives feature compact design with enhanced cooling systems for operation in high-temperature areas with advanced diagnostics and protection functions.</p>

<p>Available in power ratings from 0.37 kW to 315 kW. VSS drives are suitable for industrial machinery, heavy-duty pump applications, and harsh operational environments. These drives deliver superior reliability and durability with minimal downtime requirements.</p>',

    69 => '<p>Emotron VSX Drives are high-specification variable frequency drives featuring advanced power electronics and control technology. These drives offer excellent efficiency, precise motor speed control, and harmonic filtering technology for superior power quality.</p>

<p>Available in power ratings from 0.37 kW to 250 kW in compact frame configurations. VSX drives are ideal for precision machinery, sensitive process control, and applications requiring superior electrical compatibility and minimal electromagnetic interference.</p>',

    70 => '<p>Emotron VSM Drives are medium-power variable frequency drives designed for general industrial motor applications. These drives offer reliable motor speed control with energy-saving capabilities suitable for pumps, fans, machinery, and industrial applications.</p>

<p>Available in power ratings from 0.37 kW to 160 kW in compact frame configurations. VSM drives combine reliability, user-friendly programming, and compact design. These drives provide consistent performance with minimal maintenance for straightforward industrial applications.</p>',

    71 => '<p>Emotron VSR Solar Drive is a specialized variable frequency drive designed for solar-powered pump systems. This drive optimizes pump operation for variable solar power input with automatic adaptation to changing solar availability.</p>

<p>Available in power ratings from 0.37 kW to 100 kW with maximum power point tracking and smooth power transition capabilities. VSR Solar Drive delivers efficient, reliable pump operation powered by renewable solar energy. Perfect for remote water supply systems and off-grid pumping applications.</p>',

    72 => '<p>DC Drive is a variable speed drive for controlling direct current motors in industrial applications. DC drives provide smooth acceleration, precise speed control, and efficient power management for DC motor systems.</p>

<p>Available in power ratings from 0.37 kW to 1000 kW with industrial-grade construction. DC drives deliver superior performance with excellent controllability and reliability for applications requiring variable speed, high starting torque, and precise regulation such as cranes, hoists, and industrial machinery.</p>',

    73 => '<p>MV Drives - Medium Voltage variable frequency drives are engineered for medium voltage motor applications (1 kV to 6.6 kV). These drives feature advanced power electronics technology for industrial applications requiring high-power motor control.</p>

<p>Available in power ratings from 37 kW to 10,000 kW for industrial-scale applications. Medium voltage drives are suitable for large motors in power plants, petrochemical facilities, mining operations, and heavy industrial applications. These drives deliver efficient motor control with superior power quality.</p>',

    74 => '<p>Emotron MSF 2.0 Softstarter is an intelligent soft-starting device designed to reduce mechanical and electrical stress during motor startup. This advanced starter provides smooth acceleration of AC motors while reducing inrush current and mechanical shock.</p>

<p>Available in power ratings from 5.5 kW to 280 kW with energy-saving operation by adjusting motor speed to match load requirements. MSF 2.0 softstarters are ideal for pump, fan, and conveyor applications. These starters deliver reliable motor starting with extended equipment life and reduced maintenance.</p>',

    75 => '<p>Emotron TSA Softstarter is a compact soft-starting device for controlling AC motor startup and speed. This intelligent softstarter reduces mechanical stress and electrical inrush current while enabling smooth acceleration of motor systems.</p>

<p>Available in power ratings from 1.1 kW to 160 kW with advanced control features for optimized motor startup. TSA softstarters are suitable for centrifugal pumps, fans, conveyor systems, and general industrial machinery. These starters provide reliable, smooth motor starting with minimal mechanical stress.</p>',

    76 => '<p>Variable Frequency Starter (VFS) is an advanced motor control device combining soft-starting and variable frequency capabilities. This integrated solution provides smooth motor startup with energy-efficient speed control for diverse industrial applications.</p>

<p>Available in power ratings from 0.37 kW to 315 kW with integrated functionality. VFS technology offers reduced inrush current, smooth acceleration, and optimized speed control in a single package. Ideal for pump systems, fan applications, compressors, and machinery requiring both controlled startup and energy efficiency.</p>',
);

$updated = 0;

foreach ($correctMotorDescriptions as $motorID => $description) {
    $sql = "UPDATE mx_motor SET motorDesc = ? WHERE motorID = ? AND status = 1";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        echo "Error preparing for motorID $motorID\n";
        continue;
    }

    $stmt->bind_param('si', $description, $motorID);

    if ($stmt->execute()) {
        echo "Updated motorID $motorID\n";
        $updated++;
    } else {
        echo "Failed motorID $motorID\n";
    }

    $stmt->close();
}

echo "\nTotal updated: $updated motors\n";

$mysqli->close();
?>
