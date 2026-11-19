<?php
declare(strict_types=1);

namespace App\Auth;

use App\Config;
use App\Database;
use RuntimeException;

/**
 * TokenManager — handles Dropbox OAuth token lifecycle.
 *
 * - Stores encrypted access_token + refresh_token in dropbox_credentials
 * - Proactively refreshes the access token 5 minutes before expiry
 * - Called by the worker every 10 files to ensure the token stays valid
 */
class TokenManager
{
    private const TOKEN_URL       = 'https://api.dropboxapi.com/oauth2/token';
    private const REFRESH_BUFFER  = 300;  // Refresh if expiry < 5 minutes away

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Store a new token set (called after OAuth callback).
     */
    public static function store(
        string $accessToken,
        string $refreshToken,
        int    $expiresIn,
        string $accountEmail = '',
        string $accountId    = '',
        string $scope        = ''
    ): void {
        $expiresAt = time() + $expiresIn;

        // Upsert — only one credential row is expected (single-account setup)
        $existing = Database::fetchOne('SELECT id FROM dropbox_credentials LIMIT 1');

        if ($existing) {
            Database::execute(
                'UPDATE dropbox_credentials
                    SET access_token  = :at,
                        refresh_token = :rt,
                        expires_at    = :ea,
                        account_email = :email,
                        account_id    = :aid,
                        scope         = :scope
                  WHERE id = :id',
                [
                    ':at'    => Encryption::encrypt($accessToken),
                    ':rt'    => Encryption::encrypt($refreshToken),
                    ':ea'    => $expiresAt,
                    ':email' => $accountEmail,
                    ':aid'   => $accountId,
                    ':scope' => $scope,
                    ':id'    => $existing['id'],
                ]
            );
        } else {
            Database::execute(
                'INSERT INTO dropbox_credentials
                    (access_token, refresh_token, expires_at, account_email, account_id, scope)
                 VALUES (:at, :rt, :ea, :email, :aid, :scope)',
                [
                    ':at'    => Encryption::encrypt($accessToken),
                    ':rt'    => Encryption::encrypt($refreshToken),
                    ':ea'    => $expiresAt,
                    ':email' => $accountEmail,
                    ':aid'   => $accountId,
                    ':scope' => $scope,
                ]
            );
        }
    }

    /**
     * Get the current (valid) access token.
     * Automatically refreshes if within the buffer window.
     *
     * @throws RuntimeException if no credentials are stored or refresh fails.
     */
    public static function getAccessToken(): string
    {
        $cred = self::getCredentials();
        if (!$cred) {
            throw new RuntimeException('No Dropbox credentials found. Please connect your account first.');
        }

        if (self::isExpiringSoon((int) $cred['expires_at'])) {
            self::refresh($cred);
            // Re-fetch after refresh
            $cred = self::getCredentials();
        }

        return Encryption::decrypt($cred['access_token']);
    }

    /**
     * Proactive refresh check — call this every N files in the worker.
     * Returns true if a refresh was performed.
     */
    public static function refreshIfNeeded(): bool
    {
        $cred = self::getCredentials();
        if (!$cred) {
            return false;
        }

        if (self::isExpiringSoon((int) $cred['expires_at'])) {
            self::refresh($cred);
            return true;
        }

        return false;
    }

    /**
     * Return raw credentials row (with encrypted tokens) for display purposes.
     */
    public static function getStatus(): array|false
    {
        return self::getCredentials();
    }

    /**
     * Delete all stored credentials (disconnect).
     */
    public static function revoke(): void
    {
        Database::execute('DELETE FROM dropbox_credentials');
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private static function getCredentials(): array|false
    {
        return Database::fetchOne('SELECT * FROM dropbox_credentials ORDER BY id DESC LIMIT 1');
    }

    private static function isExpiringSoon(int $expiresAt): bool
    {
        return time() > ($expiresAt - self::REFRESH_BUFFER);
    }

    private static function refresh(array $cred): void
    {
        $refreshToken = Encryption::decrypt($cred['refresh_token']);

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id'     => Config::dropboxAppKey(),
                'client_secret' => Config::dropboxAppSecret(),
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        @curl_close($ch);

        if ($httpCode !== 200 || $body === false) {
            throw new RuntimeException("Token refresh failed (HTTP {$httpCode}): {$body}");
        }

        $data = json_decode($body, true);
        if (empty($data['access_token'])) {
            throw new RuntimeException('Token refresh response missing access_token.');
        }

        $expiresAt = time() + (int) ($data['expires_in'] ?? 14400);

        Database::execute(
            'UPDATE dropbox_credentials
                SET access_token = :at,
                    expires_at   = :ea
              WHERE id = :id',
            [
                ':at' => Encryption::encrypt($data['access_token']),
                ':ea' => $expiresAt,
                ':id' => $cred['id'],
            ]
        );
    }
}
