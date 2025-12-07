<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/AnalyticsAPI.php';
require_once __DIR__.'/../lib/helpers.php';

$page_title='Analytics & Compliance';
include __DIR__.'/_layout_top.php';

// Get firm data
$firm = $pdo->query('SELECT id FROM firms LIMIT 1')->fetch();
$firm_id = $firm['id'] ?? null;

// Get current FY and quarter
$today = date('Y-m-d');
[$curFy, $curQ] = fy_quarter_from_date($today);

// Get parameters from URL
$fy = $_GET['fy'] ?? $curFy;
$quarter = $_GET['quarter'] ?? $curQ;

// Initialize analytics API
$analytics = new AnalyticsAPI($pdo);
$complianceResult = null;
$riskAssessment = null;
$deducteeAnalysis = null;
$error = null;

// Check if there's any data first
$hasData = false;
if ($firm_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE fy=? AND quarter=?");
        $stmt->execute([$fy, $quarter]);
        $hasData = $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        $hasData = false;
    }
}

if ($firm_id && $hasData) {
    try {
        // Perform compliance check
        $complianceResult = $analytics->performTDSComplianceCheck($firm_id, $fy, $quarter);

        // Assess risk
        $riskAssessment = $analytics->assessFilingRisk($firm_id, $fy, $quarter);

        // Analyze deductees
        $deducteeAnalysis = $analytics->analyzeDeducteeTDS($firm_id, $fy, $quarter);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Map check names to display labels
$checkLabels = [
    'invoices_exist' => 'Invoices Exist',
    'tds_calculation' => 'TDS Calculations Valid',
    'challan_matching' => 'Challan Matching',
    'pan_validation' => 'Deductee PAN Validation',
    'amount_validation' => 'Amount Validation',
    'duplicate_check' => 'Duplicate Check',
    'date_validation' => 'Date Range Validation',
    'allocation_status' => 'Allocation Status'
];

?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <h2 style="margin: 0;">Analytics & Compliance Check</h2>
  <div style="display: flex; gap: 8px;">
    <md-filled-tonal-button onclick="location.reload()">
      <span class="material-symbols-rounded" style="margin-right: 6px;">refresh</span>
      Refresh
    </md-filled-tonal-button>
    <md-filled-button onclick="goToDashboard()">
      <span class="material-symbols-rounded" style="margin-right: 6px;">arrow_back</span>
      Back to Dashboard
    </md-filled-button>
  </div>
</div>

<?php
// Get available financial years for dropdown
$fyList = fy_list(7); // Get 7 years span
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
  <div style="padding: 8px;">
    <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Financial Year</label>
    <select id="fySelect" onchange="updateAnalytics(this.value, document.getElementById('quarterSelect').value)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
      <?php foreach ($fyList as $fyOption): ?>
        <option value="<?=htmlspecialchars($fyOption)?>" <?= $fy === $fyOption ? 'selected' : '' ?>><?=htmlspecialchars($fyOption)?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="padding: 8px;">
    <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Quarter</label>
    <select id="quarterSelect" onchange="updateAnalytics(document.getElementById('fySelect').value, this.value)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
      <option value="Q1" <?= $quarter === 'Q1' ? 'selected' : '' ?>>Q1 (Apr-Jun)</option>
      <option value="Q2" <?= $quarter === 'Q2' ? 'selected' : '' ?>>Q2 (Jul-Sep)</option>
      <option value="Q3" <?= $quarter === 'Q3' ? 'selected' : '' ?>>Q3 (Oct-Dec)</option>
      <option value="Q4" <?= $quarter === 'Q4' ? 'selected' : '' ?>>Q4 (Jan-Mar)</option>
    </select>
  </div>
</div>

<?php if ($error): ?>
<div style="padding: 16px; background: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px; margin-bottom: 24px;">
  <strong style="color: #d32f2f;">Error:</strong>
  <p style="margin: 8px 0 0 0; color: #c62828;"><?=htmlspecialchars($error)?></p>
</div>
<?php endif; ?>

<?php if ($complianceResult && $complianceResult['status'] === 'success'): ?>

<!-- COMPLIANCE STATUS CARD -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
  <div style="padding: 16px; border-radius: 8px; background: <?=$complianceResult['overall_status'] === 'COMPLIANT' ? '#e8f5e9' : '#fff3e0'?>; border: 1px solid <?=$complianceResult['overall_status'] === 'COMPLIANT' ? '#c8e6c9' : '#ffe0b2'?>;">
    <div style="font-size: 12px; color: #666; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 8px;">Overall Status</div>
    <div style="font-size: 32px; font-weight: 600; margin-bottom: 8px; color: <?=$complianceResult['overall_status'] === 'COMPLIANT' ? '#4caf50' : '#ff9800'?>;">
      <?=$complianceResult['overall_status'] === 'COMPLIANT' ? '✓' : '⚠'?>
    </div>
    <div style="font-size: 20px; font-weight: 500; color: <?=$complianceResult['overall_status'] === 'COMPLIANT' ? '#4caf50' : '#ff9800'?>;">
      <?=$complianceResult['overall_status']?>
    </div>
    <div style="font-size: 12px; color: #666; margin-top: 8px;">
      <?=$complianceResult['passed_checks']?>/<?=$complianceResult['total_checks']?> checks passed
    </div>
  </div>

  <div style="padding: 16px; border-radius: 8px; background: <?=$riskAssessment['risk_level'] === 'LOW' ? '#e8f5e9' : ($riskAssessment['risk_level'] === 'MEDIUM' ? '#fff3e0' : '#ffebee')?>; border: 1px solid <?=$riskAssessment['risk_level'] === 'LOW' ? '#c8e6c9' : ($riskAssessment['risk_level'] === 'MEDIUM' ? '#ffe0b2' : '#ffcdd2')?> ;">
    <div style="font-size: 12px; color: #666; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 8px;">Risk Assessment</div>
    <div style="font-size: 32px; font-weight: 600; margin-bottom: 8px; color: <?=$riskAssessment['risk_level'] === 'LOW' ? '#4caf50' : ($riskAssessment['risk_level'] === 'MEDIUM' ? '#ff9800' : '#d32f2f')?>;">
      <?=$riskAssessment['risk_score']?>
    </div>
    <div style="font-size: 16px; font-weight: 500; color: <?=$riskAssessment['risk_level'] === 'LOW' ? '#4caf50' : ($riskAssessment['risk_level'] === 'MEDIUM' ? '#ff9800' : '#d32f2f')?>;">
      <?=$riskAssessment['risk_level']?> Risk
    </div>
    <div style="font-size: 12px; color: #666; margin-top: 8px;">
      <?=$riskAssessment['safe_to_file'] ? '✓ Safe to file' : '❌ Fix issues first'?>
    </div>
  </div>
</div>

<!-- DETAILED CHECKS -->
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 16px; margin-bottom: 24px;">
  <h3 style="margin: 0 0 16px 0; font-size: 16px;">Compliance Checks (8-Point Validation)</h3>

  <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
    <?php foreach ($complianceResult['details'] as $check_key => $check): ?>
      <div style="padding: 12px; border-radius: 4px; background: <?=$check['status'] === 'PASS' ? '#f1f8f5' : ($check['status'] === 'WARN' ? '#fef9f0' : '#fef0f0')?>; border-left: 4px solid <?=$check['status'] === 'PASS' ? '#4caf50' : ($check['status'] === 'WARN' ? '#ff9800' : '#d32f2f')?>;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 6px;">
          <div style="font-weight: 500; font-size: 14px;">
            <?= $checkLabels[$check_key] ?? $check_key ?>
          </div>
          <span style="padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; background: <?=$check['status'] === 'PASS' ? '#c8e6c9' : ($check['status'] === 'WARN' ? '#ffe0b2' : '#ffcdd2')?>; color: <?=$check['status'] === 'PASS' ? '#2e7d32' : ($check['status'] === 'WARN' ? '#e65100' : '#c62828')?>;">
            <?=$check['status']?>
          </span>
        </div>
        <?php if (!empty($check['message'])): ?>
          <div style="font-size: 12px; color: #666; margin-top: 4px;">
            <?=htmlspecialchars($check['message'])?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- RECOMMENDATIONS -->
<?php if (!empty($complianceResult['recommendations'])): ?>
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 16px; margin-bottom: 24px;">
  <h3 style="margin: 0 0 16px 0; font-size: 16px;">Recommendations</h3>

  <div style="display: flex; flex-direction: column; gap: 12px;">
    <?php foreach ($complianceResult['recommendations'] as $rec): ?>
      <div style="padding: 12px; border-radius: 4px; background: #f5f5f5; border-left: 4px solid #2196f3;">
        <div style="font-weight: 500; font-size: 14px; color: #1976d2; margin-bottom: 4px;">
          <?=htmlspecialchars($rec['category'] ?? 'Recommendation')?>
        </div>
        <div style="font-size: 13px; color: #555;">
          <?=htmlspecialchars($rec['message'])?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- DEDUCTEE ANALYSIS -->
<?php if (!empty($deducteeAnalysis['deductees'])): ?>
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 16px;">
  <h3 style="margin: 0 0 16px 0; font-size: 16px;">Deductee Analysis</h3>

  <div class="table-wrap">
    <table class="table" style="font-size: 13px;">
      <thead>
        <tr>
          <th>Deductee PAN</th>
          <th>Name</th>
          <th>Invoices</th>
          <th>Total Amount</th>
          <th>TDS Deducted</th>
          <th>TDS Paid</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($deducteeAnalysis['deductees'] as $deductee): ?>
          <tr>
            <td><strong><?=htmlspecialchars($deductee['pan'])?></strong></td>
            <td><?=htmlspecialchars($deductee['name'])?></td>
            <td><?=$deductee['invoice_count']?></td>
            <td>₹<?=number_format($deductee['total_amount'], 2)?></td>
            <td>₹<?=number_format($deductee['tds_deducted'], 2)?></td>
            <td>₹<?=number_format($deductee['tds_paid'] ?? 0, 2)?></td>
            <td>
              <span style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; background: <?=($deductee['tds_deducted'] == ($deductee['tds_paid'] ?? 0)) ? '#c8e6c9' : '#ffe0b2'?>; color: <?=($deductee['tds_deducted'] == ($deductee['tds_paid'] ?? 0)) ? '#2e7d32' : '#e65100'?>;">
                <?=($deductee['tds_deducted'] == ($deductee['tds_paid'] ?? 0)) ? 'Matched' : 'Pending'?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($deducteeAnalysis['summary'])): ?>
    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e0e0e0; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
      <div>
        <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Total Deductees</div>
        <div style="font-size: 24px; font-weight: 600; color: #1976d2;"><?=$deducteeAnalysis['summary']['total_deductees']?></div>
      </div>
      <div>
        <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Total Invoices</div>
        <div style="font-size: 24px; font-weight: 600; color: #1976d2;"><?=$deducteeAnalysis['summary']['total_invoices']?></div>
      </div>
      <div>
        <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Total TDS Deducted</div>
        <div style="font-size: 20px; font-weight: 600; color: #1976d2;">₹<?=number_format($deducteeAnalysis['summary']['total_tds_deducted'], 2)?></div>
      </div>
      <div>
        <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Total TDS Paid</div>
        <div style="font-size: 20px; font-weight: 600; color: #4caf50;">₹<?=number_format($deducteeAnalysis['summary']['total_tds_paid'], 2)?></div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php else: ?>
<div style="padding: 24px; background: #fff3e0; border-radius: 8px; border-left: 4px solid #ff9800;">
  <div style="display: flex; align-items: flex-start; gap: 16px;">
    <span class="material-symbols-rounded" style="font-size: 48px; color: #ff9800; flex-shrink: 0;">info</span>
    <div style="text-align: left;">
      <h3 style="margin: 0 0 8px 0; color: #e65100;">⚠️ No Data to Analyze</h3>
      <p style="color: #666; margin: 0 0 12px 0;">
        <?php if (!$firm_id): ?>
          No firm has been configured yet.
          <br/><strong>Next Step:</strong> Go to Firms page and setup your firm details.
        <?php else: ?>
          No invoices found for <?=htmlspecialchars($fy)?> <?=htmlspecialchars($quarter)?>.
          <br/><strong>Next Step:</strong> Go to the Invoices page and add some invoices to analyze compliance.
        <?php endif; ?>
      </p>
      <div style="display: flex; gap: 8px;">
        <?php if (!$firm_id): ?>
          <md-filled-button onclick="location.href='firms.php'">
            <span class="material-symbols-rounded" style="margin-right: 6px;">apartment</span>
            Setup Firm
          </md-filled-button>
        <?php else: ?>
          <md-filled-button onclick="location.href='invoices.php'">
            <span class="material-symbols-rounded" style="margin-right: 6px;">receipt_long</span>
            Add Invoices
          </md-filled-button>
          <md-filled-button onclick="location.href='challans.php'">
            <span class="material-symbols-rounded" style="margin-right: 6px;">account_balance</span>
            Add Challans
          </md-filled-button>
          <md-filled-tonal-button onclick="location.href='reconcile.php'">
            <span class="material-symbols-rounded" style="margin-right: 6px;">sync</span>
            Reconcile
          </md-filled-tonal-button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div style="height: 20px;"></div>
<div style="padding: 16px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
  <strong style="color: #1976d2;">💡 How to get started:</strong>
  <ol style="margin: 8px 0 0 0; padding-left: 20px; color: #555;">
    <li>Go to <strong>Invoices</strong> page and add invoices you've issued where TDS was deducted</li>
    <li>Go to <strong>Challans</strong> page and add bank TDS challans you've submitted</li>
    <li>Go to <strong>Reconcile</strong> page to match invoices with challans</li>
    <li>Come back to <strong>Analytics</strong> to see your compliance status</li>
  </ol>
</div>
<?php endif; ?>

<script>
function updateAnalytics(fy, quarter) {
  const params = new URLSearchParams({ fy: fy, quarter: quarter });
  location.href = '?' + params.toString();
}

function goToDashboard() {
  location.href = 'dashboard.php';
}
</script>

<?php include __DIR__.'/_layout_bottom.php'; ?>
