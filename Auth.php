<?php
namespace App\Core;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AuditLog;
use App\Helpers\ActivityLogger;

class Auth {
    private static $instance = null;
    private $session;
    
    private function __construct() {
        $this->session = Session::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function login($identifier, $password) {
        $user = User::findByEmailOrPhone($identifier);
        
        if (!$user) {
            ActivityLogger::failedLogin($identifier, 'User not found');
            return ['success' => false, 'message' => 'User not found'];
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            ActivityLogger::failedLogin($identifier, 'Invalid password');
            return ['success' => false, 'message' => 'Invalid password'];
        }
        
        if (!$user['is_active']) {
            ActivityLogger::failedLogin($identifier, 'Account is deactivated');
            return ['success' => false, 'message' => 'Account is deactivated'];
        }
        
        // Update last login
        User::updateLastLogin($user['id']);
        
        // Regenerate session ID for security
        $this->session->regenerate();
        
        // Start session
        $roles = Role::getUserRoles($user['id']);
        $roleNames = [];
        foreach ($roles as $role) {
            $roleNames[] = $role['name'];
        }
        $primaryRole = !empty($roleNames) ? $roleNames[0] : $user['role'];

        $this->session->set('user_id', $user['id']);
        $this->session->set('user_role', $primaryRole);
        $this->session->set('user_name', $user['full_name']);
        $this->session->set('user_email', $user['email']);
        $this->session->set('user_roles', $roleNames);
        $this->session->set('login_time', time());
        $this->session->set('ip_address', $this->getClientIP());

        // Log to both AuditLog and ActivityLogger
        AuditLog::log($user['id'], 'login', 'auth', 'User logged in');
        ActivityLogger::login($user['id']);
        
        return [
            'success' => true,
            'user' => $user,
            'role' => $user['role']
        ];
    }
    
    /**
     * Secure logout mechanism
     * Destroys session completely and logs the logout
     * 
     * @return bool
     */
    public function logout() {
        $userId = $this->session->get('user_id');
        
        // Log the logout before destroying session
        if ($userId) {
            AuditLog::log($userId, 'logout', 'auth', 'User logged out');
            ActivityLogger::logout($userId);
        }
        
        // Destroy session completely
        $this->session->destroy();
        
        // Clear any remember me cookies if implemented
        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, '/');
            unset($_COOKIE['remember_me']);
        }
        
        return true;
    }
    
    public function isAuthenticated() {
        return $this->session->has('user_id');
    }
    
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return User::find($this->session->get('user_id'));
    }
    
    public function getCurrentRole() {
        return $this->session->get('user_role');
    }
    
    public function hasRole($role) {
        return $this->getCurrentRole() === $role;
    }
    
    public function hasAnyRole($roles) {
        $roles = (array)$roles;
        $currentRoles = $this->session->get('user_roles', []);
        if (empty($currentRoles)) {
            $currentRoles = [$this->getCurrentRole()];
        }
        return count(array_intersect($currentRoles, $roles)) > 0;
    }
    
    public function checkPermission($permission) {
        if ($this->getCurrentRole() === 'admin') {
            return true;
        }

        $roleName = $this->getCurrentRole();
        $permissions = Permission::getByRole($roleName);
        foreach ($permissions as $item) {
            if ($item['name'] === $permission || $item['name'] === '*') {
                return true;
            }
        }

        return false;
    }

    public function canAccessModule($module) {
        $allowedModules = [
            'admin' => ['dashboard','students','lecturers','courses','grades','attendance','assignments','chat','reports','settings','ai','announcements'],
            'lecturer' => ['dashboard','courses','grades','attendance','assignments','chat','announcements'],
            'student' => ['dashboard','announcements','assignments','grades','attendance','chat']
        ];
        $role = $this->getCurrentRole();
        return isset($allowedModules[$role]) && in_array($module, $allowedModules[$role]);
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
}