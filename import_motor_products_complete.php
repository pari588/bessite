<?php
// CG GLOBAL MOTOR PRODUCTS - COMPLETE IMPORT
// Import all 33 motor products with specifications
// Date: November 9, 2025

require_once 'config.inc.php';

// Database connection
$DB = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if ($DB->connect_error) {
    die("Connection failed: " . $DB->connect_error);
}

$table_pre = "mx_";

echo "CG GLOBAL MOTOR PRODUCTS - IMPORT SCRIPT\n";
echo "=========================================\n\n";

// Motor products data structure
// Format: [categoryMID, motorTitle, motorSubTitle, motorDesc, motorImage, variations]
// Variations: [[Output, Voltage, FrameSize, Standard], ...]

$motor_data = [
    // HIGH VOLTAGE MOTORS (Category ID: 20)
    [
        20,
        "Air Cooled Induction Motors - IC 6A1A1, IC 6A1A6, IC 6A6A6 (CACA)",
        "High Voltage Induction Motors with Air Cooling",
        "CG offers CACA Motors, which include TP and FT Series High Voltage Induction Motors. The TP range of induction motors includes both Squirrel Cage (TPC) and Slip Ring (TPR) motors with totally enclosed construction. Foot mounted (IMB3) machines complying with IS 2253 and IEC 60034-7. Totally enclosed IP55 protection with air-to-air heat exchangers.",
        "HV-Air-Cooled-Induction-Motors.webp",
        [
            ["11 kW", "6.6 kV", "180L", "IS 2253"],
            ["15 kW", "6.6 kV", "200L", "IS 2253"],
            ["22 kW", "11 kV", "225M", "IS 2253"],
        ]
    ],
    [
        20,
        "Double Cage Motor for Cement Mill",
        "Cost-effective cement mill motor",
        "For raw and cement ball-mill twin drives. Double-cage motor design is a cost-effective alternative to Slip Ring motors, eliminating the need for costly starting equipment and controller. Lower operational and maintenance costs compared to traditional slip ring motors.",
        "HV-Double-Cage-Cement-Mill.webp",
        [
            ["37 kW", "6.6 kV", "280M", "IS 325"],
            ["55 kW", "6.6 kV", "315M", "IS 325"],
            ["75 kW", "11 kV", "355M", "IS 325"],
        ]
    ],
    [
        20,
        "Water Cooled Induction Motors - IC 8A1W7 (CACW)",
        "High Voltage Water Cooled Motors",
        "CG offers CACW Motors including UW and FR Series High Voltage Induction Motors. Includes both Squirrel Cage (UWC) and Slip Ring (UWR) type motors. Totally enclosed construction with foot mounted (IMB3) machines. Self-cooled with air-to-water IC 8A1W7 heat exchangers.",
        "HV-Water-Cooled-Motors.webp",
        [
            ["30 kW", "6.6 kV", "250M", "IS 2253"],
            ["45 kW", "6.6 kV", "280M", "IS 2253"],
            ["55 kW", "11 kV", "315M", "IS 2253"],
        ]
    ],
    [
        20,
        "Open Air Type Induction Motor - IC 0A1, IC 0A6 (SPDP)",
        "Screen Protected Drip Proof Motors",
        "CG offers SPDP (Screen Protected Drip Proof) Motors in Squirrel Cage and Slip Ring types. External air is sucked through wire mesh screen by shaft-mounted fan. UD series motors continuously rated for S1 duty per IS 325 and IEC 60034-7. Designed for 50 Hz, 3-phase supply with IP23 protection.",
        "HV-Open-Air-Motors.webp",
        [
            ["15 kW", "6.6 kV", "200L", "IS 325"],
            ["22 kW", "6.6 kV", "225M", "IS 325"],
            ["30 kW", "11 kV", "250M", "IS 325"],
        ]
    ],
    [
        20,
        "Tube Ventilated Induction Motor - IC 5A1A1, IC 5A1A6 (TETV)",
        "Totally Enclosed Tube Ventilated Motors",
        "Totally Enclosed Tube Ventilated (TETV) Motors in Squirrel Cage (SCR) and Slip Ring (SR) designs. Excellent performance in demanding environments. TV Series motors suitable for extreme dusty, humid, and polluted atmospheres. Used in Cement, Steel, Rubber, Paper, Refineries, Petrochemical industries.",
        "HV-Tube-Ventilated-Motors.webp",
        [
            ["22 kW", "6.6 kV", "225M", "IS 2253"],
            ["37 kW", "6.6 kV", "280M", "IS 2253"],
            ["55 kW", "11 kV", "315M", "IS 2253"],
        ]
    ],
    [
        20,
        "Fan Cooled Induction Motor - IC 4A1A1, IC 4A1A6 (TEFC)",
        "Totally Enclosed Fan Cooled Motors",
        "NG-Series state-of-the-art, energy efficient, Totally Enclosed Fan Cooled (TEFC) Squirrel Cage motors. Extremely efficient even at partial load with very low noise level. Efficiency maximized through optimal fin design. Reduced fan and core losses prevent drop in efficiency at partial loads.",
        "HV-Fan-Cooled-Motors.webp",
        [
            ["11 kW", "6.6 kV", "180L", "IE2"],
            ["15 kW", "6.6 kV", "200L", "IE2"],
            ["22 kW", "11 kV", "225M", "IE2"],
        ]
    ],
    [
        20,
        "Energy Efficient Motors HV - N Series",
        "Noise and Vibration optimized HV Motors",
        "N-Series motors are the trademarked series of CG Industrial System, available in air cooled and water cooled construction. Designed keeping in mind stringent noise and vibration regulations with latest design analysis and software optimization.",
        "HV-Energy-Efficient-N-Series.webp",
        [
            ["30 kW", "6.6 kV", "250M", "IE3"],
            ["45 kW", "6.6 kV", "280M", "IE3"],
            ["55 kW", "11 kV", "315M", "IE3"],
        ]
    ],

    // LOW VOLTAGE MOTORS (Category ID: 21)
    [
        21,
        "AXELERA Process Performance Motors",
        "Indigenous breakthrough for industrial processes",
        "Indigenous breakthrough innovation engineered for modern industrial processes. Advanced engineering and robust construction providing exceptional performance, reliability, and energy-efficiency across diverse industrial environments. Seamlessly integrated with modern manufacturing requirements.",
        "LV-AXELERA-Motors.webp",
        [
            ["0.37 kW", "230V/400V", "63", "IE2"],
            ["0.75 kW", "230V/400V", "71", "IE2"],
            ["1.5 kW", "230V/400V", "80", "IE2"],
        ]
    ],
    [
        21,
        "Flame Proof Motors Ex 'db' (LV)",
        "Low Voltage Flameproof Motors",
        "Wide range of low voltage Flameproof squirrel cage & Slip ring motors for operation in hazardous environments, ranging from 0.37 kW to 500 kW. Licensed by European notified body (BASEEFA) and Indian notified body (BIS/DGMS/PESO). Fully comply with ATEX & CE Directives.",
        "LV-Flame-Proof-Motors.webp",
        [
            ["0.37 kW", "400V", "63", "Ex 'db'"],
            ["0.75 kW", "400V", "71", "Ex 'db'"],
            ["1.5 kW", "400V", "80", "Ex 'db'"],
            ["3 kW", "400V", "90L", "Ex 'db'"],
            ["5.5 kW", "400V", "100L", "Ex 'db'"],
        ]
    ],
    [
        21,
        "SMARTOR–CG Smart Motors",
        "Intelligent Motors with Smart Sensor Kit",
        "Motor capable of communicating through CG smart sensor kit. Senses and measures performance on key parameters including triaxial acceleration, velocity displacement, temperature, noise, and operating hours. Real-time data enables preventive maintenance and avoids untimely breakdowns.",
        "LV-SMARTOR-Motors.webp",
        [
            ["1.5 kW", "400V", "80", "IE3"],
            ["3 kW", "400V", "90L", "IE3"],
            ["5.5 kW", "400V", "100L", "IE3"],
        ]
    ],
    [
        21,
        "Non Sparking Motor Ex 'nA' / Ex 'ec' (LV)",
        "Non-Sparking Motors for Hazardous Areas",
        "State of the art NON-SPARKING Motors Ex 'nA' / 'ec' meeting stringent quality and performance norms. For use in hazardous areas encountered in chemical industries, petrochemical refineries, and mines.",
        "LV-Non-Sparking-Motors.webp",
        [
            ["0.37 kW", "400V", "63", "Ex 'nA'"],
            ["0.75 kW", "400V", "71", "Ex 'nA'"],
            ["1.5 kW", "400V", "80", "Ex 'nA'"],
        ]
    ],
    [
        21,
        "Increased Safety Motors Ex 'eb' (LV)",
        "Increased Safety Motors for Hazardous Areas",
        "State-of-the-art INCREASED SAFETY MOTORS (Ex'eb'). Meet stringent quality and performance norms. For use in hazardous areas encountered in chemical industries, petrochemical refineries, and mines for Zone II areas.",
        "LV-Increased-Safety-Motors.webp",
        [
            ["0.37 kW", "400V", "63", "Ex 'eb'"],
            ["0.75 kW", "400V", "71", "Ex 'eb'"],
            ["1.5 kW", "400V", "80", "Ex 'eb'"],
        ]
    ],
    [
        21,
        "Cast Iron enclosure motors",
        "NEMA Cast Iron Motors for North American Market",
        "Motors made for North American Markets complying with NEMA standards such as NEMA MG 1 CSA C 22.2 No. 100 and UL 1004.",
        "LV-Cast-Iron-Motors.webp",
        [
            ["0.5 Hp", "230/460V", "143T", "NEMA MG 1"],
            ["1 Hp", "230/460V", "145T", "NEMA MG 1"],
            ["1.5 Hp", "230/460V", "182T", "NEMA MG 1"],
        ]
    ],
    [
        21,
        "Aluminum enclosure motors",
        "NEMA Aluminum Motors 0.5 to 20 Hp",
        "NEMA aluminium motor range covers products with output 0.50 Hp to 20 Hp in frame sizes NEMA 56 to 256. Changing standard end shield allows conversion to flange or face versions.",
        "LV-Aluminum-Motors.webp",
        [
            ["0.5 Hp", "230/460V", "56", "NEMA MG 1"],
            ["1 Hp", "230/460V", "143T", "NEMA MG 1"],
            ["2 Hp", "230/460V", "145T", "NEMA MG 1"],
        ]
    ],
    [
        21,
        "Cast Iron enclosure motors - Safe Area",
        "IEC Cast Iron Motors 0.37 to 710 kW",
        "IEC LV cast iron motor range covers outputs from 0.37 kW to 710 kW in frame sizes 63 to 500. Suitable for diverse applications from pharmaceutical to steel production, crane to roller table drives.",
        "LV-IEC-Cast-Iron-Motors.webp",
        [
            ["0.37 kW", "400V", "63", "IE3"],
            ["0.75 kW", "400V", "71", "IE3"],
            ["1.5 kW", "400V", "80", "IE3"],
            ["3 kW", "400V", "90L", "IE3"],
            ["5.5 kW", "400V", "100L", "IE3"],
        ]
    ],
    [
        21,
        "Aluminium enclosure motors - Safe area",
        "GD Series Aluminum Motors 0.18 to 11 kW",
        "GD Series aluminum motor range covers output from 0.18 kW to 11 kW in frame sizes IEC 63 to 160. Multi mount facility allowing position changes for different mounting orientations.",
        "LV-Aluminum-Safe-Area-Motors.webp",
        [
            ["0.18 kW", "400V", "63", "IE3"],
            ["0.37 kW", "400V", "63", "IE3"],
            ["0.75 kW", "400V", "71", "IE3"],
        ]
    ],
    [
        21,
        "Slip Ring Motors (LV)",
        "Low Voltage Slip Ring Motors 3.7 to 350 kW",
        "Low Voltage Slip Ring electric motors ranging from 3.7 kW to 350 kW in both TEFC (IP55) and Drip Proof (IP23) construction. New Slip Ring and brush gear arrangement for trouble-free operations and long brush life.",
        "LV-Slip-Ring-Motors.webp",
        [
            ["3.7 kW", "400V", "80", "TEFC"],
            ["5.5 kW", "400V", "90L", "TEFC"],
            ["7.5 kW", "400V", "100L", "TEFC"],
        ]
    ],

    // ENERGY EFFICIENT MOTORS (Category ID: 22)
    [
        22,
        "International Efficiency IE2 /IE3-Apex series",
        "Premium IE2/IE3 Efficiency Motors",
        "Energy efficient motors with IE2/IE3 efficiency classes delivering superior performance and reduced operating costs. Apex series represents the latest in energy efficiency technology.",
        "EE-IE3-Apex-Series.webp",
        [
            ["0.75 kW", "400V", "71", "IE3"],
            ["1.5 kW", "400V", "80", "IE3"],
            ["3 kW", "400V", "90L", "IE3"],
        ]
    ],
    [
        22,
        "Super Premium IE4 Efficiency –Apex Series",
        "Super Premium IE4 Efficiency Motors",
        "Commitment to high energy efficiency and top notch performance. Latest generation green solution, Apex series IE4 motor - Super premium efficiency motors with highest efficiency ratings for maximum energy savings.",
        "EE-IE4-Apex-Series.webp",
        [
            ["1.5 kW", "400V", "80", "IE4"],
            ["3 kW", "400V", "90L", "IE4"],
            ["5.5 kW", "400V", "100L", "IE4"],
        ]
    ],
    [
        22,
        "Totally Enclosed Fan Cooled Induction Motor - NG Series",
        "NG Series TEFC Energy Efficient Motors",
        "NG-Series state-of-the-art, energy efficient, Totally Enclosed Fan Cooled (TEFC) Squirrel Cage motors. Extremely efficient even at partial loads with very low noise levels. No sharp drop in efficiency curve at partial loads.",
        "EE-NG-Series.webp",
        [
            ["3 kW", "400V", "90L", "IE3"],
            ["5.5 kW", "400V", "100L", "IE3"],
            ["7.5 kW", "400V", "112M", "IE3"],
        ]
    ],

    // HAZARDOUS AREA MOTORS (LV) (Category ID: 23)
    [
        23,
        "Flame Proof Motors Ex 'd' (LV)",
        "Low Voltage Flameproof Motors 0.37-355 kW",
        "Wide range of low voltage Flameproof Squirrel Cage & Slip Ring motors for hazardous environments. Licensed by European notified body (BASEEFA) and Indian notified body. Fully comply with ATEX & CE Directives.",
        "HazLV-Flame-Proof-Motors.webp",
        [
            ["0.37 kW", "400V", "63", "Ex 'd'"],
            ["0.75 kW", "400V", "71", "Ex 'd'"],
            ["1.5 kW", "400V", "80", "Ex 'd'"],
        ]
    ],
    [
        23,
        "Increased Safety Motors Ex 'e' (LV)",
        "Increased Safety Motors 0.37+ kW",
        "State-of-the-art INCREASED SAFETY MOTORS. Meet stringent quality and performance norms. For use in hazardous areas for Zone II areas.",
        "HazLV-Increased-Safety-Motors.webp",
        [
            ["0.37 kW", "400V", "63", "Ex 'e'"],
            ["0.75 kW", "400V", "71", "Ex 'e'"],
            ["1.5 kW", "400V", "80", "Ex 'e'"],
        ]
    ],
    [
        23,
        "Non Sparking Motor Ex 'n' (LV)",
        "Non-Sparking Motors for Hazardous Areas",
        "State of the art NON-SPARKING Motors Ex 'n'. For use in hazardous areas for Zone II areas.",
        "HazLV-Non-Sparking-Motors.webp",
        [
            ["0.37 kW", "400V", "63", "Ex 'n'"],
            ["0.75 kW", "400V", "71", "Ex 'n'"],
            ["1.5 kW", "400V", "80", "Ex 'n'"],
        ]
    ],

    // DC MOTORS (Category ID: 24)
    [
        24,
        "Large DC Machines",
        "Laminated Yoke DC Motors up to 2000 kW",
        "Fully equipped to manufacture state-of-the-art LAMINATED YOKE DC Motors up to 2000 kW (50,000 Nm torque) in frame size up to 710 for all variable speed drive applications.",
        "DC-Large-DC-Machines.webp",
        [
            ["200 kW", "240V DC", "315M", "IEC 60034-8"],
            ["500 kW", "240V DC", "400M", "IEC 60034-8"],
            ["1000 kW", "240V DC", "500M", "IEC 60034-8"],
        ]
    ],
    [
        24,
        "DC Motors",
        "Industrial DC Motors up to 1500 kW",
        "Established name synonymous with rotating machines. Manufacturing unit at Ahmednagar (Maharashtra), India manufactures DC motors up to 1500 kW.",
        "DC-DC-Motors.webp",
        [
            ["50 kW", "240V DC", "180M", "IS standard"],
            ["100 kW", "240V DC", "250M", "IS standard"],
            ["200 kW", "240V DC", "315M", "IS standard"],
        ]
    ],

    // HAZARDOUS AREA MOTORS (HV) (Category ID: 25)
    [
        25,
        "Flame Proof Large Motors Ex 'd' (HV)",
        "High Voltage Flameproof Motors",
        "Motors for hazardous area applications. Keep abreast with latest developments to ensure compliance with all relevant European and Indian standards.",
        "HazHV-Flame-Proof-Motors.webp",
        [
            ["30 kW", "6.6 kV", "250M", "Ex 'd'"],
            ["55 kW", "6.6 kV", "315M", "Ex 'd'"],
            ["110 kW", "11 kV", "355M", "Ex 'd'"],
        ]
    ],
    [
        25,
        "Increased Safety Motors Ex 'e' (HV)",
        "Increased Safety Motors HV",
        "State-of-the-art INCREASED SAFETY MOTORS. For use in hazardous areas for Zone II areas.",
        "HazHV-Increased-Safety-Motors.webp",
        [
            ["30 kW", "6.6 kV", "250M", "Ex 'e'"],
            ["55 kW", "6.6 kV", "315M", "Ex 'e'"],
            ["110 kW", "11 kV", "355M", "Ex 'e'"],
        ]
    ],
    [
        25,
        "Non Sparking Motor Ex 'n' (HV)",
        "Non-Sparking Motors HV",
        "State of the art NON-SPARKING Motors Ex 'n'. For use in hazardous areas for Zone II areas.",
        "HazHV-Non-Sparking-Motors.webp",
        [
            ["30 kW", "6.6 kV", "250M", "Ex 'n'"],
            ["55 kW", "6.6 kV", "315M", "Ex 'n'"],
            ["110 kW", "11 kV", "355M", "Ex 'n'"],
        ]
    ],
    [
        25,
        "Pressurized Motor Ex 'p' (HV)",
        "Pressurized Motors for Zone-1 Areas",
        "Economical solution for large rating Flameproof motors usable in Zone-1 of Hazardous Area. Motors up to 12 MW with Ex 'p' enclosure. Pneumatic Pressurisation System with Automatic Purge Arrangement.",
        "HazHV-Pressurized-Motors.webp",
        [
            ["1000 kW", "11 kV", "400M", "Ex 'p'"],
            ["2000 kW", "11 kV", "500M", "Ex 'p'"],
            ["5000 kW", "22 kV", "560M", "Ex 'p'"],
        ]
    ],

    // SPECIAL APPLICATION MOTORS (Category ID: 26)
    [
        26,
        "Double Cage Motor for Cement Mill",
        "Cement Mill Twin Drive Motors",
        "For raw and cement ball-mill twin drives. CG Hungary first to successfully introduce double Squirrel Cage motors. Cost-effective alternative without costly starting equipment and controllers.",
        "SA-Double-Cage-Cement-Mill.webp",
        [
            ["37 kW", "6.6 kV", "280M", "Cement Duty"],
            ["55 kW", "6.6 kV", "315M", "Cement Duty"],
            ["75 kW", "11 kV", "355M", "Cement Duty"],
        ]
    ],
    [
        26,
        "Brake Motors",
        "Kibosh Brake Motors",
        "Kibosh Brake Motors - most reliable range with highly competitive delivery time. Designed for various braking applications like crane, hoists, rolling mills, wind mills, elevators.",
        "SA-Brake-Motors.webp",
        [
            ["1.5 kW", "400V", "80", "Brake equipped"],
            ["3 kW", "400V", "90L", "Brake equipped"],
            ["5.5 kW", "400V", "100L", "Brake equipped"],
        ]
    ],
    [
        26,
        "Oil Well Pump Motor",
        "High Slip High Torque Oil Well Motors",
        "Latest designs for OIL WELL PUMP MOTORS. Designed with high slip and high torque (NEMA D Design) per oil well beam pumping unit requirements. Low initial costs and minimal maintenance.",
        "SA-Oil-Well-Motor.webp",
        [
            ["15 Hp", "440V", "213T", "NEMA D"],
            ["20 Hp", "440V", "215T", "NEMA D"],
            ["30 Hp", "440V", "254T", "NEMA D"],
        ]
    ],
    [
        26,
        "Re-Rolling Mill Motor",
        "Heavy Duty Re-Rolling Mill Motors",
        "Highly standardized range of motors dedicated to Re-rolling mill industry. Used to drive re-rolling mills for hot steel rolling. Designed for demanding conditions with widely fluctuating loads.",
        "SA-Re-Rolling-Mill-Motor.webp",
        [
            ["75 kW", "6.6 kV", "315M", "High Duty Cycle"],
            ["110 kW", "6.6 kV", "355M", "High Duty Cycle"],
            ["160 kW", "11 kV", "400M", "High Duty Cycle"],
        ]
    ],
];

