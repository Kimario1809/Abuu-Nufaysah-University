<?php
namespace App\Core;

class Logger {
    private static $instance = null;
    private $logDir;
    private $level;
    private $maxFileSize = 5242880; // 5MB
    private $maxFiles = 10;
    
    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_CRITICAL = 4;
    
    private function __construct() {
        $this->logDir = ROOT_PATH . '/logs';
        $this->ensureLogDirectory();
        $this->level = defined('LOG_LEVEL') ? LOG_LEVEL : self::LEVEL_INFO;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory() {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Create subdirectories
        $subdirs = ['archive', 'errors', 'access'];
        foreach ($subdirs as $dir) {
            $path = $this->logDir . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
    
    /**
     * Log a message
     */
    public function log($level, $message, $context = [], $type = 'app') {
        if ($level < $this->level) {
            return;
        }
        
        $levelName = $this->getLevelName($level);
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $sessionId = session_id() ?: 'no-session';
        
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logMessage = sprintf(
            "[%s] [%s] [%s] [%s] %s%s\n",
            $timestamp,
            $levelName,
            $ip,
            $sessionId,
            $message,
            $contextStr
        );
        
        $logFile = $this->getLogFile($type);
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Rotate if file is too large
        $this->rotateLogFile($logFile, $type);
    }
    
    /**
     * Get log file path
     */
    private function getLogFile($type = 'app') {
        $date = date('Y-m-d');
        $filename = $type . '-' . $date . '.log';
        return $this->logDir . '/' . $filename;
    }
    
    /**
     * Rotate log file if too large
     */
    private function rotateLogFile($file, $type) {
        if (!file_exists($file)) return;
        
        if (filesize($file) >= $this->maxFileSize) {
            $archiveDir = $this->logDir . '/archive';
            $date = date('Y-m-d_H-i-s');
            $archiveFile = $archiveDir . '/' . $type . '-' . $date . '.log.gz';
            
            // Compress the file
            $content = file_get_contents($file);
            file_put_contents($archiveFile, gzencode($content, 9));
            
            // Clear the original file
            file_put_contents($file, '');
            
            // Delete old archives if too many
            $this->cleanOldArchives($type);
        }
    }
    
    /**
     * Clean old archived logs
     */
    private function cleanOldArchives($type) {
        $archiveDir = $this->logDir . '/archive';
        $files = glob($archiveDir . '/' . $type . '-*.log.gz');
        
        if (count($files) > $this->maxFiles) {
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $toDelete = array_slice($files, 0, count($files) - $this->maxFiles);
            foreach ($toDelete as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get level name
     */
    private function getLevelName($level) {
        $names = [
            self::LEVEL_DEBUG => 'DEBUG',
            self::LEVEL_INFO => 'INFO',
            self::LEVEL_WARNING => 'WARNING',
            self::LEVEL_ERROR => 'ERROR',
            self::LEVEL_CRITICAL => 'CRITICAL'
        ];
        return $names[$level] ?? 'UNKNOWN';
    }
    
    /**
     * Convenience methods
     */
    public function debug($message, $context = [], $type = 'app') {
        $this->log(self::LEVEL_DEBUG, $message, $context, $type);
    }
    
    public function info($message, $context = [], $type = 'app') {
        $this->log(self::LEVEL_INFO, $message, $context, $type);
    }
    
    public function warning($message, $context = [], $type = 'app') {
        $this->log(self::LEVEL_WARNING, $message, $context, $type);
    }
    
    public function error($message, $context = [], $type = 'app') {
        $this->log(self::LEVEL_ERROR, $message, $context, $type);
    }
    
    public function critical($message, $context = [], $type = 'app') {
        $this->log(self::LEVEL_CRITICAL, $message, $context, $type);
    }
    
    /**
     * Log database query
     */
    public function query($sql, $params = [], $time = null) {
        $context = [
            'sql' => $sql,
            'params' => $params,
            'time' => $time
        ];
        $this->debug('Database Query', $context, 'db');
    }
    
    /**
     * Log user action
     */
    public function action($userId, $action, $details = []) {
        $context = [
            'user_id' => $userId,
            'action' => $action,
            'details' => $details
        ];
        $this->info('User Action', $context, 'user');
    }
    
    /**
     * Log error with stack trace
     */
    public function logError($exception) {
        $context = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        $this->error('Exception', $context, 'error');
    }
    
    /**
     * Log access
     */
    public function access($method, $uri, $status, $duration = null) {
        $context = [
            'method' => $method,
            'uri' => $uri,
            'status' => $status,
            'duration' => $duration
        ];
        $this->info('Access', $context, 'access');
    }
    
    /**
     * Get log files list
     */
    public function getLogFiles() {
        $files = [];
        $pattern = $this->logDir . '/*.log';
        foreach (glob($pattern) as $file) {
            $files[] = [
                'name' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file),
                'size_formatted' => $this->formatFileSize(filesize($file))
            ];
        }
        return $files;
    }
    
    /**
     * Read log file
     */
    public function readLog($filename, $lines = 100) {
        $file = $this->logDir . '/' . $filename;
        if (!file_exists($file)) {
            return ['error' => 'File not found'];
        }
        
        $content = file($file);
        $total = count($content);
        $start = max(0, $total - $lines);
        return array_slice($content, $start);
    }
    
    /**
     * Clear log file
     */
    public function clearLog($filename) {
        $file = $this->logDir . '/' . $filename;
        if (file_exists($file)) {
            file_put_contents($file, '');
            return true;
        }
        return false;
    }
    
    /**
     * Delete log file
     */
    public function deleteLog($filename) {
        $file = $this->logDir . '/' . $filename;
        if (file_exists($file)) {
            unlink($file);
            return true;
        }
        return false;
    }
    
    /**
     * Format file size
     */
    private function formatFileSize($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get log stats
     */
    public function getStats() {
        $files = glob($this->logDir . '/*.log');
        $totalSize = 0;
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'total_files' => count($files),
            'total_size' => $this->formatFileSize($totalSize),
            'total_size_bytes' => $totalSize,
            'archive_count' => count(glob($this->logDir . '/archive/*.log.gz'))
        ];
    }
}