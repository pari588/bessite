<?php
/**
 * Web OCR Upload Simulation
 * Simulates exactly what happens when a PDF is uploaded via the web form
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== WEB OCR UPLOAD SIMULATION ===\n\n";

// Include the actual handlers as the web form would
require_once("/home/bombayengg/public_html/core/ocr.inc.php");

// Get a test PDF to simulate upload
$sourcePDF = "/home/bombayengg/public_html/uploads/fuel-expense/new-kampala-s-station-5239-1.PDF";
$uploadDir = "/home/bombayengg/public_html/uploads/fuel-expense";

// Simulate the file upload as done in x-fuel-expense.inc.php
echo "Simulating file upload...\n";
echo "Source: $sourcePDF\n";
echo "Upload Dir: $uploadDir\n\n";

// Generate unique filename like the web form does
$fileExt = strtolower(pathinfo($sourcePDF, PATHINFO_EXTENSION));
$filename = "bill_" . time() . "_" . uniqid() . "." . $fileExt;
$uploadPath = $uploadDir . "/" . $filename;

echo "Generated filename: $filename\n";
echo "Upload path: $uploadPath\n\n";

// Copy the file (simulating move_uploaded_file)
echo "Copying file... ";
if (copy($sourcePDF, $uploadPath)) {
    echo "✓ Done\n";
    echo "File size: " . filesize($uploadPath) . " bytes\n";
    echo "File readable: " . (is_readable($uploadPath) ? "YES" : "NO") . "\n\n";
} else {
    echo "✗ Failed\n";
    exit(1);
}

// Now call processBillOCR exactly as the web form does
echo "Calling processBillOCR(\$uploadPath)...\n";
echo "─────────────────────────────────────────\n";

$startTime = microtime(true);
$ocrResult = processBillOCR($uploadPath);
$duration = microtime(true) - $startTime;

echo "Duration: " . number_format($duration, 3) . "s\n\n";

echo "=== RESULT ===\n";
echo "Status: " . $ocrResult["status"] . "\n";
echo "Message: " . $ocrResult["message"] . "\n";
echo "Date: " . $ocrResult["extractedData"]["date"] . "\n";
echo "Amount: " . $ocrResult["extractedData"]["amount"] . "\n";
echo "Confidence: " . $ocrResult["overallConfidence"] . "%\n";

if ($ocrResult["status"] !== "success") {
    echo "\n=== DEBUG INFO ===\n";
    if (!empty($ocrResult["debug"])) {
        echo "Command: " . $ocrResult["debug"]["command"] . "\n";
        echo "Input File: " . $ocrResult["debug"]["inputFile"] . "\n";
        echo "File Exists: " . ($ocrResult["debug"]["fileExists"] ? "YES" : "NO") . "\n";
        echo "Output: " . $ocrResult["debug"]["output"] . "\n";
    }
}

// Cleanup
echo "\n=== CLEANUP ===\n";
if (file_exists($uploadPath)) {
    if (unlink($uploadPath)) {
        echo "✓ Deleted: $uploadPath\n";
    } else {
        echo "✗ Failed to delete: $uploadPath\n";
    }
} else {
    echo "File doesn't exist: $uploadPath\n";
}

echo "\n=== END ===\n";

?>
