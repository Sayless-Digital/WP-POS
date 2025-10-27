<?php
// FILE: /jpos/api/orders.php

require_once __DIR__ . '/../../wp-load.php';

header('Content-Type: application/json');

// --- AUTHENTICATION AND AUTHORIZATION ---
if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}
// --- END AUTHENTICATION ---

global $wpdb;

// Handle POST request for order deletion (using POST because some servers don't support DELETE properly)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    error_log('JPOS Orders API - POST Request Body: ' . $raw_input);
    
    $input = json_decode($raw_input, true);
    error_log('JPOS Orders API - Decoded Input: ' . print_r($input, true));
    
    // Check if this is a delete request
    if (isset($input['action']) && $input['action'] === 'delete_order') {
        error_log('JPOS Orders API - Delete action triggered');
        if (!isset($input['order_id'])) {
            wp_send_json_error(['message' => 'Missing order_id parameter'], 400);
            exit;
        }
        
        $order_id = intval($input['order_id']);
        $restore_stock = isset($input['restore_stock']) ? filter_var($input['restore_stock'], FILTER_VALIDATE_BOOLEAN) : false;
        
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found'], 404);
            exit;
        }
        
        // If restore_stock is true, restore inventory for each item
        if ($restore_stock) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
                $product = wc_get_product($product_id);
                
                if ($product && $product->managing_stock()) {
                    $current_stock = $product->get_stock_quantity();
                    $new_stock = $current_stock + $item->get_quantity();
                    $product->set_stock_quantity($new_stock);
                    $product->save();
                }
            }
        }
        
        // Delete the order
        $deleted = wp_delete_post($order_id, true); // true = force delete (bypass trash)
        
        if ($deleted) {
            wp_send_json_success([
                'message' => $restore_stock ? 'Order deleted and stock restored' : 'Order deleted without stock restoration'
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to delete order'], 500);
        }
        exit;
    }
    
    // If we get here with a POST request but no action, log it
    error_log('JPOS Orders API - POST request but no delete_order action found');
}

// Only proceed with GET orders if not a POST delete request
$limit = 100; // Limit the number of orders returned for performance

// Base SQL with conditional JPOS filter
$sql = "SELECT p.ID FROM {$wpdb->prefix}posts as p
     WHERE p.post_type = 'shop_order'";

// Add source filter (POS vs Online orders) - using prepared statements
$sql_params = [];
if (isset($_GET['source_filter']) && !empty($_GET['source_filter']) && $_GET['source_filter'] !== 'all') {
    $source_filter = sanitize_text_field($_GET['source_filter']);
    if ($source_filter === 'pos') {
        // Only POS orders (created via JPOS)
        $sql .= " AND EXISTS (
            SELECT 1 FROM {$wpdb->prefix}postmeta pm
            WHERE pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value = %s
        )";
        $sql_params[] = '_created_via_jpos';
        $sql_params[] = '1';
    } elseif ($source_filter === 'online') {
        // Only online orders (NOT created via JPOS)
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM {$wpdb->prefix}postmeta pm
            WHERE pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value = %s
        )";
        $sql_params[] = '_created_via_jpos';
        $sql_params[] = '1';
    }
}
// Note: No default filter - show all orders by default

// --- Append Date Filters ---
if (isset($_GET['date_filter']) && !empty($_GET['date_filter']) && $_GET['date_filter'] !== 'all') {
    $date_filter = sanitize_text_field($_GET['date_filter']);
    $date_comparison = '';
    switch ($date_filter) {
        case 'today':
            $date_comparison = "p.post_date >= '" . date('Y-m-d 00:00:00') . "'";
            break;
        case 'this_week':
            $date_comparison = "p.post_date >= '" . date('Y-m-d 00:00:00', strtotime('monday this week')) . "'";
            break;
        case 'this_month':
            $date_comparison = "p.post_date >= '" . date('Y-m-d 00:00:00', strtotime('first day of this month')) . "'";
            break;
    }
    if ($date_comparison) {
        $sql .= " AND ($date_comparison)";
    }
}

// --- Append Status Filters ---
if (isset($_GET['status_filter']) && !empty($_GET['status_filter']) && $_GET['status_filter'] !== 'all') {
    $status = 'wc-' . sanitize_text_field($_GET['status_filter']);
    $sql .= $wpdb->prepare(" AND p.post_status = %s", $status);
}

// --- Append Customer Filters ---
if (isset($_GET['customer_filter']) && !empty($_GET['customer_filter']) && $_GET['customer_filter'] !== 'all') {
    $customer_id = intval($_GET['customer_filter']);
    $sql .= " AND EXISTS (
        SELECT 1 FROM {$wpdb->prefix}postmeta pm
        WHERE pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value = %d
    )";
    $sql_params[] = '_customer_user';
    $sql_params[] = $customer_id;
}

$sql .= " ORDER BY p.post_date DESC LIMIT %d";
$sql_params[] = $limit;

