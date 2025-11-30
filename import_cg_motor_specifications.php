<?php
/**
 * Import CG Global Motor Specifications to Database
 * Reads TSV file and inserts into motor_specification table
 */

require_once 'xsite/core-site/tpl.inc.php';

// Connect to database
$DB = new DBMysql();
if (!$DB->dbConnection()) {
    die("Database connection failed!\n");
}

echo "================================================================================\n";
echo "CG GLOBAL MOTOR SPECIFICATIONS IMPORT\n";
echo "================================================================================\n\n";

$tsvFile = '/home/bombayengg/public_html/CG_HV_LV_MOTOR_SPECIFICATIONS.tsv';

if (!file_exists($tsvFile)) {
    die("TSV file not found: $tsvFile\n");
}

// Read TSV file
$lines = file($tsvFile, FILE_SKIP_EMPTY_LINES);
$header = array_map('trim', explode("\t", array_shift($lines)));

echo "Header columns: " . implode(", ", $header) . "\n\n";

$insertCount = 0;
$updateCount = 0;
$skipCount = 0;

// Process each product
foreach ($lines as $lineNum => $line) {
    $line = trim($line);
    if (empty($line)) continue;

    $data = array_map('trim', explode("\t", $line));
    $row = array_combine($header, $data);

    $productName = $row['Product Name'] ?? '';
    $description = $row['Description'] ?? '';
    $outputPower = $row['Output Power'] ?? '';
    $voltages = $row['Voltages'] ?? '';
    $frameSize = $row['Frame Size'] ?? '';
    $standards = $row['Standards'] ?? '';
    $category = $row['Category'] ?? '';

    if (empty($productName)) {
        $skipCount++;
        continue;
    }

    echo "Processing: $productName\n";
    echo "  Description: " . substr($description, 0, 80) . "...\n";

    // Check if motor exists
    $DB->vals = array($productName, 1);
    $DB->types = "si";
    $DB->sql = "SELECT motorID FROM `mx_motor` WHERE motorTitle=? AND status=? LIMIT 1";
    $motorResult = $DB->dbRows();

    if ($motorResult && count($motorResult) > 0) {
        $motorID = $motorResult[0]['motorID'];
        echo "  ✓ Found motor ID: $motorID\n";

        // Check if specifications already exist
        $DB->vals = array($motorID, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT COUNT(*) as cnt FROM `mx_motor_specification` WHERE motorID=? AND status=?";
        $specCheck = $DB->dbRows();
        $existingSpecs = $specCheck[0]['cnt'] ?? 0;

        if ($existingSpecs > 0) {
            echo "  ℹ Specifications already exist ($existingSpecs records)\n";
            $skipCount++;
            continue;
        }

        // Extract multiple specifications from description and fields
        // Parse output power ranges (e.g., "0.37 kW to 710 kW" or "0.18kw to 11kw")
        $specs = array();

        // Parse output power
        if (!empty($outputPower)) {
            $specs[] = array(
                'title' => 'Output Power',
                'output' => $outputPower,
                'voltage' => $voltages,
                'frameSize' => $frameSize,
                'standard' => $standards
            );
        }

        // If no output power specified, extract from description
        if (empty($outputPower) && !empty($description)) {
            // Common patterns in descriptions
            if (preg_match('/(\d+[\s]?(?:kW|Hp|HP))\s*(?:to|-)?\s*(\d+[\s]?(?:kW|Hp|HP))?/i', $description, $matches)) {
                $specs[] = array(
                    'title' => 'Output Power',
                    'output' => $matches[0],
                    'voltage' => $voltages,
                    'frameSize' => $frameSize,
                    'standard' => $standards
                );
            } else {
                // Default entry with description content
                $specs[] = array(
                    'title' => 'Specifications',
                    'output' => substr($description, 0, 100),
                    'voltage' => $voltages,
                    'frameSize' => $frameSize,
                    'standard' => $standards
                );
            }
        }

        // Insert specifications
        foreach ($specs as $spec) {
            $DB->vals = array(
                $motorID,
                $spec['title'],
                $spec['output'],
                $spec['voltage'],
                $spec['frameSize'],
                '',  // specPoles
                '',  // specFrequency
                $spec['standard'],
                1    // status
            );
            $DB->types = "issssssi";
            $DB->sql = "INSERT INTO `mx_motor_specification`
                        (motorID, specTitle, specOutput, specVoltage, specFrameSize, specPoles, specFrequency, specStandard, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            if ($DB->dbInsert()) {
                $insertCount++;
                echo "  ✓ Added specification: {$spec['title']}\n";
            } else {
                echo "  ✗ Failed to insert specification\n";
            }
        }

    } else {
        echo "  ✗ Motor not found in database\n";
        echo "    Please create motor first: $productName\n";
        $skipCount++;
    }

    echo "\n";
}

echo "\n================================================================================\n";
echo "IMPORT COMPLETE\n";
echo "================================================================================\n";
echo "Total Records Processed: " . (count($lines)) . "\n";
echo "Specifications Added: " . $insertCount . "\n";
echo "Skipped/Not Found: " . $skipCount . "\n";
echo "================================================================================\n";

?>
