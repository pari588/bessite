<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php'; require_once __DIR__.'/../lib/CSILoader.php';
$firm_id = (int)$pdo->query('SELECT id FROM firms LIMIT 1')->fetchColumn();
if(isset($_POST['manual'])){
  $bsr=$_POST['bsr']; $date=$_POST['date']; $serial=$_POST['serial']; $amt=(float)$_POST['amount'];
  [$fy,$q]=fy_quarter_from_date($date);
  $ins=$pdo->prepare('INSERT INTO challans (firm_id,bsr_code,challan_date,challan_serial_no,amount_total,amount_tds,fy,quarter) VALUES (?,?,?,?,?,?,?,?)');
  $ins->execute([$firm_id,$bsr,$date,$serial,$amt,$amt,$fy,$q]);
  header('Location: /tds-autofile/admin/challans.php'); exit;
}
if(isset($_FILES['csi']) && is_uploaded_file($_FILES['csi']['tmp_name'])){
  CSILoader::ingest($firm_id,$_FILES['csi']['tmp_name']);
}
header('Location: /tds-autofile/admin/challans.php');
