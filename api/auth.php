<?php
// FILE: /jpos/api/auth.php

ini_set('display_errors', 0); // Disable error display for security
error_reporting(0);

require_once __DIR__ . '/../../wp-load.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_GET['action'] ?? null;

if ($action === 'login') {
    // Nonce check can be added here for extra security if desired
    $creds = [
        'user_login'    => sanitize_user($data['username'] ?? '', true),
        'user_password' => $data['password'] ?? '',
        'remember'      => true,
    ];

    $user = wp_signon($creds, is_ssl());

    if (is_wp_error($user)) {
        $response['message'] = 'Invalid username or password.';
    } else {
        if (user_can($user, 'manage_woocommerce')) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, true, is_ssl());

            $response = [
                'success' => true,
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user->ID,
                    'displayName' => $user->display_name
                ]
            ];
        } else {
            $response['message'] = 'You do not have permission to access the POS.';
        }
    }
} elseif ($action === 'logout') {
    wp_logout();
    $response = ['success' => true, 'message' => 'Logged out successfully.'];
} elseif ($action === 'check_status') {
    if (is_user_logged_in() && current_user_can('manage_woocommerce')) {
        $current_user = wp_get_current_user();
        $response = [
            'success' => true,
            'loggedIn' => true,
            'user' => [
                'id' => $current_user->ID,
                'displayName' => $current_user->display_name
            ]
        ];
    } else {
        $response = ['success' => true, 'loggedIn' => false];
    }
}

echo json_encode($response);