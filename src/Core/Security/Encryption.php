<?php
namespace JiFramework\Core\Security;

class Encryption
{
    // =========================================================================
    // Internal constants
    // =========================================================================

    private const CIPHER     = 'aes-256-gcm';
    private const KEY_SIZE   = 32; // bytes — 256 bits
    private const NONCE_SIZE = 12; // bytes — 96 bits (GCM recommendation)
    private const TAG_SIZE   = 16; // bytes — 128 bits (maximum GCM tag)
    private const SALT_SIZE  = 16; // bytes — 128 bits

    // =========================================================================
    // Symmetric encryption — AES-256-GCM
    // =========================================================================

    /**
     * Encrypt plaintext using AES-256-GCM.
     *
     * A fresh random nonce is generated on every call — never reused.
     * Authentication is built into GCM: no separate HMAC required.
     *
     * @param string $plaintext Data to encrypt.
     * @param string $key       32-byte key in hexadecimal (64 hex chars). Use generateKey() to produce one.
     * @return string           Base64-encoded output: nonce[12] + tag[16] + ciphertext.
     *
     * @throws \InvalidArgumentException If the key is not a valid 64-character hex string.
     * @throws \RuntimeException         If the OpenSSL encryption call fails.
     */
    public function encrypt(string $plaintext, string $key): string
    {
        $binaryKey = $this->parseKey($key);

        return base64_encode($this->encryptRaw($plaintext, $binaryKey));
    }

    /**
     * Decrypt a ciphertext produced by encrypt().
     *
     * Returns false when the ciphertext is malformed, the key is wrong,
     * or the authentication tag does not match (tampered data).
     *
     * @param string $ciphertext Base64-encoded ciphertext from encrypt().
     * @param string $key        The same 64-character hex key used to encrypt.
     * @return string|false      Decrypted plaintext, or false on any failure.
     *
     * @throws \InvalidArgumentException If the key is not a valid 64-character hex string.
     */
    public function decrypt(string $ciphertext, string $key)
    {
        $binaryKey = $this->parseKey($key);
        $data      = base64_decode($ciphertext, true);

        if ($data === false) {
            return false;
        }

        return $this->decryptRaw($data, $binaryKey);
    }

    // =========================================================================
    // Password-based encryption — PBKDF2 + AES-256-GCM
    // =========================================================================

    /**
     * Encrypt plaintext using a human-supplied password.
     *
     * The password is stretched into a 256-bit key using PBKDF2-SHA256 with a
     * fresh random salt. The salt is embedded in the output so decryptWithPassword()
     * can re-derive the key automatically — no separate salt storage required.
     *
     * Use this when you have a password rather than a stored key.
     * Use encrypt() when you already have a proper 32-byte random key.
     *
     * @param string $plaintext  Data to encrypt.
     * @param string $password   Password of any length.
     * @param int    $iterations PBKDF2 iteration count. Higher = slower = more brute-force resistant. Default: 100,000.
     * @return string            Base64-encoded output: salt[16] + nonce[12] + tag[16] + ciphertext.
     *
     * @throws \RuntimeException If the OpenSSL encryption call fails.
     */
    public function encryptWithPassword(string $plaintext, string $password, int $iterations = 100000): string
    {
        $salt = random_bytes(self::SALT_SIZE);
        $key  = hash_pbkdf2('sha256', $password, $salt, $iterations, self::KEY_SIZE, true);

        return base64_encode($salt . $this->encryptRaw($plaintext, $key));
    }

    /**
     * Decrypt a ciphertext produced by encryptWithPassword().
     *
     * Returns false when the ciphertext is malformed, the password is wrong,
     * or the authentication tag does not match (tampered data).
     *
     * @param string $ciphertext Base64-encoded ciphertext from encryptWithPassword().
     * @param string $password   The same password used to encrypt.
     * @param int    $iterations The same PBKDF2 iteration count used to encrypt.
     * @return string|false      Decrypted plaintext, or false on any failure.
     */
    public function decryptWithPassword(string $ciphertext, string $password, int $iterations = 100000)
    {
        $data = base64_decode($ciphertext, true);

        if ($data === false) {
            return false;
        }

        // Minimum valid length: salt[16] + nonce[12] + tag[16] = 44 bytes
        if (strlen($data) < self::SALT_SIZE + self::NONCE_SIZE + self::TAG_SIZE) {
            return false;
        }

        $salt      = substr($data, 0, self::SALT_SIZE);
        $encrypted = substr($data, self::SALT_SIZE);
        $key       = hash_pbkdf2('sha256', $password, $salt, $iterations, self::KEY_SIZE, true);

        return $this->decryptRaw($encrypted, $key);
    }

    // =========================================================================
    // Key management
    // =========================================================================

    /**
     * Generate a cryptographically secure random encryption key.
     *
     * Store the result in an environment variable or secrets manager.
     * Never hardcode keys in source code.
     *
     * @return string 64-character hexadecimal string (32 bytes / 256 bits).
     */
    public function generateKey(): string
    {
        return bin2hex(random_bytes(self::KEY_SIZE));
    }

