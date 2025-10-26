<?php
// FILE: /jpos/api/get-refund-receipt.php
// Fetches refund receipt data for a specific order

require_once __DIR__ . '/../../wp-load.php';
require_once __DIR__ . '/error_handler.php';

header('Content-Type: application/json');

JPOS_Error_Handler::check_auth();

$order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;

if (!$order_id) {
    wp_send_json_error(['message' => 'Order ID required'], 400);
    exit;
}

$original_order = wc_get_order($order_id);
if (!$original_order) {
    wp_send_json_error(['message' => 'Order not found'], 404);
    exit;
}

// Get refunds for this order
$refunds = $original_order->get_refunds();

if (empty($refunds)) {
    wp_send_json_error(['message' => 'No refunds found for this order'], 404);
    exit;
}

// Use the most recent refund
$refund = $refunds[0];

// Get returned items from refund
$returned_items = [];
foreach ($refund->get_items() as $item) {
    $product = $item->get_product();
    $returned_items[] = [
        'name' => $item->get_name(),
        'sku' => $product ? $product->get_sku() : '',
        'quantity' => -abs($item->get_quantity()), // Negative for display
        'price' => abs(floatval($item->get_total()) / abs($item->get_quantity())),
        'total' => -abs(floatval($item->get_total())) // Negative total
    ];
}

// Check if this was an exchange by looking at order notes
$exchange_order_number = null;
$exchange_total = 0;
$new_items = [];

$notes = $original_order->get_customer_order_notes();
foreach ($notes as $note) {
    if (preg_match('/Exchanged for new Order #(\d+)/', $note->comment_content, $matches)) {
        $exchange_order_number = $matches[1];
        // Try to get the exchange order
        $exchange_order_id = wc_get_order_id_by_order_key($exchange_order_number);
        if (!$exchange_order_id) {
            // Try searching by order number
            global $wpdb;
            $exchange_order_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND ID = %d",
                $exchange_order_number
            ));
        }
        
        if ($exchange_order_id) {
            $exchange_order = wc_get_order($exchange_order_id);
            if ($exchange_order) {
                $exchange_total = floatval($exchange_order->get_total());
                
                // Get new items from exchange order
                foreach ($exchange_order->get_items() as $item) {
                    $product = $item->get_product();
                    $new_items[] = [
                        'name' => $item->get_name(),
                        'sku' => $product ? $product->get_sku() : '',
                        'quantity' => $item->get_quantity(),
                        'price' => floatval($item->get_total()) / $item->get_quantity(),
                        'total' => floatval($item->get_total())
                    ];
                }
            }
        }
        break;
    }
}

$refund_amount = abs(floatval($refund->get_amount()));
$transaction_type = $exchange_order_number ? 'EXCHANGE' : 'REFUND';

// Build receipt data
$receipt_data = [
    'transaction_type' => $transaction_type,
    'refund_id' => $refund->get_id(),
    'original_order_number' => $original_order->get_order_number(),
    'date_created' => $refund->get_date_created()->date('Y-m-d H:i:s'),
    'customer_name' => $original_order->get_formatted_billing_full_name() ?: 'Guest',
    'payment_method' => $original_order->get_payment_method_title(),
    'returned_items' => $returned_items,
    'refund_amount' => $refund_amount,
    'new_items' => $new_items,
    'exchange_order_number' => $exchange_order_number,
    'exchange_total' => $exchange_total,
    'net_amount' => $exchange_total - $refund_amount
];

wp_send_json_success($receipt_data);