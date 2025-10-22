<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/ajax_helpers.php';

$id = (int)($_POST['id'] ?? 0);
if($id<=0){ json_err('Invalid id'); }

@$pdo->prepare('DELETE FROM challan_allocations WHERE challan_id=?')->execute([$id]);
$ok = $pdo->prepare('DELETE FROM challans WHERE id=?')->execute([$id]);
if(!$ok){ json_err('Delete failed'); }
json_ok();
