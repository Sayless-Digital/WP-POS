<?php
// FILE: /jpos/api/performance-monitor.php
// Performance monitoring for JPOS optimization

class JPOS_Performance_Monitor {
    
    private static $start_time;
    private static $memory_start;
    private static $query_count = 0;
    private static $cache_hits = 0;
    private static $cache_misses = 0;
    
    /**
     * Start performance monitoring
     */
    public static function start_monitoring() {
        self::$start_time = microtime(true);
        self::$memory_start = memory_get_usage();
        self::$query_count = 0;
        self::$cache_hits = 0;
        self::$cache_misses = 0;
    }
    
    /**
     * End performance monitoring and return stats
     */
    public static function end_monitoring() {
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        return [
            'execution_time' => round(($end_time - self::$start_time) * 1000, 2), // milliseconds
            'memory_usage' => round(($end_memory - self::$memory_start) / 1024 / 1024, 2), // MB
            'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2), // MB
            'query_count' => self::$query_count,
            'cache_hits' => self::$cache_hits,
            'cache_misses' => self::$cache_misses,
            'cache_hit_rate' => self::$cache_hits + self::$cache_misses > 0 
                ? round((self::$cache_hits / (self::$cache_hits + self::$cache_misses)) * 100, 1) 
                : 0
        ];
    }
    
    /**
     * Increment query count
     */
    public static function increment_query_count() {
        self::$query_count++;
    }
    
    /**
     * Record cache hit
     */
    public static function record_cache_hit() {
        self::$cache_hits++;
    }
    
    /**
     * Record cache miss
     */
    public static function record_cache_miss() {
        self::$cache_misses++;
    }
    
    /**
     * Log performance metrics
     */
    public static function log_performance($operation, $stats) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operation' => $operation,
            'stats' => $stats
        ];
        
        $log_file = __DIR__ . '/../logs/performance-' . date('Y-m-d') . '.log';
        $log_dir = dirname($log_file);
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get performance summary for today
     */
    public static function get_performance_summary() {
        $log_file = __DIR__ . '/../logs/performance-' . date('Y-m-d') . '.log';
        
        if (!file_exists($log_file)) {
            return null;
        }
        
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats = [
            'total_operations' => count($lines),
            'avg_execution_time' => 0,
            'avg_memory_usage' => 0,
            'total_queries' => 0,
            'avg_cache_hit_rate' => 0,
            'operations' => []
        ];
        
        $total_time = 0;
        $total_memory = 0;
        $total_cache_hits = 0;
        $total_cache_attempts = 0;
        
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if ($entry && isset($entry['stats'])) {
                $s = $entry['stats'];
                $total_time += $s['execution_time'];
                $total_memory += $s['memory_usage'];
                $total_queries += $s['query_count'];
                $total_cache_hits += $s['cache_hits'];
                $total_cache_attempts += $s['cache_hits'] + $s['cache_misses'];
                
                $stats['operations'][] = [
                    'operation' => $entry['operation'],
                    'time' => $entry['timestamp'],
                    'execution_time' => $s['execution_time'],
                    'memory_usage' => $s['memory_usage']
                ];
            }
        }
        
        if ($stats['total_operations'] > 0) {
            $stats['avg_execution_time'] = round($total_time / $stats['total_operations'], 2);
            $stats['avg_memory_usage'] = round($total_memory / $stats['total_operations'], 2);
            $stats['total_queries'] = $total_queries;
            $stats['avg_cache_hit_rate'] = $total_cache_attempts > 0 
                ? round(($total_cache_hits / $total_cache_attempts) * 100, 1) 
                : 0;
        }
        
        return $stats;
    }
}

