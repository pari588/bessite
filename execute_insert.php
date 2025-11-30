<?php
require_once('./core/core.inc.php');

global $DB;

// SQL statements to execute
$sql_statements = array(
    "INSERT IGNORE INTO mx_pump_category (categoryTitle, seoUri, parentID, status, addDate) VALUES ('Residential Pumps', 'residential-pumps', 0, 1, NOW())",
    "INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status, addDate) SELECT 'Mini Everest Mini Pump', (SELECT MAX(categoryPID) FROM mx_pump_category WHERE categoryTitle='Residential Pumps'), 'Compact pump for gardening, lawn sprinkling', '1.1kW', 'Single Phase', '25mm x 25mm', '', 'B.I.S. Compliant', '', 'Mini Pump', 'mini-everest-mini-pump', 1, NOW() FROM mx_pump_category LIMIT 1",
    "INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status, addDate) SELECT 'AQUAGOLD DURA 150', (SELECT MAX(categoryPID) FROM mx_pump_category WHERE categoryTitle='Residential Pumps'), 'Durable aquagold pump for household use', '1.5HP / 1.1kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'aquagold-dura-150', 1, NOW() FROM mx_pump_category LIMIT 1",
    "INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status, addDate) SELECT 'AQUAGOLD 150', (SELECT MAX(categoryPID) FROM mx_pump_category WHERE categoryTitle='Residential Pumps'), 'Standard aquagold pump', '1.5HP / 1.1kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'aquagold-150', 1, NOW() FROM mx_pump_category LIMIT 1",
    "INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status, addDate) SELECT 'WIN PLUS I', (SELECT MAX(categoryPID) FROM mx_pump_category WHERE categoryTitle='Residential Pumps'), 'Window pump series', '1.0HP / 0.75kW', 'Single Phase', '25mm x 25mm', '', '', '', 'Mini Pump', 'win-plus-i', 1, NOW() FROM mx_pump_category LIMIT 1",
    "INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status, addDate) SELECT 'ULTIMO II', (SELECT MAX(categoryPID) FROM mx_pump_category WHERE categoryTitle='Residential Pumps'), 'Entry-level pump', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'ultimo-ii', 1, NOW() FROM mx_pump_category LIMIT 1",
    "INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status, addDate) SELECT 'ULTIMO I', (SELECT MAX(categoryPID) FROM mx_pump_category WHERE categoryTitle='Residential Pumps'), 'Basic pump model', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'ultimo-i', 1, NOW() FROM mx_pump_category LIMIT 1",
    "INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status, addDate) SELECT 'STAR PLUS I', (SELECT MAX(categoryPID) FROM mx_pump_category WHERE categoryTitle='Residential Pumps'), 'Star series pump', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'star-plus-i', 1, NOW() FROM mx_pump_category LIMIT 1",
    "INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status, addDate) SELECT 'STAR DURA I', (SELECT MAX(categoryPID) FROM mx_pump_category WHERE categoryTitle='Residential Pumps'), 'Durable star series', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'star-dura-i', 1, NOW() FROM mx_pump_category LIMIT 1",
    "INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status, addDate) SELECT 'PRIMO I', (SELECT MAX(categoryPID) FROM mx_pump_category WHERE categoryTitle='Residential Pumps'), 'Premium pump', '1.0HP / 0.75kW', 'Single Phase', '', '', '', '', 'Mini Pump', 'primo-i', 1, NOW() FROM mx_pump_category LIMIT 1",
);

$executed = 0;

foreach ($sql_statements as $sql) {
    $DB->sql = $sql;
    if ($DB->dbQuery()) {
        $executed++;
    }
}

echo "Executed $executed statements out of " . count($sql_statements);
?>
