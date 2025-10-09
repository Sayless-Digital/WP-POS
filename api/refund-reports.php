<?php
// FILE: /wp-pos/api/refund-reports.php
// Refund & Exchange Reports API

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
 * Get period boundaries based on preset or custom dates
 */
function getRefundPeriodBoundaries($period, $custom_start = null, $custom_end = null) {
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
 * Get refunds for the period
 */
function getRefundsForPeriod($start_date, $end_date, $limit = 100) {
    global $wpdb;
    
    // Query for refund orders (shop_order_refund post type)
    $sql = "SELECT p.ID
    FROM {$wpdb->prefix}posts p
    WHERE p.post_type = 'shop_order_refund'
    AND p.post_status != 'trash'
    AND p.post_date >= %s
    AND p.post_date <= %s
    ORDER BY p.post_date DESC
    LIMIT %d";
    
    $refund_ids = $wpdb->get_col($wpdb->prepare($sql, $start_date, $end_date, $limit));
    
    $refunds = [];
    foreach ($refund_ids as $refund_id) {
        $refund = wc_get_order($refund_id);
        if (!$refund) continue;
        
        $date_created = $refund->get_date_created();
        if ($date_created) {
            $date_created->setTimezone(new DateTimeZone('America/New_York'));
            $date_created_str = $date_created->format('M j, Y, g:i a');
        } else {
            $date_created_str = '';
        }
        
        // Get parent order
        $parent_order_id = $refund->get_parent_id();
        $parent_order = wc_get_order($parent_order_id);
        
        // Check if this is an exchange (look for order note)
        $order_notes = wc_get_order_notes(['order_id' => $parent_order_id, 'limit' => 10]);
        $is_exchange = false;
        $exchange_order_id = null;
        
        foreach ($order_notes as $note) {
            if (strpos($note->content, 'Exchanged for new Order') !== false) {
                $is_exchange = true;
                // Extract exchange order number from note
                if (preg_match('/Order #(\d+)/', $note->content, $matches)) {
                    $exchange_order_id = $matches[1];
                }
                break;
            }
        }
        
        $refunds[] = [
            'id' => $refund->get_id(),
            'refund_number' => $refund->get_id(),
            'date' => $date_created_str,
            'date_raw' => $refund->get_date_created()->format('Y-m-d H:i:s'),
            'amount' => floatval($refund->get_amount()),
            'reason' => $refund->get_reason(),
            'parent_order_id' => $parent_order_id,
            'parent_order_number' => $parent_order ? $parent_order->get_order_number() : $parent_order_id,
            'is_exchange' => $is_exchange,
            'exchange_order_id' => $exchange_order_id,
            'customer' => $parent_order ? ($parent_order->get_billing_first_name() . ' ' . $parent_order->get_billing_last_name()) : 'Unknown',
            'items' => array_values(array_map(function($item) {
                return [
                    'name' => $item->get_name(),
                    'quantity' => abs($item->get_quantity()), // Refund quantities are negative
                    'total' => abs(floatval($item->get_total()))
                ];
            }, $refund->get_items()))
        ];
    }
    
    return $refunds;
}

/**
 * Calculate summary statistics for refunds
 */
function getRefundSummaryStats($start_date, $end_date) {
    global $wpdb;
    
    $sql = "SELECT
        COUNT(*) as total_refunds,
        SUM(ABS(CAST(pm_total.meta_value AS DECIMAL(10,2)))) as total_refunded,
        AVG(ABS(CAST(pm_total.meta_value AS DECIMAL(10,2)))) as avg_refund_amount,
        MIN(ABS(CAST(pm_total.meta_value AS DECIMAL(10,2)))) as min_refund_amount,
        MAX(ABS(CAST(pm_total.meta_value AS DECIMAL(10,2)))) as max_refund_amount
    FROM {$wpdb->prefix}posts p
    LEFT JOIN {$wpdb->prefix}postmeta pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_refund_amount'
    WHERE p.post_type = 'shop_order_refund'
    AND p.post_status != 'trash'
    AND p.post_date >= %s
    AND p.post_date <= %s";
    
    $result = $wpdb->get_row($wpdb->prepare($sql, $start_date, $end_date));
    
    // Count exchanges (refunds with exchange order notes)
    $refunds = getRefundsForPeriod($start_date, $end_date, 1000);
    $exchange_count = 0;
    $total_exchange_value = 0;
    
    foreach ($refunds as $refund) {
        if ($refund['is_exchange']) {
            $exchange_count++;
            $total_exchange_value += $refund['amount'];
        }
    }
    
    return [
        'total_refunds' => intval($result->total_refunds ?? 0),
        'total_refunded' => floatval($result->total_refunded ?? 0),
        'avg_refund_amount' => floatval($result->avg_refund_amount ?? 0),
        'min_refund_amount' => floatval($result->min_refund_amount ?? 0),
        'max_refund_amount' => floatval($result->max_refund_amount ?? 0),
        'total_exchanges' => $exchange_count,
        'total_exchange_value' => $total_exchange_value,
        'refunds_only' => intval($result->total_refunds ?? 0) - $exchange_count
    ];
}

try {
    // Get request parameters
    $period = sanitize_text_field($_GET['period'] ?? 'today');
    $custom_start = sanitize_text_field($_GET['custom_start'] ?? null);
    $custom_end = sanitize_text_field($_GET['custom_end'] ?? null);
    
    // Get period boundaries
    $boundaries = getRefundPeriodBoundaries($period, $custom_start, $custom_end);
    $start_date = $boundaries['start'];
    $end_date = $boundaries['end'];
    
    // Get refunds
    $refunds = getRefundsForPeriod($start_date, $end_date);
    
    // Get summary statistics
    $summary = getRefundSummaryStats($start_date, $end_date);
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'period' => [
                'type' => $period,
                'start' => $start_date,
                'end' => $end_date
            ],
            'summary' => $summary,
            'refunds' => $refunds
        ]
    ];
    
    wp_send_json($response);
    
} catch (Exception $e) {
    wp_send_json_error(['message' => $e->getMessage()], 400);
}