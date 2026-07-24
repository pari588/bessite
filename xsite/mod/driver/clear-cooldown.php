<?php
session_start();

// Clear cooldown from session
unset($_SESSION['last_mark_in_time']);
unset($_SESSION['last_mark_out_time']);

// Return response
header('Content-Type: application/json');
echo json_encode(['status' => 'Cooldown cleared']);
?>
