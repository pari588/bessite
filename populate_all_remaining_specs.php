<?php
/**
 * Update Complete Specifications for:
 * - 3-Inch Borewell Submersibles
 * - 4-Inch Borewell Submersibles (Oil-Filled & Water-Filled)
 * - Pressure Booster Pumps
 * - Residential Openwell Pumps
 * All from Official Crompton Website
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

// Complete specifications from Crompton
$complete_specs = [
    // 3-INCH BOREWELL SUBMERSIBLES
    '3W10AK1A' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '76', 'noOfStage' => '8', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Agricultural'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 76, 'noOfStageD' => 8, 'headRange' => 70, 'dischargeRange' => '15-25', 'mrp' => '₹13,800.00', 'warrenty' => '18 Months'],
        ]
    ],
    '3W10AP1D' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '76', 'noOfStage' => '8', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Agricultural'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 76, 'noOfStageD' => 8, 'headRange' => 70, 'dischargeRange' => '15-25', 'mrp' => '₹13,800.00', 'warrenty' => '18 Months'],
        ]
    ],
    '3W12AP1D' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '76', 'noOfStage' => '8', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Agricultural'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 76, 'noOfStageD' => 8, 'headRange' => 70, 'dischargeRange' => '15-25', 'mrp' => '₹14,600.00', 'warrenty' => '18 Months'],
        ]
    ],

    // 4-INCH WATER-FILLED BOREWELL SUBMERSIBLES
    '4W7BU1AU' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '4', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Water-Filled Borewell'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 4, 'headRange' => 35, 'dischargeRange' => '22-38', 'mrp' => '₹14,750.00', 'warrenty' => '18 Months'],
        ]
    ],
    '4W10BU1AU' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '7', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Water-Filled Borewell'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 7, 'headRange' => 65, 'dischargeRange' => '18-28', 'mrp' => '₹15,875.00', 'warrenty' => '18 Months'],
        ]
    ],
    '4W12BF1.5E' => [
        'main' => ['kwhp' => '1.5HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '5', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Water-Filled Borewell'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 5, 'headRange' => 60, 'dischargeRange' => '1000-1200', 'mrp' => '₹17,700.00', 'warrenty' => '18 Months'],
        ]
    ],
    '4W14BF1.5E' => [
        'main' => ['kwhp' => '1.5HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '7', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Water-Filled Borewell'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 7, 'headRange' => 85, 'dischargeRange' => '900-1100', 'mrp' => '₹19,750.00', 'warrenty' => '18 Months'],
        ]
    ],
    '4W14BU2EU' => [
        'main' => ['kwhp' => '2HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '10', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Water-Filled Borewell'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 1.5, 'powerHp' => 2.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 10, 'headRange' => 95, 'dischargeRange' => '800-1000', 'mrp' => '₹22,700.00', 'warrenty' => '18 Months'],
        ]
    ],

    // 4-INCH OIL-FILLED BOREWELL SUBMERSIBLES
    '4VO7BU1EU' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '4', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Oil-Filled Borewell'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 4, 'headRange' => 50, 'dischargeRange' => '800-1000', 'mrp' => '₹12,850.00', 'warrenty' => '24 Months'],
        ]
    ],
    '4VO1/7-BUE(U4S)' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '4', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Oil-Filled Borewell'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 4, 'headRange' => 50, 'dischargeRange' => '800-1000', 'mrp' => '₹12,850.00', 'warrenty' => '24 Months'],
        ]
    ],
    '4VO10BU1EU' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '5', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Oil-Filled Borewell'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 5, 'headRange' => 65, 'dischargeRange' => '700-900', 'mrp' => '₹13,650.00', 'warrenty' => '24 Months'],
        ]
    ],
    '4VO1/10-BUE(U4S)' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '5', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Oil-Filled Borewell'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 5, 'headRange' => 65, 'dischargeRange' => '700-900', 'mrp' => '₹13,650.00', 'warrenty' => '24 Months'],
        ]
    ],
    '4VO1.5/12-BUE(U4S)' => [
        'main' => ['kwhp' => '1.5HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '6', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Oil-Filled Borewell'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 6, 'headRange' => 75, 'dischargeRange' => '1000-1200', 'mrp' => '₹16,450.00', 'warrenty' => '24 Months'],
        ]
    ],
    '4VO1.5/14-BUE(U4S)' => [
        'main' => ['kwhp' => '1.5HP', 'supplyPhase' => 'S', 'deliveryPipe' => '100', 'noOfStage' => '7', 'isi' => 'Yes', 'mnre' => 'Yes', 'pumpType' => 'Submersible Oil-Filled Borewell'],
        'details' => [
            ['categoryref' => 'Agricultural', 'powerKw' => 1.1, 'powerHp' => 1.5, 'supplyPhaseD' => 1, 'pipePhase' => 100, 'noOfStageD' => 7, 'headRange' => 90, 'dischargeRange' => '900-1100', 'mrp' => '₹17,200.00', 'warrenty' => '24 Months'],
        ]
    ],

    // PRESSURE BOOSTER PUMPS
    'CFMSMB3D0.50-V24' => [
        'main' => ['kwhp' => '0.5HP', 'supplyPhase' => 'S', 'deliveryPipe' => '25', 'noOfStage' => 'N/A', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Pressure Booster'],
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 25, 'noOfStageD' => 1, 'headRange' => 20, 'dischargeRange' => '15-25', 'mrp' => '₹26,075.00', 'warrenty' => '12 Months'],
        ]
    ],
    'CFMSMB5D1.00-V24' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => 'N/A', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Pressure Booster'],
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 30, 'dischargeRange' => '30-50', 'mrp' => '₹27,950.00', 'warrenty' => '12 Months'],
        ]
    ],

    // RESIDENTIAL OPENWELL PUMPS
    'OWE052(1PH)Z-21FS' => [
        'main' => ['kwhp' => '0.5HP', 'supplyPhase' => 'S', 'deliveryPipe' => '32', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Openwell Self-Priming'],
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.37, 'powerHp' => 0.5, 'supplyPhaseD' => 1, 'pipePhase' => 32, 'noOfStageD' => 1, 'headRange' => 8, 'dischargeRange' => '25-40', 'mrp' => '₹11,500.00', 'warrenty' => '12 Months'],
        ]
    ],
    'OWE12(1PH)Z-28' => [
        'main' => ['kwhp' => '1HP', 'supplyPhase' => 'S', 'deliveryPipe' => '50', 'noOfStage' => '1', 'isi' => 'Yes', 'mnre' => 'No', 'pumpType' => 'Openwell Self-Priming'],
        'details' => [
            ['categoryref' => 'Residential', 'powerKw' => 0.75, 'powerHp' => 1.0, 'supplyPhaseD' => 1, 'pipePhase' => 50, 'noOfStageD' => 1, 'headRange' => 10, 'dischargeRange' => '40-60', 'mrp' => '₹13,625.00', 'warrenty' => '12 Months'],
        ]
    ],
];

$main_updated = 0;
$detail_updated = 0;
$detail_inserted = 0;
$log = [];

foreach ($complete_specs as $pump_pattern => $spec_data) {
    $main_spec = $spec_data['main'];
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
            }
        }

        // Update or insert detail records
        $check_query = "SELECT COUNT(*) as cnt FROM mx_pump_detail WHERE pumpID = $pumpID";
        $check_result = mysqli_query($conn, $check_query);
        $check = mysqli_fetch_assoc($check_result);

        if ($check['cnt'] > 0) {
            // Delete and reinsert with complete specs
            mysqli_query($conn, "DELETE FROM mx_pump_detail WHERE pumpID = $pumpID");
        }

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
        $log[] = "[✓] $title - Complete specs updated with official Crompton MRP";
    }
}

echo "Updating All Remaining Pump Specifications from Crompton...\n";
echo str_repeat("=", 80) . "\n\n";
echo "Main Specs Updated: $main_updated\n";
echo "Detail Records Inserted: $detail_inserted\n\n";
echo "Update Log:\n";
foreach ($log as $entry) {
    echo "$entry\n";
}

// Save log
$logfile = "ALL_REMAINING_SPECS_LOG_" . date('YmdHis') . ".txt";
file_put_contents($logfile, implode("\n", $log));
echo "\nLog saved: $logfile\n";

mysqli_close($conn);
?>
