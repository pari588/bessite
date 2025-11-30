<?php
$conn = new mysqli('localhost', 'bombayengg', 'oCFCrCMwKyy5jzg', 'bombayengg');

echo "DEBUG: CATEGORY CLICK FLOW\n";
echo "==========================\n\n";

// Simulate what happens when user clicks "Mini Pumps"
$category_seouri = 'mini-pumps';

// Step 1: Find the category by seoUri
echo "Step 1: Find category by seoUri='$category_seouri'\n";
$result = $conn->query("SELECT categoryPID, categoryTitle, parentID FROM mx_pump_category WHERE seoUri='$category_seouri'");
if ($result->num_rows > 0) {
    $cat = $result->fetch_assoc();
    echo "  ✓ Found: {$cat['categoryTitle']} (ID: {$cat['categoryPID']}, Parent: {$cat['parentID']})\n";
    $categoryID = $cat['categoryPID'];
} else {
    echo "  ✗ Not found!\n";
    exit;
}

// Step 2: Check what getSideNav() would do
echo "\nStep 2: getSideNav() logic\n";
echo "  categoryID = $categoryID\n";
echo "  topCat would be determined from URL\n";
echo "  ARRCAT would be set based on topCat\n";

// Step 3: Check if products exist in this category
echo "\nStep 3: Check products in category $categoryID\n";
$result = $conn->query("SELECT pumpID, pumpTitle FROM mx_pump WHERE categoryPID=$categoryID AND status=1");
echo "  Found: " . $result->num_rows . " products\n";
if ($result->num_rows > 0) {
    echo "  Products:\n";
    while ($row = $result->fetch_assoc()) {
        echo "    - {$row['pumpTitle']}\n";
    }
} else {
    echo "  ✗ NO PRODUCTS FOUND!\n";
}

// Step 4: Test the SQL query that getPumpProducts() uses
echo "\nStep 4: Test getPumpProducts() SQL query\n";
$ARRCAT = array($categoryID);
$inWhere = implode(",", array_fill(0, count($ARRCAT), "?"));
echo "  ARRCAT = [" . implode(", ", $ARRCAT) . "]\n";
echo "  inWhere = $inWhere\n";

$types = "i" . implode("", array_fill(0, count($ARRCAT), "i"));
echo "  types = $types\n";

$vals = $ARRCAT;
array_unshift($vals, 1);
echo "  vals = [" . implode(", ", $vals) . "]\n";

echo "\n  Test SQL Query:\n";
$sql = "SELECT P.pumpID, P.pumpTitle FROM mx_pump AS P WHERE P.status=? AND P.categoryPID IN($inWhere)";
echo "  $sql\n";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$vals);
$stmt->execute();
$result = $stmt->get_result();
echo "\n  Result: " . $result->num_rows . " rows\n";
if ($result->num_rows > 0) {
    echo "  ✓ Products found!\n";
} else {
    echo "  ✗ No products found!\n";
}

$conn->close();
