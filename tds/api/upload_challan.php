<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php'; require_once __DIR__.'/../lib/CSILoader.php';
$firm_id = (int)$pdo->query('SELECT id FROM firms LIMIT 1')->fetchColumn();
$want_json = (isset($_GET['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'],'application/json')));
function json_out($a){ header('Content-Type: application/json'); echo json_encode($a); exit; }

if(isset($_POST['manual'])){
  $bsr=$_POST['bsr']; $date=$_POST['date']; $serial=$_POST['serial']; $amt=(float)$_POST['amount'];
  [$fy,$q]=fy_quarter_from_date($date);
  $ins=$pdo->prepare('INSERT INTO challans (firm_id,bsr_code,challan_date,challan_serial_no,amount_total,amount_tds,fy,quarter) VALUES (?,?,?,?,?,?,?,?)');
  $ins->execute([$firm_id,$bsr,$date,$serial,$amt,$amt,$fy,$q]);
  if($want_json){ json_out(['ok'=>true,'bsr_code'=>$bsr,'challan_date'=>$date,'challan_serial_no'=>$serial,'amount_tds'=>$amt,'fy'=>$fy,'quarter'=>$q]); }
  header('Location: /tds/admin/challans.php'); exit;
}

if(isset($_FILES['csi']) && is_uploaded_file($_FILES['csi']['tmp_name'])){
  [$added,$lines] = CSILoader::ingest($firm_id,$_FILES['csi']['tmp_name']);
  if($want_json){ json_out(['ok'=>true,'added'=>$added,'lines'=>$lines]); }
}
header('Location: /tds/admin/challans.php');
