<?php

namespace JiFramework\Tests\Unit;

use JiFramework\Core\Security\Encryption;
use JiFramework\Tests\TestCase;

class EncryptionTest extends TestCase
{
    private Encryption $enc;
    private string $key;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enc = new Encryption();
        $this->key = $this->enc->generateKey();
    }

    // ── generateKey() ────────────────────────────────────────────────────────

    public function testGenerateKeyIs64HexChars(): void
    {
        $this->assertSame(64, strlen($this->key));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $this->key);
    }

    public function testGenerateKeyIsUnique(): void
    {
        $this->assertNotSame($this->key, $this->enc->generateKey());
    }

    // ── encrypt() / decrypt() ─────────────────────────────────────────────────

    public function testEncryptDecryptRoundtrip(): void
    {
        $plaintext  = 'Hello, JiFramework!';
        $ciphertext = $this->enc->encrypt($plaintext, $this->key);
        $decrypted  = $this->enc->decrypt($ciphertext, $this->key);
        $this->assertSame($plaintext, $decrypted);
    }

    public function testEncryptProducesDifferentOutputEachTime(): void
    {
        $plaintext = 'same input';
        $c1 = $this->enc->encrypt($plaintext, $this->key);
        $c2 = $this->enc->encrypt($plaintext, $this->key);
        $this->assertNotSame($c1, $c2); // fresh nonce each time
    }

    public function testDecryptReturnsFalseOnWrongKey(): void
    {
        $ciphertext = $this->enc->encrypt('secret', $this->key);
        $wrongKey   = $this->enc->generateKey();
        $this->assertFalse($this->enc->decrypt($ciphertext, $wrongKey));
    }

    public function testDecryptReturnsFalseOnTamperedData(): void
    {
        $ciphertext = $this->enc->encrypt('secret', $this->key);
        $tampered   = base64_encode(str_repeat('x', 50));
        $this->assertFalse($this->enc->decrypt($tampered, $this->key));
    }

    public function testDecryptReturnsFalseOnInvalidBase64(): void
    {
        $this->assertFalse($this->enc->decrypt('not-valid-base64!!!', $this->key));
    }

    public function testEncryptThrowsOnInvalidKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->enc->encrypt('data', 'tooshort');
    }

    public function testEncryptEmptyString(): void
    {
        $ciphertext = $this->enc->encrypt('', $this->key);
        $decrypted  = $this->enc->decrypt($ciphertext, $this->key);
        $this->assertSame('', $decrypted);
    }

    public function testEncryptUnicodeData(): void
    {
        $plaintext  = 'مرحبا بالعالم — Hello World — नमस्ते';
        $ciphertext = $this->enc->encrypt($plaintext, $this->key);
        $this->assertSame($plaintext, $this->enc->decrypt($ciphertext, $this->key));
    }

    // ── encryptWithPassword() / decryptWithPassword() ─────────────────────────

    public function testEncryptDecryptWithPassword(): void
    {
        $plaintext = 'password-based encryption test';
        $password  = 'my-super-secret-password';
        $ciphertext = $this->enc->encryptWithPassword($plaintext, $password);
        $decrypted  = $this->enc->decryptWithPassword($ciphertext, $password);
        $this->assertSame($plaintext, $decrypted);
    }

    public function testDecryptWithPasswordReturnsFalseOnWrongPassword(): void
    {
        $ciphertext = $this->enc->encryptWithPassword('data', 'correct');
        $this->assertFalse($this->enc->decryptWithPassword($ciphertext, 'wrong'));
    }

    // ── hashPassword() / verifyPassword() ────────────────────────────────────

    public function testHashAndVerifyPassword(): void
    {
        $hash = $this->enc->hashPassword('my-password');
        $this->assertTrue($this->enc->verifyPassword('my-password', $hash));
        $this->assertFalse($this->enc->verifyPassword('wrong-password', $hash));
    }

    public function testHashPasswordProducesDifferentHashes(): void
    {
        $h1 = $this->enc->hashPassword('same');
        $h2 = $this->enc->hashPassword('same');
        $this->assertNotSame($h1, $h2); // bcrypt salt
    }

    // ── randomBytes() / randomString() ───────────────────────────────────────

    public function testRandomBytesLength(): void
    {
        // randomBytes(n) returns bin2hex output — 16 raw bytes = 32 hex chars
        $bytes = $this->enc->randomBytes(16);
        $this->assertSame(32, strlen($bytes));
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $bytes);
    }

    public function testRandomBytesIsUnique(): void
    {
        $this->assertNotSame($this->enc->randomBytes(16), $this->enc->randomBytes(16));
    }

    public function testRandomStringLength(): void
    {
        $s = $this->enc->randomString(16);
        $this->assertSame(16, strlen($s));
    }

    public function testRandomStringIsUnique(): void
    {
        $this->assertNotSame($this->enc->randomString(16), $this->enc->randomString(16));
    }
}
