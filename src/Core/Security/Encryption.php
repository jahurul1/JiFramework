<?php
namespace JiFramework\Core\Security;

class Encryption
{
    /**
     * Generate a random encryption key.
     *
     * @param int $length Length of the key in bytes. For AES-256, the key length should be 32 bytes.
     * @return string     The generated key in hexadecimal representation.
     */
    public function generateKey($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Encrypt data using AES-256-CBC encryption.
     *
     * @param string $plaintext The data to encrypt.
     * @param string $key       The encryption key in hexadecimal.
     * @param string $iv        Optional initialization vector in hexadecimal. If not provided, a random IV will be generated.
     * @return string          The base64-encoded encrypted data.
     */
    public function encrypt($plaintext, $key, $iv = null)
    {
        // Convert key and IV from hex to binary
        $key = hex2bin($key);

        // Generate a random IV if not provided
        if ($iv === null) {
            $ivLength = openssl_cipher_iv_length('aes-256-cbc');
            $iv = random_bytes($ivLength);
        } else {
            $iv = hex2bin($iv);
        }

        // Encrypt the data
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        // Generate HMAC for authentication
        $hmac = hash_hmac('sha256', $ciphertext, $key, true);

        // Concatenates the IV, HMAC, and encrypted data, separated by '::', and encodes in base64
        $encryptedData = base64_encode($iv . '::' . $ciphertext . '::' . $hmac);

        // Return the encrypted data
        return $encryptedData;

    }

    /**
     * Decrypt data using AES-256-CBC encryption.
     *
     * @param string $ciphertext The encrypted data in hexadecimal.
     * @param string $key        The encryption key in hexadecimal.
     * @return string            The decrypted plaintext.
     */
    public function decrypt($ciphertext, $key)
    {
        // Decodes from base64 to get the IV and encrypted data
		$data = base64_decode($ciphertext);

        // Separates the IV, ciphertext, and HMAC
		$explodedData = explode('::', $data, 3);

        // Check if we have IV, ciphertext, and HMAC
		if (count($explodedData) !== 3) {
			// Return false if any part is missing
			return false;
		}

        // Convert key from hex to binary
        $key = hex2bin($key);

        // Extract the IV, ciphertext, and HMAC
        $iv = $explodedData[0];
        $ciphertext = $explodedData[1];
        $hmac = $explodedData[2];

        // Verify HMAC
        $calculatedHmac = hash_hmac('sha256', $ciphertext, $key, true);
        if (!hash_equals($hmac, $calculatedHmac)) {
            return false; // HMAC verification failed
        }

        // Decrypt the data
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return $plaintext;
    }

    /**
     * Generate a secure encryption key from a password using PBKDF2.
     *
     * @param string $password The password to derive the key from.
     * @param string $salt     Optional salt in hexadecimal. If not provided, a random salt will be generated.
     * @param int    $iterations Number of iterations for the key derivation function.
     * @return array            An array containing 'key' and 'salt' in hexadecimal.
     */
    public function generateKeyFromPassword($password, $salt = null, $iterations = 100000)
    {
        // Generate a random salt if not provided
        if ($salt === null) {
            $salt = random_bytes(16); // 128-bit salt
        } else {
            $salt = hex2bin($salt);
        }

        // Derive the key using PBKDF2
        $key = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);

        // Return the key and salt in hexadecimal format
        return [
            'key'  => bin2hex($key),
            'salt' => bin2hex($salt),
        ];
    }

    /**
     * Securely hash a password for storage.
     *
     * @param string $password The password to hash.
     * @return string          The hashed password.
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a password against a hash.
     *
     * @param string $password The plaintext password.
     * @param string $hash     The hashed password.
     * @return bool            True if the password matches the hash, false otherwise.
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate a random initialization vector (IV).
     *
     * @param string $cipherMethod The cipher method to use (default is 'aes-256-cbc').
     * @return string              The IV in hexadecimal format.
     */
    public function generateInitializationVector($cipherMethod = 'aes-256-cbc')
    {
        $ivLength = openssl_cipher_iv_length($cipherMethod);
        $iv = random_bytes($ivLength);
        return bin2hex($iv);
    }

    /**
     * Encrypt data using a password of any length.
     *
     * @param string $plaintext  The data to encrypt.
     * @param string $password   The password to derive the encryption key.
     * @param int    $iterations Number of iterations for the key derivation function.
     * @return string            The base64-encoded encrypted data containing salt, IV, and ciphertext.
     */
    public function encryptWithPassword($plaintext, $password, $iterations = 100000)
    {
        // Generate a random salt
        $salt = random_bytes(16); // 128-bit salt

        // Generate key from password and salt
        $key = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);

        // Generate a random IV
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = random_bytes($ivLength);

        // Encrypt the data
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        // Generate HMAC for authentication
        $hmac = hash_hmac('sha256', $ciphertext, $key, true);

        // Concatenate salt, iv, ciphertext, and HMAC (all in binary)
        $encryptedData = $salt . $iv . $ciphertext . $hmac;

        // Encode the result in base64 for storage/transmission
        $encodedData = base64_encode($encryptedData);

        return $encodedData;
    }

    /**
     * Decrypt data using a password of any length.
     *
     * @param string $encodedData The base64-encoded encrypted data containing salt, IV, and ciphertext.
     * @param string $password    The password to derive the decryption key.
     * @param int    $iterations  Number of iterations for the key derivation function.
     * @return string|false       The decrypted plaintext or false on failure.
     */
    public function decryptWithPassword($encodedData, $password, $iterations = 100000)
    {
        $encryptedData = base64_decode($encodedData);

        // Extract the salt, IV, ciphertext, and HMAC
        $saltLength = 16; // 16 bytes
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $hmacLength = 32; // SHA-256 HMAC is 32 bytes

        $salt = substr($encryptedData, 0, $saltLength);
        $iv = substr($encryptedData, $saltLength, $ivLength);
        $ciphertext = substr($encryptedData, $saltLength + $ivLength, -$hmacLength);
        $hmac = substr($encryptedData, -$hmacLength);

        // Generate key from password and salt
        $key = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);

        // Verify HMAC
        $calculatedHmac = hash_hmac('sha256', $ciphertext, $key, true);
        if (!hash_equals($hmac, $calculatedHmac)) {
            return false; // HMAC verification failed
        }

        // Decrypt the data
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return $plaintext;
    }
}


