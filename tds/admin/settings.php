<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php';
$page_title='Settings'; include __DIR__.'/_layout_top.php';
$firm = $pdo->query('SELECT * FROM firms ORDER BY id ASC LIMIT 1')->fetch();
function v($a,$k){ return htmlspecialchars($a[$k]??'', ENT_QUOTES); }
?>
<div class="card fade-in" style="max-width:980px">
  <h3>Firm Settings (Deductor)</h3>
  <form id="firmForm" action="/tds/api/save_firm.php" method="post" class="form-grid">
    <input type="hidden" name="id" value="<?=v($firm,'id')?>"/>
    <md-outlined-text-field name="display_name" label="Deductor / Firm Name" value="<?=v($firm,'display_name')?>" class="span-2" required></md-outlined-text-field>
    <md-outlined-text-field name="tan" label="TAN" value="<?=v($firm,'tan')?>" required></md-outlined-text-field>
    <md-outlined-text-field name="pan" label="PAN" value="<?=v($firm,'pan')?>" required></md-outlined-text-field>
    <md-outlined-text-field name="address1" label="Address Line 1" value="<?=v($firm,'address1')?>" class="span-2" required></md-outlined-text-field>
    <md-outlined-text-field name="address2" label="Address Line 2" value="<?=v($firm,'address2')?>"></md-outlined-text-field>
    <md-outlined-text-field name="address3" label="Address Line 3" value="<?=v($firm,'address3')?>"></md-outlined-text-field>
    <md-outlined-text-field name="state_code" label="State Code (numeric)" value="<?=v($firm,'state_code')?>" required></md-outlined-text-field>
    <md-outlined-text-field name="pincode" label="PIN Code" value="<?=v($firm,'pincode')?>" required></md-outlined-text-field>
    <md-outlined-text-field name="email" label="Email" value="<?=v($firm,'email')?>"></md-outlined-text-field>
    <md-outlined-text-field name="std_code" label="STD Code" value="<?=v($firm,'std_code')?>"></md-outlined-text-field>
    <md-outlined-text-field name="phone" label="Telephone" value="<?=v($firm,'phone')?>"></md-outlined-text-field>
    <md-outlined-text-field name="rp_name" label="Responsible Person Name" value="<?=v($firm,'rp_name')?>" class="span-2"></md-outlined-text-field>
    <md-outlined-text-field name="rp_designation" label="Responsible Person Designation" value="<?=v($firm,'rp_designation')?>"></md-outlined-text-field>
    <md-outlined-text-field name="rp_mobile" label="Responsible Person Mobile (10 digits)" value="<?=v($firm,'rp_mobile')?>"></md-outlined-text-field>
    <md-outlined-text-field name="rp_email" label="Responsible Person Email" value="<?=v($firm,'rp_email')?>"></md-outlined-text-field>
    <div class="span-3" style="display:flex;gap:10px;justify-content:flex-end">
      <md-filled-button type="submit">Save</md-filled-button>
      <span id="firmMsg" class="badge" style="display:none"></span>
    </div>
  </form>
</div>
<script>
document.getElementById('firmForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const res = await fetch(e.target.action, { method:'POST', body: fd });
  const data = await res.json().catch(()=>({ok:false}));
  const el = document.getElementById('firmMsg'); el.style.display='inline-block';
  el.textContent = data.ok ? (data.msg || 'Saved') : ('Save failed: ' + (data.msg||''));
  setTimeout(()=>{ el.style.display='none'; }, 3000);
});
</script>
<?php include __DIR__.'/_layout_bottom.php';
