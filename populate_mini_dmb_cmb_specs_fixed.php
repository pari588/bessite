<?php
/**
 * Mini Pumps & DMB/CMB Specifications - FIXED VERSION
 * Using numeric headRange values (double field)
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

// Mini & DMB/CMB specifications with numeric headRange
$detailed_specs = [
    'MINI MASTER' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.74, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 25, 'dischargeRange' => '12-18', 'mrp' => '7500-9500', 'warrenty' => '12 Months'],
    ],
    'MINI MASTERPLUS' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 25, 'dischargeRange' => '12-18', 'mrp' => '8000-10000', 'warrenty' => '12 Months'],
    ],
    'MINI FORCE' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => 16, 'dischargeRange' => '8-12', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '6500-8000', 'warrenty' => '12 Months'],
    ],
    'MINI MARVEL' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 25, 'dischargeRange' => '12-18', 'mrp' => '5500-7500', 'warrenty' => '12 Months'],
    ],
    'MINI CREST' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '5000-7000', 'warrenty' => '12 Months'],
    ],
    'MINI SUMO' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'CHAMP' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-14', 'mrp' => '4000-6000', 'warrenty' => '12 Months'],
    ],
    'CHAMP DURA' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-14', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'CHAMP PLUS' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-14', 'mrp' => '4000-6000', 'warrenty' => '12 Months'],
    ],
    'FLOMAX' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.55, 'powerHp' => 0.75, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => 19, 'dischargeRange' => '9-13', 'mrp' => '3500-5500', 'warrenty' => '12 Months'],
    ],
    'FLOMAX PLUS' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 22, 'dischargeRange' => '11-17', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'GLIDE' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 22, 'dischargeRange' => '11-17', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'GLIDE PLUS' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 22, 'dischargeRange' => '11-17', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'GLORY' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'GLORY PLUS' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'MASTER' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 22, 'dischargeRange' => '11-17', 'mrp' => '5000-7000', 'warrenty' => '12 Months'],
    ],
    'MASTER DURA' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 22, 'dischargeRange' => '11-17', 'mrp' => '5500-7500', 'warrenty' => '12 Months'],
    ],
    'MASTER PLUS' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 22, 'dischargeRange' => '11-17', 'mrp' => '5000-7000', 'warrenty' => '12 Months'],
    ],
    'NILE' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'NILE DURA' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'NILE PLUS' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'PRIMO' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-15', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'STAR' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'STAR DURA' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '5000-7000', 'warrenty' => '12 Months'],
    ],
    'STAR PLUS' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'ULTIMO' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'WIN' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-16', 'mrp' => '4500-6500', 'warrenty' => '12 Months'],
    ],
    'EVEREST' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.55, 'powerHp' => 0.75, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => 19, 'dischargeRange' => '9-14', 'mrp' => '3500-5500', 'warrenty' => '12 Months'],
    ],

    // DMB/CMB Agricultural Mini Pumps
    'DMB' => [
        ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '14-22', 'mrp' => '6500-8500', 'warrenty' => '12 Months'],
    ],
    'DMB10D PLUS' => [
        ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '14-22', 'mrp' => '6500-8500', 'warrenty' => '12 Months'],
    ],
    'DMB10DCSL' => [
        ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '14-22', 'mrp' => '7000-9000', 'warrenty' => '12 Months'],
    ],
    'CMB' => [
        ['categoryref' => 'Agricultural', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => 17, 'dischargeRange' => '10-16', 'mrp' => '5000-7000', 'warrenty' => '12 Months'],
        ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '14-22', 'mrp' => '6000-8000', 'warrenty' => '12 Months'],
    ],
    'CMB05NV PLUS' => [
        ['categoryref' => 'Agricultural', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => 17, 'dischargeRange' => '10-16', 'mrp' => '5500-7500', 'warrenty' => '12 Months'],
    ],
    'CMB10NV PLUS' => [
        ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '14-22', 'mrp' => '6500-8500', 'warrenty' => '12 Months'],
    ],
    'AQUAGOLD' => [
        ['categoryref' => 'Residential', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => 16, 'dischargeRange' => '8-12', 'mrp' => '4000-6000', 'warrenty' => '12 Months'],
        ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 21, 'dischargeRange' => '10-15', 'mrp' => '5000-7000', 'warrenty' => '12 Months'],
        ['categoryref' => 'Residential', 'powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 25, 'dischargeRange' => '12-20', 'mrp' => '6500-8500', 'warrenty' => '12 Months'],
    ],
];

$updated = 0;
$skipped = 0;
$log = [];

foreach ($detailed_specs as $pattern => $specs_array) {
    // Get pump by title pattern
    $query = "SELECT pumpID, pumpTitle FROM mx_pump WHERE status = 1 AND pumpTitle LIKE '%$pattern%'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        $log[] = "[ERROR] Query failed for $pattern: " . mysqli_error($conn);
        continue;
    }

    while ($pump = mysqli_fetch_assoc($result)) {
        $pumpID = intval($pump['pumpID']);
        $title = $pump['pumpTitle'];

        // Check if details already exist
        $check_query = "SELECT COUNT(*) as cnt FROM mx_pump_detail WHERE pumpID = $pumpID";
        $check_result = mysqli_query($conn, $check_query);
        $check = mysqli_fetch_assoc($check_result);

        if ($check['cnt'] > 0) {
            $log[] = "[SKIP] $title - Details already exist";
            $skipped++;
            continue;
        }

        // Insert detail records
        foreach ($specs_array as $spec) {
            $categoryref = mysqli_real_escape_string($conn, $spec['categoryref']);
            $powerKw = floatval($spec['powerKw']);
            $powerHp = floatval($spec['powerHp']);
            $supplyPhaseD = intval($spec['supplyPhaseD']);
            $pipePhase = intval($spec['pipePhase']);
            $noOfStageD = intval($spec['noOfStageD']);
            $headRange = floatval($spec['headRange']); // NUMERIC!
            $dischargeRange = mysqli_real_escape_string($conn, $spec['dischargeRange']);
            $mrp = mysqli_real_escape_string($conn, $spec['mrp']);
            $warrenty = mysqli_real_escape_string($conn, $spec['warrenty']);

            $insert_query = "INSERT INTO mx_pump_detail
                (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase,
                 noOfStageD, headRange, dischargeRange, mrp, warrenty, status)
                VALUES
                ($pumpID, '$categoryref', $powerKw, $powerHp, $supplyPhaseD, $pipePhase,
                 $noOfStageD, $headRange, '$dischargeRange', '$mrp', '$warrenty', 1)";

            if (mysqli_query($conn, $insert_query)) {
                $updated++;
            } else {
                $log[] = "[ERROR] Insert failed for $title: " . mysqli_error($conn);
            }
        }
        $log[] = "[✓] $title - " . count($specs_array) . " detail records inserted";
    }
}

echo "Processing Mini Pumps & DMB/CMB Specifications (FIXED)...\n";
echo str_repeat("=", 80) . "\n\n";
echo "Detail Records Inserted: $updated\n";
echo "Pumps Skipped: $skipped\n\n";
echo "Update Log:\n";
foreach ($log as $entry) {
    echo "$entry\n";
}

// Save log
$logfile = "MINI_DMB_CMB_SPECS_FIXED_LOG_" . date('YmdHis') . ".txt";
file_put_contents($logfile, implode("\n", $log));
echo "\nLog saved: $logfile\n";

mysqli_close($conn);
?>
