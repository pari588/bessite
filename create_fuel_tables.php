<?php
require_once('config.inc.php');
$mysqli = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

$sql1 = "CREATE TABLE IF NOT EXISTS `mx_vehicle` (
  `vehicleID` int(11) NOT NULL AUTO_INCREMENT,
  `vehicleName` varchar(100) NOT NULL,
  `registrationNumber` varchar(50) DEFAULT NULL,
  `fuelType` enum('Petrol','Diesel','CNG') DEFAULT 'Petrol',
  `notes` text,
  `status` tinyint(1) DEFAULT 1,
  `createdDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedDate` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`vehicleID`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$sql2 = "CREATE TABLE IF NOT EXISTS `mx_fuel_expense` (
  `fuelExpenseID` int(11) NOT NULL AUTO_INCREMENT,
  `vehicleID` int(11) NOT NULL,
  `billDate` date NOT NULL,
  `expenseAmount` decimal(10,2) NOT NULL,
  `fuelQuantity` decimal(8,2) DEFAULT NULL,
  `billImage` varchar(255) DEFAULT NULL,
  `ocrText` longtext,
  `extractedData` json DEFAULT NULL,
  `confidenceScore` int(3) DEFAULT 0,
  `manuallyEdited` tinyint(1) DEFAULT 0,
  `paymentStatus` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `paidDate` date DEFAULT NULL,
  `remarks` text,
  `status` tinyint(1) DEFAULT 1,
  `createdDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedDate` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`fuelExpenseID`),
  KEY `vehicleID` (`vehicleID`),
  KEY `billDate` (`billDate`),
  KEY `paymentStatus` (`paymentStatus`),
  KEY `status` (`status`),
  CONSTRAINT `fk_fuel_expense_vehicle` FOREIGN KEY (`vehicleID`) REFERENCES `mx_vehicle` (`vehicleID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

echo "Creating tables...\n";
if ($mysqli->query($sql1)) {
    echo "[✓] mx_vehicle\n";
} else {
    echo "[✗] mx_vehicle error: " . $mysqli->error . "\n";
}

if ($mysqli->query($sql2)) {
    echo "[✓] mx_fuel_expense\n";
} else {
    echo "[✗] mx_fuel_expense error: " . $mysqli->error . "\n";
}

// Verify
$chk1 = $mysqli->query("SHOW TABLES LIKE 'mx_vehicle'");
$chk2 = $mysqli->query("SHOW TABLES LIKE 'mx_fuel_expense'");

echo "\nVerification:\n";
echo "mx_vehicle: " . ($chk1 && $chk1->num_rows > 0 ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "mx_fuel_expense: " . ($chk2 && $chk2->num_rows > 0 ? "✓ EXISTS" : "✗ MISSING") . "\n";

$mysqli->close();
echo "\nDone!\n";
?>
