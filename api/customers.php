<?php
// FILE: /jpos/api/customers.php

require_once __DIR__ . '/../../wp-load.php';
require_once __DIR__ . '/performance-monitor.php';

header('Content-Type: application/json');

// Authentication check
if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}

// CSRF protection
if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'jpos_customer_search_nonce')) {
    wp_send_json_error(['message' => 'Invalid security token.'], 403);
    exit;
}

// Start performance monitoring
JPOS_Performance_Monitor::start_monitoring();

try {
    // Get search query parameter
    $search_query = isset($_GET['query']) ? sanitize_text_field($_GET['query']) : '';
    
    // Require at least 2 characters for search
    if (strlen($search_query) < 2) {
        wp_send_json_success([
            'customers' => [],
            'count' => 0,
            'message' => 'Please enter at least 2 characters to search'
        ]);
        exit;
    }
    
    // Limit results
    $limit = min(20, max(5, intval($_GET['limit'] ?? 10)));
    
    // Search WordPress users by display name or email
    $user_args = array(
        'search' => '*' . $search_query . '*',
        'search_columns' => array('user_login', 'user_email', 'display_name'),
        'number' => $limit,
        'orderby' => 'display_name',
        'order' => 'ASC'
    );
    
    $user_query = new WP_User_Query($user_args);
    $users = $user_query->get_results();
    
    // Format results
    $customers = array();
    foreach ($users as $user) {
        $customers[] = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'username' => $user->user_login
        );
    }
    
    // End performance monitoring
    $performance_stats = JPOS_Performance_Monitor::end_monitoring();
    JPOS_Performance_Monitor::log_performance('search_customers', $performance_stats);
    
    wp_send_json_success([
        'customers' => $customers,
        'count' => count($customers),
        'query' => $search_query,
        'performance' => $performance_stats
    ]);
    
} catch (Exception $e) {
    wp_send_json_error(['message' => 'Error searching customers: ' . $e->getMessage()], 500);
}