<?php
namespace App\Core;

class ErrorHandler {
    private static $instance = null;
    private $logger;
    private $debug = false;
    
    private function __construct() {
        $this->logger = Logger::getInstance();
        $this->debug = App\Core\Env::get('APP_DEBUG', false);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register error handlers
     */
    public function register() {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorType = $this->getErrorType($errno);
        $context = [
            'errno' => $errno,
            'errfile' => $errfile,
            'errline' => $errline,
            'errtype' => $errorType
        ];
        
        $this->logger->error("PHP Error [$errorType]: $errstr", $context, 'error');
        
        if ($this->debug) {
            $this->displayError($errorType, $errstr, $errfile, $errline);
        }
        
        return true;
    }
    
    /**
     * Handle exceptions
     */
    public function handleException($exception) {
        $this->logger->logError($exception);
        
        if ($this->debug) {
            $this->displayException($exception);
        } else {
            $this->showErrorPage(500);
        }
    }
    
    /**
     * Handle fatal errors
     */
    public function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
            $this->logger->critical(
                "Fatal Error: {$error['message']}",
                [
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'type' => $error['type']
                ],
                'error'
            );
            
            if ($this->debug) {
                $this->displayError('FATAL', $error['message'], $error['file'], $error['line']);
            } else {
                $this->showErrorPage(500);
            }
        }
    }
    
    /**
     * Get error type name
     */
    private function getErrorType($errno) {
        $types = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Standards',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated'
        ];
        return $types[$errno] ?? 'Unknown Error';
    }
    
    /**
     * Display error in debug mode
     */
    private function displayError($type, $message, $file, $line) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px; font-family: monospace;'>";
        echo "<h3 style='color: #721c24;'>⚠️ $type</h3>";
        echo "<p><strong>Message:</strong> $message</p>";
        echo "<p><strong>File:</strong> $file</p>";
        echo "<p><strong>Line:</strong> $line</p>";
        echo "</div>";
    }
    
    /**
     * Display exception in debug mode
     */
    private function displayException($exception) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px; font-family: monospace;'>";
        echo "<h3 style='color: #721c24;'>💥 " . get_class($exception) . "</h3>";
        echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $exception->getFile() . ":" . $exception->getLine() . "</p>";
        echo "<details style='margin-top: 10px;'>";
        echo "<summary style='cursor: pointer; font-weight: bold;'>Stack Trace</summary>";
        echo "<pre style='background: #fff; padding: 10px; margin-top: 5px; overflow: auto; max-height: 400px;'>" . $exception->getTraceAsString() . "</pre>";
        echo "</details>";
        echo "</div>";
    }
    
    /**
     * Show error page
     */
    private function showErrorPage($code = 500) {
        http_response_code($code);
        
        $errorPage = ROOT_PATH . "/views/errors/$code.php";
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo "<!DOCTYPE html>";
            echo "<html><head><title>Error $code</title>";
            echo "<style>body{font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5;}</style>";
            echo "</head><body>";
            echo "<div style='text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
            echo "<h1 style='color: #dc3545;'>Error $code</h1>";
            echo "<p style='color: #666;'>Something went wrong. Please try again later.</p>";
            echo "<a href='/' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go Home</a>";
            echo "</div>";
            echo "</body></html>";
        }
        exit;
    }
}