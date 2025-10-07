<?php
/**
 * WP POS Customer Assignment Fix
 * 
 * This file implements hook-level protection to prevent WooCommerce from
 * overriding customer assignments in POS orders.
 * 
 * Usage: Include this file in checkout.php before creating orders
 */

// Global variable to store intended customer during order creation
global $jpos_intended_customer_id;
$jpos_intended_customer_id = null;

/**
 * Prevent WooCommerce from auto-assigning logged-in user to orders
 * This hook fires during order creation and ensures our intended customer is used
 */
add_filter('woocommerce_checkout_customer_id', function($customer_id) {
    global $jpos_intended_customer_id;
    
    // If we've set an intended customer, use it regardless of logged-in user
    if ($jpos_intended_customer_id !== null) {
        error_log("JPOS: Hook override - forcing customer_id to: {$jpos_intended_customer_id}");
        return $jpos_intended_customer_id;
    }
    
    return $customer_id;
}, 999); // High priority to run last

/**
 * Prevent customer override during order status changes
 */
add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status, $order) {
    global $jpos_intended_customer_id;
    
    // Only protect orders being created via JPOS
    if ($jpos_intended_customer_id !== null) {
        $current_customer = $order->get_customer_id();
        
        if ($current_customer != $jpos_intended_customer_id) {
            error_log("JPOS: Status change tried to override customer from {$jpos_intended_customer_id} to {$current_customer} - restoring");
            $order->set_customer_id($jpos_intended_customer_id);
            $order->save();
        }
    }
}, 10, 4);

/**
 * Prevent customer override after calculate_totals()
 */
add_action('woocommerce_order_after_calculate_totals', function($and_taxes, $order) {
    global $jpos_intended_customer_id;
    
    if ($jpos_intended_customer_id !== null) {
        $current_customer = $order->get_customer_id();
        
        if ($current_customer != $jpos_intended_customer_id) {
            error_log("JPOS: calculate_totals tried to override customer from {$jpos_intended_customer_id} to {$current_customer} - restoring");
            $order->set_customer_id($jpos_intended_customer_id);
        }
    }
}, 10, 2);

/**
 * Helper function to safely create order with intended customer
 * 
 * @param int $customer_id The intended customer ID for this order
 * @return WC_Order|WP_Error The created order or error
 */
function jpos_create_order_with_customer($customer_id) {
    global $jpos_intended_customer_id;
    
    // Set global so hooks can access it
    $jpos_intended_customer_id = $customer_id;
    
    error_log("JPOS: Creating order with intended customer: {$customer_id}");
    
    // Create order WITH customer_id parameter (WooCommerce best practice)
    $order = wc_create_order([
        'customer_id' => $customer_id,
        'status' => 'pending'
    ]);
    
    if (!$order || is_wp_error($order)) {
        $jpos_intended_customer_id = null; // Clear global on error
        return $order;
    }
    
    // Verify customer was set correctly
    if ($order->get_customer_id() != $customer_id) {
        error_log("JPOS WARNING: Order created but customer_id mismatch! Expected {$customer_id}, got {$order->get_customer_id()}");
        $order->set_customer_id($customer_id);
    }
    
    return $order;
}

/**
 * Helper function to finalize order and clear protection
 * Call this after order is completely saved and finalized
 * 
 * @param WC_Order $order The order to finalize
 */
function jpos_finalize_order($order) {
    global $jpos_intended_customer_id;
    
    $order_id = $order->get_id();
    $final_customer = $order->get_customer_id();
    
    error_log("JPOS: Finalizing order {$order_id} with customer: {$final_customer} (intended: {$jpos_intended_customer_id})");
    
    // Final verification
    if ($final_customer != $jpos_intended_customer_id) {
        error_log("JPOS ERROR: Final customer mismatch! Forcing correction.");
        $order->set_customer_id($jpos_intended_customer_id);
        $order->save();
        
        // Force database update as last resort
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            ['post_author' => $jpos_intended_customer_id],
            ['ID' => $order_id],
            ['%d'],
            ['%d']
        );
        
        update_post_meta($order_id, '_customer_user', $jpos_intended_customer_id);
        clean_post_cache($order_id);
    }
    
    // Clear global protection
    $jpos_intended_customer_id = null;
    
    error_log("JPOS: Order {$order_id} finalized successfully with customer: {$final_customer}");
}