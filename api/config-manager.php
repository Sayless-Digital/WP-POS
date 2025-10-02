<?php
// FILE: /jpos/api/config-manager.php
// Configuration management for JPOS

class JPOS_Config_Manager {
    
    private static $config = [];
    private static $config_file;
    private static $defaults = [
        // Database settings
        'database' => [
            'query_cache_ttl' => 300, // 5 minutes
            'max_results_per_page' => 100,
            'enable_query_logging' => false
        ],
        
        // Cache settings
        'cache' => [
            'enabled' => true,
            'default_ttl' => 300, // 5 minutes
            'cleanup_interval' => 3600, // 1 hour
            'max_cache_size' => 50 * 1024 * 1024 // 50MB
        ],
        
        // Performance settings
        'performance' => [
            'enable_bundling' => true,
            'enable_minification' => true,
            'enable_compression' => true,
            'lazy_load_images' => true,
            'max_products_per_request' => 50
        ],
        
        // API settings
        'api' => [
            'rate_limit_enabled' => false,
            'rate_limit_requests_per_minute' => 100,
            'enable_cors' => false,
            'cors_origins' => []
        ],
        
        // Security settings
        'security' => [
            'enable_csrf_protection' => true,
            'session_timeout' => 3600, // 1 hour
            'max_login_attempts' => 5,
            'lockout_duration' => 900 // 15 minutes
        ],
        
        // UI settings
        'ui' => [
            'default_theme' => 'dark',
            'items_per_page' => 20,
            'auto_refresh_interval' => 30000, // 30 seconds
            'show_debug_info' => false
        ],
        
        // Receipt settings
        'receipt' => [
            'default_printer' => 'thermal',
            'paper_width' => 80,
            'font_size' => 'normal',
            'show_logo' => true,
            'show_qr_code' => false
        ],
        
        // Payment settings
        'payment' => [
            'default_method' => 'Cash',
            'allow_split_payments' => true,
            'require_cash_drawer' => true,
            'auto_calculate_change' => true
        ],
        
        // Inventory settings
        'inventory' => [
            'low_stock_threshold' => 5,
            'track_stock_levels' => true,
            'allow_negative_stock' => false,
            'auto_update_variations' => true
        ],
        
        // Reporting settings
        'reports' => [
            'default_date_range' => 30,
            'cache_reports' => true,
            'auto_generate_daily' => true,
            'include_online_orders' => true
        ]
    ];
    
    /**
     * Initialize configuration manager
     */
    public static function init() {
        self::$config_file = __DIR__ . '/../config/jpos-config.json';
        
        // Load configuration
        self::load_config();
    }
    
    /**
     * Load configuration from file or use defaults
     */
    private static function load_config() {
        if (file_exists(self::$config_file)) {
            $file_content = file_get_contents(self::$config_file);
            $loaded_config = json_decode($file_content, true);
            
            if ($loaded_config) {
                self::$config = array_merge_recursive(self::$defaults, $loaded_config);
            } else {
                self::$config = self::$defaults;
            }
        } else {
            self::$config = self::$defaults;
            self::save_config();
        }
    }
    
    /**
     * Save configuration to file
     */
    public static function save_config() {
        // Ensure config directory exists
        $config_dir = dirname(self::$config_file);
        if (!file_exists($config_dir)) {
            wp_mkdir_p($config_dir);
        }
        
        return file_put_contents(
            self::$config_file, 
            json_encode(self::$config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        ) !== false;
    }
    
    /**
     * Get configuration value
     */
    public static function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Set configuration value
     */
    public static function set($key, $value) {
        $keys = explode('.', $key);
        $config = &self::$config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
        return true;
    }
    
    /**
     * Get all configuration
     */
    public static function get_all() {
        return self::$config;
    }
    
    /**
     * Reset configuration to defaults
     */
    public static function reset_to_defaults() {
        self::$config = self::$defaults;
        return self::save_config();
    }
    
    /**
     * Get configuration schema for validation
     */
    public static function get_schema() {
        return [
            'database.query_cache_ttl' => ['type' => 'integer', 'min' => 60, 'max' => 3600],
            'database.max_results_per_page' => ['type' => 'integer', 'min' => 10, 'max' => 500],
            'database.enable_query_logging' => ['type' => 'boolean'],
            'cache.enabled' => ['type' => 'boolean'],
            'cache.default_ttl' => ['type' => 'integer', 'min' => 60, 'max' => 3600],
            'performance.enable_bundling' => ['type' => 'boolean'],
            'performance.enable_minification' => ['type' => 'boolean'],
            'security.enable_csrf_protection' => ['type' => 'boolean'],
            'security.session_timeout' => ['type' => 'integer', 'min' => 300, 'max' => 86400],
            'ui.items_per_page' => ['type' => 'integer', 'min' => 10, 'max' => 100],
            'receipt.paper_width' => ['type' => 'integer', 'min' => 58, 'max' => 112],
            'payment.default_method' => ['type' => 'string', 'options' => ['Cash', 'Card', 'Check', 'Other']],
            'inventory.low_stock_threshold' => ['type' => 'integer', 'min' => 1, 'max' => 100],
            'reports.default_date_range' => ['type' => 'integer', 'min' => 7, 'max' => 365]
        ];
    }
    
    /**
     * Validate configuration value
     */
    public static function validate($key, $value) {
        $schema = self::get_schema();
        
        if (!isset($schema[$key])) {
            return true; // No validation defined
        }
        
        $rules = $schema[$key];
        
        // Type validation
        switch ($rules['type']) {
            case 'integer':
                if (!is_int($value)) {
                    return false;
                }
                break;
            case 'boolean':
                if (!is_bool($value)) {
                    return false;
                }
                break;
            case 'string':
                if (!is_string($value)) {
                    return false;
                }
                break;
        }
        
        // Range validation
        if (isset($rules['min']) && $value < $rules['min']) {
            return false;
        }
        if (isset($rules['max']) && $value > $rules['max']) {
            return false;
        }
        
        // Options validation
        if (isset($rules['options']) && !in_array($value, $rules['options'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Update configuration with validation
     */
    public static function update($updates) {
        $updated = [];
        $errors = [];
        
        foreach ($updates as $key => $value) {
            if (self::validate($key, $value)) {
                self::set($key, $value);
                $updated[] = $key;
            } else {
                $errors[] = "Invalid value for {$key}: {$value}";
            }
        }
        
        if (empty($errors)) {
            self::save_config();
        }
        
        return [
            'success' => empty($errors),
            'updated' => $updated,
            'errors' => $errors
        ];
    }
    
    /**
     * Get configuration for API response
     */
    public static function get_public_config() {
        // Return only non-sensitive configuration
        return [
            'ui' => self::get('ui'),
            'receipt' => self::get('receipt'),
            'payment' => self::get('payment'),
            'inventory' => self::get('inventory'),
            'reports' => self::get('reports')
        ];
    }
    
    /**
     * Export configuration
     */
    public static function export() {
        return json_encode(self::$config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Import configuration
     */
    public static function import($json_config) {
        $imported_config = json_decode($json_config, true);
        
        if ($imported_config) {
            self::$config = array_merge_recursive(self::$defaults, $imported_config);
            return self::save_config();
        }
        
        return false;
    }
}

// Initialize configuration manager
JPOS_Config_Manager::init();
