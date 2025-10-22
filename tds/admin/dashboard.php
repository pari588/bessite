<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php';
$page_title='Dashboard'; include __DIR__.'/_layout_top.php';

$today = date('Y-m-d'); [$curFy,$curQ] = fy_quarter_from_date($today);
$cntInv = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
$sumTds = (float)$pdo->query("SELECT COALESCE(SUM(total_tds),0) FROM invoices")->fetchColumn();
$cntCh = (int)$pdo->query("SELECT COUNT(*) FROM challans")->fetchColumn();
$cntVendFY = (int)$pdo->query("SELECT COUNT(DISTINCT vendor_id) FROM invoices WHERE fy=".$pdo->quote($curFy))->fetchColumn();
$cntInvFY = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE fy=".$pdo->quote($curFy))->fetchColumn();
$sumTdsFY = (float)$pdo->query("SELECT COALESCE(SUM(total_tds),0) FROM invoices WHERE fy=".$pdo->quote($curFy))->fetchColumn();
?>
<div class="kpis fade-in">
  <div class="kpi k1">
    <div class="label">Total Invoices</div>
    <div class="value"><?=number_format($cntInv)?></div>
  </div>
  <div class="kpi k2">
    <div class="label">Total TDS (All time)</div>
    <div class="value">₹ <?=number_format($sumTds,2)?></div>
  </div>
  <div class="kpi k3">
    <div class="label">Challans</div>
    <div class="value"><?=number_format($cntCh)?></div>
  </div>
  <div class="kpi k4">
    <div class="label">Active Vendors (FY)</div>
    <div class="value"><?=number_format($cntVendFY)?></div>
  </div>
</div>

<div style="height:14px"></div>
<div class="card fade-in">
  <h3 style="margin-top:0">This FY snapshot — <?=$curFy?> (<?=$curQ?>)</h3>
  <p class="muted">Invoices: <span class="badge"><?=$cntInvFY?></span> · Vendors (distinct): <span class="badge"><?=$cntVendFY?></span> · TDS total: <span class="badge">₹ <?=number_format($sumTdsFY,2)?></span></p>
  <p>Quick links:
    <a href="invoices.php">Invoices</a> ·
    <a href="challans.php">Challans</a> ·
    <a href="reconcile.php">Reconcile</a> ·
    <a href="returns.php">Returns</a>
  </p>
</div>
<?php include __DIR__.'/_layout_bottom.php';
