<?php
// FILE: /wp-pos/api/checkout.php

require_once __DIR__ . '/../../wp-load.php';
require_once __DIR__ . '/jpos-auth-helper.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/checkout-customer-fix.php';

define('JPOS_SETTINGS_OPTION_KEY', 'jpos_receipt_settings');

header('Content-Type: application/json');

// Require authentication
jpos_require_auth('checkout');

global $wpdb;

$data = JPOS_Validation::validate_json_input(file_get_contents('php://input'));

// CSRF Protection: Verify nonce for checkout requests
$nonce = $data['nonce'] ?? '';
if (!wp_verify_nonce($nonce, 'wppos_checkout_nonce')) {
    wp_send_json_error(['message' => 'Security token invalid. Please refresh the page and try again.'], 403);
    exit;
}

// Validate checkout input
$validated_data = JPOS_Validation::validate_input($data, [
    'payment_method' => ['type' => 'text', 'required' => false, 'max_length' => 50],
    'cart_items' => ['type' => 'text', 'required' => true] // Will be validated separately as array
]);

// Validate cart items structure
$cart_items = $data['cart_items'] ?? [];
if (!is_array($cart_items)) {
    wp_send_json_error(['message' => 'Cart items must be an array.'], 400);
    exit;
}

$payment_method_title = $validated_data['payment_method'] ?? 'Cash';
$fee_discount_data = $data['fee_discount'] ?? null;
$split_payments = $data['split_payments'] ?? null;
$current_user = wp_get_current_user();

// Check if customer is attached to cart, otherwise use cashier
$customer_id = isset($data['customer_id']) ? intval($data['customer_id']) : null;

if ($customer_id && $customer_id > 0) {
    // Use attached customer
    $customer = get_userdata($customer_id);
    if ($customer) {
        $order_customer_id = $customer_id;
        $customer_name = $customer->display_name;
        error_log("JPOS CHECKOUT - Using attached customer: {$customer_name} (ID: {$order_customer_id})");
    } else {
        // Customer not found, fall back to cashier
        $order_customer_id = $current_user->ID;
        $customer_name = $current_user->display_name;
        error_log("JPOS CHECKOUT - Customer ID {$customer_id} not found, using cashier as customer");
    }
} else {
    // No customer attached, use cashier
    $order_customer_id = $current_user->ID;
    $customer_name = $current_user->display_name;
    error_log("JPOS CHECKOUT - No customer attached, using cashier as customer: " . $order_customer_id);
}

if (empty($cart_items) && !$fee_discount_data) {
    wp_send_json_error(['message' => 'Cart is empty.'], 400);
    exit;
}

$wpdb->query('START TRANSACTION');

