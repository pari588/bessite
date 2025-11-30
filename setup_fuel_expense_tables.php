<?php
/**
 * Setup script for Fuel Expense Module tables
 * This creates the database tables needed for the fuel expenses tracking system
 */

require_once('config.inc.php');

// Create direct database connection
$mysqli = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

echo "Creating Fuel Expense Module Tables...\n";
echo "=====================================\n\n";

// Table 1: mx_vehicle
$sql_vehicle = "CREATE TABLE IF NOT EXISTS `mx_vehicle` (
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

if ($mysqli->query($sql_vehicle)) {
    echo "[✓] mx_vehicle table created/exists\n";
} else {
    echo "[✗] ERROR creating mx_vehicle: " . $mysqli->error . "\n";
    exit(1);
}

// Table 2: mx_fuel_expense
$sql_fuel_expense = "CREATE TABLE IF NOT EXISTS `mx_fuel_expense` (
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

if ($mysqli->query($sql_fuel_expense)) {
    echo "[✓] mx_fuel_expense table created/exists\n";
} else {
    echo "[✗] ERROR creating mx_fuel_expense: " . $mysqli->error . "\n";
    exit(1);
}

// Verify tables exist
echo "\nVerifying tables...\n";

$result = $mysqli->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DBNAME . "' AND TABLE_NAME IN ('mx_vehicle', 'mx_fuel_expense')");

$found_tables = array();
while ($row = $result->fetch_assoc()) {
    $found_tables[] = $row['TABLE_NAME'];
}

echo "Tables found: " . implode(', ', $found_tables) . "\n";

if (count($found_tables) === 2) {
    echo "\n✓ SUCCESS: All tables created successfully!\n";
} else {
    echo "\n✗ ERROR: Not all tables were created\n";
    exit(1);
}

$mysqli->close();
?>
