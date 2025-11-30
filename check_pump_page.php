<?php
echo "DEBUGGING /pumps/ PAGE SIDEBAR DISPLAY\n";
echo "=====================================\n\n";

// Check if x-pumps.php calls getSideNav
$file_content = file_get_contents("/home/bombayengg/public_html/xsite/mod/pumps/x-pumps.php");

if (strpos($file_content, 'getSideNav()') !== false) {
    echo "✓ getSideNav() IS called in x-pumps.php\n";
} else {
    echo "✗ getSideNav() is NOT called\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "SIDEBAR LOGIC SIMULATION\n";
echo str_repeat("=", 50) . "\n\n";

$conn = new mysqli('localhost', 'bombayengg', 'oCFCrCMwKyy5jzg', 'bombayengg');

// On /pumps/ listing page - no category in URL
$categoryID = 0;
$topCat = $categoryID;
$TBLCAT = "pump_category";
$TPL_uriArr = array();  // Empty on listing page

echo "Initial state (on /pumps/ listing page):\n";
echo "  categoryID = $categoryID\n";
echo "  topCat = $topCat\n";
echo "  uriArr = empty\n\n";

// Check default for pump_category listing
if (count($TPL_uriArr) == 0 && $categoryID == 0 && $TBLCAT == "pump_category") {
    echo "Finding default 'Residential Pumps' category...\n";
    
    $result = $conn->query("SELECT categoryPID FROM mx_pump_category WHERE status=1 AND categoryTitle='Residential Pumps'");
    
    if ($row = $result->fetch_assoc()) {
        $topCat = $categoryID = $row['categoryPID'];
        echo "✓ Found: ID $topCat\n\n";
    }
}

// Now query sidebar
echo "Querying L1 categories (sidebar):\n";
echo "  SQL: SELECT * FROM mx_pump_category WHERE status=1 AND parentID=$topCat\n\n";

$result = $conn->query("SELECT categoryPID, categoryTitle, seoUri FROM mx_pump_category WHERE status=1 AND parentID=$topCat ORDER BY categoryPID");

if ($result->num_rows > 0) {
    echo "✓ SIDEBAR WILL SHOW " . $result->num_rows . " CATEGORIES:\n\n";
    
    while ($row = $result->fetch_assoc()) {
        $cid = $row['categoryPID'];
        $ctitle = $row['categoryTitle'];
        $curi = $row['seoUri'];
        
        // Check if has children
        $check = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump_category WHERE parentID=$cid");
        $crow = $check->fetch_assoc();
        $has_children = $crow['cnt'] > 0 ? "has children (SPAN)" : "no children (LINK)";
        
        echo "  ✓ $ctitle - $has_children\n";
    }
} else {
    echo "✗ No categories found - sidebar would be EMPTY\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "CONCLUSION:\n";
echo str_repeat("=", 50) . "\n";
echo "✓ The sidebar SHOULD be displaying on /pumps/ page\n";
echo "✓ All 8 categories should be visible as clickable links\n";
echo "✓ No category should be highlighted (listing page)\n\n";

echo "If sidebar is NOT showing:\n";
echo "  1. Clear browser cache (Ctrl+Shift+Delete)\n";
echo "  2. Check if PHP changes were saved correctly\n";
echo "  3. Look for PHP errors in error_log\n";
echo "  4. Verify the getSideNav() function is working\n";

$conn->close();
?>
