<?php
namespace JiFramework\Core\Utilities;

use JiFramework\Config\Config;
use DateTime;
use DateTimeZone;

class DateTimeHelper
{
    /** Default format for database storage and general use. */
    const DB_FORMAT = 'Y-m-d H:i:s';

    // =========================================================================
    // Config / Timezone info
    // =========================================================================

    /**
     * Get the application's configured timezone.
     * Falls back to the system default if Config::$timezone is empty.
     */
    public static function getAppTimezone(): string
    {
        return !empty(Config::$timezone) ? Config::$timezone : date_default_timezone_get();
    }

    /**
     * Check if a timezone identifier is valid.
     *
     * @param string $timezone  e.g. 'Asia/Dhaka', 'UTC', 'America/New_York'
     */
    public static function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, DateTimeZone::listIdentifiers(), true);
    }

    /**
     * Get all supported timezone identifiers.
     *
     * @return string[]
     */
    public static function getSupportedTimezones(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    // =========================================================================
    // Current time
    // =========================================================================

    /**
     * Get the current datetime in the app timezone (or a specific timezone).
     *
     * @param string $format    Output format (default: 'Y-m-d H:i:s')
     * @param string $timezone  Timezone override. Empty = app timezone.
     */
    public static function now(string $format = self::DB_FORMAT, string $timezone = ''): string
    {
        $tz = new DateTimeZone(self::resolveTimezone($timezone));
        return (new DateTime('now', $tz))->format($format);
    }

    /**
     * Get today's date in the app timezone.
     *
     * @param string $format    Output format (default: 'Y-m-d')
     * @param string $timezone  Timezone override.
     */
    public static function today(string $format = 'Y-m-d', string $timezone = ''): string
    {
        return self::now($format, $timezone);
    }

    /**
     * Get tomorrow's date in the app timezone.
     *
     * @param string $format    Output format (default: 'Y-m-d')
     * @param string $timezone  Timezone override.
     */
    public static function tomorrow(string $format = 'Y-m-d', string $timezone = ''): string
    {
        $tz = new DateTimeZone(self::resolveTimezone($timezone));
        return (new DateTime('tomorrow', $tz))->format($format);
    }

    /**
     * Get yesterday's date in the app timezone.
     *
     * @param string $format    Output format (default: 'Y-m-d')
     * @param string $timezone  Timezone override.
     */
    public static function yesterday(string $format = 'Y-m-d', string $timezone = ''): string
    {
        $tz = new DateTimeZone(self::resolveTimezone($timezone));
        return (new DateTime('yesterday', $tz))->format($format);
    }

    // =========================================================================
    // Format only (no timezone conversion)
    // =========================================================================

    /**
     * Reformat a datetime string without changing its timezone.
     *
     * @param string $targetFormat  Desired output format
     * @param string $datetime      Input datetime string
     * @param string $sourceFormat  Input format (default: 'Y-m-d H:i:s')
     */
    public static function format(
        string $targetFormat,
        string $datetime,
        string $sourceFormat = self::DB_FORMAT
    ): string {
        return self::makeDateTime($datetime, $sourceFormat)->format($targetFormat);
    }

    /**
     * Format a datetime for human-readable display.
     * e.g. '2024-01-15 15:30:00' → 'Jan 15, 2024 3:30 PM'
     *
     * @param string $datetime      Input datetime string
     * @param string $sourceFormat  Input format (default: 'Y-m-d H:i:s')
     */
    public static function formatForDisplay(string $datetime, string $sourceFormat = self::DB_FORMAT): string
    {
        return self::format('M j, Y g:i A', $datetime, $sourceFormat);
    }

    // =========================================================================
    // Timezone conversion
    // =========================================================================

    /**
     * Convert a datetime string from one timezone to another.
     *
     * @param string $datetime      Input datetime string
     * @param string $fromTz        Source timezone (e.g. 'Europe/Warsaw')
     * @param string $toTz          Target timezone (e.g. 'Asia/Kolkata')
     * @param string $format        Output format (default: 'Y-m-d H:i:s')
     * @param string $sourceFormat  Input format (default: 'Y-m-d H:i:s')
     */
    public static function convertTimezone(
        string $datetime,
        string $fromTz,
        string $toTz,
        string $format = self::DB_FORMAT,
        string $sourceFormat = self::DB_FORMAT
    ): string {
        $dt = self::makeDateTime($datetime, $sourceFormat, $fromTz);
        $dt->setTimezone(new DateTimeZone($toTz));
        return $dt->format($format);
    }

    /**
     * Convert a UTC datetime to a target timezone for display.
     * Shortcut for convertTimezone(..., 'UTC', $toTimezone).
     *
     * @param string $datetime    UTC datetime string
     * @param string $toTimezone  Target timezone (e.g. 'Asia/Dhaka')
     * @param string $format      Output format (default: 'Y-m-d H:i:s')
     */
    public static function fromUtc(
        string $datetime,
        string $toTimezone,
        string $format = self::DB_FORMAT
    ): string {
        return self::convertTimezone($datetime, 'UTC', $toTimezone, $format);
    }

    /**
     * Convert a datetime from a source timezone to UTC.
     * Shortcut for convertTimezone(..., $fromTimezone, 'UTC').
     *
     * @param string $datetime      Input datetime string
     * @param string $fromTimezone  Source timezone (e.g. 'Asia/Dhaka')
     * @param string $format        Output format (default: 'Y-m-d H:i:s')
     */
    public static function toUtc(
        string $datetime,
        string $fromTimezone,
        string $format = self::DB_FORMAT
    ): string {
        return self::convertTimezone($datetime, $fromTimezone, 'UTC', $format);
    }

    // =========================================================================
    // DB helpers
    // =========================================================================

    /**
     * Prepare a datetime for database storage by converting to UTC.
     * Uses the app timezone as the source if $fromTimezone is not specified.
     * Output is always 'Y-m-d H:i:s'.
     *
     * Single-timezone apps: do not use this — just call now() and store directly.
     * Multi-timezone apps: always call this before saving user-submitted datetimes.
     *
     * @param string $datetime      Input datetime string
     * @param string $fromTimezone  Source timezone. Empty = app timezone.
     */
    public static function forDatabase(string $datetime, string $fromTimezone = ''): string
    {
        return self::toUtc($datetime, self::resolveTimezone($fromTimezone), self::DB_FORMAT);
    }

    /**
     * Convert a UTC datetime retrieved from the database to the app timezone (or specified timezone).
     *
     * @param string $datetime    UTC datetime string from DB
     * @param string $toTimezone  Target timezone. Empty = app timezone.
     * @param string $format      Output format (default: 'Y-m-d H:i:s')
     */
    public static function fromDatabase(
        string $datetime,
        string $toTimezone = '',
        string $format = self::DB_FORMAT
    ): string {
        return self::fromUtc($datetime, self::resolveTimezone($toTimezone), $format);
    }

    // =========================================================================
    // Manipulation
    // =========================================================================

    /**
     * Add days to a datetime string.
     *
     * @param string $date    Input datetime string
     * @param int    $days    Number of days to add (negative to subtract)
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function addDays(string $date, int $days, string $format = self::DB_FORMAT): string
    {
        return self::modify($date, ($days >= 0 ? "+{$days}" : "{$days}") . ' days', $format);
    }

    /** Subtract days from a datetime string. */
    public static function subtractDays(string $date, int $days, string $format = self::DB_FORMAT): string
    {
        return self::addDays($date, -abs($days), $format);
    }

    /**
     * Add hours to a datetime string.
     *
     * @param string $date    Input datetime string
     * @param int    $hours   Number of hours to add (negative to subtract)
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function addHours(string $date, int $hours, string $format = self::DB_FORMAT): string
    {
        return self::modify($date, ($hours >= 0 ? "+{$hours}" : "{$hours}") . ' hours', $format);
    }

    /** Subtract hours from a datetime string. */
    public static function subtractHours(string $date, int $hours, string $format = self::DB_FORMAT): string
    {
        return self::addHours($date, -abs($hours), $format);
    }

    /**
     * Add minutes to a datetime string.
     *
     * @param string $date     Input datetime string
     * @param int    $minutes  Number of minutes to add (negative to subtract)
     * @param string $format   Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function addMinutes(string $date, int $minutes, string $format = self::DB_FORMAT): string
    {
        return self::modify($date, ($minutes >= 0 ? "+{$minutes}" : "{$minutes}") . ' minutes', $format);
    }

    /** Subtract minutes from a datetime string. */
    public static function subtractMinutes(string $date, int $minutes, string $format = self::DB_FORMAT): string
    {
        return self::addMinutes($date, -abs($minutes), $format);
    }

    /**
     * Add months to a datetime string.
     *
     * @param string $date    Input datetime string
     * @param int    $months  Number of months to add (negative to subtract)
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function addMonths(string $date, int $months, string $format = self::DB_FORMAT): string
    {
        return self::modify($date, ($months >= 0 ? "+{$months}" : "{$months}") . ' months', $format);
    }

    /** Subtract months from a datetime string. */
    public static function subtractMonths(string $date, int $months, string $format = self::DB_FORMAT): string
    {
        return self::addMonths($date, -abs($months), $format);
    }

    /**
     * Add years to a datetime string.
     *
     * @param string $date    Input datetime string
     * @param int    $years   Number of years to add (negative to subtract)
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function addYears(string $date, int $years, string $format = self::DB_FORMAT): string
    {
        return self::modify($date, ($years >= 0 ? "+{$years}" : "{$years}") . ' years', $format);
    }

    /** Subtract years from a datetime string. */
    public static function subtractYears(string $date, int $years, string $format = self::DB_FORMAT): string
    {
        return self::addYears($date, -abs($years), $format);
    }

    // =========================================================================
    // Start / End of period
    // =========================================================================

    /**
     * Get the start of the day (00:00:00).
     *
     * @param string $date    Input datetime string
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function startOfDay(string $date, string $format = self::DB_FORMAT): string
    {
        $dt = self::makeDateTime($date, $format);
        $dt->setTime(0, 0, 0);
        return $dt->format($format);
    }

    /**
     * Get the end of the day (23:59:59).
     *
     * @param string $date    Input datetime string
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function endOfDay(string $date, string $format = self::DB_FORMAT): string
    {
        $dt = self::makeDateTime($date, $format);
        $dt->setTime(23, 59, 59);
        return $dt->format($format);
    }

    /**
     * Get the first moment of the month (first day, 00:00:00).
     *
     * @param string $date    Input datetime string
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function startOfMonth(string $date, string $format = self::DB_FORMAT): string
    {
        $dt = self::makeDateTime($date, $format);
        $dt->modify('first day of this month')->setTime(0, 0, 0);
        return $dt->format($format);
    }

    /**
     * Get the last moment of the month (last day, 23:59:59).
     *
     * @param string $date    Input datetime string
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function endOfMonth(string $date, string $format = self::DB_FORMAT): string
    {
        $dt = self::makeDateTime($date, $format);
        $dt->modify('last day of this month')->setTime(23, 59, 59);
        return $dt->format($format);
    }

    /**
     * Get the first moment of the year (Jan 1, 00:00:00).
     *
     * @param string $date    Input datetime string
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function startOfYear(string $date, string $format = self::DB_FORMAT): string
    {
        $dt = self::makeDateTime($date, $format);
        $dt->modify('first day of january')->setTime(0, 0, 0);
        return $dt->format($format);
    }

    /**
     * Get the last moment of the year (Dec 31, 23:59:59).
     *
     * @param string $date    Input datetime string
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function endOfYear(string $date, string $format = self::DB_FORMAT): string
    {
        $dt = self::makeDateTime($date, $format);
        $dt->modify('last day of december')->setTime(23, 59, 59);
        return $dt->format($format);
    }

    /**
     * Get the start of the ISO week (Monday 00:00:00).
     *
     * @param string $date    Input datetime string
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function startOfWeek(string $date, string $format = self::DB_FORMAT): string
    {
        $dt = self::makeDateTime($date, $format);
        $dayOfWeek = (int) $dt->format('N'); // 1 = Mon, 7 = Sun (ISO 8601)
        $dt->modify('-' . ($dayOfWeek - 1) . ' days')->setTime(0, 0, 0);
        return $dt->format($format);
    }

    /**
     * Get the end of the ISO week (Sunday 23:59:59).
     *
     * @param string $date    Input datetime string
     * @param string $format  Input and output format (default: 'Y-m-d H:i:s')
     */
    public static function endOfWeek(string $date, string $format = self::DB_FORMAT): string
    {
        $dt = self::makeDateTime($date, $format);
        $dayOfWeek = (int) $dt->format('N'); // 1 = Mon, 7 = Sun (ISO 8601)
        $dt->modify('+' . (7 - $dayOfWeek) . ' days')->setTime(23, 59, 59);
        return $dt->format($format);
    }

    // =========================================================================
    // Comparison
    // =========================================================================

    /**
     * Check if a datetime is in the past (relative to now in the app timezone).
     *
     * @param string $datetime  Input datetime string
     * @param string $format    Input format (default: 'Y-m-d H:i:s')
     */
    public static function isPast(string $datetime, string $format = self::DB_FORMAT): bool
    {
        $tz  = new DateTimeZone(self::getAppTimezone());
        $dt  = self::makeDateTime($datetime, $format, self::getAppTimezone());
        $now = new DateTime('now', $tz);
        return $dt < $now;
    }

    /**
     * Check if a datetime is in the future (relative to now in the app timezone).
     *
     * @param string $datetime  Input datetime string
     * @param string $format    Input format (default: 'Y-m-d H:i:s')
     */
    public static function isFuture(string $datetime, string $format = self::DB_FORMAT): bool
    {
        $tz  = new DateTimeZone(self::getAppTimezone());
        $dt  = self::makeDateTime($datetime, $format, self::getAppTimezone());
        $now = new DateTime('now', $tz);
        return $dt > $now;
    }

    /**
     * Check if $date1 is strictly before $date2.
     *
     * @param string $date1   First datetime string
     * @param string $date2   Second datetime string
     * @param string $format  Input format for both dates (default: 'Y-m-d H:i:s')
     */
    public static function isBefore(string $date1, string $date2, string $format = self::DB_FORMAT): bool
    {
        return self::makeDateTime($date1, $format) < self::makeDateTime($date2, $format);
    }

    /**
     * Check if $date1 is strictly after $date2.
     *
     * @param string $date1   First datetime string
     * @param string $date2   Second datetime string
     * @param string $format  Input format for both dates (default: 'Y-m-d H:i:s')
     */
    public static function isAfter(string $date1, string $date2, string $format = self::DB_FORMAT): bool
    {
        return self::makeDateTime($date1, $format) > self::makeDateTime($date2, $format);
    }

    /**
     * Check if two datetimes fall on the same calendar day.
     *
     * @param string $date1   First datetime string
     * @param string $date2   Second datetime string
     * @param string $format  Input format for both dates (default: 'Y-m-d H:i:s')
     */
    public static function isSameDay(string $date1, string $date2, string $format = self::DB_FORMAT): bool
    {
        return self::makeDateTime($date1, $format)->format('Y-m-d')
            === self::makeDateTime($date2, $format)->format('Y-m-d');
    }

    /**
     * Check if a datetime falls between $start and $end (inclusive).
     *
     * @param string $datetime  The datetime to check
     * @param string $start     Start of range
     * @param string $end       End of range
     * @param string $format    Input format for all three dates (default: 'Y-m-d H:i:s')
     */
    public static function isBetween(
        string $datetime,
        string $start,
        string $end,
        string $format = self::DB_FORMAT
    ): bool {
        $dt      = self::makeDateTime($datetime, $format);
        $dtStart = self::makeDateTime($start, $format);
        $dtEnd   = self::makeDateTime($end, $format);
        return $dt >= $dtStart && $dt <= $dtEnd;
    }

    /**
     * Check if a datetime falls on today's date in the app timezone.
     *
     * @param string $date    Input datetime string
     * @param string $format  Input format (default: 'Y-m-d H:i:s')
     */
    public static function isToday(string $date, string $format = self::DB_FORMAT): bool
    {
        $tz = new DateTimeZone(self::getAppTimezone());
        return self::makeDateTime($date, $format)->format('Y-m-d')
            === (new DateTime('now', $tz))->format('Y-m-d');
    }

    /**
     * Check if a datetime falls on yesterday's date in the app timezone.
     *
     * @param string $date    Input datetime string
     * @param string $format  Input format (default: 'Y-m-d H:i:s')
     */
    public static function isYesterday(string $date, string $format = self::DB_FORMAT): bool
    {
        $tz = new DateTimeZone(self::getAppTimezone());
        return self::makeDateTime($date, $format)->format('Y-m-d')
            === (new DateTime('yesterday', $tz))->format('Y-m-d');
    }

    /**
     * Check if a datetime falls on tomorrow's date in the app timezone.
     *
     * @param string $date    Input datetime string
     * @param string $format  Input format (default: 'Y-m-d H:i:s')
     */
    public static function isTomorrow(string $date, string $format = self::DB_FORMAT): bool
    {
        $tz = new DateTimeZone(self::getAppTimezone());
        return self::makeDateTime($date, $format)->format('Y-m-d')
            === (new DateTime('tomorrow', $tz))->format('Y-m-d');
    }

    /**
     * Check if a date falls on a weekend (Saturday or Sunday).
     *
     * @param string $date    Input datetime string
     * @param string $format  Input format (default: 'Y-m-d H:i:s')
     */
    public static function isWeekend(string $date, string $format = self::DB_FORMAT): bool
    {
        return in_array(self::makeDateTime($date, $format)->format('N'), ['6', '7'], true);
    }

    /**
     * Check if a date falls on a weekday (Monday through Friday).
     *
     * @param string $date    Input datetime string
     * @param string $format  Input format (default: 'Y-m-d H:i:s')
     */
    public static function isWeekday(string $date, string $format = self::DB_FORMAT): bool
    {
        return !self::isWeekend($date, $format);
    }

    // =========================================================================
    // Integer diffs
    // =========================================================================

    /**
     * Get the difference in whole days between two dates.
     *
     * @param string $date1     First datetime string
     * @param string $date2     Second datetime string
     * @param bool   $absolute  Whether to return absolute value (default: true)
     * @param string $format    Input format for both dates (default: 'Y-m-d H:i:s')
     */
    public static function diffInDays(
        string $date1,
        string $date2,
        bool $absolute = true,
        string $format = self::DB_FORMAT
    ): int {
        $interval = self::makeDateTime($date1, $format)
            ->diff(self::makeDateTime($date2, $format));
        $days = (int) $interval->days;
        return $absolute ? $days : ($interval->invert ? -$days : $days);
    }

    /**
     * Get the difference in whole hours between two datetimes.
     *
     * @param string $date1     First datetime string
     * @param string $date2     Second datetime string
     * @param bool   $absolute  Whether to return absolute value (default: true)
     * @param string $format    Input format for both dates (default: 'Y-m-d H:i:s')
     */
    public static function diffInHours(
        string $date1,
        string $date2,
        bool $absolute = true,
        string $format = self::DB_FORMAT
    ): int {
        $seconds = self::makeDateTime($date2, $format)->getTimestamp()
                 - self::makeDateTime($date1, $format)->getTimestamp();
        return (int)(($absolute ? abs($seconds) : $seconds) / 3600);
    }

    /**
     * Get the difference in whole minutes between two datetimes.
     *
     * @param string $date1     First datetime string
     * @param string $date2     Second datetime string
     * @param bool   $absolute  Whether to return absolute value (default: true)
     * @param string $format    Input format for both dates (default: 'Y-m-d H:i:s')
     */
    public static function diffInMinutes(
        string $date1,
        string $date2,
        bool $absolute = true,
        string $format = self::DB_FORMAT
    ): int {
        $seconds = self::makeDateTime($date2, $format)->getTimestamp()
                 - self::makeDateTime($date1, $format)->getTimestamp();
        return (int)(($absolute ? abs($seconds) : $seconds) / 60);
    }

    /**
     * Get the difference in seconds between two datetimes.
     *
     * @param string $date1     First datetime string
     * @param string $date2     Second datetime string
     * @param bool   $absolute  Whether to return absolute value (default: true)
     * @param string $format    Input format for both dates (default: 'Y-m-d H:i:s')
     */
    public static function diffInSeconds(
        string $date1,
        string $date2,
        bool $absolute = true,
        string $format = self::DB_FORMAT
    ): int {
        $seconds = self::makeDateTime($date2, $format)->getTimestamp()
                 - self::makeDateTime($date1, $format)->getTimestamp();
        return $absolute ? abs($seconds) : $seconds;
    }

    // =========================================================================
    // Human readable
    // =========================================================================

    /**
     * Get a human-readable elapsed or remaining time string.
     *
     * Examples:
     *   '3 hours ago', 'in 2 days', '1 year, 3 months ago', 'just now'
     *
     * @param string          $datetime  Input datetime string
     * @param bool            $full      Show all components (false = largest unit only)
     * @param string          $format    Input datetime format (default: 'Y-m-d H:i:s')
     * @param string          $now       Reference point ('now' or a datetime string in $format)
     */
    public static function getTimeElapsedString(
        string $datetime,
        bool $full = false,
        string $format = self::DB_FORMAT,
        string $now = 'now'
    ): string {
        $appTz = self::getAppTimezone();
        $tz    = new DateTimeZone($appTz);

        $dt    = self::makeDateTime($datetime, $format, $appTz);
        $nowDt = $now === 'now'
            ? new DateTime('now', $tz)
            : self::makeDateTime($now, $format, $appTz);

        // $nowDt->diff($dt): positive (invert=0) when $dt is in the future
        $diff     = $nowDt->diff($dt);
        $isFuture = $diff->invert === 0;

        $parts = [];
        if ($diff->y) $parts[] = $diff->y . ' year'   . ($diff->y > 1 ? 's' : '');
        if ($diff->m) $parts[] = $diff->m . ' month'  . ($diff->m > 1 ? 's' : '');
        if ($diff->d) $parts[] = $diff->d . ' day'    . ($diff->d > 1 ? 's' : '');
        if ($diff->h) $parts[] = $diff->h . ' hour'   . ($diff->h > 1 ? 's' : '');
        if ($diff->i) $parts[] = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        if ($diff->s) $parts[] = $diff->s . ' second' . ($diff->s > 1 ? 's' : '');

        if (empty($parts)) {
            return 'just now';
        }

        if (!$full) {
            $parts = array_slice($parts, 0, 1);
        }

        $string = implode(', ', $parts);

        return $isFuture ? 'in ' . $string : $string . ' ago';
    }

    // =========================================================================
    // Validate
    // =========================================================================

    /**
     * Check if a date string is valid and matches the given format exactly.
     *
     * @param string $date    The date string to validate
     * @param string $format  Expected format (default: 'Y-m-d H:i:s')
     */
    public static function isValidDate(string $date, string $format = self::DB_FORMAT): bool
    {
        $dt = DateTime::createFromFormat($format, $date);
        return $dt !== false && $dt->format($format) === $date;
    }

    // =========================================================================
    // Info / Misc
    // =========================================================================

    /**
     * Calculate age in whole years from a birthdate to today (in the app timezone).
     *
     * @param string $birthdate  Birthdate string
     * @param string $format     Input format (default: 'Y-m-d')
     */
    public static function age(string $birthdate, string $format = 'Y-m-d'): int
    {
        $tz    = new DateTimeZone(self::getAppTimezone());
        $birth = self::makeDateTime($birthdate, $format);
        $now   = new DateTime('now', $tz);
        return (int) $birth->diff($now)->y;
    }

    /**
     * Get the full weekday name of a date (e.g. 'Monday', 'Friday').
     *
     * @param string $date    Input datetime string
     * @param string $format  Input format (default: 'Y-m-d H:i:s')
     */
    public static function getWeekday(string $date, string $format = self::DB_FORMAT): string
    {
        return self::makeDateTime($date, $format)->format('l');
    }

    /**
     * Convert a datetime string to a Unix timestamp.
     *
     * @param string $datetime  Input datetime string
     * @param string $timezone  Timezone of the input. Empty = app timezone.
     * @param string $format    Input format (default: 'Y-m-d H:i:s')
     */
    public static function toTimestamp(
        string $datetime,
        string $timezone = '',
        string $format = self::DB_FORMAT
    ): int {
        return self::makeDateTime($datetime, $format, self::resolveTimezone($timezone))->getTimestamp();
    }

    /**
     * Convert a Unix timestamp to a datetime string.
     *
     * @param int    $timestamp  Unix timestamp
     * @param string $timezone   Output timezone. Empty = app timezone.
     * @param string $format     Output format (default: 'Y-m-d H:i:s')
     */
    public static function fromTimestamp(
        int $timestamp,
        string $timezone = '',
        string $format = self::DB_FORMAT
    ): string {
        $dt = new DateTime("@{$timestamp}");
        $dt->setTimezone(new DateTimeZone(self::resolveTimezone($timezone)));
        return $dt->format($format);
    }

    /**
     * Get the DateInterval between two dates.
     * For simple numeric differences prefer diffInDays/Hours/Minutes/Seconds.
     *
     * @param string $date1     First datetime string
     * @param string $date2     Second datetime string
     * @param bool   $absolute  Whether to return absolute difference
     * @param string $format    Input format for both dates (default: 'Y-m-d H:i:s')
     * @return \DateInterval
     */
    public static function getDateDifference(
        string $date1,
        string $date2,
        bool $absolute = false,
        string $format = self::DB_FORMAT
    ): \DateInterval {
        return self::makeDateTime($date1, $format)
            ->diff(self::makeDateTime($date2, $format), $absolute);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Create a DateTime object from a string and format.
     * Optionally set a timezone on the parsed result.
     *
     * @throws \Exception If the string cannot be parsed with the given format.
     */
    private static function makeDateTime(
        string $datetime,
        string $format,
        string $timezone = ''
    ): DateTime {
        if ($timezone !== '') {
            $tz = new DateTimeZone($timezone);
            $dt = DateTime::createFromFormat($format, $datetime, $tz);
        } else {
            $dt = DateTime::createFromFormat($format, $datetime);
        }

        if ($dt === false) {
            throw new \Exception("Cannot parse '{$datetime}' with format '{$format}'.");
        }

        return $dt;
    }

    /**
     * Apply a modify() string to a datetime and return the formatted result.
     * Uses the app timezone so DST transitions are handled correctly.
     */
    private static function modify(string $date, string $modification, string $format): string
    {
        $dt = self::makeDateTime($date, $format, self::getAppTimezone());
        $dt->modify($modification);
        return $dt->format($format);
    }

    /**
     * Return $timezone if non-empty, otherwise return the app timezone.
     */
    private static function resolveTimezone(string $timezone): string
    {
        return $timezone !== '' ? $timezone : self::getAppTimezone();
    }
}
