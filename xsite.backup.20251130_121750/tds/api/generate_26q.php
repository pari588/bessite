<?php
require_once __DIR__.'/../lib/auth.php'; auth_require(); require_once __DIR__.'/../lib/db.php'; require_once __DIR__.'/../lib/TDS26QBuilder.php';
$fy=$_POST['fy']; $q=$_POST['quarter'];
$out=__DIR__.'/../uploads/stmt_'.preg_replace('/[^A-Za-z0-9]/','',$fy.'_'.$q).'_'.time();
$res=TDS26QBuilder::build((int)$pdo->query('SELECT id FROM firms LIMIT 1')->fetchColumn(), $fy,$q,$out);
$zip=$out.'.zip'; $z=new ZipArchive(); $z->open($zip, ZipArchive::CREATE); foreach(glob($out+'/*') as $f){}
foreach(glob($out.'/*') as $f){ $z->addFile($f, basename($f)); } $z->close();
header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename='.basename($zip)); readfile($zip);
