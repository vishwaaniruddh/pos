<?php
declare(strict_types=1);

namespace App\Dropbox;

use App\Auth\TokenManager;
use RuntimeException;

/**
 * DropboxClient — raw cURL wrapper for Dropbox API v2.
 *
 * Auto-refreshes token via TokenManager before each request.
 * Handles 429 rate limiting with exponential backoff.
 */
class DropboxClient
{
    private const API_BASE      = 'https://api.dropboxapi.com/2';
    private const CONTENT_BASE  = 'https://content.dropboxapi.com/2';
    private const MAX_BACKOFF   = 7;          // max retry attempts on 429/5xx
    private const BACKOFF_BASE  = 2;          // seconds (doubles each retry)

    // ── Folder Operations ─────────────────────────────────────────────────────

    /**
     * Start recursive folder listing. Returns ['entries', 'cursor', 'has_more'].
     */
    public function listFolder(string $path, bool $recursive = true): array
    {
        return $this->apiPost('/files/list_folder', [
            'path'                            => $path === '/' ? '' : $path,
            'recursive'                       => $recursive,
            'include_media_info'              => false,
            'include_deleted'                 => false,
            'include_has_explicit_shared_members' => false,
            'limit'                           => 2000,
        ]);
    }

    /**
     * Continue a paginated listing using a cursor.
     */
    public function listFolderContinue(string $cursor): array
    {
        return $this->apiPost('/files/list_folder/continue', [
            'cursor' => $cursor,
        ]);
    }

    /**
     * Browse top-level contents at a path (used by folder picker endpoint).
     */
    public function browseFolder(string $path): array
    {
        $result = $this->apiPost('/files/list_folder', [
            'path'      => $path === '/' ? '' : $path,
            'recursive' => false,
            'limit'     => 200,
        ]);
        return $result['entries'] ?? [];
    }

    // ── File / Folder Write Operations ────────────────────────────────────────

    /**
     * Copy a file. Returns the new file metadata or throws on error.
     * on_conflict: 'skip' | 'overwrite' | 'autorename'
     */
    public function copyFile(string $fromPath, string $toPath, string $onConflict = 'skip'): array
    {
        $autorename = $onConflict === 'autorename';

        // If "skip", don't overwrite; if "overwrite", use the overwrite mode
        $mode = match ($onConflict) {
            'overwrite' => ['override'],
            default     => ['.tag' => 'add'],
        };

        return $this->apiPost('/files/copy_v2', [
            'from_path'  => $fromPath,
            'to_path'    => $toPath,
            'autorename' => $autorename,
        ]);
    }

    /**
     * Start a batch copy. Returns the response from Dropbox.
     * entries is an array of ['from_path' => ..., 'to_path' => ...]
     */
    public function copyBatch(array $entries, bool $autorename = false): array
    {
        return $this->apiPost('/files/copy_batch_v2', [
            'entries'    => $entries,
            'autorename' => $autorename,
        ]);
    }

    /**
     * Check the status of a batch copy job.
     */
    public function checkCopyBatch(string $asyncJobId): array
    {
        return $this->apiPost('/files/copy_batch/check_v2', [
            'async_job_id' => $asyncJobId,
        ]);
    }

    /**
     * Create a folder. Ignores FolderConflict (already exists is fine).
     */
    public function createFolder(string $path): void
    {
        try {
            $this->apiPost('/files/create_folder_v2', [
                'path'       => $path,
                'autorename' => false,
            ]);
        } catch (RuntimeException $e) {
            // "path/conflict/folder" means it already exists — that's OK
            if (str_contains($e->getMessage(), 'conflict')) {
                return;
            }
            throw $e;
        }
    }

    /**
     * Delete a file or folder.
     */
    public function delete(string $path): array
    {
        return $this->apiPost('/files/delete_v2', [
            'path' => $path,
        ]);
    }

    // ── Internal HTTP ─────────────────────────────────────────────────────────

    private function apiPost(string $endpoint, array $body): array
    {
        $token   = TokenManager::getAccessToken();
        $attempt = 0;

        while (true) {
            [$httpCode, $responseBody] = $this->curlPost(
                self::API_BASE . $endpoint,
                $body,
                $token
            );

            // Success
            if ($httpCode === 200) {
                return json_decode($responseBody, true) ?? [];
            }

            // 409 Conflict (folder already exists, file already exists with skip mode)
            if ($httpCode === 409) {
                $error = json_decode($responseBody, true);
                throw new RuntimeException("Dropbox conflict: " . json_encode($error), 409);
            }

            // 429 Rate limit — exponential backoff
            if ($httpCode === 429 && $attempt < self::MAX_BACKOFF) {
                $wait = (int) pow(self::BACKOFF_BASE, $attempt + 1);
                sleep($wait);
                $attempt++;
                // Re-fetch token in case it expired during wait
                $token = TokenManager::getAccessToken();
                continue;
            }

            // 5xx Server error — retry once
            if ($httpCode >= 500 && $attempt === 0) {
                sleep(5);
                $attempt++;
                continue;
            }

            // Fatal
            $error = json_decode($responseBody, true);
            throw new RuntimeException(
                "Dropbox API error (HTTP {$httpCode}): " . ($error['error_summary'] ?? $responseBody),
                $httpCode
            );
        }
    }

    private function curlPost(string $url, array $body, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($responseBody === false) {
            $err = curl_error($ch);
            @curl_close($ch);
            throw new RuntimeException("cURL error: {$err}");
        }

        @curl_close($ch);
        return [$httpCode, $responseBody];
    }
}
