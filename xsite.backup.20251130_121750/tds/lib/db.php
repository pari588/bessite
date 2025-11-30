<?php
$config = require __DIR__ . '/../config.php';
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
  $config['db']['host'], $config['db']['name'], $config['db']['charset']);
try {
  $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  die('DB connection failed: ' . $e->getMessage());
}
