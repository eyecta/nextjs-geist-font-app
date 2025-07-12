<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/ai.php';

session_start();

$answer = null;
$question = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Utils::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        die('Invalid CSRF token');
    }

    $question = Utils::sanitizeInput($_POST['question'] ?? '');
    if (!empty($question)) {
        $ai = new AI();
        $answer = $ai->askAI($question);
    }
}

$csrfToken = Utils::generateCSRFToken();
?>
<?php include 'templates/header.php'; ?>
<div class="container">
    <form id="question-form" method="post" action="index.php" class="search-form">
        <input type="text" name="question" placeholder="What’s on your mind?" value="<?php echo htmlspecialchars($question ?? '', ENT_QUOTES); ?>" required />
        <button type="submit" aria-label="Ask question">💡</button>
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>" />
    </form>

    <?php if ($answer !== null): ?>
        <div class="knowledge-panel">
            <?php
            require_once __DIR__ . '/vendor/parsedown/Parsedown.php';
            $parsedown = new Parsedown();
            echo $parsedown->text($answer);
            ?>
        </div>
    <?php endif; ?>

    <?php include 'templates/sidebar.php'; ?>
</div>
<?php include 'templates/footer.php'; ?>
