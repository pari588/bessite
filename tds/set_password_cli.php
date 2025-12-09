#!/usr/bin/env php
<?php
/**
 * Command-line Tool for Setting User Passwords
 * Usage: php set_password_cli.php <email> <password>
 * Example: php set_password_cli.php akash.tdf@gmail.com MySecurePassword123
 */

require_once __DIR__.'/lib/db.php';

// Check if arguments are provided
if ($argc < 3) {
    echo "usage: php set_password_cli.php <email> <password>\n";
    echo "\n";
    echo "example:\n";
    echo "  php set_password_cli.php akash.tdf@gmail.com MySecurePassword123\n";
    echo "\n";
    echo "options:\n";
    echo "  --list     List all users\n";
    echo "  --help     Show this help message\n";
    exit(1);
}

$command = $argv[1];

// Handle --list flag
if ($command === '--list') {
    echo "Available Users:\n";
    echo str_repeat("-", 60) . "\n";
    echo sprintf("%-3s | %-30s | %-25s\n", "ID", "Name", "Email");
    echo str_repeat("-", 60) . "\n";

    $stmt = $pdo->query('SELECT id, name, email FROM users ORDER BY id');
    while ($user = $stmt->fetch()) {
        echo sprintf("%-3s | %-30s | %-25s\n",
            $user['id'],
            substr($user['name'], 0, 28),
            substr($user['email'], 0, 23)
        );
    }
    echo str_repeat("-", 60) . "\n";
    exit(0);
}

// Handle --help flag
if ($command === '--help' || $command === '-h') {
    echo "Set User Password - CLI Tool\n";
    echo str_repeat("=", 60) . "\n\n";
    echo "usage: php set_password_cli.php <email> <password>\n";
    echo "\n";
    echo "arguments:\n";
    echo "  email                 User's email address\n";
    echo "  password              New password for the user\n";
    echo "\n";
    echo "examples:\n";
    echo "  php set_password_cli.php akash.tdf@gmail.com MyPassword123\n";
    echo "  php set_password_cli.php admin@example.com AdminPass456\n";
    echo "\n";
    echo "options:\n";
    echo "  --list               List all registered users\n";
    echo "  --help               Show this help message\n";
    echo "\n";
    echo "password requirements:\n";
    echo "  - Minimum 8 characters\n";
    echo "  - Can contain letters, numbers, symbols\n";
    exit(0);
}

// Get email and password from arguments
$email = $command;
$password = $argv[2];

// Validate password
if (strlen($password) < 8) {
    echo "Error: Password must be at least 8 characters long\n";
    exit(1);
}

// Find user by email
$stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "Error: User not found with email: $email\n";
    echo "\nUse 'php set_password_cli.php --list' to see available users\n";
    exit(1);
}

// Generate password hash
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Update password
try {
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$passwordHash, $user['id']]);

    echo "✓ Success!\n";
    echo str_repeat("-", 60) . "\n";
    echo "User:     " . $user['name'] . "\n";
    echo "Email:    " . $user['email'] . "\n";
    echo "Password: " . (strlen($password) > 15 ? substr($password, 0, 15) . '...' : $password) . "\n";
    echo str_repeat("-", 60) . "\n";
    echo "\nThe user can now login with:\n";
    echo "  Email:    " . $user['email'] . "\n";
    echo "  Password: (the password you just set)\n";
    exit(0);
} catch (Exception $e) {
    echo "Error: Failed to update password\n";
    echo "Details: " . $e->getMessage() . "\n";
    exit(1);
}
?>
