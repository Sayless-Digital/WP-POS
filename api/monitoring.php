<?php
// FILE: /jpos/api/monitoring.php
// Monitoring and logging system for JPOS

class JPOS_Monitoring {
    
    private static $log_file;
    private static $error_file;
    private static $performance_file;
    private static $monitoring_enabled = true;
    
    /**
     * Initialize monitoring system
     */
    public static function init() {
        $log_dir = __DIR__ . '/../logs';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        self::$log_file = $log_dir . '/jpos-' . date('Y-m-d') . '.log';
        self::$error_file = $log_dir . '/jpos-errors-' . date('Y-m-d') . '.log';
        self::$performance_file = $log_dir . '/jpos-performance-' . date('Y-m-d') . '.log';
    }
    
    /**
     * Log a message
     */
    public static function log($level, $message, $context = []) {
        if (!self::$monitoring_enabled) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $context_str = !empty($context) ? ' ' . json_encode($context) : '';
        $log_entry = "[{$timestamp}] [{$level}] {$message}{$context_str}\n";
        
        file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Also log errors to error file
        if (in_array($level, ['ERROR', 'CRITICAL'])) {
            file_put_contents(self::$error_file, $log_entry, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Log info message
     */
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * Log error message
     */
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    /**
     * Log critical message
     */
    public static function critical($message, $context = []) {
        self::log('CRITICAL', $message, $context);
    }
    
    /**
     * Log API request
     */
    public static function log_api_request($endpoint, $method, $response_time, $status_code, $user_id = null) {
        $context = [
            'endpoint' => $endpoint,
            'method' => $method,
            'response_time' => $response_time,
            'status_code' => $status_code,
            'user_id' => $user_id
        ];
        
        self::log('INFO', "API Request: {$method} {$endpoint}", $context);
        
        // Log performance data
        self::log_performance('api_request', [
            'endpoint' => $endpoint,
            'method' => $method,
            'response_time' => $response_time,
            'status_code' => $status_code
        ]);
    }
    
    /**
     * Log database query
     */
    public static function log_database_query($query, $execution_time, $rows_affected = null) {
        $context = [
            'query' => substr($query, 0, 200) . (strlen($query) > 200 ? '...' : ''),
            'execution_time' => $execution_time,
            'rows_affected' => $rows_affected
        ];
        
        self::log('INFO', 'Database Query', $context);
        
        // Log performance data for slow queries
        if ($execution_time > 1.0) {
            self::log_performance('slow_query', [
                'query' => substr($query, 0, 200),
                'execution_time' => $execution_time,
                'rows_affected' => $rows_affected
            ]);
        }
    }
    
    /**
     * Log performance metrics
     */
    public static function log_performance($metric_name, $data) {
        if (!self::$monitoring_enabled) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [PERFORMANCE] {$metric_name} " . json_encode($data) . "\n";
        
        file_put_contents(self::$performance_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get system metrics
     */
    public static function get_system_metrics() {
        $metrics = [
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'cpu_usage' => self::get_cpu_usage(),
            'disk_usage' => self::get_disk_usage(),
            'database_connections' => self::get_database_connections()
        ];
        
        return $metrics;
    }
    
    /**
     * Get CPU usage (simplified)
     */
    private static function get_cpu_usage() {
        $load = sys_getloadavg();
        return $load[0] ?? 0;
    }
    
    /**
     * Get disk usage
     */
    private static function get_disk_usage() {
        $path = __DIR__ . '/..';
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        
        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percentage' => $total > 0 ? round(($used / $total) * 100, 2) : 0
        ];
    }
    
    /**
     * Get database connections count
     */
    private static function get_database_connections() {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        // This is a simplified version - actual implementation would depend on database type
        return 1;
    }
    
    /**
     * Get log statistics
     */
    public static function get_log_stats($days = 7) {
        $stats = [
            'total_logs' => 0,
            'errors' => 0,
            'warnings' => 0,
            'info' => 0,
            'critical' => 0,
            'days' => $days
        ];
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $log_file = __DIR__ . '/../logs/jpos-' . $date . '.log';
            
            if (file_exists($log_file)) {
                $content = file_get_contents($log_file);
                $lines = explode("\n", $content);
                
                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;
                    
                    $stats['total_logs']++;
                    
                    if (strpos($line, '[ERROR]') !== false) {
                        $stats['errors']++;
                    } elseif (strpos($line, '[WARNING]') !== false) {
                        $stats['warnings']++;
                    } elseif (strpos($line, '[INFO]') !== false) {
                        $stats['info']++;
                    } elseif (strpos($line, '[CRITICAL]') !== false) {
                        $stats['critical']++;
                    }
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Clean old log files
     */
    public static function clean_old_logs($days_to_keep = 30) {
        $log_dir = __DIR__ . '/../logs';
        $files = glob($log_dir . '/jpos-*.log');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $file_time = filemtime($file);
            $days_old = (time() - $file_time) / (24 * 60 * 60);
            
            if ($days_old > $days_to_keep) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Enable/disable monitoring
     */
    public static function set_monitoring_enabled($enabled) {
        self::$monitoring_enabled = $enabled;
    }
    
    /**
     * Generate monitoring report
     */
    public static function generate_report() {
        $metrics = self::get_system_metrics();
        $log_stats = self::get_log_stats();
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'system_metrics' => $metrics,
            'log_statistics' => $log_stats,
            'monitoring_status' => self::$monitoring_enabled ? 'enabled' : 'disabled'
        ];
        
        return $report;
    }
}

// Initialize monitoring system
JPOS_Monitoring::init();
