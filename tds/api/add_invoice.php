<?php
header('Content-Type: application/json');

try {
  require_once __DIR__.'/../lib/auth.php'; auth_require();
  require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php'; require_once __DIR__.'/../lib/ajax_helpers.php';

  $vendor_name = trim($_POST['vendor_name'] ?? '');
  $vendor_pan  = strtoupper(trim($_POST['vendor_pan'] ?? ''));
  $invoice_no  = trim($_POST['invoice_no'] ?? '');
  $invoice_date= trim($_POST['invoice_date'] ?? '');
  $base_amount = (float)($_POST['base_amount'] ?? 0);
  $section     = strtoupper(trim($_POST['section_code'] ?? ''));
  $tds_rate    = isset($_POST['tds_rate']) && $_POST['tds_rate']!=='' ? (float)$_POST['tds_rate'] : null;

  if($vendor_name==='' || $vendor_pan==='' || $invoice_no==='' || $invoice_date==='' || $base_amount<=0 || $section===''){
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

  $vs = $pdo->prepare('SELECT id FROM vendors WHERE firm_id=? AND (pan=? OR name=?) ORDER BY id DESC LIMIT 1');
  $vs->execute([$firm_id,$vendor_pan,$vendor_name]);
  $vendor_id = $vs->fetchColumn();
  if(!$vendor_id){
    $insV = $pdo->prepare('INSERT INTO vendors (firm_id,name,pan) VALUES (?,?,?)');
    $insV->execute([$firm_id,$vendor_name,$vendor_pan]);
    $vendor_id = $pdo->lastInsertId();
  }

  // Resolve FY/Q
  list($fy,$q) = fy_quarter_from_date($invoice_date);

  // Resolve rate if not provided
  if($tds_rate===null){
    $stm = $pdo->prepare('SELECT rate FROM tds_rates WHERE section_code=? AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?) ORDER BY effective_from DESC LIMIT 1');
    $stm->execute([$section,$invoice_date,$invoice_date]);
    $row=$stm->fetch();
    if($row){ $tds_rate=(float)$row['rate']; } else { $tds_rate=0.0; }
  }

  $tds_amt = round($base_amount * $tds_rate / 100, 2);
  $total_tds = $tds_amt; // surcharge/cess not applied in MVP

  $insI = $pdo->prepare('INSERT INTO invoices (firm_id,vendor_id,invoice_no,invoice_date,base_amount,section_code,tds_rate,tds_amount,surcharge_amount,cess_amount,total_tds,fy,quarter) VALUES (?,?,?,?,?,?,?,?,?,?,?, ?,?)');
  $insI->execute([$firm_id,$vendor_id,$invoice_no,$invoice_date,$base_amount,$section,$tds_rate,$tds_amt,0,0,$total_tds,$fy,$q]);
  $id = $pdo->lastInsertId();

  json_ok(['id'=>$id,'row'=>[
    'id'=>$id,'invoice_date'=>$invoice_date,'vname'=>$vendor_name,'invoice_no'=>$invoice_no,
    'section_code'=>$section,'base_amount'=>$base_amount,'total_tds'=>$total_tds,'fy'=>$fy,'quarter'=>$q
  ]]);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'msg' => 'Error: ' . $e->getMessage()
  ]);
  exit;
}
