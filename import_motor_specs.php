<?php
// Add motor specifications/variants
require_once 'config.inc.php';

$DB = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if ($DB->connect_error) {
    die("Connection failed: " . $DB->connect_error);
}

echo "IMPORTING MOTOR SPECIFICATIONS\n";
echo "===============================\n\n";

// Get existing motors and add specs
$specs_data = [
    // Product titles and their variants
    "Air Cooled Induction Motors - IC 6A1A1, IC 6A1A6, IC 6A6A6 (CACA)" => [
        ["11 kW", "6.6 kV", "180L", "IS 2253"],
        ["15 kW", "6.6 kV", "200L", "IS 2253"],
        ["22 kW", "11 kV", "225M", "IS 2253"],
    ],
    "Double Cage Motor for Cement Mill" => [
        ["37 kW", "6.6 kV", "280M", "IS 325"],
        ["55 kW", "6.6 kV", "315M", "IS 325"],
        ["75 kW", "11 kV", "355M", "IS 325"],
    ],
    "Water Cooled Induction Motors - IC 8A1W7 (CACW)" => [
        ["30 kW", "6.6 kV", "250M", "IS 2253"],
        ["45 kW", "6.6 kV", "280M", "IS 2253"],
        ["55 kW", "11 kV", "315M", "IS 2253"],
    ],
    "Open Air Type Induction Motor - IC 0A1, IC 0A6 (SPDP)" => [
        ["15 kW", "6.6 kV", "200L", "IS 325"],
        ["22 kW", "6.6 kV", "225M", "IS 325"],
        ["30 kW", "11 kV", "250M", "IS 325"],
    ],
    "Tube Ventilated Induction Motor -IC 5A1A1, IC 5A1A6 (TETV)" => [
        ["22 kW", "6.6 kV", "225M", "IS 2253"],
        ["37 kW", "6.6 kV", "280M", "IS 2253"],
        ["55 kW", "11 kV", "315M", "IS 2253"],
    ],
    "Fan Cooled Induction Motor - IC 4A1A1, IC 4A1A6 (TEFC)" => [
        ["11 kW", "6.6 kV", "180L", "IE2"],
        ["15 kW", "6.6 kV", "200L", "IE2"],
        ["22 kW", "11 kV", "225M", "IE2"],
    ],
    "Energy Efficient Motors HV - N Series" => [
        ["30 kW", "6.6 kV", "250M", "IE3"],
        ["45 kW", "6.6 kV", "280M", "IE3"],
        ["55 kW", "11 kV", "315M", "IE3"],
    ],
    "AXELERA Process Performance Motors" => [
        ["0.37 kW", "230V/400V", "63", "IE2"],
        ["0.75 kW", "230V/400V", "71", "IE2"],
        ["1.5 kW", "230V/400V", "80", "IE2"],
    ],
    "Flame Proof Motors Ex 'db' (LV)" => [
        ["0.37 kW", "400V", "63", "Ex 'db'"],
        ["0.75 kW", "400V", "71", "Ex 'db'"],
        ["1.5 kW", "400V", "80", "Ex 'db'"],
        ["3 kW", "400V", "90L", "Ex 'db'"],
        ["5.5 kW", "400V", "100L", "Ex 'db'"],
    ],
    "SMARTOR–CG Smart Motors" => [
        ["1.5 kW", "400V", "80", "IE3"],
        ["3 kW", "400V", "90L", "IE3"],
        ["5.5 kW", "400V", "100L", "IE3"],
    ],
    "Non Sparking Motor Ex 'nA' / Ex 'ec' (LV)" => [
        ["0.37 kW", "400V", "63", "Ex 'nA'"],
        ["0.75 kW", "400V", "71", "Ex 'nA'"],
        ["1.5 kW", "400V", "80", "Ex 'nA'"],
    ],
    "Increased Safety Motors Ex 'eb' (LV)" => [
        ["0.37 kW", "400V", "63", "Ex 'eb'"],
        ["0.75 kW", "400V", "71", "Ex 'eb'"],
        ["1.5 kW", "400V", "80", "Ex 'eb'"],
    ],
    "Cast Iron enclosure motors" => [
        ["0.5 Hp", "230/460V", "143T", "NEMA MG 1"],
        ["1 Hp", "230/460V", "145T", "NEMA MG 1"],
        ["1.5 Hp", "230/460V", "182T", "NEMA MG 1"],
    ],
    "Aluminum enclosure motors" => [
        ["0.5 Hp", "230/460V", "56", "NEMA MG 1"],
        ["1 Hp", "230/460V", "143T", "NEMA MG 1"],
        ["2 Hp", "230/460V", "145T", "NEMA MG 1"],
    ],
    "Cast Iron enclosure motors - Safe Area" => [
        ["0.37 kW", "400V", "63", "IE3"],
        ["0.75 kW", "400V", "71", "IE3"],
        ["1.5 kW", "400V", "80", "IE3"],
        ["3 kW", "400V", "90L", "IE3"],
        ["5.5 kW", "400V", "100L", "IE3"],
    ],
    "Aluminium enclosure motors - Safe area" => [
        ["0.18 kW", "400V", "63", "IE3"],
        ["0.37 kW", "400V", "63", "IE3"],
        ["0.75 kW", "400V", "71", "IE3"],
    ],
    "Slip Ring Motors (LV)" => [
        ["3.7 kW", "400V", "80", "TEFC"],
        ["5.5 kW", "400V", "90L", "TEFC"],
        ["7.5 kW", "400V", "100L", "TEFC"],
    ],
    "International Efficiency IE2 /IE3-Apex series" => [
        ["0.75 kW", "400V", "71", "IE3"],
        ["1.5 kW", "400V", "80", "IE3"],
        ["3 kW", "400V", "90L", "IE3"],
    ],
    "Super Premium IE4 Efficiency –Apex Series" => [
        ["1.5 kW", "400V", "80", "IE4"],
        ["3 kW", "400V", "90L", "IE4"],
        ["5.5 kW", "400V", "100L", "IE4"],
    ],
    "Totally Enclosed Fan Cooled Induction Motor - NG Series" => [
        ["3 kW", "400V", "90L", "IE3"],
        ["5.5 kW", "400V", "100L", "IE3"],
        ["7.5 kW", "400V", "112M", "IE3"],
    ],
    "Flame Proof Motors Ex 'd' (LV)" => [
        ["0.37 kW", "400V", "63", "Ex 'd'"],
        ["0.75 kW", "400V", "71", "Ex 'd'"],
        ["1.5 kW", "400V", "80", "Ex 'd'"],
    ],
    "Increased Safety Motors Ex 'e' (LV)" => [
        ["0.37 kW", "400V", "63", "Ex 'e'"],
        ["0.75 kW", "400V", "71", "Ex 'e'"],
        ["1.5 kW", "400V", "80", "Ex 'e'"],
    ],
    "Non Sparking Motor Ex 'n' (LV)" => [
        ["0.37 kW", "400V", "63", "Ex 'n'"],
        ["0.75 kW", "400V", "71", "Ex 'n'"],
        ["1.5 kW", "400V", "80", "Ex 'n'"],
    ],
    "Large DC Machines" => [
        ["200 kW", "240V DC", "315M", "IEC 60034-8"],
        ["500 kW", "240V DC", "400M", "IEC 60034-8"],
        ["1000 kW", "240V DC", "500M", "IEC 60034-8"],
    ],
    "DC Motors" => [
        ["50 kW", "240V DC", "180M", "IS standard"],
        ["100 kW", "240V DC", "250M", "IS standard"],
        ["200 kW", "240V DC", "315M", "IS standard"],
    ],
    "Flame Proof Large Motors Ex 'd' (HV)" => [
        ["30 kW", "6.6 kV", "250M", "Ex 'd'"],
        ["55 kW", "6.6 kV", "315M", "Ex 'd'"],
        ["110 kW", "11 kV", "355M", "Ex 'd'"],
    ],
    "Increased Safety Motors Ex 'e' (HV)" => [
        ["30 kW", "6.6 kV", "250M", "Ex 'e'"],
        ["55 kW", "6.6 kV", "315M", "Ex 'e'"],
        ["110 kW", "11 kV", "355M", "Ex 'e'"],
    ],
    "Non Sparking Motor Ex 'n' (HV)" => [
        ["30 kW", "6.6 kV", "250M", "Ex 'n'"],
        ["55 kW", "6.6 kV", "315M", "Ex 'n'"],
        ["110 kW", "11 kV", "355M", "Ex 'n'"],
    ],
    "Pressurized Motor Ex 'p' (HV)" => [
        ["1000 kW", "11 kV", "400M", "Ex 'p'"],
        ["2000 kW", "11 kV", "500M", "Ex 'p'"],
        ["5000 kW", "22 kV", "560M", "Ex 'p'"],
    ],
    "Brake Motors" => [
        ["1.5 kW", "400V", "80", "Brake equipped"],
        ["3 kW", "400V", "90L", "Brake equipped"],
        ["5.5 kW", "400V", "100L", "Brake equipped"],
    ],
    "Oil Well Pump Motor" => [
        ["15 Hp", "440V", "213T", "NEMA D"],
        ["20 Hp", "440V", "215T", "NEMA D"],
        ["30 Hp", "440V", "254T", "NEMA D"],
    ],
    "Re-Rolling Mill Motor" => [
        ["75 kW", "6.6 kV", "315M", "High Duty Cycle"],
        ["110 kW", "6.6 kV", "355M", "High Duty Cycle"],
        ["160 kW", "11 kV", "400M", "High Duty Cycle"],
    ],
];

