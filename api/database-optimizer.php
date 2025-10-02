<?php
// FILE: /jpos/api/database-optimizer.php
// Database optimization utilities for JPOS

require_once __DIR__ . '/cache-manager.php';

class JPOS_Database_Optimizer {
    
    private static $cache_enabled = true;
    private static $cache_duration = 300; // 5 minutes
    
    /**
     * Get cached query result or execute and cache
     */
    public static function get_cached_query($query_key, $callback, $cache_duration = null) {
        if (!self::$cache_enabled) {
            return call_user_func($callback);
        }
        
        $cache_duration = $cache_duration ?: self::$cache_duration;
        
        // Try to get from cache
        $cached_result = JPOS_Cache_Manager::get($query_key);
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        // Execute query and cache result
        $result = call_user_func($callback);
        JPOS_Cache_Manager::set($query_key, $result, $cache_duration);
        
        return $result;
    }
    
    /**
     * Clear query cache
     */
    public static function clear_cache($query_key = null) {
        if ($query_key) {
            JPOS_Cache_Manager::delete($query_key);
        } else {
            JPOS_Cache_Manager::clear_all();
        }
    }
    
    /**
     * Optimized orders query with better performance
     */
    public static function get_orders_optimized($filters = []) {
        global $wpdb;
        
        $cache_key = 'orders_' . md5(serialize($filters));
        
        return self::get_cached_query($cache_key, function() use ($wpdb, $filters) {
            $limit = $filters['limit'] ?? 100;
            $offset = $filters['offset'] ?? 0;
            
            // Use a more efficient query structure
            $sql = "
                SELECT 
                    p.ID,
                    p.post_date,
                    p.post_status,
                    pm_total.meta_value as total,
                    pm_payment.meta_value as payment_method,
                    pm_jpos.meta_value as created_via_jpos
                FROM {$wpdb->prefix}posts p
                LEFT JOIN {$wpdb->prefix}postmeta pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
                LEFT JOIN {$wpdb->prefix}postmeta pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = '_payment_method'
                LEFT JOIN {$wpdb->prefix}postmeta pm_jpos ON p.ID = pm_jpos.post_id AND pm_jpos.meta_key = '_created_via_jpos'
                WHERE p.post_type = 'shop_order'
            ";
            
            $sql_params = [];
            
            // Add filters
            if (isset($filters['source']) && $filters['source'] !== 'all') {
                if ($filters['source'] === 'pos') {
                    $sql .= " AND pm_jpos.meta_value = %s";
                    $sql_params[] = '1';
                } elseif ($filters['source'] === 'online') {
                    $sql .= " AND (pm_jpos.meta_value IS NULL OR pm_jpos.meta_value != %s)";
                    $sql_params[] = '1';
                }
            }
            
            if (isset($filters['status']) && $filters['status'] !== 'all') {
                $sql .= " AND p.post_status = %s";
                $sql_params[] = $filters['status'];
            }
            
            if (isset($filters['date_from'])) {
                $sql .= " AND p.post_date >= %s";
                $sql_params[] = $filters['date_from'];
            }
            
            if (isset($filters['date_to'])) {
                $sql .= " AND p.post_date <= %s";
                $sql_params[] = $filters['date_to'];
            }
            
            $sql .= " ORDER BY p.post_date DESC LIMIT %d OFFSET %d";
            $sql_params[] = $limit;
            $sql_params[] = $offset;
            
            if (!empty($sql_params)) {
                $sql = $wpdb->prepare($sql, $sql_params);
            }
            
            return $wpdb->get_results($sql, ARRAY_A);
        });
    }
    
