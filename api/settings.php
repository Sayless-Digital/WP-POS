<?php
// FILE: /jpos/api/settings.php

require_once __DIR__ . '/../../wp-load.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/error_handler.php';

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
        'virtual_keyboard_enabled' => true,
        'virtual_keyboard_auto_show' => false,
    ];
}

JPOS_Error_Handler::check_auth();

$request_method = $_SERVER['REQUEST_METHOD'];

if ($request_method === 'GET') {
    $saved_settings = get_option(JPOS_SETTINGS_OPTION_KEY);
    $settings = wp_parse_args($saved_settings ?: [], get_jpos_default_settings());
    JPOS_Error_Handler::send_success($settings);

} elseif ($request_method === 'POST') {
    $data = JPOS_Error_Handler::safe_json_decode(file_get_contents('php://input'));

    // CSRF Protection: Verify nonce for settings update requests
    $nonce = $data['nonce'] ?? '';
    JPOS_Error_Handler::check_nonce($nonce, 'jpos_settings_nonce');

    // Validate settings input
    $validated_data = JPOS_Validation::validate_input($data, [
        'name' => ['type' => 'text', 'required' => false, 'max_length' => 100],
        'email' => ['type' => 'email', 'required' => false, 'max_length' => 100],
        'phone' => ['type' => 'text', 'required' => false, 'max_length' => 20],
        'address' => ['type' => 'text', 'required' => false, 'max_length' => 200],
        'logo_url' => ['type' => 'url', 'required' => false],
        'footer_message_1' => ['type' => 'text', 'required' => false, 'max_length' => 200],
        'footer_message_2' => ['type' => 'text', 'required' => false, 'max_length' => 200]
    ]);

    $current_settings = get_option(JPOS_SETTINGS_OPTION_KEY, get_jpos_default_settings());
    $old_settings = $current_settings; // Save original for comparison

    // Update settings with validated data
    if (isset($validated_data['logo_url'])) $current_settings['logo_url'] = $validated_data['logo_url'];
    if (isset($validated_data['name'])) $current_settings['name'] = $validated_data['name'];
    if (isset($validated_data['email'])) $current_settings['email'] = $validated_data['email'];
    if (isset($validated_data['phone'])) $current_settings['phone'] = $validated_data['phone'];
    if (isset($validated_data['address'])) $current_settings['address'] = $validated_data['address'];
    if (isset($validated_data['footer_message_1'])) $current_settings['footer_message_1'] = $validated_data['footer_message_1'];
    if (isset($validated_data['footer_message_2'])) $current_settings['footer_message_2'] = $validated_data['footer_message_2'];
    
    // Handle virtual keyboard settings (boolean values don't need validation)
    if (isset($data['virtual_keyboard_enabled'])) {
        $current_settings['virtual_keyboard_enabled'] = (bool)$data['virtual_keyboard_enabled'];
    }
    if (isset($data['virtual_keyboard_auto_show'])) {
        $current_settings['virtual_keyboard_auto_show'] = (bool)$data['virtual_keyboard_auto_show'];
    }
    
    // Check if anything actually changed by comparing old and new settings
    $settings_changed = ($old_settings !== $current_settings);
    
    if ($settings_changed) {
        // Force update since we detected changes
        update_option(JPOS_SETTINGS_OPTION_KEY, $current_settings, false);
        JPOS_Error_Handler::send_success([], 'Settings saved successfully.');
    } else {
        JPOS_Error_Handler::send_success([], 'Settings are unchanged.');
    }

} else {
    JPOS_Error_Handler::send_error('Invalid request method.', 405);
}