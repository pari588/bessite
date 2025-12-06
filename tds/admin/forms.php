<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/TDS24QGenerator.php';
require_once __DIR__.'/../lib/Form16Generator.php';

$page_title='Forms (24Q & 16)'; include __DIR__.'/_layout_top.php';

$action = $_GET['action'] ?? 'list';
$fy = $_GET['fy'] ?? date('Y') . '-' . (date('Y') + 1);

// Get current firm ID from session (default to 1 for now)
$firm_id = (int)($_SESSION['firm_id'] ?? 1);

// Get available fiscal years from database or generate default list
$stmt = $pdo->prepare('SELECT DISTINCT fy FROM invoices WHERE firm_id = ? ORDER BY fy DESC');
$stmt->execute([$firm_id]);
$availableFYs = $stmt->fetchAll(PDO::FETCH_COLUMN);

// If no invoices exist, generate list of selectable FYs (previous 5 years to next 2 years)
if (empty($availableFYs)) {
    $currentYear = (int)date('Y');
    $currentMonth = (int)date('m');
    $startYear = ($currentMonth < 4) ? $currentYear - 1 : $currentYear;

    for ($i = $startYear + 2; $i >= $startYear - 5; $i--) {
        $fy = $i . '-' . substr($i + 1, 2);
        $availableFYs[] = $fy;
    }
}

// Handle form generation actions
if ($_POST && isset($_POST['action'])) {
  $post_action = $_POST['action'];
  $post_fy = trim($_POST['fy'] ?? '');

  if ($post_action === 'generate_24q') {
    // Generate Form 24Q
    // First check if there are any invoices with complete allocation
    $checkStmt = $pdo->prepare('SELECT COUNT(*) as count FROM invoices WHERE firm_id = ? AND fy = ? AND allocation_status = "complete"');
    $checkStmt->execute([$firm_id, $post_fy]);
    $checkResult = $checkStmt->fetch();

    if ($checkResult['count'] == 0) {
      $error = 'No invoices with complete allocation found for FY ' . htmlspecialchars($post_fy) . '. Please reconcile invoices first by allocating TDS to challans.';
    } else {
      $gen24q = new TDS24QGenerator($pdo, $firm_id, $post_fy);
      $filepath = $gen24q->saveTXT();

      if ($filepath) {
        $success = 'Form 24Q generated successfully';

        // Store in database
        $stmt = $pdo->prepare('INSERT INTO tds_filing_jobs (firm_id, fy, quarter, txt_file_path, fvu_status, filing_status, control_total_records, control_total_amount, control_total_tds) VALUES (?,?,?,?,?,?,?,?,?)');
        $totals = $gen24q->getControlTotals();
        $stmt->execute([
          $firm_id,
          $post_fy,
          'Annual (24Q)',
          $filepath,
          'generated',
          'pending',
          $totals['records'],
          $totals['gross'],
          $totals['tds']
        ]);
      } else {
        $error = 'Failed to generate Form 24Q: ' . $gen24q->getError();
      }
    }
  } elseif ($post_action === 'generate_16') {
    // Generate bulk Form 16 certificates
    // First check if there are any invoices with complete allocation
    $checkStmt = $pdo->prepare('SELECT COUNT(*) as count FROM invoices WHERE firm_id = ? AND fy = ? AND allocation_status = "complete"');
    $checkStmt->execute([$firm_id, $post_fy]);
    $checkResult = $checkStmt->fetch();

    if ($checkResult['count'] == 0) {
      $error = 'No invoices with complete allocation found for FY ' . htmlspecialchars($post_fy) . '. Please reconcile invoices first by allocating TDS to challans.';
    } else {
      $gen16 = new Form16Generator($pdo, $firm_id, $post_fy);
      $results = $gen16->generateBulkForm16();

      if ($results) {
        $success = 'Form 16 certificates generated for ' . count($results) . ' deductee(s)';
      } else {
        $error = 'Failed to generate Form 16: ' . $gen16->getError();
      }
    }
  }
}

