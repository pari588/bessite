<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php';
$page_title='Invoices'; include __DIR__.'/_layout_top.php';
$sections = get_tds_sections($pdo);
?>
<div class="card">
  <h3>Upload Invoices (CSV)</h3>
  <p>Columns: vendor_name, vendor_pan, invoice_no, invoice_date(YYYY-MM-DD), base_amount, section_code (optional if you choose a default below), optional: tds_rate</p>
  <form action="/tds-autofile/api/upload_invoices.php" method="post" enctype="multipart/form-data">
    <input type="file" name="csv" accept=".csv" required />
    <div style="height:8px"></div>
    <label>Default Section (used only when a row's section_code is blank)</label>
    <select name="default_section">
      <option value="">— None —</option>
      <?php foreach($sections as $s): ?>
        <option value="<?=htmlspecialchars($s['section_code'])?>"><?=htmlspecialchars($s['section_code'].' — '.$s['descn'])?></option>
      <?php endforeach; ?>
    </select>
    <md-filled-button type="submit">Upload</md-filled-button>
  </form>
</div>
<div style="height:12px"></div>
<div class="card">
  <h3>Add Single Invoice</h3>
  <form action="/tds-autofile/api/upload_invoices.php" method="post">
    <input type="hidden" name="single" value="1" />
    <md-outlined-text-field label="Vendor Name" name="vendor_name" required></md-outlined-text-field>
    <md-outlined-text-field label="Vendor PAN" name="vendor_pan" required></md-outlined-text-field>
    <md-outlined-text-field label="Invoice No" name="invoice_no" required></md-outlined-text-field>
    <md-outlined-text-field label="Invoice Date" name="invoice_date" type="date" required></md-outlined-text-field>
    <md-outlined-text-field label="Base Amount" name="base_amount" type="number" step="0.01" required></md-outlined-text-field>
    <label>TDS Section</label>
    <select name="section_code" required>
      <?php foreach($sections as $s): ?>
        <option value="<?=htmlspecialchars($s['section_code'])?>"><?=htmlspecialchars($s['section_code'].' — '.$s['descn'])?></option>
      <?php endforeach; ?>
    </select>
    <md-outlined-text-field label="TDS Rate (%)" name="tds_rate" type="number" step="0.001" placeholder="Auto from section if blank"></md-outlined-text-field>
    <md-filled-button type="submit">Add Invoice</md-filled-button>
  </form>
</div>
<div style="height:12px"></div>
<div class="card">
  <h3>Recent Invoices</h3>
  <table class="table">
    <tr><th>Date</th><th>Vendor</th><th>Invoice</th><th>Section</th><th>Base</th><th>TDS</th><th>FY/Qtr</th></tr>
    <?php foreach($pdo->query('SELECT i.*,v.name vname FROM invoices i JOIN vendors v ON v.id=i.vendor_id ORDER BY i.id DESC LIMIT 50') as $r): ?>
    <tr>
      <td><?=htmlspecialchars($r['invoice_date'])?></td>
      <td><?=htmlspecialchars($r['vname'])?></td>
      <td><?=htmlspecialchars($r['invoice_no'])?></td>
      <td><?=htmlspecialchars($r['section_code'])?></td>
      <td>₹ <?=number_format($r['base_amount'],2)?></td>
      <td>₹ <?=number_format($r['total_tds'],2)?></td>
      <td><?=$r['fy']?>/<?=$r['quarter']?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include __DIR__.'/_layout_bottom.php';
