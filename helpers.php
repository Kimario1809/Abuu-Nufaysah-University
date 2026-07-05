<?php

/**
 * Debug function to print variable with formatting
 */
function dd($var) {
    echo '<pre style="background: #f4f4f4; padding: 20px; border: 1px solid #ddd; margin: 20px;">';
    var_dump($var);
    echo '</pre>';
    die();
}

/**
 * Dump variable without dying
 */
function dump($var) {
    echo '<pre style="background: #f4f4f4; padding: 20px; border: 1px solid #ddd; margin: 20px;">';
    var_dump($var);
    echo '</pre>';
}

/**
 * Generate URL
 */
function url($path = '') {
    $base = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header('Location: ' . url($url));
    exit;
}

/**
 * Get current date/time in specified format
 */
function now($format = 'Y-m-d H:i:s') {
    return date($format);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Calculate percentage
 */
function percentage($part, $total) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, 2);
}

/**
 * Generate random string
 */
function randomString($length = 16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }
    return $ipaddress;
}

/**
 * Log activity
 */
function logActivity($message, $level = 'info') {
    $logFile = __DIR__ . '/../../logs/' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Send email
 */
function sendEmail($to, $subject, $message, $from = null) {
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    
    if ($from) {
        $headers[] = "From: $from";
    } else {
        $headers[] = "From: " . ($_ENV['MAIL_FROM_NAME'] ?? 'Abuu University') . 
                    " <" . ($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@abuu.edu') . ">";
    }
    
    $headers = implode("\r\n", $headers);
    return mail($to, $subject, $message, $headers);
}

/**
 * Get user agent
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * Check if string starts with
 */
function startsWith($haystack, $needle) {
    return strpos($haystack, $needle) === 0;
}

/**
 * Check if string ends with
 */
function endsWith($haystack, $needle) {
    return substr($haystack, -strlen($needle)) === $needle;
}