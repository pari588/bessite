<?php
// /tds/tools/reset_admin.php
require_once __DIR__ . '/../lib/db.php'; // uses config.php -> connects $pdo

// >>> CHANGE THESE 2 LINES <<<
$email = 'admin@example.com';
$newPassword = 'Temp@1234';

$hash = password_hash($newPassword, PASSWORD_BCRYPT);

// If an admin exists -> update. If not -> create.
$pdo->beginTransaction();
try {
  // Try update existing admin by email
  $u = $pdo->prepare("UPDATE users SET password_hash=?, name='Admin', role='owner' WHERE email=?");
  $u->execute([$hash, $email]);

  if ($u->rowCount() === 0) {
    // Insert new admin
    $i = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES ('Admin', ?, ?, 'owner')");
    $i->execute([$email, $hash]);
  }

  $pdo->commit();
  echo "OK: admin user is now {$email} / {$newPassword}\n";
} catch (Exception $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo "ERROR: " . $e->getMessage();
}
