<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php';
if(session_status()===PHP_SESSION_NONE) session_start();

$fy=trim($_POST['fy'] ?? ''); $q=trim($_POST['quarter'] ?? '');
$report = ['fy'=>$fy,'quarter'=>$q,'allocations'=>[], 'allocated_total'=>0, 'invoices_count'=>0, 'source_invoices'=>0, 'source_challans'=>0];

// Count sources
$c1=$pdo->prepare('SELECT COUNT(*) FROM invoices WHERE fy=? AND quarter=? AND total_tds>0'); $c1->execute([$fy,$q]); $report['source_invoices']=(int)$c1->fetchColumn();
$c2=$pdo->prepare('SELECT COUNT(*) FROM challans WHERE fy=? AND quarter=?'); $c2->execute([$fy,$q]); $report['source_challans']=(int)$c2->fetchColumn();

// Pull data
$invs=$pdo->prepare("SELECT i.*, v.name vendor FROM invoices i JOIN vendors v ON v.id=i.vendor_id WHERE i.fy=? AND i.quarter=? AND i.total_tds>0 ORDER BY i.invoice_date,i.id");
$invs->execute([$fy,$q]);
$chls=$pdo->prepare("SELECT c.id, c.bsr_code, c.challan_serial_no, c.challan_date, (c.amount_tds - IFNULL((SELECT SUM(allocated_tds) FROM challan_allocations ca WHERE ca.challan_id=c.id),0)) AS balance FROM challans c WHERE c.fy=? AND c.quarter=? ORDER BY c.challan_date,c.id");
$chls->execute([$fy,$q]); $challans=$chls->fetchAll();

$insA=$pdo->prepare('INSERT INTO challan_allocations (challan_id,invoice_id,allocated_tds) VALUES (?,?,?) ON DUPLICATE KEY UPDATE allocated_tds=VALUES(allocated_tds)');

foreach($invs as $inv){
  $need=(float)$inv['total_tds']; $touched=false;
  foreach($challans as &$c){
    if($need<=0) break;
    $bal=(float)$c['balance']; if($bal<=0) continue;
    $alloc=min($need,$bal);
    $insA->execute([$c['id'],$inv['id'],$alloc]);
    $c['balance']=$bal-$alloc; $need-=$alloc; $touched=true;
    $report['allocations'][] = [
      'invoice_no'=>$inv['invoice_no'],
      'invoice_date'=>$inv['invoice_date'],
      'vendor'=>$inv['vendor'],
      'section'=>$inv['section_code'],
      'allocated'=>$alloc,
      'bsr'=>$c['bsr_code'],
      'challan_no'=>$c['challan_serial_no'],
      'challan_date'=>$c['challan_date']
    ];
    $report['allocated_total'] += $alloc;
  }
  if($touched) $report['invoices_count']++;
}

$_SESSION['reconcile_report'] = $report;
header('Location: /tds/admin/reconcile.php');
