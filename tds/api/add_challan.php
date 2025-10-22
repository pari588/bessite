<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php'; require_once __DIR__.'/../lib/ajax_helpers.php';

$bsr = trim($_POST['bsr'] ?? '');
$date= trim($_POST['date'] ?? '');
$serial = trim($_POST['serial'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);

if($bsr==='' || $date==='' || $serial==='' || $amount<=0){ json_err('Missing or invalid fields'); }

list($fy,$q) = fy_quarter_from_date($date);
$ins = $pdo->prepare('INSERT INTO challans (bsr_code, challan_date, challan_serial_no, amount_tds, fy, quarter) VALUES (?,?,?,?,?,?)');
$ins->execute([$bsr,$date,$serial,$amount,$fy,$q]);
$id = $pdo->lastInsertId();

json_ok(['id'=>$id,'row'=>[
  'id'=>$id,'bsr_code'=>$bsr,'challan_date'=>$date,'challan_serial_no'=>$serial,
  'amount_tds'=>$amount,'fy'=>$fy,'quarter'=>$q
]]);
