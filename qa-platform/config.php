<?php
// Database Configuration
define('DB_PATH', __DIR__ . '/qa_platform.db');

// OpenRouter API Configuration
define('OPENROUTER_API_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions');
define('OPENROUTER_API_KEY', ''); // To be set in admin panel
define('OPENROUTER_MODEL', 'openai/gpt-4o'); // Default model

// Query Limits
define('FREE_QUERIES_UNREGISTERED', 10);
define('FREE_QUERIES_REGISTERED', 20);

// Email Settings (SMTP)
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'your-email@example.com');
define('SMTP_PASS', 'yourpassword');
define('SMTP_SENDER', 'QA Platform <no-reply@example.com>');

// Cloudflare Turnstile Keys
define('TURNSTILE_SITE_KEY', '');
define('TURNSTILE_SECRET_KEY', '');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Site Configuration
define('SITE_NAME', 'AI Q&A Platform');
define('SITE_DESCRIPTION', 'Modern AI-powered question and answer platform');
define('TRENDING_QUERIES_COUNT', 5);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 for development
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