// Get generated forms
$stmt = $pdo->prepare('SELECT * FROM tds_filing_jobs WHERE firm_id = ? AND (quarter = "Annual (24Q)" OR quarter LIKE "24Q%") ORDER BY created_at DESC LIMIT 20');
$stmt->execute([$firm_id]);
$form24Qs = $stmt->fetchAll();

// Count Form 16 files
$form16Dir = __DIR__ . '/../uploads/forms/16';
$form16Files = is_dir($form16Dir) ? array_diff(scandir($form16Dir), ['.', '..']) : [];

?>

<div class="card fade-in">
  <h3 style="margin-top:0">Forms 24Q & 16 Management</h3>
  <p style="color:#666;margin-bottom:16px;">Annual Form 24Q consolidates all quarterly 26Q filings. Form 16 certificates are issued to deductees.</p>

  <?php if(isset($success)): ?>
    <div style="padding:12px;background:#e8f5e9;border-left:4px solid #4caf50;border-radius:2px;margin-bottom:16px;color:#1b5e20">
      ✓ <?=$success?>
    </div>
  <?php endif; ?>
  <?php if(isset($error)): ?>
    <div style="padding:12px;background:#ffebee;border-left:4px solid #d32f2f;border-radius:2px;margin-bottom:16px;color:#b71c1c">
      ✗ <?=$error?>
    </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">

    <!-- Form 24Q Generation -->
    <div style="padding:16px;background:#f5f5f5;border-radius:4px;border:1px solid #ddd;">
      <h4 style="margin-top:0;margin-bottom:12px;font-size:15px;">
        <span class="material-symbols-rounded" style="vertical-align:-4px;margin-right:6px;">description</span>
        Form 24Q (Annual TDS Return)
      </h4>
      <p style="font-size:13px;color:#666;margin:0 0 12px 0;">
        Annual consolidation of all quarterly TDS filings. Generate after all quarters (Q1-Q4) are filed and acknowledged.
      </p>
      <form method="post" style="display:flex;flex-direction:column;gap:8px;">
        <input type="hidden" name="action" value="generate_24q">
        <div>
          <label style="display:block;font-weight:500;font-size:13px;margin-bottom:4px;">Select FY:</label>
          <select name="fy" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;font-size:13px;">
            <option value="">-- Choose Fiscal Year --</option>
            <?php foreach($availableFYs as $fyOption): ?>
              <option value="<?=$fyOption?>" <?=$fyOption===$fy?'selected':''?>><?=$fyOption?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" style="padding:8px 12px;background:#1976d2;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;font-size:13px;">
          Generate Form 24Q
        </button>
      </form>
    </div>

    <!-- Form 16 Generation -->
    <div style="padding:16px;background:#f5f5f5;border-radius:4px;border:1px solid #ddd;">
      <h4 style="margin-top:0;margin-bottom:12px;font-size:15px;">
        <span class="material-symbols-rounded" style="vertical-align:-4px;margin-right:6px;">card_giftcard</span>
        Form 16 (TDS Certificates)
      </h4>
      <p style="font-size:13px;color:#666;margin:0 0 12px 0;">
        Generate TDS certificates for each deductee in the fiscal year. One certificate per deductee.
      </p>
      <form method="post" style="display:flex;flex-direction:column;gap:8px;">
        <input type="hidden" name="action" value="generate_16">
        <div>
          <label style="display:block;font-weight:500;font-size:13px;margin-bottom:4px;">Select FY:</label>
          <select name="fy" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;font-size:13px;">
            <option value="">-- Choose Fiscal Year --</option>
            <?php foreach($availableFYs as $fyOption): ?>
              <option value="<?=$fyOption?>" <?=$fyOption===$fy?'selected':''?>><?=$fyOption?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" style="padding:8px 12px;background:#388e3c;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:500;font-size:13px;">
          Generate Form 16
        </button>
      </form>
    </div>

  </div>

  <h4 style="margin-top:20px;margin-bottom:12px;">Form 24Q History</h4>
  <div class="table-wrap">
  <table class="table" style="font-size:13px;">
    <thead>
      <tr>
        <th>FY</th>
        <th>Type</th>
        <th>Records</th>
        <th>Gross Amount</th>
        <th>TDS Total</th>
        <th>Status</th>
        <th>Generated</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if(empty($form24Qs)): ?>
        <tr><td colspan="8" style="text-align:center;padding:20px;color:#999;">No Form 24Q generated yet</td></tr>
      <?php else: ?>
        <?php foreach($form24Qs as $form): ?>
        <tr>
          <td><strong><?=$form['fy']?></strong></td>
          <td><?=$form['quarter']==='Annual (24Q)'?'24Q':'Other'?></td>
          <td><?=(int)$form['control_total_records']?></td>
          <td>₹<?=number_format((float)$form['control_total_amount'], 2)?></td>
          <td>₹<?=number_format((float)$form['control_total_tds'], 2)?></td>
          <td><span class="badge" style="background:<?=$form['filing_status']==='pending'?'#ff9800':'#4caf50'?>"><?=$form['filing_status']?></span></td>
          <td><?=date('M d, H:i', strtotime($form['created_at']))?></td>
          <td>
            <?php if($form['txt_file_path'] && file_exists($form['txt_file_path'])): ?>
              <a href="#" onclick="downloadFile('<?=htmlspecialchars($form['txt_file_path'])?>');" style="color:#1976d2;">Download</a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  </div>

