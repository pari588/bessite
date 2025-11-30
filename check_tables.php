<?php
require_once("config.inc.php");
require_once("core/core.inc.php");

echo "<pre>";
$DB->sql = "SHOW TABLES LIKE '%pump_inquiry%'";
$DB->dbRows();

echo "Tables with 'pump_inquiry' in name:\n";
foreach ($DB->rows as $row) {
    $tableName = array_values($row)[0];
    echo "  - $tableName\n";
}

echo "\n\nDatabase prefix: " . $DB->pre . "\n";
echo "</pre>";
?>
