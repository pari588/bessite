<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php';
$page_title='Invoices'; include __DIR__.'/_layout_top.php';
$sections = get_tds_sections($pdo);
$rows = $pdo->query('SELECT i.*,v.name vname FROM invoices i JOIN vendors v ON v.id=i.vendor_id ORDER BY i.id DESC LIMIT 50')->fetchAll();
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
    <md-outlined-text-field label="Vendor PAN" name="vendor_pan" required></md-outlined-text-field>
    <md-outlined-text-field label="Invoice No" name="invoice_no" required></md-outlined-text-field>
    <div>
      <label style="display:block;font-size:12px;color:var(--m3-muted)">Invoice Date</label>
      <input class="m3-date" id="inv_date_create" name="invoice_date" type="date" required />
    </div>
    <md-outlined-text-field label="Base Amount" name="base_amount" type="number" step="0.01" required></md-outlined-text-field>
    <div>
      <label style="display:block;font-size:12px;color:var(--m3-muted)">TDS Section</label>
      <md-outlined-select name="section_code" id="inv_section_create" required>
        <?php foreach($sections as $s): ?>
          <md-select-option value="<?=htmlspecialchars($s['section_code'])?>"><div slot="headline"><?=htmlspecialchars($s['section_code'].' — '.$s['descn'])?></div></md-select-option>
        <?php endforeach; ?>
      </md-outlined-select>
    </div>
    <md-outlined-text-field label="TDS Rate (%)" name="tds_rate" id="inv_rate_create" type="number" step="0.001" placeholder="Auto from section"></md-outlined-text-field>
    <div class="span-3" style="display:flex;gap:10px;justify-content:flex-end">
      <md-filled-button type="submit"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">add</span> Add Invoice</md-filled-button>
    </div>
  </form>
</div>

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
      <md-outlined-text-field name="base_amount" id="inv_amt" type="number" step="0.01" label="Base Amount" required></md-outlined-text-field>
      <div>
        <label style="display:block;font-size:12px;color:var(--m3-muted)">Section</label>
        <md-outlined-select name="section_code" id="inv_sec" required>
          <?php foreach($sections as $s): ?>
            <md-select-option value="<?=htmlspecialchars($s['section_code'])?>"><div slot="headline"><?=htmlspecialchars($s['section_code'].' — '.$s['descn'])?></div></md-select-option>
          <?php endforeach; ?>
        </md-outlined-select>
      </div>
      <md-outlined-text-field name="tds_rate" id="inv_rate" type="number" step="0.001" label="TDS Rate (%)"></md-outlined-text-field>
      <md-outlined-text-field id="inv_vendor" label="Vendor" value="" readonly class="span-3"></md-outlined-text-field>
      <div class="span-3" style="display:flex;gap:10px;justify-content:flex-end">
        <md-filled-button type="submit">Save</md-filled-button>
        <md-text-button type="button" onclick="closeModal()">Cancel</md-text-button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__.'/_layout_bottom.php';
