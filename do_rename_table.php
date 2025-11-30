<?php
require_once("config.inc.php");
require_once("core/core.inc.php");

echo "<h2>Renaming pump inquiry table</h2>";
echo "<pre>";

// Rename the table
$DB->sql = "RENAME TABLE `bombay_pump_inquiry` TO `" . $DB->pre . "pump_inquiry`";
echo "Executing: " . $DB->sql . "\n\n";

if ($DB->dbQuery()) {
    echo "✓ Table renamed successfully!\n\n";

    // Verify
    $DB->sql = "SHOW TABLES LIKE '" . $DB->pre . "pump_inquiry'";
    $DB->dbRows();

    if ($DB->numRows > 0) {
        echo "✓ Verified: Table " . $DB->pre . "pump_inquiry exists\n";
    }
} else {
    echo "✗ ERROR: " . $DB->con->error . "\n";
}

echo "</pre>";
?>
