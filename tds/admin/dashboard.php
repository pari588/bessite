<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php';
$page_title='Dashboard'; include __DIR__.'/_layout_top.php';

$today = date('Y-m-d'); [$curFy,$curQ] = fy_quarter_from_date($today);
$cntInv = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
$sumTds = (float)$pdo->query("SELECT COALESCE(SUM(total_tds),0) FROM invoices")->fetchColumn();
$cntCh = (int)$pdo->query("SELECT COUNT(*) FROM challans")->fetchColumn();
$cntVendFY = (int)$pdo->query("SELECT COUNT(DISTINCT vendor_id) FROM invoices WHERE fy=".$pdo->quote($curFy))->fetchColumn();
$cntInvFY = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE fy=".$pdo->quote($curFy))->fetchColumn();
$sumTdsFY = (float)$pdo->query("SELECT COALESCE(SUM(total_tds),0) FROM invoices WHERE fy=".$pdo->quote($curFy))->fetchColumn();

// Get filing jobs
$stmt = $pdo->prepare("SELECT * FROM tds_filing_jobs ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$filingJobs = $stmt->fetchAll();

// Get unallocated invoices
$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE fy=? AND quarter=? AND allocation_status != 'complete'");
$stmt->execute([$curFy, $curQ]);
$unallocated = (int)$stmt->fetchColumn();
?>
<div class="kpis fade-in">
  <div class="kpi k1">
    <div class="label">Total Invoices</div>
    <div class="value"><?=number_format($cntInv)?></div>
  </div>
  <div class="kpi k2">
    <div class="label">Total TDS (All time)</div>
    <div class="value">₹ <?=number_format($sumTds,2)?></div>
  </div>
  <div class="kpi k3">
    <div class="label">Challans</div>
    <div class="value"><?=number_format($cntCh)?></div>
  </div>
  <div class="kpi k4">
    <div class="label">Active Vendors (FY)</div>
    <div class="value"><?=number_format($cntVendFY)?></div>
  </div>
</div>

<div style="height:14px"></div>
<div class="card fade-in">
  <h3 style="margin-top:0">This FY snapshot — <?=$curFy?> (<?=$curQ?>)</h3>
  <p class="muted">Invoices: <span class="badge"><?=$cntInvFY?></span> · Vendors (distinct): <span class="badge"><?=$cntVendFY?></span> · TDS total: <span class="badge">₹ <?=number_format($sumTdsFY,2)?></span></p>
  <?php if($unallocated > 0): ?>
    <p style="color: #d32f2f; margin: 8px 0;">⚠️ <strong><?=$unallocated?> invoices need reconciliation</strong></p>
  <?php endif; ?>
  <p>Quick links:
    <a href="invoices.php">Invoices</a> ·
    <a href="challans.php">Challans</a> ·
    <a href="reconcile.php">Reconcile TDS</a> ·
    <a href="filing-status.php">Filing Status</a>
  </p>
</div>

<!-- NEW: Compliance Status Section -->
<div style="height:20px"></div>
<div class="card fade-in">
  <h3 style="margin-top:0">📊 Compliance Status — <?=$curFy?> (<?=$curQ?>)</h3>
  <?php if($complianceResult['status'] === 'success'): ?>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
      <div style="padding: 12px; border-radius: 4px; background: <?=$complianceResult['overall_status'] === 'COMPLIANT' ? '#e8f5e9' : '#fff3e0'?>;">
        <div style="font-weight: bold; font-size: 14px;">Compliance Status</div>
        <div style="font-size: 20px; color: <?=$complianceResult['overall_status'] === 'COMPLIANT' ? '#4caf50' : '#ff9800'?>; margin-top: 5px;">
          <?=$complianceResult['overall_status'] === 'COMPLIANT' ? '✓ COMPLIANT' : '⚠ NON-COMPLIANT'?>
        </div>
        <div style="font-size: 12px; margin-top: 5px; color: #666;">
          <?=$complianceResult['passed_checks']?>/<?=$complianceResult['total_checks']?> checks passed
        </div>
      </div>
      <div style="padding: 12px; border-radius: 4px; background: <?=$riskAssessment['risk_level'] === 'LOW' ? '#e8f5e9' : '#fff3e0'?>;">
        <div style="font-weight: bold; font-size: 14px;">Risk Level</div>
        <div style="font-size: 20px; color: <?=$riskAssessment['risk_level'] === 'LOW' ? '#4caf50' : '#ff9800'?>; margin-top: 5px;">
          <?=$riskAssessment['risk_level']?> (<?=$riskAssessment['risk_score']?>/100)
        </div>
        <div style="font-size: 12px; margin-top: 5px; color: #666;">
          <?=$riskAssessment['safe_to_file'] ? '✓ Safe to file' : '❌ Fix issues first'?>
        </div>
      </div>
    </div>

    <p style="margin-top: 12px; font-size: 13px; color: #333;">
      <strong>Compliance Checks:</strong>
      <?php foreach(['invoices_exist', 'tds_calculation', 'challan_matching', 'pan_validation'] as $check): ?>
        <?php $status = $complianceResult['details'][$check]['status'] ?? 'UNKNOWN'; ?>
        <span style="margin-right: 10px;">
          <?php if($status === 'PASS'): ?>
            <span style="color: #4caf50;">✓ <?=$check?></span>
          <?php else: ?>
            <span style="color: #d32f2f;">✗ <?=$check?></span>
          <?php endif; ?>
        </span>
      <?php endforeach; ?>
    </p>

    <?php if(!empty($complianceResult['recommendations'])): ?>
    <p style="margin-top: 12px; font-size: 13px;">
      <strong>Recommendations:</strong>
      <ul style="margin: 5px 0; padding-left: 20px;">
      <?php foreach(array_slice($complianceResult['recommendations'], 0, 3) as $rec): ?>
        <li><?=$rec['message']?></li>
      <?php endforeach; ?>
      </ul>
    </p>
    <?php endif; ?>

  <?php else: ?>
    <p style="color: #d32f2f;">⚠️ Could not run compliance check. Ensure invoices exist for this FY/Quarter.</p>
  <?php endif; ?>
</div>

<!-- NEW: E-Filing Actions Section -->
<div style="height:20px"></div>
<div class="card fade-in">
  <h3 style="margin-top:0">📝 E-Filing Actions & New Tools</h3>
  <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Access the complete TDS & TCS filing suite:</p>
  <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 10px; margin: 15px 0;">
    <div style="padding: 12px; border: 1px solid #2196f3; border-radius: 4px; text-align: center; background: #e3f2fd;">
      <div style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">⬇️ Fetch Data</div>
      <p style="font-size: 12px; color: #1976d2; margin: 0 0 8px 0;">From Sandbox</p>
      <md-filled-button onclick="location.href='fetch_sandbox_data.php'" style="width: 100%; font-size: 12px;">
        <span class="material-symbols-rounded" style="font-size: 16px;">download</span>
      </md-filled-button>
    </div>

    <div style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
      <div style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">🧮 Calculator</div>
      <p style="font-size: 12px; color: #666; margin: 0 0 8px 0;">TDS/TCS Calculation</p>
      <md-filled-button onclick="location.href='calculator.php'" style="width: 100%; font-size: 12px;">
        <span class="material-symbols-rounded" style="font-size: 16px;">calculate</span>
      </md-filled-button>
    </div>

    <div style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
      <div style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">📊 Analytics</div>
      <p style="font-size: 12px; color: #666; margin: 0 0 8px 0;">Compliance Check</p>
      <md-filled-button onclick="location.href='analytics.php'" style="width: 100%; font-size: 12px;">
        <span class="material-symbols-rounded" style="font-size: 16px;">analytics</span>
      </md-filled-button>
    </div>

    <div style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
      <div style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">📄 Reports</div>
      <p style="font-size: 12px; color: #666; margin: 0 0 8px 0;">Form Generation</p>
      <md-filled-button onclick="location.href='reports.php'" style="width: 100%; font-size: 12px;">
        <span class="material-symbols-rounded" style="font-size: 16px;">description</span>
      </md-filled-button>
    </div>

    <div style="padding: 12px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
      <div style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">✅ Compliance</div>
      <p style="font-size: 12px; color: #666; margin: 0 0 8px 0;">E-Filing</p>
      <md-filled-button onclick="location.href='compliance.php'" style="width: 100%; font-size: 12px;">
        <span class="material-symbols-rounded" style="font-size: 16px;">verified_user</span>
      </md-filled-button>
    </div>
  </div>
  <p style="margin: 15px 0 0 0; font-size: 12px; color: #666;">
    <strong>Complete E-Filing Workflow:</strong> Fetch Data → Invoice Entry → Reconcile → Compliance Check → Generate Forms → Submit FVU → E-File → Download Certificates
  </p>
</div>

<?php if(!empty($filingJobs)): ?>
<div style="height:14px"></div>
<div class="card fade-in">
  <h3 style="margin-top:0">Recent Filing Jobs</h3>
  <div class="table-wrap">
  <table class="table" style="font-size:13px">
    <thead><tr><th>FY/Q</th><th>Status</th><th>FVU</th><th>Filed</th><th>Ack No</th><th>Created</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach($filingJobs as $job): ?>
      <tr>
        <td><strong><?=$job['fy']?> <?=$job['quarter']?></strong></td>
        <td><?=$job['filing_status']?></td>
        <td><span class="badge" style="background: <?=$job['fvu_status']==='succeeded'?'#4caf50':'#ff9800'?>"><?=$job['fvu_status']?></span></td>
        <td><?=$job['filing_ack_no']?$job['filing_ack_no']:'-'?></td>
        <td><?=$job['filing_ack_no']?'✓':'-'?></td>
        <td><?=date('M d, H:i', strtotime($job['created_at']))?></td>
        <td><a href="filing-status.php?job_id=<?=$job['id']?>">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__.'/_layout_bottom.php';
