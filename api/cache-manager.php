<?php
// FILE: /jpos/api/cache-manager.php
// Simple file-based caching system for JPOS

class JPOS_Cache_Manager {
    
    private static $cache_dir;
    private static $cache_enabled = true;
    private static $default_ttl = 300; // 5 minutes
    private static $max_cache_files = 100; // Maximum number of cache files
    private static $max_cache_size = 10485760; // 10MB in bytes
    
    /**
     * Initialize cache directory
     */
    public static function init() {
        self::$cache_dir = __DIR__ . '/../cache/';
        
        // Create cache directory if it doesn't exist
        if (!file_exists(self::$cache_dir)) {
            wp_mkdir_p(self::$cache_dir);
        }
        
        // Set proper permissions
        if (file_exists(self::$cache_dir)) {
            chmod(self::$cache_dir, 0755);
        }
        
        // Clean expired cache on initialization
        self::clean_expired();
        
        // Check cache limits
        self::enforce_cache_limits();
        
        // Schedule automatic cleanup if not already scheduled
        if (!wp_next_scheduled('jpos_cache_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'jpos_cache_cleanup');
        }
    }
    
    /**
     * Get cached data
     */
    public static function get($key) {
        if (!self::$cache_enabled) {
            return false;
        }
        
        $cache_file = self::$cache_dir . md5($key) . '.cache';
        
        if (!file_exists($cache_file)) {
            return false;
        }
        
        $data = file_get_contents($cache_file);
        $cache_data = json_decode($data, true);
        
        if (!$cache_data || !isset($cache_data['expires']) || !isset($cache_data['data'])) {
            return false;
        }
        
        // Check if cache has expired
        if (time() > $cache_data['expires']) {
            unlink($cache_file);
            return false;
        }
        
        return $cache_data['data'];
    }
    
    /**
     * Set cached data
     */
    public static function set($key, $data, $ttl = null) {
        if (!self::$cache_enabled) {
            return false;
        }
        
        $ttl = $ttl ?: self::$default_ttl;
        $cache_file = self::$cache_dir . md5($key) . '.cache';
        
        $cache_data = [
            'expires' => time() + $ttl,
            'data' => $data,
            'created' => time()
        ];
        
        return file_put_contents($cache_file, json_encode($cache_data), LOCK_EX) !== false;
    }
    
    /**
     * Delete cached data
     */
    public static function delete($key) {
        $cache_file = self::$cache_dir . md5($key) . '.cache';
        
        if (file_exists($cache_file)) {
            return unlink($cache_file);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     */
    public static function clear_all() {
        if (!file_exists(self::$cache_dir)) {
            return true;
        }
        
        $files = glob(self::$cache_dir . '*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Clean expired cache files
     */
    public static function clean_expired() {
        if (!file_exists(self::$cache_dir)) {
            return true;
        }
        
        $files = glob(self::$cache_dir . '*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = file_get_contents($file);
            $cache_data = json_decode($data, true);
            
            if (!$cache_data || !isset($cache_data['expires'])) {
                unlink($file);
                $cleaned++;
                continue;
            }
            
            if (time() > $cache_data['expires']) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Get cache statistics
     */
    public static function get_stats() {
        if (!file_exists(self::$cache_dir)) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'expired_files' => 0
            ];
        }
        
        $files = glob(self::$cache_dir . '*.cache');
        $total_size = 0;
        $expired_files = 0;
        
        foreach ($files as $file) {
            $total_size += filesize($file);
            
            $data = file_get_contents($file);
            $cache_data = json_decode($data, true);
            
            if ($cache_data && isset($cache_data['expires']) && time() > $cache_data['expires']) {
                $expired_files++;
            }
        }
        
        return [
            'total_files' => count($files),
            'total_size' => $total_size,
            'expired_files' => $expired_files
        ];
    }
    
    /**
     * Enable/disable caching
     */
    public static function set_enabled($enabled) {
        self::$cache_enabled = $enabled;
    }
    
    /**
     * Set default TTL
     */
    public static function set_default_ttl($ttl) {
        self::$default_ttl = $ttl;
    }
    
    /**
     * Enforce cache limits (file count and size)
     */
    public static function enforce_cache_limits() {
        if (!file_exists(self::$cache_dir)) {
            return;
        }
        
        $files = glob(self::$cache_dir . '*.cache');
        
        // Check file count
        if (count($files) > self::$max_cache_files) {
            // Sort by modification time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Delete oldest files until we're under the limit
            $to_delete = count($files) - self::$max_cache_files;
            for ($i = 0; $i < $to_delete; $i++) {
                if (isset($files[$i])) {
                    unlink($files[$i]);
                }
            }
        }
        
        // Check total size
        $total_size = 0;
        $files = glob(self::$cache_dir . '*.cache');
        
        foreach ($files as $file) {
            $total_size += filesize($file);
        }
        
        // If over size limit, delete oldest files
        if ($total_size > self::$max_cache_size) {
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            foreach ($files as $file) {
                if ($total_size <= self::$max_cache_size) {
                    break;
                }
                $total_size -= filesize($file);
                unlink($file);
            }
        }
    }
    
    /**
     * Cache key generator for common patterns
     */
    public static function generate_key($prefix, $params = []) {
        return $prefix . '_' . md5(serialize($params));
    }
}

// Initialize cache manager
JPOS_Cache_Manager::init();

// Register WordPress cron cleanup action
add_action('jpos_cache_cleanup', ['JPOS_Cache_Manager', 'clean_expired']);

// Clean up on plugin deactivation
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('jpos_cache_cleanup');
});
