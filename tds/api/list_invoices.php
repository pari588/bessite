<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
header('Content-Type: application/json');

// Get firm ID from session
$firm_id = $_SESSION['active_firm_id'] ?? 1;

$stmt = $pdo->prepare('SELECT i.*, v.name vname FROM invoices i JOIN vendors v ON v.id=i.vendor_id WHERE i.firm_id=? ORDER BY i.id DESC LIMIT 50');
$stmt->execute([$firm_id]);
$rows = $stmt->fetchAll();
echo json_encode(['ok'=>true,'rows'=>$rows]);
