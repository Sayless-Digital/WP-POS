<?php
// FILE: /jpos/api/reports-optimized.php
// Optimized reports endpoint with improved database performance

require_once __DIR__ . '/../../wp-load.php';
require_once __DIR__ . '/database-optimizer.php';

header('Content-Type: application/json');

if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}

try {
    // Use optimized database queries
    $filters = [
        'date_from' => date('Y-m-d 00:00:00', strtotime('-29 days')),
        'date_to' => date('Y-m-d 23:59:59')
    ];

    $report_data = JPOS_Database_Optimizer::get_reports_optimized($filters);
    $totals = $report_data['totals'];
    $daily_results = $report_data['daily_data'];

    // Fill in missing days with 0 values for a complete chart
    $complete_daily_data = [];
    $period = new DatePeriod(
        new DateTime(date('Y-m-d', strtotime('-29 days'))), // Period starts 29 days ago
        new DateInterval('P1D'),
        new DateTime(date('Y-m-d') . ' +1 day') // Period ends today
    );
    
    $dates = array_column($daily_results, 'order_date');

    foreach ($period as $date) {
        $date->setTimezone(new DateTimeZone('America/New_York'));
        $date_str = $date->format('Y-m-d');
        
        $day_data = null;
        foreach ($daily_results as $result) {
            if ($result['order_date'] === $date_str) {
                $day_data = $result;
                break;
            }
        }
        
        if ($day_data) {
            $complete_daily_data[] = [
                'date' => $date->format('M j'),
                'revenue' => floatval($day_data['daily_revenue']),
                'orders' => intval($day_data['daily_orders']),
                'cash_revenue' => floatval($day_data['daily_cash_revenue']),
                'cash_orders' => intval($day_data['daily_cash_orders']),
                'card_revenue' => floatval($day_data['daily_card_revenue']),
                'card_orders' => intval($day_data['daily_card_orders']),
                'pos_revenue' => floatval($day_data['daily_pos_revenue']),
                'pos_orders' => intval($day_data['daily_pos_orders']),
                'online_revenue' => floatval($day_data['daily_online_revenue']),
                'online_orders' => intval($day_data['daily_online_orders'])
            ];
        } else {
            $complete_daily_data[] = [
                'date' => $date->format('M j'),
                'revenue' => 0,
                'orders' => 0,
                'cash_revenue' => 0,
                'cash_orders' => 0,
                'card_revenue' => 0,
                'card_orders' => 0,
                'pos_revenue' => 0,
                'pos_orders' => 0,
                'online_revenue' => 0,
                'online_orders' => 0
            ];
        }
    }

    // Prepare response data
    $response_data = [
        'summary' => [
            'total_revenue' => $totals['total_revenue'],
            'total_orders' => $totals['total_orders'],
            'average_order_value' => round($totals['average_order_value'], 2),
            'cash_revenue' => $totals['cash_revenue'],
            'cash_orders' => $totals['cash_orders'],
            'card_revenue' => $totals['card_revenue'],
            'card_orders' => $totals['card_orders'],
            'pos_revenue' => $totals['pos_revenue'],
            'pos_orders' => $totals['pos_orders'],
            'online_revenue' => $totals['online_revenue'],
            'online_orders' => $totals['online_orders']
        ],
        'daily_data' => $complete_daily_data,
        'charts' => [
            'revenue_chart' => [
                'labels' => array_column($complete_daily_data, 'date'),
                'datasets' => [
                    [
                        'label' => 'Total Revenue',
                        'data' => array_column($complete_daily_data, 'revenue'),
                        'borderColor' => 'rgb(34, 197, 94)',
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'tension' => 0.1
                    ]
                ]
            ],
            'orders_chart' => [
                'labels' => array_column($complete_daily_data, 'date'),
                'datasets' => [
                    [
                        'label' => 'Total Orders',
                        'data' => array_column($complete_daily_data, 'orders'),
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'tension' => 0.1
                    ]
                ]
            ],
            'payment_methods_chart' => [
                'labels' => ['Cash', 'Card', 'Other'],
                'datasets' => [
                    [
                        'label' => 'Revenue by Payment Method',
                        'data' => [
                            $totals['cash_revenue'],
                            $totals['card_revenue'],
                            $totals['total_revenue'] - $totals['cash_revenue'] - $totals['card_revenue']
                        ],
                        'backgroundColor' => [
                            'rgb(34, 197, 94)',
                            'rgb(59, 130, 246)',
                            'rgb(156, 163, 175)'
                        ]
                    ]
                ]
            ],
            'pos_vs_online_chart' => [
                'labels' => ['POS Orders', 'Online Orders'],
                'datasets' => [
                    [
                        'label' => 'Revenue by Source',
                        'data' => [
                            $totals['pos_revenue'],
                            $totals['online_revenue']
                        ],
                        'backgroundColor' => [
                            'rgb(168, 85, 247)',
                            'rgb(249, 115, 22)'
                        ]
                    ]
                ]
            ]
        ]
    ];

    wp_send_json_success($response_data);

} catch (Exception $e) {
    error_log('JPOS Reports Error: ' . $e->getMessage());
    wp_send_json_error(['message' => 'Failed to generate reports. Please try again.'], 500);
}
