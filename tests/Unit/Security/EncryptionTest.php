<?php
/**
 * Test case for the Encryption class in the Unit directory
 */

class UnitEncryptionTest extends TestCase
{
    /**
     * @var \JIFramework\Core\Security\Encryption
     */
    private $encryption;
    
    /**
     * Set up the test environment
     */
    public function setUp()
    {
        parent::setUp();
        
        // Create a new encryption instance
        $this->encryption = new \JIFramework\Core\Security\Encryption();
    }
    
    /**
     * Test generating an encryption key
     */
    public function testGenerateKey()
    {
        // Test with default length
        $key = $this->encryption->generateKey();
        $this->assertEquals(64, strlen($key), 'Key should be 64 characters in hex (32 bytes)');
        $this->assertTrue(ctype_xdigit($key), 'Key should contain only hexadecimal characters');
        
        // Test with custom length
        $customLengthKey = $this->encryption->generateKey(16);
        $this->assertEquals(32, strlen($customLengthKey), 'Key should be 32 characters in hex (16 bytes)');
        $this->assertTrue(ctype_xdigit($customLengthKey), 'Key should contain only hexadecimal characters');
    }
    
    /**
     * Test encrypting and decrypting data
     */
    public function testEncryptAndDecrypt()
    {
        $plaintext = 'This is a secret message';
        $key = $this->encryption->generateKey();
        
        // Encrypt with auto-generated IV
        $ciphertext = $this->encryption->encrypt($plaintext, $key);
        $this->assertTrue($plaintext != $ciphertext, 'Encrypted text should not match plaintext');
        
        // Decrypt
        $decrypted = $this->encryption->decrypt($ciphertext, $key);
        $this->assertEquals($plaintext, $decrypted, 'Decrypted text should match original plaintext');
    }
    
    /**
     * Test that decryption fails with wrong key
     */
    public function testDecryptionWithWrongKey()
    {
        $plaintext = 'This is a secret message';
        $key = $this->encryption->generateKey();
        $wrongKey = $this->encryption->generateKey();
        
        // Encrypt
        $ciphertext = $this->encryption->encrypt($plaintext, $key);
        
        // Attempt to decrypt with wrong key
        $decrypted = $this->encryption->decrypt($ciphertext, $wrongKey);
        $this->assertFalse($decrypted, 'Decryption with wrong key should fail');
    }
    
    /**
     * Test generating initialization vector
     */
    public function testGenerateInitializationVector()
    {
        // Test default cipher method
        $iv = $this->encryption->generateInitializationVector();
        $expectedLength = bin2hex(openssl_cipher_iv_length('aes-256-cbc')); // Length in hex
        $this->assertEquals(32, strlen($iv), 'IV for AES-256-CBC should be 32 characters in hex (16 bytes)');
        $this->assertTrue(ctype_xdigit($iv), 'IV should contain only hexadecimal characters');
        
        // Test custom cipher method
        $customIv = $this->encryption->generateInitializationVector('aes-128-cbc');
        $this->assertEquals(32, strlen($customIv), 'IV for AES-128-CBC should also be 32 characters in hex');
        $this->assertTrue(ctype_xdigit($customIv), 'IV should contain only hexadecimal characters');
    }
    
    /**
     * Test generating a key from a password
     */
    public function testGenerateKeyFromPassword()
    {
        $password = 'secure_password123';
        
        // Test with default parameters
        $result = $this->encryption->generateKeyFromPassword($password);
        $this->assertTrue(isset($result['key']), 'Result should contain a key');
        $this->assertTrue(isset($result['salt']), 'Result should contain a salt');
        $this->assertEquals(64, strlen($result['key']), 'Key should be 64 characters in hex (32 bytes)');
        $this->assertTrue(ctype_xdigit($result['key']), 'Key should contain only hexadecimal characters');
        
        // Test with custom salt
        $customSalt = bin2hex(random_bytes(16));
        $result2 = $this->encryption->generateKeyFromPassword($password, $customSalt);
        $this->assertEquals($customSalt, $result2['salt'], 'Salt should match provided value');
        
        // Test that same password and salt produce the same key
        $result3 = $this->encryption->generateKeyFromPassword($password, $customSalt);
        $this->assertEquals($result2['key'], $result3['key'], 'Same password and salt should produce the same key');
    }
    
    /**
     * Test password hashing and verification
     */
    public function testPasswordHashingAndVerification()
    {
        $password = 'secure_password123';
        
        // Hash the password
        $hash = $this->encryption->hashPassword($password);
        $this->assertTrue($password != $hash, 'Hashed password should not match plaintext');
        
        // Verify the correct password
        $this->assertTrue($this->encryption->verifyPassword($password, $hash), 
            'Password verification should succeed with correct password');
        
        // Verify with wrong password
        $this->assertFalse($this->encryption->verifyPassword('wrong_password', $hash), 
            'Password verification should fail with incorrect password');
    }
    
    /**
     * Test encrypting and decrypting with password
     */
    public function testEncryptAndDecryptWithPassword()
    {
        $plaintext = 'This is a secret message';
        $password = 'secure_password123';
        
        // Encrypt with password
        $encoded = $this->encryption->encryptWithPassword($plaintext, $password);
        $this->assertTrue($plaintext != $encoded, 'Encrypted text should not match plaintext');
        
        // Decrypt with password
        $decrypted = $this->encryption->decryptWithPassword($encoded, $password);
        $this->assertEquals($plaintext, $decrypted, 'Decrypted text should match original plaintext');
        
        // Try decrypting with wrong password
        $decryptedWithWrongPass = $this->encryption->decryptWithPassword($encoded, 'wrong_password');
        $this->assertFalse($decryptedWithWrongPass, 'Decryption with wrong password should fail');
    }
} 


