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

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
  <!-- SINGLE CHALLAN FORM -->
  <div class="card fade-in">
    <h3>Add Single Challan</h3>
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

  <!-- BULK CSV IMPORT -->
  <div class="card fade-in">
    <h3>Bulk Import (CSV)</h3>
    <form id="bulkChForm" method="post" enctype="multipart/form-data" class="form-grid">
      <div style="display:flex;flex-direction:column;gap:10px">
        <div style="font-size:12px;color:#666;background:#f5f5f5;padding:12px;border-radius:4px;">
          <strong>CSV Format:</strong>
          <div style="margin-top:8px;font-family:monospace;font-size:11px">
            bsr_code, challan_date, challan_serial_no, amount_tds, surcharge, health_and_education_cess
          </div>
          <div style="margin-top:8px;color:#999;font-size:11px">
            • challan_date: YYYY-MM-DD<br>
            • surcharge, cess, interest: optional (leave empty if zero)<br>
            • <a href="#" onclick="downloadSampleChallanCSV(event)" style="color:#1976d2">Download Sample CSV</a>
          </div>
        </div>

        <div style="position:relative">
          <input type="file" id="csvChallanInput" name="csv_file" accept=".csv" style="display:none" onchange="handleChallanCsvUpload(event)" />
          <md-filled-button type="button" onclick="document.getElementById('csvChallanInput').click()" style="width:100%">
            <span class="material-symbols-rounded" style="margin-right:6px">upload_file</span>
            Choose CSV File
          </md-filled-button>
          <div id="challanFileNameDisplay" style="font-size:12px;color:#666;margin-top:8px;text-align:center"></div>
        </div>

        <div id="challanImportProgress" style="display:none">
          <md-linear-progress indeterminate></md-linear-progress>
          <div style="font-size:12px;color:#666;margin-top:8px">Importing challans...</div>
        </div>

        <div id="challanImportResult" style="display:none;padding:12px;border-radius:4px;font-size:12px"></div>
      </div>
    </form>
  </div>
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

<script>
// CSV bulk import functions for challans
async function handleChallanCsvUpload(event) {
  const file = event.target.files[0];
  if (!file) return;

  // Show file name
  document.getElementById('challanFileNameDisplay').textContent = `Selected: ${file.name}`;

  // Show progress
  document.getElementById('challanImportProgress').style.display = 'block';
  document.getElementById('challanImportResult').style.display = 'none';

  try {
    const formData = new FormData();
    formData.append('csv_file', file);

    const response = await fetch('/tds/api/bulk_import_challans.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    // Hide progress
    document.getElementById('challanImportProgress').style.display = 'none';

    // Show result
    const resultDiv = document.getElementById('challanImportResult');
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

      // Refresh challan list if successful
      if (result.data.successful > 0) {
        setTimeout(() => refreshChallans?.(), 1000);
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
    document.getElementById('challanImportProgress').style.display = 'none';
    const resultDiv = document.getElementById('challanImportResult');
    resultDiv.style.display = 'block';
    resultDiv.style.background = '#ffebee';
    resultDiv.style.borderLeft = '4px solid #d32f2f';
    resultDiv.style.color = '#c62828';
    resultDiv.innerHTML = `<strong>✗ Network Error</strong><div>${error.message}</div>`;
  }
}

function downloadSampleChallanCSV(e) {
  e.preventDefault();
  const csvContent = `bsr_code,challan_date,challan_serial_no,amount_tds,surcharge,health_and_education_cess,interest
0011021060,2024-04-30,123456,50000,0,0,0
0011021060,2024-05-31,123457,75000,5000,0,0
0011021060,2024-06-30,123458,100000,0,3000,`;

  const blob = new Blob([csvContent], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'sample_challans.csv';
  a.click();
  window.URL.revokeObjectURL(url);
}
</script>

<?php include __DIR__.'/_layout_bottom.php';
