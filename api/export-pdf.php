<?php
// FILE: /jpos/api/export-pdf.php

require_once __DIR__ . '/../../wp-load.php';

// Check if TCPDF is available, if not, we'll use a fallback
if (!class_exists('TCPDF')) {
    // Try to include TCPDF if it exists in the WordPress environment
    $tcpdf_paths = [
        ABSPATH . 'wp-content/plugins/woocommerce/includes/libraries/tcpdf/tcpdf.php',
        ABSPATH . 'wp-content/plugins/woocommerce-pdf-invoices-packing-slips/vendor/tecnickcom/tcpdf/tcpdf.php',
        ABSPATH . 'wp-content/plugins/yith-woocommerce-pdf-invoice/lib/tcpdf/tcpdf.php'
    ];
    
    foreach ($tcpdf_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

header('Content-Type: application/json');

if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}

global $wpdb;

// Get the same data as the reports API
$valid_order_statuses = "('wc-completed', 'wc-processing')";
$jpos_meta_check = "EXISTS (
    SELECT 1 FROM {$wpdb->prefix}postmeta pm_jpos
    WHERE pm_jpos.post_id = p.ID AND pm_jpos.meta_key = '_created_via_jpos' AND pm_jpos.meta_value = '1'
)";

// Enhanced summary query with payment method breakdown
$summary_query = "
    SELECT
        SUM(pm.meta_value) as total_revenue,
        COUNT(p.ID) as total_orders,
        SUM(CASE WHEN pm_payment.meta_value = 'Cash' THEN pm.meta_value ELSE 0 END) as cash_revenue,
        COUNT(CASE WHEN pm_payment.meta_value = 'Cash' THEN 1 END) as cash_orders,
        SUM(CASE WHEN pm_payment.meta_value IN ('Card', 'Linx') THEN pm.meta_value ELSE 0 END) as card_revenue,
        COUNT(CASE WHEN pm_payment.meta_value IN ('Card', 'Linx') THEN 1 END) as card_orders
    FROM {$wpdb->prefix}posts p
    JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
    LEFT JOIN {$wpdb->prefix}postmeta pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = '_payment_method'
    WHERE p.post_type = 'shop_order'
    AND p.post_status IN {$valid_order_statuses}
    AND pm.meta_key = '_order_total'
    AND {$jpos_meta_check}";

$summary_results = $wpdb->get_row($summary_query);

$total_revenue = $summary_results->total_revenue ? floatval($summary_results->total_revenue) : 0;
$total_orders = $summary_results->total_orders ? intval($summary_results->total_orders) : 0;
$average_order_value = ($total_orders > 0) ? ($total_revenue / $total_orders) : 0;

$cash_revenue = $summary_results->cash_revenue ? floatval($summary_results->cash_revenue) : 0;
$cash_orders = $summary_results->cash_orders ? intval($summary_results->cash_orders) : 0;
$card_revenue = $summary_results->card_revenue ? floatval($summary_results->card_revenue) : 0;
$card_orders = $summary_results->card_orders ? intval($summary_results->card_orders) : 0;

// Enhanced daily query with payment method breakdown
$daily_query = $wpdb->prepare(
    "SELECT
        DATE(p.post_date) as order_date,
        SUM(pm.meta_value) as daily_revenue,
        COUNT(p.ID) as daily_orders,
        SUM(CASE WHEN pm_payment.meta_value = 'Cash' THEN pm.meta_value ELSE 0 END) as daily_cash_revenue,
        COUNT(CASE WHEN pm_payment.meta_value = 'Cash' THEN 1 END) as daily_cash_orders,
        SUM(CASE WHEN pm_payment.meta_value IN ('Card', 'Linx') THEN pm.meta_value ELSE 0 END) as daily_card_revenue,
        COUNT(CASE WHEN pm_payment.meta_value IN ('Card', 'Linx') THEN 1 END) as daily_card_orders
    FROM {$wpdb->prefix}posts p
    JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
    LEFT JOIN {$wpdb->prefix}postmeta pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = '_payment_method'
    WHERE p.post_type = 'shop_order'
    AND p.post_status IN {$valid_order_statuses}
    AND p.post_date >= %s
    AND pm.meta_key = '_order_total'
    AND {$jpos_meta_check}
    GROUP BY DATE(p.post_date)
    ORDER BY order_date ASC",
    date('Y-m-d', strtotime('-30 days'))
);

$daily_results = $wpdb->get_results($daily_query, ARRAY_A);

