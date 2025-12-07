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

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
  <!-- SINGLE INVOICE FORM -->
  <div class="card fade-in">
    <h3>Add Single Invoice</h3>
    <form id="singleInvForm" method="post" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <input type="hidden" name="single" value="1" />
      <md-outlined-text-field label="Vendor Name" name="vendor_name" style="grid-column:1/3" required></md-outlined-text-field>
      <md-outlined-text-field label="Vendor PAN" name="vendor_pan" placeholder="XXXXX9999X" required></md-outlined-text-field>
      <md-outlined-text-field label="Invoice No" name="invoice_no" required></md-outlined-text-field>
      <div style="display:flex;flex-direction:column;gap:4px">
        <label style="font-size:12px;color:#666">Invoice Date</label>
        <input class="m3-date" id="inv_date_create" name="invoice_date" type="date" required />
      </div>
      <md-outlined-text-field label="Base Amount (₹)" name="base_amount" id="base_amt_create" type="number" step="0.01" required onchange="calculateTDS('create')"></md-outlined-text-field>
      <md-outlined-select label="TDS Section" name="section_code" id="inv_section_create" required onchange="calculateTDS('create')">
        <md-select-option value="">-- Select Section --</md-select-option>
        <?php foreach($sections as $s): ?>
          <md-select-option value="<?=htmlspecialchars($s['section_code'])?>"><div slot="headline"><?=htmlspecialchars($s['section_code'].' — '.$s['descn'].' ('.$s['rate'].'%)')?></div></md-select-option>
        <?php endforeach; ?>
      </md-outlined-select>
      <md-outlined-text-field label="TDS Rate (%)" name="tds_rate" id="inv_rate_create" type="number" step="0.001" placeholder="Auto-calculated" readonly></md-outlined-text-field>
      <md-outlined-text-field label="TDS Amount (₹)" name="total_tds" id="inv_tds_create" type="number" step="0.01" placeholder="Auto-calculated" readonly style="background: #f5f5f5; grid-column:1/3"></md-outlined-text-field>
      <div style="grid-column:1/3;display:flex;gap:10px;justify-content:flex-end">
        <md-filled-button type="submit"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">add</span> Add Invoice</md-filled-button>
      </div>
    </form>
  </div>

  <!-- BULK CSV IMPORT -->
  <div class="card fade-in">
    <h3>Bulk Import (CSV)</h3>
    <div style="font-size:12px;color:#666;background:#f5f5f5;padding:12px;border-radius:4px;margin-bottom:12px;">
      <strong>CSV Format:</strong>
      <div style="margin-top:8px;font-family:monospace;font-size:11px">
        vendor_name, vendor_pan, invoice_no, invoice_date, base_amount, section_code, tds_rate
      </div>
      <div style="margin-top:8px;color:#999;font-size:11px">
        • invoice_date: YYYY-MM-DD<br>
        • tds_rate: optional (auto-calculated if not provided)<br>
        • <a href="#" onclick="downloadSampleInvoiceCSV(event)" style="color:#1976d2">Download Sample CSV</a>
      </div>
    </div>

    <div style="position:relative;margin-bottom:12px">
      <input type="file" id="csvFileInput" name="csv_file" accept=".csv" style="display:none" onchange="handleCsvUpload(event)" />
      <md-filled-button type="button" onclick="document.getElementById('csvFileInput').click()" style="width:100%">
        <span class="material-symbols-rounded" style="margin-right:6px">upload_file</span>
        Choose CSV File
      </md-filled-button>
      <div id="fileNameDisplay" style="font-size:12px;color:#666;margin-top:8px;text-align:center"></div>
    </div>

    <div id="importProgress" style="display:none;margin-bottom:12px">
      <md-linear-progress indeterminate></md-linear-progress>
      <div style="font-size:12px;color:#666;margin-top:8px">Importing invoices...</div>
    </div>

    <div id="importResult" style="display:none;padding:12px;border-radius:4px;font-size:12px"></div>
  </div>
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
    <form id="invEditForm" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <input type="hidden" name="id" id="inv_id"/>
      <md-outlined-text-field name="invoice_no" id="inv_no" label="Invoice No" style="grid-column:1/3" required></md-outlined-text-field>
      <div style="display:flex;flex-direction:column;gap:4px">
        <label style="font-size:12px;color:#666">Date</label>
        <input class="m3-date" id="inv_date" name="invoice_date" type="date" required />
      </div>
      <md-outlined-text-field name="base_amount" id="inv_amt" type="number" step="0.01" label="Base Amount" required onchange="calculateTDS('edit')"></md-outlined-text-field>
      <md-outlined-select label="Section" name="section_code" id="inv_sec" required onchange="calculateTDS('edit')">
        <?php foreach($sections as $s): ?>
          <md-select-option value="<?=htmlspecialchars($s['section_code'])?>"><div slot="headline"><?=htmlspecialchars($s['section_code'].' — '.$s['descn'].' ('.$s['rate'].'%)')?></div></md-select-option>
        <?php endforeach; ?>
      </md-outlined-select>
      <md-outlined-text-field name="tds_rate" id="inv_rate" type="number" step="0.001" label="TDS Rate (%)" placeholder="Auto-calculated" readonly></md-outlined-text-field>
      <md-outlined-text-field name="total_tds" id="inv_tds" type="number" step="0.01" label="TDS Amount (₹)" placeholder="Auto-calculated" readonly style="background: #f5f5f5; grid-column:1/3"></md-outlined-text-field>
      <md-outlined-text-field id="inv_vendor" label="Vendor" value="" readonly style="grid-column:1/3"></md-outlined-text-field>
      <div style="grid-column:1/3;display:flex;gap:10px;justify-content:flex-end">
        <md-filled-button type="submit">Save</md-filled-button>
        <md-text-button type="button" onclick="closeModal()">Cancel</md-text-button>
      </div>
    </form>
  </div>
