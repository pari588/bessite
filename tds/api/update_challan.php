<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php'; require_once __DIR__.'/../lib/ajax_helpers.php';

$id = (int)($_POST['id'] ?? 0);
$bsr = trim($_POST['bsr_code'] ?? '');
$date= trim($_POST['challan_date'] ?? '');
$serial = trim($_POST['challan_serial_no'] ?? '');
$amount = (float)($_POST['amount_tds'] ?? 0);
if($id<=0 || $bsr==='' || $date==='' || $serial==='' || $amount<=0){ json_err('Missing or invalid fields'); }

list($fy,$q) = fy_quarter_from_date($date);
$up = $pdo->prepare('UPDATE challans SET bsr_code=?, challan_date=?, challan_serial_no=?, amount_tds=?, fy=?, quarter=? WHERE id=?');
$up->execute([$bsr,$date,$serial,$amount,$fy,$q,$id]);

$st=$pdo->prepare('SELECT * FROM challans WHERE id=?'); $st->execute([$id]); $r=$st->fetch();
json_ok(['row'=>$r]);
