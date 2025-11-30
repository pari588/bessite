<?php
/**
 * Generate CSV Export with Product Name, Category, Sub-Category, and Description
 */

$DBHOST = 'localhost';
$DBNAME = 'bombayengg';
$DBUSER = 'bombayengg';
$DBPASS = 'oCFCrCMwKyy5jzg';

$conn = mysqli_connect($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// Get all pump products with their categories
$query = "
SELECT 
    p.pumpTitle as product_name,
    p.pumpFeatures as description,
    p.categoryPID,
    c1.categoryTitle as main_category,
    c2.categoryTitle as sub_category,
    c1.categoryPID as main_cat_id,
    c2.categoryPID as sub_cat_id
FROM mx_pump p
LEFT JOIN mx_pump_category c2 ON p.categoryPID = c2.categoryPID
LEFT JOIN mx_pump_category c1 ON c2.parentID = c1.categoryPID
WHERE p.status = 1
ORDER BY c1.categoryTitle, c2.categoryTitle, p.pumpTitle
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Create CSV content
$csv_content = "Product Name,Category,Sub-Category,Description\n";

$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $product_name = $row['product_name'] ?: '';
    $main_category = $row['main_category'] ?: '';
    $sub_category = $row['sub_category'] ?: '';
    $description = $row['description'] ?: '';
    
    // Remove any special characters and line breaks from description
    $description = str_replace(["\n", "\r", "\t", '"'], [' ', ' ', ' ', "'"], $description);
    // Trim extra spaces
    $description = preg_replace('/\s+/', ' ', $description);
    $description = trim($description);
    
    // Properly escape CSV fields
    $csv_line = '"' . str_replace('"', '""', $product_name) . '",' .
                '"' . str_replace('"', '""', $main_category) . '",' .
                '"' . str_replace('"', '""', $sub_category) . '",' .
                '"' . str_replace('"', '""', $description) . '"' . "\n";
    
    $csv_content .= $csv_line;
    $count++;
}

// Save to xsite folder
$csv_file = '/home/bombayengg/public_html/xsite/1414.csv';
file_put_contents($csv_file, $csv_content);

echo "CSV Export Complete!\n";
echo "=====================================\n";
echo "File saved: xsite/1414.csv\n";
echo "Total records exported: $count\n";
echo "File size: " . filesize($csv_file) . " bytes\n";

mysqli_close($conn);
?>
