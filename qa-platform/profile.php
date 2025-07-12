<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';

session_start();

if (!Utils::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$userId = Utils::getCurrentUserId();
$user = $db->getUserById($userId);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Utils::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }

    $username = Utils::sanitizeInput($_POST['username'] ?? '');
    $email = Utils::sanitizeInput($_POST['email'] ?? '');

    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }

    if (!Utils::validateEmail($email)) {
        $errors[] = 'Invalid email address.';
    }

    if (empty($errors)) {
        // Update user profile
        $stmt = $db->getConnection()->prepare("UPDATE users SET username = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updated = $stmt->execute([$username, $email, $userId]);
        if ($updated) {
            $success = true;
            $user = $db->getUserById($userId);
        } else {
            $errors[] = 'Failed to update profile.';
        }
    }
}

// Fetch query history
$queryHistory = $db->getUserQueryHistory($userId);

$csrfToken = Utils::generateCSRFToken();
?>
<?php include 'templates/header.php'; ?>
<div class="container">
    <h2>Profile</h2>
    <?php if ($success): ?>
        <p class="success-message">Profile updated successfully.</p>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post" action="profile.php" novalidate>
        <label for="username">Username</label><br />
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES); ?>" required /><br />

        <label for="email">Email</label><br />
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES); ?>" required /><br />

        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>" />
        <button type="submit">Update Profile</button>
    </form>

    <h3>Query History</h3>
    <?php if (!empty($queryHistory)): ?>
        <ul>
            <?php foreach ($queryHistory as $entry): ?>
                <li>
                    <strong><?php echo htmlspecialchars($entry['query']); ?></strong><br />
                    <small><?php echo Utils::formatTimestamp($entry['timestamp']); ?></small><br />
                    <div><?php echo nl2br(htmlspecialchars(Utils::truncateText(strip_tags($entry['answer']), 200))); ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No query history found.</p>
    <?php endif; ?>
</div>
<?php include 'templates/footer.php'; ?>
