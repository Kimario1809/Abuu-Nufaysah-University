<?php
namespace App\Core;

class CSRF {
    public static function generateToken() {
        $token = bin2hex(random_bytes(32));
        Session::getInstance()->set('csrf_token', $token);
        return $token;
    }
    
    public static function getToken() {
        return Session::getInstance()->get('csrf_token');
    }
    
    public static function validateToken($token) {
        $sessionToken = self::getToken();
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
    
    public static function tokenField() {
        return '<input type="hidden" name="csrf_token" value="' . self::generateToken() . '">';
    }
}