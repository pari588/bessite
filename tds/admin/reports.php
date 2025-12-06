<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/ReportsAPI.php';

$page_title='Form Generation';
include __DIR__.'/_layout_top.php';

// Get firm data
$firm = $pdo->query('SELECT id FROM firms LIMIT 1')->fetch();
$firm_id = $firm['id'] ?? null;

// Get current FY and quarter
$today = date('Y-m-d');
function fy_quarter_from_date($date) {
    $year = (int)date('Y', strtotime($date));
    $month = (int)date('n', strtotime($date));
    if ($month >= 4) $fy = $year . '-' . ($year + 1 % 100);
    else $fy = ($year - 1) . '-' . ($year % 100);
    $quarter = 'Q' . ceil($month / 3);
    return [$fy, $quarter];
}
[$curFy, $curQ] = fy_quarter_from_date($today);

// Get parameters
$fy = $_GET['fy'] ?? $curFy;
$quarter = $_GET['quarter'] ?? $curQ;
$formType = $_GET['form'] ?? '26Q';

// Initialize reports API
$reports = new ReportsAPI($pdo);
$generatedForm = null;
$error = null;

// Generate form if requested
if ($_GET['generate'] && $firm_id) {
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
  <h2 style="margin: 0;">Form Generation</h2>
  <md-filled-button onclick="location.href='dashboard.php'">
    <span class="material-symbols-rounded" style="margin-right: 6px;">arrow_back</span>
    Back to Dashboard
  </md-filled-button>
</div>

<!-- FY/QUARTER SELECTOR -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; background: white; padding: 16px; border-radius: 8px; border: 1px solid #e0e0e0;">
  <div>
    <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Financial Year</label>
    <input type="text" value="<?=htmlspecialchars($fy)?>" onchange="updateForms(this.value, document.getElementById('quarterSelect').value)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
  </div>
  <div>
    <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Quarter (if applicable)</label>
    <select id="quarterSelect" onchange="updateForms(document.querySelector('input').value, this.value)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
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
      <md-filled-button type="button" onclick="event.stopPropagation(); generateForm('26Q')">
        <span class="material-symbols-rounded" style="margin-right: 6px;">description</span>
        Generate 26Q
      </md-filled-button>
    </div>

    <!-- FORM 24Q -->
    <div class="form-card" onclick="generateForm('24Q')">
      <div class="form-title">Form 24Q</div>
      <div class="form-description">Annual TDS Return (Consolidation)</div>
      <div class="form-code">₹ Total TDS for FY <?=htmlspecialchars($fy)?></div>
      <div style="font-size: 12px; color: #999; margin-bottom: 12px;">
        Full financial year aggregation
      </div>
      <md-filled-button type="button" onclick="event.stopPropagation(); generateForm('24Q')">
        <span class="material-symbols-rounded" style="margin-right: 6px;">description</span>
        Generate 24Q
      </md-filled-button>
    </div>

    <!-- CSI ANNEXURE -->
    <div class="form-card" onclick="generateForm('CSI')">
      <div class="form-title">CSI Annexure</div>
      <div class="form-description">Challan Summary Information</div>
      <div class="form-code">Challan-wise TDS summary</div>
      <div style="font-size: 12px; color: #999; margin-bottom: 12px;">
        Supporting document for Form 26Q
      </div>
      <md-filled-button type="button" onclick="event.stopPropagation(); generateForm('CSI')">
        <span class="material-symbols-rounded" style="margin-right: 6px;">description</span>
        Generate CSI
      </md-filled-button>
    </div>

    <!-- ANNEXURES -->
    <div class="form-card" onclick="generateForm('Annexures')">
      <div class="form-title">Supporting Annexures</div>
      <div class="form-description">Bank, Vendor, Section & Monthly Wise</div>
      <div class="form-code">4 Detailed Annexures</div>
      <div style="font-size: 12px; color: #999; margin-bottom: 12px;">
        Complete breakdown analysis
      </div>
      <md-filled-button type="button" onclick="event.stopPropagation(); generateForm('Annexures')">
        <span class="material-symbols-rounded" style="margin-right: 6px;">description</span>
        Generate All
      </md-filled-button>
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

  <md-filled-button onclick="downloadForm(<?=htmlspecialchars(json_encode($generatedForm))?>)">
    <span class="material-symbols-rounded" style="margin-right: 6px;">download</span>
    Download Form
  </md-filled-button>
  <md-filled-tonal-button onclick="copyToClipboard(<?=htmlspecialchars(json_encode($generatedForm['content']))?>)" style="margin-left: 8px;">
    <span class="material-symbols-rounded" style="margin-right: 6px;">content_copy</span>
    Copy Content
  </md-filled-tonal-button>
</div>

<?php elseif ($generatedForm && $generatedForm['status'] !== 'success'): ?>
<div style="padding: 16px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 4px;">
  <strong style="color: #e65100;">⚠️ Generation Issue:</strong>
  <p style="margin: 8px 0 0 0; color: #e65100;"><?=htmlspecialchars($generatedForm['message'] ?? 'Form could not be generated')?></p>
</div>
<?php endif; ?>

<script>
function generateForm(formType) {
  const fy = document.querySelector('input').value;
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
</script>

<?php include __DIR__.'/_layout_bottom.php'; ?>
