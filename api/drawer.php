<?php
// FILE: /jpos/api/drawer.php

require_once __DIR__ . '/../../wp-load.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/error_handler.php';

header('Content-Type: application/json');
global $wpdb;

// --- CRITICAL SECURITY: AUTHENTICATION AND AUTHORIZATION ---
if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}

// Ensure our custom table exists
$table_name = $wpdb->prefix . 'jpos_drawer_history';
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time_opened datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        time_closed datetime DEFAULT NULL,
        user_id bigint(20) NOT NULL,
        user_name varchar(255) NOT NULL,
        opening_amount decimal(10,2) NOT NULL,
        closing_amount decimal(10,2) DEFAULT NULL,
        expected_amount decimal(10,2) DEFAULT NULL,
        difference decimal(10,2) DEFAULT NULL,
        status varchar(20) DEFAULT 'open' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

$response = ['success' => false, 'message' => 'Invalid request.'];
$data = JPOS_Validation::validate_json_input(file_get_contents('php://input'));
$action = $data['action'] ?? $_GET['action'] ?? null;

// CSRF Protection: Verify nonce for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['open', 'close'])) {
    $nonce = $data['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'jpos_drawer_nonce')) {
        wp_send_json_error(['message' => 'Security token invalid. Please refresh the page and try again.'], 403);
        exit;
    }
}

if ($action === 'open') {
    // Validate opening amount
    $validated_data = JPOS_Validation::validate_input($data, [
        'openingAmount' => ['type' => 'float', 'required' => true, 'min' => 0]
    ]);
    
    $user_id = get_current_user_id();
    $user_info = get_userdata($user_id);
    $user_name = $user_info ? $user_info->display_name : 'Unknown';
    $opening_amount = $validated_data['openingAmount'];

    $open_drawer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d AND status = 'open'", $user_id));

    if ($open_drawer) {
        $response['message'] = 'You already have an open drawer session.';
    } else {
        $wpdb->insert($table_name, [
            'time_opened' => current_time('mysql', 1), // Use GMT time
            'user_id' => $user_id,
            'user_name' => $user_name,
            'opening_amount' => $opening_amount,
            'status' => 'open'
        ]);
        $response = ['success' => true, 'message' => 'Drawer opened successfully.'];
    }
} elseif ($action === 'close') {
    $user_id = get_current_user_id();
    $closing_amount = floatval($data['closingAmount']);
    
    $open_drawer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d AND status = 'open'", $user_id));

    if (!$open_drawer) {
        $response['message'] = 'No open drawer found to close.';
    } else {
        // Corrected Query: Calculate cash sales using 'cod' payment method ID.
        $cash_sales = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(pm_total.meta_value)
             FROM {$wpdb->prefix}posts p
             INNER JOIN {$wpdb->prefix}postmeta pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
             INNER JOIN {$wpdb->prefix}postmeta pm_method ON p.ID = pm_method.post_id AND pm_method.meta_key = '_payment_method'
             WHERE p.post_type = 'shop_order'
             AND p.post_status IN ('wc-completed', 'wc-processing')
             AND p.post_date_gmt >= %s
             AND pm_method.meta_value = 'cod'",
            $open_drawer->time_opened
        ));
        
        $opening_amount_float = floatval($open_drawer->opening_amount);
        $expected_amount = $opening_amount_float + $cash_sales;
        $difference = $closing_amount - $expected_amount;

        $wpdb->update($table_name, [
            'time_closed' => current_time('mysql', 1), // Use GMT time
            'closing_amount' => $closing_amount,
            'expected_amount' => $expected_amount,
            'difference' => $difference,
            'status' => 'closed'
        ], ['id' => $open_drawer->id]);

        $response = [
            'success' => true,
            'message' => 'Drawer closed successfully.',
            'data' => [
                'opening_amount' => $opening_amount_float,
                'cash_sales' => $cash_sales,
                'expected_amount' => $expected_amount,
                'closing_amount' => $closing_amount,
                'difference' => $difference
            ]
        ];
    }
} elseif ($action === 'get_status') {
    $user_id = get_current_user_id();
    $open_drawer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d AND status = 'open'", $user_id));
    
    if ($open_drawer) {
        $open_drawer->opening_amount = floatval($open_drawer->opening_amount);
    }
    
    $response = ['success' => true, 'isOpen' => !is_null($open_drawer), 'drawer' => $open_drawer];
}

echo json_encode($response);