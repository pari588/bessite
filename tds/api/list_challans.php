<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
header('Content-Type: application/json');

// Get firm ID from session
$firm_id = $_SESSION['active_firm_id'] ?? 1;

$stmt = $pdo->prepare('SELECT * FROM challans WHERE firm_id=? ORDER BY id DESC LIMIT 50');
$stmt->execute([$firm_id]);
$rows = $stmt->fetchAll();
echo json_encode(['ok'=>true,'rows'=>$rows]);
