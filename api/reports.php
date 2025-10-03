<?php
// FILE: /wp-pos/api/reports.php
// Comprehensive Reporting API with Intelligent Time Granularity

require_once __DIR__ . '/../../wp-load.php';

header('Content-Type: application/json');

// --- AUTHENTICATION AND AUTHORIZATION ---
if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}
// --- END AUTHENTICATION ---

global $wpdb;

/**
 * Calculate intelligent time granularity based on period
 */
function getTimeGranularity($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $diff = $start->diff($end);
    
    $total_days = $diff->days;
    
    // Intraday periods (same day) - hourly breakdown
    if ($total_days == 0) {
        return 'hour';
    }
    // Weekly or monthly periods - daily breakdown
    elseif ($total_days <= 31) {
        return 'day';
    }
    // 2+ months up to 2 years - monthly breakdown
    elseif ($total_days <= 730) {
        return 'month';
    }
    // More than 2 years - yearly breakdown
    else {
        return 'year';
    }
}

/**
 * Get period boundaries based on preset or custom dates
 */
function getPeriodBoundaries($period, $custom_start = null, $custom_end = null) {
    $now = new DateTime();
    
    switch ($period) {
        case 'today':
            $start = clone $now;
            $start->setTime(0, 0, 0);
            $end = clone $now;
            $end->setTime(23, 59, 59);
            break;
            
        case 'yesterday':
            $start = clone $now;
            $start->modify('-1 day');
            $start->setTime(0, 0, 0);
            $end = clone $start;
            $end->setTime(23, 59, 59);
            break;
            
        case 'this_week':
            $start = clone $now;
            $start->modify('monday this week');
            $start->setTime(0, 0, 0);
            $end = clone $now;
            $end->setTime(23, 59, 59);
            break;
            
        case 'last_week':
            $start = clone $now;
            $start->modify('monday last week');
            $start->setTime(0, 0, 0);
            $end = clone $start;
            $end->modify('+6 days');
            $end->setTime(23, 59, 59);
            break;
            
        case 'this_month':
            $start = clone $now;
            $start->modify('first day of this month');
            $start->setTime(0, 0, 0);
            $end = clone $now;
            $end->setTime(23, 59, 59);
            break;
            
        case 'this_year':
            $start = clone $now;
            $start->modify('first day of January this year');
            $start->setTime(0, 0, 0);
            $end = clone $now;
            $end->setTime(23, 59, 59);
            break;
            
        case 'custom':
            if (!$custom_start || !$custom_end) {
                throw new Exception('Custom period requires both start and end dates');
            }
            $start = new DateTime($custom_start);
            $start->setTime(0, 0, 0);
            $end = new DateTime($custom_end);
            $end->setTime(23, 59, 59);
            break;
            
        default:
            throw new Exception('Invalid period specified');
    }
    
    return [
        'start' => $start->format('Y-m-d H:i:s'),
        'end' => $end->format('Y-m-d H:i:s')
    ];
}

/**
 * Format date/time for grouping based on granularity
 */
function getGroupingFormat($granularity) {
    switch ($granularity) {
        case 'hour':
            return '%Y-%m-%d %H:00:00';
        case 'day':
            return '%Y-%m-%d';
        case 'month':
            return '%Y-%m';
        case 'year':
            return '%Y';
        default:
            return '%Y-%m-%d';
    }
}

/**
 * Get chart data with intelligent granularity
 */
function getChartData($start_date, $end_date, $granularity) {
    global $wpdb;
    
    $grouping_format = getGroupingFormat($granularity);
    
    $sql = "SELECT 
        DATE_FORMAT(p.post_date, %s) as period,
        COUNT(*) as order_count,
        SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_amount,
        AVG(CAST(pm_total.meta_value AS DECIMAL(10,2))) as avg_order_value
    FROM {$wpdb->prefix}posts p
    LEFT JOIN {$wpdb->prefix}postmeta pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
    WHERE p.post_type = 'shop_order' 
    AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
    AND p.post_date >= %s 
    AND p.post_date <= %s
    GROUP BY DATE_FORMAT(p.post_date, %s)
    ORDER BY period ASC";
    
    $results = $wpdb->get_results($wpdb->prepare($sql, $grouping_format, $start_date, $end_date, $grouping_format));
    
    return $results;
}

/**
 * Get detailed orders for the period
 */