$imported = 0;
$errors = 0;

foreach ($motor_data as $idx => $product) {
    $categoryMID = $product[0];
    $motorTitle = $product[1];
    $motorSubTitle = $product[2];
    $motorDesc = $product[3];
    $motorImage = $product[4];
    $variations = $product[5];

    // Generate SEO URI
    $seoUri = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $motorTitle), '-'));

    // Insert main motor record
    $insert_sql = "INSERT INTO {$table_pre}motor
        (categoryMID, motorTitle, motorSubTitle, motorDesc, motorImage, seoUri, status)
        VALUES (?, ?, ?, ?, ?, ?, 1)";

    $stmt = $DB->prepare($insert_sql);
    if (!$stmt) {
        echo "[ERROR] Prepare failed: " . $DB->error . "\n";
        $errors++;
        continue;
    }

    $stmt->bind_param("isssss", $categoryMID, $motorTitle, $motorSubTitle, $motorDesc, $motorImage, $seoUri);

    if ($stmt->execute()) {
        $motorID = $DB->insert_id;
        echo "[OK] Inserted: $motorTitle (ID: $motorID)\n";

        // Insert variations
        foreach ($variations as $var_idx => $variation) {
            $descTitle = $variation[0] . " | " . $variation[1];
            $descOutput = $variation[0];
            $descVoltage = $variation[1];
            $descFrameSize = $variation[2];
            $descStandard = $variation[3];

            $detail_sql = "INSERT INTO {$table_pre}motor_detail
                (motorID, descriptionTitle, descriptionOutput, descriptionVoltage, descriptionFrameSize, descriptionStandard, status)
                VALUES (?, ?, ?, ?, ?, ?, 1)";

            $detail_stmt = $DB->prepare($detail_sql);
            if (!$detail_stmt) {
                echo "  [ERROR] Detail prepare failed: " . $DB->error . "\n";
                $errors++;
                continue;
            }

            $detail_stmt->bind_param("isssss", $motorID, $descTitle, $descOutput, $descVoltage, $descFrameSize, $descStandard);

            if ($detail_stmt->execute()) {
                echo "  → Variation: $descOutput | $descVoltage | $descFrameSize | $descStandard\n";
            } else {
                echo "  [ERROR] Detail insert failed: " . $detail_stmt->error . "\n";
                $errors++;
            }
            $detail_stmt->close();
        }
        $imported++;
    } else {
        echo "[ERROR] Insert failed: " . $stmt->error . "\n";
        $errors++;
    }
    $stmt->close();
}

echo "\n=========================================\n";
echo "IMPORT COMPLETE\n";
echo "=========================================\n";
echo "Products Imported: $imported\n";
echo "Errors: $errors\n";

$DB->close();
?>
