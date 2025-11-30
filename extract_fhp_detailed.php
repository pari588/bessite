<?php
// Advanced FHP specifications extraction with proper parsing
// Extract: Description Title, Output, Voltage, Frame Size, Standard

$files = [
    'Single Phase Motors' => '/tmp/fhp_single_phase_new.html',
    '3 Phase Motors - Rolled Steel Body' => '/tmp/fhp_3phase_new.html',
    'Application Specific Motors' => '/tmp/fhp_application_new.html'
];

foreach ($files as $category => $filepath) {
    if (!file_exists($filepath)) {
        echo "File not found: $filepath\n";
        continue;
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "CATEGORY: $category\n";
    echo str_repeat("=", 60) . "\n\n";

    $html = file_get_contents($filepath);

    // Remove script and style tags
    $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

    // Look for product containers - usually divs with product class or article tags
    preg_match_all('/<(?:article|div)[^>]*class="[^"]*(?:product|motor|item)[^"]*"[^>]*>(.*?)<\/(?:article|div)>/is', $html, $productMatches);

    echo "Product containers found: " . count($productMatches[0]) . "\n";

    if (!empty($productMatches[0])) {
        foreach ($productMatches[0] as $idx => $product) {
            echo "\n--- PRODUCT " . ($idx + 1) . " ---\n";

            // Extract product title
            if (preg_match('/<h[2-4][^>]*>([^<]+)<\/h[2-4]>/i', $product, $titleMatch)) {
                echo "Title: " . trim($titleMatch[1]) . "\n";
            }

            // Extract description
            if (preg_match('/<p[^>]*>([^<]*(?:W|kW|HP|Voltage|Frame|Standard)[^<]*)<\/p>/is', $product, $descMatch)) {
                echo "Description: " . trim($descMatch[1]) . "\n";
            }

            // Extract all table data from this product
            preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $product, $tables);
            if (!empty($tables[0])) {
                echo "Tables in product: " . count($tables[0]) . "\n";
                foreach ($tables[0] as $tIdx => $table) {
                    echo "  Table " . ($tIdx + 1) . ":\n";
                    preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $table, $rows);
                    foreach ($rows[1] as $row) {
                        preg_match_all('/<t[dh][^>]*>([^<]*)<\/t[dh]>/i', $row, $cells);
                        if (!empty($cells[1])) {
                            $cellData = array_map('trim', array_filter($cells[1]));
                            if (!empty($cellData)) {
                                echo "    " . implode(" | ", $cellData) . "\n";
                            }
                        }
                    }
                }
            }

            // Extract specifications from text
            preg_match_all('/(\d+(?:\s*-\s*\d+)?)\s*(W|kW|HP)/i', $product, $outputs);
            if (!empty($outputs[0])) {
                echo "Outputs: " . implode(", ", array_unique($outputs[0])) . "\n";
            }

            preg_match_all('/(Single\s+Phase|3\s+Phase|Three\s+Phase|230V|415V|110V|120V|220V)/i', $product, $voltages);
            if (!empty($voltages[0])) {
                echo "Voltages: " . implode(", ", array_unique($voltages[0])) . "\n";
            }

            preg_match_all('/(IEC\s+\d+|NEMA\s+\d+|Frame\s+\d+)/i', $product, $frames);
            if (!empty($frames[0])) {
                echo "Frames: " . implode(", ", array_unique($frames[0])) . "\n";
            }

            preg_match_all('/(ATEX|BIS|CCOE|CMRI|IEC\s+\d+|IS\s+\d+)/i', $product, $standards);
            if (!empty($standards[0])) {
                echo "Standards: " . implode(", ", array_unique($standards[0])) . "\n";
            }
        }
    } else {
        echo "No product containers found, analyzing page structure...\n";

        // Alternative approach: look for all tables on page
        preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $html, $tables);
        echo "Tables on page: " . count($tables[0]) . "\n\n";

        if (!empty($tables[0])) {
            foreach ($tables[0] as $tIdx => $table) {
                echo "TABLE " . ($tIdx + 1) . ":\n";
                preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $table, $rows);
                echo "Rows: " . count($rows[0]) . "\n";

                foreach ($rows[1] as $rIdx => $row) {
                    preg_match_all('/<t[dh][^>]*>([^<]*)<\/t[dh]>/i', $row, $cells);
                    if (!empty($cells[1])) {
                        $cellData = array_map('trim', array_filter($cells[1]));
                        if (!empty($cellData)) {
                            echo "  Row " . ($rIdx + 1) . ": " . implode(" | ", $cellData) . "\n";
                        }
                    }
                }
                echo "\n";
            }
        }

        // Look for heading structures
        echo "\n--- Page Headings ---\n";
        preg_match_all('/<h[2-4][^>]*>([^<]+)<\/h[2-4]>/i', $html, $headings);
        foreach (array_slice(array_unique($headings[1]), 0, 15) as $heading) {
            echo "- " . trim($heading) . "\n";
        }
    }
}
?>
