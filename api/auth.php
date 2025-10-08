<?php
// FILE: /jpos/api/auth.php

ini_set('display_errors', 0); // Disable error display for security
error_reporting(0);

require_once __DIR__ . '/../../wp-load.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/wp-rbac-helper.php';

header('Content-Type: application/json');

// Handle different request methods
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = JPOS_Error_Handler::safe_json_decode(file_get_contents('php://input'));
} else {
    $data = $_GET;
}

$action = $data['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    JPOS_Error_Handler::send_error('Invalid request. Action parameter required.', 400);
}

if ($action === 'login') {
    // CSRF Protection: Verify nonce for login requests
    $nonce = $data['nonce'] ?? $_GET['nonce'] ?? '';
    JPOS_Error_Handler::check_nonce($nonce, 'wppos_login_nonce');
    
    // Validate login input
    $validated_data = JPOS_Validation::validate_input($data, [
        'username' => ['type' => 'text', 'required' => true, 'min_length' => 3, 'max_length' => 60],
        'password' => ['type' => 'text', 'required' => true, 'min_length' => 1]
    ]);
    
    $creds = [
        'user_login'    => sanitize_user($validated_data['username'], true),
        'user_password' => $validated_data['password'],
        'remember'      => true,
    ];

    $user = wp_signon($creds, is_ssl());

    if (is_wp_error($user)) {
        JPOS_Error_Handler::send_error('Invalid username or password.', 401);
    } else {
        if (user_can($user, 'manage_woocommerce') || wppos_has_pos_role($user->ID)) {
// REDUNDANT: wp_signon handles this -             wp_set_current_user($user->ID);
// REDUNDANT: wp_signon handles this -             wp_set_auth_cookie($user->ID, true, is_ssl());
            

            // Force the email to be included
            $user_email = $user->user_email ?: 'admin@saylesstt.com';
            
            // Get user capabilities and roles
            $capabilities = wppos_get_user_capabilities($user->ID);
            $roles = wppos_get_user_roles($user->ID);
            
            // Return data directly without JPOS_Error_Handler wrapper
            wp_send_json([
                'success' => true,
                'user' => [
                    'id' => $user->ID,
                    'displayName' => $user->display_name,
                    'email' => $user_email,
                    'capabilities' => $capabilities,
                    'roles' => $roles
                ]
            ]);
        } else {
            JPOS_Error_Handler::send_error('You do not have permission to access the POS.', 403);
        }
    }
} elseif ($action === 'logout') {
    // CSRF Protection: Verify nonce for logout requests
    $nonce = $data['nonce'] ?? $_GET['nonce'] ?? '';
    JPOS_Error_Handler::check_nonce($nonce, 'wppos_logout_nonce');
    
    wp_logout();
    wp_send_json(['success' => true, 'message' => 'Logged out successfully.']);
} elseif ($action === 'check_status') {
    if (is_user_logged_in() && (current_user_can('manage_woocommerce') || wppos_has_pos_role())) {
        $current_user = wp_get_current_user();
        
        // Get user capabilities and roles
        $capabilities = wppos_get_user_capabilities($current_user->ID);
        $roles = wppos_get_user_roles($current_user->ID);
        
        // Debug logging
        error_log('WP POS Auth - User ID: ' . $current_user->ID);
        error_log('WP POS Auth - Roles from function: ' . print_r($roles, true));
        error_log('WP POS Auth - Capabilities from function: ' . print_r($capabilities, true));
        
        $user_data = [
            'id' => $current_user->ID,
            'displayName' => $current_user->display_name,
            'email' => $current_user->user_email ?: 'admin@saylesstt.com',
            'capabilities' => $capabilities,
            'roles' => $roles
        ];
        
        error_log('WP POS Auth - Final user_data: ' . print_r($user_data, true));
        
        // Return data directly without JPOS_Error_Handler wrapper
        wp_send_json([
            'success' => true,
            'loggedIn' => true,
            'user' => $user_data
        ]);
    } else {
        wp_send_json(['success' => true, 'loggedIn' => false]);
    }
} else {
    JPOS_Error_Handler::send_error('Invalid action specified.', 400);
}