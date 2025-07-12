<?php
require_once __DIR__ . '/../config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $this->pdo = new PDO('sqlite:' . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initializeTables();
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function initializeTables() {
        $queries = [
            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                free_queries_remaining INTEGER DEFAULT " . FREE_QUERIES_REGISTERED . ",
                credits INTEGER DEFAULT 0,
                is_verified BOOLEAN DEFAULT 0,
                verification_token VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            // Cached responses table
            "CREATE TABLE IF NOT EXISTS responses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_text TEXT UNIQUE NOT NULL,
                query_hash VARCHAR(64) UNIQUE NOT NULL,
                answer TEXT NOT NULL,
                word_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            // Query history table
            "CREATE TABLE IF NOT EXISTS query_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                session_id VARCHAR(255),
                query TEXT NOT NULL,
                answer TEXT NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Transactions table
            "CREATE TABLE IF NOT EXISTS transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                credits_purchased INTEGER NOT NULL,
                payment_method VARCHAR(50),
                transaction_id VARCHAR(255),
                status VARCHAR(20) DEFAULT 'pending',
                transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Admin settings table
            "CREATE TABLE IF NOT EXISTS admin_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            // Static pages table
            "CREATE TABLE IF NOT EXISTS static_pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug VARCHAR(100) UNIQUE NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                meta_description TEXT,
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        ];

        foreach ($queries as $query) {
            $this->pdo->exec($query);
        }

        // Insert default admin settings if they don't exist
        $this->insertDefaultSettings();
    }

    private function insertDefaultSettings() {
        $defaultSettings = [
            ['openrouter_api_key', ''],
            ['openrouter_model', OPENROUTER_MODEL],
            ['turnstile_site_key', ''],
            ['turnstile_secret_key', ''],
            ['smtp_host', SMTP_HOST],
            ['smtp_port', SMTP_PORT],
            ['smtp_user', SMTP_USER],
            ['smtp_pass', SMTP_PASS],
            ['site_name', SITE_NAME],
            ['site_description', SITE_DESCRIPTION],
            ['trending_queries_count', TRENDING_QUERIES_COUNT]
        ];

        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO admin_settings (setting_key, setting_value) VALUES (?, ?)");
        
        foreach ($defaultSettings as $setting) {
            $stmt->execute($setting);
        }
    }

    public function getSetting($key) {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : null;
    }

    public function setSetting($key, $value) {
        $stmt = $this->pdo->prepare("INSERT OR REPLACE INTO admin_settings (setting_key, setting_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
        return $stmt->execute([$key, $value]);
    }

    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createUser($username, $email, $passwordHash, $verificationToken = null) {
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, verification_token) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$username, $email, $passwordHash, $verificationToken]);
    }

    public function updateUserQueries($userId, $remaining) {
        $stmt = $this->pdo->prepare("UPDATE users SET free_queries_remaining = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$remaining, $userId]);
    }

    public function addQueryHistory($userId, $sessionId, $query, $answer, $ipAddress, $userAgent) {
        $stmt = $this->pdo->prepare("INSERT INTO query_history (user_id, session_id, query, answer, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $sessionId, $query, $answer, $ipAddress, $userAgent]);
    }

    public function getTrendingQueries($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT query, COUNT(*) as count 
            FROM query_history 
            WHERE timestamp > datetime('now', '-7 days')
            GROUP BY query 
            ORDER BY count DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getUserQueryHistory($userId, $limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT query, answer, timestamp 
            FROM query_history 
            WHERE user_id = ? 
            ORDER BY timestamp DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
}
?>
