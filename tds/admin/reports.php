<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/ReportsAPI.php';
require_once __DIR__.'/../lib/SandboxTDSAPI.php';
require_once __DIR__.'/../lib/helpers.php';

$page_title='Form Generation & Reports';
include __DIR__.'/_layout_top.php';

// Get firm data
$firm = $pdo->query('SELECT id, tan FROM firms LIMIT 1')->fetch();
$firm_id = $firm['id'] ?? null;
$tan = $firm['tan'] ?? null;

// Get current FY and quarter
$today = date('Y-m-d');
[$curFy, $curQ] = fy_quarter_from_date($today);

// Get parameters
$fy = $_GET['fy'] ?? $curFy;
$quarter = $_GET['quarter'] ?? $curQ;
$formType = $_GET['form'] ?? '26Q';
$tab = $_GET['tab'] ?? 'local'; // 'local' or 'sandbox'
$action = $_GET['action'] ?? null;

// Initialize reports API
$reports = new ReportsAPI($pdo);
$generatedForm = null;
$error = null;
$sandboxJobId = null;
$sandboxJobStatus = null;

// Handle Sandbox Reports API requests
if ($tab === 'sandbox' && $action === 'submit' && $firm_id && $tan) {
    try {
        $api = new SandboxTDSAPI($firm_id, $pdo, null, 'production');

        // Map form type to TDS form
        $tdsForm = $formType; // Can be 24Q, 26Q, 27Q
        $fyFormat = "FY " . substr($fy, 0, 4) . "-" . substr($fy, 4); // Convert to FY format

        if ($formType === '24Q') {
            // For 24Q, don't use quarter
            $result = $api->submitTDSReportsJob($tan, 'Q4', '24Q', $fyFormat);
        } elseif ($formType === '27Q') {
            $result = $api->submitTDSReportsJob($tan, $quarter, '27Q', $fyFormat);
        } else {
            // Default to 26Q
            $result = $api->submitTDSReportsJob($tan, $quarter, '26Q', $fyFormat);
        }

        if ($result['status'] === 'success') {
            $sandboxJobId = $result['job_id'];
        } else {
            $error = $result['error'] ?? 'Failed to submit report job';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} elseif ($tab === 'sandbox' && $action === 'poll' && isset($_GET['job_id']) && $firm_id) {
    // Poll job status
    try {
        $api = new SandboxTDSAPI($firm_id, $pdo, null, 'production');
        $jobId = $_GET['job_id'];

        if ($formType === 'TCS') {
            $result = $api->pollTCSReportsJob($jobId);
        } else {
            $result = $api->pollTDSReportsJob($jobId);
        }

        $sandboxJobStatus = $result;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Generate local form if requested
if ($_GET['generate'] && $firm_id && $tab === 'local') {
    try {
        switch ($formType) {
            case '26Q':
                $generatedForm = $reports->generateForm26Q($firm_id, $fy, $quarter);
                break;
            case '24Q':
                $generatedForm = $reports->generateForm24Q($firm_id, $fy);
                break;
            case 'CSI':
                $generatedForm = $reports->generateCSIAnnexure($firm_id, $fy, $quarter);
                break;
            case 'Annexures':
                $generatedForm = $reports->generateTDSAnnexures($firm_id, $fy, $quarter);
                break;
            default:
                $error = 'Unknown form type';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get invoice count for reference
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE fy=? AND quarter=?");
    $stmt->execute([$fy, $quarter]);
    $invCount = $stmt->fetchColumn() ?? 0;
} catch (Exception $e) {
    $invCount = 0;
}

?>

<style>
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
.form-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 16px;
  transition: all 0.3s;
}
.form-card:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.form-title {
  font-weight: 600;
  font-size: 15px;
  margin-bottom: 6px;
}
.form-description {
  font-size: 12px;
  color: #666;
  margin-bottom: 12px;
}
.form-code {
  background: #f5f5f5;
  padding: 8px 12px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 12px;
  color: #1976d2;
  margin-bottom: 12px;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <h2 style="margin: 0;">Form Generation & Reports</h2>
  <md-filled-button onclick="location.href='dashboard.php'">
    <span class="material-symbols-rounded" style="margin-right: 6px;">arrow_back</span>
    Back to Dashboard
  </md-filled-button>
</div>

<!-- TABS -->
<div style="border-bottom: 2px solid #e0e0e0; margin-bottom: 24px; display: flex; gap: 0;">
  <button onclick="switchTab('local')" style="padding: 12px 20px; background: none; border: none; cursor: pointer; font-size: 14px; font-weight: <?= $tab === 'local' ? 600 : 400 ?>; color: <?= $tab === 'local' ? '#1976d2' : '#666' ?>; border-bottom: 3px solid <?= $tab === 'local' ? '#1976d2' : 'transparent' ?>; margin-bottom: -2px;">
    📋 Local Forms
  </button>
  <button onclick="switchTab('sandbox')" style="padding: 12px 20px; background: none; border: none; cursor: pointer; font-size: 14px; font-weight: <?= $tab === 'sandbox' ? 600 : 400 ?>; color: <?= $tab === 'sandbox' ? '#1976d2' : '#666' ?>; border-bottom: 3px solid <?= $tab === 'sandbox' ? '#1976d2' : 'transparent' ?>; margin-bottom: -2px;">
    🌐 Sandbox Reports
  </button>
</div>

<?php
// Get available financial years for dropdown
$fyList = fy_list(7); // Get 7 years span
?>

<!-- FY/QUARTER SELECTOR -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; background: white; padding: 16px; border-radius: 8px; border: 1px solid #e0e0e0;">
  <div>
    <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Financial Year</label>
    <select id="fySelect" onchange="updateForms(this.value, document.getElementById('quarterSelect').value)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
      <?php foreach ($fyList as $fyOption): ?>
        <option value="<?=htmlspecialchars($fyOption)?>" <?= $fy === $fyOption ? 'selected' : '' ?>><?=htmlspecialchars($fyOption)?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Quarter (if applicable)</label>
    <select id="quarterSelect" onchange="updateForms(document.getElementById('fySelect').value, this.value)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
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

<!-- LOCAL FORMS SECTION -->
<div id="localTab" style="display: <?= $tab === 'local' ? 'block' : 'none' ?>;">
  <!-- AVAILABLE FORMS -->
  <div style="margin-bottom: 24px;">
    <h3 style="margin: 0 0 16px 0; font-size: 16px;">Available Forms</h3>

    <div class="form-grid">
    <!-- FORM 26Q -->
    <div class="form-card" onclick="generateForm('26Q')">
      <div class="form-title">Form 26Q</div>
      <div class="form-description">Quarterly TDS Return</div>
      <div class="form-code">₹ TDS Deducted for <?=htmlspecialchars($quarter)?></div>
      <div style="font-size: 12px; color: #999; margin-bottom: 12px;">
        <?=$invCount?> invoices found
      </div>
      <button type="button" onclick="event.stopPropagation(); generateForm('26Q'); return false;" style="padding: 10px 16px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 6px;">
        <span class="material-symbols-rounded" style="font-size: 18px;">description</span>
        Generate 26Q
      </button>
    </div>

    <!-- FORM 24Q -->
    <div class="form-card" onclick="generateForm('24Q')">
      <div class="form-title">Form 24Q</div>
      <div class="form-description">Annual TDS Return (Consolidation)</div>
      <div class="form-code">₹ Total TDS for FY <?=htmlspecialchars($fy)?></div>
      <div style="font-size: 12px; color: #999; margin-bottom: 12px;">
        Full financial year aggregation
      </div>
      <button type="button" onclick="event.stopPropagation(); generateForm('24Q'); return false;" style="padding: 10px 16px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 6px;">
        <span class="material-symbols-rounded" style="font-size: 18px;">description</span>
        Generate 24Q
      </button>
    </div>

    <!-- CSI ANNEXURE -->
    <div class="form-card" onclick="generateForm('CSI')">
      <div class="form-title">CSI Annexure</div>
      <div class="form-description">Challan Summary Information</div>
      <div class="form-code">Challan-wise TDS summary</div>
      <div style="font-size: 12px; color: #999; margin-bottom: 12px;">
        Supporting document for Form 26Q
      </div>
      <button type="button" onclick="event.stopPropagation(); generateForm('CSI'); return false;" style="padding: 10px 16px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 6px;">
        <span class="material-symbols-rounded" style="font-size: 18px;">description</span>
        Generate CSI
      </button>
    </div>

    <!-- ANNEXURES -->
    <div class="form-card" onclick="generateForm('Annexures')">
      <div class="form-title">Supporting Annexures</div>
      <div class="form-description">Bank, Vendor, Section & Monthly Wise</div>
      <div class="form-code">4 Detailed Annexures</div>
      <div style="font-size: 12px; color: #999; margin-bottom: 12px;">
        Complete breakdown analysis
      </div>
      <button type="button" onclick="event.stopPropagation(); generateForm('Annexures'); return false;" style="padding: 10px 16px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 6px;">
        <span class="material-symbols-rounded" style="font-size: 18px;">description</span>
        Generate All
      </button>
    </div>
  </div>
</div>

<!-- GENERATED FORM RESULT -->
<?php if ($generatedForm && $generatedForm['status'] === 'success'): ?>
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px;">
  <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
    <span class="material-symbols-rounded" style="color: #4caf50; font-size: 32px;">check_circle</span>
    <div>
      <div style="font-weight: 600; font-size: 16px;">✓ Form Generated Successfully</div>
      <div style="font-size: 12px; color: #666;">File is ready for download or submission</div>
    </div>
  </div>

  <div style="background: #f5f5f5; padding: 16px; border-radius: 4px; margin-bottom: 16px; border-left: 4px solid #1976d2;">
    <div style="font-size: 12px; color: #666; margin-bottom: 8px;">Form Type</div>
    <div style="font-weight: 600; font-size: 16px; color: #1976d2; margin-bottom: 12px;">
      <?= $generatedForm['form_type'] ?? 'Form' ?>
    </div>

    <div style="font-size: 12px; color: #666; margin-bottom: 8px;">Filename</div>
    <div style="font-family: monospace; font-size: 12px; color: #333; background: white; padding: 8px; border-radius: 3px; margin-bottom: 12px;">
      <?=htmlspecialchars($generatedForm['filename'] ?? 'form.txt')?>
    </div>

    <?php if (!empty($generatedForm['deductee_count'])): ?>
      <div style="font-size: 12px; color: #666; margin-bottom: 8px;">Deductees Included</div>
      <div style="font-weight: 600; font-size: 14px; color: #1976d2;">
        <?=$generatedForm['deductee_count']?> deductees
      </div>
    <?php endif; ?>

    <?php if (!empty($generatedForm['challan_count'])): ?>
      <div style="font-size: 12px; color: #666; margin-bottom: 8px; margin-top: 12px;">Challans Included</div>
      <div style="font-weight: 600; font-size: 14px; color: #1976d2;">
        <?=$generatedForm['challan_count']?> challans
      </div>
    <?php endif; ?>
  </div>

  <div style="padding: 12px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px; margin-bottom: 16px;">
    <div style="font-size: 12px; color: #1565c0;">
      <strong>Next Step:</strong> Copy the form content and submit for FVU generation in the Compliance section, or download for offline review.
    </div>
  </div>

  <button type="button" onclick="downloadForm(<?=htmlspecialchars(json_encode($generatedForm))?>); return false;" style="padding: 10px 16px; background: #4caf50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
    <span class="material-symbols-rounded" style="font-size: 18px;">download</span>
    Download Form
  </button>
  <button type="button" onclick="copyToClipboard(<?=htmlspecialchars(json_encode($generatedForm['content']))?>); return false;" style="margin-left: 8px; padding: 10px 16px; background: #2196f3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
    <span class="material-symbols-rounded" style="font-size: 18px;">content_copy</span>
    Copy Content
  </button>
</div>

<?php elseif ($generatedForm && $generatedForm['status'] !== 'success'): ?>
<div style="padding: 16px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 4px;">
  <strong style="color: #e65100;">⚠️ Generation Issue:</strong>
  <p style="margin: 8px 0 0 0; color: #e65100;"><?=htmlspecialchars($generatedForm['message'] ?? 'Form could not be generated')?></p>
</div>
<?php endif; ?>
</div><!-- End localTab -->

<!-- SANDBOX REPORTS SECTION -->
<div id="sandboxTab" style="display: <?= $tab === 'sandbox' ? 'block' : 'none' ?>;">
  <?php if ($tan): ?>
    <div style="padding: 12px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px; margin-bottom: 16px;">
      <strong style="color: #1565c0;">🌐 Sandbox Reports API</strong>
      <p style="margin: 6px 0 0 0; font-size: 12px; color: #1565c0;">TAN: <?=htmlspecialchars($tan)?> | FY: <?=htmlspecialchars($fy)?> | Quarter: <?=htmlspecialchars($quarter)?></p>
    </div>

    <div class="form-grid">
      <!-- TDS 26Q Report -->
      <div class="form-card">
        <div class="form-title">Form 26Q Report</div>
        <div class="form-description">Non-Salary TDS Returns</div>
        <div class="form-code">Quarterly Report for <?=htmlspecialchars($quarter)?></div>
        <div style="font-size: 12px; color: #999; margin-bottom: 12px;">
          Generate official TDS report via Sandbox API
        </div>
        <button type="button" onclick="submitSandboxJob('26Q'); return false;" style="padding: 10px 16px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 6px; width: 100%;">
          <span class="material-symbols-rounded" style="font-size: 18px;">upload_file</span>
          Submit to Sandbox
        </button>
      </div>

      <!-- TCS Report -->
      <div class="form-card">
        <div class="form-title">Form 27EQ Report</div>
        <div class="form-description">Tax Collected at Source</div>
        <div class="form-code">Quarterly TCS Report for <?=htmlspecialchars($quarter)?></div>
        <div style="font-size: 12px; color: #999; margin-bottom: 12px;">
          Generate official TCS report via Sandbox API
        </div>
        <button type="button" onclick="submitSandboxJob('TCS'); return false;" style="padding: 10px 16px; background: #ff6f00; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 6px; width: 100%;">
          <span class="material-symbols-rounded" style="font-size: 18px;">upload_file</span>
          Submit TCS Report
        </button>
      </div>
    </div>

    <!-- Job Status Display -->
    <?php if ($sandboxJobId): ?>
      <div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px; margin-top: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
          <span class="material-symbols-rounded" style="color: #ff9800; font-size: 32px;">schedule</span>
          <div>
            <div style="font-weight: 600; font-size: 16px;">Report Job Submitted</div>
            <div style="font-size: 12px; color: #666;">Job is being processed by Sandbox</div>
          </div>
        </div>

        <div style="background: #f5f5f5; padding: 16px; border-radius: 4px; margin-bottom: 16px; border-left: 4px solid #1976d2;">
          <div style="font-size: 12px; color: #666; margin-bottom: 8px;">Job ID</div>
          <div style="font-family: monospace; font-size: 12px; color: #333; background: white; padding: 8px; border-radius: 3px; margin-bottom: 12px; word-break: break-all;">
            <?=htmlspecialchars($sandboxJobId)?>
          </div>

          <div style="font-size: 12px; color: #666; margin-bottom: 8px; margin-top: 12px;">Form Type</div>
          <div style="font-weight: 600; font-size: 14px; color: #1976d2;">
            <?=htmlspecialchars($formType)?>
          </div>
        </div>

        <div style="padding: 12px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px; margin-bottom: 16px;">
          <div style="font-size: 12px; color: #1565c0;">
            <strong>Next Step:</strong> Check job status below or refresh in a few moments to see if processing is complete.
          </div>
        </div>

        <button type="button" onclick="pollSandboxJob('<?=htmlspecialchars($sandboxJobId)?>', '<?=htmlspecialchars($formType)?>'); return false;" style="padding: 10px 16px; background: #4caf50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
          <span class="material-symbols-rounded" style="font-size: 18px;">refresh</span>
          Check Status
        </button>
      </div>
    <?php endif; ?>

    <!-- Job Status Result -->
    <?php if ($sandboxJobStatus): ?>
      <div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px; margin-top: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
          <?php if ($sandboxJobStatus['status'] === 'succeeded'): ?>
            <span class="material-symbols-rounded" style="color: #4caf50; font-size: 32px;">check_circle</span>
            <div>
              <div style="font-weight: 600; font-size: 16px;">✓ Report Generated Successfully</div>
              <div style="font-size: 12px; color: #666;">Download your official TDS/TCS report</div>
            </div>
          <?php elseif ($sandboxJobStatus['status'] === 'failed'): ?>
            <span class="material-symbols-rounded" style="color: #d32f2f; font-size: 32px;">cancel</span>
            <div>
              <div style="font-weight: 600; font-size: 16px;">❌ Report Generation Failed</div>
              <div style="font-size: 12px; color: #666;">Check validation report for details</div>
            </div>
          <?php else: ?>
            <span class="material-symbols-rounded" style="color: #ff9800; font-size: 32px;">schedule</span>
            <div>
              <div style="font-weight: 600; font-size: 16px;">⏳ Still Processing</div>
              <div style="font-size: 12px; color: #666;">Status: <?=htmlspecialchars($sandboxJobStatus['status'])?></div>
            </div>
          <?php endif; ?>
        </div>

        <div style="background: #f5f5f5; padding: 16px; border-radius: 4px; margin-bottom: 16px;">
          <div style="font-size: 12px; color: #666; margin-bottom: 8px;">Status</div>
          <div style="font-weight: 600; font-size: 14px; color: #1976d2; margin-bottom: 12px;">
            <?=htmlspecialchars(ucfirst($sandboxJobStatus['status']))?>
          </div>

          <?php if ($sandboxJobStatus['txt_url']): ?>
            <div style="font-size: 12px; color: #666; margin-bottom: 8px; margin-top: 12px;">Download</div>
            <button type="button" onclick="window.open('<?=htmlspecialchars($sandboxJobStatus['txt_url'])?>', '_blank');" style="padding: 10px 16px; background: #4caf50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
              <span class="material-symbols-rounded" style="font-size: 18px;">download</span>
              Download Report
            </button>
          <?php endif; ?>

          <?php if ($sandboxJobStatus['validation_report_url']): ?>
            <div style="font-size: 12px; color: #666; margin-bottom: 8px; margin-top: 12px;">Validation Report</div>
            <button type="button" onclick="window.open('<?=htmlspecialchars($sandboxJobStatus['validation_report_url'])?>', '_blank');" style="padding: 10px 16px; background: #ff9800; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
              <span class="material-symbols-rounded" style="font-size: 18px;">description</span>
              View Errors
            </button>
          <?php endif; ?>
        </div>

        <button type="button" onclick="pollSandboxJob('<?=htmlspecialchars($sandboxJobStatus['job_id'])?>', '<?=htmlspecialchars($formType)?>'); return false;" style="padding: 10px 16px; background: #2196f3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
          <span class="material-symbols-rounded" style="font-size: 18px;">refresh</span>
          Refresh Status
        </button>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div style="padding: 16px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 4px;">
      <strong style="color: #e65100;">⚠️ TAN Required</strong>
      <p style="margin: 8px 0 0 0; color: #e65100;">Please ensure your firm TAN is configured in the system to use Sandbox Reports API.</p>
    </div>
  <?php endif; ?>
</div><!-- End sandboxTab -->

<?php if ($error && $tab === 'sandbox'): ?>
<div style="padding: 16px; background: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px; margin-top: 24px;">
  <strong style="color: #d32f2f;">Error:</strong>
  <p style="margin: 8px 0 0 0; color: #c62828;"><?=htmlspecialchars($error)?></p>
</div>
<?php endif; ?>

<script>
function generateForm(formType) {
  const fy = document.getElementById('fySelect').value;
  const quarter = document.getElementById('quarterSelect').value;
  const url = new URL(location.href);
  url.searchParams.set('form', formType);
  url.searchParams.set('fy', fy);
  url.searchParams.set('quarter', quarter);
  url.searchParams.set('generate', '1');
  location.href = url.toString();
}

function updateForms(fy, quarter) {
  const url = new URL(location.href);
  url.searchParams.set('fy', fy);
  url.searchParams.set('quarter', quarter);
  url.searchParams.delete('generate');
  url.searchParams.delete('form');
  location.href = url.toString();
}

function downloadForm(formData) {
  const content = formData.content || '';
  const filename = formData.filename || 'form.txt';
  const blob = new Blob([content], { type: 'text/plain' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  window.URL.revokeObjectURL(url);
}

function copyToClipboard(content) {
  navigator.clipboard.writeText(content).then(() => {
    alert('Form content copied to clipboard');
  }).catch(() => {
    alert('Failed to copy to clipboard');
  });
}

function switchTab(tabName) {
  // Hide all tabs
  document.getElementById('localTab').style.display = 'none';
  document.getElementById('sandboxTab').style.display = 'none';

  // Show selected tab
  if (tabName === 'local') {
    document.getElementById('localTab').style.display = 'block';
  } else {
    document.getElementById('sandboxTab').style.display = 'block';
  }

  // Update URL to reflect current tab
  const url = new URL(location.href);
  url.searchParams.set('tab', tabName);
  url.searchParams.delete('action');
  url.searchParams.delete('job_id');
  url.searchParams.delete('form');
  window.history.pushState({}, '', url);
}

function submitSandboxJob(formType) {
  // Get selected financial year
  const fy = document.getElementById('fySelect').value;
  const quarter = document.getElementById('quarterSelect').value;

  if (!fy) {
    alert('Please select a financial year');
    return;
  }

  if (formType !== 'TCS' && !quarter) {
    alert('Please select a quarter');
    return;
  }

  // Build URL with submission parameters
  const url = new URL(location.href);
  url.searchParams.set('action', 'submit');
  url.searchParams.set('form', formType);
  url.searchParams.set('fy', fy);
  url.searchParams.set('quarter', quarter);
  url.searchParams.set('tab', 'sandbox');

  // Navigate to trigger PHP submission logic
  location.href = url.toString();
}

function pollSandboxJob(jobId, formType) {
  // Poll the job status by navigating to same page with poll action
  const url = new URL(location.href);
  url.searchParams.set('action', 'poll');
  url.searchParams.set('job_id', jobId);
  url.searchParams.set('form', formType);
  url.searchParams.set('tab', 'sandbox');

  // Navigate to trigger PHP polling logic
  location.href = url.toString();
}
</script>

<?php include __DIR__.'/_layout_bottom.php'; ?>
