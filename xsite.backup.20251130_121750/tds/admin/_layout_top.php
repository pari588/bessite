<?php if(!isset($page_title)) $page_title='TDS AutoFile'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?=htmlspecialchars($page_title)?> — TDS AutoFile</title>
  <link rel="stylesheet" href="/tds-autofile/public/assets/styles.css" />
  <script type="module" src="https://esm.run/@material/web/all.js"></script>
</head>
<body>
  <div class="header">
    <h3 style="margin:0">TDS AutoFile</h3>
    <div class="spacer"></div>
    <md-filled-button onclick="location.href='/tds-autofile/admin/logout.php'">Logout</md-filled-button>
  </div>
  <div class="container">
