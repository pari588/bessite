<?php
/**
 * User Password Management Interface
 * Allows admin to set passwords for new users
 */

require_once __DIR__.'/../lib/auth.php';
auth_require();

require_once __DIR__.'/../lib/db.php';

$page_title = 'User Password Management';
include __DIR__.'/_layout_top.php';

$message = null;
$error = null;

// Get user ID from URL parameter or form
$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($newPassword)) {
        $error = 'Password is required';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Update password
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$passwordHash, $userId]);

            $message = 'Password has been set successfully!';

            // Log the action (if activity_log table exists)
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO activity_log (user_id, action, description, created_at)
                    VALUES (?, ?, ?, NOW())
                ');
                $stmt->execute([
                    $_SESSION['uid'],
                    'set_password',
                    'Set password for user ID ' . $userId
                ]);
            } catch (Exception $logError) {
                // Silently skip logging if table doesn't exist
            }
        } catch (Exception $e) {
            $error = 'Failed to update password: ' . $e->getMessage();
        }
    }
}

// Get all users for dropdown
$usersStmt = $pdo->query('SELECT id, name, email FROM users ORDER BY name');
$users = $usersStmt->fetchAll();

// Get selected user details if specified
$selectedUser = null;
if ($userId) {
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $selectedUser = $stmt->fetch();
}

?>

<div style="max-width: 600px; margin: 40px auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0;">Set User Password</h2>
        <md-filled-button onclick="location.href='settings.php'">
            <span class="material-symbols-rounded" style="margin-right: 6px;">arrow_back</span>
            Back to Settings
        </md-filled-button>
    </div>

    <?php if ($message): ?>
        <div style="padding: 16px; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 4px; margin-bottom: 24px;">
            <strong style="color: #2e7d32;">✓ Success</strong>
            <p style="margin: 8px 0 0 0; color: #1b5e20;"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="padding: 16px; background: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px; margin-bottom: 24px;">
            <strong style="color: #c62828;">✕ Error</strong>
            <p style="margin: 8px 0 0 0; color: #b71c1c;"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <div style="background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 24px;">
        <form method="post">
            <!-- User Selection -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; color: #666; margin-bottom: 8px; font-weight: 500;">
                    Select User
                </label>
                <select name="user_id" id="userSelect" onchange="document.getElementById('userForm').submit();" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <option value="">-- Choose a user --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= $user['id'] == $userId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="user_id" id="userForm" value="<?= htmlspecialchars($userId ?? '') ?>">
            </div>

            <!-- User Info Display (if selected) -->
            <?php if ($selectedUser): ?>
                <div style="padding: 16px; background: #f5f5f5; border-radius: 4px; margin-bottom: 20px;">
                    <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Name</div>
                    <div style="font-weight: 600; margin-bottom: 12px;"><?= htmlspecialchars($selectedUser['name']) ?></div>

                    <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Email</div>
                    <div style="font-weight: 600; font-family: monospace; margin-bottom: 12px;"><?= htmlspecialchars($selectedUser['email']) ?></div>
                </div>

                <!-- Password Fields -->
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 14px; color: #666; margin-bottom: 8px; font-weight: 500;">
                        New Password
                    </label>
                    <input type="password" name="password" id="password" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;" placeholder="Enter new password (min 8 characters)">
                    <div style="font-size: 12px; color: #999; margin-top: 6px;">
                        Minimum 8 characters recommended
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; color: #666; margin-bottom: 8px; font-weight: 500;">
                        Confirm Password
                    </label>
                    <input type="password" name="password_confirm" id="password_confirm" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;" placeholder="Re-enter password">
                </div>

                <!-- Submit Button -->
                <button type="submit" style="padding: 12px 24px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; width: 100%;">
                    Set Password
                </button>
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
            <?php else: ?>
                <div style="padding: 16px; background: #f5f5f5; border: 1px dashed #999; border-radius: 4px; text-align: center; color: #666;">
                    Select a user from the dropdown above to set their password
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Instructions -->
    <div style="margin-top: 32px; padding: 16px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
        <strong style="color: #1565c0;">Instructions:</strong>
        <ol style="margin: 12px 0 0 0; color: #1565c0; padding-left: 20px;">
            <li>Select the user from the dropdown</li>
            <li>Enter a new password (minimum 8 characters)</li>
            <li>Confirm the password by entering it again</li>
            <li>Click "Set Password"</li>
            <li>User can now login with their email and new password</li>
        </ol>
    </div>
</div>

<?php include __DIR__.'/_layout_bottom.php'; ?>
