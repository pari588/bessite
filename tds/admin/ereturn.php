<?php
require_once __DIR__.'/../lib/auth.php';
auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/SandboxTDSAPI.php';

$page_title = 'E-Return Filing';
include __DIR__.'/_layout_top.php';

// Get current firm
$stmt = $pdo->prepare("SELECT id, display_name, tan FROM firms LIMIT 1");
$stmt->execute();
$firm = $stmt->fetch();
$firmId = $firm['id'] ?? null;

// Get available FYs
$availableFYs = [];
$currentYear = (int)date('Y');
$currentMonth = (int)date('m');
$startYear = ($currentMonth < 4) ? $currentYear - 1 : $currentYear;

for ($i = $startYear + 2; $i >= $startYear - 5; $i--) {
    $fy = $i . '-' . substr($i + 1, 2);
    $availableFYs[] = $fy;
}

$defaultFY = ($startYear) . '-' . substr($startYear + 1, 2);
$selectedFY = $_GET['fy'] ?? $_POST['fy'] ?? $defaultFY;
$selectedQuarter = $_GET['quarter'] ?? $_POST['quarter'] ?? 'Q1';

// Get filing jobs for current firm
$recentJobs = [];
try {
    // Try to get filing jobs if table exists
    $stmt = $pdo->prepare("
        SELECT * FROM tds_filing_jobs
        WHERE firm_id = ? AND fy = ? AND quarter = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$firmId, $selectedFY, $selectedQuarter]);
    $recentJobs = $stmt->fetchAll();
} catch (Exception $e) {
    // Table doesn't exist, continue without filing jobs
    $recentJobs = [];
}

// Determine workflow completion status
$invoiceCount = 0;
$challanCount = 0;
$unreconciled = 0;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE firm_id = ? AND fy = ? AND quarter = ?");
    $stmt->execute([$firmId, $selectedFY, $selectedQuarter]);
    $invoiceCount = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $invoiceCount = 0;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM challans WHERE firm_id = ? AND fy = ? AND quarter = ?");
    $stmt->execute([$firmId, $selectedFY, $selectedQuarter]);
    $challanCount = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $challanCount = 0;
}

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM invoices i
        LEFT JOIN challans c ON c.fy = i.fy AND c.quarter = i.quarter AND c.firm_id = i.firm_id
        WHERE i.firm_id = ? AND i.fy = ? AND i.quarter = ? AND c.id IS NULL
    ");
    $stmt->execute([$firmId, $selectedFY, $selectedQuarter]);
    $unreconciled = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $unreconciled = 0;
}

// Workflow status determination
$step1_complete = $invoiceCount > 0;
$step2_complete = $challanCount > 0 && $unreconciled == 0;
$step3_started = !empty($recentJobs);
$step3_complete = !empty($recentJobs) && ($recentJobs[0]['status'] ?? null) === 'completed';
$step4_started = !empty($recentJobs);
$step5_complete = !empty($recentJobs) && in_array($recentJobs[0]['status'] ?? '', ['submitted', 'acknowledged', 'accepted']);
?>

<style>
.ereturn-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

@media (max-width: 1024px) {
  .ereturn-container {
    grid-template-columns: 1fr;
  }
}

.ereturn-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
  transition: all 0.3s;
}

.ereturn-card:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.ereturn-card h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  color: #1976d2;
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.status-pending {
  background: #fff3e0;
  color: #e65100;
}

.status-processing {
  background: #e3f2fd;
  color: #1565c0;
}

.status-completed {
  background: #e8f5e9;
  color: #2e7d32;
}

.status-failed {
  background: #ffebee;
  color: #c62828;
}

.workflow-step {
  display: flex;
  gap: 12px;
  padding: 12px;
  border-left: 4px solid #1976d2;
  background: #f5f5f5;
  border-radius: 4px;
  margin-bottom: 12px;
}

.workflow-step.completed {
  border-left-color: #4caf50;
  background: #f1f8e9;
}

.workflow-step.current {
  border-left-color: #ff9800;
  background: #fff8f0;
}

.workflow-step.pending {
  border-left-color: #ccc;
  background: #f5f5f5;
  opacity: 0.6;
}

.workflow-number {
  background: #1976d2;
  color: white;
  min-width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 12px;
}

.workflow-step.completed .workflow-number {
  background: #4caf50;
}

.workflow-step.pending .workflow-number {
  background: #ccc;
}

.workflow-content {
  flex: 1;
}

.workflow-content strong {
  display: block;
  margin-bottom: 4px;
}

.workflow-content p {
  margin: 0;
  font-size: 12px;
  color: #666;
}

.invoice-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.stat-box {
  background: #f5f5f5;
  padding: 12px;
  border-radius: 4px;
  text-align: center;
}

