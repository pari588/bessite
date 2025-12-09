<?php
header('Content-Type: application/json');
require_once __DIR__.'/../lib/auth.php';
auth_require();
require_once __DIR__.'/../lib/db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? '';
    $name = $input['name'] ?? '';
    $email = $input['email'] ?? '';
    $role = $input['role'] ?? '';

    if (!$userId || !$name || !$email || !$role) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Missing required fields']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Invalid email address']);
        exit;
    }

    if (!in_array($role, ['owner', 'staff'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Invalid role']);
        exit;
    }

    // Check if user exists
    $checkStmt = $pdo->prepare('SELECT id, email FROM users WHERE id = ?');
    $checkStmt->execute([$userId]);
    $user = $checkStmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'User not found']);
        exit;
    }

    // Check if email is being changed and if new email already exists
    if ($email !== $user['email']) {
        $emailCheckStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $emailCheckStmt->execute([$email, $userId]);
        if ($emailCheckStmt->fetch()) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'msg' => 'Email already in use']);
            exit;
        }
    }

    // Update user
    $updateStmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
    $updateStmt->execute([$name, $email, $role, $userId]);

    echo json_encode(['ok' => true, 'msg' => 'User updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Server error']);
}
