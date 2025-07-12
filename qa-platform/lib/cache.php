<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';

class Cache {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get cached response for a query
     * @param string $query
     * @return string|null
     */
    public function getCachedResponse(string $query): ?string {
        $queryHash = Utils::generateQueryHash($query);
        $stmt = $this->db->prepare("SELECT answer FROM responses WHERE query_hash = ?");
        $stmt->execute([$queryHash]);
        $row = $stmt->fetch();
        return $row ? $row['answer'] : null;
    }

    /**
     * Save response to cache
     * @param string $query
     * @param string $answer
     * @return bool
     */
    public function saveResponse(string $query, string $answer): bool {
        $queryHash = Utils::generateQueryHash($query);
        $wordCount = Utils::countWords($answer);
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO responses (query_text, query_hash, answer, word_count, created_at, updated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        return $stmt->execute([$query, $queryHash, $answer, $wordCount]);
    }
}
?>