$inserted = 0;
$errors = 0;

foreach ($specs_data as $motor_title => $variants) {
    // Find the motor ID by title
    $get_sql = "SELECT motorID FROM mx_motor WHERE motorTitle LIKE ? LIMIT 1";
    $get_stmt = $DB->prepare($get_sql);
    if (!$get_stmt) {
        echo "[ERROR] Prepare failed: " . $DB->error . "\n";
        $errors++;
        continue;
    }

    $search_title = "%$motor_title%";
    $get_stmt->bind_param("s", $search_title);
    $get_stmt->execute();
    $get_result = $get_stmt->get_result();
    $motor_row = $get_result->fetch_assoc();

    if (!$motor_row) {
        echo "[SKIP] Motor not found: $motor_title\n";
        $get_stmt->close();
        continue;
    }

    $motorID = $motor_row['motorID'];
    $get_stmt->close();

    echo "[OK] Processing: $motor_title (ID: $motorID)\n";

    // Delete existing specs for this motor
    $DB->query("DELETE FROM mx_motor_detail WHERE motorID = $motorID");

    // Insert new variants
    foreach ($variants as $variant) {
        $descTitle = $variant[0] . " | " . $variant[1];
        $descOutput = $variant[0];
        $descVoltage = $variant[1];
        $descFrameSize = $variant[2];
        $descStandard = $variant[3];

        $insert_sql = "INSERT INTO mx_motor_detail
            (motorID, descriptionTitle, descriptionOutput, descriptionVoltage, descriptionFrameSize, descriptionStandard, status)
            VALUES (?, ?, ?, ?, ?, ?, 1)";

        $insert_stmt = $DB->prepare($insert_sql);
        if (!$insert_stmt) {
            echo "  [ERROR] Prepare failed: " . $DB->error . "\n";
            $errors++;
            continue;
        }

        $insert_stmt->bind_param("isssss", $motorID, $descTitle, $descOutput, $descVoltage, $descFrameSize, $descStandard);

        if ($insert_stmt->execute()) {
            echo "  → $descOutput | $descVoltage | $descFrameSize | $descStandard\n";
            $inserted++;
        } else {
            echo "  [ERROR] Insert failed: " . $insert_stmt->error . "\n";
            $errors++;
        }
        $insert_stmt->close();
    }
}

echo "\n===============================\n";
echo "SPECIFICATION IMPORT COMPLETE\n";
echo "===============================\n";
echo "Specifications Inserted: $inserted\n";
echo "Errors: $errors\n";

$DB->close();
?>
