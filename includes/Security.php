<?php

class Security
{
    private static $instance = null;
    private $db;
    private $session;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();

        // Set security headers
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:;");
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }

        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    public function validateCSRFToken()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (
                !isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
                !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
            ) {
                http_response_code(403);
                die('Invalid CSRF token');
            }
        }
    }

    public function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validatePassword($password)
    {
        // At least 8 characters long
        // Contains at least one uppercase letter
        // Contains at least one lowercase letter
        // Contains at least one number
        // Contains at least one special character
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        return preg_match($pattern, $password);
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public function logActivity($userId, $actionType, $details = [], $ip = null)
    {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $details = json_encode($details);

        $stmt = $this->db->prepare("
            INSERT INTO activity_log (user_id, action_type, action_details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param("issss", $userId, $actionType, $details, $ip, $userAgent);
        return $stmt->execute();
    }

    public function rateLimit($key, $limit = 5, $timeframe = 300)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $now = time();

        // Clean up old attempts
        $stmt = $this->db->prepare("
            DELETE FROM rate_limits 
            WHERE ip_address = ? AND action_key = ? AND timestamp < ?
        ");

        $oldTime = $now - $timeframe;
        $stmt->bind_param("ssi", $ip, $key, $oldTime);
        $stmt->execute();

        // Count recent attempts
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM rate_limits 
            WHERE ip_address = ? AND action_key = ?
        ");

        $stmt->bind_param("ss", $ip, $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];

        if ($count >= $limit) {
            http_response_code(429);
            die('Too many attempts. Please try again later.');
        }

        // Log this attempt
        $stmt = $this->db->prepare("
            INSERT INTO rate_limits (ip_address, action_key, timestamp)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param("ssi", $ip, $key, $now);
        $stmt->execute();

        return true;
    }

    public function encryptData($data, $userId)
    {
        $key = $this->getUserEncryptionKey($userId);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decryptData($data, $userId)
    {
        $key = $this->getUserEncryptionKey($userId);
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    private function getUserEncryptionKey($userId)
    {
        // In a production environment, this should be stored securely
        // Consider using a Hardware Security Module (HSM) or secure key management service
        return hash('sha256', $userId . getenv('ENCRYPTION_KEY'), true);
    }
}
