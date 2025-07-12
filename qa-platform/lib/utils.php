<?php
require_once __DIR__ . '/../config.php';

class Utils {
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Sanitize input string
     */
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email address
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
    }

    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Generate random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Get client IP address
     */
    public static function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get user agent
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }

    /**
     * Log error message
     */
    public static function logError($message, $context = []) {
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
        if (!empty($context)) {
            $logMessage .= ' - Context: ' . json_encode($context);
        }
        error_log($logMessage);
    }

    /**
     * Send JSON response
     */
    public static function sendJSONResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to URL
     */
    public static function redirect($url, $statusCode = 302) {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     */
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Set flash message
     */
    public static function setFlashMessage($type, $message) {
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Get and clear flash messages
     */
    public static function getFlashMessages() {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }

    /**
     * Count words in text
     */
    public static function countWords($text) {
        return str_word_count(strip_tags($text));
    }

    /**
     * Generate query hash for caching
     */
    public static function generateQueryHash($query) {
        return hash('sha256', strtolower(trim($query)));
    }

    /**
     * Format timestamp
     */
    public static function formatTimestamp($timestamp, $format = 'Y-m-d H:i:s') {
        return date($format, strtotime($timestamp));
    }

    /**
     * Truncate text
     */
    public static function truncateText($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . $suffix;
    }

    /**
     * Validate Turnstile CAPTCHA
     */
    public static function validateTurnstile($token) {
        $secretKey = Database::getInstance()->getSetting('turnstile_secret_key');
        
        if (empty($secretKey) || empty($token)) {
            return false;
        }

        $data = [
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => self::getClientIP()
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
        
        if ($result === false) {
            return false;
        }

        $response = json_decode($result, true);
        return isset($response['success']) && $response['success'] === true;
    }

    /**
     * Send email (basic implementation)
     */
    public static function sendEmail($to, $subject, $message, $headers = []) {
        $defaultHeaders = [
            'From: ' . SMTP_SENDER,
            'Reply-To: ' . SMTP_SENDER,
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0'
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);
        
        return mail($to, $subject, $message, implode("\r\n", $allHeaders));
    }

    /**
     * Generate verification email content
     */
    public static function generateVerificationEmail($username, $verificationLink) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Welcome to " . SITE_NAME . "!</h2>
                <p>Hello {$username},</p>
                <p>Thank you for registering with our AI-powered Q&A platform. To complete your registration, please verify your email address by clicking the link below:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$verificationLink}' style='background-color: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email Address</a>
                </p>
                <p>If you didn't create an account with us, please ignore this email.</p>
                <p>Best regards,<br>The " . SITE_NAME . " Team</p>
            </div>
        </body>
        </html>
        ";
    }
}
?>
