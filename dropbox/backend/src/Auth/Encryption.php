<?php
declare(strict_types=1);

namespace App\Auth;

use App\Config;
use RuntimeException;

/**
 * Encryption — AES-256-GCM encrypt/decrypt for storing Dropbox tokens at rest.
 *
 * The encryption key is read from APP_ENCRYPTION_KEY (must be exactly 32 bytes).
 * Each call to encrypt() generates a fresh 12-byte IV, stored as part of the ciphertext blob.
 *
 * Storage format (base64):  IV (12 bytes) + TAG (16 bytes) + CIPHERTEXT
 */
class Encryption
{
    private const CIPHER    = 'aes-256-gcm';
    private const IV_LEN    = 12;   // 96-bit IV recommended for GCM
    private const TAG_LEN   = 16;   // 128-bit authentication tag

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Encrypt a plaintext string. Returns a base64-encoded blob safe for DB storage.
     */
    public static function encrypt(string $plaintext): string
    {
        $key = self::key();
        $iv  = random_bytes(self::IV_LEN);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LEN
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt a blob produced by encrypt(). Returns the original plaintext.
     */
    public static function decrypt(string $blob): string
    {
        $key  = self::key();
        $raw  = base64_decode($blob, strict: true);

        if ($raw === false || strlen($raw) < self::IV_LEN + self::TAG_LEN) {
            throw new RuntimeException('Decryption failed: invalid blob.');
        }

        $iv         = substr($raw, 0, self::IV_LEN);
        $tag        = substr($raw, self::IV_LEN, self::TAG_LEN);
        $ciphertext = substr($raw, self::IV_LEN + self::TAG_LEN);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed: authentication tag mismatch.');
        }

        return $plaintext;
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private static function key(): string
    {
        $key = Config::encryptionKey();
        if (strlen($key) !== 32) {
            throw new RuntimeException('APP_ENCRYPTION_KEY must be exactly 32 characters.');
        }
        return $key;
    }
}
