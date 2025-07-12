<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

header('Content-Type: application/xml; charset=utf-8');

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/qa-platform/";

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Static pages -->
    <url>
        <loc><?php echo $baseUrl; ?>index.php</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>signup.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>login.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>profile.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>purchase.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

<?php
$db = Database::getInstance();
$pdo = $db->getConnection();

// Include recent queries as URLs with query parameter
$stmt = $pdo->prepare("SELECT DISTINCT query_text FROM responses ORDER BY updated_at DESC LIMIT 50");
$stmt->execute();
$queries = $stmt->fetchAll();

foreach ($queries as $row) {
    $query = urlencode($row['query_text']);
    $url = $baseUrl . "index.php?question={$query}";
    echo "    <url>\n";
    echo "        <loc>{$url}</loc>\n";
    echo "        <changefreq>weekly</changefreq>\n";
    echo "        <priority>0.7</priority>\n";
    echo "    </url>\n";
}
?>
</urlset>
