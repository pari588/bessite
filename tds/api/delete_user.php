<?php
header('Content-Type: application/json');
require_once __DIR__.'/../lib/auth.php';
auth_require();
require_once __DIR__.'/../lib/db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? '';

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Missing user_id']);
        exit;
    }

    // Prevent deleting own account
    if ($userId == $_SESSION['uid']) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Cannot delete your own account']);
        exit;
    }

    // Check if user exists
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $checkStmt->execute([$userId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'User not found']);
        exit;
    }

    // Delete user
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$userId]);

    echo json_encode(['ok' => true, 'msg' => 'User deleted']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Server error']);
}
