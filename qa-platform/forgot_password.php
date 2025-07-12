<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';

session_start();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Utils::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }

    $email = Utils::sanitizeInput($_POST['email'] ?? '');

    if (!Utils::validateEmail($email)) {
        $errors[] = 'Invalid email address.';
    }

    if (empty($errors)) {
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $resetToken = Utils::generateToken(16);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

            // Store reset token and expiry in admin_settings table or create a new table for password_resets
            $stmtInsert = $pdo->prepare("INSERT OR REPLACE INTO admin_settings (setting_key, setting_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
            $stmtInsert->execute(["password_reset_{$user['id']}_token", $resetToken]);
            $stmtInsert->execute(["password_reset_{$user['id']}_expires", $expiresAt]);

            $resetLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/qa-platform/reset_password.php?token={$resetToken}&uid={$user['id']}";

            $emailContent = "
            <html>
            <body>
                <p>Hello {$user['username']},</p>
                <p>You requested a password reset. Click the link below to reset your password. This link expires in 1 hour.</p>
                <p><a href='{$resetLink}'>Reset Password</a></p>
                <p>If you did not request this, please ignore this email.</p>
            </body>
            </html>
            ";

            Utils::sendEmail($email, 'Password Reset Request', $emailContent);
            $success = true;
        } else {
            $errors[] = 'Email address not found.';
        }
    }
}

$csrfToken = Utils::generateCSRFToken();
?>
<?php include 'templates/header.php'; ?>
<div class="container">
    <h2>Forgot Password</h2>
    <?php if ($success): ?>
        <p>A password reset link has been sent to your email address.</p>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" action="forgot_password.php" novalidate>
            <label for="email">Email</label><br />
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" required /><br />
            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>" />
            <button type="submit">Send Reset Link</button>
        </form>
    <?php endif; ?>
</div>
<?php include 'templates/footer.php'; ?>
