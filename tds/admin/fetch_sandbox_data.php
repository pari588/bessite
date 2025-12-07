<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';

$page_title = 'Fetch Data from Sandbox';
include __DIR__.'/_layout_top.php';

// Get current firm
$stmt = $pdo->prepare("SELECT id, display_name, tan FROM firms LIMIT 1");
$stmt->execute();
$currentFirm = $stmt->fetch();
$firmId = $currentFirm['id'] ?? null;

// Get list of all available FYs
$availableFYs = [];
$currentYear = (int)date('Y');
$currentMonth = (int)date('m');

// If we're before April, current FY started last year
$startYear = ($currentMonth < 4) ? $currentYear - 1 : $currentYear;

// Generate list of FYs: current, previous 5 years, and next 2 years
for ($i = $startYear + 2; $i >= $startYear - 5; $i--) {
    $fy = $i . '-' . substr($i + 1, 2);
    $availableFYs[] = $fy;
}

// Get default FY (current)
$defaultFY = ($startYear) . '-' . substr($startYear + 1, 2);
$selectedFY = $_GET['fy'] ?? $_POST['fy'] ?? $defaultFY;
$selectedQuarter = $_GET['quarter'] ?? $_POST['quarter'] ?? 'Q1';

?>

<style>
.fetch-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

@media (max-width: 1024px) {
  .fetch-container {
    grid-template-columns: 1fr;
  }
}

.fetch-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
  transition: all 0.3s;
}

.fetch-card:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.fetch-card h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  color: #1976d2;
  display: flex;
  align-items: center;
  gap: 8px;
}

.fetch-card p {
  font-size: 13px;
  color: #666;
  margin: 0 0 12px 0;
  line-height: 1.5;
}

.status-indicator {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
}

.status-success {
  background: #e8f5e9;
  color: #2e7d32;
}

.status-loading {
  background: #fff3e0;
  color: #e65100;
}

.status-error {
  background: #ffebee;
  color: #c62828;
}

.fetch-progress {
  margin-top: 16px;
  display: none;
}

.progress-bar {
  height: 4px;
  background: #e0e0e0;
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background: #1976d2;
  width: 0%;
  animation: progress 1s ease-in-out;
}

