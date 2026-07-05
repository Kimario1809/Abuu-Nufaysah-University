<?php
namespace App\Core;

class Session {
    private static $instance = null;
    
    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration for production
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', 3600); // 1 hour
            
            session_start();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public function remove($key) {
        unset($_SESSION[$key]);
    }
    
    /**
     * Securely destroy session
     * Clears all session data and destroys the session
     */
    public function destroy() {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        session_destroy();
    }
    
    /**
     * Regenerate session ID
     * 
     * @param bool $deleteOld Delete old session file
     * @return bool
     */
    public function regenerate($deleteOld = true) {
        return session_regenerate_id($deleteOld);
    }
    
    public function getFlash($key, $default = null) {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }
    
    public function setFlash($key, $value) {
        $this->set($key, $value);
    }
    
    /**
     * Validate session (check for session fixation)
     * 
     * @return bool
     */
    public function validate() {
        $ip = $this->get('ip_address');
        $userAgent = $this->get('user_agent');
        
        if ($ip && $ip !== $this->getClientIP()) {
            return false;
        }
        
        if ($userAgent && $userAgent !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    private function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        return $ip;
    }
    
    public static function getActiveCount() {
        // This is a simplified implementation
        // In a real application, you would track active sessions in a database or Redis
        return rand(5, 50); // Placeholder value
    }
}