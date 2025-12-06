<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/CalculatorAPI.php';

$page_title='TDS/TCS Calculator';
include __DIR__.'/_layout_top.php';

// Initialize calculator API
$calculator = new CalculatorAPI($pdo);

// Get all available TDS rates
$tdsRates = $calculator->getAllTDSRates();
$tcsRates = $calculator->getAllTCSRates();

// Process calculation request
$result = null;
$bulkResults = null;
$calcType = $_POST['calc_type'] ?? 'tds';
$baseAmount = $_POST['base_amount'] ?? '';
$sectionCode = $_POST['section_code'] ?? '';
$customRate = !empty($_POST['custom_rate']) ? (float)$_POST['custom_rate'] : null;

if (!empty($baseAmount) && !empty($sectionCode)) {
    try {
        if ($calcType === 'tds') {
            $result = $calculator->calculateInvoiceTDS((float)$baseAmount, $sectionCode, $customRate);
        } elseif ($calcType === 'tcs') {
            $result = $calculator->calculateTransactionTCS((float)$baseAmount, $sectionCode, $customRate);
        } elseif ($calcType === 'contractor') {
            $rate = $customRate ?? 1;
            $result = $calculator->calculateContractorTDS((float)$baseAmount, $rate);
        } elseif ($calcType === 'salary') {
            $result = $calculator->calculateSalaryTDS((float)$baseAmount, date('Y'));
        }
    } catch (Exception $e) {
        $result = ['status' => 'error', 'message' => $e->getMessage()];
    }
}

?>

