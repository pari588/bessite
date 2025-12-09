<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
$page_title='Settings';
include __DIR__.'/_layout_top.php';

$currentUser = $pdo->prepare('SELECT * FROM users WHERE id=?');
$currentUser->execute([$_SESSION['uid']]);
$user = $currentUser->fetch();

$stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');
$stmt->execute();
$users = $stmt->fetchAll();

function v($a,$k){ return htmlspecialchars($a[$k]??'', ENT_QUOTES); }
?>

<!-- CHANGE PASSWORD SECTION -->
<div class="card fade-in" style="max-width:600px;margin-right:auto">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
    <span class="material-symbols-rounded" style="font-size:24px;color:#1976d2">lock</span>
    <h3 style="margin:0;flex:1">Change Password</h3>
  </div>
  <form id="passwordForm" class="form-grid">
    <md-outlined-text-field name="current_password" label="Current Password" type="password" required></md-outlined-text-field>
    <md-outlined-text-field name="new_password" label="New Password" type="password" required></md-outlined-text-field>
    <md-outlined-text-field name="confirm_password" label="Confirm New Password" type="password" required></md-outlined-text-field>
    <div style="display:flex;gap:12px;justify-content:flex-end;align-items:center;margin-top:8px">
      <span id="passwordMsg" class="badge" style="display:none;margin-right:auto"></span>
      <md-filled-button type="submit">Update Password</md-filled-button>
    </div>
  </form>
</div>

<div style="height: 40px;"></div>

<!-- USER MANAGEMENT SECTION -->
<div class="card fade-in">
  <h3>Team Members</h3>
  <div style="margin-bottom: 20px;">
    <md-filled-button onclick="document.getElementById('addUserForm').style.display = document.getElementById('addUserForm').style.display === 'none' ? 'block' : 'none'">
      <span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">person_add</span>
      Add User
    </md-filled-button>
  </div>

  <!-- EDIT USER FORM -->
  <div id="editUserForm" style="display:none;padding:20px;background:#fff3e0;border-radius:8px;margin-bottom:20px;border-left:4px solid #ff9800">
    <h4 style="margin-top:0;margin-bottom:15px">Edit User</h4>
    <form id="editForm" class="form-grid">
      <input type="hidden" name="user_id" id="editUserId">
      <md-outlined-text-field name="name" label="Full Name" id="editName" required></md-outlined-text-field>
      <md-outlined-text-field name="email" label="Email Address" type="email" id="editEmail" required></md-outlined-text-field>
      <md-outlined-select name="role" id="editRole" required>
        <md-select-option><div slot="headline">Select Role</div></md-select-option>
        <md-select-option value="staff"><div slot="headline">Staff</div></md-select-option>
        <md-select-option value="owner"><div slot="headline">Owner</div></md-select-option>
      </md-outlined-select>
      <div class="span-2" style="display:flex;gap:10px;justify-content:flex-end">
        <md-filled-button type="button" onclick="document.getElementById('editUserForm').style.display='none'" style="background:#999;--md-filled-button-container-color:#999">Cancel</md-filled-button>
        <md-filled-button type="submit">Save Changes</md-filled-button>
        <span id="editMsg" class="badge" style="display:none"></span>
      </div>
    </form>
  </div>

  <!-- ADD USER FORM -->
  <div id="addUserForm" style="display:none;padding:20px;background:#f5f5f5;border-radius:8px;margin-bottom:20px">
    <h4 style="margin-top:0;margin-bottom:15px">Add New User</h4>
    <form id="addForm" class="form-grid">
      <md-outlined-text-field name="name" label="Full Name" required></md-outlined-text-field>
      <md-outlined-text-field name="email" label="Email Address" type="email" required></md-outlined-text-field>
      <md-outlined-text-field name="password" label="Password" type="password" required></md-outlined-text-field>
      <md-outlined-select name="role" required>
        <md-select-option><div slot="headline">Select Role</div></md-select-option>
        <md-select-option value="staff"><div slot="headline">Staff</div></md-select-option>
        <md-select-option value="owner"><div slot="headline">Owner</div></md-select-option>
      </md-outlined-select>
      <div class="span-2" style="display:flex;gap:10px;justify-content:flex-end">
        <md-filled-button type="submit">Create User</md-filled-button>
        <span id="addMsg" class="badge" style="display:none"></span>
      </div>
    </form>
  </div>

  <!-- USERS TABLE -->
  <div class="table-wrap">
    <table class="table" style="font-size:13px">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Added</th>
          <th style="text-align:center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($users as $u): ?>
          <tr>
            <td><?=v($u,'name')?> <?=$u['id']==$_SESSION['uid']?' <em style="color:#999">(you)</em>':''?></td>
            <td><?=v($u,'email')?></td>
            <td>
              <span style="display:inline-block;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:600;background:<?=$u['role']==='owner'?'#e3f2fd':'#f5f5f5'?>;color:<?=$u['role']==='owner'?'#1976d2':'#666'?>">
                <?=ucfirst($u['role'])?>
              </span>
            </td>
            <td style="font-size:12px;color:#999"><?=date('M d, Y', strtotime($u['created_at']))?></td>
            <td style="text-align:center;display:flex;gap:8px;justify-content:center">
              <?php if($u['id'] !== $_SESSION['uid']): ?>
                <button type="button" class="editBtn" data-id="<?=$u['id']?>" data-name="<?=v($u,'name')?>" data-email="<?=v($u,'email')?>" data-role="<?=$u['role']?>" style="background:none;border:none;color:#1976d2;cursor:pointer;font-size:18px;padding:0;line-height:1" title="Edit user">
                  <span class="material-symbols-rounded">edit</span>
                </button>
                <button type="button" class="deleteBtn" data-id="<?=$u['id']?>" data-name="<?=v($u,'name')?>" style="background:none;border:none;color:#d32f2f;cursor:pointer;font-size:18px;padding:0;line-height:1" title="Delete user">
                  <span class="material-symbols-rounded">delete</span>
                </button>
              <?php else: ?>
                <span style="color:#999;font-size:12px">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Change Password Form
