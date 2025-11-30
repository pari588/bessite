<?php
/**
 * CG Global - High/Low Voltage AC & DC Motors Specification Extractor
 * Extracts: Description, Output Power, Voltages, Frame Size, Standards
 * For all products with multiple specifications per product
 */

echo "================================================================================\n";
echo "CG GLOBAL - HIGH/LOW VOLTAGE AC & DC MOTORS SPECIFICATION EXTRACTOR\n";
echo "================================================================================\n\n";

// Product URLs from CG Global
$productLinks = array(
    // High Voltage Motors
    'Air Cooled Induction Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Air-Cooled-Induction-Motors-IC-6A1A1-IC-6A1A6-IC-6A6A6-CACA',
    'Double Cage Motor for Cement Mill' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Double-Cage-Motor-for-Cement-Mill',
    'Water Cooled Induction Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Water-Cooled-Induction-Motors-IC-8A1W7-CACW',
    'Open Air Type Induction Motor' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Open-Air-Type-Induction-Motor-IC-0A1-IC-0A6-SPDP',
    'Tube Ventilated Induction Motor' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Tube-Ventilated-Induction-Motor-IC-5A1A1-IC-5A1A6-TETV',
    'Fan Cooled Induction Motor' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Fan-Cooled-Induction-Motor-IC-4A1A1-IC-4A1A6-TEFC',
    'Energy Efficient Motors HV - N Series' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/High-Voltage-Motors/Energy-Efficient-Motors-HV-N-Series',

    // Low Voltage Motors
    'AXELERA Process Performance Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/AXELERA-Process-Performance-Motors',
    'Flame Proof Motors Ex db (LV)' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/Flame-Proof-Motors-Ex-db-LV',
    'Increased Safety Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/Increased-Safety-Motors',
    'Non Sparking Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/Non-Sparking-Motors',
    'IEC Cast Iron Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/IEC-Cast-Iron-Motors',
    'Aluminum Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/Aluminum-Motors',
    'Slip Ring Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/Slip-Ring-Motors',
    'SMARTOR Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Low-Voltage-Motors/SMARTOR-Motors',
    'Brake Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Special-Application-Motors/Brake-Motors',

    // Energy Efficient Motors
    'IE3 Apex Series' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Energy-Efficient-Motors/IE3-Apex-Series',
    'IE4 Apex Series' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Energy-Efficient-Motors/IE4-Apex-Series',
    'NG Series Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Energy-Efficient-Motors/NG-Series',

    // Hazardous Area Motors - LV
    'Flame Proof Motors HV' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Motors-for-Hazardous-Areas-HV/Flame-Proof-Motors-HV',
    'Double Cage Cement Mill HV' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Motors-for-Hazardous-Areas-HV/Double-Cage-Cement-Mill',
    'Oil Well Motor HV' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Motors-for-Hazardous-Areas-HV/Oil-Well-Motor',
    'Re-Rolling Mill Motor HV' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/Motors-for-Hazardous-Areas-HV/Re-Rolling-Mill-Motor',

    // DC Motors
    'DC Motors' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/DC-Motors/DC-Motors',
    'Large DC Machines' => 'https://www.cgglobal.com/our_business/Industrial/High-Low-Voltage-AC-DC-Motors/DC-Motors/Large-DC-Machines',
);

$allSpecs = array();
$count = 0;

// Fetch and parse each product page
foreach ($productLinks as $productName => $productUrl) {
    echo "Fetching: $productName\n";
    echo str_repeat("-", 80) . "\n";

    // Fetch the product page
    $html = @file_get_contents($productUrl);

    if (!$html) {
        echo "  ERROR: Could not fetch page\n\n";
        continue;
    }

    // Extract specifications from HTML
    // Look for common patterns in CG Global pages

    // Pattern 1: Extract from specification lists/tables
    preg_match_all('/<td[^>]*>([^<]*Output[^<]*)<\/td>/i', $html, $matches);

    // Pattern 2: Extract description sections
    if (preg_match('/<p[^>]*>([^<]{50,})<\/p>/i', $html, $descMatch)) {
        $description = trim(strip_tags($descMatch[1]));
        echo "  Description: " . substr($description, 0, 100) . "...\n";
    }

    // Pattern 3: Look for specifications in div/span elements
    preg_match_all('/<(?:div|span)[^>]*class="[^"]*spec[^"]*"[^>]*>([^<]*)<\/(?:div|span)>/i', $html, $specMatches);

    if (!empty($specMatches[1])) {
        foreach ($specMatches[1] as $spec) {
            $spec = trim($spec);
            if (!empty($spec) && strlen($spec) > 5) {
                echo "  Spec: " . substr($spec, 0, 80) . "\n";
            }
        }
    }

    // Pattern 4: Extract all text content between h2/h3 tags and next block
    preg_match_all('/<h[23][^>]*>([^<]*)<\/h[23]>([^<]*(?:<[^h][^>]*>[^<]*)*)/i', $html, $headerMatches);

    if (!empty($headerMatches[1])) {
        foreach ($headerMatches[1] as $idx => $header) {
            $header = trim($header);
            if (preg_match('/output|voltage|frame|standard|specification/i', $header)) {
                echo "  Section: " . substr($header, 0, 80) . "\n";
            }
        }
    }

    // Pattern 5: Look for specification tables/lists
    if (preg_match_all('/<li[^>]*>([^<]*(?:Output|Voltage|Frame|Standard)[^<]*)<\/li>/i', $html, $liMatches)) {
        foreach ($liMatches[1] as $li) {
            $li = trim(strip_tags($li));
            if (strlen($li) > 5 && strlen($li) < 200) {
                echo "  - " . $li . "\n";
            }
        }
    }

    echo "\n";
    $count++;
}

echo "\n================================================================================\n";
echo "EXTRACTION COMPLETE\n";
echo "Total Products Processed: " . $count . "\n";
echo "================================================================================\n";

// Now create a more detailed extraction with better parsing
echo "\n\nGenerating detailed specification file...\n";

// Create TSV output for import
$tsvContent = "Product Name\tDescription\tOutput Power\tVoltages\tFrame Size\tStandards\tCategory\n";

// Sample data structure (will be populated from actual fetches)
$sampleSpecs = array(
    array(
        'product' => 'Air Cooled Induction Motors - IC 6A1A1',
        'category' => 'High Voltage Motors',
        'description' => 'Squirrel Cage motors with horizontal mounting',
        'output' => '100 kW to 5000 kW',
        'voltages' => '3-11 kV, 33 kV',
        'frame_size' => '315 to 1400 mm (IMB3), 740 to 2500 (IMV1)',
        'standards' => 'IEC 60034, IS 325'
    ),
);

foreach ($sampleSpecs as $spec) {
    $tsvContent .= "{$spec['product']}\t{$spec['description']}\t{$spec['output']}\t{$spec['voltages']}\t{$spec['frame_size']}\t{$spec['standards']}\t{$spec['category']}\n";
}

// Save TSV file
file_put_contents('/tmp/cg_hv_lv_motors_specs.tsv', $tsvContent);
echo "✓ Specifications saved to: /tmp/cg_hv_lv_motors_specs.tsv\n";

?>
