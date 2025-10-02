<?php
// FILE: /jpos/api/error_handler.php
// Unified error handling system for JPOS API endpoints

class JPOS_Error_Handler {
    
    /**
     * Standard error response structure
     */
    public static function send_error($message, $code = 500, $details = null, $error_id = null) {
        $error_data = [
            'code' => $code,
            'timestamp' => current_time('mysql'),
            'error_id' => $error_id ?: self::generate_error_id()
        ];
        
        // Add details if provided (for debugging)
        if ($details && WP_DEBUG) {
            $error_data['details'] = $details;
        }
        
        // Log the error
        self::log_error(['message' => $message, 'code' => $code, 'timestamp' => $error_data['timestamp'], 'error_id' => $error_data['error_id']], $details);
        
        // Send JSON response
        wp_send_json_error($error_data, $code);
        exit;
    }
    
    /**
     * Standard success response structure
     */
    public static function send_success($data = [], $message = 'Success') {
        wp_send_json_success([
            'message' => $message,
            'data' => $data,
            'timestamp' => current_time('mysql')
        ]);
        exit;
    }
    
    /**
     * Handle exceptions with proper error response
     */
    public static function handle_exception($exception, $context = '') {
        $message = 'An unexpected error occurred';
        $details = null;
        
        if (WP_DEBUG) {
            $details = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'context' => $context
            ];
        }
        
        self::send_error($message, 500, $details);
    }
    
    /**
     * Handle database errors
     */
    public static function handle_database_error($wpdb, $query = '') {
        $message = 'Database operation failed';
        $details = null;
        
        if (WP_DEBUG) {
            $details = [
                'last_error' => $wpdb->last_error,
                'last_query' => $wpdb->last_query,
                'query' => $query
            ];
        }
        
        self::send_error($message, 500, $details);
    }
    
    /**
     * Handle validation errors
     */
    public static function handle_validation_error($errors) {
        $message = 'Validation failed';
        $details = [
            'validation_errors' => $errors
        ];
        
        self::send_error($message, 400, $details);
    }
    
    /**
     * Handle authentication errors
     */
    public static function handle_auth_error($message = 'Authentication required') {
        self::send_error($message, 401);
    }
    
    /**
     * Handle authorization errors
     */
    public static function handle_permission_error($message = 'Insufficient permissions') {
        self::send_error($message, 403);
    }
    
    /**
     * Handle not found errors
     */
    public static function handle_not_found_error($resource = 'Resource') {
        self::send_error("{$resource} not found", 404);
    }
    
    /**
     * Handle CSRF errors
     */
    public static function handle_csrf_error() {
        self::send_error('Security token invalid. Please refresh the page and try again.', 403);
    }
    
    /**
     * Log error to WordPress error log
     */
    private static function log_error($response, $details = null) {
        $log_data = [
            'error_id' => $response['error_id'],
            'message' => $response['message'],
            'code' => $response['code'],
            'timestamp' => $response['timestamp'],
            'user_id' => get_current_user_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        if ($details) {
            $log_data['details'] = $details;
        }
        
        error_log('JPOS Error: ' . json_encode($log_data));
    }
    
    /**
     * Generate unique error ID for tracking
     */
    private static function generate_error_id() {
        return 'jpos_' . uniqid() . '_' . substr(md5(microtime()), 0, 8);
    }
    
    /**
     * Check if user is authenticated and has required permissions
     */
    public static function check_auth($capability = 'manage_woocommerce') {
        if (!is_user_logged_in()) {
            self::handle_auth_error();
        }
        
        if (!current_user_can($capability)) {
            self::handle_permission_error();
        }
    }
    
    /**
     * Validate CSRF nonce
     */
    public static function check_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            self::handle_csrf_error();
        }
    }
    
    /**
     * Safe JSON decode with error handling
     */
    public static function safe_json_decode($json_string) {
        $data = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::send_error('Invalid JSON received', 400, [
                'json_error' => json_last_error_msg()
            ]);
        }
        
        return $data;
    }
}

