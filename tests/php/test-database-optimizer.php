<?php
// FILE: /jpos/tests/php/test-database-optimizer.php
// Tests for database optimizer functionality

require_once __DIR__ . '/test-runner.php';
require_once __DIR__ . '/../../api/database-optimizer.php';
require_once __DIR__ . '/../../wp-load.php';

// Test database optimizer functionality
JPOS_Test_Runner::add_test('Database Optimizer - Get Products', function() {
    $products = JPOS_Database_Optimizer::get_products_optimized(['limit' => 5]);
    JPOS_Test_Runner::assert_true(is_array($products), 'Should return an array');
    JPOS_Test_Runner::assert_true(count($products) <= 5, 'Should respect limit parameter');
});

JPOS_Test_Runner::add_test('Database Optimizer - Get Orders', function() {
    $orders = JPOS_Database_Optimizer::get_orders_optimized(['limit' => 5]);
    JPOS_Test_Runner::assert_true(is_array($orders), 'Should return an array');
    JPOS_Test_Runner::assert_true(count($orders) <= 5, 'Should respect limit parameter');
});

JPOS_Test_Runner::add_test('Database Optimizer - Get Reports', function() {
    $reports = JPOS_Database_Optimizer::get_reports_optimized();
    JPOS_Test_Runner::assert_true(is_array($reports), 'Should return an array');
    JPOS_Test_Runner::assert_true(isset($reports['totals']), 'Should have totals key');
    JPOS_Test_Runner::assert_true(isset($reports['daily_data']), 'Should have daily_data key');
});

JPOS_Test_Runner::add_test('Database Optimizer - Cache Clear', function() {
    // This should not throw an exception
    JPOS_Database_Optimizer::clear_cache();
    JPOS_Test_Runner::assert_true(true, 'Cache clear should not throw exception');
});

JPOS_Test_Runner::add_test('Database Optimizer - Set Cache Duration', function() {
    JPOS_Database_Optimizer::set_cache_duration(600);
    JPOS_Test_Runner::assert_true(true, 'Set cache duration should not throw exception');
});

JPOS_Test_Runner::add_test('Database Optimizer - Set Cache Enabled', function() {
    JPOS_Database_Optimizer::set_cache_enabled(false);
    JPOS_Database_Optimizer::set_cache_enabled(true);
    JPOS_Test_Runner::assert_true(true, 'Set cache enabled should not throw exception');
});
