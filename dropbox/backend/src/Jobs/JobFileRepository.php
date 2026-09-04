<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Database;

/**
 * JobFileRepository — all database operations on the `scanned_result` table.
 */
class JobFileRepository
{
    // ── Create ────────────────────────────────────────────────────────────────

    /**
     * Bulk-insert discovered files from a folder scan page.
     * Only inserts files (not folders) that match the SKU.
     */
    public static function insertBatch(int $jobId, array $entries, string $sku): int
    {
        $inserted = 0;

        foreach ($entries as $entry) {
            // Only process file entries (not folders)
            if (($entry['.tag'] ?? '') !== 'file') {
                continue;
            }

            $fileName = basename($entry['path_display'] ?? $entry['path_lower']);

            // SKU match check (case-insensitive)
            if (stripos($fileName, $sku) === false) {
                continue;
            }

            $fileType = self::detectFileType($fileName);
            $sizeBytes = (int) ($entry['size'] ?? 0);

            Database::execute(
                'INSERT IGNORE INTO scanned_result
                    (job_id, file_name, sku, scanned_path, doc_type, file_size, status)
                 VALUES (:jid, :name, :sku, :src, :type, :size, :status)',
                [
                    ':jid'    => $jobId,
                    ':name'   => $fileName,
                    ':sku'    => $sku,
                    ':src'    => $entry['path_display'] ?? $entry['path_lower'],
                    ':type'   => $fileType,
                    ':size'   => $sizeBytes,
                    ':status' => 'pending',
                ]
            );
            $inserted++;
        }

        return $inserted;
    }

    /**
     * Insert a single pre-checked file entry.
     */
    public static function insertOne(int $jobId, array $entry, string $matchedSku): int
    {
        $fileName = basename($entry['path_display'] ?? $entry['path_lower']);
        $fileType = self::detectFileType($fileName);

        Database::execute(
            'INSERT IGNORE INTO scanned_result
                (job_id, file_name, sku, scanned_path, doc_type, file_size, status)
             VALUES (:jid, :name, :sku, :src, :type, :size, :status)',
            [
                ':jid'    => $jobId,
                ':name'   => $fileName,
                ':sku'    => $matchedSku,
                ':src'    => $entry['path_display'] ?? $entry['path_lower'],
                ':type'   => $fileType,
                ':size'   => (int) ($entry['size'] ?? 0),
                ':status' => 'pending',
            ]
        );

        return (int) Database::lastInsertId();
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public static function getPending(int $jobId, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT * FROM scanned_result
              WHERE job_id = :jid AND status = 'pending'
              ORDER BY id ASC
              LIMIT {$limit}",
            [':jid' => $jobId]
        );
    }

    public static function list(int $jobId, ?string $status = null, int $page = 1, int $limit = 50): array
    {
        $where  = ['job_id = :jid'];
        $params = [':jid' => $jobId];

        if ($status) {
            $where[]        = 'status = :status';
            $params[':status'] = $status;
        }

        $offset = max(0, ($page - 1)) * $limit;
        $sql    = 'SELECT * FROM scanned_result WHERE ' . implode(' AND ', $where)
                . " ORDER BY id ASC LIMIT {$limit} OFFSET {$offset}";

        return Database::fetchAll($sql, $params);
    }

    public static function countByStatus(int $jobId): array
    {
        $rows = Database::fetchAll(
            'SELECT status, COUNT(*) as cnt FROM scanned_result WHERE job_id = :jid GROUP BY status',
            [':jid' => $jobId]
        );

        $out = ['pending' => 0, 'success' => 0, 'fail' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['cnt'];
        }
        return $out;
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public static function markCopied(int $fileId, string $destinationPath): bool
    {
        $updated = Database::execute(
            "UPDATE scanned_result
                SET status      = 'success',
                    copied_path = :dest
              WHERE id = :id AND status = 'pending'",
            [':dest' => $destinationPath, ':id' => $fileId]
        );
        return $updated > 0;
    }

    public static function markFailed(int $fileId, string $errorMessage, int $retryCount = 0): bool
    {
        $updated = Database::execute(
            "UPDATE scanned_result
                SET status        = 'fail',
                    error_message = :err,
                    retry_count   = :rc
              WHERE id = :id AND status = 'pending'",
            [':err' => $errorMessage, ':rc' => $retryCount, ':id' => $fileId]
        );
        return $updated > 0;
    }

    public static function markSkipped(int $fileId): void
    {
        Database::execute(
            "UPDATE scanned_result SET status = 'skipped' WHERE id = :id",
            [':id' => $fileId]
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Detect image / video / other from file extension.
     */
    public static function detectFileType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $images = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'tif', 'svg', 'heic', 'heif', 'raw', 'cr2', 'nef'];
        $videos = ['mp4', 'mov', 'avi', 'mkv', 'wmv', 'flv', 'webm', 'm4v', 'mpg', 'mpeg', '3gp', 'mts'];

        if (in_array($ext, $images, true)) return 'image';
        if (in_array($ext, $videos, true)) return 'video';
        return 'other';
    }
}
