<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/ajax_helpers.php';

$id = (int)($_POST['id'] ?? 0);
if($id<=0){ json_err('Invalid id'); }

// delete allocations then invoice (if you have challan_allocations table)
@$pdo->prepare('DELETE FROM challan_allocations WHERE invoice_id=?')->execute([$id]);
$ok = $pdo->prepare('DELETE FROM invoices WHERE id=?')->execute([$id]);
if(!$ok){ json_err('Delete failed'); }
json_ok();