try {
    
    // Create order - don't pass customer_id yet
    $order = wc_create_order(['status' => 'pending']);
    
    if (!$order || is_wp_error($order)) {
        throw new Exception("Failed to create order");
    }
    
    $order_id = $order->get_id();
    
    // IMMEDIATELY write customer directly to database - bypass all WooCommerce methods
    $wpdb->update(
        $wpdb->posts,
        ['post_author' => $order_customer_id],
        ['ID' => $order_id],
        ['%d'],
        ['%d']
    );
    
    // IMMEDIATELY write customer meta directly
    update_post_meta($order_id, '_customer_user', $order_customer_id);
    
    // Force cache clear
    clean_post_cache($order_id);
    wp_cache_delete('order-' . $order_id, 'orders');
    
    error_log("JPOS CHECKOUT DEBUG - Order {$order_id} created, customer {$order_customer_id} written DIRECTLY to database");
    
    // Track who created the order via POS
    $order->add_meta_data('_jpos_created_by', $current_user->ID, true);
    $order->add_meta_data('_jpos_created_by_name', $current_user->display_name, true);

    // Use store address for all POS sales
    $pos_settings = get_option(JPOS_SETTINGS_OPTION_KEY, []);
    $store_address = [
        'first_name' => $pos_settings['name'] ?? 'POS',
        'last_name'  => 'Sale',
        'company'    => $pos_settings['name'] ?? 'JPOS',
        'address_1'  => $pos_settings['address'] ?? 'N/A',
        'city'       => '',
        'state'      => '',
        'postcode'   => '',
        'country'    => '',
        'email'      => $pos_settings['email'] ?? $current_user->user_email,
        'phone'      => $pos_settings['phone'] ?? '',
    ];
    $order->set_address($store_address, 'billing');
    $order->set_address($store_address, 'shipping');

    foreach ($cart_items as $cart_item) {
        $product = wc_get_product($cart_item['id'] ?? 0);
        if (!$product) { throw new Exception("Product with ID {$cart_item['id']} could not be found."); }
        $order->add_product($product, $cart_item['qty']);
    }

    $receipt_fees = [];
    // Always include fee and discount objects for frontend safety
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
    if ($fee_discount_data && isset($fee_discount_data['type'], $fee_discount_data['amount']) && floatval($fee_discount_data['amount']) > 0) {
        if ($fee_discount_data['type'] === 'fee') {
            $fee_data = $fee_discount_data;
        } else if ($fee_discount_data['type'] === 'discount') {
            $discount_data = $fee_discount_data;
        }
    }
    if ($fee_discount_data && isset($fee_discount_data['type'], $fee_discount_data['amount']) && floatval($fee_discount_data['amount']) > 0) {
        $item_fee = new WC_Order_Item_Fee();
        $amount = floatval($fee_discount_data['amount']);
        $label = sanitize_text_field($fee_discount_data['label']);
        $type = sanitize_text_field($fee_discount_data['type']);
        $amountType = sanitize_text_field($fee_discount_data['amountType']);
        $fee_label = !empty($label) ? $label : ucwords("$type - $amount" . ($amountType === 'percentage' ? '%' : ''));
        $item_fee->set_name($fee_label);

        if ($amountType === 'percentage') {
            $subtotal = $order->get_subtotal();
            $fee_value = ($subtotal > 0) ? ($subtotal * ($amount / 100)) : 0;
        } else {
            $fee_value = $amount;
        }
        
        $fee_amount_for_order = ($type === 'discount') ? -abs($fee_value) : abs($fee_value);
        $item_fee->set_total($fee_amount_for_order);
        $item_fee->set_tax_status('none');
        $order->add_item($item_fee);
        
        $receipt_fees[] = [
            'name' => $fee_label,
            'total' => wc_format_decimal($fee_amount_for_order, 2),
        ];
    }

    $order->set_payment_method('jpos_payment');
    $order->set_payment_method_title($payment_method_title);
    $order->add_meta_data('_created_via_jpos', '1', true);
    if ($split_payments && is_array($split_payments) && count($split_payments) > 1) {
        $order->add_meta_data('_jpos_split_payments', json_encode($split_payments), true);
    }
    
    // Calculate totals
    $order->calculate_totals(true);
    
    // Force customer in database again BEFORE save (calculate_totals may have changed it)
    $wpdb->update(
        $wpdb->posts,
        ['post_author' => $order_customer_id],
        ['ID' => $order_id],
        ['%d'],
        ['%d']
    );
    update_post_meta($order_id, '_customer_user', $order_customer_id);
    
    // Save the order
    $saved_order_id = $order->save();
    
    if (!$saved_order_id) { throw new Exception("Could not save the order."); }
    
    // Force customer in database again AFTER save
    $wpdb->update(
        $wpdb->posts,
        ['post_author' => $order_customer_id],
        ['ID' => $order_id],
        ['%d'],
        ['%d']
    );
    update_post_meta($order_id, '_customer_user', $order_customer_id);
    
    error_log("JPOS CHECKOUT DEBUG - Order {$saved_order_id} saved, customer forced in DB");
    
    // Set status to completed
    $order->set_status('completed');
    
    // Force customer in database again BEFORE status save
    $wpdb->update(
        $wpdb->posts,
        ['post_author' => $order_customer_id],
        ['ID' => $order_id],
        ['%d'],
        ['%d']
    );
    update_post_meta($order_id, '_customer_user', $order_customer_id);
    
    $order->save();
    
    // FINAL database write - this is the last word
    $wpdb->update(
        $wpdb->posts,
        ['post_author' => $order_customer_id],
        ['ID' => $order_id],
        ['%d'],
        ['%d']
    );
    update_post_meta($order_id, '_customer_user', $order_customer_id);
    
    // Clear all caches
    clean_post_cache($order_id);
    wp_cache_delete('order-' . $order_id, 'orders');
    wc_delete_shop_order_transients($order_id);
    
    error_log("JPOS CHECKOUT DEBUG - Order {$order_id} completed, customer {$order_customer_id} FORCED in database (final)");
    
    // Schedule a background action to run AFTER any async WooCommerce processes
    // This ensures customer persists even if WooCommerce has background jobs
    if (function_exists('as_schedule_single_action')) {
        as_schedule_single_action(
            time() + 5, // 5 seconds from now
            'jpos_final_customer_lock',
            [
                'order_id' => $order_id,
                'customer_id' => $order_customer_id
            ],
            'jpos'
        );
    }
    
    $receipt_items = [];
    foreach ($order->get_items('line_item') as $item) {
        $unit_price = ($item->get_quantity() > 0) ? ($item->get_total() / $item->get_quantity()) : 0;
        $receipt_items[] = [
            'name'      => $item->get_name(), 'sku' => $item->get_product() ? $item->get_product()->get_sku() : 'N/A',
            'quantity'  => $item->get_quantity(), 'price' => wc_format_decimal($unit_price, 2),
            'total'     => wc_format_decimal($item->get_total(), 2)
        ];
    }
    
    $date_created_utc4 = $order->get_date_created();
    if ($date_created_utc4) {
        $date_created_utc4->setTimezone(new DateTimeZone('America/New_York'));
        $date_created_str = $date_created_utc4->format('M j, Y, g:i a');
    } else {
        $date_created_str = '';
    }
    $receipt_data = [
        'order_number' => $order->get_order_number(),
        'date_created' => $date_created_str,
        'subtotal'     => wc_format_decimal($order->get_subtotal(), 2),
        'total' => wc_format_decimal($order->get_total(), 2),
        'payment_method' => ($split_payments && is_array($split_payments) && count($split_payments) > 1)
            ? implode(' + ', array_map(function($s) { return $s['method'] . ' ($' . number_format($s['amount'], 2) . ')'; }, $split_payments))
            : $order->get_payment_method_title(),
        'items'        => $receipt_items,
        'fee'          => $fee_data,
        'discount'     => $discount_data,
        'fees'         => $receipt_fees,
        'split_payments' => $split_payments,
        'customer_name' => $customer_name,
        'cashier_name' => $current_user->display_name,
    ];
    
    $wpdb->query('COMMIT');
    wp_send_json_success(['message' => 'Checkout successful!', 'receipt_data' => $receipt_data]);

} catch (Exception $e) {
    $wpdb->query('ROLLBACK');
    error_log("JPOS Checkout Exception: " . $e->getMessage());
    wp_send_json_error(['message' => 'Checkout failed. ' . $e->getMessage()], 500);
}

// Add WooCommerce admin display for split payments
add_action('woocommerce_admin_order_data_after_billing_address', 'jpos_display_split_payments_in_admin');
function jpos_display_split_payments_in_admin($order) {
    $split_payments = $order->get_meta('_jpos_split_payments', true);
    if ($split_payments) {
        $split_payments = json_decode($split_payments, true);
        if (is_array($split_payments) && count($split_payments) > 1) {
            echo '<div class="jpos-split-payments" style="margin-top: 10px; padding: 10px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">';
            echo '<h4 style="margin: 0 0 10px 0; color: #495057; font-size: 14px; font-weight: 600;">Split Payment Breakdown</h4>';
            foreach ($split_payments as $payment) {
                echo '<div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px;">';
                echo '<span style="font-weight: 500;">' . esc_html($payment['method']) . '</span>';
                echo '<span style="font-weight: 600; color: #28a745;">$' . number_format($payment['amount'], 2) . '</span>';
                echo '</div>';
            }
            echo '</div>';
        }
    }
}