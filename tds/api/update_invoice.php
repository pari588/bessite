<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php'; require_once __DIR__.'/../lib/ajax_helpers.php';

$id = (int)($_POST['id'] ?? 0);
$invoice_no  = trim($_POST['invoice_no'] ?? '');
$invoice_date= trim($_POST['invoice_date'] ?? '');
$base_amount = (float)($_POST['base_amount'] ?? 0);
$section     = strtoupper(trim($_POST['section_code'] ?? ''));
$tds_rate    = isset($_POST['tds_rate']) && $_POST['tds_rate']!=='' ? (float)$_POST['tds_rate'] : null;

if($id<=0 || $invoice_no==='' || $invoice_date==='' || $base_amount<=0 || $section===''){ json_err('Missing or invalid fields'); }

list($fy,$q) = fy_quarter_from_date($invoice_date);

if($tds_rate===null){
  $stm = $pdo->prepare('SELECT rate FROM tds_rates WHERE section_code=? AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?) ORDER BY effective_from DESC LIMIT 1');
  $stm->execute([$section,$invoice_date,$invoice_date]); $row=$stm->fetch();
  $tds_rate = $row ? (float)$row['rate'] : 0.0;
}

$tds_amt = round($base_amount * $tds_rate / 100, 2);
$total_tds = $tds_amt;

$up = $pdo->prepare('UPDATE invoices SET invoice_no=?, invoice_date=?, base_amount=?, section_code=?, tds_rate=?, tds_amount=?, surcharge_amount=0, cess_amount=0, total_tds=?, fy=?, quarter=? WHERE id=?');
$up->execute([$invoice_no,$invoice_date,$base_amount,$section,$tds_rate,$tds_amt,$total_tds,$fy,$q,$id]);

// return joined row
$st=$pdo->prepare('SELECT i.*, v.name vname FROM invoices i JOIN vendors v ON v.id=i.vendor_id WHERE i.id=?'); $st->execute([$id]); $r=$st->fetch();
json_ok(['row'=>$r]);
