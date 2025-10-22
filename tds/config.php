<?php
return [
  'db' => ['host'=>'127.0.0.1','name'=>'tds_autofile','user'=>'tdsuser','pass'=>'StrongPass123','charset'=>'utf8mb4'],
  // /tds/config.php
'app' => [
  'base_url' => '/tds',   // <-- make sure this is /tds (not /tds)
  'tz' => 'Asia/Kolkata',
  'upload_dir' => __DIR__ . '/uploads',
],
];
