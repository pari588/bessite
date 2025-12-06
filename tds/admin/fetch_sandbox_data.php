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
  <h2 style="margin: 0;">Fetch Data from Sandbox API</h2>
  <md-filled-button onclick="location.href='dashboard.php'">
    <span class="material-symbols-rounded" style="margin-right: 6px;">arrow_back</span>
    Back to Dashboard
  </md-filled-button>
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
  <!-- FETCH INVOICES -->
  <div class="fetch-card">
    <h3>
      <span class="material-symbols-rounded">receipt_long</span>
      Fetch Invoices
    </h3>
    <p>
      Fetch all invoices from Sandbox API for the selected period. This will import them into your local database with TDS calculations.
    </p>

    <div style="background: #f5f5f5; padding: 12px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; color: #666;">
      <strong>What gets imported:</strong>
      <ul style="margin: 6px 0 0 0; padding-left: 20px;">
        <li>Invoice number and date</li>
        <li>Vendor name and PAN</li>
        <li>Base amount and TDS section</li>
        <li>Calculated TDS amount</li>
      </ul>
    </div>

    <md-filled-button onclick="fetchData('invoices')" style="width: 100%;">
      <span class="material-symbols-rounded" style="margin-right: 6px;">download</span>
      Fetch Invoices
    </md-filled-button>

    <div id="invoicesStatus" class="fetch-progress">
      <div class="progress-bar">
        <div class="progress-fill" style="animation: progress 2s ease-in-out infinite;"></div>
      </div>
      <div style="font-size: 12px; color: #666; text-align: center;">
        Fetching invoices... Please wait
      </div>
    </div>
    <div id="invoicesResult"></div>
  </div>

  <!-- FETCH CHALLANS -->
  <div class="fetch-card">
    <h3>
      <span class="material-symbols-rounded">account_balance</span>
      Fetch Challans
    </h3>
    <p>
      Fetch all TDS payment challans from Sandbox API for the selected period. These will be matched with invoices during reconciliation.
    </p>

    <div style="background: #f5f5f5; padding: 12px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; color: #666;">
      <strong>What gets imported:</strong>
      <ul style="margin: 6px 0 0 0; padding-left: 20px;">
        <li>BSR code and serial number</li>
        <li>Challan date and bank code</li>
        <li>TDS amount paid</li>
      </ul>
    </div>

    <md-filled-button onclick="fetchData('challans')" style="width: 100%;">
      <span class="material-symbols-rounded" style="margin-right: 6px;">download</span>
      Fetch Challans
    </md-filled-button>

    <div id="challansStatus" class="fetch-progress">
      <div class="progress-bar">
        <div class="progress-fill" style="animation: progress 2s ease-in-out infinite;"></div>
      </div>
      <div style="font-size: 12px; color: #666; text-align: center;">
        Fetching challans... Please wait
      </div>
    </div>
    <div id="challansResult"></div>
  </div>
</div>

<!-- FETCH ALL -->
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px; margin-bottom: 24px;">
  <h3 style="margin: 0 0 12px 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
    <span class="material-symbols-rounded">sync</span>
    Fetch All Data at Once
  </h3>
  <p style="font-size: 13px; color: #666; margin: 0 0 16px 0;">
    Fetch and import both invoices and challans in a single operation. This is the fastest way to get all your data into the system.
  </p>

  <md-filled-button onclick="fetchData('all')" style="width: 100%;">
    <span class="material-symbols-rounded" style="margin-right: 6px;">download_2</span>
    Fetch Invoices & Challans
  </md-filled-button>

  <div id="allStatus" class="fetch-progress">
    <div class="progress-bar">
      <div class="progress-fill" style="animation: progress 2s ease-in-out infinite;"></div>
    </div>
    <div style="font-size: 12px; color: #666; text-align: center;">
      Fetching all data... Please wait
    </div>
  </div>
  <div id="allResult"></div>
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

function fetchData(action) {
  const fy = document.getElementById('fySelect').value;
  const quarter = document.getElementById('quarterSelect').value;

  // Show progress
  const statusDiv = document.getElementById(action + 'Status');
  const resultDiv = document.getElementById(action + 'Result');
  statusDiv.style.display = 'block';
  resultDiv.innerHTML = '';

  // Make request
  fetch('/tds/api/fetch_from_sandbox.php?action=' + action + '&fy=' + fy + '&quarter=' + quarter)
    .then(response => response.json())
    .then(data => {
      statusDiv.style.display = 'none';

      if (data.status === 'success') {
        let html = '<div style="margin-top: 12px; padding: 12px; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 4px;">';
        html += '<div style="color: #2e7d32; font-weight: 500; margin-bottom: 8px;">✓ ' + data.message + '</div>';
        html += '<div style="font-size: 12px; color: #2e7d32;">';

        if (action === 'invoices') {
          html += 'Fetched: ' + data.data.fetched + ' invoices<br/>';
          html += 'Imported: ' + data.data.imported + ' invoices';
        } else if (action === 'challans') {
          html += 'Fetched: ' + data.data.fetched + ' challans<br/>';
          html += 'Imported: ' + data.data.imported + ' challans';
        } else if (action === 'all') {
          html += 'Invoices: ' + data.data.invoices.fetched + ' fetched, ' + data.data.invoices.imported + ' imported<br/>';
          html += 'Challans: ' + data.data.challans.fetched + ' fetched, ' + data.data.challans.imported + ' imported<br/>';
          html += '<strong>' + data.data.summary + '</strong>';
        }

        html += '</div></div>';
        resultDiv.innerHTML = html;
      } else {
        let html = '<div style="margin-top: 12px; padding: 12px; background: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px;">';
        html += '<div style="color: #c62828; font-weight: 500;">❌ Error</div>';
        html += '<div style="font-size: 12px; color: #c62828; margin-top: 4px;">' + data.message + '</div>';
        html += '</div>';
        resultDiv.innerHTML = html;
      }
    })
    .catch(error => {
      statusDiv.style.display = 'none';
      resultDiv.innerHTML = '<div style="margin-top: 12px; padding: 12px; background: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px;">' +
        '<div style="color: #c62828; font-weight: 500;">❌ Request failed</div>' +
        '<div style="font-size: 12px; color: #c62828; margin-top: 4px;">' + error.message + '</div>' +
        '</div>';
    });
}
</script>

<?php include __DIR__.'/_layout_bottom.php'; ?>