    /**
     * Derive a deterministic encryption key from a password using PBKDF2-SHA256.
     *
     * Always use a fresh random salt when deriving a new key. Store the returned
     * salt alongside the encrypted data so the key can be re-derived for decryption.
     *
     * Note: encryptWithPassword() handles all of this automatically.
     * Use generateKeyFromPassword() only when you need direct access to the derived key.
     *
     * @param string      $password   Password of any length.
     * @param string|null $salt       Salt in hexadecimal. A random 128-bit salt is generated when null.
     * @param int         $iterations PBKDF2 iteration count. Default: 100,000.
     * @return array                  ['key' => string (hex, 64 chars), 'salt' => string (hex, 32 chars)]
     */
    public function generateKeyFromPassword(string $password, ?string $salt = null, int $iterations = 100000): array
    {
        $binarySalt = ($salt === null) ? random_bytes(self::SALT_SIZE) : hex2bin($salt);
        $key        = hash_pbkdf2('sha256', $password, $binarySalt, $iterations, self::KEY_SIZE, true);

        return [
            'key'  => bin2hex($key),
            'salt' => bin2hex($binarySalt),
        ];
    }

    // =========================================================================
    // Password hashing — bcrypt
    // =========================================================================

    /**
     * Hash a password for secure storage using bcrypt (PASSWORD_DEFAULT).
     *
     * Never store plaintext passwords. Always hash before saving to the database.
     *
     * @param string $password Plaintext password.
     * @return string          Bcrypt hash, safe to store directly in the database.
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a plaintext password against a stored bcrypt hash.
     *
     * @param string $password Plaintext password to verify.
     * @param string $hash     Stored bcrypt hash.
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check whether a stored password hash needs to be upgraded.
     *
     * Call this after a successful login. If it returns true, re-hash the password
     * with hashPassword() and update the stored value. This silently upgrades hashes
     * when PHP raises the default bcrypt cost factor in a future version.
     *
     * @param string $hash Stored bcrypt hash.
     * @return bool        True when the hash should be updated.
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    // =========================================================================
    // Secure random utilities
    // =========================================================================

    /**
     * Generate cryptographically secure random bytes.
     *
     * @param int $length Number of random bytes. The returned hex string is 2× this length.
     * @return string     Hexadecimal string.
     */
    public function randomBytes(int $length): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Generate a cryptographically secure random alphanumeric string.
     *
     * Uses only URL-safe characters (A-Z, a-z, 0-9). Suitable for tokens,
     * invite codes, temporary passwords, API keys, and URL slugs.
     *
     * @param int $length Number of characters.
     * @return string
     */
    public function randomString(int $length): string
    {
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $max    = strlen($chars) - 1;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $max)];
        }

        return $result;
    }

    /**
     * Generate a cryptographically secure random integer within a given range.
     *
     * @param int $min Minimum value (inclusive).
     * @param int $max Maximum value (inclusive).
     * @return int
     */
    public function randomInt(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    // =========================================================================
    // Private — AES-256-GCM core
    // =========================================================================

    /**
     * Encrypt using AES-256-GCM. Returns raw binary: nonce[12] + tag[16] + ciphertext[n].
     *
     * @param string $plaintext  Data to encrypt.
     * @param string $binaryKey  Raw 32-byte binary key.
     * @return string            Raw binary output.
     *
     * @throws \RuntimeException If OpenSSL encryption fails.
     */
    private function encryptRaw(string $plaintext, string $binaryKey): string
    {
        $nonce = random_bytes(self::NONCE_SIZE);
        $tag   = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $binaryKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_SIZE
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        return $nonce . $tag . $ciphertext;
    }

    /**
     * Decrypt AES-256-GCM binary input: nonce[12] + tag[16] + ciphertext[n].
     * Authentication is verified by OpenSSL — returns false on tag mismatch.
     *
     * @param string $data       Raw binary input.
     * @param string $binaryKey  Raw 32-byte binary key.
     * @return string|false      Decrypted plaintext, or false on failure.
     */
    private function decryptRaw(string $data, string $binaryKey)
    {
        // Minimum: nonce[12] + tag[16] = 28 bytes (zero-length plaintext is valid)
        if (strlen($data) < self::NONCE_SIZE + self::TAG_SIZE) {
            return false;
        }

        $nonce      = substr($data, 0, self::NONCE_SIZE);
        $tag        = substr($data, self::NONCE_SIZE, self::TAG_SIZE);
        $ciphertext = substr($data, self::NONCE_SIZE + self::TAG_SIZE);

        return openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $binaryKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );
    }

    /**
     * Parse and validate a hex key string, returning the raw binary.
     *
     * @param string $hexKey  64-character hexadecimal string.
     * @return string         Raw 32-byte binary key.
     *
     * @throws \InvalidArgumentException If the key is invalid.
     */
    private function parseKey(string $hexKey): string
    {
        if (!ctype_xdigit($hexKey) || strlen($hexKey) !== self::KEY_SIZE * 2) {
            throw new \InvalidArgumentException(
                'Encryption key must be a 64-character hexadecimal string (32 bytes). '
                . 'Use $app->encryption->generateKey() to produce a valid key.'
            );
        }

        return hex2bin($hexKey);
    }
}
