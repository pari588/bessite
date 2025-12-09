<?php
header('Content-Type: application/json');
require_once __DIR__.'/../lib/auth.php';
auth_require();
require_once __DIR__.'/../lib/db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '';
    $email = $input['email'] ?? '';

    if (!$name || !$email) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Missing required fields']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Invalid email address']);
        exit;
    }

    // Get current user
    $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['uid']]);
    $currentUser = $stmt->fetch();

    if (!$currentUser) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'User not found']);
        exit;
    }

    // Check if email is being changed and if new email already exists
    if ($email !== $currentUser['email']) {
        $emailCheckStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $emailCheckStmt->execute([$email, $_SESSION['uid']]);
        if ($emailCheckStmt->fetch()) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'msg' => 'Email already in use']);
            exit;
        }
    }

    // Update profile
    $updateStmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
    $updateStmt->execute([$name, $email, $_SESSION['uid']]);

    echo json_encode(['ok' => true, 'msg' => 'Profile updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Server error']);
}
