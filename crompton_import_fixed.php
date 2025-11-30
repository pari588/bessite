<?php
/**
 * Crompton Residential Pumps - Complete Import with Categories (FIXED)
 */

$conn = new mysqli('localhost', 'bombayengg', 'oCFCrCMwKyy5jzg', 'bombayengg');

if ($conn->connect_error) {
    die('Database Connection Failed: ' . $conn->connect_error);
}

$results = array(
    'categories_created' => 0,
    'products_added' => 0,
    'errors' => array()
);

// Get or create main "Residential Pumps" category
$result = $conn->query("SELECT categoryPID FROM mx_pump_category WHERE categoryTitle='Residential Pumps' AND parentID=0 LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $residential_pumps_id = $row['categoryPID'];
} else {
    $conn->query("INSERT INTO mx_pump_category (categoryTitle, seoUri, parentID, status)
                  VALUES ('Residential Pumps', 'residential-pumps', 0, 1)");
    $residential_pumps_id = $conn->insert_id;
    $results['categories_created']++;
}

// Define pump sub-categories
$pump_categories = array(
    array('Mini Pumps', 'mini-pumps'),
    array('DMB-CMB Pumps', 'dmb-cmb-pumps'),
    array('Shallow Well Pumps', 'shallow-well-pumps'),
    array('3-Inch Borewell Submersibles', '3-inch-borewell-submersibles'),
    array('4-Inch Borewell Submersibles', '4-inch-borewell-submersibles'),
    array('Residential Openwell Pumps', 'residential-openwell-pumps'),
    array('Pressure Booster Pumps', 'pressure-booster-pumps'),
    array('Control Panels', 'control-panels'),
);

$category_ids = array();

foreach ($pump_categories as $cat) {
    $cat_title = $conn->real_escape_string($cat[0]);
    $cat_seouri = $conn->real_escape_string($cat[1]);

    $result = $conn->query("SELECT categoryPID FROM mx_pump_category WHERE categoryTitle='$cat_title' AND parentID=$residential_pumps_id LIMIT 1");

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $category_ids[$cat[0]] = $row['categoryPID'];
    } else {
        $conn->query("INSERT INTO mx_pump_category (categoryTitle, seoUri, parentID, status)
                      VALUES ('$cat_title', '$cat_seouri', $residential_pumps_id, 1)");
        $category_ids[$cat[0]] = $conn->insert_id;
        $results['categories_created']++;
    }
}

// Define all 27 Crompton products - with shortened values to fit VARCHAR(10) columns
$all_products = array(
    'Mini Pumps' => array(
        array('Mini Everest Mini Pump', 'Compact pump for gardening', '1.1kW', 'SP', '25x25', '', 'BIS', '', 'mini-everest-mini-pump'),
        array('AQUAGOLD DURA 150', 'Durable aquagold pump', '1.5HP/1.1k', 'SP', '', '', '', '', 'aquagold-dura-150'),
        array('AQUAGOLD 150', 'Standard aquagold pump', '1.5HP/1.1k', 'SP', '', '', '', '', 'aquagold-150'),
        array('WIN PLUS I', 'Window pump series', '1.0HP/0.75', 'SP', '25x25', '', '', '', 'win-plus-i'),
        array('ULTIMO II', 'Entry-level pump', '1.0HP/0.75', 'SP', '', '', '', '', 'ultimo-ii'),
        array('ULTIMO I', 'Basic pump model', '1.0HP/0.75', 'SP', '', '', '', '', 'ultimo-i'),
        array('STAR PLUS I', 'Star series pump', '1.0HP/0.75', 'SP', '', '', '', '', 'star-plus-i'),
        array('STAR DURA I', 'Durable star series', '1.0HP/0.75', 'SP', '', '', '', '', 'star-dura-i'),
        array('PRIMO I', 'Premium pump', '1.0HP/0.75', 'SP', '', '', '', '', 'primo-i'),
    ),
    'DMB-CMB Pumps' => array(
        array('CMB10NV PLUS', 'Monoblock pump 0.5 HP', '0.5HP/0.37', 'SP', '', '', 'BIS', '', 'cmb10nv-plus'),
        array('DMB10D PLUS', 'Monoblock pump 1.0 HP', '1.0HP/0.75', 'SP', '', '', 'BIS', '', 'dmb10d-plus'),
        array('DMB10DCSL', 'Monoblock pump 1440RPM', '1.0HP/0.75', 'SP', '', '', 'BIS', '', 'dmb10dcsl'),
        array('CMB05NV PLUS', 'Monoblock with impeller', '0.5HP/0.37', 'SP', '', '', 'BIS', '', 'cmb05nv-plus'),
    ),
    'Shallow Well Pumps' => array(
        array('SWJ1', 'Shallow well jet pump', '1.0HP/0.75', 'SP', '', '', '', '', 'swj1'),
        array('SWJ100AT-36 PLUS', 'Shallow jet + tank', '1.0HP/0.75', 'SP', '', '', '', '', 'swj100at-36-plus'),
        array('SWJ50AT-30 PLUS', 'Shallow well pump', '0.5HP/0.37', 'SP', '', '', '', '', 'swj50at-30-plus'),
    ),
    '3-Inch Borewell Submersibles' => array(
        array('3W12AP1D', '3-inch submersible', '1.0HP/0.75', 'SP', '3 inch', '', '', '', '3w12ap1d'),
        array('3W10AP1D', '3-inch submersible', '1.0HP/0.75', 'SP', '3 inch', '', '', '', '3w10ap1d'),
        array('3W10AK1A', '3-inch submersible', '1.0HP/0.75', 'SP', '3 inch', '', '', '', '3w10ak1a'),
    ),
    '4-Inch Borewell Submersibles' => array(
        array('4W7BU1AU', '4-inch submersible', '1.0HP/0.75', 'SP', '4 inch', '7', '', '', '4w7bu1au'),
        array('4W14BU2EU', '4-inch multi-stage', '2.0HP/1.5', 'SP', '4 inch', '14', '', '', '4w14bu2eu'),
        array('4W10BU1AU', '4-inch submersible', '1.0HP/0.75', 'SP', '4 inch', '10', '', '', '4w10bu1au'),
    ),
    'Residential Openwell Pumps' => array(
        array('OWE12(1PH)Z-28', 'Openwell pump', '1.0HP/0.75', 'SP', '', '', '', '', 'owe121phz-28'),
        array('OWE052(1PH)Z-21FS', 'Openwell pump', '0.5HP/0.37', 'SP', '', '', '', '', 'owe0521phz-21fs'),
    ),
    'Pressure Booster Pumps' => array(
        array('Mini Force I', 'Booster pump', '0.5HP/0.37', 'SP', '', '', '', '', 'mini-force-i'),
        array('CFMSMB5D1.00-V24', 'Booster pump', '1.0HP/0.75', 'SP', '', '1', '', '', 'cfmsmb5d1.00-v24'),
    ),
    'Control Panels' => array(
        array('ARMOR1.5-DSU', 'Control panel', '1.5HP/1.1', 'SP', '', '', '', '', 'armor1.5-dsu'),
        array('ARMOR1.0-CQU', 'Control panel', '1.0HP/0.75', 'SP', '', '', '', '', 'armor1.0-cqu'),
    ),
);

// Insert products by category
foreach ($all_products as $category_name => $products) {
    if (!isset($category_ids[$category_name])) {
        $results['errors'][] = "Category '$category_name' not found";
        continue;
    }

    $cat_id = $category_ids[$category_name];

    foreach ($products as $prod) {
        // Escape strings
        $title = $conn->real_escape_string($prod[0]);
        $desc = $conn->real_escape_string($prod[1]);
        $kwhp = $conn->real_escape_string(substr($prod[2], 0, 10));
        $phase = $conn->real_escape_string(substr($prod[3], 0, 10));
        $pipe = $conn->real_escape_string(substr($prod[4], 0, 10));
        $stages = $conn->real_escape_string(substr($prod[5], 0, 10));
        $isi = $conn->real_escape_string(substr($prod[6], 0, 10));
        $mnre = $conn->real_escape_string(substr($prod[7], 0, 10));
        $seouri = $conn->real_escape_string($prod[8]);

        $sql = "INSERT INTO mx_pump (pumpTitle, categoryPID, pumpFeatures, kwhp, supplyPhase, deliveryPipe, noOfStage, isi, mnre, pumpType, seoUri, status)
                VALUES ('$title', $cat_id, '$desc', '$kwhp', '$phase', '$pipe', '$stages', '$isi', '$mnre', 'Residential', '$seouri', 1)";

        if ($conn->query($sql)) {
            $results['products_added']++;
        } else {
            $results['errors'][] = "$title: " . $conn->error;
        }
    }
}

$conn->close();

// Output results
echo "\n";
echo "=====================================\n";
echo "CROMPTON RESIDENTIAL PUMPS IMPORTED\n";
echo "=====================================\n\n";
echo "✓ Categories Created: " . $results['categories_created'] . "/8\n";
echo "✓ Products Added: " . $results['products_added'] . "/27\n";

if (count($results['errors']) > 0) {
    echo "\n✗ Errors (" . count($results['errors']) . "):\n";
    foreach (array_slice($results['errors'], 0, 5) as $err) {
        echo "  - $err\n";
    }
    if (count($results['errors']) > 5) {
        echo "  ... and " . (count($results['errors']) - 5) . " more errors\n";
    }
} else {
    echo "\n✓ ALL PRODUCTS IMPORTED SUCCESSFULLY!\n";
    echo "✓ Available in xadmin pump management\n";
    echo "✓ Visible on xsite frontend\n";
}

echo "\n=====================================\n\n";

