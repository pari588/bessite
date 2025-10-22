<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php';
$page_title='Reconcile'; include __DIR__.'/_layout_top.php';
$fys = fy_list();
$today = date('Y-m-d'); [$currFY,$currQ] = fy_quarter_from_date($today);
$rep = $_SESSION['reconcile_report'] ?? null;
$chosenFY = $rep['fy'] ?? $currFY;
$chosenQ = $rep['quarter'] ?? $currQ;
?>
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
<?php include __DIR__.'/_layout_bottom.php';
