<?php

namespace JiFramework\Tests\Unit;

use JiFramework\Core\Utilities\Str;
use JiFramework\Tests\TestCase;

class StrTest extends TestCase
{
    private Str $str;

    protected function setUp(): void
    {
        parent::setUp();
        $this->str = new Str();
    }

    // ── escape() ─────────────────────────────────────────────────────────────

    public function testEscapeString(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            $this->str->escape('<script>alert("xss")</script>')
        );
    }

    public function testEscapeArray(): void
    {
        $result = $this->str->escape(['key' => '<b>bold</b>']);
        $this->assertSame('&lt;b&gt;bold&lt;/b&gt;', $result['key']);
    }

    public function testEscapeNonStringPassthrough(): void
    {
        $this->assertSame(42, $this->str->escape(42));
    }

    // ── length() ─────────────────────────────────────────────────────────────

    public function testLengthAscii(): void
    {
        $this->assertSame(5, $this->str->length('hello'));
    }

    public function testLengthArabic(): void
    {
        $this->assertSame(5, $this->str->length('مرحبا'));
    }

    public function testLengthHindi(): void
    {
        $this->assertSame(6, $this->str->length('नमस्ते'));
    }

    public function testLengthEmpty(): void
    {
        $this->assertSame(0, $this->str->length(''));
    }

    // ── wordCount() ──────────────────────────────────────────────────────────

    public function testWordCountEnglish(): void
    {
        $this->assertSame(3, $this->str->wordCount('hello world foo'));
    }

    public function testWordCountArabic(): void
    {
        $this->assertSame(2, $this->str->wordCount('مرحبا بالعالم'));
    }

    public function testWordCountExtraSpaces(): void
    {
        $this->assertSame(2, $this->str->wordCount('  a  b  '));
    }

    // ── countOccurrences() ───────────────────────────────────────────────────

    public function testCountOccurrences(): void
    {
        $this->assertSame(3, $this->str->countOccurrences('ababab', 'ab'));
    }

    public function testCountOccurrencesEmptyNeedle(): void
    {
        $this->assertSame(0, $this->str->countOccurrences('hello', ''));
    }

    public function testCountOccurrencesArabic(): void
    {
        $this->assertSame(2, $this->str->countOccurrences('مرحبا مرحبا', 'مرحبا'));
    }

    // ── words() ──────────────────────────────────────────────────────────────

    public function testWordsFirst2(): void
    {
        $this->assertSame('one two', $this->str->words('one two three four', 2));
    }

    public function testWordsFewerThanCount(): void
    {
        $this->assertSame('hello', $this->str->words('hello', 5));
    }

    public function testWordsArabic(): void
    {
        $this->assertSame('أ ب ج', $this->str->words('أ ب ج د هـ', 3));
    }

    // ── plainText() ──────────────────────────────────────────────────────────

    public function testPlainTextStripTags(): void
    {
        $this->assertSame('Hello world foo', $this->str->plainText('<b>Hello</b> <i>world foo bar</i>', 3));
    }

    public function testPlainTextDecodeEntities(): void
    {
        $this->assertSame('& <test>', $this->str->plainText('&amp; &lt;test&gt;', 2));
    }

    // ── before() / after() / between() ───────────────────────────────────────

    public function testBefore(): void
    {
        $this->assertSame('hello', $this->str->before('hello-world', '-'));
    }

    public function testBeforeNotFound(): void
    {
        $this->assertSame('hello', $this->str->before('hello', '-'));
    }

    public function testAfter(): void
    {
        $this->assertSame('world', $this->str->after('hello-world', '-'));
    }

    public function testAfterNotFound(): void
    {
        $this->assertSame('hello', $this->str->after('hello', '-'));
    }

    public function testBetween(): void
    {
        $this->assertSame('foo', $this->str->between('[foo]', '[', ']'));
    }

    public function testBetweenMissingEnd(): void
    {
        $this->assertSame('', $this->str->between('[foo', '[', ']'));
    }

    public function testBetweenArabic(): void
    {
        $this->assertSame('مرحبا', $this->str->between('(مرحبا)', '(', ')'));
    }

    // ── extractInteger() ─────────────────────────────────────────────────────

    public function testExtractInteger(): void
    {
        $this->assertSame(123456, $this->str->extractInteger('abc123def456'));
    }

    public function testExtractIntegerOrderId(): void
    {
        $this->assertSame(7, $this->str->extractInteger('Order #007'));
    }

    public function testExtractIntegerNoDigits(): void
    {
        $this->assertSame(0, $this->str->extractInteger('no digits'));
    }

    // ── truncate() ───────────────────────────────────────────────────────────

    public function testTruncate(): void
    {
        $this->assertSame('Hell...', $this->str->truncate('Hello World', 7));
    }

    public function testTruncateShorterThanLimit(): void
    {
        $this->assertSame('Hi', $this->str->truncate('Hi', 10));
    }

    public function testTruncateArabicCharCount(): void
    {
        $result = $this->str->truncate('مرحبا بالعالم', 8);
        $this->assertSame(8, $this->str->length($result));
    }

    // ── mask() ───────────────────────────────────────────────────────────────

    public function testMaskCreditCard(): void
    {
        $this->assertSame('XXXXXXXXXXXX3456', $this->str->mask('1234567890123456', 4));
    }

    public function testMaskArabic(): void
    {
        $this->assertSame('XXXبا', $this->str->mask('مرحبا', 2));
    }

    public function testMaskUnmaskedGreaterThanLength(): void
    {
        $this->assertSame('hi', $this->str->mask('hi', 5));
    }

    // ── zeroPad() ────────────────────────────────────────────────────────────

    public function testZeroPad(): void
    {
        $this->assertSame('00042', $this->str->zeroPad(42, 5));
    }

    public function testZeroPadExactLength(): void
    {
        $this->assertSame('12345', $this->str->zeroPad(12345, 5));
    }

    // ── reverse() ────────────────────────────────────────────────────────────

    public function testReverseAscii(): void
    {
        $this->assertSame('olleh', $this->str->reverse('hello'));
    }

    public function testReverseArabic(): void
    {
        $this->assertSame('ابحرم', $this->str->reverse('مرحبا'));
    }

    // ── replaceFirst() / replaceLast() ───────────────────────────────────────

    public function testReplaceFirst(): void
    {
        $this->assertSame('Xaa', $this->str->replaceFirst('a', 'X', 'aaa'));
    }

    public function testReplaceLast(): void
    {
        $this->assertSame('aaX', $this->str->replaceLast('a', 'X', 'aaa'));
    }

    public function testReplaceFirstArabic(): void
    {
        $this->assertSame('Xبأ', $this->str->replaceFirst('أ', 'X', 'أبأ'));
    }

    public function testReplaceLastArabic(): void
    {
        $this->assertSame('أبX', $this->str->replaceLast('أ', 'X', 'أبأ'));
    }

    public function testReplaceFirstNotFound(): void
    {
        $this->assertSame('hello', $this->str->replaceFirst('z', 'X', 'hello'));
    }

    // ── Case conversion ──────────────────────────────────────────────────────

    public function testToCamelCaseSpaces(): void
    {
        $this->assertSame('helloWorldFoo', $this->str->toCamelCase('hello world foo'));
    }

    public function testToCamelCaseHyphens(): void
    {
        $this->assertSame('myClassName', $this->str->toCamelCase('my-class-name'));
    }

    public function testToCamelCaseUnderscores(): void
    {
        $this->assertSame('helloWorldFoo', $this->str->toCamelCase('hello_world_foo'));
    }

    public function testToPascalCase(): void
    {
        $this->assertSame('HelloWorld', $this->str->toPascalCase('hello world'));
    }

    public function testToSnakeCaseCamel(): void
    {
        $this->assertSame('hello_world_foo', $this->str->toSnakeCase('helloWorldFoo'));
    }

    public function testToSnakeCaseAcronym(): void
    {
        $this->assertSame('html_parser', $this->str->toSnakeCase('HTMLParser'));
    }

    public function testToSnakeCaseSpaces(): void
    {
        $this->assertSame('hello_world', $this->str->toSnakeCase('Hello World'));
    }

    public function testToKebabCase(): void
    {
        $this->assertSame('hello-world-foo', $this->str->toKebabCase('Hello World Foo'));
    }

    public function testToTitleCase(): void
    {
        $this->assertSame('Hello World', $this->str->toTitleCase('hello world'));
    }

    // ── contains() / startsWith() / endsWith() ───────────────────────────────

    public function testContainsFound(): void
    {
        $this->assertTrue($this->str->contains('hello world', 'world'));
    }

    public function testContainsNotFound(): void
    {
        $this->assertFalse($this->str->contains('hello', 'xyz'));
    }

    public function testContainsArabic(): void
    {
        $this->assertTrue($this->str->contains('مرحبا بالعالم', 'بالعالم'));
    }

    public function testStartsWith(): void
    {
        $this->assertTrue($this->str->startsWith('hello world', 'hello'));
        $this->assertFalse($this->str->startsWith('hello', 'world'));
    }

    public function testStartsWithArabic(): void
    {
        $this->assertTrue($this->str->startsWith('مرحبا', 'مر'));
    }

    public function testEndsWith(): void
    {
        $this->assertTrue($this->str->endsWith('hello world', 'world'));
        $this->assertTrue($this->str->endsWith('hello', ''));
    }

    public function testEndsWithArabic(): void
    {
        $this->assertTrue($this->str->endsWith('مرحبا', 'با'));
    }

    // ── isJson() ─────────────────────────────────────────────────────────────

    public function testIsJsonValidObject(): void
    {
        $this->assertTrue($this->str->isJson('{"key":"value"}'));
    }

    public function testIsJsonValidArray(): void
    {
        $this->assertTrue($this->str->isJson('[1,2,3]'));
    }

    public function testIsJsonInvalid(): void
    {
        $this->assertFalse($this->str->isJson('{bad json}'));
    }

    public function testIsJsonPlainString(): void
    {
        $this->assertFalse($this->str->isJson('hello world'));
    }

    // ── slugify() ────────────────────────────────────────────────────────────

    public function testSlugifyBasic(): void
    {
        $this->assertSame('hello-world', $this->str->slugify('Hello World!'));
    }

    public function testSlugifyCollapse(): void
    {
        $this->assertSame('foo-bar-baz', $this->str->slugify('foo   bar---baz'));
    }

    // ── formatCurrency() ─────────────────────────────────────────────────────

    public function testFormatCurrencyUsd(): void
    {
        $this->assertSame('$1,234.50', $this->str->formatCurrency(1234.5, 2, '$'));
    }

    public function testFormatCurrencyNoSymbol(): void
    {
        $this->assertSame('9.99', $this->str->formatCurrency(9.99));
    }

    // ── randomInt() ──────────────────────────────────────────────────────────

    public function testRandomIntInRange(): void
    {
        $v = $this->str->randomInt(1, 100);
        $this->assertIsInt($v);
        $this->assertGreaterThanOrEqual(1, $v);
        $this->assertLessThanOrEqual(100, $v);
    }

    // ── randomString() ───────────────────────────────────────────────────────

    public function testRandomStringLength(): void
    {
        $s = $this->str->randomString(16);
        $this->assertSame(16, strlen($s));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $s);
    }

    public function testRandomStringUnique(): void
    {
        $this->assertNotSame($this->str->randomString(16), $this->str->randomString(16));
    }

    // ── randomToken() ────────────────────────────────────────────────────────

    public function testRandomTokenLength(): void
    {
        $t = $this->str->randomToken(16);
        $this->assertSame(32, strlen($t)); // 16 bytes = 32 hex chars
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $t);
    }

    public function testRandomTokenUnique(): void
    {
        $this->assertNotSame($this->str->randomToken(16), $this->str->randomToken(16));
    }

    // ── randomColor() ────────────────────────────────────────────────────────

    public function testRandomColor(): void
    {
        $c = $this->str->randomColor();
        $this->assertStringStartsWith('#', $c);
        $this->assertSame(7, strlen($c));
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $c);
    }
}
