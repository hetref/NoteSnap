<?php

class Session
{
    private static $instance = null;
    private $db;
    private $user_id = null;
    private $is_logged_in = false;

    private function __construct()
    {
        session_start([
            'cookie_httponly' => true,     // Prevent XSS accessing session cookie
            'cookie_secure' => true,       // Only send cookie over HTTPS
            'cookie_samesite' => 'Strict', // Prevent CSRF
            'use_strict_mode' => true      // Enforce strict session id validation
        ]);

        $this->db = Database::getInstance();
        $this->checkSession();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function checkSession()
    {
        if (isset($_SESSION['user_id']) && isset($_SESSION['token'])) {
            $stmt = $this->db->prepare("
                SELECT u.id, u.username, s.expires_at 
                FROM user_sessions s
                JOIN users u ON s.user_id = u.id
                WHERE s.user_id = ? AND s.session_token = ? AND s.is_active = 1
                AND s.expires_at > NOW()
            ");

            $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['token']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $this->user_id = $row['id'];
                $this->is_logged_in = true;

                // Extend session if it's close to expiring
                if (strtotime($row['expires_at']) - time() < 3600) {
                    $this->extendSession();
                }
            } else {
                $this->destroy();
            }
        }
    }

    public function createSession($user_id)
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (user_id, session_token, expires_at)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param("iss", $user_id, $token, $expires);

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['token'] = $token;
            $this->user_id = $user_id;
            $this->is_logged_in = true;
            return true;
        }

        return false;
    }

    private function extendSession()
    {
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $this->db->prepare("
            UPDATE user_sessions 
            SET expires_at = ? 
            WHERE user_id = ? AND session_token = ?
        ");

        $stmt->bind_param("sis", $expires, $_SESSION['user_id'], $_SESSION['token']);
        $stmt->execute();
    }

    public function destroy()
    {
        if (isset($_SESSION['user_id']) && isset($_SESSION['token'])) {
            $stmt = $this->db->prepare("
                UPDATE user_sessions 
                SET is_active = 0 
                WHERE user_id = ? AND session_token = ?
            ");

            $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['token']);
            $stmt->execute();
        }

        $this->user_id = null;
        $this->is_logged_in = false;
        session_destroy();

        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }

    public function isLoggedIn()
    {
        return $this->is_logged_in;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function requireAuth()
    {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }
}
