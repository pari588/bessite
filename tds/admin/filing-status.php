<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php';
$page_title='Filing Status'; include __DIR__.'/_layout_top.php';

$jobId = (int)($_GET['job_id'] ?? 0);

if ($jobId > 0) {
  // Show specific job details
  $stmt = $pdo->prepare('SELECT * FROM tds_filing_jobs WHERE id=?');
  $stmt->execute([$jobId]);
  $job = $stmt->fetch();

  if (!$job) {
    echo '<div class="card" style="color: #d32f2f;"><p>Job not found</p></div>';
    include __DIR__.'/_layout_bottom.php';
    exit;
  }

  // Get logs
  $stmt = $pdo->prepare('SELECT * FROM tds_filing_logs WHERE job_id=? ORDER BY created_at DESC');
  $stmt->execute([$jobId]);
  $logs = $stmt->fetchAll();

  // Get deductees
  $stmt = $pdo->prepare('SELECT * FROM deductees WHERE job_id=?');
  $stmt->execute([$jobId]);
  $deductees = $stmt->fetchAll();
?>

<div class="card fade-in">
  <h3 style="margin-top:0">Filing Job #<?=$job['id']?> — <?=$job['fy']?> <?=$job['quarter']?></h3>

  <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin: 16px 0;">
    <div style="padding: 12px; background: #f5f5f5; border-radius: 4px;">
      <small style="color: #666;">FVU Status</small><br>
      <strong style="font-size: 16px; color: <?=$job['fvu_status']==='succeeded'?'#4caf50':'#ff9800'?>">
        <?=strtoupper($job['fvu_status'])?>
      </strong>
    </div>
    <div style="padding: 12px; background: #f5f5f5; border-radius: 4px;">
      <small style="color: #666;">Filing Status</small><br>
      <strong style="font-size: 16px; color: <?=$job['filing_status']==='accepted'?'#4caf50':'#ff9800'?>">
        <?=strtoupper($job['filing_status'])?>
      </strong>
    </div>
    <div style="padding: 12px; background: #f5f5f5; border-radius: 4px;">
      <small style="color: #666;">Ack No</small><br>
      <strong style="font-size: 16px;"><?=$job['filing_ack_no']?:'—'?></strong>
    </div>
  </div>

  <div style="margin: 16px 0; padding: 12px; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 2px;">
    <strong>Control Totals</strong><br>
    Records: <span class="badge"><?=$job['control_total_records']?></span> |
    Amount: <span class="badge">₹<?=number_format($job['control_total_amount'], 2)?></span> |
    TDS: <span class="badge">₹<?=number_format($job['control_total_tds'], 2)?></span>
  </div>

  <div style="margin-top: 16px; padding: 12px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 2px; display: <?=$job['fvu_error_message']?'block':'none'?>">
    <strong style="color: #e65100;">FVU Error:</strong><br>
    <?=$job['fvu_error_message']?>
  </div>

  <div style="margin-top: 16px; padding: 12px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 2px; display: <?=$job['filing_error_message']?'block':'none'?>">
    <strong style="color: #e65100;">Filing Error:</strong><br>
    <?=$job['filing_error_message']?>
  </div>

  <?php if ($job['fvu_status'] === 'succeeded' && !$job['filing_job_id']): ?>
  <div style="margin-top: 16px;">
    <form id="submitForm" method="post" action="/tds/api/filing/submit">
      <input type="hidden" name="job_id" value="<?=$job['id']?>">
      <md-filled-button type="submit">
        <span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px;margin-right:6px">send</span>
        Submit for E-Filing
      </md-filled-button>
    </form>
    <script>
      document.getElementById('submitForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(this);
        try {
          const res = await fetch('/tds/api/filing/submit', { method: 'POST', body: formData });
          const data = await res.json();
          if (data.ok) {
            alert('Filing submitted! Tracking ID: ' + data.filing_job_id);
            location.reload();
          } else {
            alert('Error: ' + data.msg);
          }
        } catch (err) {
          alert('Error: ' + err.message);
        }
      });
    </script>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($deductees)): ?>
