# jiFramework Tests

This directory contains test cases for the jiFramework.

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB for database tests
- A test database named `jiframework_test`

## Setup

1. Make sure you have a test database created:
   ```sql
   CREATE DATABASE jiframework_test;
   ```

2. Update the database configuration in `tests/bootstrap.php` if needed.

## Running Tests

To run all tests, execute:

```
php tests/run_tests.php
```

## Test Structure

- `tests/bootstrap.php` - Initializes the test environment
- `tests/TestCase.php` - Base test case class with assertion methods
- `tests/run_tests.php` - Test runner script
- `tests/JIFramework/Core/` - Test cases for core components

## Writing New Tests

1. Create a new PHP file in the appropriate directory.
2. Define a class that extends `TestCase`.
3. Implement methods that start with `test` to define your test cases.
4. Use the assertion methods from the `TestCase` class to verify your code works correctly.

Example:

```php
<?php
class MyComponentTest extends TestCase
{
    public function testFeature()
    {
        // Your test code here
        $this->assertTrue(true, 'This should pass');
    }
}
```

## Assertions Available

- `assertTrue($condition, $message)`
- `assertFalse($condition, $message)`
- `assertEquals($expected, $actual, $message)`
- `assertStringContains($needle, $haystack, $message)`
- `assertNotNull($var, $message)`
- `assertNull($var, $message)` 