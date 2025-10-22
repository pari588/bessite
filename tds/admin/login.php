<?php
require_once __DIR__.'/../lib/auth.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(auth_login($_POST['email']??'', $_POST['password']??'')){
    header('Location: dashboard.php'); exit;
  }
  $err='Invalid credentials';
}
$page_title='Login'; include __DIR__.'/_layout_top.php';
?>
<div class="card" style="max-width:420px;margin:48px auto">
  <h2>Admin Login</h2>
  <?php if(!empty($err)):?><p style="color:#b00020"><?=$err?></p><?php endif; ?>
  <form method="post">
    <md-outlined-text-field label="Email" name="email" type="email" style="width:100%" required></md-outlined-text-field>
    <div style="height:12px"></div>
    <md-outlined-text-field label="Password" name="password" type="password" style="width:100%" required></md-outlined-text-field>
    <div style="height:16px"></div>
    <md-filled-button type="submit">Login</md-filled-button>
  </form>
</div>
<?php include __DIR__.'/_layout_bottom.php';
