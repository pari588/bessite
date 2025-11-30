<?php
/**
 * Import CG Global Motor Specifications to Database
 */

// Database credentials
$DBHOST = 'localhost';
$DBNAME = 'bombayengg';
$DBUSER = 'bombayengg';
$DBPASS = 'oCFCrCMwKyy5jzg';

// Connect to database
$mysqli = new mysqli($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

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

$motorCreated = 0;
$specInserted = 0;
$skipped = 0;

// Process each product
foreach ($lines as $lineNum => $line) {
    $line = trim($line);
    if (empty($line)) continue;

    $data = array_map('trim', explode("\t", $line));

    // Handle mismatched column count
    if (count($data) !== count($header)) {
        // Pad or trim the data to match header count
        $data = array_slice(array_pad($data, count($header), ''), 0, count($header));
    }

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
    $stmt = $mysqli->prepare("SELECT motorID FROM mx_motor WHERE motorTitle = ? LIMIT 1");
    $stmt->bind_param("s", $productName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row_data = $result->fetch_assoc();
        $motorID = $row_data['motorID'];
        echo "  ✓ Motor exists (ID: $motorID)\n";
    } else {
        // Create new motor
        $seoUri = strtolower(preg_replace('/[^a-z0-9]+/', '-', $productName));
        $descShort = $description ? substr($description, 0, 200) : $productName;

        $stmt = $mysqli->prepare("INSERT INTO mx_motor (motorTitle, motorDesc, seoUri, categoryMID, status)
                                VALUES (?, ?, ?, 1, 1)");
        $stmt->bind_param("sss", $productName, $descShort, $seoUri);

        if ($stmt->execute()) {
            $motorID = $mysqli->insert_id;
            $motorCreated++;
            echo "  ✓ Motor created (ID: $motorID)\n";
        } else {
            echo "  ✗ Failed to create motor: " . $mysqli->error . "\n";
            $skipped++;
            continue;
        }
    }

    // Check if specs already exist
    $stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM mx_motor_specification WHERE motorID = ?");
    $stmt->bind_param("i", $motorID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row_data = $result->fetch_assoc();
    $existingSpecs = $row_data['cnt'] ?? 0;

    if ($existingSpecs > 0) {
        echo "  ℹ Specifications already exist ($existingSpecs)\n\n";
        // Skip if specs exist
        // continue;
    }

    // Add specifications
    // 1. Output Power
    if (!empty($outputPower)) {
        $stmt = $mysqli->prepare("INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specPoles, specFrequency, specStandard, status)
                                VALUES (?, 'Output Power', ?, '', '', '', '', '', 1)");
        $stmt->bind_param("is", $motorID, $outputPower);
        if ($stmt->execute()) {
            $specInserted++;
            echo "  ✓ Added: Output Power\n";
        }
    }

    // 2. Voltage
    if (!empty($voltages)) {
        $stmt = $mysqli->prepare("INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specPoles, specFrequency, specStandard, status)
                                VALUES (?, 'Voltage', '', ?, '', '', '', '', 1)");
        $stmt->bind_param("is", $motorID, $voltages);
        if ($stmt->execute()) {
            $specInserted++;
            echo "  ✓ Added: Voltage\n";
        }
    }

    // 3. Frame Size
    if (!empty($frameSize)) {
        $stmt = $mysqli->prepare("INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specPoles, specFrequency, specStandard, status)
                                VALUES (?, 'Frame Size', '', '', ?, '', '', '', 1)");
        $stmt->bind_param("is", $motorID, $frameSize);
        if ($stmt->execute()) {
            $specInserted++;
            echo "  ✓ Added: Frame Size\n";
        }
    }

    // 4. Standards
    if (!empty($standards)) {
        $stmt = $mysqli->prepare("INSERT INTO mx_motor_specification (motorID, specTitle, specOutput, specVoltage, specFrameSize, specPoles, specFrequency, specStandard, status)
                                VALUES (?, 'Standards', '', '', '', '', '', ?, 1)");
        $stmt->bind_param("is", $motorID, $standards);
        if ($stmt->execute()) {
            $specInserted++;
            echo "  ✓ Added: Standards\n";
        }
    }

    echo "\n";
}

echo "\n================================================================================\n";
echo "IMPORT COMPLETE\n";
echo "================================================================================\n";
echo "Motors Created: " . $motorCreated . "\n";
echo "Specifications Added: " . $specInserted . "\n";
echo "Skipped: " . $skipped . "\n";
echo "================================================================================\n";

$mysqli->close();

?>
