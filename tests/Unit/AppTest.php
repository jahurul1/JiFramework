<?php
/**
 * Test case for the App class (Unit test version)
 */

class AppUnitTest extends TestCase
{
    /**
     * Test that the App class can be instantiated
     */
    public function testAppInitialization()
    {
        $this->assertNotNull($this->app, 'App should be initialized');
        $this->assertTrue($this->app instanceof \JIFramework\Core\App\App, 'App should be an instance of App class');
    }

    /**
     * Test that the App has a valid database instance
     */
    public function testAppHasDatabase()
    {
        $this->assertNotNull($this->app->db, 'App should have a database instance');
        $this->assertTrue($this->app->db instanceof \JIFramework\Core\Database\QueryBuilder, 'Database should be an instance of QueryBuilder');
    }

    /**
     * Test that the App has a valid auth instance
     */
    public function testAppHasAuth()
    {
        $this->assertNotNull($this->app->auth, 'App should have an auth instance');
        $this->assertTrue($this->app->auth instanceof \JIFramework\Core\Auth\Auth, 'Auth should be an instance of Auth class');
    }

    /**
     * Test that the App has a valid session manager
     */
    public function testAppHasSessionManager()
    {
        $this->assertNotNull($this->app->sessionManager, 'App should have a session manager');
        $this->assertTrue($this->app->sessionManager instanceof \JIFramework\Core\Session\SessionManager, 'SessionManager should be an instance of SessionManager class');
    }

    /**
     * Test the redirect method
     */
    public function testRedirectMethod()
    {
        // We can't fully test the redirect method because it uses header() and exit()
        // But we can check that the method exists
        $this->assertTrue(method_exists($this->app, 'redirect'), 'App should have a redirect method');
    }

    /**
     * Test the exit method
     */
    public function testExitMethod()
    {
        // Similarly, we can't fully test the exit method because it calls exit()
        // But we can check that the method exists
        $this->assertTrue(method_exists($this->app, 'exit'), 'App should have an exit method');
    }
} 


