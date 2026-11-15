<?php
/**
 * Author: valedrat
 * Product: phpSketch / phpDQuery
 *
 * AES-256-CBC encryption with HMAC-SHA256 integrity check.
 * Supports both global static key (for convenience) and per-call key override.
 *
 * Format stored: base64( IV [16 bytes] + HMAC [32 bytes] + ciphertext )
 */

namespace PhpDQuery\Security;

use PhpDQuery\Exception\SecurityException;

class Crypt
{
    private static string $globalKey = '';
    private static string $cipher    = 'aes-256-cbc';

    // ─── Key management ──────────────────────────────────────────────────────

    /**
     * Set the global encryption key used when no per-call key is provided.
     * Key should be 32 bytes for AES-256 (longer is silently truncated by OpenSSL,
     * shorter is padded — always use exactly 32 chars for predictability).
     */
    public static function setKey(string $key): void
    {
        self::$globalKey = $key;
    }

    public static function getKey(): string
    {
        return self::$globalKey;
    }

    public static function hasKey(): bool
    {
        return self::$globalKey !== '';
    }

    // ─── Core encrypt / decrypt ───────────────────────────────────────────────

    /**
     * Encrypt a plaintext string.
     * @param string $data    Plaintext to encrypt
     * @param string $key     Optional per-call key (overrides global key)
     */
    public static function encrypt(string $data, string $key = ''): string
    {
        $key = self::resolveKey($key);

        $ivLen          = openssl_cipher_iv_length(self::$cipher);
        $iv             = openssl_random_pseudo_bytes($ivLen);
        $ciphertextRaw  = openssl_encrypt($data, self::$cipher, $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertextRaw === false) {
            throw new SecurityException('Encryption failed.');
        }

        $hmac = hash_hmac('sha256', $ciphertextRaw, $key, true);

        return base64_encode($iv . $hmac . $ciphertextRaw);
    }

    /**
     * Decrypt a previously encrypted string.
     * @param string $data    Base64-encoded ciphertext
     * @param string $key     Optional per-call key (overrides global key)
     */
    public static function decrypt(string $data, string $key = ''): string
    {
        $key = self::resolveKey($key);

        $raw   = base64_decode($data, true);
        $ivLen = openssl_cipher_iv_length(self::$cipher);

        if ($raw === false || strlen($raw) < $ivLen + 32) {
            throw new SecurityException('Invalid encrypted data format.');
        }

        $iv             = substr($raw, 0, $ivLen);
        $hmac           = substr($raw, $ivLen, 32);
        $ciphertextRaw  = substr($raw, $ivLen + 32);

        $calcMac = hash_hmac('sha256', $ciphertextRaw, $key, true);
        if (!hash_equals($hmac, $calcMac)) {
            throw new SecurityException('Data integrity check failed (HMAC mismatch).');
        }

        $plaintext = openssl_decrypt($ciphertextRaw, self::$cipher, $key, OPENSSL_RAW_DATA, $iv);

        if ($plaintext === false) {
            throw new SecurityException('Decryption failed.');
        }

        return $plaintext;
    }

    // ─── Convenience helpers ──────────────────────────────────────────────────

    /**
     * Encrypt only if value is not already encrypted (idempotent-safe insert).
     * Checks by attempting a decode+HMAC verify first.
     */
    public static function encryptIfPlain(string $data, string $key = ''): string
    {
        if (self::isEncrypted($data, $key)) {
            return $data;
        }
        return self::encrypt($data, $key);
    }

    /**
     * Returns true if the string appears to be a valid Crypt ciphertext.
     */
    public static function isEncrypted(string $data, string $key = ''): bool
    {
        try {
            self::decrypt($data, $key);
            return true;
        } catch (SecurityException) {
            return false;
        }
    }

    /**
     * Encrypt an array of key=>value pairs. Only values are encrypted.
     * @return array<string, string>
     */
    public static function encryptArray(array $data, string $key = ''): array
    {
        foreach ($data as &$value) {
            if (is_string($value)) {
                $value = self::encrypt($value, $key);
            }
        }
        return $data;
    }

    /**
     * Decrypt an array of key=>value pairs. Only values are decrypted.
     * @return array<string, string>
     */
    public static function decryptArray(array $data, string $key = ''): array
    {
        foreach ($data as &$value) {
            if (is_string($value)) {
                $value = self::decrypt($value, $key);
            }
        }
        return $data;
    }

    /**
     * Re-encrypt data under a new key (key rotation).
     */
    public static function reEncrypt(string $data, string $oldKey, string $newKey): string
    {
        $plain = self::decrypt($data, $oldKey);
        return self::encrypt($plain, $newKey);
    }

    /**
     * Generate a cryptographically random key of the given byte length.
     * Default 32 bytes = 256 bits = perfect for AES-256.
     */
    public static function generateKey(int $bytes = 32): string
    {
        return base64_encode(random_bytes($bytes));
    }

    // ─── Searchable Encryption (Blind Index) ─────────────────────────────────

    /**
     * Generate a deterministic, keyed blind index for a plaintext value.
     *
     * Unlike encrypt(), this always produces the same output for the same input+key,
     * so it CAN be searched with WHERE. It is NOT reversible.
     *
     * Algorithm: HMAC-SHA256(value, key), truncated to $bits bits, base64url-encoded.
     * Truncation trades collision resistance for a smaller storage footprint.
     * Default 128 bits gives 1-in-2^128 false positive rate — safe for most use cases.
     * Use 256 bits for maximum safety (full HMAC output).
     *
     * Example usage:
     *   // Insert
     *   $db->into('users')->insert(['email'=>'alice@x.com','email_idx'=>null])
     *      ->encrypt(['email'])->blindIndex(['email'=>'email_idx'])->run();
     *
     *   // Search
     *   $user = $db->from('users')
     *      ->where('email_idx', Crypt::blindIndex('alice@x.com'))
     *      ->decrypt(['email'])->first();
     *
     * @param string $data   Plaintext to index (e.g. 'alice@example.com')
     * @param string $key    Optional per-call key override (uses global key if empty)
     * @param int    $bits   Output length in bits (default 128; max 256)
     */
    public static function blindIndex(string $data, string $key = '', int $bits = 128): string
    {
        $key   = self::resolveKey($key);
        $bits  = min(max($bits, 8), 256);
        $bytes = (int) ceil($bits / 8);

        $full = hash_hmac('sha256', $data, $key, true);
        $truncated = substr($full, 0, $bytes);

        // Base64url (no padding) — safe for DB storage and URL params
        return rtrim(strtr(base64_encode($truncated), '+/', '-_'), '=');
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private static function resolveKey(string $override): string
    {
        $key = $override !== '' ? $override : self::$globalKey;

        if ($key === '') {
            throw new SecurityException(
                'Encryption key is not set. Call Crypt::setKey() or pass a key directly.'
            );
        }

        return $key;
    }
}
