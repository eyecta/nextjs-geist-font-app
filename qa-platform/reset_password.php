<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';

session_start();

$errors = [];
$success = false;

$token = $_GET['token'] ?? '';
$userId = (int)($_GET['uid'] ?? 0);

if (empty($token) || $userId <= 0) {
    die('Invalid password reset link.');
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT setting_value AS token FROM admin_settings WHERE setting_key = ?");
$stmt->execute(["password_reset_{$userId}_token"]);
$storedToken = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT setting_value AS expires FROM admin_settings WHERE setting_key = ?");
$stmt->execute(["password_reset_{$userId}_expires"]);
$expires = $stmt->fetchColumn();

if ($storedToken !== $token || strtotime($expires) < time()) {
    die('Password reset link is invalid or expired.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Utils::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!Utils::validatePassword($password)) {
        $errors[] = 'Password must be at least 8 characters, include uppercase, lowercase, and a number.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $passwordHash = Utils::hashPassword($password);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if ($stmt->execute([$passwordHash, $userId])) {
            // Invalidate token
            $stmt = $pdo->prepare("DELETE FROM admin_settings WHERE setting_key IN (?, ?)");
            $stmt->execute(["password_reset_{$userId}_token", "password_reset_{$userId}_expires"]);
            $success = true;
        } else {
            $errors[] = 'Failed to update password. Please try again later.';
        }
    }
}

$csrfToken = Utils::generateCSRFToken();
?>
<?php include 'templates/header.php'; ?>
<div class="container">
    <h2>Reset Password</h2>
    <?php if ($success): ?>
        <p>Your password has been reset successfully. You can now <a href="login.php">log in</a>.</p>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" action="reset_password.php?token=<?php echo urlencode($token); ?>&uid=<?php echo $userId; ?>" novalidate>
            <label for="password">New Password</label><br />
            <input type="password" id="password" name="password" required /><br />

            <label for="confirm_password">Confirm New Password</label><br />
            <input type="password" id="confirm_password" name="confirm_password" required /><br />

            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>" />
            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>
</div>
<?php include 'templates/footer.php'; ?>
