<?php
namespace JiFramework\Core\Utilities;

class Str
{
    private const ENCODING = 'UTF-8';

    // =========================================================================
    // HTML / Security
    // =========================================================================

    /**
     * Escape special HTML characters in a string, array, or object (recursive).
     */
    public function escape(mixed $data): mixed
    {
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, self::ENCODING);
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->escape($value);
            }
            return $data;
        }
        if (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = $this->escape($value);
            }
            return $data;
        }
        return $data;
    }

    // =========================================================================
    // Measurement
    // =========================================================================

    /**
     * Get the character length of a string (multibyte-safe).
     */
    public function length(string $string): int
    {
        return mb_strlen($string, self::ENCODING);
    }

    /**
     * Count the number of words in a string.
     */
    public function wordCount(string $string): int
    {
        $parts = preg_split('/\s+/u', trim($string), -1, PREG_SPLIT_NO_EMPTY);
        return $parts ? count($parts) : 0;
    }

    /**
     * Count the number of times a substring appears in a string.
     */
    public function countOccurrences(string $haystack, string $needle): int
    {
        if ($needle === '') {
            return 0;
        }
        return mb_substr_count($haystack, $needle);
    }

    // =========================================================================
    // Slicing and extraction
    // =========================================================================

    /**
     * Get the first N words from a string.
     */
    public function words(string $string, int $count = 10): string
    {
        $parts = preg_split('/\s+/u', $string, $count + 1);
        if (count($parts) > $count) {
            array_pop($parts);
        }
        return implode(' ', $parts);
    }

    /**
     * Strip HTML tags and return the first N words as plain text.
     */
    public function plainText(string $html, int $wordCount = 10): string
    {
        $text  = html_entity_decode(strip_tags($html), ENT_QUOTES, self::ENCODING);
        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        return implode(' ', array_slice($parts, 0, $wordCount));
    }

    /**
     * Return the portion of a string before the first occurrence of a search string.
     * Returns the full string if the search string is not found.
     */
    public function before(string $string, string $search): string
    {
        if ($search === '') {
            return $string;
        }
        $pos = mb_strpos($string, $search, 0, self::ENCODING);
        return $pos === false ? $string : mb_substr($string, 0, $pos, self::ENCODING);
    }

    /**
     * Return the portion of a string after the first occurrence of a search string.
     * Returns the full string if the search string is not found.
     */
    public function after(string $string, string $search): string
    {
        if ($search === '') {
            return $string;
        }
        $pos = mb_strpos($string, $search, 0, self::ENCODING);
        if ($pos === false) {
            return $string;
        }
        return mb_substr($string, $pos + mb_strlen($search, self::ENCODING), null, self::ENCODING);
    }

    /**
     * Extract the substring between two delimiter strings.
     * Returns an empty string if either delimiter is not found.
     */
    public function between(string $string, string $start, string $end): string
    {
        $startPos = mb_strpos($string, $start, 0, self::ENCODING);
        if ($startPos === false) {
            return '';
        }
        $startPos += mb_strlen($start, self::ENCODING);
        $endPos = mb_strpos($string, $end, $startPos, self::ENCODING);
        if ($endPos === false) {
            return '';
        }
        return mb_substr($string, $startPos, $endPos - $startPos, self::ENCODING);
    }

    /**
     * Extract all digit characters from a string and return as an integer.
     * Example: 'abc123def456' → 123456
     */
    public function extractInteger(string $string): int
    {
        return (int) preg_replace('/[^0-9]+/', '', $string);
    }

    // =========================================================================
    // Manipulation
    // =========================================================================

    /**
     * Truncate a string to a maximum character length, appending an ellipsis if cut.
     * Character-safe for all languages.
     */
    public function truncate(string $string, int $length, string $ellipsis = '...'): string
    {
        if (mb_strlen($string, self::ENCODING) <= $length) {
            return $string;
        }
        $cut = $length - mb_strlen($ellipsis, self::ENCODING);
        return mb_substr($string, 0, max(0, $cut), self::ENCODING) . $ellipsis;
    }

    /**
     * Mask a portion of a string, leaving the last N characters visible.
     *
     * @param string $string           The original string.
     * @param int    $unmaskedChars    Number of trailing characters to leave unmasked.
     * @param string $maskingCharacter The character used for masking.
     */
    public function mask(string $string, int $unmaskedChars = 4, string $maskingCharacter = 'X'): string
    {
        $len = mb_strlen($string, self::ENCODING);
        if ($unmaskedChars < 0 || $unmaskedChars > $len) {
            return $string;
        }
        $maskLen = max(0, $len - $unmaskedChars);
        return str_repeat($maskingCharacter, $maskLen)
            . mb_substr($string, -$unmaskedChars, null, self::ENCODING);
    }

    /**
     * Prefix a number with zeros to reach a target string length.
     */
    public function zeroPad(int $number, int $targetLength): string
    {
        return str_pad((string) $number, $targetLength, '0', STR_PAD_LEFT);
    }

    /**
     * Reverse a string (multibyte-safe).
     */
    public function reverse(string $string): string
    {
        $chars = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
        return $chars ? implode('', array_reverse($chars)) : '';
    }

    /**
     * Replace only the first occurrence of a search string.
     */
    public function replaceFirst(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }
        $pos = mb_strpos($subject, $search, 0, self::ENCODING);
        if ($pos === false) {
            return $subject;
        }
        return mb_substr($subject, 0, $pos, self::ENCODING)
            . $replace
            . mb_substr($subject, $pos + mb_strlen($search, self::ENCODING), null, self::ENCODING);
    }

    /**
     * Replace only the last occurrence of a search string.
     */
    public function replaceLast(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }
        $pos = mb_strrpos($subject, $search, 0, self::ENCODING);
        if ($pos === false) {
            return $subject;
        }
        return mb_substr($subject, 0, $pos, self::ENCODING)
            . $replace
            . mb_substr($subject, $pos + mb_strlen($search, self::ENCODING), null, self::ENCODING);
    }

    // =========================================================================
    // Case conversion
    // =========================================================================

    /**
     * Convert a string to camelCase (multibyte-safe).
     * Handles spaces, hyphens, and underscores as word separators.
     */
    public function toCamelCase(string $string): string
    {
        $words  = preg_split('/[\s\-_]+/u', $string, -1, PREG_SPLIT_NO_EMPTY);
        $result = '';
        foreach ($words as $i => $word) {
            if ($i === 0) {
                $result .= mb_strtolower($word, self::ENCODING);
            } else {
                $result .= mb_strtoupper(mb_substr($word, 0, 1, self::ENCODING), self::ENCODING)
                    . mb_strtolower(mb_substr($word, 1, null, self::ENCODING), self::ENCODING);
            }
        }
        return $result;
    }

    /**
     * Convert a string to PascalCase (UpperCamelCase).
     */
    public function toPascalCase(string $string): string
    {
        $camel = $this->toCamelCase($string);
        return mb_strtoupper(mb_substr($camel, 0, 1, self::ENCODING), self::ENCODING)
            . mb_substr($camel, 1, null, self::ENCODING);
    }

    /**
     * Convert a string to snake_case.
     * Handles camelCase, PascalCase, spaces, and hyphens.
     */
    public function toSnakeCase(string $string): string
    {
        $string = preg_replace('/[\s\-]+/u', '_', $string);
        // Insert underscore between a lowercase/digit and an uppercase letter
        $string = preg_replace('/([a-z\d])([A-Z])/u', '$1_$2', $string);
        // Insert underscore between a run of uppercase letters and a trailing uppercase+lowercase
        $string = preg_replace('/([A-Z]+)([A-Z][a-z])/u', '$1_$2', $string);
        $string = mb_strtolower($string, self::ENCODING);
        $string = preg_replace('/__+/', '_', $string);
        return trim($string, '_');
    }

    /**
     * Convert a string to kebab-case.
     */
    public function toKebabCase(string $string): string
    {
        return str_replace('_', '-', $this->toSnakeCase($string));
    }

    /**
     * Convert a string to Title Case (each word capitalised).
     */
    public function toTitleCase(string $string): string
    {
        return mb_convert_case($string, MB_CASE_TITLE, self::ENCODING);
    }

    // =========================================================================
    // Search / Match
    // =========================================================================

    /**
     * Determine if a string contains a given substring.
     */
    public function contains(string $haystack, string $needle): bool
    {
        return $needle !== '' && mb_strpos($haystack, $needle, 0, self::ENCODING) !== false;
    }

    /**
     * Determine if a string starts with a given substring.
     */
    public function startsWith(string $haystack, string $needle): bool
    {
        return mb_substr($haystack, 0, mb_strlen($needle, self::ENCODING), self::ENCODING) === $needle;
    }

    /**
     * Determine if a string ends with a given substring.
     */
    public function endsWith(string $haystack, string $needle): bool
    {
        $len = mb_strlen($needle, self::ENCODING);
        if ($len === 0) {
            return true;
        }
        return mb_substr($haystack, -$len, null, self::ENCODING) === $needle;
    }

    /**
     * Determine if a string is valid JSON.
     */
    public function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    // =========================================================================
    // Slug / URL
    // =========================================================================

    /**
     * Convert a string to a URL-friendly slug.
     * Non-ASCII characters (e.g. Arabic, Devanagari) are transliterated where
     * possible and stripped otherwise.
     */
    public function slugify(string $string): string
    {
        $string = mb_strtolower($string, self::ENCODING);
        $string = strip_tags($string);
        $string = preg_replace('~[^\pL\d]+~u', '-', $string);
        $string = iconv(self::ENCODING, 'ASCII//TRANSLIT//IGNORE', $string);
        $string = preg_replace('~[^-\w]+~', '', $string);
        $string = trim($string, '-');
        $string = preg_replace('~-+~', '-', $string);
        return $string !== '' ? $string : 'n-a';
    }

    // =========================================================================
    // Formatting
    // =========================================================================

    /**
     * Format a number as a currency string.
     *
     * @param float  $number         The number to format.
     * @param int    $decimalPlaces  Decimal places (default 2).
     * @param string $currencySymbol Symbol to prepend (e.g. '$', '৳', '€').
     * @param string $decimalPoint   Decimal separator (default '.').
     * @param string $thousandsSep   Thousands separator (default ',').
     */
    public function formatCurrency(
        float  $number,
        int    $decimalPlaces  = 2,
        string $currencySymbol = '',
        string $decimalPoint   = '.',
        string $thousandsSep   = ','
    ): string {
        return $currencySymbol . number_format($number, $decimalPlaces, $decimalPoint, $thousandsSep);
    }

    // =========================================================================
    // Random / Generation
    // =========================================================================

    /**
     * Generate a cryptographically secure random integer between $min and $max (inclusive).
     */
    public function randomInt(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    /**
     * Generate a cryptographically secure random alphanumeric string.
     *
     * @param int $length Number of characters in the result.
     */
    public function randomString(int $length = 10): string
    {
        $chars  = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max    = strlen($chars) - 1;
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $max)];
        }
        return $result;
    }

    /**
     * Generate a cryptographically secure random hex token.
     *
     * @param int $length Number of random bytes (output will be $length * 2 hex chars).
     */
    public function randomToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Generate a random hexadecimal color code (e.g. '#a3f1b2').
     */
    public function randomColor(): string
    {
        return sprintf('#%02x%02x%02x',
            random_int(0, 255),
            random_int(0, 255),
            random_int(0, 255)
        );
    }
}
