<?php
if(!isset($page_title)) $page_title='TDS AutoFile';
if(session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../lib/db.php';

// Handle firm switching
if (isset($_GET['switch_firm'])) {
    $_SESSION['active_firm_id'] = (int)$_GET['switch_firm'];
}

// Get active firm ID from session or default to first firm
$firmId = $_SESSION['active_firm_id'] ?? null;
if (!$firmId) {
    $stmt = $pdo->query('SELECT id FROM firms LIMIT 1');
    $firstFirm = $stmt->fetch();
    $firmId = $firstFirm['id'] ?? null;
    if ($firmId) {
        $_SESSION['active_firm_id'] = $firmId;
    }
}

// Get current firm details
$firm = null;
$allFirms = [];
if ($firmId) {
    $stmt = $pdo->prepare('SELECT id, display_name, tan, pan FROM firms WHERE id = ?');
    $stmt->execute([$firmId]);
    $firm = $stmt->fetch();
}

// Get all firms for dropdown
$stmt = $pdo->query('SELECT id, display_name, tan FROM firms ORDER BY display_name');
$allFirms = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?=htmlspecialchars($page_title)?> — TDS AutoFile</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Roboto+Mono:wght@400;600&display=swap">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24..48,400,0..1,0" />
  <link rel="stylesheet" href="/tds/public/assets/styles.css" />
  <script type="module" src="https://esm.run/@material/web/all.js"></script>
  <script defer src="/tds/public/assets/app.js"></script>
</head>
<body>
  <div id="globalLoader" class="loader hidden">
    <md-circular-progress indeterminate></md-circular-progress>
  </div>
  <div class="header">
    <md-icon-button class="nav-btn" onclick="window.toggleNav()"><span class="material-symbols-rounded">menu</span></md-icon-button>
    <h3 style="margin:0">TDS AutoFile</h3>

    <!-- Firm Selector -->
    <div class="firm-chip" style="cursor: pointer; position: relative;" onclick="toggleFirmDropdown(event)">
      <?php if($firm): ?>
        <span class="material-symbols-rounded">apartment</span>
        <span><?=htmlspecialchars($firm['display_name']?:'Firm')?></span>
        <span class="dot">•</span>
        <span class="material-symbols-rounded">badge</span>
        <span>TAN: <?=htmlspecialchars($firm['tan']?:'—')?></span>
        <span class="dot">•</span>
        <span class="material-symbols-rounded">id_card</span>
        <span>PAN: <?=htmlspecialchars($firm['pan']?:'—')?></span>
        <span class="material-symbols-rounded" style="margin-left:6px;font-size:16px">expand_more</span>
      <?php else: ?>
        <span class="material-symbols-rounded">apartment</span>
        <span>No Firm Selected</span>
        <span class="material-symbols-rounded" style="margin-left:6px;font-size:16px">expand_more</span>
      <?php endif; ?>

      <!-- Dropdown Menu -->
      <div id="firmDropdown" style="display:none;position:absolute;top:100%;right:0;background:white;border:1px solid #ddd;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.15);min-width:300px;z-index:1000;margin-top:4px">
        <div style="padding:12px;border-bottom:1px solid #f0f0f0;font-weight:600;font-size:13px">Select Firm:</div>
        <?php if (!empty($allFirms)): ?>
          <?php foreach ($allFirms as $f): ?>
            <a href="?switch_firm=<?=$f['id']?>" style="display:block;padding:12px;border-bottom:1px solid #f0f0f0;text-decoration:none;color:inherit;transition:background 0.2s;border-left:3px solid <?=$f['id']==$firmId?'#1976d2':'transparent'?>;padding-left:9px;background:<?=$f['id']==$firmId?'#f0f7ff':'transparent'?>" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='<?=$f['id']==$firmId?'#f0f7ff':'transparent'?>'">
              <div style="font-weight:600;font-size:13px"><?=htmlspecialchars($f['display_name'])?></div>
              <div style="font-size:11px;color:#666;margin-top:2px">TAN: <?=htmlspecialchars($f['tan'])?></div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="padding:12px;color:#999;text-align:center">No firms available</div>
        <?php endif; ?>
        <a href="firms.php" style="display:block;padding:12px;border-top:1px solid #f0f0f0;text-decoration:none;color:#1976d2;font-weight:500;font-size:12px;text-align:center" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='transparent'">
          <span class="material-symbols-rounded" style="font-size:14px;vertical-align:-2px;margin-right:4px">add</span>Manage Firms
        </a>
      </div>
    </div>

    <div class="spacer"></div>
    <?php if(isset($_SESSION['uid'])): ?>
      <md-filled-tonal-button onclick="location.href='logout.php'"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px;margin-right:6px">logout</span>Logout</md-filled-tonal-button>
    <?php endif; ?>
  </div>

  <script>
    function toggleFirmDropdown(event) {
      event.stopPropagation();
      const dropdown = document.getElementById('firmDropdown');
      dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }

    document.addEventListener('click', function(event) {
      const dropdown = document.getElementById('firmDropdown');
      if (dropdown && !event.target.closest('.firm-chip')) {
        dropdown.style.display = 'none';
      }
    });
  </script>
  <div class="shell">
    <?php if(isset($_SESSION['uid'])): ?>
    <aside class="sidebar" id="sidebar">
      <?php $curr = basename($_SERVER['SCRIPT_NAME']); ?>
      <nav>
        <a href="dashboard.php" class="<?= $curr==='dashboard.php'?'active':'' ?>"><span class="material-symbols-rounded">home</span><span>Dashboard</span></a>
        <a href="invoices.php" class="<?= $curr==='invoices.php'?'active':'' ?>"><span class="material-symbols-rounded">receipt_long</span><span>Invoices</span></a>
        <a href="challans.php" class="<?= $curr==='challans.php'?'active':'' ?>"><span class="material-symbols-rounded">account_balance</span><span>Challans</span></a>
        <a href="reconcile.php" class="<?= $curr==='reconcile.php'?'active':'' ?>"><span class="material-symbols-rounded">sync</span><span>Reconcile TDS</span></a>
        <a href="ereturn.php" class="<?= $curr==='ereturn.php'?'active':'' ?>"><span class="material-symbols-rounded">cloud_upload</span><span>E-Return Filing</span></a>
        <hr/>
        <a href="analytics.php" class="<?= $curr==='analytics.php'?'active':'' ?>"><span class="material-symbols-rounded">analytics</span><span>Analytics</span></a>
        <a href="calculator.php" class="<?= $curr==='calculator.php'?'active':'' ?>"><span class="material-symbols-rounded">calculate</span><span>Calculator</span></a>
        <a href="reports.php" class="<?= $curr==='reports.php'?'active':'' ?>"><span class="material-symbols-rounded">description</span><span>Reports</span></a>
        <a href="compliance.php" class="<?= $curr==='compliance.php'?'active':'' ?>"><span class="material-symbols-rounded">verified_user</span><span>Compliance</span></a>
        <a href="filing-status.php" class="<?= $curr==='filing-status.php'?'active':'' ?>"><span class="material-symbols-rounded">check_circle</span><span>Filing Status</span></a>
        <hr/>
        <a href="forms.php" class="<?= $curr==='forms.php'?'active':'' ?>"><span class="material-symbols-rounded">description</span><span>Forms (24Q/16)</span></a>
        <a href="firms.php" class="<?= $curr==='firms.php'?'active':'' ?>"><span class="material-symbols-rounded">apartment</span><span>Firms</span></a>
        <a href="settings.php" class="<?= $curr==='settings.php'?'active':'' ?>"><span class="material-symbols-rounded">settings</span><span>Settings</span></a>
      </nav>
    </aside>
    <?php endif; ?>
    <main class="main" onclick="window.closeNav()">
      <div class="container">
