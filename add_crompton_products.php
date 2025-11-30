<?php
/**
 * Crompton Residential Pumps - Bulk Add Script
 * This script adds 27 Crompton residential pump products to the database
 * Created: 2025-11-05
 */

require_once('./core/core.inc.php');
require_once('./xadmin/inc/site.inc.php');

global $DB;

// Products array - all Crompton residential pumps
$crompton_products = array(
    // Mini Pumps (9 products)
    array('name' => 'Mini Everest Mini Pump', 'desc' => 'Compact pump for gardening, lawn sprinkling', 'hp' => '', 'kw' => '1.1', 'phase' => 'Single Phase', 'pipe' => '25mm x 25mm', 'stages' => '', 'isi' => 'B.I.S. Compliant', 'mnre' => ''),
    array('name' => 'AQUAGOLD DURA 150', 'desc' => 'Durable aquagold pump for household use', 'hp' => '1.5', 'kw' => '1.1', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'AQUAGOLD 150', 'desc' => 'Standard aquagold pump', 'hp' => '1.5', 'kw' => '1.1', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'WIN PLUS I', 'desc' => 'Window pump series', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '25mm x 25mm', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'ULTIMO II', 'desc' => 'Entry-level pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'ULTIMO I', 'desc' => 'Basic pump model', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'STAR PLUS I', 'desc' => 'Star series pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'STAR DURA I', 'desc' => 'Durable star series', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'PRIMO I', 'desc' => 'Premium pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),

    // DMB-CMB Pumps (4 products)
    array('name' => 'CMB10NV PLUS', 'desc' => 'Centrifugal monoblock pump, 0.5 HP', 'hp' => '0.5', 'kw' => '0.37', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => 'B.I.S. Compliant', 'mnre' => ''),
    array('name' => 'DMB10D PLUS', 'desc' => 'Centrifugal monoblock pump, 1.0 HP, max head 54m', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => 'B.I.S. Compliant', 'mnre' => ''),
    array('name' => 'DMB10DCSL', 'desc' => 'Centrifugal monoblock pump, 1440 RPM', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => 'B.I.S. Compliant', 'mnre' => ''),
    array('name' => 'CMB05NV PLUS', 'desc' => 'Centrifugal monoblock pump with brass impeller', 'hp' => '0.5', 'kw' => '0.37', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => 'B.I.S. Compliant', 'mnre' => ''),

    // Shallow Well Pumps (3 products)
    array('name' => 'SWJ1', 'desc' => 'Shallow well jet pump with 8m suction', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'SWJ100AT-36 PLUS', 'desc' => 'Shallow well jet pump with tank', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'SWJ50AT-30 PLUS', 'desc' => 'Shallow well pump with tank, 0.5 HP', 'hp' => '0.5', 'kw' => '0.37', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),

    // 3-Inch Borewell Submersibles (3 products)
    array('name' => '3W12AP1D', 'desc' => '3-inch water-filled submersible pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '3 inch (75mm)', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => '3W10AP1D', 'desc' => '3-inch water-filled submersible pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '3 inch (75mm)', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => '3W10AK1A', 'desc' => '3-inch water-filled submersible pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '3 inch (75mm)', 'stages' => '', 'isi' => '', 'mnre' => ''),

    // 4-Inch Borewell Submersibles (3 products)
    array('name' => '4W7BU1AU', 'desc' => '4-inch water-filled submersible, 7 stages', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '4 inch (100mm)', 'stages' => '7', 'isi' => '', 'mnre' => ''),
    array('name' => '4W14BU2EU', 'desc' => '4-inch water-filled submersible, 14 stages', 'hp' => '2.0', 'kw' => '1.5', 'phase' => 'Single Phase', 'pipe' => '4 inch (100mm)', 'stages' => '14', 'isi' => '', 'mnre' => ''),
    array('name' => '4W10BU1AU', 'desc' => '4-inch water-filled submersible, 10 stages', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '4 inch (100mm)', 'stages' => '10', 'isi' => '', 'mnre' => ''),

    // Residential Openwell Pumps (2 products)
    array('name' => 'OWE12(1PH)Z-28', 'desc' => 'Centrifugal openwell pump with anti-rust coating', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'OWE052(1PH)Z-21FS', 'desc' => 'Centrifugal openwell submersible pump', 'hp' => '0.5', 'kw' => '0.37', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),

    // Pressure Booster Pumps (2 products)
    array('name' => 'Mini Force I', 'desc' => 'Automatic pressure booster pump with dry run protection', 'hp' => '0.5', 'kw' => '0.37', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'CFMSMB5D1.00-V24', 'desc' => 'Centrifugal booster pump, single stage', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '1', 'isi' => '', 'mnre' => ''),

    // Control Panels (2 products)
    array('name' => 'ARMOR1.5-DSU', 'desc' => 'Control panel with settable OFF-timers, 1.5 HP', 'hp' => '1.5', 'kw' => '1.1', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
    array('name' => 'ARMOR1.0-CQU', 'desc' => 'Control panel compatible with submersible pumps, 1.0 HP', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => ''),
);

// Function to create SEO URI from title
function makeSeoUriFromTitle($title) {
    $uri = strtolower($title);
    $uri = preg_replace('/[^a-z0-9\s\-]/', '', $uri);
    $uri = preg_replace('/\s+/', '-', $uri);
    return trim($uri, '-');
}

// Get or create "Residential Pumps" category
$DB->vals = array('Residential Pumps');
$DB->types = 's';
$DB->sql = "SELECT categoryPID FROM " . $DB->pre . "pump_category WHERE categoryTitle=? LIMIT 1";
$cat_row = $DB->dbRow();

if ($cat_row) {
    $residential_cat_id = $cat_row['categoryPID'];
} else {
    // Create new category
    $data = array(
        'categoryTitle' => 'Residential Pumps',
        'seoUri' => 'residential-pumps',
        'parentID' => 0,
        'status' => 1,
        'addDate' => date('Y-m-d H:i:s'),
        'xOrder' => 0
    );

    $DB->table = $DB->pre . "pump_category";
    $DB->data = $data;
    if ($DB->dbInsert()) {
        $residential_cat_id = $DB->insertID;
    } else {
        die("Failed to create Residential Pumps category");
    }
}

// Insert all products
$added = 0;
$errors = array();

foreach ($crompton_products as $product) {
    // Build kwhp value
    $kwhp = '';
    if ($product['hp']) {
        $kwhp = $product['hp'] . 'HP';
        if ($product['kw']) {
            $kwhp .= ' / ' . $product['kw'] . 'kW';
        }
    } else if ($product['kw']) {
        $kwhp = $product['kw'] . 'kW';
    }

    // Prepare product data
    $data = array(
        'pumpTitle' => cleanTitle($product['name']),
        'categoryPID' => $residential_cat_id,
        'pumpFeatures' => cleanHtml($product['desc']),
        'kwhp' => cleanTitle($kwhp),
        'supplyPhase' => cleanTitle($product['phase']),
        'deliveryPipe' => cleanTitle($product['pipe']),
        'noOfStage' => cleanTitle($product['stages']),
        'isi' => cleanTitle($product['isi']),
        'mnre' => cleanTitle($product['mnre']),
        'pumpType' => cleanTitle('Residential Pump'),
        'seoUri' => makeSeoUriFromTitle($product['name']),
        'status' => 1,
        'addDate' => date('Y-m-d H:i:s')
    );

    // Insert product
    $DB->table = $DB->pre . "pump";
    $DB->data = $data;

    if ($DB->dbInsert()) {
        $added++;
    } else {
        $errors[] = $product['name'];
    }
}

// Output result
echo "<pre>";
echo "=============================================================\n";
echo "CROMPTON RESIDENTIAL PUMPS - DATABASE INSERT COMPLETE\n";
echo "=============================================================\n";
echo "Total Products Added: $added / " . count($crompton_products) . "\n";
echo "Category ID: $residential_cat_id (Residential Pumps)\n";

if (count($errors) > 0) {
    echo "\nFailed to add (" . count($errors) . "):\n";
    foreach ($errors as $err) {
        echo "  - $err\n";
    }
} else {
    echo "\n✓ All products added successfully!\n";
}

echo "=============================================================\n";
echo "</pre>";

// Delete this file after execution
unlink(__FILE__);
?>
