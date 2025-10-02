<?php
// FILE: /jpos/api/validation.php
// Input validation middleware for JPOS API endpoints

class JPOS_Validation {
    
    /**
     * Validate and sanitize input data
     */
    public static function validate_input($data, $rules) {
        $validated = [];
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Check if field is required
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[] = "Field '{$field}' is required.";
                continue;
            }
            
            // Skip validation if field is empty and not required
            if (empty($value) && !isset($rule['required'])) {
                $validated[$field] = $rule['default'] ?? null;
                continue;
            }
            
            // Apply validation rules
            if (!empty($value)) {
                $sanitized_value = self::sanitize_value($value, $rule);
                
                // Validate the sanitized value
                $validation_result = self::validate_value($sanitized_value, $rule);
                
                if ($validation_result === true) {
                    $validated[$field] = $sanitized_value;
                } else {
                    $errors[] = "Field '{$field}': " . $validation_result;
                }
            }
        }
        
        if (!empty($errors)) {
            wp_send_json_error(['message' => 'Validation failed.', 'errors' => $errors], 400);
            exit;
        }
        
        return $validated;
    }
    
    /**
     * Sanitize value based on type
     */
    private static function sanitize_value($value, $rule) {
        $type = $rule['type'] ?? 'text';
        
        switch ($type) {
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Validate value based on rules
     */
    private static function validate_value($value, $rule) {
        $type = $rule['type'] ?? 'text';
        
        // Type-specific validation
        switch ($type) {
            case 'email':
                if (!is_email($value)) {
                    return 'Invalid email format.';
                }
                break;
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return 'Invalid URL format.';
                }
                break;
            case 'int':
                if (!is_numeric($value) || intval($value) != $value) {
                    return 'Must be a valid integer.';
                }
                break;
            case 'float':
                if (!is_numeric($value)) {
                    return 'Must be a valid number.';
                }
                break;
        }
        
        // Range validation for numbers
        if (in_array($type, ['int', 'float']) && is_numeric($value)) {
            if (isset($rule['min']) && $value < $rule['min']) {
                return "Must be at least {$rule['min']}.";
            }
            if (isset($rule['max']) && $value > $rule['max']) {
                return "Must be at most {$rule['max']}.";
            }
        }
        
        // Length validation for strings
        if (in_array($type, ['text', 'email', 'url'])) {
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                return "Must be at least {$rule['min_length']} characters long.";
            }
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                return "Must be at most {$rule['max_length']} characters long.";
            }
        }
        
        // Enum validation
        if (isset($rule['enum']) && !in_array($value, $rule['enum'])) {
            return 'Invalid value. Must be one of: ' . implode(', ', $rule['enum']);
        }
        
        return true;
    }
    
    /**
     * Validate JSON input
     */
    public static function validate_json_input($json_string) {
        $data = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid JSON received.'], 400);
            exit;
        }
        
        if (!is_array($data)) {
            wp_send_json_error(['message' => 'JSON must contain an object.'], 400);
            exit;
        }
        
        return $data;
    }
    
    /**
     * Common validation rules for JPOS
     */
    public static function get_common_rules() {
        return [
            'username' => [
                'type' => 'text',
                'required' => true,
                'min_length' => 3,
                'max_length' => 60
            ],
            'password' => [
                'type' => 'text',
                'required' => true,
                'min_length' => 1
            ],
            'email' => [
                'type' => 'email',
                'required' => false,
                'max_length' => 100
            ],
            'phone' => [
                'type' => 'text',
                'required' => false,
                'max_length' => 20
            ],
            'name' => [
                'type' => 'text',
                'required' => false,
                'max_length' => 100
            ],
            'address' => [
                'type' => 'text',
                'required' => false,
                'max_length' => 200
            ],
            'amount' => [
                'type' => 'float',
                'required' => false,
                'min' => 0
            ],
            'quantity' => [
                'type' => 'int',
                'required' => false,
                'min' => 0
            ]
        ];
    }
}


