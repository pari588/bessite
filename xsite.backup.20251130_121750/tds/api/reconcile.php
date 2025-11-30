<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php';
$fy=$_POST['fy']; $q=$_POST['quarter'];
$invs=$pdo->prepare("SELECT * FROM invoices WHERE fy=? AND quarter=? ORDER BY invoice_date,id"); $invs->execute([$fy,$q]);
$chls=$pdo->prepare("SELECT id, amount_tds - IFNULL((SELECT SUM(allocated_tds) FROM challan_allocations WHERE challan_id=challans.id),0) AS balance FROM challans WHERE fy=? AND quarter=? ORDER BY challan_date,id"); $chls->execute([$fy,$q]); $challans=$chls->fetchAll();
$insA=$pdo->prepare('INSERT INTO challan_allocations (challan_id,invoice_id,allocated_tds) VALUES (?,?,?) ON DUPLICATE KEY UPDATE allocated_tds=VALUES(allocated_tds)');
foreach($invs as $inv){
  $need=(float)$inv['total_tds'];
  foreach($challans as &$c){
    if($need<=0) break;
    $bal=(float)$c['balance']; if($bal<=0) continue;
    $alloc=min($need,$bal);
    $insA->execute([$c['id'],$inv['id'],$alloc]);
    $c['balance']=$bal-$alloc; $need-=$alloc;
  }
}
header('Location: /tds-autofile/admin/reconcile.php');