function getOrdersForPeriod($start_date, $end_date, $limit = 100) {
    global $wpdb;
    
    $sql = "SELECT p.ID 
    FROM {$wpdb->prefix}posts p
    WHERE p.post_type = 'shop_order' 
    AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
    AND p.post_date >= %s 
    AND p.post_date <= %s
    ORDER BY p.post_date DESC 
    LIMIT %d";
    
    $order_ids = $wpdb->get_col($wpdb->prepare($sql, $start_date, $end_date, $limit));
    
    $orders = [];
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;
        
        $date_created = $order->get_date_created();
        if ($date_created) {
            $date_created->setTimezone(new DateTimeZone('America/New_York'));
            $date_created_str = $date_created->format('M j, Y, g:i a');
        } else {
            $date_created_str = '';
        }
        
        $is_pos_order = $order->get_meta('_created_via_jpos') === '1';
        $order_source = $is_pos_order ? 'POS' : 'Online';
        
        $orders[] = [
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'date' => $date_created_str,
            'date_raw' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'status' => $order->get_status(),
            'source' => $order_source,
            'total' => floatval($order->get_total()),
            'item_count' => $order->get_item_count(),
            'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'payment_method' => $order->get_payment_method_title(),
            'items' => array_map(function($item) {
                return [
                    'name' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'total' => floatval($item->get_total())
                ];
            }, $order->get_items())
        ];
    }
    
    return $orders;
}

/**
 * Calculate summary statistics
 */
function getSummaryStats($start_date, $end_date) {
    global $wpdb;
    
    $sql = "SELECT 
        COUNT(*) as total_orders,
        SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_revenue,
        AVG(CAST(pm_total.meta_value AS DECIMAL(10,2))) as avg_order_value,
        MIN(CAST(pm_total.meta_value AS DECIMAL(10,2))) as min_order_value,
        MAX(CAST(pm_total.meta_value AS DECIMAL(10,2))) as max_order_value
    FROM {$wpdb->prefix}posts p
    LEFT JOIN {$wpdb->prefix}postmeta pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
    WHERE p.post_type = 'shop_order' 
    AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
    AND p.post_date >= %s 
    AND p.post_date <= %s";
    
    $result = $wpdb->get_row($wpdb->prepare($sql, $start_date, $end_date));
    
    return [
        'total_orders' => intval($result->total_orders ?? 0),
        'total_revenue' => floatval($result->total_revenue ?? 0),
        'avg_order_value' => floatval($result->avg_order_value ?? 0),
        'min_order_value' => floatval($result->min_order_value ?? 0),
        'max_order_value' => floatval($result->max_order_value ?? 0)
    ];
}

try {
    // Get request parameters
    $period = sanitize_text_field($_GET['period'] ?? 'today');
    $custom_start = sanitize_text_field($_GET['custom_start'] ?? null);
    $custom_end = sanitize_text_field($_GET['custom_end'] ?? null);
    
    // Get period boundaries
    $boundaries = getPeriodBoundaries($period, $custom_start, $custom_end);
    $start_date = $boundaries['start'];
    $end_date = $boundaries['end'];
    
    // Determine intelligent time granularity
    $granularity = getTimeGranularity($start_date, $end_date);
    
    // Get chart data
    $chart_data = getChartData($start_date, $end_date, $granularity);
    
    // Get detailed orders
    $orders = getOrdersForPeriod($start_date, $end_date);
    
    // Get summary statistics
    $summary = getSummaryStats($start_date, $end_date);
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'period' => [
                'type' => $period,
                'start' => $start_date,
                'end' => $end_date,
                'granularity' => $granularity
            ],
            'summary' => $summary,
            'chart_data' => $chart_data,
            'orders' => $orders,
            'chart_labels' => array_map(function($item) use ($granularity) {
                $date = new DateTime($item->period);
                switch ($granularity) {
                    case 'hour':
                        return $date->format('g:i A');
                    case 'day':
                        return $date->format('M j');
                    case 'month':
                        return $date->format('M Y');
                    case 'year':
                        return $date->format('Y');
                    default:
                        return $date->format('M j, Y');
                }
            }, $chart_data),
            'chart_values' => array_map(function($item) {
                return floatval($item->total_amount);
            }, $chart_data),
            'chart_order_counts' => array_map(function($item) {
                return intval($item->order_count);
            }, $chart_data)
        ]
    ];
    
    wp_send_json($response);
    
} catch (Exception $e) {
    wp_send_json_error(['message' => $e->getMessage()], 400);
}
