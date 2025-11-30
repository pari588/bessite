<?php
require_once('./core/core.inc.php');
global $DB;

// Count pumps
$DB->vals = array(1);
$DB->types = 'i';
$DB->sql = "SELECT COUNT(*) as cnt FROM " . $DB->pre . "pump WHERE status=?";
$result = $DB->dbRow();

echo "Total active pumps: " . $result['cnt'] . "\n";

// Get Residential Pumps category
$DB->vals = array('Residential Pumps');
$DB->types = 's';
$DB->sql = "SELECT categoryPID FROM " . $DB->pre . "pump_category WHERE categoryTitle=?";
$cat = $DB->dbRow();

if ($cat) {
    $cat_id = $cat['categoryPID'];
    echo "Residential Pumps category found (ID: $cat_id)\n\n";

    // Get products in this category
    $DB->vals = array($cat_id);
    $DB->types = 'i';
    $DB->sql = "SELECT pumpID, pumpTitle, kwhp FROM " . $DB->pre . "pump WHERE categoryPID=? ORDER BY pumpID DESC LIMIT 10";
    $products = $DB->dbRows();

    echo "Recent products in Residential Pumps category:\n";
    foreach ($products as $p) {
        echo "  - " . $p['pumpTitle'] . " (" . $p['kwhp'] . ")\n";
    }
} else {
    echo "Residential Pumps category not found\n";
}
?>
