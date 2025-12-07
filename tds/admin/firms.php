<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
$page_title='Firms'; include __DIR__.'/_layout_top.php';

$action = $_GET['action'] ?? 'list';
$firm_id = (int)($_GET['id'] ?? 0);

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
  $post_action = $_POST['action'];

  if ($post_action === 'add' || $post_action === 'edit') {
    $display_name = trim($_POST['display_name'] ?? '');
    $legal_name = trim($_POST['legal_name'] ?? '');
    $pan = strtoupper(trim($_POST['pan'] ?? ''));
    $tan = strtoupper(trim($_POST['tan'] ?? ''));
    $address1 = trim($_POST['address1'] ?? '');
    $address2 = trim($_POST['address2'] ?? '');
    $address3 = trim($_POST['address3'] ?? '');
    $state_code = trim($_POST['state_code'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $ao_code = trim($_POST['ao_code'] ?? '');
    $deductor_category = trim($_POST['deductor_category'] ?? '');
    $rp_name = trim($_POST['rp_name'] ?? '');
    $rp_designation = trim($_POST['rp_designation'] ?? '');
    $rp_mobile = trim($_POST['rp_mobile'] ?? '');
    $rp_email = trim($_POST['rp_email'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $std_code = trim($_POST['std_code'] ?? '');
    $fy_start_month = (int)($_POST['fy_start_month'] ?? 4);

    // Validation
    if (!$display_name || !$legal_name || !$pan || !$tan) {
      $error = 'Display Name, Legal Name, PAN, and TAN are required';
    } elseif (strlen($pan) !== 10) {
      $error = 'PAN must be 10 characters';
    } elseif (strlen($tan) !== 10) {
      $error = 'TAN must be 10 characters';
    } else {
      if ($post_action === 'add') {
        // Check for duplicate TAN
        $stmt = $pdo->prepare('SELECT id FROM firms WHERE tan=?');
        $stmt->execute([$tan]);
        if ($stmt->fetch()) {
          $error = 'Firm with this TAN already exists';
        } else {
          $stmt = $pdo->prepare('INSERT INTO firms (display_name, legal_name, pan, tan, address1, address2, address3, state_code, pincode, ao_code, deductor_category, rp_name, rp_designation, rp_mobile, rp_email, email, phone, std_code, fy_start_month) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
          if ($stmt->execute([$display_name, $legal_name, $pan, $tan, $address1, $address2, $address3, $state_code, $pincode, $ao_code, $deductor_category, $rp_name, $rp_designation, $rp_mobile, $rp_email, $email, $phone, $std_code, $fy_start_month])) {
            $success = 'Firm added successfully';
            $action = 'list';
          } else {
            $error = 'Failed to add firm';
          }
        }
      } else { // edit
        $stmt = $pdo->prepare('UPDATE firms SET display_name=?, legal_name=?, pan=?, tan=?, address1=?, address2=?, address3=?, state_code=?, pincode=?, ao_code=?, deductor_category=?, rp_name=?, rp_designation=?, rp_mobile=?, rp_email=?, email=?, phone=?, std_code=?, fy_start_month=? WHERE id=?');
        if ($stmt->execute([$display_name, $legal_name, $pan, $tan, $address1, $address2, $address3, $state_code, $pincode, $ao_code, $deductor_category, $rp_name, $rp_designation, $rp_mobile, $rp_email, $email, $phone, $std_code, $fy_start_month, $firm_id])) {
          $success = 'Firm updated successfully';
          $action = 'list';
        } else {
          $error = 'Failed to update firm';
        }
      }
    }
  } elseif ($post_action === 'delete') {
    $firm_id = (int)($_POST['id'] ?? 0);
    if ($firm_id > 0) {
      $stmt = $pdo->prepare('DELETE FROM firms WHERE id=?');
      if ($stmt->execute([$firm_id])) {
        $success = 'Firm deleted successfully';
        $action = 'list';
      } else {
        $error = 'Failed to delete firm';
      }
    }
  }
}

// Get all firms
$stmt = $pdo->prepare('SELECT id, display_name, legal_name, tan, pan, rp_name, created_at FROM firms ORDER BY created_at DESC');
$stmt->execute();
$firms = $stmt->fetchAll();

if ($action === 'list'): ?>

<div class="card fade-in">
  <h3 style="margin-top:0">Firms Management</h3>
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

  <p style="margin-bottom:16px;">
    <a href="?action=add" style="text-decoration:none;">
      <md-filled-button><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px;margin-right:6px">add</span>Add New Firm</md-filled-button>
    </a>
  </p>

  <div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>Display Name</th>
        <th>Legal Name</th>
        <th>TAN</th>
        <th>PAN</th>
        <th>RP Name</th>
        <th>Created</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($firms)): ?>
        <tr><td colspan="7" style="text-align:center;padding:20px;color:#999;">No firms yet</td></tr>
      <?php else: ?>
        <?php foreach($firms as $f): ?>
        <tr>
          <td><strong><?=htmlspecialchars($f['display_name'])?></strong></td>
          <td><?=htmlspecialchars($f['legal_name'])?></td>
          <td><?=htmlspecialchars($f['tan'])?></td>
          <td><?=htmlspecialchars($f['pan'])?></td>
          <td><?=htmlspecialchars($f['rp_name']?:'—')?></td>
          <td><?=date('M d, Y', strtotime($f['created_at']))?></td>
          <td>
            <a href="?action=edit&id=<?=$f['id']?>" style="margin-right:8px;">Edit</a>
            <a href="?action=delete&id=<?=$f['id']?>" style="color:#d32f2f;">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif ($action === 'add' || ($action === 'edit' && $firm_id > 0)):
  if ($action === 'edit') {
    $stmt = $pdo->prepare('SELECT * FROM firms WHERE id=?');
    $stmt->execute([$firm_id]);
    $firm = $stmt->fetch();
    if (!$firm) {
      echo '<div class="card" style="color:#d32f2f;"><p>Firm not found</p></div>';
      include __DIR__.'/_layout_bottom.php';
      exit;
    }
  } else {
    $firm = [
      'display_name' => '',
      'legal_name' => '',
      'pan' => '',
      'tan' => '',
      'address1' => '',
      'address2' => '',
      'address3' => '',
      'state_code' => '',
      'pincode' => '',
      'ao_code' => '',
      'deductor_category' => 'Company',
      'rp_name' => '',
      'rp_designation' => '',
      'rp_mobile' => '',
      'rp_email' => '',
      'email' => '',
      'phone' => '',
      'std_code' => '',
      'fy_start_month' => 4,
    ];
  }
?>

<div class="card fade-in">
  <h3 style="margin-top:0"><?=$action==='add'?'Add New Firm':'Edit Firm'?></h3>

  <form method="post" style="max-width:700px;">
    <input type="hidden" name="action" value="<?=$action?>">
    <?php if($action === 'edit'): ?>
      <input type="hidden" name="id" value="<?=$firm['id']?>">
    <?php endif; ?>

    <div style="margin-bottom:16px;">
      <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">Display Name *</label>
      <input type="text" name="display_name" value="<?=htmlspecialchars($firm['display_name']??'')?>" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
      <small style="color:#666;">Name shown in UI (e.g., "T D Framjee and Co")</small>
    </div>

    <div style="margin-bottom:16px;">
      <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">Legal Name *</label>
      <input type="text" name="legal_name" value="<?=htmlspecialchars($firm['legal_name']??'')?>" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
      <small style="color:#666;">Official legal name as per registration</small>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">PAN *</label>
        <input type="text" name="pan" value="<?=htmlspecialchars($firm['pan']??'')?>" maxlength="10" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;text-transform:uppercase;">
        <small style="color:#666;">10-digit PAN</small>
      </div>
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">TAN *</label>
        <input type="text" name="tan" value="<?=htmlspecialchars($firm['tan']??'')?>" maxlength="10" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;text-transform:uppercase;">
        <small style="color:#666;">10-digit TAN</small>
      </div>
    </div>

    <div style="margin-bottom:16px;">
      <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">Address Line 1</label>
      <input type="text" name="address1" value="<?=htmlspecialchars($firm['address1']??'')?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
    </div>

    <div style="margin-bottom:16px;">
      <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">Address Line 2</label>
      <input type="text" name="address2" value="<?=htmlspecialchars($firm['address2']??'')?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
    </div>

    <div style="margin-bottom:16px;">
      <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">Address Line 3</label>
      <input type="text" name="address3" value="<?=htmlspecialchars($firm['address3']??'')?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">State Code</label>
        <input type="text" name="state_code" value="<?=htmlspecialchars($firm['state_code']??'')?>" maxlength="4" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
        <small style="color:#666;">e.g., 27 for Maharashtra</small>
      </div>
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">PIN Code</label>
        <input type="text" name="pincode" value="<?=htmlspecialchars($firm['pincode']??'')?>" maxlength="6" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">AO Code</label>
        <input type="text" name="ao_code" value="<?=htmlspecialchars($firm['ao_code']??'')?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
        <small style="color:#666;">Assessing Officer Code</small>
      </div>
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">Deductor Category</label>
        <select name="deductor_category" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
          <option value="Company" <?=$firm['deductor_category']==='Company'?'selected':''?>>Company</option>
          <option value="Individual" <?=$firm['deductor_category']==='Individual'?'selected':''?>>Individual</option>
          <option value="Partnership" <?=$firm['deductor_category']==='Partnership'?'selected':''?>>Partnership</option>
          <option value="LLP" <?=$firm['deductor_category']==='LLP'?'selected':''?>>LLP</option>
          <option value="Cooperative" <?=$firm['deductor_category']==='Cooperative'?'selected':''?>>Cooperative</option>
        </select>
      </div>
    </div>

    <hr style="margin:20px 0;border:none;border-top:1px solid #eee;">
    <h4 style="margin-top:0;margin-bottom:12px;font-size:16px;">Responsible Person (RP)</h4>

    <div style="margin-bottom:16px;">
      <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">RP Name</label>
      <input type="text" name="rp_name" value="<?=htmlspecialchars($firm['rp_name']??'')?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
    </div>

    <div style="margin-bottom:16px;">
      <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">RP Designation</label>
      <input type="text" name="rp_designation" value="<?=htmlspecialchars($firm['rp_designation']??'')?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
      <small style="color:#666;">e.g., Director, Manager, CFO</small>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">RP Mobile</label>
        <input type="tel" name="rp_mobile" value="<?=htmlspecialchars($firm['rp_mobile']??'')?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
        <small style="color:#666;">10-digit mobile number</small>
      </div>
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">RP Email</label>
        <input type="email" name="rp_email" value="<?=htmlspecialchars($firm['rp_email']??'')?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
      </div>
    </div>

    <hr style="margin:20px 0;border:none;border-top:1px solid #eee;">
    <h4 style="margin-top:0;margin-bottom:12px;font-size:16px;">Firm Contact</h4>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">Email</label>
        <input type="email" name="email" value="<?=htmlspecialchars($firm['email']??'')?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
      </div>
      <div>
        <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">STD Code</label>
        <input type="text" name="std_code" value="<?=htmlspecialchars($firm['std_code']??'')?>" maxlength="8" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
        <small style="color:#666;">Telephone STD code (e.g., 022)</small>
      </div>
    </div>

    <div style="margin-bottom:16px;">
      <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">Phone</label>
      <input type="tel" name="phone" value="<?=htmlspecialchars($firm['phone']??'')?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
      <small style="color:#666;">Landline number (with or without STD)</small>
    </div>

    <div style="margin-bottom:16px;">
      <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px;">FY Start Month</label>
      <select name="fy_start_month" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;box-sizing:border-box;">
        <option value="1" <?=$firm['fy_start_month']==1?'selected':''?>>January</option>
        <option value="4" <?=$firm['fy_start_month']==4?'selected':''?>>April</option>
        <option value="7" <?=$firm['fy_start_month']==7?'selected':''?>>July</option>
      </select>
      <small style="color:#666;">Most Indian firms use April (4)</small>
    </div>

    <div style="margin-top:24px;display:flex;gap:8px;">
      <md-filled-button type="submit"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px;margin-right:6px">save</span><?=$action==='add'?'Add Firm':'Save Changes'?></md-filled-button>
      <md-filled-tonal-button type="button" onclick="location.href='firms.php'"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px;margin-right:6px">close</span>Cancel</md-filled-tonal-button>
    </div>
  </form>
</div>

<?php elseif ($action === 'delete' && $firm_id > 0):
  $stmt = $pdo->prepare('SELECT * FROM firms WHERE id=?');
  $stmt->execute([$firm_id]);
  $firm = $stmt->fetch();
  if (!$firm) {
    echo '<div class="card" style="color:#d32f2f;"><p>Firm not found</p></div>';
    include __DIR__.'/_layout_bottom.php';
    exit;
  }
?>

<div class="card fade-in">
  <h3 style="margin-top:0;color:#d32f2f;">Delete Firm?</h3>
  <p style="font-size:16px;margin:16px 0;">
    Are you sure you want to delete <strong><?=htmlspecialchars($firm['display_name'])?></strong>?
  </p>
  <p style="color:#d32f2f;background:#ffebee;padding:12px;border-radius:4px;margin-bottom:16px;">
    ⚠️ This will delete all associated invoices, challans, and filing jobs. This action cannot be undone.
  </p>

  <form method="post" style="display:flex;gap:8px;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="<?=$firm_id?>">
    <md-filled-button type="submit" style="background:#d32f2f;"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px;margin-right:6px">delete</span>Delete Firm</md-filled-button>
    <md-filled-tonal-button type="button" onclick="location.href='firms.php'"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px;margin-right:6px">close</span>Cancel</md-filled-tonal-button>
  </form>
</div>

<?php endif;
include __DIR__.'/_layout_bottom.php';
?>
