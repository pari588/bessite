<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php';
$page_title='Dashboard'; include __DIR__.'/_layout_top.php';
$firm=$pdo->query('SELECT * FROM firms LIMIT 1')->fetch();
$inv=$pdo->query('SELECT COUNT(*) c, COALESCE(SUM(total_tds),0) s FROM invoices')->fetch();
$chl=$pdo->query('SELECT COUNT(*) c, COALESCE(SUM(amount_tds),0) s FROM challans')->fetch();
?>
<div class="grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
  <div class="card"><h4>Firm</h4><p><?=htmlspecialchars($firm['display_name']??'—')?></p><p>TAN: <?=htmlspecialchars($firm['tan']??'—')?></p></div>
  <div class="card"><h4>Invoices</h4><p>Total: <?=$inv['c']?></p><p>TDS: ₹ <?=number_format($inv['s'],2)?></p></div>
  <div class="card"><h4>Challans</h4><p>Total: <?=$chl['c']?></p><p>TDS: ₹ <?=number_format($chl['s'],2)?></p></div>
</div>
<div style="height:16px"></div>
<div class="card">
  <h4>Quick Actions</h4>
  <md-filled-button onclick="location.href='invoices.php'">Upload Invoices</md-filled-button>
  <md-filled-button onclick="location.href='challans.php'">Upload CSI/Challan</md-filled-button>
  <md-filled-button onclick="location.href='reconcile.php'">Auto‑Reconcile</md-filled-button>
  <md-filled-button onclick="location.href='returns.php'">Generate 26Q</md-filled-button>
</div>
<?php include __DIR__.'/_layout_bottom.php';
