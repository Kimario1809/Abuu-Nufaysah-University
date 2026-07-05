<?php
namespace App\Core;

/**
 * Environment Configuration Loader
 * Loads configuration from .env file
 */
class Env {
    private static $loaded = false;
    
    /**
     * Load .env file
     * 
     * @param string $path Path to .env file
     * @return void
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($path === null) {
            $path = __DIR__ . '/../../.env';
        }
        
        if (!file_exists($path)) {
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // Set environment variable
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get environment variable
     * 
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        return $_ENV[$key] ?? $default;
    }
    
    /**
     * Check if environment variable exists
     * 
     * @param string $key Variable name
     * @return bool
     */
    public static function has($key) {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset($_ENV[$key]);
    }
    
    /**
     * Set environment variable
     * 
     * @param string $key Variable name
     * @param mixed $value Value
     * @return void
     */
    public static function set($key, $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}