<div style="height:14px"></div>
<div class="card fade-in">
  <h3 style="margin-top:0">Deductees (<?=count($deductees)?>)</h3>
  <div class="table-wrap">
  <table class="table" style="font-size:13px">
    <thead><tr><th>PAN</th><th>Name</th><th>Section</th><th>Gross</th><th>TDS</th><th>Count</th></tr></thead>
    <tbody>
    <?php foreach($deductees as $d): ?>
      <tr>
        <td><?=$d['pan']?></td>
        <td><?=$d['name']?></td>
        <td><?=$d['section_code']?></td>
        <td>₹<?=number_format($d['total_gross'], 2)?></td>
        <td>₹<?=number_format($d['total_tds'], 2)?></td>
        <td><?=$d['payment_count']?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<div style="height:14px"></div>
<div class="card fade-in">
  <h3 style="margin-top:0">Processing Logs (Last 20)</h3>
  <div class="table-wrap">
  <table class="table" style="font-size:12px">
    <thead><tr><th>Time</th><th>Stage</th><th>Status</th><th>Message</th></tr></thead>
    <tbody>
    <?php foreach(array_slice($logs, 0, 20) as $log): ?>
      <tr>
        <td><?=date('M d H:i:s', strtotime($log['created_at']))?></td>
        <td><strong><?=$log['stage']?></strong></td>
        <td><span class="badge" style="background: <?=$log['status']==='succeeded'?'#4caf50':($log['status']==='failed'?'#d32f2f':'#ff9800')?>"><?=$log['status']?></span></td>
        <td><?=$log['message']?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div style="height:14px"></div>
<p style="text-align: center; color: #666; font-size: 12px;">
  Created: <?=date('M d, Y H:i', strtotime($job['created_at']))?> |
  Updated: <?=date('M d, Y H:i', strtotime($job['updated_at']))?><br>
  <a href="dashboard.php">Back to Dashboard</a>
</p>

<?php
} else {
  // Show list of all jobs
  $stmt = $pdo->prepare('SELECT * FROM tds_filing_jobs ORDER BY created_at DESC');
  $stmt->execute();
  $jobs = $stmt->fetchAll();
?>

<div class="card fade-in">
  <h3 style="margin-top:0">All Filing Jobs</h3>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>FY/Q</th><th>FVU Status</th><th>Filing Status</th><th>Ack No</th><th>Records</th><th>TDS Total</th><th>Created</th><th>Action</th></tr></thead>
    <tbody>
    <?php if (empty($jobs)): ?>
      <tr><td colspan="8" style="text-align:center;padding:20px;color:#999;">No filing jobs yet. <a href="dashboard.php">Create one</a></td></tr>
    <?php else: ?>
      <?php foreach($jobs as $j): ?>
      <tr>
        <td><strong><?=$j['fy']?> <?=$j['quarter']?></strong></td>
        <td><span class="badge" style="background: <?=$j['fvu_status']==='succeeded'?'#4caf50':'#ff9800'?>"><?=$j['fvu_status']?></span></td>
        <td><span class="badge" style="background: <?=$j['filing_status']==='accepted'?'#4caf50':'#ff9800'?>"><?=$j['filing_status']?></span></td>
        <td><?=$j['filing_ack_no']?:'—'?></td>
        <td><?=$j['control_total_records']?></td>
        <td>₹<?=number_format($j['control_total_tds'], 2)?></td>
        <td><?=date('M d, H:i', strtotime($j['created_at']))?></td>
        <td><a href="?job_id=<?=$j['id']?>">View</a></td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<div style="height:14px"></div>
<p style="color: #666; font-size: 13px;">
  <strong>To file a TDS return:</strong><br>
  1. Add invoices and challans<br>
  2. Reconcile all TDS to challans<br>
  3. Go to <strong>Dashboard</strong> and look for "File TDS Return" button<br>
  4. Track progress here
</p>

<?php } include __DIR__.'/_layout_bottom.php'; ?>
