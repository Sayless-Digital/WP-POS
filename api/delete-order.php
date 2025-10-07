<?php
// FILE: /jpos/api/delete-order.php
// Dedicated endpoint for order deletion

require_once __DIR__ . '/../../wp-load.php';

header('Content-Type: application/json');

// Authentication
if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wp_send_json_error(['message' => 'Only POST method allowed'], 405);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['order_id'])) {
    wp_send_json_error(['message' => 'Missing order_id parameter'], 400);
    exit;
}

$order_id = intval($input['order_id']);
$restore_stock = isset($input['restore_stock']) ? filter_var($input['restore_stock'], FILTER_VALIDATE_BOOLEAN) : false;

// Get order
$order = wc_get_order($order_id);
if (!$order) {
    wp_send_json_error(['message' => 'Order not found'], 404);
    exit;
}

// Restore stock if requested
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
$deleted = wp_delete_post($order_id, true);

if ($deleted) {
    wp_send_json_success([
        'message' => $restore_stock ? 'Order deleted and stock restored' : 'Order deleted without stock restoration'
    ]);
} else {
    wp_send_json_error(['message' => 'Failed to delete order'], 500);
}