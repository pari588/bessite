<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php';

// Single-invoice submit
if(isset($_POST['single'])){
  $firm=$pdo->query('SELECT id FROM firms LIMIT 1')->fetchColumn();
  $vendor=$_POST['vendor_name']; $pan=strtoupper(trim($_POST['vendor_pan']));
  $inv=$_POST['invoice_no']; $dt=$_POST['invoice_date']; $amt=(float)$_POST['base_amount'];
  $sec=strtoupper($_POST['section_code']); $rate=$_POST['tds_rate']!==''?(float)$_POST['tds_rate']:null;
  $q=$pdo->prepare('SELECT rate FROM tds_rates WHERE section_code=? AND (effective_to IS NULL OR effective_to>=CURDATE()) ORDER BY effective_from DESC LIMIT 1');
  $q->execute([$sec]); $r=$q->fetch();
  if(!$r){ die('Invalid Section: '.$sec); }
  if($rate===null){ $rate=(float)$r['rate']; }
  $tds=round($amt*$rate/100,2); $surcharge=0; $cess=0; $total=$tds+$surcharge+$cess; [$fy,$qr]=fy_quarter_from_date($dt);
  $insV=$pdo->prepare('INSERT INTO vendors (firm_id,name,pan) VALUES (?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
  $selV=$pdo->prepare('SELECT id FROM vendors WHERE firm_id=? AND pan=?');
  $insV->execute([$firm,$vendor,$pan]); $selV->execute([$firm,$pan]); $vid=(int)$selV->fetchColumn();
  $insI=$pdo->prepare('INSERT INTO invoices (firm_id,vendor_id,invoice_no,invoice_date,base_amount,section_code,tds_rate,tds_amount,surcharge_amount,cess_amount,total_tds,fy,quarter) VALUES (?,?,?,?,?,?,?,?,?,?,?, ?,?)');
  $insI->execute([$firm,$vid,$inv,$dt,$amt,$sec,$rate,$tds,$surcharge,$cess,$total,$fy,$qr]);
  header('Location: /tds-autofile/admin/invoices.php'); exit;
}

// CSV upload
if(!isset($_FILES['csv'])){ http_response_code(400); exit('No file'); }
$defaultSection = isset($_POST['default_section']) && $_POST['default_section']!=='' ? strtoupper($_POST['default_section']) : null;
if($defaultSection){ $chk=$pdo->prepare('SELECT 1 FROM tds_rates WHERE section_code=?'); $chk->execute([$defaultSection]); if(!$chk->fetch()) die('Invalid default section: '.$defaultSection); }
$fp=fopen($_FILES['csv']['tmp_name'],'r'); $hdr=fgetcsv($fp); $map=array_flip(array_map('strtolower',$hdr));
foreach(['vendor_name','vendor_pan','invoice_no','invoice_date','base_amount'] as $n){ if(!isset($map[$n])) die('Missing column: '.$n); }
$firm=$pdo->query('SELECT id FROM firms LIMIT 1')->fetchColumn();
$insV=$pdo->prepare('INSERT INTO vendors (firm_id,name,pan) VALUES (?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
$selV=$pdo->prepare('SELECT id FROM vendors WHERE firm_id=? AND pan=?');
$insI=$pdo->prepare('INSERT INTO invoices (firm_id,vendor_id,invoice_no,invoice_date,base_amount,section_code,tds_rate,tds_amount,surcharge_amount,cess_amount,total_tds,fy,quarter) VALUES (?,?,?,?,?,?,?,?,?,?,?, ?,?)');
while(($row=fgetcsv($fp))!==false){
  $row=clean_csv_row($row);
  $vendor=$row[$map['vendor_name']]; $pan=strtoupper($row[$map['vendor_pan']]);
  $inv=$row[$map['invoice_no']]; $dt=$row[$map['invoice_date']]; $amt=(float)$row[$map['base_amount']];
  $sec = (isset($map['section_code']) and $row[$map['section_code']]!=='') ? strtoupper($row[$map['section_code']]) : $defaultSection;
  if(!$sec){ fclose($fp); die('Missing section_code and no default selected.'); }
  $qr=$pdo->prepare('SELECT rate FROM tds_rates WHERE section_code=? AND (effective_to IS NULL OR effective_to>=CURDATE()) ORDER BY effective_from DESC LIMIT 1');
  $qr->execute([$sec]); $rateRow=$qr->fetch(); if(!$rateRow) die('Invalid section in CSV: '.$sec);
  $rate = isset($map['tds_rate']) and $row[$map['tds_rate']]!=='' ? (float)$row[$map['tds_rate']] : (float)$rateRow['rate'];
  $tds=round($amt*$rate/100,2); $surcharge=0; $cess=0; $total=$tds+$surcharge+$cess; [$fy,$qtr]=fy_quarter_from_date($dt);
  $insV->execute([$firm,$vendor,$pan]); $selV->execute([$firm,$pan]); $vid=(int)$selV->fetchColumn();
  $insI->execute([$firm,$vid,$inv,$dt,$amt,$sec,$rate,$tds,$surcharge,$cess,$total,$fy,$qtr]);
}
fclose($fp);
header('Location: /tds-autofile/admin/invoices.php');