    /**
     * Optimized products query with bulk loading
     */
    public static function get_products_optimized($filters = []) {
        global $wpdb;
        
        $cache_key = 'products_' . md5(serialize($filters));
        
        return self::get_cached_query($cache_key, function() use ($wpdb, $filters) {
            // First, get all product IDs with filters
            $sql = "
                SELECT DISTINCT p.ID
                FROM {$wpdb->prefix}posts p
                LEFT JOIN {$wpdb->prefix}postmeta pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock_status'
                WHERE p.post_type = 'product'
                AND p.post_status = 'publish'
            ";
            
            $sql_params = [];
            
            if (isset($filters['stock']) && $filters['stock'] !== 'all') {
                $sql .= " AND pm_stock.meta_value = %s";
                $sql_params[] = $filters['stock'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $sql .= " AND (p.post_title LIKE %s OR p.post_content LIKE %s)";
                $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
                $sql_params[] = $search_term;
                $sql_params[] = $search_term;
            }
            
            if (!empty($sql_params)) {
                $sql = $wpdb->prepare($sql, $sql_params);
            }
            
            $product_ids = $wpdb->get_col($sql);
            
            if (empty($product_ids)) {
                return [];
            }
            
            $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
            
            // Bulk load all product data in one query
            $sql = "
                SELECT 
                    p.ID,
                    p.post_title as name,
                    p.post_content as description,
                    p.post_excerpt as short_description,
                    pm_price.meta_value as price,
                    pm_sale_price.meta_value as sale_price,
                    pm_sku.meta_value as sku,
                    pm_stock.meta_value as stock_status,
                    pm_stock_qty.meta_value as stock_quantity,
                    pm_weight.meta_value as weight,
                    pm_type.meta_value as type,
                    pm_image.meta_value as image_id
                FROM {$wpdb->prefix}posts p
                LEFT JOIN {$wpdb->prefix}postmeta pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
                LEFT JOIN {$wpdb->prefix}postmeta pm_sale_price ON p.ID = pm_sale_price.post_id AND pm_sale_price.meta_key = '_sale_price'
                LEFT JOIN {$wpdb->prefix}postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
                LEFT JOIN {$wpdb->prefix}postmeta pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock_status'
                LEFT JOIN {$wpdb->prefix}postmeta pm_stock_qty ON p.ID = pm_stock_qty.post_id AND pm_stock_qty.meta_key = '_stock'
                LEFT JOIN {$wpdb->prefix}postmeta pm_weight ON p.ID = pm_weight.post_id AND pm_weight.meta_key = '_weight'
                LEFT JOIN {$wpdb->prefix}postmeta pm_type ON p.ID = pm_type.post_id AND pm_type.meta_key = '_product_type'
                LEFT JOIN {$wpdb->prefix}postmeta pm_image ON p.ID = pm_image.post_id AND pm_image.meta_key = '_thumbnail_id'
                WHERE p.ID IN ($placeholders)
                ORDER BY p.post_title ASC
            ";
            
            $sql = $wpdb->prepare($sql, $product_ids);
            $products = $wpdb->get_results($sql, ARRAY_A);
            
            // Process and format the data
            foreach ($products as &$product) {
                $product['price'] = $product['sale_price'] ?: $product['price'];
                $product['stock_quantity'] = intval($product['stock_quantity'] ?: 0);
                $product['weight'] = floatval($product['weight'] ?: 0);
                
                // Get image URL if image_id exists
                if ($product['image_id']) {
                    $image_url = wp_get_attachment_image_url($product['image_id'], 'medium');
                    $product['image'] = $image_url ?: '';
                } else {
                    $product['image'] = '';
                }
                
                // Get categories and tags (bulk load)
                $product['categories'] = wp_get_post_terms($product['ID'], 'product_cat', ['fields' => 'all']);
                $product['tags'] = wp_get_post_terms($product['ID'], 'product_tag', ['fields' => 'all']);
            }
            
            return $products;
        });
    }
    
    /**
     * Optimized reports query with single query approach
     */
    public static function get_reports_optimized($filters = []) {
        global $wpdb;
        
        $cache_key = 'reports_' . md5(serialize($filters));
        
        return self::get_cached_query($cache_key, function() use ($wpdb, $filters) {
            $thirty_days_ago = date('Y-m-d 00:00:00', strtotime('-29 days'));
            
            // Single optimized query for all report data
            $sql = "
                SELECT
                    DATE(p.post_date) as order_date,
                    COUNT(p.ID) as daily_orders,
                    SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as daily_revenue,
                    SUM(CASE WHEN pm_payment.meta_value IN ('Cash', 'cash', 'cod') THEN CAST(pm_total.meta_value AS DECIMAL(10,2)) ELSE 0 END) as daily_cash_revenue,
                    COUNT(CASE WHEN pm_payment.meta_value IN ('Cash', 'cash', 'cod') THEN 1 END) as daily_cash_orders,
                    SUM(CASE WHEN pm_payment.meta_value IN ('Card', 'card', 'credit_card', 'debit', 'linx', 'Linx') THEN CAST(pm_total.meta_value AS DECIMAL(10,2)) ELSE 0 END) as daily_card_revenue,
                    COUNT(CASE WHEN pm_payment.meta_value IN ('Card', 'card', 'credit_card', 'debit', 'linx', 'Linx') THEN 1 END) as daily_card_orders,
                    SUM(CASE WHEN pm_jpos.meta_value = '1' THEN CAST(pm_total.meta_value AS DECIMAL(10,2)) ELSE 0 END) as daily_pos_revenue,
                    COUNT(CASE WHEN pm_jpos.meta_value = '1' THEN 1 END) as daily_pos_orders,
                    SUM(CASE WHEN pm_jpos.meta_value != '1' OR pm_jpos.meta_value IS NULL THEN CAST(pm_total.meta_value AS DECIMAL(10,2)) ELSE 0 END) as daily_online_revenue,
                    COUNT(CASE WHEN pm_jpos.meta_value != '1' OR pm_jpos.meta_value IS NULL THEN 1 END) as daily_online_orders
                FROM {$wpdb->prefix}posts p
                LEFT JOIN {$wpdb->prefix}postmeta pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
                LEFT JOIN {$wpdb->prefix}postmeta pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = '_payment_method'
                LEFT JOIN {$wpdb->prefix}postmeta pm_jpos ON p.ID = pm_jpos.post_id AND pm_jpos.meta_key = '_created_via_jpos'
                WHERE p.post_type = 'shop_order'
                AND p.post_status IN ('wc-completed', 'wc-processing')
                AND p.post_date >= %s
                GROUP BY DATE(p.post_date)
                ORDER BY order_date ASC
            ";
            
            $sql = $wpdb->prepare($sql, $thirty_days_ago);
            $daily_results = $wpdb->get_results($sql, ARRAY_A);
            
            // Calculate totals
            $totals = [
                'total_revenue' => 0,
                'total_orders' => 0,
                'cash_revenue' => 0,
                'cash_orders' => 0,
                'card_revenue' => 0,
                'card_orders' => 0,
                'pos_revenue' => 0,
                'pos_orders' => 0,
                'online_revenue' => 0,
                'online_orders' => 0
            ];
            
            foreach ($daily_results as $day) {
                $totals['total_revenue'] += floatval($day['daily_revenue']);
                $totals['total_orders'] += intval($day['daily_orders']);
                $totals['cash_revenue'] += floatval($day['daily_cash_revenue']);
                $totals['cash_orders'] += intval($day['daily_cash_orders']);
                $totals['card_revenue'] += floatval($day['daily_card_revenue']);
                $totals['card_orders'] += intval($day['daily_card_orders']);
                $totals['pos_revenue'] += floatval($day['daily_pos_revenue']);
                $totals['pos_orders'] += intval($day['daily_pos_orders']);
                $totals['online_revenue'] += floatval($day['daily_online_revenue']);
                $totals['online_orders'] += intval($day['daily_online_orders']);
            }
            
            $totals['average_order_value'] = $totals['total_orders'] > 0 ? $totals['total_revenue'] / $totals['total_orders'] : 0;
            
            return [
                'totals' => $totals,
                'daily_data' => $daily_results
            ];
        });
    }
    
    /**
     * Enable/disable query caching
     */
    public static function set_cache_enabled($enabled) {
        self::$cache_enabled = $enabled;
    }
    
    /**
     * Set cache duration
     */
    public static function set_cache_duration($duration) {
        self::$cache_duration = $duration;
    }
}
