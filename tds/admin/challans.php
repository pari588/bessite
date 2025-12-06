<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';

$page_title='Challans';
include __DIR__.'/_layout_top.php';

// Use prepared statement to avoid SQL errors
$stmt = $pdo->prepare('SELECT * FROM challans ORDER BY id DESC LIMIT 50');
$stmt->execute();
$rows = $stmt->fetchAll();

// Get summary statistics
$summaryStmt = $pdo->prepare('SELECT COUNT(*) as count, COALESCE(SUM(amount_tds), 0) as total_tds FROM challans');
$summaryStmt->execute();
$summary = $summaryStmt->fetch();
?>
<link rel="stylesheet" href="/tds/public/assets/styles_extra_dates.css" />
<link rel="stylesheet" href="/tds/public/assets/inputs_no_spinners.css" />
<script defer src="/tds/public/assets/app_dates.js"></script>
<script defer src="/tds/public/assets/app_live.js"></script>

<div class="card fade-in">
  <h3>Manual Challan</h3>
  <form id="manChForm" method="post" class="form-grid">
    <md-outlined-text-field label="BSR Code" name="bsr" required></md-outlined-text-field>
    <div>
      <label style="display:block;font-size:12px;color:var(--m3-muted)">Challan Date</label>
      <input class="m3-date" id="ch_date_create" name="date" type="date" required />
    </div>
    <md-outlined-text-field label="Challan Serial No" name="serial" required></md-outlined-text-field>
    <md-outlined-text-field label="TDS Amount" name="amount" type="number" step="0.01" required></md-outlined-text-field>
    <div class="span-3" style="display:flex;gap:10px;justify-content:flex-end">
      <md-filled-button type="submit">Add Challan</md-filled-button>
    </div>
  </form>
</div>

<div style="height:12px"></div>

<!-- SUMMARY CARD -->
<div class="card fade-in" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 16px;">
  <div style="padding: 12px; background: #f5f5f5; border-radius: 4px; border-left: 4px solid #2196f3;">
    <div style="font-size: 12px; color: #666; margin-bottom: 6px;">Total Challans</div>
    <div style="font-size: 24px; font-weight: 600; color: #1976d2;"><?=$summary['count']?></div>
  </div>
  <div style="padding: 12px; background: #f5f5f5; border-radius: 4px; border-left: 4px solid #4caf50;">
    <div style="font-size: 12px; color: #666; margin-bottom: 6px;">Total TDS Paid</div>
    <div style="font-size: 20px; font-weight: 600; color: #4caf50;">₹ <?=number_format($summary['total_tds'], 2)?></div>
  </div>
</div>

<div style="height:12px"></div>
<div class="card fade-in">
  <h3>Recent Challans</h3>
  <div class="table-wrap">
  <table class="table" id="challan-table">
    <thead><tr><th>BSR</th><th>Date</th><th>Serial</th><th>TDS</th><th>FY/Qtr</th><th>Action</th></tr></thead>
    <tbody id="challan-tbody">
      <?php foreach($rows as $r): ?>
      <tr data-id="<?=$r['id']?>" data-challan='<?=json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>'>
        <td><?=$r['bsr_code']?></td><td><?=$r['challan_date']?></td><td class="mono"><?=$r['challan_serial_no']?></td>
        <td>₹ <?=number_format($r['amount_tds'],2)?></td><td><?=$r['fy']?>/<?=$r['quarter']?></td>
        <td>
          <md-text-button onclick="openChEdit(this)"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">edit</span> Edit</md-text-button>
          <md-text-button onclick="deleteCh(<?=$r['id']?>)"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">delete</span> Delete</md-text-button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- Modal -->
<div id="cmodal" class="modal" style="display:none">
  <div class="modal-card">
    <h3>Edit Challan</h3>
    <form id="chEditForm" class="form-grid">
      <input type="hidden" name="id" id="ch_id"/>
      <md-outlined-text-field name="bsr_code" id="ch_bsr" label="BSR" required></md-outlined-text-field>
      <div>
        <label style="display:block;font-size:12px;color:var(--m3-muted)">Date</label>
        <input class="m3-date" id="ch_date" name="challan_date" type="date" required />
      </div>
      <md-outlined-text-field name="challan_serial_no" id="ch_serial" label="Serial No" required></md-outlined-text-field>
      <md-outlined-text-field name="amount_tds" id="ch_amt" type="number" step="0.01" label="TDS Amount" required></md-outlined-text-field>
      <div class="span-3" style="display:flex;gap:10px;justify-content:flex-end">
        <md-filled-button type="submit">Save</md-filled-button>
        <md-text-button type="button" onclick="closeCModal()">Cancel</md-text-button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__.'/_layout_bottom.php';
