<?php
/**
 * Test Runner for jiFramework
 * 
 * This script finds and runs all test cases for the framework.
 * 
 * Usage:
 *   php tests/run_tests.php [option]
 * 
 * Options:
 *   <className>  - Run tests only for the specified class
 *   debug        - Show detailed output for each test (verbose logging)
 *   quiet        - Suppress all output except final test results (same as default, for backwards compatibility)
 *   progress     - Show real-time progress with dots (. for pass, F for fail)
 *   help         - Display this help message
 */

// Load bootstrap file
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to discover and run all test cases
function runTests($directory = null) {
    $testCount = 0;
    $passCount = 0;
    $failCount = 0;
    $directory = $directory ?: __DIR__;
    $failedTests = []; // Track failed tests for summary

    // Check command line arguments
    global $argv;
    $filterClass = null;
    $debug = false;
    $quiet = true; // Default to quiet mode
    $progress = false;
    
    // Check for help flag
    if (isset($argv[1]) && $argv[1] === 'help') {
        displayHelp();
        return true;
    }
    
    // Parse command line arguments
    if (isset($argv[1])) {
        if ($argv[1] === 'debug') {
            $debug = true;
            $quiet = false;
        } elseif ($argv[1] === 'quiet') {
            $quiet = true;
        } elseif ($argv[1] === 'progress') {
            $progress = true;
            $quiet = true;
        } else {
            $filterClass = $argv[1];
            
            // Check if there's a second argument for mode
            if (isset($argv[2])) {
                if ($argv[2] === 'debug') {
                    $debug = true;
                    $quiet = false;
                } elseif ($argv[2] === 'quiet') {
                    $quiet = true;
                } elseif ($argv[2] === 'progress') {
                    $progress = true;
                    $quiet = true;
                }
            }
        }
    }

    // Display appropriate header
    echo "\n==========================================================\n";
    if ($debug) {
        echo "        jiFramework Test Runner (Debug Mode)              \n";
    } else if ($progress) {
        echo "        jiFramework Test Runner (Progress Mode)           \n";
    } else {
        echo "        jiFramework Test Runner                           \n";
    }
    echo "==========================================================\n\n";

    if ($filterClass) {
        echo "Running only tests in class: {$filterClass}\n\n";
    }

    if ($debug) {
        echo "Running in debug mode with verbose output\n\n";
    }

    // Get the initial list of declared classes
    $initialClasses = get_declared_classes();
    
    // Get all PHP files in the directory
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();
            
            // Skip bootstrap.php, TestCase.php and this file
            if (
                basename($filePath) == 'bootstrap.php' || 
                basename($filePath) == 'TestCase.php' || 
                basename($filePath) == 'run_tests.php'
            ) {
                continue;
            }
            
            if ($debug) {
                echo "Loading file: {$filePath}\n";
            }

            // Remember currently declared classes
            $beforeClasses = get_declared_classes();
            
            // Capture output during file inclusion
            ob_start();
            require_once $filePath;
            if ($quiet || $progress) {
                ob_end_clean(); // Discard output in quiet/progress mode
            } else {
                ob_end_flush(); // Display output in debug mode
            }
            
            // Find newly declared classes
            $afterClasses = get_declared_classes();
            $newClasses = array_diff($afterClasses, $beforeClasses);
            
            // Filter for TestCase classes
            $testClasses = array_filter($newClasses, function($class) {
                return is_subclass_of($class, 'TestCase');
            });
            
            if (empty($testClasses)) {
                if ($debug) {
                    echo "No TestCase classes found in file: {$filePath}\n";
                }
                continue;
            }
            
            // Run tests for each TestCase class
            foreach ($testClasses as $className) {
                // Skip if filter is set and doesn't match
                if ($filterClass && $className !== $filterClass) {
                    continue;
                }
                
                // Display class name header for each test class
                if (!$progress) {
                    echo "\n" . str_repeat("-", 60) . "\n";
                    echo "Running tests in {$className}\n";
                    echo str_repeat("-", 60) . "\n";
                }
                
                // Create an instance of the class
                $testCase = new $className();
                
                // Get all methods of the class that start with 'test'
                $methods = get_class_methods($testCase);
                // Sort methods to ensure consistent order
                sort($methods);
                foreach ($methods as $method) {
                    if (strpos($method, 'test') === 0) {
                        $testCount++;
                        try {
                            // Call setUp method
                            if (method_exists($testCase, 'setUp')) {
                                // Capture and potentially suppress output
                                ob_start();
                                $testCase->setUp();
                                if ($quiet || $progress) {
                                    ob_end_clean();
                                } else {
                                    ob_end_flush();
                                }
                            }
                            
                            // Run the test with output buffering
                            ob_start();
                            $testCase->$method();
                            if ($quiet || $progress) {
                                ob_end_clean();
                            } else {
                                ob_end_flush();
                                // Make sure output is flushed to the screen
                                flush();
                            }
                            
                            // Call tearDown method
                            if (method_exists($testCase, 'tearDown')) {
                                ob_start();
                                $testCase->tearDown();
                                if ($quiet || $progress) {
                                    ob_end_clean();
                                } else {
                                    ob_end_flush();
                                    // Make sure output is flushed to the screen
                                    flush();
                                }
                            }
                            
                            // Print test result immediately
                            if ($progress) {
                                echo "."; // Simple dot for each passing test
                                flush();
                            } else {
                                echo "  ✓ " . str_pad($method, 40) . " : PASS\n";
                                flush();
                            }
                            $passCount++;
                        } catch (Exception $e) {
                            // Store failed test information
                            $failedTests[] = [
                                'class' => $className,
                                'method' => $method,
                                'message' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                                'trace' => $e->getTraceAsString()
                            ];
                            
                            // Print test result immediately
                            if ($progress) {
                                echo "F"; // F for failed test
                                flush();
                            } else {
                                echo "  ✗ " . str_pad($method, 40) . " : FAIL - " . $e->getMessage() . "\n";
                                flush();
                            }
                            if ($debug) {
                                echo "    Exception details: " . get_class($e) . "\n";
                                echo "    File: " . $e->getFile() . " (Line " . $e->getLine() . ")\n";
                                echo "    Trace: " . $e->getTraceAsString() . "\n";
                                // Flush to make sure it's displayed immediately
                                flush();
                            }
                            $failCount++;
                        }
                    }
                }
            }
        }
    }

    // In progress mode, add a newline after all the dots
    if ($progress) {
        echo "\n";
        
        // If there are failed tests, show them in detail
        if (!empty($failedTests)) {
            echo "\nFailed Tests:\n";
            echo str_repeat("-", 60) . "\n";
            
            foreach ($failedTests as $index => $test) {
                echo ($index + 1) . ") {$test['class']}::{$test['method']}\n";
                echo "   {$test['message']}\n";
                echo "   {$test['file']} (Line {$test['line']})\n\n";
            }
        }
    }

    echo "\n==========================================================\n";
    echo "Test Results: {$passCount}/{$testCount} passed, {$failCount} failed\n";
    echo "==========================================================\n\n";

    return $failCount === 0;
}

/**
 * Display help information
 */
function displayHelp() {
    echo "\n==========================================================\n";
    echo "        jiFramework Test Runner Help                      \n";
    echo "==========================================================\n\n";
    
    echo "Usage:\n";
    echo "  php tests/run_tests.php [option]\n\n";
    
    echo "Options:\n";
    echo "  <className>  - Run tests only for the specified class\n";
    echo "  debug        - Show detailed output for each test (verbose logging)\n";
    echo "  quiet        - Suppress all output except final test results (same as default)\n";
    echo "  progress     - Show real-time progress with dots (. for pass, F for fail)\n";
    echo "  help         - Display this help message\n\n";
    
    echo "Examples:\n";
    echo "  php tests/run_tests.php                     # Run all tests with minimal output\n";
    echo "  php tests/run_tests.php debug               # Run all tests with detailed output\n";
    echo "  php tests/run_tests.php quiet               # Run all tests with minimal output\n";
    echo "  php tests/run_tests.php progress            # Run all tests with progress indicators\n";
    echo "  php tests/run_tests.php RateLimiterUnitTest # Run only RateLimiterUnitTest tests\n\n";
}

// Run tests
$success = runTests();

// Exit with non-zero status if any tests failed
exit($success ? 0 : 1); 


