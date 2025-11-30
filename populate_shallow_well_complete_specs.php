<?php
/**
 * Shallow Well Pumps - Complete Specifications from Crompton
 * Updates all detail specifications with correct MRP prices
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

// Complete Shallow Well Pump Specifications with Official Crompton MRP
$shallow_well_specs = [
    'SWJ1' => [
        'mrp' => '₹16,200.00',
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => 18, 'dischargeRange' => '25-40', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ50A-30 PLUS' => [
        'mrp' => '₹11,050.00',
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 15, 'dischargeRange' => '15-25', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ50AP-30 PLUS' => [
        'mrp' => '₹10,225.00',
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 15, 'dischargeRange' => '15-25', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ50AT-30 PLUS' => [
        'mrp' => '₹10,850.00',
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 15, 'dischargeRange' => '15-25', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ100A-36 PLUS' => [
        'mrp' => '₹13,125.00',
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => 18, 'dischargeRange' => '25-40', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ100AP-36 PLUS' => [
        'mrp' => '₹12,025.00',
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => 18, 'dischargeRange' => '25-40', 'warrenty' => '12 Months'],
        ]
    ],
    'SWJ100AT-36 PLUS' => [
        'mrp' => '₹12,875.00',
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => 18, 'dischargeRange' => '25-40', 'warrenty' => '12 Months'],
        ]
    ],
];

$updated = 0;
$inserted = 0;
$log = [];

foreach ($shallow_well_specs as $pump_pattern => $spec_data) {
    $mrp = $spec_data['mrp'];
    $details_array = $spec_data['details'];

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

        // Check if details already exist
        $check_query = "SELECT COUNT(*) as cnt FROM mx_pump_detail WHERE pumpID = $pumpID";
        $check_result = mysqli_query($conn, $check_query);
        $check = mysqli_fetch_assoc($check_result);

        if ($check['cnt'] > 0) {
            // Update existing records with correct MRP
            $update_mrp_query = "UPDATE mx_pump_detail SET mrp = '$mrp' WHERE pumpID = $pumpID";
            if (mysqli_query($conn, $update_mrp_query)) {
                $updated++;
                $log[] = "[✓ UPDATE] $title - MRP updated to $mrp";
            }
        } else {
            // Insert new detail records
            foreach ($details_array as $spec) {
                $categoryref = mysqli_real_escape_string($conn, $spec['categoryref']);
                $powerKw = floatval($spec['powerKw']);
                $powerHp = floatval($spec['powerHp']);
                $supplyPhaseD = intval($spec['supplyPhaseD']);
                $pipePhase = intval($spec['pipePhase']);
                $noOfStageD = intval($spec['noOfStageD']);
                $headRange = floatval($spec['headRange']);
                $dischargeRange = mysqli_real_escape_string($conn, $spec['dischargeRange']);
                $warrenty = mysqli_real_escape_string($conn, $spec['warrenty']);

                $insert_query = "INSERT INTO mx_pump_detail
                    (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase,
                     noOfStageD, headRange, dischargeRange, mrp, warrenty, status)
                    VALUES
                    ($pumpID, '$categoryref', $powerKw, $powerHp, $supplyPhaseD, $pipePhase,
                     $noOfStageD, $headRange, '$dischargeRange', '$mrp', '$warrenty', 1)";

                if (mysqli_query($conn, $insert_query)) {
                    $inserted++;
                    $log[] = "[✓ INSERT] $title - Detail record inserted with MRP $mrp";
                } else {
                    $log[] = "[ERROR] Insert failed for $title: " . mysqli_error($conn);
                }
            }
        }
    }
}

echo "Updating Shallow Well Pump Specifications from Crompton...\n";
echo str_repeat("=", 80) . "\n\n";
echo "Records Updated: $updated\n";
echo "Details Inserted: $inserted\n\n";
echo "Update Log:\n";
foreach ($log as $entry) {
    echo "$entry\n";
}

// Save log
$logfile = "SHALLOW_WELL_SPECS_UPDATE_LOG_" . date('YmdHis') . ".txt";
file_put_contents($logfile, implode("\n", $log));
echo "\nLog saved: $logfile\n";

mysqli_close($conn);
?>
