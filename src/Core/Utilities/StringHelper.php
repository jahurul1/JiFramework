<?php
namespace JiFramework\Core\Utilities;

class StringHelper
{
    /**
     * Escape special HTML characters in a string or recursively in an array or object.
     *
     * @param mixed $data The data to escape.
     * @return mixed      The escaped data.
     */
    public function escape($data)
    {
        if (is_string($data)) {
            // Escape and return strings
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        } elseif (is_array($data)) {
            // Recursively escape arrays
            foreach ($data as $key => $value) {
                $data[$key] = $this->escape($value);
            }
            return $data;
        } elseif (is_object($data)) {
            // Recursively escape objects
            foreach ($data as $key => $value) {
                $data->$key = $this->escape($value);
            }
            return $data;
        }
        // Return the data unchanged if it is neither a string, array, nor object
        return $data;
    }

    /**
	 * Generates a random integer within a specified range.
	 * 
	 * @param int $min The minimum value of the range.
	 * @param int $max The maximum value of the range.
	 * @return int A random integer between $min and $max.
	 */
	public function generateRandomInteger($min, $max) {
		return mt_rand($min, $max);
	}

	/**
	 * Creates a secure cryptographic token.
	 * 
	 * @param int $length The length of the token in bytes before hexadecimal encoding.
	 * @return string A hexadecimal string representing the generated token.
	 */
	public function generateToken($length) {
		return bin2hex(openssl_random_pseudo_bytes($length));
	}
	
	/**
	 * Calculates the length of a string.
	 * 
	 * @param string $string The string to measure.
	 * @return int The number of characters in $string.
	 */
	public function strLength($string) {
		return strlen($string);
	}

    /**
     * Mask a string by replacing a portion of it with a specified masking character.
     *
     * @param string $string           The original string.
     * @param int    $unmaskedChars    Number of characters to leave unmasked at the end.
     * @param string $maskingCharacter The character used for masking.
     * @return string                  The masked string.
     */
    public function maskString($string, $unmaskedChars = 4, $maskingCharacter = 'X')
    {
        // Validate the number of unmasked characters
        $stringLength = strlen($string);
        if ($unmaskedChars < 0 || $unmaskedChars > $stringLength) {
            // Return the original string if the requested number of unmasked characters is invalid
            return $string;
        }

        // Calculate the number of characters to mask
        $maskLength = max(0, $stringLength - $unmaskedChars);

        // Create the masked section
        $maskedSection = str_repeat($maskingCharacter, $maskLength);

        // Concatenate the masked section with the unmasked end characters
        return $maskedSection . substr($string, -$unmaskedChars);
    }

     /**
     * Extract an integer value from a string containing numbers and other characters.
     *
     * @param string $string The input string.
     * @return int           The extracted integer.
     */
    public function extractInteger($string)
    {
        $intValue = intval(preg_replace('/[^0-9]+/', '', $string));
        return $intValue;
    }

    /**
     * Prefix a number with zeros to reach a specified total length.
     *
     * @param int $number       The original number.
     * @param int $targetLength The desired total length of the output string.
     * @return string           The number as a string, prefixed with zeros.
     */
    public function padWithZeros($number, $targetLength)
    {
        return str_pad($number, $targetLength, '0', STR_PAD_LEFT);
    }

    /**
     * Get the first N words from a sentence.
     *
     * @param string $sentence The input sentence.
     * @param int    $count    The number of words to extract.
     * @return string          The extracted words.
     */
    public function getFirstWords($sentence, $count = 10)
    {
        $words = preg_split('/\s+/', $sentence, $count + 1);
        if (count($words) > $count) {
            array_pop($words);
        }
        return implode(' ', $words);
    }

