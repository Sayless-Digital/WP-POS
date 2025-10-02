<?php
// FILE: /jpos/api/reports.php

require_once __DIR__ . '/../../wp-load.php';
require_once __DIR__ . '/database-optimizer.php';

header('Content-Type: application/json');

if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}

global $wpdb;

$valid_order_statuses = "('wc-completed', 'wc-processing')";
$jpos_meta_check = "EXISTS (
    SELECT 1 FROM {$wpdb->prefix}postmeta pm_jpos
    WHERE pm_jpos.post_id = p.ID AND pm_jpos.meta_key = '_created_via_jpos' AND pm_jpos.meta_value = '1'
)";

// Only consider orders from the last 30 days
$thirty_days_ago = date('Y-m-d 00:00:00', strtotime('-29 days'));

// Best practice: classify payment methods (using prepared statement placeholders)
$cash_methods = [ 'Cash', 'cash', 'cod' ];
$card_methods = [ 'Card', 'card', 'credit_card', 'debit', 'linx', 'Linx' ];
$cash_placeholders = implode(',', array_fill(0, count($cash_methods), '%s'));
$card_placeholders = implode(',', array_fill(0, count($card_methods), '%s'));

$summary_query = $wpdb->prepare("
    SELECT
        SUM(pm.meta_value) as total_revenue,
        COUNT(p.ID) as total_orders,
        SUM(CASE WHEN pm_payment.meta_value IN ($cash_placeholders) THEN pm.meta_value ELSE 0 END) as cash_revenue,
        COUNT(CASE WHEN pm_payment.meta_value IN ($cash_placeholders) THEN 1 END) as cash_orders,
        SUM(CASE WHEN pm_payment.meta_value IN ($card_placeholders) THEN pm.meta_value ELSE 0 END) as card_revenue,
        COUNT(CASE WHEN pm_payment.meta_value IN ($card_placeholders) THEN 1 END) as card_orders,
        SUM(CASE WHEN (pm_payment.meta_value NOT IN ($cash_placeholders) AND pm_payment.meta_value NOT IN ($card_placeholders) OR pm_payment.meta_value IS NULL) THEN pm.meta_value ELSE 0 END) as other_revenue,
        COUNT(CASE WHEN (pm_payment.meta_value NOT IN ($cash_placeholders) AND pm_payment.meta_value NOT IN ($card_placeholders) OR pm_payment.meta_value IS NULL) THEN 1 END) as other_orders
    FROM {$wpdb->prefix}posts p
    JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
    LEFT JOIN {$wpdb->prefix}postmeta pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = '_payment_method'
    WHERE p.post_type = 'shop_order'
    AND p.post_status IN {$valid_order_statuses}
    AND pm.meta_key = '_order_total'
    AND p.post_date >= %s
", array_merge($cash_methods, $card_methods, $cash_methods, $card_methods, $cash_methods, $card_methods, $cash_methods, $card_methods, [$thirty_days_ago]));

$summary_results = $wpdb->get_row($summary_query);

$total_revenue = $summary_results->total_revenue ? floatval($summary_results->total_revenue) : 0;
$total_orders = $summary_results->total_orders ? intval($summary_results->total_orders) : 0;
$average_order_value = ($total_orders > 0) ? ($total_revenue / $total_orders) : 0;

$cash_revenue = $summary_results->cash_revenue ? floatval($summary_results->cash_revenue) : 0;
$cash_orders = $summary_results->cash_orders ? intval($summary_results->cash_orders) : 0;
$card_revenue = $summary_results->card_revenue ? floatval($summary_results->card_revenue) : 0;
$card_orders = $summary_results->card_orders ? intval($summary_results->card_orders) : 0;
$other_revenue = $summary_results->other_revenue ? floatval($summary_results->other_revenue) : 0;
$other_orders = $summary_results->other_orders ? intval($summary_results->other_orders) : 0;

// Calculate POS and Online revenue/orders
$pos_query = "
    SELECT
        SUM(pm.meta_value) as pos_revenue,
        COUNT(p.ID) as pos_orders
    FROM {$wpdb->prefix}posts p
    JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
    WHERE p.post_type = 'shop_order'
    AND p.post_status IN {$valid_order_statuses}
    AND pm.meta_key = '_order_total'
    AND EXISTS (
        SELECT 1 FROM {$wpdb->prefix}postmeta pm2
        WHERE pm2.post_id = p.ID AND pm2.meta_key = '_created_via_jpos' AND pm2.meta_value = '1'
    )";
$pos_results = $wpdb->get_row($pos_query);
$pos_revenue = $pos_results->pos_revenue ? floatval($pos_results->pos_revenue) : 0;
$pos_orders = $pos_results->pos_orders ? intval($pos_results->pos_orders) : 0;

$online_query = "
    SELECT
        SUM(pm.meta_value) as online_revenue,
        COUNT(p.ID) as online_orders
    FROM {$wpdb->prefix}posts p
    JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
    WHERE p.post_type = 'shop_order'
    AND p.post_status IN {$valid_order_statuses}
    AND pm.meta_key = '_order_total'
    AND NOT EXISTS (
        SELECT 1 FROM {$wpdb->prefix}postmeta pm2
        WHERE pm2.post_id = p.ID AND pm2.meta_key = '_created_via_jpos' AND pm2.meta_value = '1'
    )";
$online_results = $wpdb->get_row($online_query);
$online_revenue = $online_results->online_revenue ? floatval($online_results->online_revenue) : 0;
$online_orders = $online_results->online_orders ? intval($online_results->online_orders) : 0;

// Enhanced daily query with payment method breakdown
$daily_query = $wpdb->prepare(
    "SELECT
        DATE(p.post_date) as order_date,
        SUM(pm.meta_value) as daily_revenue,
        COUNT(p.ID) as daily_orders,
        SUM(CASE WHEN pm_payment.meta_value IN ($cash_methods_sql) THEN pm.meta_value ELSE 0 END) as daily_cash_revenue,
        COUNT(CASE WHEN pm_payment.meta_value IN ($cash_methods_sql) THEN 1 END) as daily_cash_orders,
        SUM(CASE WHEN pm_payment.meta_value IN ($card_methods_sql) THEN pm.meta_value ELSE 0 END) as daily_card_revenue,
        COUNT(CASE WHEN pm_payment.meta_value IN ($card_methods_sql) THEN 1 END) as daily_card_orders,
        SUM(CASE WHEN (pm_payment.meta_value NOT IN ($cash_methods_sql, $card_methods_sql) OR pm_payment.meta_value IS NULL) THEN pm.meta_value ELSE 0 END) as daily_other_revenue,
        COUNT(CASE WHEN (pm_payment.meta_value NOT IN ($cash_methods_sql, $card_methods_sql) OR pm_payment.meta_value IS NULL) THEN 1 END) as daily_other_orders
    FROM {$wpdb->prefix}posts p
    JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
    LEFT JOIN {$wpdb->prefix}postmeta pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = '_payment_method'
    WHERE p.post_type = 'shop_order'
    AND p.post_status IN {$valid_order_statuses}
    AND p.post_date >= %s
    AND pm.meta_key = '_order_total'
    GROUP BY DATE(p.post_date)
    ORDER BY order_date ASC",
    $thirty_days_ago
);

$daily_results = $wpdb->get_results($daily_query, ARRAY_A);

// Fill in missing days with 0 values for a complete chart
$report_data = [];
$period = new DatePeriod(
    new DateTime(date('Y-m-d', strtotime('-29 days'))), // Period starts 29 days ago
    new DateInterval('P1D'),
    new DateTime(date('Y-m-d') . ' +1 day') // Period ends today
);
$dates = array_column($daily_results, 'order_date');

foreach ($period as $date) {
    $date->setTimezone(new DateTimeZone('America/New_York'));
    $date_string = $date->format('Y-m-d');
    $key = array_search($date_string, $dates);

    if ($key !== false) {
        $report_data[] = [
            'order_date' => $date_string,
            'daily_revenue' => floatval($daily_results[$key]['daily_revenue']),
            'daily_orders' => intval($daily_results[$key]['daily_orders']),
            'daily_cash_revenue' => floatval($daily_results[$key]['daily_cash_revenue']),
            'daily_cash_orders' => intval($daily_results[$key]['daily_cash_orders']),
            'daily_card_revenue' => floatval($daily_results[$key]['daily_card_revenue']),
            'daily_card_orders' => intval($daily_results[$key]['daily_card_orders']),
        ];
    } else {
        $report_data[] = [
            'order_date' => $date_string,
            'daily_revenue' => 0,
            'daily_orders' => 0,
            'daily_cash_revenue' => 0,
            'daily_cash_orders' => 0,
            'daily_card_revenue' => 0,
            'daily_card_orders' => 0,
        ];
    }
}

$response_data = [
    'summary' => [
        'total_revenue' => $total_revenue,
        'total_orders' => $total_orders,
        'average_order_value' => $average_order_value,
        'cash_revenue' => $cash_revenue,
        'cash_orders' => $cash_orders,
        'card_revenue' => $card_revenue,
        'card_orders' => $card_orders,
        'other_revenue' => $other_revenue,
        'other_orders' => $other_orders,
        'pos_revenue' => $pos_revenue,
        'pos_orders' => $pos_orders,
        'online_revenue' => $online_revenue,
        'online_orders' => $online_orders,
    ],
    'daily_data' => $report_data
];

wp_send_json_success($response_data);