</div>

<script>
// CSV bulk import functions
async function handleCsvUpload(event) {
  const file = event.target.files[0];
  if (!file) return;

  // Show file name
  document.getElementById('fileNameDisplay').textContent = `Selected: ${file.name}`;

  // Show progress
  document.getElementById('importProgress').style.display = 'block';
  document.getElementById('importResult').style.display = 'none';

  try {
    const formData = new FormData();
    formData.append('csv_file', file);

    const response = await fetch('/tds/api/bulk_import_invoices.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    // Hide progress
    document.getElementById('importProgress').style.display = 'none';

    // Show result
    const resultDiv = document.getElementById('importResult');
    resultDiv.style.display = 'block';

    if (result.ok) {
      const successColor = result.data.failed === 0 ? '#4caf50' : '#ff9800';
      const successBg = result.data.failed === 0 ? '#e8f5e9' : '#fff8f0';

      resultDiv.style.background = successBg;
      resultDiv.style.borderLeft = `4px solid ${successColor}`;
      resultDiv.style.color = '#333';

      let html = `<div style="font-weight:600;color:${successColor};margin-bottom:8px">
        ${result.data.failed === 0 ? '✓ Import Successful' : '⚠ Import Completed with Errors'}
      </div>`;

      html += `<div style="margin-bottom:8px">
        <div>Total: ${result.data.total} rows</div>
        <div style="color:#4caf50">✓ Successful: ${result.data.successful}</div>
        ${result.data.failed > 0 ? `<div style="color:#d32f2f">✗ Failed: ${result.data.failed}</div>` : ''}
      </div>`;

      if (result.data.errors && result.data.errors.length > 0) {
        html += `<details style="margin-top:8px"><summary style="cursor:pointer;color:#1976d2">View Errors</summary>
          <div style="margin-top:8px;padding:8px;background:#f5f5f5;border-radius:4px;max-height:200px;overflow-y:auto;font-size:11px">`;

        result.data.errors.forEach(err => {
          html += `<div style="margin-bottom:6px">Row ${err.row}: ${err.error}</div>`;
        });

        html += `</div></details>`;
      }

      resultDiv.innerHTML = html;

      // Refresh invoice list if successful
      if (result.data.successful > 0) {
        setTimeout(() => refreshInvoices?.(), 1000);
      }
    } else {
      resultDiv.style.background = '#ffebee';
      resultDiv.style.borderLeft = '4px solid #d32f2f';
      resultDiv.style.color = '#c62828';
      resultDiv.innerHTML = `<strong>✗ Import Failed</strong><div>${result.msg || 'Unknown error'}</div>`;
    }

    // Reset file input
    event.target.value = '';

  } catch (error) {
    document.getElementById('importProgress').style.display = 'none';
    const resultDiv = document.getElementById('importResult');
    resultDiv.style.display = 'block';
    resultDiv.style.background = '#ffebee';
    resultDiv.style.borderLeft = '4px solid #d32f2f';
    resultDiv.style.color = '#c62828';
    resultDiv.innerHTML = `<strong>✗ Network Error</strong><div>${error.message}</div>`;
  }
}

function downloadSampleInvoiceCSV(e) {
  e.preventDefault();
  const csvContent = `vendor_name,vendor_pan,invoice_no,invoice_date,base_amount,section_code,tds_rate
ABC Corp,ABCDE1234F,INV-001,2024-04-15,100000,194H,2.0
XYZ Ltd,XYZAB5678G,INV-002,2024-05-20,250000,194H,2.0
Tech Services,TECH91234H,INV-003,2024-06-10,500000,194J,`;

  const blob = new Blob([csvContent], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'sample_invoices.csv';
  a.click();
  window.URL.revokeObjectURL(url);
}
</script>

<?php include __DIR__.'/_layout_bottom.php';
