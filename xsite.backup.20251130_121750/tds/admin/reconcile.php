<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php';
$page_title='Reconcile'; include __DIR__.'/_layout_top.php';
?>
<div class="card">
  <h3>Auto‑Reconcile</h3>
  <form action="/tds-autofile/api/reconcile.php" method="post">
    <label>Financial Year (e.g., 2025-26)</label>
    <md-outlined-text-field name="fy" value="<?=date('Y')-1?>-<?=substr(date('Y'),-2)?>" required></md-outlined-text-field>
    <label>Quarter</label>
    <select name="quarter">
      <option>Q1</option><option>Q2</option><option>Q3</option><option>Q4</option>
    </select>
    <md-filled-button type="submit">Run</md-filled-button>
  </form>
</div>
<?php include __DIR__.'/_layout_bottom.php';
