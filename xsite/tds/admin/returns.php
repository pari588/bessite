<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php';
$page_title='Returns'; include __DIR__.'/_layout_top.php';
?>
<div class="card">
  <h3>Prepare 26Q Draft</h3>
  <form action="/tds-autofile/api/generate_26q.php" method="post">
    <md-outlined-text-field label="FY" name="fy" value="<?=date('Y')-1?>-<?=substr(date('Y'),-2)?>" required></md-outlined-text-field>
    <label>Quarter</label>
    <select name="quarter"><option>Q1</option><option>Q2</option><option>Q3</option><option>Q4</option></select>
    <md-filled-button type="submit">Generate</md-filled-button>
  </form>
  <p style="opacity:.7;margin-top:8px">This creates a zip with CSVs + statement stub. Validate via FVU 9.2.</p>
</div>
<?php include __DIR__.'/_layout_bottom.php';
