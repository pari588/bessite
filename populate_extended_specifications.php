<?php
/**
 * Extended Pump Specifications Auto-Population
 * Covers additional pump models not in first script
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

// Extended specifications for remaining pumps
$extended_specs = [
    // ARMOR series - Openwell/Mini
    'ARMOR' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25-32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-16 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
            ['powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '15-35', 'dischargeRange' => '15-22 LPM', 'mrp' => '5500-7500', 'warrenty' => '12 Months']
        ]
    ],

    // CFM series - Centrifugal/Monoset
    'CFM' => [
        'main' => ['kwhp' => '0.5-1 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25-32', 'noOfStage' => 'N/A', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Centrifugal Monoset'],
        'detail' => [
            ['powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '5-12', 'dischargeRange' => '30-50 LPM', 'mrp' => '4000-6000', 'warrenty' => '12 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '8-15', 'dischargeRange' => '45-75 LPM', 'mrp' => '5500-7500', 'warrenty' => '12 Months']
        ]
    ],

    // CMB/DMB series - Agricultural Mini
    'CMB' => [
        'main' => ['kwhp' => '0.5-1 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Mini Agricultural'],
        'detail' => [
            ['powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '10-25', 'dischargeRange' => '10-16 LPM', 'mrp' => '5000-7000', 'warrenty' => '12 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '14-22 LPM', 'mrp' => '6000-8000', 'warrenty' => '12 Months']
        ]
    ],

    'DMB' => [
        'main' => ['kwhp' => '1 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Mini Agricultural'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '14-22 LPM', 'mrp' => '6500-8500', 'warrenty' => '12 Months']]
    ],

    // MAD series - Agricultural Mini
    'MAD' => [
        'main' => ['kwhp' => '0.5-1 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32-50', 'noOfStage' => 'N/A', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Agricultural Mini'],
        'detail' => [
            ['powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '10-25', 'dischargeRange' => '12-20 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '15-25 LPM', 'mrp' => '5500-7500', 'warrenty' => '12 Months']
        ]
    ],

    // MB series - Monoset/Centrifugal
    'MB' => [
        'main' => ['kwhp' => '1-2 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32-50', 'noOfStage' => 'N/A', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Centrifugal Monoset'],
        'detail' => [
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '8-20', 'dischargeRange' => '40-70 LPM', 'mrp' => '5000-7000', 'warrenty' => '12 Months'],
            ['powerKw' => 1.5, 'powerHp' => 2.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => '10-25', 'dischargeRange' => '60-100 LPM', 'mrp' => '8000-11000', 'warrenty' => '12 Months']
        ]
    ],

    // MI series - Mini
    'MI' => [
        'main' => ['kwhp' => '0.75-1 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25-32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [
            ['powerKw' => 0.55, 'powerHp' => 0.75, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '10-28', 'dischargeRange' => '9-14 LPM', 'mrp' => '4000-6000', 'warrenty' => '12 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-32', 'dischargeRange' => '11-17 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']
        ]
    ],

    // MIN series - Mini
    'MIN' => [
        'main' => ['kwhp' => '0.75 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Mini Agricultural'],
        'detail' => [['powerKw' => 0.55, 'powerHp' => 0.75, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '10-25', 'dischargeRange' => '10-16 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']]
    ],

    // MINH/MIP series - Mini Agricultural
    'MINH' => [
        'main' => ['kwhp' => '0.5-1 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25-32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Mini Agricultural'],
        'detail' => [
            ['powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '8-20', 'dischargeRange' => '8-12 LPM', 'mrp' => '3500-5500', 'warrenty' => '12 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '10-25', 'dischargeRange' => '10-16 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']
        ]
    ],

    'MIP' => [
        'main' => ['kwhp' => '0.5-1 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25-32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Mini Agricultural'],
        'detail' => [
            ['powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '8-22', 'dischargeRange' => '9-14 LPM', 'mrp' => '3500-5500', 'warrenty' => '12 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '10-28', 'dischargeRange' => '11-17 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']
        ]
    ],

    // OWE series - Openwell
    'OWE' => [
        'main' => ['kwhp' => '0.5-1 HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32-50', 'noOfStage' => 'N/A', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Openwell Self-Priming'],
        'detail' => [
            ['powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '5-15', 'dischargeRange' => '25-40 LPM', 'mrp' => '4000-6000', 'warrenty' => '12 Months'],
            ['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => '8-18', 'dischargeRange' => '40-60 LPM', 'mrp' => '5500-7500', 'warrenty' => '12 Months']
        ]
    ],

    // Other Mini/Self-Priming varieties
    'CREST' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-16 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']]
    ],

    'EVEREST' => [
        'main' => ['kwhp' => '0.75HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.55, 'powerHp' => 0.75, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => '10-28', 'dischargeRange' => '9-14 LPM', 'mrp' => '3500-5500', 'warrenty' => '12 Months']]
    ],

    'GLIDE' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-32', 'dischargeRange' => '11-17 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']]
    ],

    'GLORY' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-16 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']]
    ],

    'MASTER' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-32', 'dischargeRange' => '11-17 LPM', 'mrp' => '5000-7000', 'warrenty' => '12 Months']]
    ],

    'NILE' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-16 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']]
    ],

    'PRIMO' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-15 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']]
    ],

    'STAR' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-16 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']]
    ],

    'SUMO' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-16 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']]
    ],

    'ULTIMO' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-16 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']]
    ],

    'WIN' => [
        'main' => ['kwhp' => '1.0HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Mini Self-Priming'],
        'detail' => [['powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => '12-30', 'dischargeRange' => '10-16 LPM', 'mrp' => '4500-6500', 'warrenty' => '12 Months']]
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

echo "Processing extended specifications for " . count($pumps) . " pumps...\n";
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
    foreach ($extended_specs as $pattern => $spec) {
        if (strpos($title_upper, $pattern) !== false) {
            $matched_spec = $spec;
            break;
        }
    }

    if (!$matched_spec) {
        continue; // Skip if not in extended specs
    }

    $main_spec = $matched_spec['main'];

    // Check current specs
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
        $detail_check = "SELECT COUNT(*) as cnt FROM mx_pump_detail WHERE pumpID = $pumpID";
        $detail_result = mysqli_query($conn, $detail_check);
        $detail_count = mysqli_fetch_assoc($detail_result)['cnt'];

        if ($detail_count == 0) {
            foreach ($matched_spec['detail'] as $detail_spec) {
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
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "EXTENDED SPECIFICATIONS UPDATE COMPLETE\n";
echo str_repeat("=", 80) . "\n\n";
echo "Additional Main Specifications Updated: $updated_main\n";
echo "Additional Detail Specifications Added: $updated_detail\n\n";

echo "Log:\n";
foreach ($log as $entry) {
    echo "$entry\n";
}

// Save log
$logfile = "PUMP_EXTENDED_SPECS_LOG_" . date('YmdHis') . ".txt";
file_put_contents($logfile, implode("\n", $log));
echo "\nLog saved: $logfile\n";

mysqli_close($conn);
?>
