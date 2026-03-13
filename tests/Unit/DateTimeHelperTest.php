<?php

namespace JiFramework\Tests\Unit;

use JiFramework\Core\Utilities\DateTimeHelper;
use JiFramework\Tests\TestCase;

/**
 * DateTimeHelper is fully static — no App boot needed.
 * Methods default to 'Y-m-d H:i:s' format; date-only tests pass 'Y-m-d' explicitly.
 */
class DateTimeHelperTest extends TestCase
{
    private DateTimeHelper $dt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dt = new DateTimeHelper();
    }

    // ── Current time getters ─────────────────────────────────────────────────

    public function testNowReturnsFullDatetime(): void
    {
        $now = $this->dt->now();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $now);
    }

    public function testTodayReturnsDate(): void
    {
        $this->assertSame(date('Y-m-d'), $this->dt->today());
    }

    public function testTomorrow(): void
    {
        $this->assertSame(date('Y-m-d', strtotime('+1 day')), $this->dt->tomorrow());
    }

    public function testYesterday(): void
    {
        $this->assertSame(date('Y-m-d', strtotime('-1 day')), $this->dt->yesterday());
    }

    // ── format() ─────────────────────────────────────────────────────────────
    // Signature: format(string $targetFormat, string $datetime, string $sourceFormat = 'Y-m-d H:i:s')

    public function testFormatCustom(): void
    {
        $result = $this->dt->format('d/m/Y', '2024-06-15 10:30:00');
        $this->assertSame('15/06/2024', $result);
    }

    public function testFormatDateOnly(): void
    {
        $result = $this->dt->format('d M Y', '2024-06-15', 'Y-m-d');
        $this->assertSame('15 Jun 2024', $result);
    }

    public function testFormatForDisplay(): void
    {
        $result = $this->dt->formatForDisplay('2024-06-15 10:30:00');
        $this->assertStringContainsString('2024', $result);
        $this->assertStringContainsString('Jun', $result);
    }

    // ── Timezone conversion ──────────────────────────────────────────────────

    public function testConvertTimezone(): void
    {
        $result = $this->dt->convertTimezone('2024-01-01 12:00:00', 'UTC', 'America/New_York');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    public function testToUtcAndFromUtc(): void
    {
        $local     = '2024-06-15 18:00:00';
        $utc       = $this->dt->toUtc($local, 'Asia/Dhaka');
        $roundtrip = $this->dt->fromUtc($utc, 'Asia/Dhaka');
        $this->assertSame($local, $roundtrip);
    }

    // ── Database helpers ─────────────────────────────────────────────────────
    // forDatabase($datetime, $fromTimezone = '')  — input: Y-m-d H:i:s

    public function testForDatabase(): void
    {
        $result = $this->dt->forDatabase('2024-06-15 10:00:00');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    // fromDatabase($datetime, $toTimezone = '', $format = 'Y-m-d H:i:s')

    public function testFromDatabase(): void
    {
        $result = $this->dt->fromDatabase('2024-06-15 10:30:00', '', 'd M Y');
        $this->assertSame('15 Jun 2024', $result);
    }

    // ── Arithmetic — pass 'Y-m-d H:i:s' (default) ────────────────────────────

    public function testAddDays(): void
    {
        $result = $this->dt->addDays('2024-01-01 00:00:00', 5);
        $this->assertStringStartsWith('2024-01-06', $result);
    }

    public function testAddDaysDateOnlyFormat(): void
    {
        $result = $this->dt->addDays('2024-01-01', 5, 'Y-m-d');
        $this->assertSame('2024-01-06', $result);
    }

    public function testSubtractDays(): void
    {
        $result = $this->dt->subtractDays('2024-01-10', 5, 'Y-m-d');
        $this->assertSame('2024-01-05', $result);
    }

    public function testAddMonths(): void
    {
        $result = $this->dt->addMonths('2024-01-15', 2, 'Y-m-d');
        $this->assertSame('2024-03-15', $result);
    }

    public function testAddYears(): void
    {
        $result = $this->dt->addYears('2024-01-15', 1, 'Y-m-d');
        $this->assertSame('2025-01-15', $result);
    }

    public function testAddHours(): void
    {
        $result = $this->dt->addHours('2024-01-01 10:00:00', 3);
        $this->assertStringContainsString('13:00:00', $result);
    }

    // ── Period boundaries ────────────────────────────────────────────────────

    public function testStartOfDay(): void
    {
        $result = $this->dt->startOfDay('2024-06-15 14:30:00');
        $this->assertStringEndsWith('00:00:00', $result);
    }

    public function testEndOfDay(): void
    {
        $result = $this->dt->endOfDay('2024-06-15 14:30:00');
        $this->assertStringEndsWith('23:59:59', $result);
    }

    public function testStartOfMonth(): void
    {
        $result = $this->dt->startOfMonth('2024-06-15', 'Y-m-d');
        $this->assertStringStartsWith('2024-06-01', $result);
    }

    public function testEndOfMonth(): void
    {
        $result = $this->dt->endOfMonth('2024-06-15', 'Y-m-d');
        $this->assertStringStartsWith('2024-06-30', $result);
    }

    // ── Comparison — pass 'Y-m-d' format explicitly ──────────────────────────

    public function testIsPast(): void
    {
        $this->assertTrue($this->dt->isPast('2000-01-01 00:00:00'));
        $this->assertFalse($this->dt->isPast('2099-01-01 00:00:00'));
    }

    public function testIsFuture(): void
    {
        $this->assertFalse($this->dt->isFuture('2000-01-01 00:00:00'));
        $this->assertTrue($this->dt->isFuture('2099-01-01 00:00:00'));
    }

    public function testIsBefore(): void
    {
        $this->assertTrue($this->dt->isBefore('2020-01-01 00:00:00', '2021-01-01 00:00:00'));
        $this->assertFalse($this->dt->isBefore('2021-01-01 00:00:00', '2020-01-01 00:00:00'));
    }

    public function testIsAfter(): void
    {
        $this->assertTrue($this->dt->isAfter('2021-01-01 00:00:00', '2020-01-01 00:00:00'));
        $this->assertFalse($this->dt->isAfter('2020-01-01 00:00:00', '2021-01-01 00:00:00'));
    }

    public function testIsSameDay(): void
    {
        $this->assertTrue($this->dt->isSameDay('2024-06-15 10:00:00', '2024-06-15 22:00:00'));
        $this->assertFalse($this->dt->isSameDay('2024-06-15 00:00:00', '2024-06-16 00:00:00'));
    }

    public function testIsBetween(): void
    {
        $this->assertTrue($this->dt->isBetween('2024-06-15', '2024-01-01', '2024-12-31', 'Y-m-d'));
        $this->assertFalse($this->dt->isBetween('2025-01-01', '2024-01-01', '2024-12-31', 'Y-m-d'));
    }

    public function testIsToday(): void
    {
        $this->assertTrue($this->dt->isToday(date('Y-m-d H:i:s')));
        $this->assertFalse($this->dt->isToday('2000-01-01 00:00:00'));
    }

    public function testIsWeekend(): void
    {
        // 2024-06-15 is Saturday
        $this->assertTrue($this->dt->isWeekend('2024-06-15', 'Y-m-d'));
        // 2024-06-17 is Monday
        $this->assertFalse($this->dt->isWeekend('2024-06-17', 'Y-m-d'));
    }

    public function testIsWeekday(): void
    {
        $this->assertFalse($this->dt->isWeekday('2024-06-15', 'Y-m-d')); // Saturday
        $this->assertTrue($this->dt->isWeekday('2024-06-17', 'Y-m-d'));  // Monday
    }

    // ── Diff ─────────────────────────────────────────────────────────────────

    public function testDiffInDays(): void
    {
        // diffInDays($date1, $date2, $absolute = true, $format = 'Y-m-d H:i:s')
        $diff = $this->dt->diffInDays('2024-01-01', '2024-01-11', true, 'Y-m-d');
        $this->assertSame(10, $diff);
    }

    public function testDiffInHours(): void
    {
        $diff = $this->dt->diffInHours('2024-01-01 00:00:00', '2024-01-01 05:00:00');
        $this->assertSame(5, $diff);
    }

    public function testDiffInMinutes(): void
    {
        $diff = $this->dt->diffInMinutes('2024-01-01 10:00:00', '2024-01-01 10:30:00');
        $this->assertSame(30, $diff);
    }

    // ── Human readable ───────────────────────────────────────────────────────

    public function testGetTimeElapsedStringReturnsString(): void
    {
        $result = $this->dt->getTimeElapsedString(date('Y-m-d H:i:s'));
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGetTimeElapsedStringPast(): void
    {
        $past   = date('Y-m-d H:i:s', strtotime('-2 days'));
        $result = $this->dt->getTimeElapsedString($past);
        $this->assertStringContainsString('day', $result);
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function testIsValidDateWithFullFormat(): void
    {
        $this->assertTrue($this->dt->isValidDate('2024-06-15 10:30:00'));
        $this->assertFalse($this->dt->isValidDate('not-a-date'));
    }

    public function testIsValidDateWithDateOnlyFormat(): void
    {
        $this->assertTrue($this->dt->isValidDate('2024-06-15', 'Y-m-d'));
        $this->assertFalse($this->dt->isValidDate('2024-13-01', 'Y-m-d')); // month 13
    }

    // ── Info ─────────────────────────────────────────────────────────────────

    public function testAge(): void
    {
        $dob = date('Y-m-d', strtotime('-30 years'));
        $this->assertSame(30, $this->dt->age($dob));
    }

    public function testGetWeekday(): void
    {
        // 2024-06-17 is Monday
        $this->assertSame('Monday', $this->dt->getWeekday('2024-06-17', 'Y-m-d'));
    }

    public function testToTimestampAndFromTimestamp(): void
    {
        // toTimestamp($datetime, $timezone = '', $format = 'Y-m-d H:i:s')
        // fromTimestamp($timestamp, $timezone = '', $format = 'Y-m-d H:i:s')
        $ts     = $this->dt->toTimestamp('2024-06-15 10:00:00', 'UTC');
        $result = $this->dt->fromTimestamp($ts, 'UTC');
        $this->assertSame('2024-06-15 10:00:00', $result);
    }
}
