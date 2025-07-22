<?php
// FILE: /jpos/api/settings.php

require_once __DIR__ . '/../../wp-load.php';

header('Content-Type: application/json');

define('JPOS_SETTINGS_OPTION_KEY', 'jpos_receipt_settings');

function get_jpos_default_settings() {
    return [
        'logo_url'         => 'https://www.jonesytt.com/wp-content/uploads/2021/07/cropped-jonesy-logo-small.png',
        'name'             => 'Jonesy',
        'email'            => 'Jonesy.tt.ss@gmail.com',
        'phone'            => '18682257656',
        'address'          => '54 Henry Street, Port of Spain-00000',
        'footer_message_1' => 'Thank You For Your Purchase!',
        'footer_message_2' => 'No Exchanges After 7 Days Or Without Original Packaging.',
    ];
}

if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}

$request_method = $_SERVER['REQUEST_METHOD'];

if ($request_method === 'GET') {
    $saved_settings = get_option(JPOS_SETTINGS_OPTION_KEY);
    $settings = wp_parse_args($saved_settings ?: [], get_jpos_default_settings());
    wp_send_json_success($settings);

} elseif ($request_method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data)) {
        wp_send_json_error(['message' => 'No data provided.'], 400);
        exit;
    }

    $current_settings = get_option(JPOS_SETTINGS_OPTION_KEY, get_jpos_default_settings());

    if (isset($data['logo_url'])) $current_settings['logo_url'] = esc_url_raw($data['logo_url']);
    if (isset($data['name'])) $current_settings['name'] = sanitize_text_field($data['name']);
    if (isset($data['email'])) $current_settings['email'] = sanitize_email($data['email']);
    if (isset($data['phone'])) $current_settings['phone'] = sanitize_text_field($data['phone']);
    if (isset($data['address'])) $current_settings['address'] = sanitize_text_field($data['address']);
    if (isset($data['footer_message_1'])) $current_settings['footer_message_1'] = sanitize_text_field($data['footer_message_1']);
    if (isset($data['footer_message_2'])) $current_settings['footer_message_2'] = sanitize_text_field($data['footer_message_2']);
    
    $result = update_option(JPOS_SETTINGS_OPTION_KEY, $current_settings);

    if ($result) {
        wp_send_json_success(['message' => 'Settings saved successfully.']);
    } else {
        wp_send_json_success(['message' => 'Settings are unchanged.']);
    }

} else {
    wp_send_json_error(['message' => 'Invalid request method.'], 405);
}