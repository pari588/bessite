<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/SandboxTDSAPI.php';
require_once __DIR__.'/../lib/helpers.php';

$page_title='Risk Analytics & Potential Notices';
include __DIR__.'/_layout_top.php';

// Get firm data
$firm = $pdo->query('SELECT id, tan FROM firms LIMIT 1')->fetch();
$firm_id = $firm['id'] ?? null;
$firm_tan = $firm['tan'] ?? '';

// Get current FY and quarter
$today = date('Y-m-d');
[$curFy, $curQ] = fy_quarter_from_date($today);

// Get parameters
$fy = $_GET['fy'] ?? $curFy;
$quarter = $_GET['quarter'] ?? $curQ;
$tab = $_GET['tab'] ?? 'tds'; // tds or tcs

// Initialize API (safely handle missing API key)
$apiKey = getenv('SANDBOX_API_KEY') ?? '';
try {
    $api = new SandboxTDSAPI($apiKey, '', function($msg) { /* logging */ });
} catch (Exception $e) {
    // API initialization failed, but page should still render
    $api = null;
}

// Process actions
$actionResult = null;
$action = $_POST['action'] ?? '';

// Handle Analytics API actions
if (!empty($action)) {
    try {
        // Check if API is initialized
        if (!$api) {
            throw new Exception("Analytics API not available. Please check SANDBOX_API_KEY configuration.");
        }

        switch ($action) {
            case 'submit_tds_analytics':
                $form_type = $_POST['form'] ?? '26Q';
                $form_content = $_POST['form_content'] ?? '';

                if (empty($form_content)) {
                    throw new Exception("Form content is required");
                }

                if (!in_array($form_type, ['24Q', '26Q', '27Q'])) {
                    throw new Exception("Invalid form type: $form_type");
                }

                $result = $api->submitTDSAnalyticsJob($firm_tan, $quarter, $form_type, $fy, $form_content);

                if ($result['error']) {
                    throw new Exception($result['error']);
                }

                $actionResult = [
                    'status' => 'success',
                    'message' => "TDS $form_type submitted for analysis. Job ID: {$result['job_id']}"
                ];
                break;

            case 'submit_tcs_analytics':
                $form_content = $_POST['form_content'] ?? '';

                if (empty($form_content)) {
                    throw new Exception("Form content is required");
                }

                $result = $api->submitTCSAnalyticsJob($firm_tan, $quarter, $fy, $form_content);

                if ($result['error']) {
                    throw new Exception($result['error']);
                }

                $actionResult = [
                    'status' => 'success',
                    'message' => "TCS Form 27EQ submitted for analysis. Job ID: {$result['job_id']}"
                ];
                break;

            case 'check_analytics_status':
                $job_id = $_POST['job_id'] ?? '';
                $job_type = $_POST['job_type'] ?? 'tds';

                if (empty($job_id)) {
                    throw new Exception("Job ID is required");
                }

                $result = $job_type === 'tds'
                    ? $api->pollTDSAnalyticsJob($job_id)
                    : $api->pollTCSAnalyticsJob($job_id);

                if ($result['error']) {
                    throw new Exception($result['error']);
                }

                $actionResult = [
                    'status' => 'success',
                    'message' => "Job Status: " . ucfirst($result['status']) . " | Risk Level: " . ($result['risk_level'] ?? 'N/A') . " | Risk Score: " . ($result['risk_score'] ?? 'N/A')
                ];
                break;

            default:
                throw new Exception("Unknown action: $action");
        }
    } catch (Exception $e) {
        $actionResult = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <h2 style="margin: 0;">📊 Risk Analytics & Potential Notices</h2>
  <div style="font-size: 12px; color: #666;">Tab: <?=htmlspecialchars($tab)?> | TAN: <?=htmlspecialchars($firm_tan)?></div>
</div>

<!-- DEBUG: Check if page is rendering -->
<div style="background: #fff8e1; border: 2px solid #ffb74d; padding: 12px; margin-bottom: 16px; border-radius: 4px; font-size: 12px; color: #e65100;">
  <strong>✓ Analytics Page Loaded</strong> - If you see this, the page is rendering correctly. Tab: <strong><?=htmlspecialchars($tab)?></strong>
</div>

<?php if ($actionResult): ?>
<div style="padding: 16px; background: <?=$actionResult['status'] === 'success' ? '#e8f5e9' : '#ffebee'?>; border-left: 4px solid <?=$actionResult['status'] === 'success' ? '#4caf50' : '#d32f2f'?>; border-radius: 4px; margin-bottom: 24px;">
  <strong style="color: <?=$actionResult['status'] === 'success' ? '#4caf50' : '#d32f2f'?>"><?=ucfirst($actionResult['status'])?></strong>: <?=htmlspecialchars($actionResult['message'])?>
</div>
<?php endif; ?>

<!-- Context Info -->
<div style="background: #f0f7ff; border-left: 4px solid #1976d2; padding: 16px; border-radius: 4px; margin-bottom: 20px;">
  <strong style="color: #1976d2;">TAN:</strong> <code><?=htmlspecialchars($firm_tan)?></code> | <strong style="color: #1976d2;">FY:</strong> <code><?=htmlspecialchars($fy)?></code> | <strong style="color: #1976d2;">Quarter:</strong> <code><?=htmlspecialchars($quarter)?></code>
</div>

<!-- Tab Navigation -->
<div style="display: flex; gap: 2px; margin-bottom: 20px; border-bottom: 1px solid #e0e0e0;">
  <a href="?tab=tds" style="padding: 12px 20px; text-decoration: none; color: <?=$tab==='tds' ? '#1976d2' : '#666'?>; border-bottom: 3px solid <?=$tab==='tds' ? '#1976d2' : 'transparent'?>; font-weight: <?=$tab==='tds' ? '600' : '500'?>;">
    📊 TDS Analytics (24Q, 26Q, 27Q)
  </a>
  <a href="?tab=tcs" style="padding: 12px 20px; text-decoration: none; color: <?=$tab==='tcs' ? '#1976d2' : '#666'?>; border-bottom: 3px solid <?=$tab==='tcs' ? '#1976d2' : 'transparent'?>; font-weight: <?=$tab==='tcs' ? '600' : '500'?>;">
    📊 TCS Analytics (27EQ)
  </a>
</div>

<?php if ($tab === 'tds'): ?>
<!-- TDS Analytics -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
  <!-- Submit TDS -->
  <div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px;">
    <h3 style="margin: 0 0 16px 0; font-size: 16px;">📤 Submit TDS Form</h3>
    <form method="POST" style="display: flex; flex-direction: column; gap: 12px;">
      <input type="hidden" name="action" value="submit_tds_analytics">

      <div>
        <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Form Type</label>
        <select name="form" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
          <option value="24Q">24Q - Salary TDS</option>
          <option value="26Q" selected>26Q - Non-Salary TDS</option>
          <option value="27Q">27Q - NRI TDS</option>
        </select>
        <small style="display: block; margin-top: 4px; color: #666; font-size: 12px;">Which TDS form are you analyzing?</small>
      </div>

      <div>
        <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Form Content</label>
        <textarea name="form_content" rows="8" placeholder="Paste XML or JSON form data here..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 12px;"></textarea>
        <small style="display: block; margin-top: 4px; color: #666; font-size: 12px;">Content will be base64 encoded before submission</small>
      </div>

      <button type="submit" style="padding: 10px; background: #1976d2; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 13px;">
        🚀 Submit for Analysis
      </button>
    </form>
  </div>

  <!-- Check Status -->
  <div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px;">
    <h3 style="margin: 0 0 16px 0; font-size: 16px;">🔍 Check Job Status</h3>
    <form method="POST" style="display: flex; flex-direction: column; gap: 12px;">
      <input type="hidden" name="action" value="check_analytics_status">
      <input type="hidden" name="job_type" value="tds">

      <div>
        <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Job ID</label>
        <input type="text" name="job_id" placeholder="Paste job ID from submission" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
        <small style="display: block; margin-top: 4px; color: #666; font-size: 12px;">Returned when you submitted the form</small>
      </div>

      <button type="submit" style="padding: 10px; background: #1976d2; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 13px;">
        ⏱️ Check Status
      </button>
    </form>

    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e0e0e0; font-size: 12px; color: #666;">
      <strong style="color: #333;">Status Values:</strong>
      <ul style="margin: 8px 0 0 0; padding-left: 18px; font-size: 12px;">
        <li><strong>created</strong> - Waiting to process</li>
        <li><strong>queued</strong> - In queue</li>
        <li><strong>processing</strong> - Analyzing</li>
        <li><strong>succeeded</strong> - Complete</li>
        <li><strong>failed</strong> - Error</li>
      </ul>
    </div>
  </div>
</div>

<?php elseif ($tab === 'tcs'): ?>
<!-- TCS Analytics -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
  <!-- Submit TCS -->
  <div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px;">
    <h3 style="margin: 0 0 16px 0; font-size: 16px;">📤 Submit Form 27EQ (TCS)</h3>
    <form method="POST" style="display: flex; flex-direction: column; gap: 12px;">
      <input type="hidden" name="action" value="submit_tcs_analytics">

      <div style="padding: 10px; background: #f0f7ff; border-left: 3px solid #1976d2; border-radius: 4px;">
        <strong style="color: #1976d2; font-size: 13px;">Form Type: 27EQ (Tax Collected at Source)</strong>
      </div>

      <div>
        <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Form Content</label>
        <textarea name="form_content" rows="8" placeholder="Paste Form 27EQ XML or JSON data here..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 12px;"></textarea>
        <small style="display: block; margin-top: 4px; color: #666; font-size: 12px;">Content will be base64 encoded before submission</small>
      </div>

      <button type="submit" style="padding: 10px; background: #1976d2; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 13px;">
        🚀 Submit for Analysis
      </button>
    </form>
  </div>

  <!-- Check Status -->
  <div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px;">
    <h3 style="margin: 0 0 16px 0; font-size: 16px;">🔍 Check Job Status</h3>
    <form method="POST" style="display: flex; flex-direction: column; gap: 12px;">
      <input type="hidden" name="action" value="check_analytics_status">
      <input type="hidden" name="job_type" value="tcs">

      <div>
        <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Job ID</label>
        <input type="text" name="job_id" placeholder="Paste job ID from submission" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
        <small style="display: block; margin-top: 4px; color: #666; font-size: 12px;">Returned when you submitted the form</small>
      </div>

      <button type="submit" style="padding: 10px; background: #1976d2; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 13px;">
        ⏱️ Check Status
      </button>
    </form>

    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e0e0e0; font-size: 12px; color: #666;">
      <strong style="color: #333;">Processing Info:</strong>
      <ul style="margin: 8px 0 0 0; padding-left: 18px; font-size: 12px;">
        <li><strong>Time:</strong> 30 min - 2 hours</li>
        <li><strong>Risk Score:</strong> 0-100</li>
        <li><strong>Risk Levels:</strong> LOW, MEDIUM, HIGH</li>
      </ul>
    </div>
  </div>
</div>

<?php endif; ?>

<!-- Info Section -->
<div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 20px; margin-top: 20px;">
  <h3 style="margin: 0 0 12px 0; font-size: 16px;">ℹ️ About Risk Analytics</h3>
  <p style="margin: 0 0 12px 0; font-size: 13px; color: #666;">
    The Analytics API analyzes your TDS/TCS forms to identify potential risks and tax notices <strong>before filing</strong>. This allows you to address compliance issues proactively.
  </p>
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 12px;">
    <div>
      <strong style="display: block; margin-bottom: 8px; font-size: 13px;">What it does:</strong>
      <ul style="margin: 0; padding-left: 18px; font-size: 12px; color: #666;">
        <li>Analyzes form structure and data</li>
        <li>Identifies compliance gaps</li>
        <li>Predicts potential notices</li>
        <li>Provides risk scoring (0-100)</li>
        <li>Suggests remediation</li>
      </ul>
    </div>
    <div>
      <strong style="display: block; margin-bottom: 8px; font-size: 13px;">Supported Forms:</strong>
      <ul style="margin: 0; padding-left: 18px; font-size: 12px; color: #666;">
        <li><strong>TDS:</strong> 24Q, 26Q, 27Q</li>
        <li><strong>TCS:</strong> 27EQ</li>
        <li><strong>Workflow:</strong> Submit → Poll → Review</li>
        <li><strong>Documentation:</strong> See <code>SANDBOX_ANALYTICS_API_REFERENCE.md</code></li>
      </ul>
    </div>
  </div>
</div>

<?php include __DIR__.'/_layout_bottom.php'; ?>
