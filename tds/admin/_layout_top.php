<?php
if(!isset($page_title)) $page_title='TDS AutoFile';
if(session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../lib/db.php';
$firm = $pdo->query('SELECT display_name, tan, pan FROM firms LIMIT 1')->fetch();
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
    <div class="firm-chip">
      <?php if($firm): ?>
        <span class="material-symbols-rounded">apartment</span>
        <span><?=htmlspecialchars($firm['display_name']?:'Firm')?></span>
        <span class="dot">•</span>
        <span class="material-symbols-rounded">badge</span>
        <span>TAN: <?=htmlspecialchars($firm['tan']?:'—')?></span>
        <span class="dot">•</span>
        <span class="material-symbols-rounded">id_card</span>
        <span>PAN: <?=htmlspecialchars($firm['pan']?:'—')?></span>
      <?php endif; ?>
    </div>
    <div class="spacer"></div>
    <?php if(isset($_SESSION['uid'])): ?>
      <md-filled-tonal-button onclick="location.href='logout.php'"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px;margin-right:6px">logout</span>Logout</md-filled-tonal-button>
    <?php endif; ?>
  </div>
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
