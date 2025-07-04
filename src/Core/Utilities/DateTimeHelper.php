<?php
namespace JiFramework\Core\Utilities;

use JiFramework\Config\Config;
use DateTime;
use DateTimeZone;
use Exception;

class DateTimeHelper
{
    /**
     * Retrieves the application's default timezone.
     *
     * This function returns the application's default timezone as defined by the
     * Config class, or falls back to the system default timezone.
     *
     * @return string The application's default timezone.
     */
    public static function getDefaultTimezone(): string
    {   
        // Check if the TIMEZONE constant is defined and not empty in the Config class
        if (defined('JiFramework\Config\Config::TIMEZONE') && !empty(Config::TIMEZONE)) {
            return Config::TIMEZONE;
        } else {
            return date_default_timezone_get();
        }
    }

    /**
     * Convert a datetime string to a Unix timestamp.
     *
     * @param string $datetime The datetime string.
     * @param string $format   The format of the input datetime string.
     * @return int             The Unix timestamp.
     * @throws Exception       If the datetime cannot be parsed.
     */
    public static function datetimeToTimestamp($datetime, $format = 'Y-m-d H:i:s')
    {
        // Create a DateTime object from the input datetime string
        $date = DateTime::createFromFormat($format, $datetime);
        if ($date === false) {
            throw new Exception("Invalid datetime format: {$datetime}");
        }
        return $date->getTimestamp();
    }

    /**
     * Convert a Unix timestamp to a formatted datetime string.
     *
     * @param int    $timestamp The Unix timestamp.
     * @param string $format    The desired output format.
     * @return string           The formatted datetime string.
     */
    public static function timestampToDatetime($timestamp, $format = 'Y-m-d H:i:s')
    {
        // Use the configured timezone instead of system timezone
        $dateTime = new DateTime("@$timestamp");
        $dateTime->setTimezone(new DateTimeZone(self::getDefaultTimezone()));
        return $dateTime->format($format);
    }

        /**
     * Converts a date string from one format to another, with an optional source format.
     *
     * If a source format is provided, the function attempts to parse the date string according to
     * that format before converting it to the target format. If no source format is specified,
     * it assumes the date string is in a format understandable by strtotime().
     *
     * @param string $targetFormat The target date format, as specified by PHP's date() function.
     * @param string $dateString The input date string to be converted.
     * @param string $sourceFormat Optional. The source date format. If provided, used to parse $dateString.
     * @return string|false The formatted date string, or false if input date is invalid or conversion fails.
     */
    public static function formatDate($targetFormat, $dateString, $sourceFormat = '') {
		// Create DateTime object from the source format if provided
		if (!empty($sourceFormat)) {
			$date = DateTime::createFromFormat($sourceFormat, $dateString);
			// Check if the date creation was successful
			if ($date === false) {
				return false; // Indicate failure to parse the date
			}
		} else {
			// Use DateTime constructor for conversion if no source format is provided
			try {
				$date = new DateTime($dateString);
			} catch (Exception $e) {
				return false; // Indicate failure to parse the date
			}
		}

		// Format the date and return (no timezone conversion - preserve original time)
		return $date->format($targetFormat);
	}

    /**
     * Get a human-readable time elapsed string.
     *
     * @param string       $datetime The datetime string.
     * @param bool         $full     Whether to return the full time difference.
     * @param string       $format   The format of the datetime string.
     * @param string|DateTime $now   The current datetime or 'now'.
     * @return string                The elapsed time string.
     * @throws Exception             If the datetime cannot be parsed.
     */
    public static function getTimeElapsedString($datetime, $full = false, $format = 'Y-m-d H:i:s', $now = 'now')
    {
        // Create DateTime objects for the given datetime and now
        $dateTime = DateTime::createFromFormat($format, $datetime);
        $nowTime = $now === 'now' ? new DateTime() : (is_string($now) ? DateTime::createFromFormat($format, $now) : $now);

        if ($dateTime === false || $nowTime === false) {
            throw new Exception("Invalid datetime format.");
        }

        // Calculate the difference
        $diff = $nowTime->diff($dateTime);

        // Total number of days
        $totalDays = $diff->days;

        // Calculate weeks and remaining days
        $weeks = floor($totalDays / 7);
        $days = $totalDays % 7;

        // Map of time units and their singular names
        $timeComponents = [
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];

        $elapsed = [];

        // Years
        if ($diff->y) {
            $elapsed['y'] = $diff->y . ' ' . $timeComponents['y'] . ($diff->y > 1 ? 's' : '');
        }

        // Months
        if ($diff->m) {
            $elapsed['m'] = $diff->m . ' ' . $timeComponents['m'] . ($diff->m > 1 ? 's' : '');
        }

        // Weeks
        if ($weeks) {
            $elapsed['w'] = $weeks . ' ' . $timeComponents['w'] . ($weeks > 1 ? 's' : '');
        }

        // Days
        if ($days) {
            $elapsed['d'] = $days . ' ' . $timeComponents['d'] . ($days > 1 ? 's' : '');
        }

        // Hours
        if ($diff->h) {
            $elapsed['h'] = $diff->h . ' ' . $timeComponents['h'] . ($diff->h > 1 ? 's' : '');
        }

        // Minutes
        if ($diff->i) {
            $elapsed['i'] = $diff->i . ' ' . $timeComponents['i'] . ($diff->i > 1 ? 's' : '');
        }

        // Seconds
        if ($diff->s) {
            $elapsed['s'] = $diff->s . ' ' . $timeComponents['s'] . ($diff->s > 1 ? 's' : '');
        }

        // If not full, only return the first non-zero time component
        if (!$full) {
            $elapsed = array_slice($elapsed, 0, 1);
        } else {
            if(isset($elapsed['w'])) {
                unset($elapsed['w']);
            }
        }


        // Return the formatted string or 'just now' if empty
        return $elapsed ? implode(', ', $elapsed) . ' ago' : 'just now';
    }

