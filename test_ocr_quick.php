<?php
require_once("/home/bombayengg/public_html/core/ocr.inc.php");

$sourcePDF = "/home/bombayengg/public_html/uploads/fuel-expense/new-kampala-s-station-5239-1.PDF";
$uploadDir = "/home/bombayengg/public_html/uploads/fuel-expense";

$filename = "bill_" . time() . "_" . uniqid() . ".PDF";
$uploadPath = $uploadDir . "/" . $filename;

copy($sourcePDF, $uploadPath);
echo "Testing with: $uploadPath\n";

$result = processBillOCR($uploadPath);
echo "Status: " . $result["status"] . "\n";
echo "Date: " . $result["extractedData"]["date"] . "\n";
echo "Amount: " . $result["extractedData"]["amount"] . "\n";

@unlink($uploadPath);
?>
