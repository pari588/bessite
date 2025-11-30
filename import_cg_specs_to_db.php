<?php
/**
 * Import CG Global Motor Specifications directly to Database
 * Creates motors and adds specifications
 */

require_once '/home/bombayengg/public_html/core/common.inc.php';

$DB = new DBMysql();
$DB->dbConnection();

echo "================================================================================\n";
echo "CG GLOBAL MOTOR SPECIFICATIONS - DATABASE IMPORT\n";
echo "================================================================================\n\n";

$tsvFile = '/home/bombayengg/public_html/CG_HV_LV_MOTOR_SPECIFICATIONS.tsv';

if (!file_exists($tsvFile)) {
    die("TSV file not found: $tsvFile\n");
}

// Read TSV file
$lines = file($tsvFile, FILE_SKIP_EMPTY_LINES);
$header = array_map('trim', explode("\t", array_shift($lines)));

$motorInserted = 0;
$specInserted = 0;
$skipped = 0;

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
        $skipped++;
        continue;
    }

    echo "Processing: $productName\n";

    // Check if motor already exists
    $DB->vals = array($productName);
    $DB->types = "s";
    $DB->sql = "SELECT motorID FROM `mx_motor` WHERE motorTitle=? LIMIT 1";
    $motorResult = $DB->dbRows();

    if ($motorResult && count($motorResult) > 0) {
        $motorID = $motorResult[0]['motorID'];
        echo "  ✓ Motor exists (ID: $motorID)\n";
    } else {
        // Create new motor
        $seoUri = strtolower(preg_replace('/[^a-z0-9]+/', '-', $productName));

        $DB->vals = array(
            $productName,
            $description ? substr($description, 0, 200) : $productName,
            '',  // motorSubTitle
            '',  // motorImage
            $seoUri,
            '',  // motorDesc
            1,   // categoryMID (default)
            1    // status
        );
        $DB->types = "ssssssii";
        $DB->sql = "INSERT INTO `mx_motor`
                    (motorTitle, motorDesc, motorSubTitle, motorImage, seoUri, motorDesc, categoryMID, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        if ($DB->dbInsert()) {
            $motorID = $DB->lastID;
            $motorInserted++;
            echo "  ✓ Motor created (ID: $motorID)\n";
        } else {
            echo "  ✗ Failed to create motor\n";
            $skipped++;
            continue;
        }
    }

    // Check if specs already exist
    $DB->vals = array($motorID);
    $DB->types = "i";
    $DB->sql = "SELECT COUNT(*) as cnt FROM `mx_motor_specification` WHERE motorID=?";
    $specCheck = $DB->dbRows();
    $existingSpecs = ($specCheck && isset($specCheck[0]['cnt'])) ? $specCheck[0]['cnt'] : 0;

    if ($existingSpecs > 0) {
        echo "  ℹ Specifications already exist ($existingSpecs)\n\n";
        continue;
    }

    // Add specifications
    // Parse output power if available
    if (!empty($outputPower)) {
        $DB->vals = array(
            $motorID,
            'Output Power',
            $outputPower,
            $voltages,
            $frameSize,
            '',
            '',
            $standards,
            1
        );
        $DB->types = "issssssi";
        $DB->sql = "INSERT INTO `mx_motor_specification`
                    (motorID, specTitle, specOutput, specVoltage, specFrameSize, specPoles, specFrequency, specStandard, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($DB->dbInsert()) {
            $specInserted++;
            echo "  ✓ Specification added: Output Power\n";
        }
    }

    // Add voltage specification if available and different
    if (!empty($voltages) && $voltages !== $outputPower) {
        $DB->vals = array(
            $motorID,
            'Voltage',
            '',
            $voltages,
            '',
            '',
            '',
            '',
            1
        );
        $DB->types = "issssssi";
        $DB->sql = "INSERT INTO `mx_motor_specification`
                    (motorID, specTitle, specOutput, specVoltage, specFrameSize, specPoles, specFrequency, specStandard, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($DB->dbInsert()) {
            $specInserted++;
            echo "  ✓ Specification added: Voltage\n";
        }
    }

    // Add frame size specification if available
    if (!empty($frameSize)) {
        $DB->vals = array(
            $motorID,
            'Frame Size',
            '',
            '',
            $frameSize,
            '',
            '',
            '',
            1
        );
        $DB->types = "issssssi";
        $DB->sql = "INSERT INTO `mx_motor_specification`
                    (motorID, specTitle, specOutput, specVoltage, specFrameSize, specPoles, specFrequency, specStandard, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($DB->dbInsert()) {
            $specInserted++;
            echo "  ✓ Specification added: Frame Size\n";
        }
    }

    // Add standards specification if available
    if (!empty($standards)) {
        $DB->vals = array(
            $motorID,
            'Standards',
            '',
            '',
            '',
            '',
            '',
            $standards,
            1
        );
        $DB->types = "issssssi";
        $DB->sql = "INSERT INTO `mx_motor_specification`
                    (motorID, specTitle, specOutput, specVoltage, specFrameSize, specPoles, specFrequency, specStandard, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($DB->dbInsert()) {
            $specInserted++;
            echo "  ✓ Specification added: Standards\n";
        }
    }

    echo "\n";
}

echo "\n================================================================================\n";
echo "IMPORT COMPLETE\n";
echo "================================================================================\n";
echo "Motors Created: " . $motorInserted . "\n";
echo "Specifications Added: " . $specInserted . "\n";
echo "Skipped: " . $skipped . "\n";
echo "================================================================================\n";

?>
