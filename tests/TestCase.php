<?php
/**
 * Base TestCase class for jiFramework
 * 
 * This class provides common functionality for all test cases.
 */

class TestCase
{
    /**
     * @var App $app
     */
    protected $app;

    /**
     * Set up method to be called before each test.
     */
    public function setUp()
    {
        // Create a new instance of the application
        $this->app = new \JiFramework\Core\App\App();
    }

    /**
     * Tear down method to be called after each test.
     */
    public function tearDown()
    {
        // Reset any app state or clean up resources
        $this->app = null;
    }

    /**
     * Assert that a condition is true.
     * 
     * @param bool $condition The condition to check
     * @param string $message The message to display if the assertion fails
     * @throws Exception If the assertion fails
     */
    public function assertTrue($condition, $message = 'Expected true but got false')
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    /**
     * Assert that a condition is false.
     * 
     * @param bool $condition The condition to check
     * @param string $message The message to display if the assertion fails
     * @throws Exception If the assertion fails
     */
    public function assertFalse($condition, $message = 'Expected false but got true')
    {
        if ($condition) {
            throw new Exception($message);
        }
    }

    /**
     * Assert that two values are equal.
     * 
     * @param mixed $expected The expected value
     * @param mixed $actual The actual value
     * @param string $message The message to display if the assertion fails
     * @throws Exception If the assertion fails
     */
    public function assertEquals($expected, $actual, $message = null)
    {
        if ($expected !== $actual) {
            if ($message === null) {
                $message = "Expected $expected but got $actual";
            }
            throw new Exception($message);
        }
    }

    /**
     * Assert that a string contains a substring.
     * 
     * @param string $needle The substring to search for
     * @param string $haystack The string to search in
     * @param string $message The message to display if the assertion fails
     * @throws Exception If the assertion fails
     */
    public function assertStringContains($needle, $haystack, $message = null)
    {
        if (strpos($haystack, $needle) === false) {
            if ($message === null) {
                $message = "Expected string to contain '$needle' but it doesn't";
            }
            throw new Exception($message);
        }
    }

    /**
     * Assert that a variable is not null.
     * 
     * @param mixed $var The variable to check
     * @param string $message The message to display if the assertion fails
     * @throws Exception If the assertion fails
     */
    public function assertNotNull($var, $message = 'Expected not null but got null')
    {
        if ($var === null) {
            throw new Exception($message);
        }
    }

    /**
     * Assert that a variable is null.
     * 
     * @param mixed $var The variable to check
     * @param string $message The message to display if the assertion fails
     * @throws Exception If the assertion fails
     */
    public function assertNull($var, $message = 'Expected null')
    {
        if ($var !== null) {
            throw new Exception($message);
        }
    }

    /**
     * Print test result information.
     * 
     * @param string $testName The name of the test
     * @param string $result The result of the test
     */
    protected function printResult($testName, $result)
    {
        echo str_pad($testName, 40) . " : " . $result . PHP_EOL;
    }
} 


