<?php
// FILE: /jpos/tests/php/test-runner.php
// Simple PHP test runner for JPOS

class JPOS_Test_Runner {
    
    private static $tests = [];
    private static $results = [];
    private static $start_time;
    
    /**
     * Add a test to the test suite
     */
    public static function add_test($name, $callback) {
        self::$tests[] = [
            'name' => $name,
            'callback' => $callback
        ];
    }
    
    /**
     * Assert that a condition is true
     */
    public static function assert_true($condition, $message = '') {
        if (!$condition) {
            throw new Exception("Assertion failed: " . $message);
        }
        return true;
    }
    
    /**
     * Assert that two values are equal
     */
    public static function assert_equals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new Exception("Assertion failed: Expected '{$expected}', got '{$actual}'. " . $message);
        }
        return true;
    }
    
    /**
     * Assert that an exception is thrown
     */
    public static function assert_throws($callback, $expected_exception = 'Exception') {
        try {
            $callback();
            throw new Exception("Expected exception '{$expected_exception}' was not thrown");
        } catch (Exception $e) {
            if (get_class($e) !== $expected_exception && $expected_exception !== 'Exception') {
                throw new Exception("Expected exception '{$expected_exception}', got '" . get_class($e) . "'");
            }
            return true;
        }
    }
    
    /**
     * Run all tests
     */
    public static function run_tests() {
        self::$start_time = microtime(true);
        self::$results = [];
        
        echo "=== JPOS Test Suite ===\n\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach (self::$tests as $test) {
            try {
                $test_start = microtime(true);
                call_user_func($test['callback']);
                $test_time = microtime(true) - $test_start;
                
                self::$results[] = [
                    'name' => $test['name'],
                    'status' => 'PASSED',
                    'time' => $test_time
                ];
                
                echo "✅ {$test['name']} (" . round($test_time * 1000, 2) . "ms)\n";
                $passed++;
                
            } catch (Exception $e) {
                $test_time = microtime(true) - $test_start;
                
                self::$results[] = [
                    'name' => $test['name'],
                    'status' => 'FAILED',
                    'error' => $e->getMessage(),
                    'time' => $test_time
                ];
                
                echo "❌ {$test['name']} (" . round($test_time * 1000, 2) . "ms)\n";
                echo "   Error: {$e->getMessage()}\n";
                $failed++;
            }
        }
        
        $total_time = microtime(true) - self::$start_time;
        
        echo "\n=== Test Results ===\n";
        echo "Total tests: " . count(self::$tests) . "\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        echo "Total time: " . round($total_time * 1000, 2) . "ms\n";
        echo "Success rate: " . round(($passed / count(self::$tests)) * 100, 1) . "%\n";
        
        return [
            'total' => count(self::$tests),
            'passed' => $passed,
            'failed' => $failed,
            'time' => $total_time,
            'success_rate' => ($passed / count(self::$tests)) * 100
        ];
    }
    
    /**
     * Get test results
     */
    public static function get_results() {
        return self::$results;
    }
    
    /**
     * Generate test report
     */
    public static function generate_report() {
        $results = self::run_tests();
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => $results,
            'tests' => self::$results
        ];
        
        // Save report to file
        $report_file = __DIR__ . '/../reports/test-report-' . date('Y-m-d-H-i-s') . '.json';
        file_put_contents($report_file, json_encode($report, JSON_PRETTY_PRINT));
        
        return $report;
    }
}
