#!/usr/bin/env php
<?php
/**
 * Populate Product Details - Features and Specifications
 * Adds detailed product information extracted from Crompton website
 */

define('DBHOST', 'localhost');
define('DBNAME', 'bombayengg');
define('DBUSER', 'bombayengg');
define('DBPASS', 'oCFCrCMwKyy5jzg');

echo "\n" . str_repeat("=", 80) . "\n";
echo "POPULATE PRODUCT DETAILS - FEATURES AND SPECIFICATIONS\n";
echo str_repeat("=", 80) . "\n";
echo "Starting at: " . date('Y-m-d H:i:s') . "\n\n";

// Connect to database
try {
    $conn = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    echo "✓ Database connected\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Product details data from extraction file
$productDetails = [
    // Mini Pumps - All 1 HP models
    'NILE PLUS I' => [
        'features' => 'Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Features include thermal overload protection, anti-jam winding with high starting torque, wide voltage application, and superior suction capability up to 8.0 metres. Suitable for household, agricultural, and industrial applications. IP 55 protection and F-Class insulation.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'NILE PLUS I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '5850', 'warrenty' => '1 Year']
        ]
    ],
    'NILE DURA I' => [
        'features' => 'Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Premium model with anti-jam winding, F Class Insulation, and anti-drip protection (ADDS - Drip Proof Adaptor). Lift capacity up to 8.0 metres. 12 Month warranty. High-quality construction with brass impeller and stainless-steel inserts for corrosion resistance.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'NILE DURA I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '6700', 'warrenty' => '12 Months']
        ]
    ],
    'MINI SUMO I' => [
        'features' => 'Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Reliable pump with thermal overload protection, anti-jam winding with high starting torque, wide voltage design, and superior suction up to 8.0 metres. IP 55 protection and F-Class insulation. Suitable for all residential and agricultural applications.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MINI SUMO I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '6025', 'warrenty' => '1 Year']
        ]
    ],
    'MINI MASTERPLUS I' => [
        'features' => 'Premium Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Features 40% faster filling capability with 2-year warranty. IP 55 protection and F-Class insulation. Lift capacity up to 8.0 metres with durable brass impeller. Advanced electrical stamping and Hy-Flo Max technology for superior performance.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MINI MASTERPLUS I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '13050', 'warrenty' => '2 Years']
        ]
    ],
    'MINI MASTER I' => [
        'features' => 'Reliable Mini Self Priming Regenerative Pump 1 HP (0.74 kW). Features wide voltage design, thermal overload protection, 1-year warranty, IP 55 protection and F-Class insulation. Self-priming up to 7.5m. Weight: 14.58 kg. Ideal for household, agricultural, and industrial applications.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MINI MASTER I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '7.5m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '12025', 'warrenty' => '1 Year']
        ]
    ],
    'MINI MARVEL I' => [
        'features' => 'Compact Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Features wide voltage design, thermal overload protection, 1-year warranty, IP 55 protection and F-Class insulation. Lift capacity up to 8.0 metres. Weight: 9.91 kg. Lightweight and easy to install.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MINI MARVEL I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '7950', 'warrenty' => '1 Year']
        ]
    ],
    'GLORY PLUS I' => [
        'features' => 'Budget-friendly Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Features instant suction with lift up to 7.0 metres, thermal overload protection, anti-jam winding with high starting torque. Suitable for household, irrigation, and other applications. Reliable performance at affordable price.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'GLORY PLUS I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '7.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '6025', 'warrenty' => '1 Year']
        ]
    ],
    'MINI CREST I' => [
        'features' => 'Reliable Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Features wide voltage design, 1-year warranty, thermal overload protection, and lift capacity up to 8.0 metres. IP 55 protection and F-Class insulation. Durable construction with brass impeller and stainless-steel inserts.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MINI CREST I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '6375', 'warrenty' => '1 Year']
        ]
    ],
    'CHAMP PLUS I' => [
        'features' => 'Affordable Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Features wide voltage design, 1-year warranty, thermal overload protection, IP 55 protection and F-Class insulation. Reliable self-priming performance for household and agricultural applications.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'CHAMP PLUS I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '6025', 'warrenty' => '1 Year']
        ]
    ],
    'MASTER PLUS I' => [
        'features' => 'Premium Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Features anti-jam winding technology, ADDS (drip-proof adaptor), wide voltage design, and self-priming regenerative design. High-quality construction with advanced electrical stamping and Hy-Flo Max technology.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MASTER PLUS I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '12350', 'warrenty' => '1 Year']
        ]
    ],
    'MASTER DURA I' => [
        'features' => 'Premium Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Premium model with anti-jam winding, thermal overload protection, wide voltage protection, high suction capacity, and drip-proof design. Black & Silver color variant. Advanced electrical stamping and Hy-Flo Max technology.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MASTER DURA I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '12700', 'warrenty' => '1 Year']
        ]
    ],
    'GLIDE PLUS II' => [
        'features' => 'Economical Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Features high suction capacity up to 7.0 metres, drip proof adaptor, and wide voltage application. Affordable option with reliable performance. Ideal for budget-conscious customers.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'GLIDE PLUS II', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '7.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '4525', 'warrenty' => '1 Year']
        ]
    ],
    'GLIDE PLUS I' => [
        'features' => 'Efficient Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Features high suction capacity with suction lift up to 7.0 metres, instant suction capability, and self-priming regenerative design. Reliable performance for household and agricultural applications.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'GLIDE PLUS I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '7.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '5850', 'warrenty' => '1 Year']
        ]
    ],
    'FLOMAX PLUS I' => [
        'features' => 'Premium Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Features 1-year warranty, anti-rust technology, anti-jam winding, wide voltage design, advanced electrical stamping and Hy-Flo Max technology. High-quality brass impeller and stainless-steel inserts. Weight: 16.22 kg.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'FLOMAX PLUS I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '12900', 'warrenty' => '1 Year']
        ]
    ],
    'CHAMP DURA I' => [
        'features' => 'Durable Mini Self Priming Regenerative Pump 1 HP (0.75 kW). Premium DURA series model with anti-jam winding, thermal overload protection, wide voltage protection, high suction capacity, and drip-proof design. Black & Silver color variant. Advanced construction and Hy-Flo Max technology.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'CHAMP DURA I', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '6375', 'warrenty' => '1 Year']
        ]
    ],
];

