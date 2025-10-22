<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php';
header('Content-Type: application/json');

function col_exists(PDO $pdo, $col){
  $sql = "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'firms' AND column_name = ?";
  $st = $pdo->prepare($sql); $st->execute([$col]);
  return (int)$st->fetchColumn() > 0;
}
function ensure_cols(PDO $pdo){
  $defs = [
    'pan' => 'CHAR(10)',
    'address1' => 'VARCHAR(200)',
    'address2' => 'VARCHAR(200)',
    'address3' => 'VARCHAR(200)',
    'state_code' => 'VARCHAR(4)',
    'pincode' => 'VARCHAR(6)',
    'email' => 'VARCHAR(190)',
    'std_code' => 'VARCHAR(8)',
    'phone' => 'VARCHAR(20)',
    'rp_name' => 'VARCHAR(120)',
    'rp_designation' => 'VARCHAR(120)',
    'rp_mobile' => 'VARCHAR(12)',
    'rp_email' => 'VARCHAR(190)'
  ];
  $adds = [];
  foreach($defs as $c=>$typ){
    if(!col_exists($pdo,$c)){ $adds[] = "ADD COLUMN `$c` $typ NULL"; }
  }
  if($adds){
    $sql = "ALTER TABLE firms " . implode(", ", $adds);
    $pdo->exec($sql);
  }
}

try{ ensure_cols($pdo); }catch(Exception $e){ echo json_encode(['ok'=>false,'msg'=>'Schema update failed: '.$e->getMessage()]); exit; }

$id = (int)($_POST['id'] ?? 0);
$display = trim($_POST['display_name'] ?? '');
$tan = strtoupper(trim($_POST['tan'] ?? ''));
$pan = strtoupper(trim($_POST['pan'] ?? ''));
$address1 = trim($_POST['address1'] ?? '');
$address2 = trim($_POST['address2'] ?? '');
$address3 = trim($_POST['address3'] ?? '');
$state_code = trim($_POST['state_code'] ?? '');
$pincode = trim($_POST['pincode'] ?? '');
$email = trim($_POST['email'] ?? '');
$std = trim($_POST['std_code'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$rp_name = trim($_POST['rp_name'] ?? '');
$rp_desig = trim($_POST['rp_designation'] ?? '');
$rp_mobile = trim($_POST['rp_mobile'] ?? '');
$rp_email = trim($_POST['rp_email'] ?? '');

if($display===''){ echo json_encode(['ok'=>false,'msg'=>'Firm name required']); exit; }
if(!preg_match('/^[A-Z]{4}[0-9]{5}[A-Z]$/',$tan)){ echo json_encode(['ok'=>false,'msg'=>'Invalid TAN']); exit; }
if(!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/',$pan)){ echo json_encode(['ok'=>false,'msg'=>'Invalid PAN']); exit; }
if($state_code!=='' && !preg_match('/^[0-9]{1,2}$/',$state_code)){ echo json_encode(['ok'=>false,'msg'=>'State code must be 1-2 digits']); exit; }
if($pincode!=='' && !preg_match('/^[0-9]{6}$/',$pincode)){ echo json_encode(['ok'=>false,'msg'=>'PIN must be 6 digits']); exit; }
if($rp_mobile!=='' && !preg_match('/^[0-9]{10}$/',$rp_mobile)){ echo json_encode(['ok'=>false,'msg'=>'Mobile must be 10 digits']); exit; }

try{
  // Build dynamic SQL safe and simple
  $fields = ['display_name','pan','tan','address1','address2','address3','state_code','pincode','email','std_code','phone','rp_name','rp_designation','rp_mobile','rp_email'];
  $vals = [$display,$pan,$tan,$address1,$address2,$address3,$state_code,$pincode,$email,$std,$phone,$rp_name,$rp_desig,$rp_mobile,$rp_email];

  if($id>0){
    $sets = implode(',', array_map(fn($c)=>"`$c` = ?", $fields));
    $st = $pdo->prepare("UPDATE firms SET $sets WHERE id=?");
    $vals2 = $vals; $vals2[] = $id;
    $st->execute($vals2);
  }else{
    $chk = $pdo->query('SELECT id FROM firms ORDER BY id ASC LIMIT 1')->fetchColumn();
    if($chk){
      $sets = implode(',', array_map(fn($c)=>"`$c` = ?", $fields));
      $st = $pdo->prepare("UPDATE firms SET $sets WHERE id=?");
      $vals2 = $vals; $vals2[] = $chk;
      $st->execute($vals2);
    }else{
      $cols = '`'.implode('`,`',$fields).'`';
      $qs = rtrim(str_repeat('?,', count($fields)),',');
      $st = $pdo->prepare("INSERT INTO firms ($cols, legal_name) VALUES ($qs, ?)");
      $vals3 = $vals; $vals3[] = $display;
      $st->execute($vals3);
    }
  }
  echo json_encode(['ok'=>true,'msg'=>'Saved']);
}catch(Exception $e){
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
