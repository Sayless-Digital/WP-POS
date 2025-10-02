<?php
// FILE: /jpos/api/sessions.php

require_once __DIR__ . '/../../wp-load.php';
require_once __DIR__ . '/error_handler.php';

header('Content-Type: application/json');

// --- AUTHENTICATION AND AUTHORIZATION ---
JPOS_Error_Handler::check_auth();
// --- END AUTHENTICATION ---

global $wpdb;
$table_name = $wpdb->prefix . 'jpos_drawer_history';

// Fetch all closed sessions, most recent first.
$sessions_raw = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table_name WHERE status = %s ORDER BY time_closed DESC LIMIT 200",
        'closed'
    ),
    ARRAY_A // Get results as an associative array
);

if (is_null($sessions_raw)) {
    wp_send_json_error(['message' => 'Failed to query session history.'], 500);
    exit;
}

$sessions = [];
foreach ($sessions_raw as $session) {
    $time_opened = $session['time_opened'] ? (new DateTime($session['time_opened'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/New_York'))->format('M j, Y, g:i a') : '';
    $time_closed = $session['time_closed'] ? (new DateTime($session['time_closed'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/New_York'))->format('M j, Y, g:i a') : '';
    $sessions[] = [
        'id'               => (int) $session['id'],
        'user_name'        => $session['user_name'],
        'time_opened'      => $time_opened,
        'time_closed'      => $time_closed,
        'opening_amount'   => floatval($session['opening_amount']),
        'closing_amount'   => floatval($session['closing_amount']),
        'expected_amount'  => floatval($session['expected_amount']),
        'difference'       => floatval($session['difference']),
    ];
}

wp_send_json_success($sessions);