</div>

<?php if(!empty($form16Files)): ?>

<div style="height:14px;"></div>
<div class="card fade-in">
  <h4 style="margin-top:0;margin-bottom:12px;">Generated Form 16 Certificates (<?=count($form16Files)?>)</h4>
  <div class="table-wrap">
  <table class="table" style="font-size:13px;">
    <thead>
      <tr>
        <th>Filename</th>
        <th>Size</th>
        <th>Generated</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($form16Files as $file): ?>
      <tr>
        <td><?=htmlspecialchars($file)?></td>
        <td><?=round(filesize($form16Dir . '/' . $file) / 1024, 1)?> KB</td>
        <td><?=date('M d, H:i', filemtime($form16Dir . '/' . $file))?></td>
        <td>
          <a href="?action=download_16&file=<?=urlencode($file)?>" style="color:#1976d2;">Download</a> |
          <a href="?action=delete_16&file=<?=urlencode($file)?>" style="color:#d32f2f;" onclick="return confirm('Delete this certificate?');">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php endif; ?>

<div style="height:14px;"></div>
<div class="card fade-in" style="background:#f5f5f5;border-left:4px solid #1976d2;">
  <h4 style="margin-top:0;margin-bottom:8px;font-size:14px;">About These Forms</h4>
  <p style="font-size:13px;margin:8px 0;"><strong>Form 24Q</strong> is the annual TDS return that consolidates all quarterly returns (Form 26Q) filed during the fiscal year. It must be filed by specified deadlines and serves as the final TDS compliance document.</p>
  <p style="font-size:13px;margin:8px 0;"><strong>Form 16</strong> is a TDS certificate issued to each deductee/contractor showing TDS deducted during the financial year. These are essential for deductees to claim TDS credit in their personal income tax returns.</p>
  <p style="font-size:13px;margin:8px 0;color:#666;"><strong>Timing:</strong> Form 16 can be generated after the FY ends. Form 24Q must be filed within 30 days of the quarter end.</p>
</div>

<script>
function downloadFile(filepath) {
  if (!filepath) {
    alert('File path not available');
    return false;
  }
  // In production, implement actual file download via server endpoint
  alert('Download feature would be implemented via server endpoint for: ' + filepath);
  return false;
}
</script>

<?php include __DIR__.'/_layout_bottom.php'; ?>
