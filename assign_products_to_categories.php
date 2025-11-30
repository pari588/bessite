<?php
$conn = new mysqli('localhost', 'bombayengg', 'oCFCrCMwKyy5jzg', 'bombayengg');

echo "ASSIGNING PRODUCTS TO CATEGORIES\n";
echo "=================================\n\n";

// Get category IDs
$categories = array();
$result = $conn->query("SELECT categoryPID, categoryTitle FROM mx_pump_category WHERE parentID=23 ORDER BY categoryPID");
while ($row = $result->fetch_assoc()) {
    $categories[$row['categoryTitle']] = $row['categoryPID'];
    echo "Found: {$row['categoryTitle']} (ID: {$row['categoryPID']})\n";
}

echo "\n";

// Define product-to-category mapping
$assignments = array(
    24 => array('Mini Pumps', array('Mini Everest Mini Pump', 'AQUAGOLD DURA 150', 'AQUAGOLD 150', 'WIN PLUS I', 'ULTIMO II', 'ULTIMO I', 'STAR PLUS I', 'STAR DURA I', 'PRIMO I')),
    25 => array('DMB-CMB Pumps', array('CMB10NV PLUS', 'DMB10D PLUS', 'DMB10DCSL', 'CMB05NV PLUS')),
    26 => array('Shallow Well Pumps', array('SWJ1', 'SWJ100AT-36 PLUS', 'SWJ50AT-30 PLUS')),
    27 => array('3-Inch Borewell', array('3W12AP1D', '3W10AP1D', '3W10AK1A')),
    28 => array('4-Inch Borewell', array('4W7BU1AU', '4W14BU2EU', '4W10BU1AU')),
    29 => array('Openwell Pumps', array('OWE12(1PH)Z-28', 'OWE052(1PH)Z-21FS')),
    30 => array('Booster Pumps', array('Mini Force I', 'CFMSMB5D1.00-V24')),
    31 => array('Control Panels', array('ARMOR1.5-DSU', 'ARMOR1.0-CQU')),
);

$total_updated = 0;

// Assign each product to its category
foreach ($assignments as $cat_id => $cat_data) {
    $cat_name = $cat_data[0];
    $products = $cat_data[1];
    
    echo "Assigning products to: $cat_name (ID: $cat_id)\n";
    
    foreach ($products as $product_name) {
        $escaped_name = $conn->real_escape_string($product_name);
        $sql = "UPDATE mx_pump SET categoryPID=$cat_id WHERE pumpTitle='$escaped_name'";
        if ($conn->query($sql)) {
            if ($conn->affected_rows > 0) {
                echo "  ✓ $product_name\n";
                $total_updated++;
            }
        }
    }
}

echo "\n=================================\n";
echo "✓ Total products assigned: $total_updated\n";
echo "=================================\n\n";

// Verify the assignment
echo "VERIFICATION:\n";
echo "=============\n";
$result = $conn->query("SELECT c.categoryTitle, COUNT(*) as cnt FROM mx_pump p 
                       JOIN mx_pump_category c ON c.categoryPID = p.categoryPID 
                       WHERE p.categoryPID IN (24,25,26,27,28,29,30,31) AND p.status=1 
                       GROUP BY c.categoryTitle ORDER BY c.categoryTitle");

while ($row = $result->fetch_assoc()) {
    echo "  {$row['categoryTitle']}: {$row['cnt']} products\n";
}

$result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE categoryPID IN (24,25,26,27,28,29,30,31) AND status=1");
$row = $result->fetch_assoc();
echo "\n✓ Total products in Residential categories: {$row['cnt']}\n";

$conn->close();
