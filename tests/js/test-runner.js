// FILE: /jpos/tests/js/test-runner.js
// Simple JavaScript test runner for JPOS

class JPOS_Test_Runner {
    constructor() {
        this.tests = [];
        this.results = [];
        this.startTime = null;
    }
    
    /**
     * Add a test to the test suite
     */
    addTest(name, callback) {
        this.tests.push({
            name: name,
            callback: callback
        });
    }
    
    /**
     * Assert that a condition is true
     */
    assertTrue(condition, message = '') {
        if (!condition) {
            throw new Error(`Assertion failed: ${message}`);
        }
        return true;
    }
    
    /**
     * Assert that two values are equal
     */
    assertEquals(expected, actual, message = '') {
        if (expected !== actual) {
            throw new Error(`Assertion failed: Expected '${expected}', got '${actual}'. ${message}`);
        }
        return true;
    }
    
    /**
     * Assert that two objects are deeply equal
     */
    assertDeepEquals(expected, actual, message = '') {
        if (JSON.stringify(expected) !== JSON.stringify(actual)) {
            throw new Error(`Assertion failed: Expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}. ${message}`);
        }
        return true;
    }
    
    /**
     * Assert that an exception is thrown
     */
    assertThrows(callback, expectedError = 'Error') {
        try {
            callback();
            throw new Error(`Expected error '${expectedError}' was not thrown`);
        } catch (e) {
            if (e.name !== expectedError && expectedError !== 'Error') {
                throw new Error(`Expected error '${expectedError}', got '${e.name}'`);
            }
            return true;
        }
    }
    
    /**
     * Run all tests
     */
    async runTests() {
        this.startTime = performance.now();
        this.results = [];
        
        console.log('=== JPOS JavaScript Test Suite ===\n');
        
        let passed = 0;
        let failed = 0;
        
        for (const test of this.tests) {
            try {
                const testStart = performance.now();
                
                // Handle async tests
                if (test.callback.constructor.name === 'AsyncFunction') {
                    await test.callback();
                } else {
                    test.callback();
                }
                
                const testTime = performance.now() - testStart;
                
                this.results.push({
                    name: test.name,
                    status: 'PASSED',
                    time: testTime
                });
                
                console.log(`✅ ${test.name} (${testTime.toFixed(2)}ms)`);
                passed++;
                
            } catch (error) {
                const testTime = performance.now() - testStart;
                
                this.results.push({
                    name: test.name,
                    status: 'FAILED',
                    error: error.message,
                    time: testTime
                });
                
                console.log(`❌ ${test.name} (${testTime.toFixed(2)}ms)`);
                console.log(`   Error: ${error.message}`);
                failed++;
            }
        }
        
        const totalTime = performance.now() - this.startTime;
        
        console.log('\n=== Test Results ===');
        console.log(`Total tests: ${this.tests.length}`);
        console.log(`Passed: ${passed}`);
        console.log(`Failed: ${failed}`);
        console.log(`Total time: ${totalTime.toFixed(2)}ms`);
        console.log(`Success rate: ${((passed / this.tests.length) * 100).toFixed(1)}%`);
        
        return {
            total: this.tests.length,
            passed: passed,
            failed: failed,
            time: totalTime,
            successRate: (passed / this.tests.length) * 100
        };
    }
    
    /**
     * Get test results
     */
    getResults() {
        return this.results;
    }
    
    /**
     * Generate test report
     */
    async generateReport() {
        const results = await this.runTests();
        
        const report = {
            timestamp: new Date().toISOString(),
            summary: results,
            tests: this.results
        };
        
        return report;
    }
}

// Global test runner instance
window.JPOS_Test_Runner = JPOS_Test_Runner;
