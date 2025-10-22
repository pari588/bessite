<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/helpers.php';
$page_title='Returns'; include __DIR__.'/_layout_top.php';
$fys = fy_list();
$today = date('Y-m-d'); [$currFY,$currQ] = fy_quarter_from_date($today);
$uploads = __DIR__.'/../uploads';
$zips = [];
foreach(glob($uploads.'/*.zip') as $zp){
  $bn = basename($zp);
  if(preg_match('/(26Q|stmt)_(\d{4}-\d{2})_(Q[1-4])_\d+\.zip$/',$bn,$m)){
    $zips[$m[2]][$m[3]][] = $bn;
  }
}
$msg = $_GET['msg'] ?? '';
?>
<div class="card fade-in">
  <h3>Prepare 26Q Draft</h3>
  <?php if($msg): ?><p class="badge" style="display:inline-block;margin:0 0 8px 0"><?=$msg?></p><?php endif; ?>
  <form action="/tds/api/generate_26q.php" method="post" class="form-grid">
    <div>
      <label style="display:block;font-size:12px;color:var(--m3-muted)">Financial Year (IST)</label>
      <md-outlined-select name="fy" id="fySel" required>
        <md-select-option value="<?=$currFY?>" selected><div slot="headline"><?=$currFY?></div></md-select-option>
        <?php foreach($fys as $fy): if($fy===$currFY) continue; ?>
          <md-select-option value="<?=$fy?>"><div slot="headline"><?=$fy?></div></md-select-option>
        <?php endforeach; ?>
      </md-outlined-select>
    </div>
    <div class="row" style="align-items:center;gap:10px">
      <div class="badge" id="ayHint" style="margin-top:22px">AY: <?=ay_from_fy($currFY)?></div>
    </div>
    <div>
      <label style="display:block;font-size:12px;color:var(--m3-muted)">Quarter</label>
      <md-outlined-select name="quarter">
        <?php foreach(['Q1','Q2','Q3','Q4'] as $qq): ?>
          <md-select-option value="<?=$qq?>" <?=$qq===$currQ?'selected':''?>><div slot="headline"><?=$qq?></div></md-select-option>
        <?php endforeach; ?>
      </md-outlined-select>
    </div>
    <div class="span-3" style="display:flex;gap:10px;justify-content:flex-end">
      <md-filled-button type="submit"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">archive</span> Generate</md-filled-button>
    </div>
  </form>
  <p class="muted" style="margin-top:8px">No file will download if there are no records for the selected FY/Quarter.</p>
</div>

<div style="height:12px"></div>
<div class="card fade-in">
  <h3>Downloads</h3>
  <?php if($msg && stripos($msg,'generated')!==false): ?>
    <p class="badge badge-ok">✅ <?=$msg?></p>
  <?php endif; ?>
  <?php if(empty($zips)): ?>
    <p>No downloads yet.</p>
  <?php else: ?>
    <?php foreach($zips as $fy => $qmap): ?>
      <h4>FY <?=$fy?> <span class="badge">AY <?=ay_from_fy($fy)?></span></h4>
      <?php foreach($qmap as $q => $files): ?>
        <div class="table-wrap">
        <table class="table" id="tbl-<?=$fy?>-<?=$q?>">
          <tr><th>File</th><th>Size</th><th>Action</th></tr>
          <?php foreach($files as $bn): $p=$uploads.'/'.$bn; ?>
            <tr data-zip="<?=$bn?>">
              <td><a href="/tds/uploads/<?=$bn?>" target="_blank"><?=$bn?></a></td>
              <td><?=number_format(filesize($p))?> bytes</td>
              <td><md-text-button onclick="deleteZipX('<?=$bn?>', 'tbl-<?=$fy?>-<?=$q?>')">Delete</md-text-button></td>
            </tr>
          <?php endforeach; ?>
        </table>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
document.getElementById('fySel')?.addEventListener('change', e => {
  const val = e.target.value;
  const [y, yy] = val.split('-');
  const ay1 = Number(y) + 1;
  const ay2 = (Number(yy) + 1) % 100;
  document.getElementById('ayHint').textContent = 'AY: ' + ay1 + '-' + String(ay2).padStart(2,'0');
});
async function deleteZipX(file, tableId){
  const fd = new FormData(); fd.append('file', file);
  const res = await fetch('/tds/api/delete_zip.php', { method:'POST', body: fd });
  const data = await res.json().catch(()=>({ok:false}));
  if(data.ok){
    const tbl = document.getElementById(tableId);
    const row = tbl.querySelector('tr[data-zip="'+file+'"]');
    row?.remove();
  }else{
    alert('Delete failed: '+(data.msg||'Unknown'));
  }
}
</script>
<?php include __DIR__.'/_layout_bottom.php';
