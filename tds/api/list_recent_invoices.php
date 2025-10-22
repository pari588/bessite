<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php';
header('Content-Type: application/json');
$stmt=$pdo->query('SELECT i.*,v.name vname FROM invoices i JOIN vendors v ON v.id=i.vendor_id ORDER BY i.id DESC LIMIT 50');
echo json_encode($stmt->fetchAll());
