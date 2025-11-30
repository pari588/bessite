<?php
/**
 * Fix Shallow Well Pumps - Ensure ALL have complete specifications
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

// All Shallow Well Pump specifications
$all_shallow_well = [
    'SWJ1' => [
        'main' => ['kwhp' => '1HP/0.75', 'supplyPhase' => 'SP', 'deliveryPipe' => '50', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Shallow Well Self-Priming'],
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => 18, 'dischargeRange' => '25-40', 'mrp' => '₹16,200.00', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ50A-30 PLUS' => [
        'main' => ['kwhp' => '0.5HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Shallow Well Self-Priming'],
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 15, 'dischargeRange' => '15-25', 'mrp' => '₹11,050.00', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ50AP-30 PLUS' => [
        'main' => ['kwhp' => '0.5HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Shallow Well Self-Priming'],
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 15, 'dischargeRange' => '15-25', 'mrp' => '₹10,225.00', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ50AT-30 PLUS' => [
        'main' => ['kwhp' => '0.5HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Shallow Well Self-Priming'],
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 15, 'dischargeRange' => '15-25', 'mrp' => '₹10,850.00', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ100A-36 PLUS' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '50', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Shallow Well Self-Priming'],
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => 18, 'dischargeRange' => '25-40', 'mrp' => '₹13,125.00', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ100AP-36 PLUS' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '50', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Shallow Well Self-Priming'],
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => 18, 'dischargeRange' => '25-40', 'mrp' => '₹12,025.00', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ100AT-36 PLUS' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '50', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Shallow Well Self-Priming'],
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => 18, 'dischargeRange' => '25-40', 'mrp' => '₹12,875.00', 'warrenty' => '12 Months'],
        ]
    ],
];

$main_updated = 0;
$detail_deleted = 0;
$detail_inserted = 0;
$log = [];

foreach ($all_shallow_well as $pump_pattern => $spec_data) {
    // Find pump by title
    $query = "SELECT pumpID, pumpTitle FROM mx_pump WHERE status = 1 AND pumpTitle LIKE '%$pump_pattern%'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        $log[] = "[ERROR] Query failed for $pump_pattern: " . mysqli_error($conn);
        continue;
    }

    while ($pump = mysqli_fetch_assoc($result)) {
        $pumpID = intval($pump['pumpID']);
        $title = $pump['pumpTitle'];
        $main_spec = $spec_data['main'];
        $details_array = $spec_data['details'];

        // Update main specifications
        $main_updates = [];
        foreach ($main_spec as $field => $value) {
            $value = mysqli_real_escape_string($conn, $value);
            $main_updates[] = "$field = '$value'";
        }

        if (count($main_updates) > 0) {
            $update_main_query = "UPDATE mx_pump SET " . implode(", ", $main_updates) . " WHERE pumpID = $pumpID";
            if (mysqli_query($conn, $update_main_query)) {
                $main_updated++;
                $log[] = "[✓ MAIN] $title - Main specs updated";
            }
        }

        // Delete old detail records and insert new ones
        $delete_query = "DELETE FROM mx_pump_detail WHERE pumpID = $pumpID";
        if (mysqli_query($conn, $delete_query)) {
            $detail_deleted++;
        }

        // Insert complete detail records
        foreach ($details_array as $spec) {
            $categoryref = mysqli_real_escape_string($conn, $spec['categoryref']);
            $powerKw = floatval($spec['powerKw']);
            $powerHp = floatval($spec['powerHp']);
            $supplyPhaseD = intval($spec['supplyPhaseD']);
            $pipePhase = intval($spec['pipePhase']);
            $noOfStageD = intval($spec['noOfStageD']);
            $headRange = floatval($spec['headRange']);
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
                $detail_inserted++;
            }
        }
        $log[] = "[✓ DETAIL] $title - Detail records reset with complete specs";
    }
}

echo "Fixing Shallow Well Pump Specifications...\n";
echo str_repeat("=", 80) . "\n\n";
echo "Main Specs Updated: $main_updated\n";
echo "Old Details Deleted: $detail_deleted\n";
echo "New Details Inserted: $detail_inserted\n\n";
echo "Update Log:\n";
foreach ($log as $entry) {
    echo "$entry\n";
}

// Save log
$logfile = "SHALLOW_WELL_FIX_LOG_" . date('YmdHis') . ".txt";
file_put_contents($logfile, implode("\n", $log));
echo "\nLog saved: $logfile\n";

mysqli_close($conn);
?>
