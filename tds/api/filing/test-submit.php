<?php
/**
 * Test endpoint for debugging
 */

header('Content-Type: application/json');

// Test 1: Check if basic require works
try {
    require_once __DIR__.'/../../lib/db.php';
    echo json_encode(['test1' => 'db.php loaded successfully']);
    exit;
} catch (Exception $e) {
    echo json_encode(['test1_error' => $e->getMessage()]);
    exit;
}
?>
