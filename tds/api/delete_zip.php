<?php
require_once __DIR__.'/../lib/auth.php'; auth_require();
header('Content-Type: application/json');

$fn = basename($_POST['file'] ?? '');
if(!$fn){ echo json_encode(['ok'=>false,'msg'=>'No file specified']); exit; }

$uploads = realpath(__DIR__.'/../uploads');
if(!$uploads){ echo json_encode(['ok'=>false,'msg'=>'Uploads folder missing']); exit; }

$path = realpath($uploads . DIRECTORY_SEPARATOR . $fn);
if(!$path || strpos($path, $uploads) !== 0){ echo json_encode(['ok'=>false,'msg'=>'Invalid path']); exit; }

if(!file_exists($path)){ echo json_encode(['ok'=>false,'msg'=>'File not found']); exit; }
if(!is_writable($path)){ echo json_encode(['ok'=>false,'msg'=>'File not writable']); exit; }
if(!is_writable($uploads)){ echo json_encode(['ok'=>false,'msg'=>'Uploads directory not writable']); exit; }

$ok = @unlink($path);
if(!$ok){
  $err = error_get_last();
  echo json_encode(['ok'=>false,'msg'=>'unlink failed: '.(($err['message']??'unknown error'))]);
  exit;
}
echo json_encode(['ok'=>true]);
