<?php
/**
 * Pump Specifications Auto-Population Script
 * Fills in missing: kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType
 * And pump_detail specifications
 */

$DBHOST = 'localhost';
$DBNAME = 'bombayengg';
$DBUSER = 'bombayengg';
$DBPASS = 'oCFCrCMwKyy5jzg';

$conn = mysqli_connect($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// Comprehensive specifications database
$pump_specs = [
    // MINI PUMPS
    'MINI MASTER' => [
        'main' => ['kwhp' => '0.74KW', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.74, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '10-30', 'dischargeRange' => '10-15 LPM', 'mrp' => '6500-8500', 'warrenty' => '12 Months']]
    ],
    'MINI MASTERPLUS' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '15-35', 'dischargeRange' => '12-18 LPM', 'mrp' => '7500-9500', 'warrenty' => '12 Months']]
    ],
    'MINI FORCE' => [
        'main' => ['kwhp' => '0.5-1 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [
            ['powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '8-25', 'dischargeRange' => '8-12 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-16 LPM', 'mrp' => '6500-8000', 'warrenty' => '12 Months']
        ]
    ],
    'MINI MARVEL' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '15-35', 'dischargeRange' => '12-18 LPM', 'mrp' => '5500-7500', 'warrenty' => '12 Months']]
    ],
    'CHAMP' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-14 LPM', 'mrp' => '4000-6000', 'warrenty' => '12 Months']]
    ],
    'FLOMAX' => [
        'main' => ['kwhp' => '0.75HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.55, 'powerHp' => 0.75, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '10-28', 'dischargeRange' => '9-13 LPM', 'mrp' => '3500-5500', 'warrenty' => '12 Months']]
    ],
    'AQUAGOLD' => [
        'main' => ['kwhp' => '0.75-1.5HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25-32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [
            ['powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '8-25', 'dischargeRange' => '8-12 LPM', 'mrp' => '4000-6000', 'warrenty' => '12 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-15 LPM', 'mrp' => '5000-7000', 'warrenty' => '12 Months'],
            ['powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '15-35', 'dischargeRange' => '12-20 LPM', 'mrp' => '6500-8500', 'warrenty' => '12 Months']
        ]
    ],

    // 3-INCH SUBMERSIBLES
    '3W' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '76', 'noOfStage' => '8', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Agricultural'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 76, 'noOfStageD' => 8, 'headRange' => '20-70', 'dischargeRange' => '15-25 LPM', 'mrp' => '8000-12000', 'warrenty' => '18 Months']]
    ],

    // 4-INCH OIL-FILLED SUBMERSIBLES
    '4VO' => [
        'main' => ['kwhp' => '0.75-2 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '4-7', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Oil-Filled Borewell'],
        'detail' => [
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 4, 'headRange' => '40-80', 'dischargeRange' => '20-35 LPM', 'mrp' => '12000-15000', 'warrenty' => '24 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 5, 'headRange' => '50-100', 'dischargeRange' => '18-30 LPM', 'mrp' => '13000-16000', 'warrenty' => '24 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 7, 'headRange' => '70-130', 'dischargeRange' => '15-25 LPM', 'mrp' => '14000-17000', 'warrenty' => '24 Months'],
            ['powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 5, 'headRange' => '50-110', 'dischargeRange' => '28-45 LPM', 'mrp' => '15000-18000', 'warrenty' => '24 Months'],
            ['powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 6, 'headRange' => '60-120', 'dischargeRange' => '25-40 LPM', 'mrp' => '16000-19000', 'warrenty' => '24 Months'],
            ['powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 7, 'headRange' => '70-140', 'dischargeRange' => '22-35 LPM', 'mrp' => '17000-20000', 'warrenty' => '24 Months']
        ]
    ],

    // 4-INCH WATER-FILLED SUBMERSIBLES
    '4W' => [
        'main' => ['kwhp' => '0.75-2 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '4-7', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Water-Filled Borewell'],
        'detail' => [
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 4, 'headRange' => '35-75', 'dischargeRange' => '22-38 LPM', 'mrp' => '10000-13000', 'warrenty' => '18 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 7, 'headRange' => '65-125', 'dischargeRange' => '18-28 LPM', 'mrp' => '11000-14000', 'warrenty' => '18 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 10, 'headRange' => '95-175', 'dischargeRange' => '14-22 LPM', 'mrp' => '12000-15000', 'warrenty' => '18 Months'],
            ['powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 5, 'headRange' => '45-95', 'dischargeRange' => '35-55 LPM', 'mrp' => '13000-16000', 'warrenty' => '18 Months'],
            ['powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 7, 'headRange' => '65-130', 'dischargeRange' => '30-48 LPM', 'mrp' => '14000-17000', 'warrenty' => '18 Months']
        ]
    ],

    // OPENWELL PUMPS
    'OPENWELL' => [
        'main' => ['kwhp' => '1-3 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '50-75', 'noOfStage' => 'N/A', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Openwell Centrifugal'],
        'detail' => [
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => '5-10', 'dischargeRange' => '40-60 LPM', 'mrp' => '4000-6000', 'warrenty' => '12 Months'],
            ['powerKw' => 1.5, 'powerHp' => 2.0, 'supplyPhaseD' => 1, 'pipePhase' => 75, 'noOfStageD' => 1, 'headRange' => '8-12', 'dischargeRange' => '80-120 LPM', 'mrp' => '8000-12000', 'warrenty' => '12 Months'],
            ['powerKw' => 2.25, 'powerHp' => 3.0, 'supplyPhaseD' => 1, 'pipePhase' => 75, 'noOfStageD' => 1, 'headRange' => '10-15', 'dischargeRange' => '120-180 LPM', 'mrp' => '12000-18000', 'warrenty' => '12 Months']
        ]
    ],

    // SHALLOW WELL PUMPS
    'SWJ' => [
        'main' => ['kwhp' => '0.5-1 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32-50', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Shallow Well Self-Priming'],
        'detail' => [
            ['powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '7-15', 'dischargeRange' => '15-25 LPM', 'mrp' => '5000-7000', 'warrenty' => '12 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => '8-18', 'dischargeRange' => '25-40 LPM', 'mrp' => '6500-8500', 'warrenty' => '12 Months']
        ]
    ],

    // AGRICULTURAL SUBMERSIBLES (100W)
    '100W' => [
        'main' => ['kwhp' => '100W', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Agricultural Submersible'],
        'detail' => [['powerKw' => 0.1, 'powerHp' => 0.13, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '5-20', 'dischargeRange' => '8-15 LPM', 'mrp' => '3000-5000', 'warrenty' => '12 Months']]
    ],

    // PRESSURE BOOSTER
    'BOOSTER' => [
        'main' => ['kwhp' => '1-2 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25-32', 'noOfStage' => 'N/A', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Pressure Booster'],
        'detail' => [
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '20-40', 'dischargeRange' => '20-30 LPM', 'mrp' => '8000-11000', 'warrenty' => '18 Months'],
            ['powerKw' => 1.5, 'powerHp' => 2.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '30-60', 'dischargeRange' => '35-50 LPM', 'mrp' => '12000-15000', 'warrenty' => '18 Months']
        ]
    ],

    // CIRCULATORY
    'CIRCULATORY' => [
        'main' => ['kwhp' => '0.5-1 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '19-25', 'noOfStage' => 'N/A', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Circulatory In-Line'],
        'detail' => [['powerKw' => 0.55, 'powerHp' => 0.75, 'supplyPhaseD' => 1, 'pipePhase' => 19, 'noOfStageD' => 1, 'headRange' => '5-15', 'dischargeRange' => '20-40 LPM', 'mrp' => '5000-8000', 'warrenty' => '12 Months']]
    ]
];

// Get all pumps
$query = "SELECT pumpID, pumpTitle FROM mx_pump WHERE status = 1 ORDER BY pumpTitle";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$pumps = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pumps[] = $row;
}

echo "Processing " . count($pumps) . " pumps...\n";
echo str_repeat("=", 80) . "\n";

$updated_main = 0;
$updated_detail = 0;
$log = [];

foreach ($pumps as $pump) {
    $pumpID = intval($pump['pumpID']);
    $title = $pump['pumpTitle'];
    $title_upper = strtoupper($title);

    // Find matching specification template
    $matched_spec = null;
    foreach ($pump_specs as $pattern => $spec) {
        if (strpos($title_upper, $pattern) !== false) {
            $matched_spec = $spec;
            break;
        }
    }

    // Update main pump table if we have specs
    if ($matched_spec) {
        $main_spec = $matched_spec['main'];

        // Check if main specs are missing
        $check_query = "SELECT kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType FROM mx_pump WHERE pumpID = $pumpID";
        $check_result = mysqli_query($conn, $check_query);
        $current = mysqli_fetch_assoc($check_result);

        // Update if fields are empty
        $updates = [];
        if (empty($current['kwhp'])) $updates['kwhp'] = $main_spec['kwhp'];
        if (empty($current['supplyPhase'])) $updates['supplyPhase'] = $main_spec['supplyPhase'];
        if (empty($current['deliveryPipe'])) $updates['deliveryPipe'] = $main_spec['deliveryPipe'];
        if (empty($current['noOfStage'])) $updates['noOfStage'] = $main_spec['noOfStage'];
        if (empty($current['isi'])) $updates['isi'] = $main_spec['isi'];
        if (empty($current['mnre'])) $updates['mnre'] = $main_spec['mnre'];
        if (empty($current['pumpType'])) $updates['pumpType'] = $main_spec['pumpType'];

        if (count($updates) > 0) {
            $set_clause = [];
            foreach ($updates as $field => $value) {
                $value = mysqli_real_escape_string($conn, $value);
                $set_clause[] = "$field = '$value'";
            }
            $update_query = "UPDATE mx_pump SET " . implode(", ", $set_clause) . " WHERE pumpID = $pumpID";
            if (mysqli_query($conn, $update_query)) {
                $updated_main++;
                $log[] = "[✓ MAIN] $title - Updated " . count($updates) . " fields";
            }
        }

        // Update or insert pump details
        if (isset($matched_spec['detail']) && is_array($matched_spec['detail'])) {
            // Check if details exist
            $detail_check = "SELECT COUNT(*) as cnt FROM mx_pump_detail WHERE pumpID = $pumpID";
            $detail_result = mysqli_query($conn, $detail_check);
            $detail_count = mysqli_fetch_assoc($detail_result)['cnt'];

            // If no details, insert them
            if ($detail_count == 0) {
                foreach ($matched_spec['detail'] as $detail_idx => $detail_spec) {
                    $categoryref = isset($detail_spec['categoryref']) ? $detail_spec['categoryref'] : '';
                    $powerKw = $detail_spec['powerKw'];
                    $powerHp = $detail_spec['powerHp'];
                    $supplyPhaseD = $detail_spec['supplyPhaseD'];
                    $pipePhase = $detail_spec['pipePhase'];
                    $noOfStageD = $detail_spec['noOfStageD'];
                    $headRange = mysqli_real_escape_string($conn, $detail_spec['headRange']);
                    $dischargeRange = mysqli_real_escape_string($conn, $detail_spec['dischargeRange']);
                    $mrp = mysqli_real_escape_string($conn, $detail_spec['mrp']);
                    $warrenty = mysqli_real_escape_string($conn, $detail_spec['warrenty']);

                    $insert_query = "INSERT INTO mx_pump_detail
                        (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty, status)
                        VALUES
                        ($pumpID, '$categoryref', $powerKw, $powerHp, $supplyPhaseD, $pipePhase, $noOfStageD, '$headRange', '$dischargeRange', '$mrp', '$warrenty', 1)";

                    if (mysqli_query($conn, $insert_query)) {
                        $updated_detail++;
                    }
                }
                $log[] = "[✓ DETAIL] $title - Inserted " . count($matched_spec['detail']) . " detail records";
            }
        }
    } else {
        $log[] = "[⚠ SKIPPED] $title - No matching template found";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SPECIFICATIONS UPDATE COMPLETE\n";
echo str_repeat("=", 80) . "\n\n";
echo "Main Specifications Updated: $updated_main\n";
echo "Detail Specifications Added: $updated_detail\n";
echo "Total Pumps Processed: " . count($pumps) . "\n\n";

echo "Log:\n";
foreach ($log as $entry) {
    echo "$entry\n";
}

// Save log
$logfile = "PUMP_SPECS_UPDATE_LOG_" . date('YmdHis') . ".txt";
file_put_contents($logfile, implode("\n", $log));
echo "\nLog saved: $logfile\n";

mysqli_close($conn);
?>
