<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Manually defining DB creds to ensure no config issues
$DBHOST = 'localhost';
$DBNAME = 'bombayengg';
$DBUSER = 'bombayengg';
$DBPASS = 'oCFCrCMwKyy5jzg';

// Minimal DB class
class SimpleDb {
    public $con;
    public function __construct($h, $u, $p, $n) {
        $this->con = new mysqli($h, $u, $p, $n);
        if ($this->con->connect_error) die("Connect failed: " . $this->con->connect_error);
    }
    public function query($sql) {
        return $this->con->query($sql);
    }
}

$db = new SimpleDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);

echo "--- Checking Role Access for Role 1 (Admin) ---
";
$res = $db->query("SELECT A.accessType, M.seoUri, M.menuTitle FROM mx_x_admin_role_access A JOIN mx_x_admin_menu M ON A.adminMenuID = M.adminMenuID WHERE A.roleID = 1 AND M.seoUri LIKE '%fuel%'");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "Module: " . $row['menuTitle'] . " (" . $row['seoUri'] . ")\n";
        echo "Access: " . $row['accessType'] . "\n\n";
    }
} else {
    echo "Query failed.\n";
}
?>