    /**
     * Get the difference between two dates.
     *
     * @param string $date1    The first date and time.
     * @param string $date2    The second date and time.
     * @param bool   $absolute Whether to return absolute difference.
     * @param string $format   The format of the input dates.
     * @return \DateInterval   The DateInterval object representing the difference.
     * @throws Exception       If the dates cannot be parsed.
     */
    public static function getDateDifference($dateTime1, $dateTime2, $absolute = false, $format = 'Y-m-d H:i:s')
    {
        $dateTime1 = DateTime::createFromFormat($format, $dateTime1);
        $dateTime2 = DateTime::createFromFormat($format, $dateTime2);

        if ($dateTime1 === false || $dateTime2 === false) {
            throw new Exception("Invalid date format.");
        }

        return $dateTime1->diff($dateTime2, $absolute);
    }

    /**
     * Check if a date string is valid according to a specified format.
     *
     * @param string $date   The date string to validate.
     * @param string $format The expected date format.
     * @return bool          True if valid, false otherwise.
     */
    public static function isValidDate($date, $format = 'Y-m-d H:i:s')
    {
        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    /**
     * Get the weekday name of a given date.
     *
     * @param string $date   The date string.
     * @param string $format The format of the date string.
     * @return string        The weekday name.
     * @throws Exception     If the date cannot be parsed.
     */
    public static function getWeekday($date, $format = 'Y-m-d H:i:s')
    {
        $dateTime = DateTime::createFromFormat($format, $date);
        if ($dateTime === false) {
            throw new Exception("Invalid date format.");
        }
        return $dateTime->format('l'); // 'l' (lowercase 'L') gives the full textual representation of the day
    }

    /**
     * Add a specified number of days to a date.
     *
     * @param string $date   The original date string.
     * @param int    $days   The number of days to add.
     * @param string $format The format of the date string.
     * @return string        The new date string after adding days.
     * @throws Exception     If the date cannot be parsed.
     */
    public static function addDays($date, $days, $format = 'Y-m-d H:i:s')
    {
        $dateTime = DateTime::createFromFormat($format, $date);
        if ($dateTime === false) {
            throw new Exception("Invalid date format.");
        }
        $dateTime->modify("+{$days} days");
        return $dateTime->format($format);
    }

    /**
     * Subtract a specified number of days from a date.
     *
     * @param string $date   The original date string.
     * @param int    $days   The number of days to subtract.
     * @param string $format The format of the date string.
     * @return string        The new date string after subtracting days.
     * @throws Exception     If the date cannot be parsed.
     */
    public static function subtractDays($date, $days, $format = 'Y-m-d H:i:s')
    {
        return self::addDays($date, -$days, $format);
    }

    /**
     * Get the current datetime string in a specified format and timezone.
     *
     * @param string $format   The desired output format.
     * @param string $timezone The timezone identifier.
     * @return string          The current datetime string.
     * @throws Exception       If the timezone is invalid.
     */
    public static function getCurrentDatetime($format = 'Y-m-d H:i:s', $timezone = '')
    {
        //Check if the timezone is empty and set the default timezone
        if (empty($timezone)) {
            $timezone = self::getDefaultTimezone();
        }
        $dateTime = new DateTime('now', new DateTimeZone($timezone));
        return $dateTime->format($format);
    }

    /**
     * Get the list of supported timezone identifiers.
     *
     * @return array An array of timezone identifiers.
     */
    public static function getSupportedTimezones()
    {
        return DateTimeZone::listIdentifiers();
    }

    /**
     * Get the current datetime string using the default timezone.
     *
     * @param string $format The desired output format.
     * @return string        The current datetime string.
     */
    public static function now($format = 'Y-m-d H:i:s')
    {
        return self::getCurrentDatetime($format);
    }

    /**
     * Format a date for human-readable display.
     *
     * @param string $date   The date string to format.
     * @param string $format The input date format.
     * @return string        The formatted date string.
     * @throws Exception     If the date cannot be parsed.
     */
    public static function formatForDisplay($date, $format = 'Y-m-d H:i:s')
    {
        $dateTime = DateTime::createFromFormat($format, $date);
        if ($dateTime === false) {
            throw new Exception("Invalid date format.");
        }
        return $dateTime->format('M j, Y g:i A'); // e.g., "Jan 15, 2024 3:30 PM"
    }
}


