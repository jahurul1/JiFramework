<?php

namespace JiFramework\Tests\Unit;

use JiFramework\Core\Utilities\Validator;
use JiFramework\Exceptions\ValidationException;
use JiFramework\Tests\TestCase;

/**
 * Validator API:
 *   make($data, $rules, $messages)  — validates a field map, fluent
 *   check($value, $rules)           — validate a SINGLE value, returns bool
 *   checkOrFail($value, $rules)     — like check(), throws on failure
 */
class ValidatorTest extends TestCase
{
    private Validator $v;

    protected function setUp(): void
    {
        parent::setUp();
        $this->v = new Validator();
    }

    // ── make() / passes() / fails() / errors() ───────────────────────────────

    public function testPassesOnValidData(): void
    {
        $this->v->make(['email' => 'good@test.com'], ['email' => 'required|email']);
        $this->assertTrue($this->v->passes());
        $this->assertFalse($this->v->fails());
        $this->assertEmpty($this->v->errors());
    }

    public function testFailsOnInvalidData(): void
    {
        $this->v->make(['email' => 'not-an-email'], ['email' => 'required|email']);
        $this->assertFalse($this->v->passes());
        $this->assertTrue($this->v->fails());
        $this->assertArrayHasKey('email', $this->v->errors());
    }

    // ── first() ──────────────────────────────────────────────────────────────

    public function testFirstReturnsFirstErrorForField(): void
    {
        $this->v->make(['name' => ''], ['name' => 'required|min:3']);
        $first = $this->v->first('name');
        $this->assertIsString($first);
        $this->assertNotEmpty($first);
    }

    public function testFirstReturnsEmptyStringForValidField(): void
    {
        $this->v->make(['name' => 'John'], ['name' => 'required']);
        $this->assertSame('', $this->v->first('name'));
    }

    public function testFirstWithoutArgumentReturnsMap(): void
    {
        $this->v->make(['a' => '', 'b' => ''], ['a' => 'required', 'b' => 'required']);
        $firsts = $this->v->first();
        $this->assertIsArray($firsts);
        $this->assertArrayHasKey('a', $firsts);
        $this->assertArrayHasKey('b', $firsts);
    }

    // ── throw() ──────────────────────────────────────────────────────────────

    public function testThrowOnFailure(): void
    {
        $this->expectException(ValidationException::class);
        $this->v->make(['x' => ''], ['x' => 'required'])->throw();
    }

    public function testThrowDoesNothingOnSuccess(): void
    {
        $this->v->make(['x' => 'ok'], ['x' => 'required'])->throw();
        $this->assertTrue(true); // no exception
    }

    // ── check($value, $rules) ────────────────────────────────────────────────
    // check() validates a SINGLE value against a rule string

    public function testCheckReturnsTrue(): void
    {
        $this->assertTrue($this->v->check(25, 'required|integer'));
    }

    public function testCheckReturnsFalse(): void
    {
        $this->assertFalse($this->v->check('', 'required'));
    }

    // ── checkOrFail() ────────────────────────────────────────────────────────

    public function testCheckOrFail(): void
    {
        $this->expectException(ValidationException::class);
        $this->v->checkOrFail(5, 'min:18');
    }

    // ── stopOnFirstFailure() ─────────────────────────────────────────────────

    public function testStopOnFirstFailure(): void
    {
        $this->v->stopOnFirstFailure()->make(['x' => ''], ['x' => 'required|min:3|max:10']);
        $errors = $this->v->errors();
        $this->assertCount(1, $errors['x'] ?? []);
    }

    // ── addMessages() ────────────────────────────────────────────────────────

    public function testCustomMessages(): void
    {
        $this->v->make(
            ['email' => ''],
            ['email' => 'required'],
            ['email.required' => 'Email is mandatory']
        );
        $this->assertStringContainsString('mandatory', $this->v->first('email'));
    }

    // ── addRule() ────────────────────────────────────────────────────────────

    public function testAddCustomRule(): void
    {
        // addRule() callback receives ($field, $value, $params, $data)
        $this->v->addRule('mustBeFoo', function ($field, $value) {
            return $value === 'foo';
        });

        $this->assertFalse($this->v->check('bar', 'mustBeFoo'));
        $this->assertTrue($this->v->check('foo', 'mustBeFoo'));
    }

    // ── Built-in rules via check($value, $rule) ───────────────────────────────

    public function testRequiredRule(): void
    {
        $this->assertFalse($this->v->check('', 'required'));
        $this->assertFalse($this->v->check(null, 'required'));
        $this->assertTrue($this->v->check('0', 'required'));
        $this->assertTrue($this->v->check('hello', 'required'));
    }

