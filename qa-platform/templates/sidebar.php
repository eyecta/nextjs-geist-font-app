<?php
require_once __DIR__ . '/../lib/db.php';

$db = Database::getInstance();
$trendingCount = (int)$db->getSetting('trending_queries_count') ?: 5;
$trendingQueries = $db->getTrendingQueries($trendingCount);
?>

<aside class="sidebar">
    <h2>Trending Queries</h2>
    <ul>
        <?php if (!empty($trendingQueries)): ?>
            <?php foreach ($trendingQueries as $item): ?>
                <li>
                    <a href="index.php?question=<?php echo urlencode($item['query']); ?>">
                        <?php echo htmlspecialchars($item['query']); ?> (<?php echo $item['count']; ?>)
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>No trending queries yet.</li>
        <?php endif; ?>
    </ul>
</aside>
