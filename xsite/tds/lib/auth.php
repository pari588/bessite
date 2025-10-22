<?php
session_start();
require_once __DIR__.'/db.php';
function auth_require(){ if (!isset($_SESSION['uid'])) { header('Location: /tds-autofile/admin/login.php'); exit; } }
function auth_login($email,$password){
  global $pdo;
  $stmt=$pdo->prepare('SELECT * FROM users WHERE email=?');
  $stmt->execute([$email]);
  $u=$stmt->fetch();
  if($u && password_verify($password,$u['password_hash'])){
    $_SESSION['uid']=$u['id']; $_SESSION['name']=$u['name']; return true;
  }
  return false;
}
function auth_logout(){ session_destroy(); }