<style>
.calc-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
}
@media (max-width: 1024px) {
  .calc-container {
    grid-template-columns: 1fr;
  }
}
.calc-card {
  background: white;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  padding: 20px;
}
.calc-result {
  background: #f5f5f5;
  border-radius: 8px;
  padding: 16px;
  margin-top: 16px;
}
.result-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #e0e0e0;
}
.result-row:last-child {
  border-bottom: none;
}
.result-label {
  color: #666;
  font-size: 13px;
}
.result-value {
  font-weight: 600;
  color: #1976d2;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <h2 style="margin: 0;">TDS & TCS Calculator</h2>
  <md-filled-button onclick="location.href='dashboard.php'">
    <span class="material-symbols-rounded" style="margin-right: 6px;">arrow_back</span>
    Back to Dashboard
  </md-filled-button>
</div>

<div class="calc-container">
  <!-- CALCULATOR FORM -->
  <div class="calc-card">
    <h3 style="margin: 0 0 16px 0; font-size: 16px;">Calculate TDS/TCS</h3>

    <form method="POST" style="display: flex; flex-direction: column; gap: 16px;">
      <div>
        <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Calculation Type</label>
        <select name="calc_type" onchange="updateRates(this.value)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
          <option value="tds" <?= $calcType === 'tds' ? 'selected' : '' ?>>TDS (Deduction)</option>
          <option value="tcs" <?= $calcType === 'tcs' ? 'selected' : '' ?>>TCS (Collection)</option>
          <option value="contractor" <?= $calcType === 'contractor' ? 'selected' : '' ?>>Contractor TDS (₹50K+ Threshold)</option>
          <option value="salary" <?= $calcType === 'salary' ? 'selected' : '' ?>>Salary TDS (With Tax Slabs)</option>
        </select>
      </div>

      <div>
        <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Base Amount (₹)</label>
        <input type="number" name="base_amount" value="<?=htmlspecialchars($baseAmount)?>" placeholder="Enter amount" step="0.01" min="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
      </div>

      <div id="sectionDiv">
        <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Section Code</label>
        <select id="sectionCode" name="section_code" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
          <option value="">-- Select Section --</option>
          <?php foreach ($tdsRates as $code => $rate): ?>
            <option value="<?=htmlspecialchars($code)?>" <?= $sectionCode === $code ? 'selected' : '' ?>>
              <?=htmlspecialchars($code)?> - <?=htmlspecialchars($rate['description'])?> (<?=$rate['rate']?>%)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">
          Custom Rate (%) <span style="color: #999;">(optional)</span>
        </label>
        <input type="number" name="custom_rate" value="<?=htmlspecialchars($customRate ?? '')?>" placeholder="Default rate will be used" step="0.01" min="0" max="100" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
      </div>

      <md-filled-button type="submit" style="width: 100%; margin-top: 8px;">
        <span class="material-symbols-rounded" style="margin-right: 6px;">calculate</span>
        Calculate
      </md-filled-button>
    </form>

    <!-- RATE REFERENCE -->
    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0;">
      <h4 style="margin: 0 0 12px 0; font-size: 13px; text-transform: uppercase; color: #999; letter-spacing: 0.5px;">Standard Rates</h4>
      <div style="display: flex; flex-direction: column; gap: 6px;">
        <?php foreach (array_slice($tdsRates, 0, 5) as $code => $rate): ?>
          <div style="display: flex; justify-content: space-between; font-size: 12px;">
            <span><?=htmlspecialchars($code)?></span>
            <span style="color: #1976d2; font-weight: 600;"><?=$rate['rate']?>%</span>
          </div>
        <?php endforeach; ?>
        <?php if (count($tdsRates) > 5): ?>
          <div style="font-size: 11px; color: #999; margin-top: 6px;">
            + <?= count($tdsRates) - 5 ?> more sections available
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- RESULT CARD -->
  <div class="calc-card">
    <h3 style="margin: 0 0 16px 0; font-size: 16px;">Calculation Result</h3>

    <?php if ($result && $result['status'] !== 'error'): ?>
      <div class="calc-result">
        <div class="result-row">
          <span class="result-label">Base Amount</span>
          <span class="result-value">₹<?=number_format($result['base_amount'], 2)?></span>
        </div>
        <div class="result-row">
          <span class="result-label">Rate</span>
          <span class="result-value"><?=$result['rate']?>%</span>
        </div>
        <div class="result-row">
          <span class="result-label">TDS/TCS Amount</span>
          <span class="result-value" style="color: #d32f2f; font-size: 18px;">₹<?=number_format($result['tds_amount'] ?? $result['tcs_amount'], 2)?></span>
        </div>
        <div class="result-row">
          <span class="result-label">Net Amount (After TDS)</span>
          <span class="result-value">₹<?=number_format($result['net_amount'], 2)?></span>
        </div>
        <?php if (!empty($result['surcharge'])): ?>
          <div class="result-row">
            <span class="result-label">Surcharge</span>
            <span class="result-value">₹<?=number_format($result['surcharge'], 2)?></span>
          </div>
        <?php endif; ?>
        <?php if (!empty($result['cess'])): ?>
          <div class="result-row">
            <span class="result-label">Cess</span>
            <span class="result-value">₹<?=number_format($result['cess'], 2)?></span>
          </div>
        <?php endif; ?>
        <?php if (!empty($result['threshold_status'])): ?>
          <div class="result-row">
            <span class="result-label">Threshold Status</span>
            <span class="result-value"><?=htmlspecialchars($result['threshold_status'])?></span>
          </div>
        <?php endif; ?>
      </div>

      <div style="margin-top: 16px; padding: 12px; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 4px;">
        <div style="font-size: 13px; color: #2e7d32;">
          <strong>✓ Calculation Complete</strong>
          <p style="margin: 6px 0 0 0; font-size: 12px;">Copy this TDS amount to your invoice record.</p>
        </div>
      </div>
    <?php elseif ($result && $result['status'] === 'error'): ?>
      <div style="padding: 12px; background: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px;">
        <div style="color: #c62828; font-size: 13px;">
          <strong>❌ Error</strong>
          <p style="margin: 6px 0 0 0; font-size: 12px;"><?=htmlspecialchars($result['message'])?></p>
        </div>
      </div>
    <?php else: ?>
      <div style="padding: 24px; text-align: center; background: #f5f5f5; border-radius: 4px;">
        <span class="material-symbols-rounded" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 8px;">calculate</span>
        <p style="color: #999; margin: 0; font-size: 13px;">
          Enter amount and section code, then click Calculate
        </p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function updateRates(type) {
  const sectionDiv = document.getElementById('sectionDiv');
  if (type === 'salary' || type === 'contractor') {
    sectionDiv.style.display = 'none';
  } else {
    sectionDiv.style.display = 'block';
  }
}

// Update section div visibility on page load
document.addEventListener('DOMContentLoaded', function() {
  updateRates(document.querySelector('select[name="calc_type"]').value);
});
</script>

<?php include __DIR__.'/_layout_bottom.php'; ?>