// Updated products (0.5 HP variants)
$updatedProducts = [
    'MINI MASTER II' => [
        'features' => 'Compact Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features wide voltage design, thermal overload protection, IP 55 protection and F-Class insulation. Lift capacity up to 8.0 metres. Perfect for smaller installations and low-flow applications.',
        'kwhp' => '0.5',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '20',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MINI MASTER II', 'powerKw' => '0.375', 'powerHp' => '0.5', 'supplyPhaseD' => '1PH', 'pipePhase' => '20', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '500-600 LPM', 'mrp' => '7500', 'warrenty' => '1 Year']
        ]
    ],
    'CHAMP PLUS II' => [
        'features' => 'Affordable Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features wide voltage design, thermal overload protection, IP 55 protection and F-Class insulation. Reliable performance for small applications. Ideal for budget-conscious customers.',
        'kwhp' => '0.5',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '20',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'CHAMP PLUS II', 'powerKw' => '0.375', 'powerHp' => '0.5', 'supplyPhaseD' => '1PH', 'pipePhase' => '20', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '500-600 LPM', 'mrp' => '4650', 'warrenty' => '1 Year']
        ]
    ],
    'MINI MASTERPLUS II' => [
        'features' => 'Premium Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features 40% faster filling capability with excellent performance. IP 55 protection and F-Class insulation. Lift capacity up to 8.0 metres. Advanced electrical stamping and Hy-Flo Max technology.',
        'kwhp' => '0.5',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '20',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MINI MASTERPLUS II', 'powerKw' => '0.375', 'powerHp' => '0.5', 'supplyPhaseD' => '1PH', 'pipePhase' => '20', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '500-600 LPM', 'mrp' => '8375', 'warrenty' => '1 Year']
        ]
    ],
    'MINI MARVEL II' => [
        'features' => 'Compact Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features wide voltage design, thermal overload protection, IP 55 protection and F-Class insulation. Lift capacity up to 8.0 metres. Lightweight and easy to install for small applications.',
        'kwhp' => '0.5',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '20',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MINI MARVEL II', 'powerKw' => '0.375', 'powerHp' => '0.5', 'supplyPhaseD' => '1PH', 'pipePhase' => '20', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '500-600 LPM', 'mrp' => '6375', 'warrenty' => '1 Year']
        ]
    ],
    'MINI CREST II' => [
        'features' => 'Reliable Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features wide voltage design, 1-year warranty, thermal overload protection, and lift capacity up to 8.0 metres. IP 55 protection and F-Class insulation. Durable construction for small installations.',
        'kwhp' => '0.5',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '20',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MINI CREST II', 'powerKw' => '0.375', 'powerHp' => '0.5', 'supplyPhaseD' => '1PH', 'pipePhase' => '20', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '500-600 LPM', 'mrp' => '4950', 'warrenty' => '1 Year']
        ]
    ],
    'AQUAGOLD 50-30' => [
        'features' => 'Economical Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Affordable AQUAGOLD series pump with reliable performance. Features include IP 55 protection, F-Class insulation, and lift capacity up to 7.0 metres. Ideal for budget-conscious customers.',
        'kwhp' => '0.5',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '20',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'AQUAGOLD 50-30', 'powerKw' => '0.375', 'powerHp' => '0.5', 'supplyPhaseD' => '1PH', 'pipePhase' => '20', 'noOfStageD' => 'Regenerative', 'headRange' => '7.0m', 'dischargeRange' => '500-600 LPM', 'mrp' => '7750', 'warrenty' => '1 Year']
        ]
    ],
    'AQUAGOLD 100-33' => [
        'features' => 'Efficient Mini Self Priming Regenerative Pump 1.0 HP (0.75 kW). AQUAGOLD series pump with reliable self-priming design. Features include IP 55 protection, F-Class insulation, and lift capacity up to 8.0 metres. Good performance at affordable price.',
        'kwhp' => '1',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '25',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'AQUAGOLD 100-33', 'powerKw' => '0.75', 'powerHp' => '1.0', 'supplyPhaseD' => '1PH', 'pipePhase' => '25', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '800-1000 LPM', 'mrp' => '10175', 'warrenty' => '1 Year']
        ]
    ],
    'FLOMAX PLUS II' => [
        'features' => 'Compact Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features anti-rust technology, anti-jam winding, wide voltage design. Ideal for small applications. Advanced electrical stamping and Hy-Flo Max technology for reliable performance.',
        'kwhp' => '0.5',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '20',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'FLOMAX PLUS II', 'powerKw' => '0.375', 'powerHp' => '0.5', 'supplyPhaseD' => '1PH', 'pipePhase' => '20', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '500-600 LPM', 'mrp' => '7925', 'warrenty' => '1 Year']
        ]
    ],
    'MASTER DURA II' => [
        'features' => 'Premium Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). DURA series model with anti-jam winding, thermal overload protection, wide voltage protection, high suction capacity, and drip-proof design. Black & Silver color variant. Advanced construction.',
        'kwhp' => '0.5',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '20',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MASTER DURA II', 'powerKw' => '0.375', 'powerHp' => '0.5', 'supplyPhaseD' => '1PH', 'pipePhase' => '20', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '500-600 LPM', 'mrp' => '8350', 'warrenty' => '1 Year']
        ]
    ],
    'MASTER PLUS II' => [
        'features' => 'Compact Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features anti-jam winding technology, drip-proof adaptor, wide voltage design. Reliable self-priming regenerative design for small applications. High-quality construction.',
        'kwhp' => '0.5',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '20',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'MASTER PLUS II', 'powerKw' => '0.375', 'powerHp' => '0.5', 'supplyPhaseD' => '1PH', 'pipePhase' => '20', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '500-600 LPM', 'mrp' => '8050', 'warrenty' => '1 Year']
        ]
    ],
    'STAR PLUS II' => [
        'features' => 'Reliable Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). Features wide voltage design, thermal overload protection, IP 55 protection and F-Class insulation. Good performance for small applications at affordable price.',
        'kwhp' => '0.5',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '20',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'STAR PLUS II', 'powerKw' => '0.375', 'powerHp' => '0.5', 'supplyPhaseD' => '1PH', 'pipePhase' => '20', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '500-600 LPM', 'mrp' => '6700', 'warrenty' => '1 Year']
        ]
    ],
    'CHAMP DURA II' => [
        'features' => 'Durable Mini Self Priming Regenerative Pump 0.5 HP (0.375 kW). DURA series model with anti-jam winding, thermal overload protection, wide voltage protection, high suction capacity, and drip-proof design. Black & Silver color variant. Advanced construction.',
        'kwhp' => '0.5',
        'supplyPhase' => '1PH',
        'deliveryPipe' => '20',
        'noOfStage' => 'Regenerative',
        'isi' => 'Yes',
        'mnre' => 'N/A',
        'pumpType' => 'Mini Self-Priming',
        'specifications' => [
            ['categoryref' => 'CHAMP DURA II', 'powerKw' => '0.375', 'powerHp' => '0.5', 'supplyPhaseD' => '1PH', 'pipePhase' => '20', 'noOfStageD' => 'Regenerative', 'headRange' => '8.0m', 'dischargeRange' => '500-600 LPM', 'mrp' => '4950', 'warrenty' => '1 Year']
        ]
    ],
];

