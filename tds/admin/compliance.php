<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/ComplianceAPI.php';
require_once __DIR__.'/../lib/helpers.php';

$page_title='E-Filing & Compliance';
include __DIR__.'/_layout_top.php';

// Get firm data
$firm = $pdo->query('SELECT id FROM firms LIMIT 1')->fetch();
$firm_id = $firm['id'] ?? null;

// Get current FY and quarter
$today = date('Y-m-d');
[$curFy, $curQ] = fy_quarter_from_date($today);

// Get parameters
$fy = $_GET['fy'] ?? $curFy;
$quarter = $_GET['quarter'] ?? $curQ;

// Initialize compliance API
$apiKey = getenv('SANDBOX_API_KEY') ?? '';
$compliance = new ComplianceAPI($pdo, $apiKey);

// Get recent filing jobs (with error handling if table doesn't exist yet)
$filingJobs = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM tds_filing_jobs ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $filingJobs = $stmt->fetchAll();
} catch (Exception $e) {
    // Table doesn't exist yet - this is OK, will be created in Phase 3
}

// Process actions
$actionResult = null;
$action = $_POST['action'] ?? '';

// Determine workflow step status based on actual data
$workflowStatus = [
    1 => 'pending',  // Invoice Entry
    2 => 'pending',  // Challan Entry
    3 => 'pending',  // Compliance Analysis
    4 => 'pending',  // Form Generation
    5 => 'pending',  // FVU Generation
    6 => 'pending',  // E-Filing Submission
    7 => 'pending'   // Acknowledgement & Certificates
];

// Check Step 1: Invoices exist
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE fy=? AND quarter=?");
    $stmt->execute([$fy, $quarter]);
    if ($stmt->fetchColumn() > 0) {
        $workflowStatus[1] = 'completed';
    }
} catch (Exception $e) {}

// Check Step 2: Challans exist
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM challans WHERE fy=? AND quarter=?");
    $stmt->execute([$fy, $quarter]);
    if ($stmt->fetchColumn() > 0) {
        $workflowStatus[2] = 'completed';
    }
} catch (Exception $e) {}

// Check Step 3: Reconciliation is complete
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE fy=? AND quarter=? AND allocation_status='complete'");
    $stmt->execute([$fy, $quarter]);
    $completeCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE fy=? AND quarter=?");
    $stmt->execute([$fy, $quarter]);
    $totalCount = $stmt->fetchColumn();

    if ($totalCount > 0 && $completeCount == $totalCount) {
        $workflowStatus[3] = 'completed';
    }
} catch (Exception $e) {}

// Check Step 4: Forms can be generated (invoices + challans exist)
if ($workflowStatus[1] === 'completed' && $workflowStatus[2] === 'completed') {
    $workflowStatus[4] = 'completed';
}

// Check Step 5: FVU generation (only if forms are ready)
if (!empty($filingJobs) && count($filingJobs) > 0) {
    $workflowStatus[5] = 'completed';
}

// Check Step 6 & 7: E-filing submission status
if (!empty($filingJobs)) {
    foreach ($filingJobs as $job) {
        if ($job['fvu_status'] === 'ready' || $job['fvu_status'] === 'READY') {
            $workflowStatus[6] = 'active';
        }
        if ($job['e_filing_status'] === 'ACKNOWLEDGED') {
            $workflowStatus[7] = 'completed';
        }
    }
}

// Set current active step
$activeStep = 5;  // Default to FVU generation
if ($workflowStatus[1] === 'pending') $activeStep = 1;
elseif ($workflowStatus[2] === 'pending') $activeStep = 2;
elseif ($workflowStatus[3] === 'pending') $activeStep = 3;
elseif ($workflowStatus[4] === 'pending') $activeStep = 4;
elseif ($workflowStatus[6] === 'pending') $activeStep = 6;
elseif ($workflowStatus[7] === 'pending') $activeStep = 7;

if ($workflowStatus[$activeStep] === 'pending') {
    $workflowStatus[$activeStep] = 'active';
}

// Process actions
$actionResult = null;
$action = $_POST['action'] ?? '';

if ($action === 'generate_fvu' && $firm_id) {
    try {
        // This would typically use the form content from reports.generateForm26Q()
        $formContent = "Sample Form 26Q NS1 Format";
        $fvu = $compliance->generateFVU($formContent, '26Q', $firm_id);
        $actionResult = ['status' => 'success', 'message' => 'FVU generated successfully', 'data' => $fvu];
    } catch (Exception $e) {
        $actionResult = ['status' => 'error', 'message' => $e->getMessage()];
    }
} elseif ($action === 'check_fvu' && !empty($_POST['job_uuid'])) {
    try {
        $status = $compliance->checkFVUStatus($_POST['job_uuid']);
        $actionResult = ['status' => 'success', 'message' => 'FVU status checked', 'data' => $status];
    } catch (Exception $e) {
        $actionResult = ['status' => 'error', 'message' => $e->getMessage()];
    }
}