// Execute the query to get order IDs
$order_ids = $wpdb->get_col($wpdb->prepare($sql, $sql_params));

$response_data = [];
if (!empty($order_ids)) {
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        $date_created_utc4 = $order->get_date_created();
        if ($date_created_utc4) {
            $date_created_utc4->setTimezone(new DateTimeZone('America/New_York'));
            $date_created_str = $date_created_utc4->format('M j, Y, g:i a');
        } else {
            $date_created_str = '';
        }
        
        // Check if this is a POS order
        $is_pos_order = $order->get_meta('_created_via_jpos') === '1';
        $order_source = $is_pos_order ? 'POS' : 'Online';
        
        $split_payments = $order->get_meta('_jpos_split_payments', true);
        $split_payments = $split_payments ? json_decode($split_payments, true) : null;
        
        // Get customer information
        $customer_id = $order->get_customer_id();
        $customer_name = '';
        if ($customer_id) {
            $customer = get_userdata($customer_id);
            if ($customer) {
                $customer_name = trim($customer->first_name . ' ' . $customer->last_name);
                if (empty($customer_name)) {
                    $customer_name = $customer->display_name;
                }
            }
        }
        if (empty($customer_name)) {
            $billing_first = $order->get_billing_first_name();
            $billing_last = $order->get_billing_last_name();
            $customer_name = trim($billing_first . ' ' . $billing_last);
            if (empty($customer_name)) {
                $customer_name = 'Guest';
            }
        }
        
        // Check if order has refunds
        $refunds = $order->get_refunds();
        $has_refunds = !empty($refunds);
        
        // Extract fee/discount items from order
        $fee_data = [
            'type' => 'fee',
            'amount' => '',
            'label' => '',
            'amountType' => 'flat',
        ];
        $discount_data = [
            'type' => 'discount',
            'amount' => '',
            'label' => '',
            'amountType' => 'flat',
        ];
        $fees_array = [];
        
        // Get fee items from order (WC_Order_Item_Fee objects)
        foreach ($order->get_items('fee') as $fee_item) {
            $fee_name = $fee_item->get_name();
            $fee_total = floatval($fee_item->get_total());
            
            $fees_array[] = [
                'name' => $fee_name,
                'total' => wc_format_decimal($fee_total, 2),
            ];
            
            // Determine if it's a fee or discount based on amount sign
            // Also try to extract percentage from name if present
            $is_percentage = (strpos($fee_name, '%') !== false);
            
            if ($fee_total < 0) {
                // Negative = Discount
                $amount_value = abs($fee_total);
                
                // Try to extract percentage value from name (e.g., "30% Discount")
                if ($is_percentage && preg_match('/(\d+(?:\.\d+)?)\s*%/', $fee_name, $matches)) {
                    $discount_data = [
                        'type' => 'discount',
                        'amount' => $matches[1],
                        'label' => $fee_name,
                        'amountType' => 'percentage',
                    ];
                } else {
                    $discount_data = [
                        'type' => 'discount',
                        'amount' => (string)$amount_value,
                        'label' => $fee_name,
                        'amountType' => 'flat',
                    ];
                }
            } else if ($fee_total > 0) {
                // Positive = Fee
                $amount_value = $fee_total;
                
                // Try to extract percentage value from name (e.g., "5% Fee")
                if ($is_percentage && preg_match('/(\d+(?:\.\d+)?)\s*%/', $fee_name, $matches)) {
                    $fee_data = [
                        'type' => 'fee',
                        'amount' => $matches[1],
                        'label' => $fee_name,
                        'amountType' => 'percentage',
                    ];
                } else {
                    $fee_data = [
                        'type' => 'fee',
                        'amount' => (string)$amount_value,
                        'label' => $fee_name,
                        'amountType' => 'flat',
                    ];
                }
            }
        }
        
        $response_data[] = [
            'id'           => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'date_created' => $date_created_str,
            'status'       => $order->get_status(),
            'total'        => wc_format_decimal($order->get_total(), 2),
            'subtotal'     => wc_format_decimal($order->get_subtotal(), 2),
            'item_count'   => $order->get_item_count(),
            'source'       => $order_source,
            'customer_id'  => $customer_id,
            'customer_name' => $customer_name,
            'has_refunds'  => $has_refunds,
            'items'        => array_map(function($item) {
                $product = $item->get_product();
                return [
                    'name'      => $item->get_name(),
                    'quantity'  => $item->get_quantity(),
                    'total'     => wc_format_decimal($item->get_total(), 2),
                    'sku'       => $product ? $product->get_sku() : 'N/A',
                    'id'        => $item->get_variation_id() ?: $item->get_product_id(),
                ];
            }, array_values($order->get_items())),
            'split_payments' => $split_payments,
            'payment_method' => $order->get_payment_method_title(),
            'payment_method_id' => $order->get_payment_method(),
            'fee'          => $fee_data,
            'discount'     => $discount_data,
            'fees'         => $fees_array,
        ];
    }
}

wp_send_json_success($response_data);