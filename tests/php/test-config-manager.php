<?php
// FILE: /jpos/tests/php/test-config-manager.php
// Tests for configuration manager functionality

require_once __DIR__ . '/test-runner.php';
require_once __DIR__ . '/../../api/config-manager.php';

// Test configuration manager functionality
JPOS_Test_Runner::add_test('Config Manager - Get Default Value', function() {
    $db_ttl = JPOS_Config_Manager::get('database.query_cache_ttl');
    JPOS_Test_Runner::assert_equals(300, $db_ttl, 'Default database cache TTL should be 300');
});

JPOS_Test_Runner::add_test('Config Manager - Get Nested Value', function() {
    $cache_enabled = JPOS_Config_Manager::get('cache.enabled');
    JPOS_Test_Runner::assert_equals(true, $cache_enabled, 'Default cache enabled should be true');
});

JPOS_Test_Runner::add_test('Config Manager - Get Non-existent Key', function() {
    $non_existent = JPOS_Config_Manager::get('non.existent.key', 'default_value');
    JPOS_Test_Runner::assert_equals('default_value', $non_existent, 'Non-existent key should return default value');
});

JPOS_Test_Runner::add_test('Config Manager - Set and Get Value', function() {
    $test_key = 'test.value.' . time();
    $test_value = 'test_value_' . time();
    
    JPOS_Config_Manager::set($test_key, $test_value);
    $retrieved_value = JPOS_Config_Manager::get($test_key);
    
    JPOS_Test_Runner::assert_equals($test_value, $retrieved_value, 'Set and get value should work correctly');
});

JPOS_Test_Runner::add_test('Config Manager - Get All Config', function() {
    $all_config = JPOS_Config_Manager::get_all();
    
    JPOS_Test_Runner::assert_true(is_array($all_config), 'All config should be an array');
    JPOS_Test_Runner::assert_true(isset($all_config['database']), 'All config should have database section');
    JPOS_Test_Runner::assert_true(isset($all_config['cache']), 'All config should have cache section');
});

JPOS_Test_Runner::add_test('Config Manager - Get Public Config', function() {
    $public_config = JPOS_Config_Manager::get_public_config();
    
    JPOS_Test_Runner::assert_true(is_array($public_config), 'Public config should be an array');
    JPOS_Test_Runner::assert_true(isset($public_config['ui']), 'Public config should have ui section');
    JPOS_Test_Runner::assert_true(isset($public_config['receipt']), 'Public config should have receipt section');
});

JPOS_Test_Runner::add_test('Config Manager - Validate Integer', function() {
    $is_valid = JPOS_Config_Manager::validate('database.query_cache_ttl', 300);
    JPOS_Test_Runner::assert_true($is_valid, 'Valid integer should pass validation');
    
    $is_invalid = JPOS_Config_Manager::validate('database.query_cache_ttl', 'invalid');
    JPOS_Test_Runner::assert_equals(false, $is_invalid, 'Invalid type should fail validation');
});

JPOS_Test_Runner::add_test('Config Manager - Validate Boolean', function() {
    $is_valid = JPOS_Config_Manager::validate('cache.enabled', true);
    JPOS_Test_Runner::assert_true($is_valid, 'Valid boolean should pass validation');
    
    $is_invalid = JPOS_Config_Manager::validate('cache.enabled', 'invalid');
    JPOS_Test_Runner::assert_equals(false, $is_invalid, 'Invalid type should fail validation');
});

JPOS_Test_Runner::add_test('Config Manager - Update Config', function() {
    $updates = [
        'database.query_cache_ttl' => 600,
        'cache.enabled' => true
    ];
    
    $result = JPOS_Config_Manager::update($updates);
    
    JPOS_Test_Runner::assert_true($result['success'], 'Valid updates should succeed');
    JPOS_Test_Runner::assert_true(count($result['updated']) > 0, 'Should have updated keys');
    JPOS_Test_Runner::assert_equals(0, count($result['errors']), 'Should have no errors');
});

JPOS_Test_Runner::add_test('Config Manager - Export Config', function() {
    $exported = JPOS_Config_Manager::export();
    
    JPOS_Test_Runner::assert_true(is_string($exported), 'Export should return string');
    JPOS_Test_Runner::assert_true(!empty($exported), 'Export should not be empty');
    
    $decoded = json_decode($exported, true);
    JPOS_Test_Runner::assert_true(is_array($decoded), 'Exported JSON should be valid array');
});
