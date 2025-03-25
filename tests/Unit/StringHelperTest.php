<?php
/**
 * Test case for the StringHelper class in the Unit directory
 */

class UnitStringHelperTest extends TestCase
{
    /**
     * Test the generateRandomString method
     */
    public function testGenerateRandomString()
    {
        $length = 10;
        $randomString = $this->app->stringHelper->generateRandomString($length);
        
        $this->assertEquals($length, strlen($randomString), 'Random string should have the specified length');
        
        // Test with a different length
        $length2 = 20;
        $randomString2 = $this->app->stringHelper->generateRandomString($length2);
        $this->assertEquals($length2, strlen($randomString2), 'Random string should have the specified length');
        
        // Test that two random strings are different
        $this->assertFalse($randomString === $randomString2, 'Two random strings should be different');
    }
    
    /**
     * Test the slugify method
     */
    public function testSlugify()
    {
        $input = "This is a Test String!";
        $expected = "this-is-a-test-string";
        
        $slug = $this->app->stringHelper->slugify($input);
        $this->assertEquals($expected, $slug, 'Slugify should convert text to lowercase and replace spaces with hyphens');
        
        // Test with special characters
        $input2 = "Spécial Chàrácters & Symbols!";
        $slug2 = $this->app->stringHelper->slugify($input2);
        
        // Verify no special characters remain
        $this->assertFalse(preg_match('/[^a-z0-9\-]/', $slug2), 'Slugified string should only contain lowercase letters, numbers, and hyphens');
    }
    
    /**
     * Test the truncateString method
     */
    public function testTruncateString()
    {
        $longText = "This is a very long text that should be truncated at a certain length.";
        $length = 20;
        $ellipsis = "...";
        
        $truncated = $this->app->stringHelper->truncateString($longText, $length, $ellipsis);
        
        // Check if the truncated string is not longer than expected
        $this->assertTrue(strlen($truncated) <= ($length + strlen($ellipsis)), 'Truncated text should not exceed the specified length plus ellipsis');
        $this->assertTrue(strpos($truncated, $ellipsis) !== false, 'Truncated text should contain the ellipsis');
    }
    
    /**
     * Test the escape method
     */
    public function testEscape()
    {
        $input = "<script>alert('XSS');</script>Test input with HTML";
        $sanitized = $this->app->stringHelper->escape($input);
        
        $this->assertFalse(strpos($sanitized, "<script>") !== false, 'Escaped input should not contain unescaped script tags');
        $this->assertTrue(strpos($sanitized, "&lt;script&gt;") !== false, 'Escaped input should contain escaped script tags');
        $this->assertTrue(strpos($sanitized, "Test input with HTML") !== false, 'Escaped input should preserve normal text');
    }
    
    /**
     * Test the formatCurrency method
     */
    public function testFormatCurrency()
    {
        $number = 1234567.89;
        
        // Test formatting with default parameters
        $formatted = $this->app->stringHelper->formatCurrency($number);
        
        $this->assertTrue(strpos($formatted, ',') !== false, 'Formatted currency should contain thousand separators');
        $this->assertTrue(strpos($formatted, '.') !== false, 'Formatted currency should contain a decimal point');
        
        // Test with different parameters
        $formatted2 = $this->app->stringHelper->formatCurrency($number, 0, '$');
        $this->assertTrue(strpos($formatted2, '$') === 0, 'Formatted currency should start with the currency symbol');
        $this->assertFalse(strpos($formatted2, '.') !== false, 'Formatted currency with 0 decimal places should not contain a decimal point');
    }
    
    /**
     * Test the generateRandomHexColor method
     */
    public function testGenerateRandomHexColor()
    {
        $color = $this->app->stringHelper->generateRandomHexColor();
        
        // Hex color should match the standard format
        $pattern = '/^#[0-9a-f]{6}$/i';
        $this->assertTrue(preg_match($pattern, $color) === 1, 'Generated hex color should match standard format');
        
        // Two colors should be different (although there's a tiny chance they could be the same)
        $color2 = $this->app->stringHelper->generateRandomHexColor();
        $this->assertTrue($color === $color2 || $color !== $color2, 'Two generated colors could be different');
    }
} 


