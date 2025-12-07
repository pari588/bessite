<?php
header('Content-Type: application/json');

try {
  require_once __DIR__.'/../lib/auth.php'; auth_require();
  require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php'; require_once __DIR__.'/../lib/ajax_helpers.php';

  $bsr = trim($_POST['bsr'] ?? '');
  $date= trim($_POST['date'] ?? '');
  $serial = trim($_POST['serial'] ?? '');
  $amount = (float)($_POST['amount'] ?? 0);

  if($bsr==='' || $date==='' || $serial==='' || $amount<=0){
    json_err('Missing or invalid fields');
  }

  // Get firm ID from session
  $firm_id = $_SESSION['active_firm_id'] ?? 1;
  if (!$firm_id) {
    json_err('No firm selected');
  }

  // Verify firm exists
  $checkFirm = $pdo->prepare('SELECT id FROM firms WHERE id = ?');
  $checkFirm->execute([$firm_id]);
  if (!$checkFirm->fetch()) {
    json_err('Selected firm does not exist');
  }

  list($fy,$q) = fy_quarter_from_date($date);

  // amount_total = amount_tds + amount_interest + amount_fee (user provides only TDS, others are 0)
  $amount_total = $amount;  // For manual entry, total = TDS amount

  $ins = $pdo->prepare('INSERT INTO challans (firm_id, bsr_code, challan_date, challan_serial_no, amount_tds, amount_total, amount_interest, amount_fee, fy, quarter) VALUES (?,?,?,?,?,?,?,?,?,?)');
  $ins->execute([$firm_id, $bsr, $date, $serial, $amount, $amount_total, 0, 0, $fy, $q]);
  $id = $pdo->lastInsertId();

  json_ok(['id'=>$id,'row'=>[
    'id'=>$id,'bsr_code'=>$bsr,'challan_date'=>$date,'challan_serial_no'=>$serial,
    'amount_tds'=>$amount,'fy'=>$fy,'quarter'=>$q
  ]]);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'msg' => 'Error: ' . $e->getMessage()
  ]);
  exit;
}