// Merge both arrays
$allProducts = array_merge($productDetails, $updatedProducts);

echo "STEP 1: Updating pumpFeatures and basic specifications...\n";
echo str_repeat("-", 80) . "\n";

$updated = 0;
$failed = 0;

foreach ($allProducts as $title => $data) {
    $stmt = $conn->prepare(
        "UPDATE mx_pump SET
            pumpFeatures = ?,
            kwhp = ?,
            supplyPhase = ?,
            deliveryPipe = ?,
            noOfStage = ?,
            isi = ?,
            mnre = ?,
            pumpType = ?
         WHERE pumpTitle = ? AND status = 1"
    );

    $stmt->bind_param(
        "sssssssss",
        $data['features'],
        $data['kwhp'],
        $data['supplyPhase'],
        $data['deliveryPipe'],
        $data['noOfStage'],
        $data['isi'],
        $data['mnre'],
        $data['pumpType'],
        $title
    );

    if ($stmt->execute()) {
        echo "[$title] ✓ Updated\n";
        $updated++;

        // Get pumpID for detail records
        $getIdStmt = $conn->prepare("SELECT pumpID FROM mx_pump WHERE pumpTitle = ? AND status = 1");
        $getIdStmt->bind_param("s", $title);
        $getIdStmt->execute();
        $result = $getIdStmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $pumpID = $row['pumpID'];

            // Delete existing details
            $conn->query("DELETE FROM mx_pump_detail WHERE pumpID = $pumpID");

            // Insert new specifications
            foreach ($data['specifications'] as $spec) {
                $insertStmt = $conn->prepare(
                    "INSERT INTO mx_pump_detail
                    (pumpID, categoryref, powerKw, powerHp, supplyPhaseD, pipePhase, noOfStageD, headRange, dischargeRange, mrp, warrenty)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $insertStmt->bind_param(
                    "issssssssss",
                    $pumpID,
                    $spec['categoryref'],
                    $spec['powerKw'],
                    $spec['powerHp'],
                    $spec['supplyPhaseD'],
                    $spec['pipePhase'],
                    $spec['noOfStageD'],
                    $spec['headRange'],
                    $spec['dischargeRange'],
                    $spec['mrp'],
                    $spec['warrenty']
                );

                $insertStmt->execute();
                $insertStmt->close();
            }
        }
        $getIdStmt->close();
    } else {
        echo "[$title] ✗ Failed\n";
        $failed++;
    }

    $stmt->close();
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY:\n";
echo "Updated: $updated products\n";
echo "Failed: $failed\n";
echo "Total: " . count($allProducts) . "\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 80) . "\n\n";

$conn->close();

?>
