<?php
/**
 * Test case for the Logger class in the Unit directory
 */

class UnitLoggerTest extends TestCase
{
    /**
     * @var string Test log file path
     */
    private $testLogFile;
    
    /**
     * @var \JIFramework\Core\Logger\Logger
     */
    private $logger;
    
    /**
     * Set up the test environment
     */
    public function setUp()
    {
        parent::setUp();
        
        // Create a test log file in a temporary directory
        $this->testLogFile = \JIFramework\Config\Config::STORAGE_PATH . 'Logs/test/test_log_' . uniqid() . '.log';
        
        // Create a new logger instance with our test log file
        $this->logger = new \JIFramework\Core\Logger\Logger($this->testLogFile);
    }
    
    /**
     * Clean up the test environment
     */
    public function tearDown()
    {
        // Delete the test log file if it exists
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
        
        parent::tearDown();
    }
    
    /**
     * Test debug level logging
     */
    public function testDebugLogging()
    {
        $message = 'This is a debug message';
        $this->logger->debug($message);
        
        // Sleep to ensure the log is written
        usleep(100000); // 100ms
        
        $logContent = file_get_contents($this->testLogFile);
        $this->assertTrue(strpos($logContent, '[DEBUG]') !== false, 'Log should contain DEBUG level');
        $this->assertTrue(strpos($logContent, $message) !== false, 'Log should contain the message');
    }
    
    /**
     * Test info level logging
     */
    public function testInfoLogging()
    {
        $message = 'This is an info message';
        $this->logger->info($message);
        
        // Sleep to ensure the log is written
        usleep(100000); // 100ms
        
        $logContent = file_get_contents($this->testLogFile);
        $this->assertTrue(strpos($logContent, '[INFO]') !== false, 'Log should contain INFO level');
        $this->assertTrue(strpos($logContent, $message) !== false, 'Log should contain the message');
    }
    
    /**
     * Test warning level logging
     */
    public function testWarningLogging()
    {
        $message = 'This is a warning message';
        $this->logger->warning($message);
        
        // Sleep to ensure the log is written
        usleep(100000); // 100ms
        
        $logContent = file_get_contents($this->testLogFile);
        $this->assertTrue(strpos($logContent, '[WARNING]') !== false, 'Log should contain WARNING level');
        $this->assertTrue(strpos($logContent, $message) !== false, 'Log should contain the message');
    }
    
    /**
     * Test error level logging
     */
    public function testErrorLogging()
    {
        $message = 'This is an error message';
        $this->logger->error($message);
        
        // Sleep to ensure the log is written
        usleep(100000); // 100ms
        
        $logContent = file_get_contents($this->testLogFile);
        $this->assertTrue(strpos($logContent, '[ERROR]') !== false, 'Log should contain ERROR level');
        $this->assertTrue(strpos($logContent, $message) !== false, 'Log should contain the message');
    }
    
    /**
     * Test critical level logging
     */
    public function testCriticalLogging()
    {
        $message = 'This is a critical message';
        $this->logger->critical($message);
        
        // Sleep to ensure the log is written
        usleep(100000); // 100ms
        
        $logContent = file_get_contents($this->testLogFile);
        $this->assertTrue(strpos($logContent, '[CRITICAL]') !== false, 'Log should contain CRITICAL level');
        $this->assertTrue(strpos($logContent, $message) !== false, 'Log should contain the message');
    }
    
    /**
     * Test context interpolation in log messages
     */
    public function testContextInterpolation()
    {
        $message = 'User {username} logged in from {ip}';
        $context = [
            'username' => 'john_doe',
            'ip' => '192.168.1.1'
        ];
        
        $this->logger->info($message, $context);
        
        // Sleep to ensure the log is written
        usleep(100000); // 100ms
        
        $logContent = file_get_contents($this->testLogFile);
        $this->assertTrue(strpos($logContent, 'User john_doe logged in from 192.168.1.1') !== false, 
            'Log should contain interpolated message');
    }
    
    /**
     * Test changing the log file at runtime
     */
    public function testSetLogFile()
    {
        $newLogFile = \JIFramework\Config\Config::STORAGE_PATH . 'Logs/test/new_test_log_' . uniqid() . '.log';
        
        // Write to the original log file
        $this->logger->info('Message in original log file');
        
        // Change the log file
        $this->logger->setLogFile($newLogFile);
        
        // Write to the new log file
        $newMessage = 'Message in new log file';
        $this->logger->info($newMessage);
        
        // Sleep to ensure the logs are written
        usleep(100000); // 100ms
        
        // Check that the new message is in the new log file
        $this->assertTrue(file_exists($newLogFile), 'New log file should be created');
        $newLogContent = file_get_contents($newLogFile);
        $this->assertTrue(strpos($newLogContent, $newMessage) !== false, 
            'New log file should contain the new message');
        
        // Clean up the new log file
        if (file_exists($newLogFile)) {
            unlink($newLogFile);
        }
    }
} 


