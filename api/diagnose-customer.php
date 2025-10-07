<?php
// Diagnostic tool for customer assignment debugging
require_once __DIR__ . '/../../wp-load.php';

header('Content-Type: application/json');

if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    wp_send_json_error(['message' => 'Order ID required'], 400);
    exit;
}

global $wpdb;

// Get direct database values
$post_author = $wpdb->get_var($wpdb->prepare(
    "SELECT post_author FROM {$wpdb->posts} WHERE ID = %d",
    $order_id
));

$customer_user_meta = get_post_meta($order_id, '_customer_user', true);
$jpos_customer_id = get_post_meta($order_id, '_jpos_customer_id', true);
$jpos_customer_name = get_post_meta($order_id, '_jpos_customer_name', true);
$jpos_created_by = get_post_meta($order_id, '_jpos_created_by', true);
$jpos_created_by_name = get_post_meta($order_id, '_jpos_created_by_name', true);

// Get WooCommerce object value
$order = wc_get_order($order_id);
$wc_customer_id = $order ? $order->get_customer_id() : null;
$wc_user_id = $order ? $order->get_user_id() : null;

// Check for scheduled actions that might modify order
$pending_actions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}actionscheduler_actions 
     WHERE hook LIKE %s AND args LIKE %s AND status = 'pending'",
    '%order%',
    '%' . $order_id . '%'
));

wp_send_json_success([
    'order_id' => $order_id,
    'database' => [
        'wp_posts.post_author' => $post_author,
        '_customer_user_meta' => $customer_user_meta,
    ],
    'woocommerce_object' => [
        'get_customer_id()' => $wc_customer_id,
        'get_user_id()' => $wc_user_id,
    ],
    'jpos_metadata' => [
        '_jpos_customer_id' => $jpos_customer_id,
        '_jpos_customer_name' => $jpos_customer_name,
        '_jpos_created_by' => $jpos_created_by,
        '_jpos_created_by_name' => $jpos_created_by_name,
    ],
    'pending_actions' => count($pending_actions),
    'current_user' => get_current_user_id(),
]);