@keyframes progress {
  0% { width: 0%; }
  50% { width: 70%; }
  100% { width: 100%; }
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <h2 style="margin: 0;">Import Data from Sandbox</h2>
  <div style="display: flex; gap: 8px;">
    <md-filled-tonal-button onclick="location.href='/tds/SANDBOX_API_SETUP_GUIDE.md'" target="_blank">
      <span class="material-symbols-rounded" style="margin-right: 6px;">help</span>
      Setup Guide
    </md-filled-tonal-button>
    <md-filled-button onclick="location.href='dashboard.php'">
      <span class="material-symbols-rounded" style="margin-right: 6px;">arrow_back</span>
      Back to Dashboard
    </md-filled-button>
  </div>
</div>

<div style="background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
  <h3 style="margin: 0 0 8px 0; color: #e65100;">ℹ️ How to Import Data</h3>
  <p style="margin: 0 0 12px 0; color: #666;">
    Sandbox.co.in manages invoices and challans through their web interface. To bring this data into your TDS system, use one of these methods:
  </p>
  <div style="background: white; padding: 12px; border-radius: 4px; margin-top: 8px;">
    <div style="font-size: 12px; color: #333; margin-bottom: 12px;">
      <strong>Recommended Method:</strong>
      <ol style="margin: 8px 0 0 0; padding-left: 20px;">
        <li>Export data from Sandbox.co.in web portal to CSV</li>
        <li>Use "Manual Invoice Import" → "Go to Invoices Page"</li>
        <li>Upload the CSV file in the form</li>
        <li>System automatically calculates TDS</li>
      </ol>
    </div>
  </div>
</div>

<!-- FIRM INFO & SELECTION -->
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px; margin-bottom: 24px;">
  <h3 style="margin: 0 0 16px 0; font-size: 16px;">Current Firm & Time Period</h3>

  <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px;">
    <div>
      <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Firm Name</label>
      <div style="font-size: 14px; font-weight: 500; color: #333;">
        <?=htmlspecialchars($currentFirm['display_name'] ?? 'Not configured')?>
      </div>
    </div>

    <div>
      <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">TAN</label>
      <div style="font-size: 14px; font-weight: 500; color: #333;">
        <?=htmlspecialchars($currentFirm['tan'] ?? 'Not configured')?>
      </div>
    </div>

    <div>
      <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Firm ID</label>
      <div style="font-size: 14px; font-weight: 500; color: #333;">
        <?=$firmId?>
      </div>
    </div>
  </div>

  <!-- FY AND QUARTER SELECTOR -->
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
    <div>
      <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Select Financial Year</label>
      <select id="fySelect" onchange="updateSelectedPeriod()" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
        <?php foreach ($availableFYs as $fy): ?>
          <option value="<?=$fy?>" <?= $fy === $selectedFY ? 'selected' : '' ?>>
            FY <?=$fy?> <?= $fy === $defaultFY ? '(Current)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Select Quarter</label>
      <select id="quarterSelect" onchange="updateSelectedPeriod()" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
        <option value="Q1" <?= $selectedQuarter === 'Q1' ? 'selected' : '' ?>>Q1 (April - June)</option>
        <option value="Q2" <?= $selectedQuarter === 'Q2' ? 'selected' : '' ?>>Q2 (July - September)</option>
        <option value="Q3" <?= $selectedQuarter === 'Q3' ? 'selected' : '' ?>>Q3 (October - December)</option>
        <option value="Q4" <?= $selectedQuarter === 'Q4' ? 'selected' : '' ?>>Q4 (January - March)</option>
      </select>
    </div>
  </div>
</div>

<!-- FETCH CARDS -->
<div class="fetch-container">
  <!-- FETCH FROM SANDBOX API -->
  <div class="fetch-card">
    <h3>
      <span class="material-symbols-rounded">cloud_download</span>
      Fetch from Sandbox API
    </h3>
    <p>
      Pull invoices and challans directly from your Sandbox.co.in account for the selected period.
    </p>

    <div style="background: #e3f2fd; padding: 12px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; color: #1565c0;">
      <strong>ℹ️ API Status:</strong>
      <ul style="margin: 6px 0 0 0; padding-left: 20px;">
        <li>✓ Authentication: Working</li>
        <li>✓ API Connection: Established</li>
        <li>⏳ Data Endpoints: Not Available</li>
        <li>→ Use CSV import or manual entry below</li>
      </ul>
    </div>

    <p style="font-size: 11px; color: #666; margin: 0 0 12px 0; font-style: italic;">
      Data endpoints are not available from Sandbox API. Use CSV export from Sandbox web portal instead.
    </p>

    <md-filled-button onclick="location.href='invoices.php'" style="width: 100%; margin-bottom: 8px;">
      <span class="material-symbols-rounded" style="margin-right: 6px;">upload_file</span>
      Import Invoices from CSV
    </md-filled-button>

    <md-filled-button onclick="location.href='challans.php'" style="width: 100%;">
      <span class="material-symbols-rounded" style="margin-right: 6px;">upload_file</span>
      Import Challans from CSV
    </md-filled-button>

  </div>

  <!-- MANUAL INVOICE IMPORT -->
  <div class="fetch-card">
    <h3>
      <span class="material-symbols-rounded">receipt_long</span>
      Manual Invoice Import
    </h3>
    <p>
      Import invoices from CSV or manually enter them into the system.
    </p>

    <div style="background: #f5f5f5; padding: 12px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; color: #666;">
      <strong>Options:</strong>
      <ul style="margin: 6px 0 0 0; padding-left: 20px;">
        <li>Upload CSV with invoice data</li>
        <li>Manual entry via form</li>
        <li>Auto-calculated TDS amounts</li>
      </ul>
    </div>

    <md-filled-button onclick="location.href='invoices.php'" style="width: 100%;">
      <span class="material-symbols-rounded" style="margin-right: 6px;">receipt_long</span>
      Go to Invoices Page
    </md-filled-button>
  </div>

  <!-- MANUAL CHALLAN IMPORT -->
  <div class="fetch-card">
    <h3>
      <span class="material-symbols-rounded">account_balance</span>
      Manual Challan Import
    </h3>
    <p>
      Import TDS payment challans from CSV or manually enter them.
    </p>

    <div style="background: #f5f5f5; padding: 12px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; color: #666;">
      <strong>Options:</strong>
      <ul style="margin: 6px 0 0 0; padding-left: 20px;">
        <li>Upload CSV with challan data</li>
        <li>Manual entry via form</li>
        <li>Auto-matched to invoices</li>
      </ul>
    </div>

    <md-filled-button onclick="location.href='challans.php'" style="width: 100%;">
      <span class="material-symbols-rounded" style="margin-right: 6px;">account_balance</span>
      Go to Challans Page
    </md-filled-button>
  </div>
</div>

<!-- IMPORT WORKFLOW -->
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px; margin-bottom: 24px;">
  <h3 style="margin: 0 0 12px 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
    <span class="material-symbols-rounded">workflow</span>
    Recommended Import Workflow
  </h3>

  <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px;">
    <div style="background: #e3f2fd; padding: 12px; border-radius: 4px; border-left: 4px solid #2196f3;">
      <div style="font-weight: 600; color: #1976d2; margin-bottom: 4px;">Step 1</div>
      <div style="font-size: 12px; color: #555;">Go to Invoices page</div>
    </div>

    <div style="background: #f3e5f5; padding: 12px; border-radius: 4px; border-left: 4px solid #9c27b0;">
      <div style="font-weight: 600; color: #7b1fa2; margin-bottom: 4px;">Step 2</div>
      <div style="font-size: 12px; color: #555;">Add invoices (CSV or manual)</div>
    </div>

    <div style="background: #fce4ec; padding: 12px; border-radius: 4px; border-left: 4px solid #e91e63;">
      <div style="font-weight: 600; color: #ad1457; margin-bottom: 4px;">Step 3</div>
      <div style="font-size: 12px; color: #555;">Go to Challans page</div>
    </div>

    <div style="background: #fff3e0; padding: 12px; border-radius: 4px; border-left: 4px solid #ff9800;">
      <div style="font-weight: 600; color: #e65100; margin-bottom: 4px;">Step 4</div>
      <div style="font-size: 12px; color: #555;">Add challans (CSV or manual)</div>
    </div>
  </div>

  <div style="background: #f5f5f5; padding: 16px; border-radius: 4px; border-left: 4px solid #666;">
    <strong style="display: block; margin-bottom: 8px;">After importing data:</strong>
    <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
      <li>Go to <strong>Reconcile</strong> page to match invoices with challans</li>
      <li>Go to <strong>Analytics</strong> page to check compliance status</li>
      <li>Go to <strong>Reports</strong> page to generate forms</li>
      <li>Go to <strong>Compliance</strong> page for e-filing</li>
    </ul>
  </div>
</div>

<script>
function updateSelectedPeriod() {
  const fy = document.getElementById('fySelect').value;
  const quarter = document.getElementById('quarterSelect').value;
  const url = new URL(location.href);
  url.searchParams.set('fy', fy);
  url.searchParams.set('quarter', quarter);
  location.href = url.toString();
}

function fetchSandboxData(action) {
  const fy = document.getElementById('fySelect').value;
  const quarter = document.getElementById('quarterSelect').value;

  // Show progress
  const statusDiv = document.getElementById('sandboxStatus');
  const resultDiv = document.getElementById('sandboxResult');
  statusDiv.style.display = 'block';
  resultDiv.innerHTML = '';

  // Disable buttons while fetching
  const buttons = document.querySelectorAll('[onclick*="fetchSandboxData"]');
  buttons.forEach(btn => btn.disabled = true);

  // Make request
  fetch('/tds/api/fetch_from_sandbox.php?action=' + action + '&fy=' + fy + '&quarter=' + quarter)
    .then(response => response.json())
    .then(data => {
      statusDiv.style.display = 'none';
      buttons.forEach(btn => btn.disabled = false);

      if (data.status === 'success') {
        let html = '<div style="margin-top: 12px; padding: 12px; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 4px;">';
        html += '<div style="color: #2e7d32; font-weight: 600; margin-bottom: 8px;">✓ ' + data.message + '</div>';
        html += '<div style="font-size: 12px; color: #2e7d32;">';

        if (action === 'invoices') {
          html += '<strong>Invoices:</strong> ' + data.data.fetched + ' fetched, ' + data.data.imported + ' imported';
        } else if (action === 'challans') {
          html += '<strong>Challans:</strong> ' + data.data.fetched + ' fetched, ' + data.data.imported + ' imported';
        } else if (action === 'all') {
          html += '<strong>Invoices:</strong> ' + data.data.invoices.fetched + ' fetched, ' + data.data.invoices.imported + ' imported<br/>';
          html += '<strong>Challans:</strong> ' + data.data.challans.fetched + ' fetched, ' + data.data.challans.imported + ' imported<br/>';
          html += '<br/><strong>' + data.data.summary + '</strong>';
        }

        html += '</div></div>';
        resultDiv.innerHTML = html;
      } else {
        let html = '<div style="margin-top: 12px; padding: 12px; background: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px;">';
        html += '<div style="color: #c62828; font-weight: 600;">❌ Sync Failed</div>';
        html += '<div style="font-size: 12px; color: #c62828; margin-top: 4px;">' + data.message + '</div>';
        html += '<div style="font-size: 11px; color: #999; margin-top: 8px;">Tip: You can still manually import invoices and challans via CSV or manual entry.</div>';
        html += '</div>';
        resultDiv.innerHTML = html;
      }
    })
    .catch(error => {
      statusDiv.style.display = 'none';
      buttons.forEach(btn => btn.disabled = false);
      resultDiv.innerHTML = '<div style="margin-top: 12px; padding: 12px; background: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px;">' +
        '<div style="color: #c62828; font-weight: 600;">❌ Request Failed</div>' +
        '<div style="font-size: 12px; color: #c62828; margin-top: 4px;">' + error.message + '</div>' +
        '</div>';
    });
}

function fetchData(action) {
  // Legacy function for backward compatibility
  fetchSandboxData(action);
}
</script>

<?php include __DIR__.'/_layout_bottom.php'; ?>
