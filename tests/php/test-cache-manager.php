<?php
// FILE: /jpos/tests/php/test-cache-manager.php
// Tests for cache manager functionality

require_once __DIR__ . '/test-runner.php';
require_once __DIR__ . '/../../api/cache-manager.php';

// Test cache manager functionality
JPOS_Test_Runner::add_test('Cache Manager - Set and Get', function() {
    $test_key = 'test_key_' . time();
    $test_data = ['message' => 'Hello Cache!', 'timestamp' => time()];
    
    JPOS_Cache_Manager::set($test_key, $test_data, 60);
    $retrieved_data = JPOS_Cache_Manager::get($test_key);
    
    JPOS_Test_Runner::assert_equals($test_data, $retrieved_data, 'Cached data should match original data');
});

JPOS_Test_Runner::add_test('Cache Manager - Get Non-existent Key', function() {
    $non_existent_key = 'non_existent_key_' . time();
    $result = JPOS_Cache_Manager::get($non_existent_key);
    
    JPOS_Test_Runner::assert_equals(false, $result, 'Non-existent key should return false');
});

JPOS_Test_Runner::add_test('Cache Manager - Delete Key', function() {
    $test_key = 'test_delete_key_' . time();
    $test_data = ['test' => 'data'];
    
    JPOS_Cache_Manager::set($test_key, $test_data, 60);
    $before_delete = JPOS_Cache_Manager::get($test_key);
    
    JPOS_Cache_Manager::delete($test_key);
    $after_delete = JPOS_Cache_Manager::get($test_key);
    
    JPOS_Test_Runner::assert_equals($test_data, $before_delete, 'Data should exist before delete');
    JPOS_Test_Runner::assert_equals(false, $after_delete, 'Data should not exist after delete');
});

JPOS_Test_Runner::add_test('Cache Manager - Get Stats', function() {
    $stats = JPOS_Cache_Manager::get_stats();
    
    JPOS_Test_Runner::assert_true(is_array($stats), 'Stats should be an array');
    JPOS_Test_Runner::assert_true(isset($stats['total_files']), 'Stats should have total_files key');
    JPOS_Test_Runner::assert_true(isset($stats['total_size']), 'Stats should have total_size key');
    JPOS_Test_Runner::assert_true(isset($stats['expired_files']), 'Stats should have expired_files key');
});

JPOS_Test_Runner::add_test('Cache Manager - Clean Expired', function() {
    $cleaned = JPOS_Cache_Manager::clean_expired();
    
    JPOS_Test_Runner::assert_true(is_int($cleaned), 'Clean expired should return integer');
    JPOS_Test_Runner::assert_true($cleaned >= 0, 'Cleaned count should be non-negative');
});

JPOS_Test_Runner::add_test('Cache Manager - Generate Key', function() {
    $key = JPOS_Cache_Manager::generate_key('test_prefix', ['param1' => 'value1', 'param2' => 'value2']);
    
    JPOS_Test_Runner::assert_true(is_string($key), 'Generated key should be string');
    JPOS_Test_Runner::assert_true(strpos($key, 'test_prefix') === 0, 'Key should start with prefix');
});

JPOS_Test_Runner::add_test('Cache Manager - Set Cache Enabled', function() {
    JPOS_Cache_Manager::set_enabled(false);
    JPOS_Cache_Manager::set_enabled(true);
    JPOS_Test_Runner::assert_true(true, 'Set cache enabled should not throw exception');
});

JPOS_Test_Runner::add_test('Cache Manager - Set Default TTL', function() {
    JPOS_Cache_Manager::set_default_ttl(300);
    JPOS_Test_Runner::assert_true(true, 'Set default TTL should not throw exception');
});
