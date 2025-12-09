<?php
header('Content-Type: application/json');
require_once __DIR__.'/../lib/auth.php';
auth_require();
require_once __DIR__.'/../lib/db.php';

try {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (!$name || !$email || !$password || !$role) {
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

    // Check if email already exists
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Email already in use']);
        exit;
    }

    // Hash password and create user
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, $hash, $role]);

    echo json_encode(['ok' => true, 'msg' => 'User created successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Server error']);
}
