<?php
/**
 * Test case for the Config class in the Unit directory
 */

class UnitConfigTest extends TestCase
{
    /**
     * Test the Config::get method with constants
     */
    public function testGetConstant()
    {
        $timezone = \JIFramework\Config\Config::get('TIMEZONE');
        $this->assertEquals('Asia/Kolkata', $timezone, 'Should get the correct timezone');
        
        $appMode = \JIFramework\Config\Config::get('APP_MODE');
        $this->assertEquals('development', $appMode, 'Should get the correct app mode');
    }
    
    /**
     * Test the Config::get method with static properties
     */
    public function testGetStaticProperty()
    {
        $dbConfig = \JIFramework\Config\Config::get('primaryDatabase');
        $this->assertTrue(is_array($dbConfig), 'Should get an array for database config');
        $this->assertTrue(isset($dbConfig['host']), 'Database config should have a host key');
        $this->assertTrue(isset($dbConfig['database']), 'Database config should have a database key');
    }
    
    /**
     * Test the Config::get method with nested properties
     */
    public function testGetNestedProperty()
    {
        $dbHost = \JIFramework\Config\Config::get('primaryDatabase.host');
        $this->assertEquals('localhost', $dbHost, 'Should get the correct database host');
    }
    
    /**
     * Test the Config::get method with default value
     */
    public function testGetWithDefault()
    {
        $nonExistentValue = \JIFramework\Config\Config::get('NON_EXISTENT_KEY', 'default_value');
        $this->assertEquals('default_value', $nonExistentValue, 'Should return the default value for a non-existent key');
    }
    
    /**
     * Test the Config::initSession method
     */
    /* public function testInitSession()
    {
        // Save session status before the test
        $sessionStatusBefore = session_status();
        
        // Close the session if it's active
        if ($sessionStatusBefore == PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Now call the initSession method
        \JIFramework\Config\Config::initSession();
        
        // Check that the session is active
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status(), 'Session should be active after calling initSession');
    } */
    
    /**
     * Test that all required directories exist
     */
    public function testDirectoriesExist()
    {
        $this->assertTrue(is_dir(\JIFramework\Config\Config::STORAGE_PATH), 'Storage directory should exist');
        $this->assertTrue(is_dir(\JIFramework\Config\Config::STORAGE_PATH . 'Logs/'), 'Logs directory should exist');
        $this->assertTrue(is_dir(\JIFramework\Config\Config::STORAGE_PATH . 'Uploads/'), 'Uploads directory should exist');
    }
} 


