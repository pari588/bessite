<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php';
header('Content-Type: application/json');
$stmt=$pdo->query('SELECT * FROM challans ORDER BY id DESC LIMIT 50');
echo json_encode($stmt->fetchAll());
