<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
header('Content-Type: application/json');
$rows = $pdo->query('SELECT * FROM challans ORDER BY id DESC LIMIT 50')->fetchAll();
echo json_encode(['ok'=>true,'rows'=>$rows]);
