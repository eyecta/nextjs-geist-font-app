<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';

session_start();

$token = $_GET['token'] ?? '';
$verified = false;
$message = '';

if (!empty($token)) {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_verified']) {
            $message = 'Your email is already verified.';
        } else {
            $updateStmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            if ($updateStmt->execute([$user['id']])) {
                $verified = true;
                $message = 'Email verification successful! You can now log in.';
            } else {
                $message = 'Failed to verify email. Please try again later.';
            }
        }
    } else {
        $message = 'Invalid or expired verification token.';
    }
} else {
    $message = 'No verification token provided.';
}
?>
<?php include 'templates/header.php'; ?>
<div class="container">
    <h2>Email Verification</h2>
    <p><?php echo htmlspecialchars($message); ?></p>
    <?php if ($verified): ?>
        <p><a href="login.php">Click here to log in</a></p>
    <?php endif; ?>
</div>
<?php include 'templates/footer.php'; ?>