    /**
     * Extract plain text from HTML content and get the first N words.
     *
     * @param string $htmlContent The HTML content.
     * @param int    $wordCount   The number of words to extract.
     * @return string             The extracted plain text.
     */
    public function extractTextFromHtml($htmlContent, $wordCount = 10)
    {
        // Remove HTML tags and decode HTML entities
        $text = html_entity_decode(strip_tags($htmlContent));

        // Split the text into words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Extract the first $wordCount words
        $leadingWords = array_slice($words, 0, $wordCount);

        // Join the words back into a string
        return implode(' ', $leadingWords);
    }

    /**
     * Format a number as currency.
     *
     * @param float  $number          The number to format.
     * @param int    $decimalPlaces   Number of decimal places.
     * @param string $currencySymbol  Currency symbol to prepend.
     * @param string $decimalPoint    Decimal point character.
     * @param string $thousandsSep    Thousands separator.
     * @return string                 The formatted currency string.
     */
    public function formatCurrency($number, $decimalPlaces = 2, $currencySymbol = '', $decimalPoint = '.', $thousandsSep = ',')
    {
        $formattedAmount = number_format($number, $decimalPlaces, $decimalPoint, $thousandsSep);
        return $currencySymbol . $formattedAmount;
    }

    /**
     * Generate a random hexadecimal color code.
     *
     * @return string The hexadecimal color code.
     */
    public function generateRandomHexColor()
    {
        // Generate random values for red, green, and blue components of the color
        $red = str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
        $green = str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
        $blue = str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);

        // Combine the components into a single hexadecimal color code
        return '#' . $red . $green . $blue;
    }

    /**
     * Generate a random alphanumeric string.
     *
     * @param int $length The length of the string to generate.
     * @return string     The generated string.
     */
    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Truncate a string to a specified length, optionally adding an ellipsis.
     *
     * @param string $string    The input string.
     * @param int    $length    The maximum length.
     * @param string $ellipsis  The ellipsis to append if truncated.
     * @return string           The truncated string.
     */
    public function truncateString($string, $length, $ellipsis = '...')
    {
        if (strlen($string) > $length) {
            return substr($string, 0, $length - strlen($ellipsis)) . $ellipsis;
        }
        return $string;
    }

    /**
     * Convert a string to a URL-friendly "slug".
     *
     * @param string $string The input string.
     * @return string        The slugified string.
     */
    public function slugify($string)
    {
        // Convert to lowercase
        $string = strtolower($string);
        // Remove HTML tags
        $string = strip_tags($string);
        // Replace non-letter or digits with hyphens
        $string = preg_replace('~[^\pL\d]+~u', '-', $string);
        // Transliterate to ASCII
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        // Remove unwanted characters
        $string = preg_replace('~[^-\w]+~', '', $string);
        // Trim
        $string = trim($string, '-');
        // Remove duplicate hyphens
        $string = preg_replace('~-+~', '-', $string);
        // Ensure the slug is not empty
        if (empty($string)) {
            return 'n-a';
        }
        return $string;
    }

    /**
     * Convert a string to camelCase.
     *
     * @param string $string The input string.
     * @return string        The camelCased string.
     */
    public function toCamelCase($string)
    {
        $result = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
        $result[0] = strtolower($result[0]);
        return $result;
    }

    /**
     * Convert a string to snake_case.
     *
     * @param string $string The input string.
     * @return string        The snake_cased string.
     */
    public function toSnakeCase($string)
    {
        $string = preg_replace('/\s+/', '_', $string);
        $string = preg_replace('/[A-Z]/', '_$0', $string);
        $string = strtolower($string);
        $string = preg_replace('/__+/', '_', $string);
        $string = trim($string, '_');
        return $string;
    }

    /**
     * Check if a string starts with a given substring.
     *
     * @param string $haystack The string to search in.
     * @param string $needle   The substring to search for.
     * @return bool            True if $haystack starts with $needle, false otherwise.
     */
    public function startsWith($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Check if a string ends with a given substring.
     *
     * @param string $haystack The string to search in.
     * @param string $needle   The substring to search for.
     * @return bool            True if $haystack ends with $needle, false otherwise.
     */
    public function endsWith($haystack, $needle)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}


