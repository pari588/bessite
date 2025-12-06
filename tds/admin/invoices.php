<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/helpers.php';
require_once __DIR__.'/../lib/CalculatorAPI.php';

$page_title='Invoices';
include __DIR__.'/_layout_top.php';

$sections = get_tds_sections($pdo);
$calculator = new CalculatorAPI($pdo);

// Prepare statement to avoid SQL parameter binding errors
$stmt = $pdo->prepare('SELECT i.*,v.name vname FROM invoices i JOIN vendors v ON v.id=i.vendor_id ORDER BY i.id DESC LIMIT 50');
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<link rel="stylesheet" href="/tds/public/assets/styles_extra_dates.css" />
<link rel="stylesheet" href="/tds/public/assets/inputs_no_spinners.css" />
<script defer src="/tds/public/assets/app_dates.js"></script>
<script defer src="/tds/public/assets/app_live.js"></script>

<div class="card fade-in">
  <h3>Add Single Invoice</h3>
  <form id="singleInvForm" method="post" class="form-grid">
    <input type="hidden" name="single" value="1" />
    <md-outlined-text-field label="Vendor Name" name="vendor_name" class="span-2" required></md-outlined-text-field>
    <md-outlined-text-field label="Vendor PAN" name="vendor_pan" placeholder="XXXXX9999X" required></md-outlined-text-field>
    <md-outlined-text-field label="Invoice No" name="invoice_no" required></md-outlined-text-field>
    <div>
      <label style="display:block;font-size:12px;color:var(--m3-muted)">Invoice Date</label>
      <input class="m3-date" id="inv_date_create" name="invoice_date" type="date" required />
    </div>
    <md-outlined-text-field label="Base Amount (₹)" name="base_amount" id="base_amt_create" type="number" step="0.01" required onchange="calculateTDS('create')"></md-outlined-text-field>
    <div>
      <label style="display:block;font-size:12px;color:var(--m3-muted)">TDS Section</label>
      <md-outlined-select name="section_code" id="inv_section_create" required onchange="calculateTDS('create')">
        <md-select-option value="">-- Select Section --</md-select-option>
        <?php foreach($sections as $s): ?>
          <md-select-option value="<?=htmlspecialchars($s['section_code'])?>"><div slot="headline"><?=htmlspecialchars($s['section_code'].' — '.$s['descn'].' ('.$s['rate'].'%)')?></div></md-select-option>
        <?php endforeach; ?>
      </md-outlined-select>
    </div>
    <md-outlined-text-field label="TDS Rate (%)" name="tds_rate" id="inv_rate_create" type="number" step="0.001" placeholder="Auto-calculated" readonly></md-outlined-text-field>
    <md-outlined-text-field label="TDS Amount (₹)" name="total_tds" id="inv_tds_create" type="number" step="0.01" placeholder="Auto-calculated" readonly style="background: #f5f5f5;" class="span-2"></md-outlined-text-field>
    <div class="span-3" style="display:flex;gap:10px;justify-content:flex-end">
      <md-filled-button type="submit"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">add</span> Add Invoice</md-filled-button>
    </div>
  </form>
</div>

<script>
function calculateTDS(mode) {
  const baseAmt = parseFloat(document.getElementById('base_amt_' + mode).value) || 0;
  const section = document.getElementById('inv_section_' + mode).value;

  if (baseAmt <= 0 || !section) {
    document.getElementById('inv_rate_' + mode).value = '';
    document.getElementById('inv_tds_' + mode).value = '';
    return;
  }

  // Get rate from section dropdown
  const option = document.getElementById('inv_section_' + mode).querySelector('md-select-option[value="' + section + '"]');
  const text = option ? option.textContent : '';
  const rateMatch = text.match(/\(([0-9.]+)%\)/);
  const rate = rateMatch ? parseFloat(rateMatch[1]) : 0;

  document.getElementById('inv_rate_' + mode).value = rate.toFixed(3);

  const tds = (baseAmt * rate / 100);
  document.getElementById('inv_tds_' + mode).value = tds.toFixed(2);
}

// Call on page load to populate any existing data
document.addEventListener('DOMContentLoaded', function() {
  calculateTDS('create');
});
</script>

<div style="height:12px"></div>
<div class="card fade-in">
  <h3>Recent Invoices</h3>
  <div class="table-wrap">
  <table class="table" id="invoice-table">
    <thead><tr><th>Date</th><th>Vendor</th><th>Invoice</th><th>Section</th><th>Base</th><th>TDS</th><th>FY/Qtr</th><th>Action</th></tr></thead>
    <tbody id="invoice-tbody">
    <?php foreach($rows as $r): ?>
    <tr data-id="<?=$r['id']?>" data-invoice='<?=json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>'>
      <td><?=htmlspecialchars($r['invoice_date'])?></td>
      <td><?=htmlspecialchars($r['vname'])?></td>
      <td class="mono"><?=htmlspecialchars($r['invoice_no'])?></td>
      <td><?=htmlspecialchars($r['section_code'])?></td>
      <td>₹ <?=number_format($r['base_amount'],2)?></td>
      <td>₹ <?=number_format($r['total_tds'],2)?></td>
      <td><?=$r['fy']?>/<?=$r['quarter']?></td>
      <td>
        <md-text-button onclick="openInvEdit(this)"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">edit</span> Edit</md-text-button>
        <md-text-button onclick="deleteInv(<?=$r['id']?>)"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">delete</span> Delete</md-text-button>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- Modal -->
<div id="modal" class="modal" style="display:none">
  <div class="modal-card">
    <h3>Edit Invoice</h3>
    <form id="invEditForm" class="form-grid">
      <input type="hidden" name="id" id="inv_id"/>
      <md-outlined-text-field name="invoice_no" id="inv_no" label="Invoice No" class="span-2" required></md-outlined-text-field>
      <div>
        <label style="display:block;font-size:12px;color:var(--m3-muted)">Date</label>
        <input class="m3-date" id="inv_date" name="invoice_date" type="date" required />
      </div>
      <md-outlined-text-field name="base_amount" id="inv_amt" type="number" step="0.01" label="Base Amount" required onchange="calculateTDS('edit')"></md-outlined-text-field>
      <div>
        <label style="display:block;font-size:12px;color:var(--m3-muted)">Section</label>
        <md-outlined-select name="section_code" id="inv_sec" required onchange="calculateTDS('edit')">
          <?php foreach($sections as $s): ?>
            <md-select-option value="<?=htmlspecialchars($s['section_code'])?>"><div slot="headline"><?=htmlspecialchars($s['section_code'].' — '.$s['descn'].' ('.$s['rate'].'%)')?></div></md-select-option>
          <?php endforeach; ?>
        </md-outlined-select>
      </div>
      <md-outlined-text-field name="tds_rate" id="inv_rate" type="number" step="0.001" label="TDS Rate (%)" placeholder="Auto-calculated" readonly></md-outlined-text-field>
      <md-outlined-text-field name="total_tds" id="inv_tds" type="number" step="0.01" label="TDS Amount (₹)" placeholder="Auto-calculated" readonly style="background: #f5f5f5;"></md-outlined-text-field>
      <md-outlined-text-field id="inv_vendor" label="Vendor" value="" readonly class="span-3"></md-outlined-text-field>
      <div class="span-3" style="display:flex;gap:10px;justify-content:flex-end">
        <md-filled-button type="submit">Save</md-filled-button>
        <md-text-button type="button" onclick="closeModal()">Cancel</md-text-button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__.'/_layout_bottom.php';