    public function testEmailRule(): void
    {
        $this->assertTrue($this->v->check('a@b.com', 'email'));
        $this->assertFalse($this->v->check('not-email', 'email'));
    }

    public function testMinMaxStringLength(): void
    {
        $this->assertTrue($this->v->check('hello', 'min:3|max:10'));
        $this->assertFalse($this->v->check('hi', 'min:3'));
        $this->assertFalse($this->v->check('toolongstring', 'max:5'));
    }

    public function testIntegerRule(): void
    {
        $this->assertTrue($this->v->check(42, 'integer'));
        $this->assertTrue($this->v->check('42', 'integer'));
        $this->assertFalse($this->v->check('3.14', 'integer'));
    }

    public function testNumericRule(): void
    {
        $this->assertTrue($this->v->check('3.14', 'numeric'));
        $this->assertFalse($this->v->check('abc', 'numeric'));
    }

    public function testAlphaRule(): void
    {
        $this->assertTrue($this->v->check('hello', 'alpha'));
        $this->assertFalse($this->v->check('hel1o', 'alpha'));
    }

    public function testAlphaNumRule(): void
    {
        // rule name is 'alphaNum' (camelCase maps to validateAlphaNum)
        $this->assertTrue($this->v->check('hello1', 'alphaNum'));
        $this->assertFalse($this->v->check('hi!', 'alphaNum'));
    }

    public function testUrlRule(): void
    {
        $this->assertTrue($this->v->check('https://example.com', 'url'));
        $this->assertFalse($this->v->check('not a url', 'url'));
    }

    public function testInRule(): void
    {
        $this->assertTrue($this->v->check('a', 'in:a,b,c'));
        $this->assertFalse($this->v->check('d', 'in:a,b,c'));
    }

    public function testNotInRule(): void
    {
        // rule name 'notIn' (camelCase)
        $this->assertTrue($this->v->check('d', 'notIn:a,b,c'));
        $this->assertFalse($this->v->check('a', 'notIn:a,b,c'));
    }

    public function testBooleanRule(): void
    {
        // Validator accepts: true, false, 1, 0, '1', '0', 'true', 'false', 'on', 'off', 'yes', 'no'
        foreach ([true, false, 1, 0, '1', '0', 'yes', 'no', 'on', 'off', 'true', 'false'] as $val) {
            $this->assertTrue($this->v->check($val, 'boolean'), "Expected boolean rule to pass for: " . var_export($val, true));
        }
        $this->assertFalse($this->v->check('maybe', 'boolean'));
        $this->assertFalse($this->v->check('invalid', 'boolean'));
    }

    public function testDateRule(): void
    {
        $this->assertTrue($this->v->check('2024-01-15 00:00:00', 'date'));
        $this->assertFalse($this->v->check('not-a-date', 'date'));
    }

    public function testNullableSkipsOtherRules(): void
    {
        // Empty value with nullable should skip email rule
        $this->assertTrue($this->v->check('', 'nullable|email'));
        $this->assertTrue($this->v->check(null, 'nullable|min:5'));
    }

    // ── Dot notation ─────────────────────────────────────────────────────────

    public function testDotNotation(): void
    {
        $this->v->make(['user' => ['name' => '']], ['user.name' => 'required']);
        $this->assertTrue($this->v->fails());

        $this->v->make(['user' => ['name' => 'Alice']], ['user.name' => 'required']);
        $this->assertTrue($this->v->passes());
    }

    // ── confirmed / same / different (multi-field, use make()) ───────────────

    public function testConfirmedRule(): void
    {
        $this->v->make(
            ['password' => 'secret', 'password_confirmation' => 'secret'],
            ['password' => 'confirmed']
        );
        $this->assertTrue($this->v->passes());

        $this->v->make(
            ['password' => 'secret', 'password_confirmation' => 'wrong'],
            ['password' => 'confirmed']
        );
        $this->assertTrue($this->v->fails());
    }

    public function testSameRule(): void
    {
        $this->v->make(['a' => 'x', 'b' => 'x'], ['a' => 'same:b']);
        $this->assertTrue($this->v->passes());

        $this->v->make(['a' => 'x', 'b' => 'y'], ['a' => 'same:b']);
        $this->assertTrue($this->v->fails());
    }

    public function testDifferentRule(): void
    {
        $this->v->make(['a' => 'x', 'b' => 'y'], ['a' => 'different:b']);
        $this->assertTrue($this->v->passes());

        $this->v->make(['a' => 'x', 'b' => 'x'], ['a' => 'different:b']);
        $this->assertTrue($this->v->fails());
    }
}
