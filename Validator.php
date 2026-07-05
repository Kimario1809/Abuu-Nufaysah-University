<?php
namespace App\Core;

class Validator {
    private $errors = [];
    
    public function validate($data, $rules) {
        $this->errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $ruleArray = explode('|', $ruleString);
            $value = $data[$field] ?? null;
            
            foreach ($ruleArray as $rule) {
                $this->applyRule($field, $value, $rule, $data);
            }
        }
        
        return empty($this->errors);
    }
    
    private function applyRule($field, $value, $rule, $data) {
        $params = [];
        
        if (strpos($rule, ':') !== false) {
            $parts = explode(':', $rule);
            $rule = $parts[0];
            $params = array_slice($parts, 1);
        }
        
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    $this->errors[$field][] = "The {$field} field is required";
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "The {$field} must be a valid email";
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < $params[0]) {
                    $this->errors[$field][] = "The {$field} must be at least {$params[0]} characters";
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > $params[0]) {
                    $this->errors[$field][] = "The {$field} must not exceed {$params[0]} characters";
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->errors[$field][] = "The {$field} must be numeric";
                }
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if (isset($data[$confirmField]) && $value !== $data[$confirmField]) {
                    $this->errors[$field][] = "The {$field} confirmation does not match";
                }
                break;
                
            case 'unique':
                // This would need database access - placeholder for now
                break;
        }
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    public function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}
