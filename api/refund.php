<?php
// FILE: /jpos/api/refund.php

require_once __DIR__ . '/../wp-load.php';
require_once __DIR__ . '/error_handler.php';

header('Content-Type: application/json');

JPOS_Error_Handler::check_auth();

global $wpdb;

$data = JPOS_Error_Handler::safe_json_decode(file_get_contents('php://input'));

// CSRF Protection: Verify nonce for refund requests
$nonce = $data['nonce'] ?? '';
JPOS_Error_Handler::check_nonce($nonce, 'jpos_refund_nonce');

$original_order_id = absint($data['original_order_id'] ?? 0);
$refund_items = $data['refund_items'] ?? [];
$payment_method_title = sanitize_text_field($data['payment_method'] ?? 'Cash');
$new_sale_items = $data['new_sale_items'] ?? [];
$restore_stock = isset($data['restore_stock']) ? (bool)$data['restore_stock'] : true; // Default to true for backward compatibility

if (empty($original_order_id) || (empty($refund_items) && empty($new_sale_items))) {
    wp_send_json_error(['message' => 'Original Order ID and items are required.'], 400);
    exit;
}

$wpdb->query('START TRANSACTION');

try {
    $original_order = wc_get_order($original_order_id);
    if (!$original_order) {
        throw new Exception("Original order #{$original_order_id} not found.");
    }
    
    $total_refund_amount = 0;
    $line_items_to_refund = [];
    
    foreach ($refund_items as $item_to_refund) {
        $product_id_to_find = $item_to_refund['id'];
        $quantity_to_refund = abs($item_to_refund['qty']);
        $unit_price = floatval($item_to_refund['price']);
        $total_refund_amount += $unit_price * $quantity_to_refund;

        $item_found_in_order = false;
        foreach ($original_order->get_items() as $line_item_id => $line_item) {
            if ($line_item->get_product_id() == $product_id_to_find || $line_item->get_variation_id() == $product_id_to_find) {
                $line_items_to_refund[$line_item_id] = [
                    'qty' => $quantity_to_refund,
                    'refund_total' => $unit_price * $quantity_to_refund,
                    'refund_tax' => [],
                ];
                $item_found_in_order = true;
                break;
            }
        }

        if (!$item_found_in_order) {
            throw new Exception("Returned item (Product ID: {$product_id_to_find}) could not be found in the original order #{$original_order_id}.");
        }
    }
    
    $refund = wc_create_refund([
        'amount' => $total_refund_amount,
        'reason' => 'POS Return/Exchange',
        'order_id' => $original_order_id,
        'line_items' => $line_items_to_refund,
        'refund_payment' => false,
        'restock_items' => $restore_stock,
    ]);

    if (is_wp_error($refund)) {
        throw new Exception("Failed to create refund: " . $refund->get_error_message());
    }

    $refund_note = "Refund of $" . wc_format_decimal($total_refund_amount, 2) . " (Refund ID #" . $refund->get_id() . ") processed via JPOS.";

    if (!empty($new_sale_items)) {
        $exchange_order = wc_create_order(['customer_id' => $original_order->get_customer_id()]);
        
        foreach ($new_sale_items as $item) {
            $product = wc_get_product($item['id']);
            if ($product) {
                $exchange_order->add_product($product, $item['qty']);
            }
        }
        
        $exchange_order->set_payment_method('jpos_payment');
        $exchange_order->set_payment_method_title($payment_method_title);
        $exchange_order->add_meta_data('_created_via_jpos', '1', true);
        $exchange_order->calculate_totals(true);
        $exchange_order->set_status('completed');
        $exchange_order->save();
        
        $refund_note .= " Exchanged for new Order #" . $exchange_order->get_order_number() . ".";
    }
    
    $original_order->add_order_note($refund_note);
    $original_order->save();
    
    // Build comprehensive receipt data for refund/exchange
    $receipt_data = [
        'transaction_type' => !empty($new_sale_items) ? 'EXCHANGE' : 'REFUND',
        'refund_id' => $refund->get_id(),
        'original_order_number' => $original_order->get_order_number(),
        'date_created' => current_time('mysql'),
        'customer_name' => $original_order->get_formatted_billing_full_name() ?: 'Guest',
        'payment_method' => $payment_method_title,
        
        // Returned items (negative quantities for display)
        'returned_items' => array_map(function($item) {
            return [
                'name' => wc_get_product($item['id'])->get_name(),
                'sku' => wc_get_product($item['id'])->get_sku(),
                'quantity' => -abs($item['qty']), // Negative to show as return
                'price' => floatval($item['price']),
                'total' => -abs(floatval($item['price']) * abs($item['qty'])) // Negative total
            ];
        }, $refund_items),
        
        'refund_amount' => $total_refund_amount,
        
        // New items if exchange
        'new_items' => !empty($new_sale_items) ? array_map(function($item) {
            $product = wc_get_product($item['id']);
            return [
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'quantity' => $item['qty'],
                'price' => floatval($product->get_price()),
                'total' => floatval($product->get_price()) * $item['qty']
            ];
        }, $new_sale_items) : [],
        
        'exchange_order_number' => !empty($new_sale_items) ? $exchange_order->get_order_number() : null,
        'exchange_total' => !empty($new_sale_items) ? floatval($exchange_order->get_total()) : 0,
        
        // Calculate net amount (negative = refund due, positive = payment due)
        'net_amount' => (!empty($new_sale_items) ? floatval($exchange_order->get_total()) : 0) - $total_refund_amount
    ];
    
    $wpdb->query('COMMIT');
    wp_send_json_success([
        'message' => 'Refund/Exchange processed successfully.',
        'refund_id' => $refund->get_id(),
        'receipt_data' => $receipt_data
    ]);

} catch (Exception $e) {
    $wpdb->query('ROLLBACK');
    error_log("JPOS Refund Exception: " . $e->getMessage());
    wp_send_json_error(['message' => 'Refund failed: ' . $e->getMessage()], 500);
}