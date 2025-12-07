<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config.inc.php';
require_once __DIR__ . '/core/db.inc.php';

$DB = new mxDb(DBHOST, DBUSER, DBPASS, DBNAME);

echo "--- Checking for Duplicate SEO URIs ---
";
$DB->sql = "SELECT seoUri, COUNT(*) as c FROM mx_x_admin_menu GROUP BY seoUri HAVING c > 1";
$DB->dbRows();
if ($DB->numRows > 0) {
    print_r($DB->rows);
} else {
    echo "No duplicate SEO URIs found.
";
}

echo "\n--- Checking Role Access for Role 1 (Admin) for Fuel Modules ---
";
// IDs: 64 (Mgmt), 65 (Vehicle), 66 (Expense), 67 (Report)
$DB->sql = "SELECT A.*, M.seoUri FROM mx_x_admin_role_access A JOIN mx_x_admin_menu M ON A.adminMenuID = M.adminMenuID WHERE A.roleID = 1 AND M.seoUri LIKE '%fuel%'";
$DB->dbRows();
foreach ($DB->rows as $row) {
    echo "Menu: " . $row['seoUri'] . " (ID: " . $row['adminMenuID'] . ")\n";
    echo "Access: " . $row['accessType'] . "\n";
    echo "----------------\n";
}

echo "\n--- Checking Role Access for Role SUPER (Virtual Check) ---
";
// SUPER gets all access for all modules where status=1
$DB->sql = "SELECT seoUri FROM mx_x_admin_menu WHERE status=1 AND seoUri LIKE '%fuel%'";
$DB->dbRows();
foreach ($DB->rows as $row) {
    echo "Menu: " . $row['seoUri'] . " => Should have ALL permissions\n";
}
?>
