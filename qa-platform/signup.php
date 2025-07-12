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

    $username = Utils::sanitizeInput($_POST['username'] ?? '');
    $email = Utils::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }

    if (!Utils::validateEmail($email)) {
        $errors[] = 'Invalid email address.';
    }

    if (!Utils::validatePassword($password)) {
        $errors[] = 'Password must be at least 8 characters, include uppercase, lowercase, and a number.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $db = Database::getInstance();
        $existingUser = $db->getUserByEmail($email);
        if ($existingUser) {
            $errors[] = 'Email is already registered.';
        } else {
            $passwordHash = Utils::hashPassword($password);
            $verificationToken = Utils::generateToken(16);

            $created = $db->createUser($username, $email, $passwordHash, $verificationToken);
            if ($created) {
                // Send verification email
                $verificationLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/qa-platform/verify_email.php?token={$verificationToken}";
                $emailContent = Utils::generateVerificationEmail($username, $verificationLink);
                Utils::sendEmail($email, 'Verify your email address', $emailContent);

                $success = true;
            } else {
                $errors[] = 'Failed to create user. Please try again later.';
            }
        }
    }
}

$csrfToken = Utils::generateCSRFToken();
?>
<?php include 'templates/header.php'; ?>
<div class="container">
    <h2>Sign Up</h2>
    <?php if ($success): ?>
        <p>Registration successful! Please check your email to verify your account.</p>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" action="signup.php" novalidate>
            <label for="username">Username</label><br />
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES); ?>" required /><br />

            <label for="email">Email</label><br />
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" required /><br />

            <label for="password">Password</label><br />
            <input type="password" id="password" name="password" required /><br />

            <label for="confirm_password">Confirm Password</label><br />
            <input type="password" id="confirm_password" name="confirm_password" required /><br />

            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>" />
            <button type="submit">Register</button>
        </form>
    <?php endif; ?>
</div>
<?php include 'templates/footer.php'; ?>
