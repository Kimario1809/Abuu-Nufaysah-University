<?php
namespace App\Helpers;

class LogRotator {
    private $logDir;
    private $maxSize;
    private $maxFiles;
    
    public function __construct($maxSize = 10485760, $maxFiles = 10) {
        $this->logDir = __DIR__ . '/../../logs';
        $this->maxSize = $maxSize; // 10MB default
        $this->maxFiles = $maxFiles;
    }
    
    public function rotate($filename) {
        $logFile = $this->logDir . '/' . $filename;
        
        if (!file_exists($logFile)) {
            return;
        }
        
        if (filesize($logFile) < $this->maxSize) {
            return;
        }
        
        // Rotate files
        for ($i = $this->maxFiles - 1; $i > 0; $i--) {
            $oldFile = $this->logDir . '/' . $filename . '.' . $i . '.gz';
            $newFile = $this->logDir . '/' . $filename . '.' . ($i + 1) . '.gz';
            
            if (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }
        
        // Compress current file
        $content = file_get_contents($logFile);
        $compressed = gzencode($content, 9);
        file_put_contents($this->logDir . '/' . $filename . '.1.gz', $compressed);
        
        // Truncate current file
        file_put_contents($logFile, '');
    }
}