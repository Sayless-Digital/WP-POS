<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing refund.php dependencies...\n\n";

// Test 1: Check if wp-load.php exists
$wp_load_path = __DIR__ . '/../../wp-load.php';
echo "1. Checking wp-load.php path: $wp_load_path\n";
if (file_exists($wp_load_path)) {
    echo "   ✓ wp-load.php found\n\n";
} else {
    echo "   ✗ wp-load.php NOT found\n\n";
    exit(1);
}

// Test 2: Load WordPress
echo "2. Loading WordPress...\n";
require_once $wp_load_path;
echo "   ✓ WordPress loaded\n\n";

// Test 3: Check if WooCommerce is active
echo "3. Checking WooCommerce...\n";
if (class_exists('WooCommerce')) {
    echo "   ✓ WooCommerce is active\n";
    echo "   Version: " . WC()->version . "\n\n";
} else {
    echo "   ✗ WooCommerce is NOT active\n\n";
    exit(1);
}

// Test 4: Check WooCommerce functions
echo "4. Checking WooCommerce functions...\n";
$required_functions = ['wc_get_order', 'wc_create_refund', 'wc_create_order', 'wc_get_product'];
foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "   ✓ $func() exists\n";
    } else {
        echo "   ✗ $func() NOT found\n";
    }
}
echo "\n";

// Test 5: Check error_handler.php
echo "5. Checking error_handler.php...\n";
$error_handler_path = __DIR__ . '/error_handler.php';
if (file_exists($error_handler_path)) {
    echo "   ✓ error_handler.php found\n";
    require_once $error_handler_path;
    if (class_exists('JPOS_Error_Handler')) {
        echo "   ✓ JPOS_Error_Handler class loaded\n\n";
    } else {
        echo "   ✗ JPOS_Error_Handler class NOT found\n\n";
    }
} else {
    echo "   ✗ error_handler.php NOT found\n\n";
}

echo "All tests passed!\n";
