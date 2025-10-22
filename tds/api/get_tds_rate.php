<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';

header('Content-Type: application/json');
$sec = strtoupper(trim($_GET['section'] ?? $_POST['section'] ?? ''));
$date = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');
if(!$sec){ echo json_encode(['ok'=>false,'msg'=>'Missing section']); exit; }

$stmt = $pdo->prepare('SELECT rate FROM tds_rates WHERE section_code=? AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?) ORDER BY effective_from DESC LIMIT 1');
$stmt->execute([$sec, $date, $date]);
$row = $stmt->fetch();
if(!$row){ echo json_encode(['ok'=>false,'msg'=>'Rate not found']); exit; }
echo json_encode(['ok'=>true,'rate'=>(float)$row['rate']]);
