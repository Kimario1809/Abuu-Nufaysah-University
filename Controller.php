<?php
namespace App\Core;

/**
 * Base Controller Class
 * All controllers extend this class for common functionality
 */
class Controller {
    
    /**
     * @var Database Database instance
     */
    protected $db;
    
    /**
     * @var Auth Authentication instance
     */
    protected $auth;
    
    /**
     * @var Session Session instance
     */
    protected $session;
    
    /**
     * @var array View data
     */
    protected $viewData = [];
    
    /**
     * @var string Layout file
     */
    protected $layout = 'main';
    
    /**
     * Constructor - Initialize core components
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->auth = Auth::getInstance();
    }
    
    /**
     * Render a view with layout
     * 
     * @param string $view View file path (relative to views folder)
     * @param array $data Data to pass to view
     * @param bool $useLayout Whether to use layout or not
     * @return void
     */
    protected function view($view, $data = [], $useLayout = true) {
        $viewInstance = View::getInstance();
        
        // Merge controller view data with passed data
        $mergedData = array_merge($this->viewData, $data);
        
        // Set layout if specified
        if ($useLayout) {
            $viewInstance->setLayout($this->layout);
        }
        
        // Pass data to view
        $viewInstance->with($mergedData);
        
        // Render view
        echo $viewInstance->render($view, [], $useLayout);
    }
    
    /**
     * Render a view without layout (for AJAX or partial views)
     * 
     * @param string $view View file path
     * @param array $data Data to pass to view
     * @return void
     */
    protected function partial($view, $data = []) {
        $viewInstance = View::getInstance();
        $mergedData = array_merge($this->viewData, $data);
        $viewInstance->with($mergedData);
        echo $viewInstance->partial($view, []);
    }
    
    /**
     * Return JSON response
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Redirect to a URL
     * 
     * @param string $url URL to redirect to
     * @return void
     */
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Redirect back to previous page
     * 
     * @return void
     */
    protected function redirectBack() {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }
    
    /**
     * Require authentication
     * 
     * @return void
     */
    protected function requireAuth() {
        if (!$this->auth->isAuthenticated()) {
            $this->session->setFlash('error', 'Please login to access this page');
            $this->redirect('/login');
        }
    }
    
    /**
     * Require specific role(s)
     * 
     * @param string|array $roles Required role(s)
     * @return void
     */
    protected function requireRole($roles) {
        $this->requireAuth();
        
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        if (!$this->auth->hasAnyRole($roles)) {
            $this->session->setFlash('error', 'You do not have permission to access this page');
            $this->redirect('/dashboard');
        }
    }
    
    /**
     * Require specific permission
     * 
     * @param string $permission Required permission
     * @return void
     */
    protected function requirePermission($permission) {
        $this->requireAuth();
        
        if (!$this->auth->checkPermission($permission)) {
            $this->session->setFlash('error', 'You do not have permission to perform this action');
            $this->redirect('/dashboard');
        }
    }
    
    /**
     * Get POST data with validation
     * 
     * @param string $key Field name
     * @param mixed $default Default value if not exists
     * @return mixed
     */
    protected function post($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get GET data with validation
     * 
     * @param string $key Field name
     * @param mixed $default Default value if not exists
     * @return mixed
     */
    protected function get($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Get all request data (POST + GET)
     * 
     * @return array
     */
    protected function request() {
        return array_merge($_GET, $_POST);
    }
    
    /**
     * Validate CSRF token
     * 
     * @return bool
     */
    protected function validateCSRF() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return CSRF::validateToken($token);
    }
    
    /**
     * Get CSRF token field HTML
     * 
     * @return string
     */
    protected function csrfField() {
        return CSRF::tokenField();
    }
    
    /**
     * Set flash message
     * 
     * @param string $type Message type (success, error, warning, info)
     * @param string $message Message content
     * @return void
     */
    protected function setFlash($type, $message) {
        $this->session->setFlash($type, $message);
    }
    
    /**
     * Get flash message
     * 
     * @param string $type Message type
     * @return string|null
     */
    protected function getFlash($type) {
        return $this->session->getFlash($type);
    }
    
    /**
     * Set layout
     * 
     * @param string $layout Layout name
     * @return void
     */
    protected function setLayout($layout) {
        $this->layout = $layout;
    }
    
    /**
     * Add data to view
     * 
     * @param string $key Key name
     * @param mixed $value Value
     * @return void
     */
    protected function setViewData($key, $value) {
        $this->viewData[$key] = $value;
    }
    
    /**
     * Get current user
     * 
     * @return array|null
     */
    protected function currentUser() {
        return $this->auth->getCurrentUser();
    }
    
    /**
     * Get current user role
     * 
     * @return string|null
     */
    protected function currentRole() {
        return $this->auth->getCurrentRole();
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    protected function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Check if request method matches
     * 
     * @param string $method HTTP method
     * @return bool
     */
    protected function isMethod($method) {
        return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
    }
    
    /**
     * Get IP address
     * 
     * @return string
     */
    protected function getIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Check for proxy IP
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        return $ip;
    }
    
    /**
     * Get user agent
     * 
     * @return string
     */
    protected function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    /**
     * Sanitize input data
     * 
     * @param mixed $data Data to sanitize
     * @return mixed
     */
    protected function sanitize($data) {
        $validator = new Validator();
        return $validator->sanitize($data);
    }
    
    /**
     * Validate request data
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array|bool Returns array of errors or true if valid
     */
    protected function validate($data, $rules) {
        $validator = new Validator();
        if ($validator->validate($data, $rules)) {
            return true;
        }
        return $validator->getErrors();
    }
    
    /**
     * Handle file upload
     * 
     * @param string $fieldName Form field name
     * @param string $targetDir Target directory
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Max file size in bytes
     * @return array|bool Uploaded file info or false on error
     */
    protected function uploadFile($fieldName, $targetDir, $allowedTypes = [], $maxSize = 5242880) {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        $file = $_FILES[$fieldName];
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $this->setFlash('error', 'File is too large');
            return false;
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            $this->setFlash('error', 'File type not allowed');
            return false;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        
        // Ensure target directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Move file
        $targetPath = $targetDir . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'name' => $file['name'],
                'filename' => $filename,
                'path' => $targetPath,
                'size' => $file['size'],
                'mime_type' => $mimeType,
                'extension' => $extension
            ];
        }
        
        return false;
    }
    
    /**
     * Download file
     * 
     * @param string $filePath File path
     * @param string $fileName Download file name
     * @return void
     */
    protected function downloadFile($filePath, $fileName = null) {
        if (!file_exists($filePath)) {
            $this->setFlash('error', 'File not found');
            $this->redirectBack();
            return;
        }
        
        if ($fileName === null) {
            $fileName = basename($filePath);
        }
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Log activity
     * 
     * @param string $action Action performed
     * @param string $details Additional details
     * @return void
     */
    protected function logActivity($action, $details = '') {
        $user = $this->currentUser();
        $logData = [
            'user_id' => $user ? $user['id'] : null,
            'action' => $action,
            'details' => $details,
            'ip' => $this->getIP(),
            'user_agent' => $this->getUserAgent(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Log to file
        $logFile = __DIR__ . '/../../logs/activity.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = json_encode($logData) . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}