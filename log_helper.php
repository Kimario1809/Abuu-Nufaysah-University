<?php
/**
 * Log Helper Functions
 * Include this file in your bootstrap or autoload
 */

use App\Core\Logger;

if (!function_exists('logger')) {
    /**
     * Get logger instance
     */
    function logger() {
        return Logger::getInstance();
    }
}

if (!function_exists('log_debug')) {
    /**
     * Log debug message
     */
    function log_debug($message, $context = [], $type = 'app') {
        logger()->debug($message, $context, $type);
    }
}

if (!function_exists('log_info')) {
    /**
     * Log info message
     */
    function log_info($message, $context = [], $type = 'app') {
        logger()->info($message, $context, $type);
    }
}

if (!function_exists('log_warning')) {
    /**
     * Log warning message
     */
    function log_warning($message, $context = [], $type = 'app') {
        logger()->warning($message, $context, $type);
    }
}

if (!function_exists('log_error')) {
    /**
     * Log error message
     */
    function log_error($message, $context = [], $type = 'app') {
        logger()->error($message, $context, $type);
    }
}

if (!function_exists('log_critical')) {
    /**
     * Log critical message
     */
    function log_critical($message, $context = [], $type = 'app') {
        logger()->critical($message, $context, $type);
    }
}

if (!function_exists('log_exception')) {
    /**
     * Log exception
     */
    function log_exception($exception) {
        logger()->logError($exception);
    }
}

if (!function_exists('log_action')) {
    /**
     * Log user action
     */
    function log_action($userId, $action, $details = []) {
        logger()->action($userId, $action, $details);
    }
}

if (!function_exists('log_query')) {
    /**
     * Log database query
     */
    function log_query($sql, $params = [], $time = null) {
        logger()->query($sql, $params, $time);
    }
}

if (!function_exists('log_access')) {
    /**
     * Log access
     */
    function log_access($method, $uri, $status, $duration = null) {
        logger()->access($method, $uri, $status, $duration);
    }
}