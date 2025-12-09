<?php
header('Content-Type: application/json');
require_once __DIR__.'/../lib/auth.php';
auth_require();
require_once __DIR__.'/../lib/db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $currentPass = $input['current_password'] ?? '';
    $newPass = $input['new_password'] ?? '';

    if (!$currentPass || !$newPass) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Missing required fields']);
        exit;
    }

    // Get current user
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['uid']]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'User not found']);
        exit;
    }

    // Verify current password
    if (!password_verify($currentPass, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'msg' => 'Current password is incorrect']);
        exit;
    }

    // Hash and update new password
    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
    $updateStmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $updateStmt->execute([$newHash, $_SESSION['uid']]);

    echo json_encode(['ok' => true, 'msg' => 'Password updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Server error']);
}
