<?php
// Extract detailed FHP specifications and create structured data
// This will extract: Description Title, Output, Voltage, Frame Size, Standard
// Multiple entries per product where applicable

include '/home/bombayengg/public_html/config.inc.php';

$specifications = [
    'Single Phase Motors' => [
        'product_titles' => [
            'Capacitor Start Motors',
            'Capacitor Run Motors',
            'Permanent Split Capacitor Motors',
            'Split Phase Motors'
        ],
        'file' => '/tmp/fhp_single_phase.html',
        'categoryID' => 102  // Single Phase Motors category
    ],
    '3 Phase Motors - Rolled Steel Body' => [
        'product_titles' => [
            '3 Phase Rolled Steel Standard',
            '3 Phase Rolled Steel Heavy Duty',
            '3 Phase Rolled Steel Premium',
            '3 Phase Rolled Steel Explosion Proof'
        ],
        'file' => '/tmp/fhp_3phase.html',
        'categoryID' => 103  // 3 Phase Motors category
    ],
    'Application Specific Motors' => [
        'product_titles' => [
            'Huller Motors',
            'Cooler Motors',
            'Flange Motors',
            'Textile Motors',
            'Agricultural Motors'
        ],
        'file' => '/tmp/fhp_application.html',
        'categoryID' => 104  // Application Specific category
    ]
];

echo "====== FHP COMMERCIAL MOTORS SPECIFICATIONS EXTRACTION ======\n\n";

// Extract specs from each category file
$allSpecs = [];

foreach ($specifications as $categoryName => $categoryData) {
    echo "Processing: $categoryName\n";
    echo "File: {$categoryData['file']}\n";

    if (!file_exists($categoryData['file'])) {
        echo "ERROR: File not found\n\n";
        continue;
    }

    $html = file_get_contents($categoryData['file']);

    // Extract all product names and related content
    // Look for product-specific sections

    // Find all divs that might contain product information
    preg_match_all('/<div[^>]*class="[^"]*product[^"]*"[^>]*>(.*?)<\/div>/is', $html, $productDivs);

    // If that doesn't work, try finding sections with product titles
    if (empty($productDivs[0])) {
        preg_match_all('/<h[3-4][^>]*>([^<]+)<\/h[3-4]>/i', $html, $headings);
    }

    // Extract table data
    preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $html, $tables);

    echo "Found " . count($tables[0]) . " tables\n";

    if (!empty($tables[0])) {
        foreach ($tables[0] as $tableIdx => $table) {
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $table, $rows);

            foreach ($rows[1] as $rowIdx => $row) {
                preg_match_all('/<t[dh][^>]*>([^<]*)<\/t[dh]>/i', $row, $cells);

                if (!empty($cells[1])) {
                    $cellData = array_map('trim', $cells[1]);
                    // Filter out empty cells
                    $cellData = array_filter($cellData, function($cell) {
                        return !empty($cell);
                    });

                    if (!empty($cellData)) {
                        echo "  Table $tableIdx, Row $rowIdx: " . implode(" | ", $cellData) . "\n";
                    }
                }
            }
        }
    }

    // Extract text content looking for specifications
    echo "\n  Extracting specification patterns...\n";

    // Look for output specifications (W, kW, HP)
    preg_match_all('/(\d+(?:\s*-\s*\d+)?)\s*(W|kW|HP|hp)\s+(\d+\/\d+)?/', $html, $outputMatches);
    if (!empty($outputMatches[0])) {
        echo "    Output values found: " . count(array_unique($outputMatches[0])) . "\n";
        foreach (array_unique($outputMatches[0]) as $output) {
            echo "      - " . trim($output) . "\n";
        }
    }

    // Look for voltage specifications
    preg_match_all('/(Single\s+Phase|3\s+Phase|Three\s+Phase|230V|415V|110V|120V|220V|230\/415V)/i', $html, $voltageMatches);
    if (!empty($voltageMatches[0])) {
        echo "    Voltage values found: " . count(array_unique($voltageMatches[0])) . "\n";
        foreach (array_unique($voltageMatches[0]) as $voltage) {
            echo "      - " . trim($voltage) . "\n";
        }
    }

    // Look for frame sizes (IEC and NEMA)
    preg_match_all('/(IEC\s+\d+|NEMA\s+\d+|Frame\s+[\w\d\-]+|\d{2,3}\s+mm)/i', $html, $frameMatches);
    if (!empty($frameMatches[0])) {
        echo "    Frame sizes found: " . count(array_unique($frameMatches[0])) . "\n";
        foreach (array_unique($frameMatches[0]) as $frame) {
            echo "      - " . trim($frame) . "\n";
        }
    }

    // Look for standards/certifications
    preg_match_all('/(ATEX|BIS|CCOE|CMRI|IEC|NEMA|IS|IS\s+\d+|\d{4}:\d{4})/i', $html, $standardMatches);
    if (!empty($standardMatches[0])) {
        echo "    Standards found: " . count(array_unique($standardMatches[0])) . "\n";
        foreach (array_unique($standardMatches[0]) as $standard) {
            echo "      - " . trim($standard) . "\n";
        }
    }

    // Look for pole/phase specifications
    preg_match_all('/(\d+)\s+(?:Pole|Phase)s?/i', $html, $poleMatches);
    if (!empty($poleMatches[0])) {
        echo "    Pole/Phase specs found: " . count(array_unique($poleMatches[0])) . "\n";
        foreach (array_unique($poleMatches[0]) as $pole) {
            echo "      - " . trim($pole) . "\n";
        }
    }

    echo "\n";
}

echo "\n====== SPECIFICATIONS READY FOR DATABASE INSERTION ======\n";
echo "Next step: Map these specifications to product records and insert into database\n";
?>
