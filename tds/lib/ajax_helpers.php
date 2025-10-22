<?php
function json_ok($arr = []){ header('Content-Type: application/json'); echo json_encode(array_merge(['ok'=>true], $arr)); exit; }
function json_err($msg, $extra = []){ header('Content-Type: application/json'); echo json_encode(array_merge(['ok'=>false,'msg'=>$msg], $extra)); exit; }
?>