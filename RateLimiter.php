<?php
namespace App\Core;

/**
 * Rate Limiter Class
 * Prevents brute-force attacks by limiting login attempts
 */
class RateLimiter {
    private static $instance = null;
    private $db;
    private $maxAttempts = 5;
    private $decayMinutes = 15;
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if IP is rate limited
     * 
     * @param string $ip IP address
     * @return bool
     */
    public function isRateLimited($ip) {
        $attempts = $this->getAttempts($ip);
        return $attempts >= $this->maxAttempts;
    }
    
    /**
     * Get number of attempts for IP
     * 
     * @param string $ip IP address
     * @return int
     */
    public function getAttempts($ip) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM login_attempts 
            WHERE ip_address = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        $stmt->execute([$ip, $this->decayMinutes]);
        $result = $stmt->fetch();
        return (int)$result['count'];
    }
    
    /**
     * Record a failed login attempt
     * 
     * @param string $ip IP address
     * @param string $email Email/identifier
     * @return void
     */
    public function recordAttempt($ip, $email = null) {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (ip_address, email, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$ip, $email]);
    }
    
    /**
     * Clear login attempts for IP
     * 
     * @param string $ip IP address
     * @return void
     */
    public function clearAttempts($ip) {
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);
    }
    
    /**
     * Get time until next attempt allowed
     * 
     * @param string $ip IP address
     * @return int Seconds until next attempt
     */
    public function availableIn($ip) {
        if (!$this->isRateLimited($ip)) {
            return 0;
        }
        
        $stmt = $this->db->prepare("
            SELECT created_at 
            FROM login_attempts 
            WHERE ip_address = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$ip]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return 0;
        }
        
        $attemptTime = strtotime($result['created_at']);
        $availableAt = $attemptTime + ($this->decayMinutes * 60);
        $remaining = $availableAt - time();
        
        return max(0, $remaining);
    }
    
    /**
     * Set max attempts
     * 
     * @param int $max Max attempts
     * @return self
     */
    public function setMaxAttempts($max) {
        $this->maxAttempts = $max;
        return $this;
    }
    
    /**
     * Set decay time in minutes
     * 
     * @param int $minutes Decay minutes
     * @return self
     */
    public function setDecayMinutes($minutes) {
        $this->decayMinutes = $minutes;
        return $this;
    }
}
