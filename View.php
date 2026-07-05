<?php
namespace App\Core;

/**
 * View Class
 * Handles view rendering with layout support
 * Fixes the issue where views/layout mixing causes undefined variables
 */
class View {
    private static $instance = null;
    private $viewData = [];
    private $layout = 'main';
    private $content = '';
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Set layout file
     * 
     * @param string $layout Layout name
     * @return self
     */
    public function setLayout($layout) {
        $this->layout = $layout;
        return $this;
    }
    
    /**
     * Add data to view
     * 
     * @param string|array $key Key name or array of data
     * @param mixed $value Value if key is string
     * @return self
     */
    public function with($key, $value = null) {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }
        return $this;
    }
    
    /**
     * Render a view with layout
     * 
     * @param string $view View file path (relative to views folder)
     * @param array $data Data to pass to view
     * @param bool $useLayout Whether to use layout or not
     * @return string
     */
    public function render($view, $data = [], $useLayout = true) {
        // Merge data with existing view data
        $viewData = array_merge($this->viewData, $data);
        
        // Add authentication to view data
        $auth = Auth::getInstance();
        $session = Session::getInstance();
        
        // Extract data to variables
        extract($viewData);
        
        // Start output buffering
        ob_start();
        
        // Include view file
        $viewFile = __DIR__ . "/../../views/{$view}.php";
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new \Exception("View file not found: {$viewFile}");
        }
        
        $this->content = ob_get_clean();
        
        if ($useLayout) {
            // Render layout with content
            return $this->renderLayout();
        }
        
        return $this->content;
    }
    
    /**
     * Render layout with content
     * 
     * @return string
     */
    private function renderLayout() {
        $content = $this->content;
        $auth = Auth::getInstance();
        $session = Session::getInstance();
        
        // Extract view data for layout access
        extract($this->viewData);
        
        ob_start();
        
        $layoutFile = __DIR__ . "/../../views/layouts/{$this->layout}.php";
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            // If layout not found, just output content
            echo $content;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render a view without layout (for AJAX or partial views)
     * 
     * @param string $view View file path
     * @param array $data Data to pass to view
     * @return string
     */
    public function partial($view, $data = []) {
        return $this->render($view, $data, false);
    }
    
    /**
     * Escape HTML to prevent XSS
     * 
     * @param string $value Value to escape
     * @return string
     */
    public static function escape($value) {
        if (is_array($value)) {
            return array_map([self::class, 'escape'], $value);
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Output escaped value
     * 
     * @param string $value Value to escape and output
     * @return void
     */
    public static function e($value) {
        echo self::escape($value);
    }
    
    /**
     * Clear view data
     * 
     * @return self
     */
    public function clear() {
        $this->viewData = [];
        $this->content = '';
        $this->layout = 'main';
        return $this;
    }
    
    /**
     * Get current content
     * 
     * @return string
     */
    public function getContent() {
        return $this->content;
    }
}
