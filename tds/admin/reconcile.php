<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/helpers.php';

$page_title='Reconcile TDS';
include __DIR__.'/_layout_top.php';

$fys = fy_list();
$today = date('Y-m-d');
[$currFY,$currQ] = fy_quarter_from_date($today);

// Check if there's actually any data in the database
$dataExists = (bool) $pdo->query('SELECT 1 FROM invoices LIMIT 1')->fetch();
if (!$dataExists) {
    // Clear stale session cache when database is empty
    unset($_SESSION['reconcile_report']);
}

$rep = $_SESSION['reconcile_report'] ?? null;
$chosenFY = $rep['fy'] ?? $currFY;
$chosenQ = $rep['quarter'] ?? $currQ;

// Get summary for current period
$stmt = $pdo->prepare('SELECT COUNT(*) as inv_count, COALESCE(SUM(total_tds), 0) as inv_tds FROM invoices WHERE fy=? AND quarter=?');
$stmt->execute([$chosenFY, $chosenQ]);
$invSummary = $stmt->fetch();

$stmt = $pdo->prepare('SELECT COUNT(*) as ch_count, COALESCE(SUM(amount_tds), 0) as ch_tds FROM challans WHERE fy=? AND quarter=?');
$stmt->execute([$chosenFY, $chosenQ]);
$chSummary = $stmt->fetch();

