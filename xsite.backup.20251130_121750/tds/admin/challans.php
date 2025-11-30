<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php';
$page_title='Challans'; include __DIR__.'/_layout_top.php';
?>
<div class="card">
  <h3>Upload CSI / Add Challan</h3>
  <form action="/tds-autofile/api/upload_challan.php" method="post" enctype="multipart/form-data">
    <label>CSI/Challan file (.txt)</label>
    <input type="file" name="csi" accept=".txt,.csv" />
    <md-filled-button type="submit">Upload</md-filled-button>
  </form>
  <p style="opacity:.7">Tip: Download CSI from Income‑tax portal (e‑Pay Tax → Payment History) or OLTAS. Formats vary by source.</p>
  <hr/>
  <form method="post" action="/tds-autofile/api/upload_challan.php">
    <h4>Manual Challan</h4>
    <input type="hidden" name="manual" value="1" />
    <md-outlined-text-field label="BSR Code" name="bsr" required></md-outlined-text-field>
    <md-outlined-text-field label="Challan Date" name="date" type="date" required></md-outlined-text-field>
    <md-outlined-text-field label="Challan Serial No" name="serial" required></md-outlined-text-field>
    <md-outlined-text-field label="TDS Amount" name="amount" type="number" step="0.01" required></md-outlined-text-field>
    <md-filled-button type="submit">Add Challan</md-filled-button>
  </form>
</div>
<div style="height:12px"></div>
<div class="card">
  <h3>Recent Challans</h3>
  <table class="table">
    <tr><th>BSR</th><th>Date</th><th>Serial</th><th>TDS</th><th>FY/Qtr</th></tr>
    <?php foreach($pdo->query('SELECT * FROM challans ORDER BY id DESC LIMIT 50') as $r): ?>
    <tr>
      <td><?=$r['bsr_code']?></td><td><?=$r['challan_date']?></td><td><?=$r['challan_serial_no']?></td>
      <td>₹ <?=number_format($r['amount_tds'],2)?></td><td><?=$r['fy']?>/<?=$r['quarter']?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include __DIR__.'/_layout_bottom.php';