// Fill in missing days with 0 values for a complete chart
$report_data = [];
$period = new DatePeriod(
    new DateTime(date('Y-m-d', strtotime('-29 days'))),
    new DateInterval('P1D'),
    new DateTime(date('Y-m-d') . ' +1 day')
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

// Get store settings for the report header
$store_name = get_option('blogname', 'Store');
$store_email = get_option('admin_email', '');
$store_address = get_option('woocommerce_store_address', '');

if (class_exists('TCPDF')) {
    // Create PDF using TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('JPOS System');
    $pdf->SetAuthor($store_name);
    $pdf->SetTitle('Sales Report - ' . date('Y-m-d'));
    $pdf->SetSubject('Sales Report');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, $store_name, 'Sales Report - ' . date('F j, Y', strtotime('-30 days')) . ' to ' . date('F j, Y'));
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Summary Section
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Sales Summary', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Ln(5);
    
    // Summary table
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(60, 8, 'Metric', 1, 0, 'L', true);
    $pdf->Cell(40, 8, 'Total', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Cash', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Card/Linx', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 8, 'Revenue', 1, 0, 'L');
    $pdf->Cell(40, 8, '$' . number_format($total_revenue, 2), 1, 0, 'C');
    $pdf->Cell(40, 8, '$' . number_format($cash_revenue, 2), 1, 0, 'C');
    $pdf->Cell(40, 8, '$' . number_format($card_revenue, 2), 1, 1, 'C');
    
    $pdf->Cell(60, 8, 'Orders', 1, 0, 'L');
    $pdf->Cell(40, 8, $total_orders, 1, 0, 'C');
    $pdf->Cell(40, 8, $cash_orders, 1, 0, 'C');
    $pdf->Cell(40, 8, $card_orders, 1, 1, 'C');
    
    $pdf->Cell(60, 8, 'Average Order', 1, 0, 'L');
    $pdf->Cell(40, 8, '$' . number_format($average_order_value, 2), 1, 0, 'C');
    $pdf->Cell(40, 8, $cash_orders > 0 ? '$' . number_format($cash_revenue / $cash_orders, 2) : '$0.00', 1, 0, 'C');
    $pdf->Cell(40, 8, $card_orders > 0 ? '$' . number_format($card_revenue / $card_orders, 2) : '$0.00', 1, 1, 'C');
    
    $pdf->Ln(10);
    
    // Daily Data Section
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Daily Breakdown (Last 30 Days)', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Ln(5);
    
    // Daily data table
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(25, 8, 'Date', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Revenue', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Orders', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Cash Rev', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Cash Ord', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Card Rev', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Card Ord', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 8);
    foreach ($report_data as $day) {
        $date = new DateTime($day['order_date']);
        $pdf->Cell(25, 6, $date->format('M j'), 1, 0, 'C');
        $pdf->Cell(25, 6, '$' . number_format($day['daily_revenue'], 2), 1, 0, 'C');
        $pdf->Cell(20, 6, $day['daily_orders'], 1, 0, 'C');
        $pdf->Cell(25, 6, '$' . number_format($day['daily_cash_revenue'], 2), 1, 0, 'C');
        $pdf->Cell(20, 6, $day['daily_cash_orders'], 1, 0, 'C');
        $pdf->Cell(25, 6, '$' . number_format($day['daily_card_revenue'], 2), 1, 0, 'C');
        $pdf->Cell(20, 6, $day['daily_card_orders'], 1, 1, 'C');
    }
    
    // Footer
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'Report generated on ' . date('F j, Y \a\t g:i A') . ' by JPOS System', 0, 1, 'C');
    
    // Output PDF
    $pdf_content = $pdf->Output('sales_report_' . date('Y-m-d') . '.pdf', 'S');
    
    // Send as base64 encoded response
    wp_send_json_success([
        'pdf_data' => base64_encode($pdf_content),
        'filename' => 'sales_report_' . date('Y-m-d') . '.pdf'
    ]);
    
} else {
    // Fallback: Return data for client-side PDF generation
    wp_send_json_success([
        'fallback' => true,
        'data' => [
            'summary' => [
                'total_revenue' => $total_revenue,
                'total_orders' => $total_orders,
                'average_order_value' => $average_order_value,
                'cash_revenue' => $cash_revenue,
                'cash_orders' => $cash_orders,
                'card_revenue' => $card_revenue,
                'card_orders' => $card_orders,
            ],
            'daily_data' => $report_data,
            'store_name' => $store_name,
            'report_date' => date('F j, Y')
        ]
    ]);
} 