?>

<style>
.workflow-step {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  margin-bottom: 12px;
}
.workflow-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e3f2fd;
  color: #1976d2;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 18px;
  flex-shrink: 0;
}
.workflow-number.completed {
  background: #c8e6c9;
  color: #2e7d32;
}
.workflow-number.active {
  background: #fff9c4;
  color: #f57f17;
  animation: pulse 2s infinite;
}
.workflow-number.pending {
  background: #f5f5f5;
  color: #999;
}
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(245, 127, 23, 0.7); }
  50% { box-shadow: 0 0 0 10px rgba(245, 127, 23, 0); }
}
.workflow-content {
  flex: 1;
}
.workflow-title {
  font-weight: 600;
  font-size: 15px;
  margin-bottom: 4px;
}
.workflow-description {
  font-size: 12px;
  color: #666;
}
.status-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}
.status-badge.completed {
  background: #c8e6c9;
  color: #2e7d32;
}
.status-badge.active {
  background: #fff9c4;
  color: #f57f17;
}
.status-badge.pending {
  background: #f5f5f5;
  color: #666;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <h2 style="margin: 0;">E-Filing & Compliance</h2>
  <md-filled-button onclick="location.href='dashboard.php'">
    <span class="material-symbols-rounded" style="margin-right: 6px;">arrow_back</span>
    Back to Dashboard
  </md-filled-button>
</div>

<!-- 7-STEP WORKFLOW -->
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px; margin-bottom: 24px;">
  <h3 style="margin: 0 0 20px 0; font-size: 16px;">E-Filing Workflow (7 Steps)</h3>

  <?php
  $workflowSteps = [
    1 => ['title' => 'Invoice Entry & Validation', 'desc' => 'Add invoices and validate TDS calculations'],
    2 => ['title' => 'Challan Entry & Reconciliation', 'desc' => 'Add bank challans and reconcile with invoices'],
    3 => ['title' => 'Compliance Analysis', 'desc' => 'Run compliance checks and risk assessment'],
    4 => ['title' => 'Form Generation', 'desc' => 'Generate Form 26Q/24Q in NS1 format'],
    5 => ['title' => 'FVU Generation', 'desc' => 'Submit to Sandbox for validation and get FVU'],
    6 => ['title' => 'E-Filing Submission', 'desc' => 'Submit FVU + Form 27A to Tax Authority'],
    7 => ['title' => 'Acknowledgement & Certificates', 'desc' => 'Receive ACK number and download certificates']
  ];

  for ($i = 1; $i <= 7; $i++):
    $status = $workflowStatus[$i];
    $step = $workflowSteps[$i];
  ?>
  <div class="workflow-step">
    <div class="workflow-number <?=$status?>">
      <?php if ($status === 'completed'): ?>
        <span style="font-size: 20px;">✓</span>
      <?php else: ?>
        <?=$i?>
      <?php endif; ?>
    </div>
    <div class="workflow-content">
      <div class="workflow-title"><?=$step['title']?></div>
      <div class="workflow-description"><?=$step['desc']?></div>
    </div>
    <span class="status-badge <?=$status?>">
      <?php
        if ($status === 'completed') echo '✓ Completed';
        elseif ($status === 'active') echo '⏳ In Progress';
        else echo '○ Pending';
      ?>
    </span>
  </div>
  <?php endfor; ?>
</div>

<!-- QUICK ACTIONS -->
<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
  <div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px;">
    <h3 style="margin: 0 0 16px 0; font-size: 15px;">Step 5: Generate FVU</h3>
    <p style="font-size: 13px; color: #666; margin: 0 0 16px 0;">
      Submit your Form 26Q to Sandbox for validation. This generates the File Validation Utility (FVU).
    </p>
    <form method="POST" style="display: flex; flex-direction: column; gap: 12px;">
      <input type="hidden" name="action" value="generate_fvu">
      <input type="text" name="fy" value="<?=htmlspecialchars($fy)?>" placeholder="FY (e.g., 2025-26)" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; box-sizing: border-box;">
      <input type="text" name="quarter" value="<?=htmlspecialchars($quarter)?>" placeholder="Quarter (e.g., Q2)" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; box-sizing: border-box;">
      <md-filled-button type="submit">
        <span class="material-symbols-rounded" style="margin-right: 6px;">upload</span>
        Generate FVU Now
      </md-filled-button>
    </form>
  </div>

  <div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px;">
    <h3 style="margin: 0 0 16px 0; font-size: 15px;">Step 6: Check FVU Status</h3>
    <p style="font-size: 13px; color: #666; margin: 0 0 16px 0;">
      Check if your FVU is ready for e-filing. Once ready, you can proceed to submission.
    </p>
    <form method="POST" style="display: flex; flex-direction: column; gap: 12px;">
      <input type="hidden" name="action" value="check_fvu">
      <input type="text" name="job_uuid" placeholder="Enter Job UUID" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; box-sizing: border-box;">
      <md-filled-button type="submit">
        <span class="material-symbols-rounded" style="margin-right: 6px;">refresh</span>
        Check Status
      </md-filled-button>
    </form>
  </div>
</div>

<?php if ($actionResult): ?>
  <?php if ($actionResult['status'] === 'success'): ?>
    <div style="padding: 16px; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 4px; margin-bottom: 24px;">
      <div style="color: #2e7d32;">
        <strong>✓ <?=htmlspecialchars($actionResult['message'])?></strong>
        <?php if (!empty($actionResult['data'])): ?>
          <pre style="margin: 8px 0 0 0; background: white; padding: 12px; border-radius: 4px; font-size: 11px; overflow-x: auto;">
<?=htmlspecialchars(json_encode($actionResult['data'], JSON_PRETTY_PRINT))?>
          </pre>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div style="padding: 16px; background: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px; margin-bottom: 24px;">
      <div style="color: #c62828;">
        <strong>❌ Error:</strong> <?=htmlspecialchars($actionResult['message'])?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<!-- RECENT FILING JOBS -->
<?php if (!empty($filingJobs)): ?>
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px;">
  <h3 style="margin: 0 0 16px 0; font-size: 16px;">Recent Filing Jobs</h3>

  <div class="table-wrap">
    <table class="table" style="font-size: 12px;">
      <thead>
        <tr>
          <th>Job ID</th>
          <th>FY/Quarter</th>
          <th>Form Type</th>
          <th>FVU Status</th>
          <th>E-Filing Status</th>
          <th>ACK No</th>
          <th>Created</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($filingJobs as $job): ?>
          <tr>
            <td><code style="font-size: 11px;"><?=substr($job['job_uuid'], 0, 8)?>...</code></td>
            <td><?=htmlspecialchars($job['fy'] ?? '-')?> <?=htmlspecialchars($job['quarter'] ?? '-')?></td>
            <td><?=htmlspecialchars($job['form_type'] ?? '-')?></td>
            <td>
              <span style="padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: 600; background: <?=($job['fvu_status']==='ready'||$job['fvu_status']==='READY')?'#c8e6c9':'#ffe0b2'?>; color: <?=($job['fvu_status']==='ready'||$job['fvu_status']==='READY')?'#2e7d32':'#e65100'?>;">
                <?=htmlspecialchars($job['fvu_status'] ?? 'PENDING')?>
              </span>
            </td>
            <td>
              <span style="padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: 600; background: <?=($job['e_filing_status']==='ACKNOWLEDGED')?'#c8e6c9':'#f5f5f5'?>; color: <?=($job['e_filing_status']==='ACKNOWLEDGED')?'#2e7d32':'#666'?>;">
                <?=htmlspecialchars($job['e_filing_status'] ?? 'PENDING')?>
              </span>
            </td>
            <td><code style="font-size: 10px;"><?=htmlspecialchars($job['ack_no'] ?? '-')?></code></td>
            <td style="font-size: 11px;"><?=date('M d H:i', strtotime($job['created_at'] ?? 'now'))?></td>
            <td><a href="filing-status.php?job_uuid=<?=urlencode($job['job_uuid'])?>" style="color: #1976d2; text-decoration: none;">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e0e0e0;">
    <md-filled-button onclick="location.href='filing-status.php'">
      <span class="material-symbols-rounded" style="margin-right: 6px;">check_circle</span>
      View All Filing Status
    </md-filled-button>
  </div>
</div>
<?php else: ?>
<div style="padding: 24px; background: #f5f5f5; border-radius: 8px; text-align: center;">
  <span class="material-symbols-rounded" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 12px;">info</span>
  <p style="color: #999; margin: 0;">No filing jobs yet. Generate and submit FVU to start e-filing.</p>
</div>
<?php endif; ?>

<?php include __DIR__.'/_layout_bottom.php'; ?>