.stat-value {
  font-size: 18px;
  font-weight: 600;
  color: #1976d2;
}

.stat-label {
  font-size: 11px;
  color: #999;
  margin-top: 4px;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <h2 style="margin: 0;">E-Return Filing (Form 26Q)</h2>
  <div style="display: flex; gap: 8px;">
    <md-filled-button onclick="location.href='reports.php'">
      <span class="material-symbols-rounded" style="margin-right: 6px;">description</span>
      Generate Form
    </md-filled-button>
    <md-filled-button onclick="location.href='dashboard.php'">
      <span class="material-symbols-rounded" style="margin-right: 6px;">arrow_back</span>
      Back
    </md-filled-button>
  </div>
</div>

<!-- FY AND QUARTER SELECTOR -->
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px; margin-bottom: 24px;">
  <h3 style="margin: 0 0 16px 0; font-size: 16px;">Select Filing Period</h3>

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
    <div>
      <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Financial Year</label>
      <select id="fySelect" onchange="updatePeriod()" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
        <?php foreach ($availableFYs as $fy): ?>
          <option value="<?=$fy?>" <?= $fy === $selectedFY ? 'selected' : '' ?>>
            FY <?=$fy?> <?= $fy === $defaultFY ? '(Current)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Quarter</label>
      <select id="quarterSelect" onchange="updatePeriod()" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
        <option value="Q1" <?= $selectedQuarter === 'Q1' ? 'selected' : '' ?>>Q1 (April - June)</option>
        <option value="Q2" <?= $selectedQuarter === 'Q2' ? 'selected' : '' ?>>Q2 (July - September)</option>
        <option value="Q3" <?= $selectedQuarter === 'Q3' ? 'selected' : '' ?>>Q3 (October - December)</option>
        <option value="Q4" <?= $selectedQuarter === 'Q4' ? 'selected' : '' ?>>Q4 (January - March)</option>
      </select>
    </div>
  </div>
</div>

<!-- WORKFLOW OVERVIEW -->
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px; margin-bottom: 24px;">
  <h3 style="margin: 0 0 16px 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
    <span class="material-symbols-rounded">timeline</span>
    E-Return Filing Workflow
  </h3>

  <!-- STEP 1: DATA COLLECTION -->
  <div class="workflow-step <?= $step1_complete ? 'completed' : ($invoiceCount > 0 ? 'current' : 'pending') ?>">
    <div class="workflow-number"><?= $step1_complete ? '✓' : '1' ?></div>
    <div class="workflow-content">
      <strong>Step 1: Data Collection</strong>
      <p>Import invoices and challans into the system via CSV or manual entry</p>
      <div style="font-size: 11px; color: #999; margin-top: 6px;">
        <?= $invoiceCount > 0 ? "✓ $invoiceCount invoice(s) added" : "⏳ Awaiting invoices" ?>
      </div>
    </div>
  </div>

  <!-- STEP 2: RECONCILIATION -->
  <div class="workflow-step <?= $step2_complete ? 'completed' : ($challanCount > 0 ? 'current' : 'pending') ?>">
    <div class="workflow-number"><?= $step2_complete ? '✓' : '2' ?></div>
    <div class="workflow-content">
      <strong>Step 2: Reconciliation</strong>
      <p>Match invoices with challans and verify TDS amounts</p>
      <div style="font-size: 11px; color: #999; margin-top: 6px;">
        <?php if ($step1_complete): ?>
          <?= $challanCount > 0 ? "✓ $challanCount challan(s) matched" : "⏳ Add challans to match with invoices" ?>
        <?php else: ?>
          ⏳ Complete Step 1 first
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- STEP 3: GENERATE FORM 26Q -->
  <div class="workflow-step <?= $step3_complete ? 'completed' : ($step2_complete ? 'current' : 'pending') ?>">
    <div class="workflow-number"><?= $step3_complete ? '✓' : '3' ?></div>
    <div class="workflow-content">
      <strong>Step 3: Generate Form 26Q</strong>
      <p>Create official Form 26Q in NS1 format required by tax authority</p>
      <div style="font-size: 11px; color: #999; margin-top: 6px;">
        <?php if ($step2_complete): ?>
          <?= $step3_started ? "✓ Form generated" : "⏳ Ready to generate" ?>
        <?php else: ?>
          ⏳ Complete reconciliation first
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- STEP 4: GENERATE FVU -->
  <div class="workflow-step <?= $step4_started ? 'current' : 'pending' ?>">
    <div class="workflow-number"><?= $step4_started ? '⟳' : '4' ?></div>
    <div class="workflow-content">
      <strong>Step 4: Generate FVU</strong>
      <p>File Validation Utility validates form before submission</p>
      <div style="font-size: 11px; color: #999; margin-top: 6px;">
        <?php if ($step3_started): ?>
          <?= $step4_started ? "✓ Processing..." : "⏳ Generate form to proceed" ?>
        <?php else: ?>
          ⏳ Complete Step 3 first
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- STEP 5: SUBMIT E-RETURN -->
  <div class="workflow-step <?= $step5_complete ? 'completed' : ($step4_started ? 'current' : 'pending') ?>">
    <div class="workflow-number"><?= $step5_complete ? '✓' : '5' ?></div>
    <div class="workflow-content">
      <strong>Step 5: Submit E-Return</strong>
      <p>Submit FVU and Form 27A to tax authority for e-filing</p>
      <div style="font-size: 11px; color: #999; margin-top: 6px;">
        <?php if ($step4_started): ?>
          <?= $step5_complete ? "✓ Return submitted" : "⏳ FVU generation required" ?>
        <?php else: ?>
          ⏳ Complete Step 4 first
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- STEP 6: TRACK STATUS -->
  <div class="workflow-step <?= $step5_complete ? 'current' : 'pending' ?>">
    <div class="workflow-number">6</div>
    <div class="workflow-content">
      <strong>Step 6: Track Status</strong>
      <p>Monitor filing status and receive acknowledgement from tax authority</p>
      <div style="font-size: 11px; color: #999; margin-top: 6px;">
        <?= $step5_complete ? "✓ View status below" : "⏳ Submit return to enable tracking" ?>
      </div>
    </div>
  </div>
</div>

<!-- FILING ACTIONS -->
<div class="ereturn-container">
  <!-- GENERATE FORM 26Q -->
  <div class="ereturn-card">
    <h3>
      <span class="material-symbols-rounded">description</span>
      Generate Form 26Q
    </h3>
    <p style="font-size: 13px; color: #666; margin-bottom: 16px;">
      Create official Form 26Q in NS1 format. This form contains all deductions made during the period.
    </p>

    <div style="background: #f5f5f5; padding: 12px; border-radius: 4px; margin-bottom: 16px; font-size: 12px;">
      <strong>What it includes:</strong>
      <ul style="margin: 6px 0 0 0; padding-left: 20px;">
        <li>All deductees and their TAN</li>
        <li>Total amounts deducted</li>
        <li>TDS section-wise breakdown</li>
        <li>Payment challans details</li>
      </ul>
    </div>

    <md-filled-button onclick="location.href='reports.php?action=generate&fy=<?=$selectedFY?>&quarter=<?=$selectedQuarter?>'" style="width: 100%;">
      <span class="material-symbols-rounded" style="margin-right: 6px;">description</span>
      Generate Form 26Q
    </md-filled-button>
  </div>

  <!-- SUBMISSION STATUS -->
  <div class="ereturn-card">
    <h3>
      <span class="material-symbols-rounded">check_circle</span>
      Filing Status
    </h3>
    <p style="font-size: 13px; color: #666; margin-bottom: 16px;">
      Track the status of your e-return submissions and receive acknowledgements.
    </p>

    <?php if (!empty($recentJobs)): ?>
      <div style="background: #f5f5f5; padding: 12px; border-radius: 4px; margin-bottom: 16px; font-size: 12px; max-height: 150px; overflow-y: auto;">
        <strong>Recent Filings:</strong>
        <div style="margin-top: 8px;">
          <?php foreach ($recentJobs as $job): ?>
            <div style="margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #ddd;">
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <strong><?=$job['fy']?> <?=$job['quarter']?></strong>
                <span class="status-badge status-<?=$job['status']?>"><?=ucfirst($job['status'])?></span>
              </div>
              <div style="font-size: 11px; color: #999; margin-top: 4px;">
                <?=date('d-m-Y H:i', strtotime($job['created_at']))?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div style="background: #f5f5f5; padding: 12px; border-radius: 4px; margin-bottom: 16px; font-size: 12px; color: #999;">
        No filings yet for this period
      </div>
    <?php endif; ?>

    <md-filled-button onclick="location.href='compliance.php?fy=<?=$selectedFY?>&quarter=<?=$selectedQuarter?>'" style="width: 100%;">
      <span class="material-symbols-rounded" style="margin-right: 6px;">check_circle</span>
      View All Filings
    </md-filled-button>
  </div>
</div>

<!-- DATA SUMMARY -->
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px; margin-bottom: 24px;">
  <h3 style="margin: 0 0 16px 0; font-size: 16px;">Data Summary for <?=$selectedFY?> <?=$selectedQuarter?></h3>

  <?php
  // Get invoice and challan counts
  $invoiceData = [
    'invoice_count' => 0,
    'total_amount' => 0,
    'total_tds' => 0
  ];
  $challanData = [
    'challan_count' => 0,
    'total_paid' => 0
  ];

  try {
    $stmt = $pdo->prepare("
      SELECT
        COUNT(*) as invoice_count,
        COALESCE(SUM(base_amount), 0) as total_amount,
        COALESCE(SUM(total_tds), 0) as total_tds
      FROM invoices
      WHERE firm_id = ? AND fy = ? AND quarter = ?
    ");
    $stmt->execute([$firmId, $selectedFY, $selectedQuarter]);
    $invoiceData = $stmt->fetch();
  } catch (Exception $e) {
    // Table doesn't exist
  }

  try {
    $stmt = $pdo->prepare("
      SELECT
        COUNT(*) as challan_count,
        COALESCE(SUM(amount_tds), 0) as total_paid
      FROM challans
      WHERE firm_id = ? AND fy = ? AND quarter = ?
    ");
    $stmt->execute([$firmId, $selectedFY, $selectedQuarter]);
    $challanData = $stmt->fetch();
  } catch (Exception $e) {
    // Table doesn't exist
  }

  $invoiceCount = $invoiceData['invoice_count'] ?? 0;
  $totalAmount = $invoiceData['total_amount'] ?? 0;
  $totalTDS = $invoiceData['total_tds'] ?? 0;
  $challanCount = $challanData['challan_count'] ?? 0;
  $totalPaid = $challanData['total_paid'] ?? 0;
  $outstanding = $totalTDS - $totalPaid;
  ?>

  <div class="invoice-stats">
    <div class="stat-box">
      <div class="stat-value"><?=$invoiceCount?></div>
      <div class="stat-label">Invoices</div>
    </div>
    <div class="stat-box">
      <div class="stat-value">₹<?=number_format($totalAmount, 0)?></div>
      <div class="stat-label">Total Amount</div>
    </div>
    <div class="stat-box">
      <div class="stat-value">₹<?=number_format($totalTDS, 0)?></div>
      <div class="stat-label">Total TDS</div>
    </div>
  </div>

  <div class="invoice-stats">
    <div class="stat-box">
      <div class="stat-value"><?=$challanCount?></div>
      <div class="stat-label">Challans</div>
    </div>
    <div class="stat-box">
      <div class="stat-value">₹<?=number_format($totalPaid, 0)?></div>
      <div class="stat-label">TDS Paid</div>
    </div>
    <div class="stat-box" style="<?= $outstanding > 0 ? 'background: #ffebee;' : 'background: #e8f5e9;' ?>">
      <div class="stat-value" style="<?= $outstanding > 0 ? 'color: #c62828;' : 'color: #2e7d32;' ?>">₹<?=number_format($outstanding, 0)?></div>
      <div class="stat-label"><?= $outstanding > 0 ? 'Outstanding' : 'Reconciled' ?></div>
    </div>
  </div>

  <?php if ($outstanding > 0): ?>
    <div style="background: #ffebee; border-left: 4px solid #c62828; padding: 12px; border-radius: 4px; margin-top: 16px;">
      <div style="color: #c62828; font-weight: 600; margin-bottom: 4px;">⚠️ TDS Amount Not Fully Paid</div>
      <div style="font-size: 12px; color: #666;">
        Outstanding TDS of ₹<?=number_format($outstanding, 0)?> needs to be deposited. Please verify all challans have been added.
      </div>
    </div>
  <?php elseif ($invoiceCount > 0 && $challanCount > 0): ?>
    <div style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 12px; border-radius: 4px; margin-top: 16px;">
      <div style="color: #2e7d32; font-weight: 600; margin-bottom: 4px;">✓ Data Reconciled</div>
      <div style="font-size: 12px; color: #666;">
        All TDS deductions have been matched with payment challans. You can proceed with filing.
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- NEXT STEPS -->
<div style="background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 8px; padding: 20px;">
  <h3 style="margin: 0 0 12px 0; font-size: 16px; color: #1565c0;">📋 Next Steps</h3>
  <ol style="margin: 0; padding-left: 20px; font-size: 13px; color: #555;">
    <li>Click "Generate Form 26Q" to create the official form</li>
    <li>Review the generated form for accuracy</li>
    <li>Submit Form 26Q and challans for FVU validation</li>
    <li>After FVU is generated, submit e-return to tax authority</li>
    <li>Track filing status in the "Filing Status" section</li>
  </ol>
</div>

<script>
function updatePeriod() {
  const fy = document.getElementById('fySelect').value;
  const quarter = document.getElementById('quarterSelect').value;
  const url = new URL(location.href);
  url.searchParams.set('fy', fy);
  url.searchParams.set('quarter', quarter);
  location.href = url.toString();
}
</script>

<?php include __DIR__.'/_layout_bottom.php'; ?>