document.getElementById('passwordForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const current = document.querySelector('[name="current_password"]').value;
  const newPass = document.querySelector('[name="new_password"]').value;
  const confirm = document.querySelector('[name="confirm_password"]').value;

  if(newPass !== confirm) {
    alert('New passwords do not match');
    return;
  }

  if(newPass.length < 6) {
    alert('Password must be at least 6 characters');
    return;
  }

  const res = await fetch('/tds/api/change_password.php', {
    method:'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({current_password: current, new_password: newPass})
  });

  const data = await res.json().catch(()=>({ok:false}));
  const el = document.getElementById('passwordMsg');
  el.style.display = 'inline-block';
  el.textContent = data.ok ? 'Password updated successfully' : ('Error: ' + (data.msg||'Failed'));
  if(data.ok) {
    document.getElementById('passwordForm').reset();
    setTimeout(()=>{ el.style.display='none'; }, 3000);
  } else {
    setTimeout(()=>{ el.style.display='none'; }, 5000);
  }
});

// Add User Form
document.getElementById('addForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const res = await fetch('/tds/api/add_user.php', {
    method:'POST',
    body: fd
  });

  const data = await res.json().catch(()=>({ok:false}));
  const el = document.getElementById('addMsg');
  el.style.display = 'inline-block';

  if(data.ok) {
    el.textContent = 'User created successfully';
    document.getElementById('addForm').reset();
    setTimeout(()=>{ location.reload(); }, 1500);
  } else {
    el.textContent = 'Error: ' + (data.msg||'Failed');
    setTimeout(()=>{ el.style.display='none'; }, 5000);
  }
});

// Edit User
document.querySelectorAll('.editBtn').forEach(btn => {
  btn.addEventListener('click', function() {
    const userId = this.dataset.id;
    const userName = this.dataset.name;
    const userEmail = this.dataset.email;
    const userRole = this.dataset.role;

    document.getElementById('editUserId').value = userId;
    document.getElementById('editName').value = userName;
    document.getElementById('editEmail').value = userEmail;
    document.getElementById('editRole').value = userRole;
    document.getElementById('editUserForm').style.display = 'block';
  });
});

// Edit Form Submit
document.getElementById('editForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const userId = document.getElementById('editUserId').value;
  const name = document.getElementById('editName').value;
  const email = document.getElementById('editEmail').value;
  const role = document.getElementById('editRole').value;

  const res = await fetch('/tds/api/edit_user.php', {
    method:'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({user_id: userId, name: name, email: email, role: role})
  });

  const data = await res.json().catch(()=>({ok:false}));
  const el = document.getElementById('editMsg');
  el.style.display = 'inline-block';

  if(data.ok) {
    el.textContent = 'User updated successfully';
    setTimeout(()=>{ location.reload(); }, 1500);
  } else {
    el.textContent = 'Error: ' + (data.msg||'Failed');
    setTimeout(()=>{ el.style.display='none'; }, 5000);
  }
});

// Delete User
document.querySelectorAll('.deleteBtn').forEach(btn => {
  btn.addEventListener('click', async function() {
    const userId = this.dataset.id;
    const userName = this.dataset.name;

    if(!confirm(`Delete user "${userName}"? This action cannot be undone.`)) return;

    const res = await fetch('/tds/api/delete_user.php', {
      method:'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({user_id: userId})
    });

    const data = await res.json().catch(()=>({ok:false}));
    if(data.ok) {
      alert('User deleted');
      location.reload();
    } else {
      alert('Error: ' + (data.msg||'Failed to delete'));
    }
  });
});
</script>
<?php include __DIR__.'/_layout_bottom.php';
