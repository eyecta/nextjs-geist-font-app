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

$errors = [];
$success = false;

// For simplicity, this example will show static plans and simulate payment success.
// Real integration requires Stripe/PayPal SDK and webhook handling.

$plans = [
    ['id' => 1, 'name' => 'Basic', 'credits' => 10, 'price' => 9.99, 'interval' => 'monthly'],
    ['id' => 2, 'name' => 'Pro', 'credits' => 50, 'price' => 39.99, 'interval' => 'monthly'],
    ['id' => 3, 'name' => 'Lifetime', 'credits' => 500, 'price' => 199.99, 'interval' => 'one-time'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId = (int)($_POST['plan_id'] ?? 0);
    $selectedPlan = null;
    foreach ($plans as $plan) {
        if ($plan['id'] === $planId) {
            $selectedPlan = $plan;
            break;
        }
    }
    if (!$selectedPlan) {
        $errors[] = 'Invalid subscription plan selected.';
    } else {
        // Simulate payment success
        // In real app, redirect to payment gateway and handle webhook for confirmation

        // Update user credits
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
        if ($stmt->execute([$selectedPlan['credits'], $userId])) {
            $success = true;
        } else {
            $errors[] = 'Failed to update credits. Please contact support.';
        }
    }
}

$csrfToken = Utils::generateCSRFToken();
?>
<?php include 'templates/header.php'; ?>
<div class="container">
    <h2>Purchase Credits</h2>
    <?php if ($success): ?>
        <p>Thank you for your purchase! Your credits have been updated.</p>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" action="purchase.php" novalidate>
            <label for="plan_id">Select a subscription plan:</label><br />
            <select id="plan_id" name="plan_id" required>
                <option value="">-- Choose a plan --</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?php echo $plan['id']; ?>">
                        <?php echo htmlspecialchars($plan['name']); ?> - <?php echo $plan['credits']; ?> credits for $<?php echo number_format($plan['price'], 2); ?> (<?php echo $plan['interval']; ?>)
                    </option>
                <?php endforeach; ?>
            </select><br /><br />
            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>" />
            <button type="submit">Purchase</button>
        </form>
    <?php endif; ?>
</div>
<?php include 'templates/footer.php'; ?>
