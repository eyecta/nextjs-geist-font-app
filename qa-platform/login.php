<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';

session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Utils::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }

    $email = Utils::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!Utils::validateEmail($email)) {
        $errors[] = 'Invalid email address.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $db = Database::getInstance();
        $user = $db->getUserByEmail($email);

        if ($user && Utils::verifyPassword($password, $user['password_hash'])) {
            if (!$user['is_verified']) {
                $errors[] = 'Please verify your email before logging in.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: index.php');
                exit;
            }
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

$csrfToken = Utils::generateCSRFToken();
?>
<?php include 'templates/header.php'; ?>
<div class="container">
    <h2>Login</h2>
    <?php if (!empty($errors)): ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post" action="login.php" novalidate>
        <label for="email">Email</label><br />
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" required /><br />

        <label for="password">Password</label><br />
        <input type="password" id="password" name="password" required /><br />

        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>" />
        <button type="submit">Login</button>
    </form>
</div>
<?php include 'templates/footer.php'; ?>