// Check allocation status
$stmt = $pdo->prepare('SELECT
  COALESCE(SUM(i.total_tds), 0) as total_tds_deducted,
  COALESCE(SUM(ca.allocated_tds), 0) as total_allocated,
  COUNT(DISTINCT CASE WHEN i.allocation_status = "complete" THEN i.id END) as complete_count,
  COUNT(i.id) as total_invoices
  FROM invoices i
  LEFT JOIN challan_allocations ca ON i.id = ca.invoice_id
  WHERE i.fy=? AND i.quarter=?');
$stmt->execute([$chosenFY, $chosenQ]);
$allocStatus = $stmt->fetch();
?>

<!-- RECONCILIATION STATUS SUMMARY -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">
  <div style="padding: 12px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
    <div style="font-size: 11px; color: #1976d2; font-weight: 600; text-transform: uppercase; margin-bottom: 6px;">Invoices This Quarter</div>
    <div style="font-size: 22px; font-weight: 600; color: #1976d2;"><?=$invSummary['inv_count']?></div>
    <div style="font-size: 11px; color: #666; margin-top: 4px;">₹ <?=number_format($invSummary['inv_tds'], 0)?> TDS</div>
  </div>

  <div style="padding: 12px; background: #f3e5f5; border-radius: 8px; border-left: 4px solid #9c27b0;">
    <div style="font-size: 11px; color: #7b1fa2; font-weight: 600; text-transform: uppercase; margin-bottom: 6px;">Challans This Quarter</div>
    <div style="font-size: 22px; font-weight: 600; color: #7b1fa2;"><?=$chSummary['ch_count']?></div>
    <div style="font-size: 11px; color: #666; margin-top: 4px;">₹ <?=number_format($chSummary['ch_tds'], 0)?> TDS Paid</div>
  </div>

  <div style="padding: 12px; background: <?=($allocStatus['total_tds_deducted'] == $allocStatus['total_allocated']) ? '#e8f5e9' : '#fff3e0'?>; border-radius: 8px; border-left: 4px solid <?=($allocStatus['total_tds_deducted'] == $allocStatus['total_allocated']) ? '#4caf50' : '#ff9800'?>;">
    <div style="font-size: 11px; color: <?=($allocStatus['total_tds_deducted'] == $allocStatus['total_allocated']) ? '#2e7d32' : '#e65100'?>; font-weight: 600; text-transform: uppercase; margin-bottom: 6px;">
      <?=($allocStatus['total_tds_deducted'] == $allocStatus['total_allocated']) ? '✓ Matched' : '⚠ Unmatched'?>
    </div>
    <div style="font-size: 11px; color: #666;">Deducted: ₹ <?=number_format($allocStatus['total_tds_deducted'], 0)?></div>
    <div style="font-size: 11px; color: #666; margin-top: 4px;">Allocated: ₹ <?=number_format($allocStatus['total_allocated'], 0)?></div>
  </div>

  <div style="padding: 12px; background: #f5f5f5; border-radius: 8px; border-left: 4px solid #666;">
    <div style="font-size: 11px; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 6px;">Reconciliation Status</div>
    <div style="font-size: 22px; font-weight: 600; color: #1976d2;"><?=$allocStatus['complete_count']?>/<?=$allocStatus['total_invoices']?></div>
    <div style="font-size: 11px; color: #666; margin-top: 4px;">Complete</div>
  </div>
</div>

<div class="card fade-in">
  <h3>Auto‑Reconcile</h3>
  <form action="/tds/api/reconcile.php" method="post" class="form-grid">
    <div>
      <label style="display:block;font-size:12px;color:var(--m3-muted)">Financial Year (IST)</label>
      <md-outlined-select name="fy" required>
        <md-select-option value="<?=$chosenFY?>" selected><div slot="headline"><?=$chosenFY?></div></md-select-option>
        <?php foreach($fys as $fy): if($fy===$chosenFY) continue; ?>
          <md-select-option value="<?=$fy?>"><div slot="headline"><?=$fy?></div></md-select-option>
        <?php endforeach; ?>
      </md-outlined-select>
    </div>
    <div>
      <label style="display:block;font-size:12px;color:var(--m3-muted)">Quarter</label>
      <md-outlined-select name="quarter">
        <?php foreach(['Q1','Q2','Q3','Q4'] as $qq): ?>
          <md-select-option value="<?=$qq?>" <?=$qq===$chosenQ?'selected':''?>><div slot="headline"><?=$qq?></div></md-select-option>
        <?php endforeach; ?>
      </md-outlined-select>
    </div>
    <div class="span-1" style="display:flex;justify-content:flex-end;align-items:end">
      <md-filled-button type="submit"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">sync</span> Run</md-filled-button>
    </div>
  </form>
</div>

<?php if($rep && !empty($rep['allocations'])): ?>
<div style="height: 20px;"></div>
<div class="card fade-in">
  <h3>Reconciliation Report — <?=$rep['fy']?> <?=$rep['quarter']?></h3>

  <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px;">
    <div style="padding: 12px; background: #e3f2fd; border-radius: 4px;">
      <div style="font-size: 11px; color: #666; margin-bottom: 4px;">Invoices Matched</div>
      <div style="font-size: 18px; font-weight: 600; color: #2196f3;"><?=$rep['invoices_count']?> / <?=$rep['source_invoices']?></div>
    </div>
    <div style="padding: 12px; background: #f3e5f5; border-radius: 4px;">
      <div style="font-size: 11px; color: #666; margin-bottom: 4px;">Challans Used</div>
      <div style="font-size: 18px; font-weight: 600; color: #9c27b0;"><?php echo count(array_unique(array_column($rep['allocations'], 'bsr'))); ?> / <?=$rep['source_challans']?></div>
    </div>
    <div style="padding: 12px; background: #e8f5e9; border-radius: 4px;">
      <div style="font-size: 11px; color: #666; margin-bottom: 4px;">Total Allocated</div>
      <div style="font-size: 16px; font-weight: 600; color: #4caf50;">₹ <?=number_format($rep['allocated_total'], 2)?></div>
    </div>
    <div style="padding: 12px; background: <?=($rep['allocated_total'] > 0) ? '#c8e6c9' : '#f5f5f5'?>; border-radius: 4px; border-left: 4px solid <?=($rep['allocated_total'] > 0) ? '#4caf50' : '#ccc'?>;">
      <div style="font-size: 11px; color: #666; margin-bottom: 4px;">Status</div>
      <div style="font-size: 14px; font-weight: 600; color: <?=($rep['allocated_total'] > 0) ? '#2e7d32' : '#999'?>;">
        <?=($rep['allocated_total'] > 0) ? '✓ Reconciled' : '⏳ Pending'?>
      </div>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" style="font-size: 12px;">
      <thead>
        <tr>
          <th>Invoice No</th>
          <th>Date</th>
          <th>Vendor</th>
          <th>Section</th>
          <th>Allocated TDS (₹)</th>
          <th>Challan BSR</th>
          <th>Challan No</th>
          <th>Challan Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rep['allocations'] as $alloc): ?>
          <tr>
            <td><strong><?=htmlspecialchars($alloc['invoice_no'])?></strong></td>
            <td><?=htmlspecialchars($alloc['invoice_date'])?></td>
            <td><?=htmlspecialchars($alloc['vendor'])?></td>
            <td><?=htmlspecialchars($alloc['section'])?></td>
            <td>₹ <?=number_format($alloc['allocated'], 2)?></td>
            <td><?=htmlspecialchars($alloc['bsr'])?></td>
            <td class="mono"><?=htmlspecialchars($alloc['challan_no'])?></td>
            <td><?=htmlspecialchars($alloc['challan_date'])?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e0e0e0;">
    <md-filled-button onclick="clearReport()">
      <span class="material-symbols-rounded" style="margin-right: 6px;">clear</span>
      Clear Report
    </md-filled-button>
  </div>
</div>

<script>
function clearReport() {
  if (confirm('Clear reconciliation report?')) {
    location.href = '/tds/admin/reconcile.php?clear=1';
  }
}
</script>
<?php endif; ?>

<?php include __DIR__.'/_layout_bottom.php';
