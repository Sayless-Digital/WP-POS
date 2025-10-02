<?php
// FILE: /jpos/tests/run-all-tests.php
// Comprehensive test suite runner for JPOS

require_once __DIR__ . '/php/test-runner.php';
require_once __DIR__ . '/php/test-database-optimizer.php';
require_once __DIR__ . '/php/test-cache-manager.php';
require_once __DIR__ . '/php/test-config-manager.php';

echo "=== JPOS COMPREHENSIVE TEST SUITE ===\n\n";

$total_tests = 0;
$total_passed = 0;
$total_failed = 0;
$total_time = 0;

// Test suites to run
$test_suites = [
    'Database Optimizer' => 'test-database-optimizer.php',
    'Cache Manager' => 'test-cache-manager.php',
    'Config Manager' => 'test-config-manager.php'
];

foreach ($test_suites as $suite_name => $test_file) {
    echo "--- Testing {$suite_name} ---\n";
    
    // Clear previous tests
    JPOS_Test_Runner::$tests = [];
    
    // Include test file (this will add tests to the runner)
    require_once __DIR__ . '/php/' . $test_file;
    
    // Run tests for this suite
    $results = JPOS_Test_Runner::run_tests();
    
    $total_tests += $results['total'];
    $total_passed += $results['passed'];
    $total_failed += $results['failed'];
    $total_time += $results['time'];
    
    echo "\n";
}

echo "=== OVERALL TEST RESULTS ===\n";
echo "Total test suites: " . count($test_suites) . "\n";
echo "Total tests: {$total_tests}\n";
echo "Total passed: {$total_passed}\n";
echo "Total failed: {$total_failed}\n";
echo "Total time: " . round($total_time * 1000, 2) . "ms\n";
echo "Overall success rate: " . round(($total_passed / $total_tests) * 100, 1) . "%\n";

// Create test report
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'summary' => [
        'total_suites' => count($test_suites),
        'total_tests' => $total_tests,
        'total_passed' => $total_passed,
        'total_failed' => $total_failed,
        'total_time' => $total_time,
        'success_rate' => ($total_passed / $total_tests) * 100
    ],
    'suites' => $test_suites
];

// Save report
$reports_dir = __DIR__ . '/reports';
if (!file_exists($reports_dir)) {
    wp_mkdir_p($reports_dir);
}

$report_file = $reports_dir . '/test-report-' . date('Y-m-d-H-i-s') . '.json';
file_put_contents($report_file, json_encode($report, JSON_PRETTY_PRINT));

echo "\nTest report saved to: {$report_file}\n";

if ($total_failed === 0) {
    echo "\n🎉 ALL TESTS PASSED! JPOS is working correctly.\n";
    exit(0);
} else {
    echo "\n⚠️  Some tests failed. Please review the errors above.\n";
    exit(1);
}
?>
