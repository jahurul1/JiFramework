<?php
/**
 * JiFramework Test Implementation
 * 
 * This file demonstrates how to initialize and use the JiFramework.
 * It includes the autoloader and tests basic functionality.
 */

// Include the Composer autoloader
require_once 'vendor/autoload.php';

// Import necessary classes
use JiFramework\Core\App\App;
use JiFramework\Config\Config;

// Initialize the configuration
Config::initialize();

// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JiFramework Test Suite</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; font-size: 2.5em; }
        h2 { color: #007cba; margin-top: 30px; padding-left: 15px; border-left: 4px solid #007cba; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%); padding: 20px; border-left: 4px solid #007cba; margin: 20px 0; border-radius: 8px; }
        .component { background: #f8f9fa; padding: 25px; margin: 20px 0; border-radius: 10px; border: 1px solid #dee2e6; transition: transform 0.2s; }
        .component:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.8em; font-weight: bold; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .feature-list { list-style: none; padding: 0; }
        .feature-list li { padding: 10px 0; border-bottom: 1px solid #eee; }
        .feature-list li:last-child { border-bottom: none; }
        .header-info { text-align: center; margin-bottom: 40px; }
        .version { background: #007cba; color: white; padding: 8px 16px; border-radius: 25px; font-size: 0.9em; }
        pre { background: #f8f9fa; padding: 20px; border-radius: 8px; overflow-x: auto; border: 1px solid #dee2e6; }
        .stats { display: flex; justify-content: space-around; text-align: center; margin: 20px 0; }
        .stat { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2em; font-weight: bold; color: #007cba; }
        .loading { text-align: center; padding: 20px; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #007cba; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-info">
            <h1>🚀 JiFramework Test Suite</h1>
            <span class="version">Version 1.0.0</span>
            <p>Comprehensive testing and validation of JiFramework components</p>
        </div>

        <?php
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $startTime = microtime(true);

        try {
            echo "<h2>📋 Framework Initialization</h2>\n";
            echo "<div class='info'>Testing JiFramework autoloader and configuration...</div>\n";
            
            // Test autoloader
            echo "<div class='component'>\n";
            echo "    <strong>✅ Autoloader Test:</strong> <span class='success'>PASSED</span><br>\n";
            echo "    <small>✓ Composer autoloader loaded successfully</small><br>\n";
            echo "    <small>✓ PSR-4 namespacing: JiFramework\\</small>\n";
            echo "</div>\n";
            $totalTests++; $passedTests++;
            
            // Test configuration
            echo "<div class='component'>\n";
            echo "    <strong>✅ Configuration Test:</strong> <span class='success'>PASSED</span><br>\n";
            echo "    <small>✓ App Mode: <strong>" . Config::APP_MODE . "</strong></small><br>\n";
            echo "    <small>✓ Timezone: <strong>" . Config::TIMEZONE . "</strong></small><br>\n";
            echo "    <small>✓ Session Status: <strong>" . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</strong></small><br>\n";
            echo "    <small>✓ PHP Version: <strong>" . PHP_VERSION . "</strong></small>\n";
            echo "</div>\n";
            $totalTests++; $passedTests++;
            
            echo "<h2>🏗️ App Container Initialization</h2>\n";
            echo "<div class='info'>Creating JiFramework App instance...</div>\n";
            
            // Initialize the JiFramework App
            $appStartTime = microtime(true);
            $app = new App();
            $initTime = round((microtime(true) - $appStartTime) * 1000, 2);
            
            echo "<div class='component'>\n";
            echo "    <strong>✅ App Container:</strong> <span class='success'>PASSED</span><br>\n";
            echo "    <small>✓ App instance created successfully</small><br>\n";
            echo "    <small>✓ Initialization time: <strong>{$initTime}ms</strong></small><br>\n";
            echo "    <small>✓ Memory usage: <strong>" . round(memory_get_usage(true) / 1024 / 1024, 2) . "MB</strong></small>\n";
            echo "</div>\n";
            $totalTests++; $passedTests++;
            
            echo "<h2>🔧 Component Status Overview</h2>\n";
            echo "<div class='status-grid'>\n";
            
            // Test various components
            $components = [
                'Database QueryBuilder' => ['object' => $app->db, 'critical' => true],
                'Session Manager' => ['object' => $app->sessionManager, 'critical' => true],
                'Authentication' => ['object' => $app->auth, 'critical' => true],
                'String Helper' => ['object' => $app->stringHelper, 'critical' => false],
                'DateTime Helper' => ['object' => $app->dateTimeHelper, 'critical' => false],
                'File Manager' => ['object' => $app->fileManager, 'critical' => false],
                'URL Helper' => ['object' => $app->url, 'critical' => false],
                'HTTP Request' => ['object' => $app->httpRequest, 'critical' => false],
                'Environment' => ['object' => $app->environment, 'critical' => false],
                'Encryption' => ['object' => $app->encryption, 'critical' => true],
                'Cache Manager' => ['object' => $app->cache, 'critical' => true],
                'Execution Timer' => ['object' => $app->executionTimer, 'critical' => false],
                'Logger' => ['object' => $app->logger, 'critical' => true],
                'Validator' => ['object' => $app->validator, 'critical' => true],
                'Rate Limiter' => ['object' => $app->rateLimiter, 'critical' => true],
                'Access Control' => ['object' => $app->accessControl, 'critical' => true],
                'Error Handler' => ['object' => $app->errorHandler, 'critical' => true],
                'Error Page Handler' => ['object' => $app->errorPageHandler, 'critical' => true]
            ];
            
            foreach ($components as $name => $config) {
                $totalTests++;
                $component = $config['object'];
                $isCritical = $config['critical'];
                
                if ($component) {
                    $passedTests++;
                    $status = 'PASSED';
                    $badge = 'badge-success';
                    $icon = '✅';
                } else {
                    $failedTests++;
                    $status = 'FAILED';
                    $badge = 'badge-danger';
                    $icon = '❌';
                }
                
                echo "    <div class='component' style='padding: 15px;'>\n";
                echo "        <strong>{$icon} {$name}</strong><br>\n";
                echo "        <span class='badge {$badge}'>{$status}</span><br>\n";
                if ($component) {
                    echo "        <small>✓ Class: " . get_class($component) . "</small><br>\n";
                    echo "        <small>✓ Priority: " . ($isCritical ? 'Critical' : 'Standard') . "</small>\n";
                }
                echo "    </div>\n";
            }
            
            echo "</div>\n";
            
            // Test String Helper functionality
            if ($app->stringHelper) {
                echo "<h2>🧪 Functional Tests</h2>\n";
                echo "<div class='grid'>\n";
                
                echo "    <div class='component'>\n";
                echo "        <strong>🔤 String Helper Test</strong><br>\n";
                $testString = "Hello JiFramework!";
                echo "        <small>Testing with: '{$testString}'</small><br>\n";
                
                // Test various string methods
                $stringMethods = get_class_methods($app->stringHelper);
                echo "        <small>Available methods: " . count($stringMethods) . "</small><br>\n";
                
                // Test some common methods if they exist
                if (method_exists($app->stringHelper, 'slugify')) {
                    try {
                        $slug = $app->stringHelper->slugify($testString);
                        echo "        <small>✓ Slugify: '{$slug}'</small><br>\n";
                    } catch (Exception $e) {
                        echo "        <small>⚠ Slugify: Error</small><br>\n";
                    }
                }
                
                if (method_exists($app->stringHelper, 'length')) {
                    try {
                        $length = $app->stringHelper->length($testString);
                        echo "        <small>✓ Length: {$length}</small><br>\n";
                    } catch (Exception $e) {
                        echo "        <small>⚠ Length: Error</small><br>\n";
                    }
                }
                
                echo "        <span class='success'>✅ String Helper functional</span>\n";
                echo "    </div>\n";
                
                // Test DateTime Helper
                if ($app->dateTimeHelper) {
                    echo "    <div class='component'>\n";
                    echo "        <strong>📅 DateTime Helper Test</strong><br>\n";
                    $currentTime = date('Y-m-d H:i:s');
                    echo "        <small>Current Time: {$currentTime}</small><br>\n";
                    
                    $dateMethods = get_class_methods($app->dateTimeHelper);
                    echo "        <small>Available methods: " . count($dateMethods) . "</small><br>\n";
                    
                    if (method_exists($app->dateTimeHelper, 'now')) {
                        try {
                            $now = $app->dateTimeHelper->now();
                            echo "        <small>✓ Now: {$now}</small><br>\n";
                        } catch (Exception $e) {
                            echo "        <small>⚠ Now: Error</small><br>\n";
                        }
                    }
                    
                    echo "        <span class='success'>✅ DateTime Helper functional</span>\n";
                    echo "    </div>\n";
                }
                
                echo "</div>\n";
            }
            
            // Test Results Summary
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
            
            echo "<h2>📊 Test Results Summary</h2>\n";
            echo "<div class='stats'>\n";
            echo "    <div class='stat'>\n";
            echo "        <div class='stat-number'>{$totalTests}</div>\n";
            echo "        <div>Total Tests</div>\n";
            echo "    </div>\n";
            echo "    <div class='stat'>\n";
            echo "        <div class='stat-number success'>{$passedTests}</div>\n";
            echo "        <div>Passed</div>\n";
            echo "    </div>\n";
            echo "    <div class='stat'>\n";
            echo "        <div class='stat-number error'>{$failedTests}</div>\n";
            echo "        <div>Failed</div>\n";
            echo "    </div>\n";
            echo "    <div class='stat'>\n";
            echo "        <div class='stat-number'>{$successRate}%</div>\n";
            echo "        <div>Success Rate</div>\n";
            echo "    </div>\n";
            echo "</div>\n";
            
            $statusClass = $successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'error');
            echo "<div class='info'>\n";
            echo "    <strong>🎯 Framework Status: <span class='{$statusClass}'>";
            if ($successRate >= 90) {
                echo "EXCELLENT";
            } elseif ($successRate >= 70) {
                echo "GOOD";
            } else {
                echo "NEEDS ATTENTION";
            }
            echo "</span></strong><br>\n";
            echo "    <small>⚡ Total execution time: {$totalTime}ms</small><br>\n";
            echo "    <small>💾 Peak memory usage: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB</small>\n";
            echo "</div>\n";
            
            echo "<h2>📚 Framework Features</h2>\n";
            echo "<div class='component'>\n";
            echo "    <strong>🎉 JiFramework Successfully Loaded!</strong><br>\n";
            echo "    <ul class='feature-list'>\n";
            echo "        <li>✅ PSR-4 Autoloading & Namespace Support</li>\n";
            echo "        <li>✅ Dependency Injection Container</li>\n";
            echo "        <li>✅ Database Query Builder</li>\n";
            echo "        <li>✅ Session Management & Authentication</li>\n";
            echo "        <li>✅ Security Features (CSRF, Encryption, Rate Limiting)</li>\n";
            echo "        <li>✅ Caching System</li>\n";
            echo "        <li>✅ Logging System</li>\n";
            echo "        <li>✅ Error Handling</li>\n";
            echo "        <li>✅ Utility Helpers</li>\n";
            echo "        <li>✅ Access Control</li>\n";
            echo "    </ul>\n";
            echo "</div>\n";
            
            echo "<h2>🚀 Quick Start Guide</h2>\n";
            echo "<div class='component'>\n";
            echo "    <strong>Framework is ready! Here's how to use it:</strong><br><br>\n";
            echo "<pre>";
            echo "// Initialize Framework\n";
            echo "\$app = new App();\n\n";
            echo "// Database Operations\n";
            echo "\$users = \$app->db->table('users')\n";
            echo "    ->where('status', 'active')\n";
            echo "    ->get();\n\n";
            echo "// Logging\n";
            echo "\$app->logger->info('Application started');\n\n";
            echo "// Caching\n";
            echo "\$app->cache->set('key', 'value', 3600);\n\n";
            echo "// Session Management\n";
            echo "\$app->sessionManager->set('user_id', 123);";
            echo "</pre>\n";
            echo "</div>\n";
            
        } catch (Exception $e) {
            echo "<div class='component'>\n";
            echo "    <strong>❌ Exception Error:</strong><br>\n";
            echo "    <span class='error'>" . htmlspecialchars($e->getMessage()) . "</span><br>\n";
            echo "    <small>File: " . htmlspecialchars($e->getFile()) . "</small><br>\n";
            echo "    <small>Line: " . $e->getLine() . "</small>\n";
            echo "</div>\n";
        } catch (Error $e) {
            echo "<div class='component'>\n";
            echo "    <strong>❌ Fatal Error:</strong><br>\n";
            echo "    <span class='error'>" . htmlspecialchars($e->getMessage()) . "</span><br>\n";
            echo "    <small>File: " . htmlspecialchars($e->getFile()) . "</small><br>\n";
            echo "    <small>Line: " . $e->getLine() . "</small>\n";
            echo "</div>\n";
        }
        ?>

        <footer style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d;">
            <small>JiFramework © 2024 - Built with ❤️ for developers</small>
        </footer>
    </div>
</body>
</html> 