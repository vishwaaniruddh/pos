<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Database;

/**
 * JobRepository — all database operations on the `jobs` table.
 */
class JobRepository
{
    // ── Read ──────────────────────────────────────────────────────────────────

    public static function findById(int $id): array|false
    {
        return Database::fetchOne('
            SELECT j.*, 
                   COALESCE(sr.remaining, 0) as remaining_clean_files
            FROM jobs j
            LEFT JOIN (
                SELECT job_id, COUNT(*) as remaining
                FROM scanned_result
                WHERE status = \'success\'
                GROUP BY job_id
            ) sr ON j.id = sr.job_id
            WHERE j.id = :id
        ', [':id' => $id]);
    }

    /**
     * @param array{status?: string, page?: int, limit?: int} $filters
     */
    public static function list(array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            // Since we alias jobs table as j in the main query, prepend table alias
            $where[]          = 'j.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['sku'])) {
            $where[]       = 'j.sku LIKE :sku';
            $params[':sku'] = '%' . $filters['sku'] . '%';
        }

        $sql = '
            SELECT j.*, 
                   COALESCE(sr.remaining, 0) as remaining_clean_files
            FROM jobs j
            LEFT JOIN (
                SELECT job_id, COUNT(*) as remaining
                FROM scanned_result
                WHERE status = \'success\'
                GROUP BY job_id
            ) sr ON j.id = sr.job_id
        ';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY j.created_at DESC';

        $limit  = max(1, min(100, (int) ($filters['limit'] ?? 25)));
        $offset = max(0, ((int) ($filters['page'] ?? 1) - 1)) * $limit;
        $sql   .= " LIMIT {$limit} OFFSET {$offset}";

        return Database::fetchAll($sql, $params);
    }

    public static function countAll(array $filters = []): int
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]          = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        $sql = 'SELECT COUNT(*) as cnt FROM jobs';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $row = Database::fetchOne($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Fetch the next job for the worker.
     *
     * Priority: in-progress jobs first (scanning/copying), then queued jobs
     * in LIFO order (newest first).
     */
    public static function nextQueued(): array|false
    {
        // First: resume any in-progress job (there should be at most one)
        $inProgress = Database::fetchOne("
            SELECT * FROM jobs 
            WHERE status IN ('scanning', 'copying') 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        if ($inProgress) {
            return $inProgress;
        }

        // Then: pick the newest queued job (LIFO)
        return Database::fetchOne("
            SELECT * FROM jobs 
            WHERE status = 'queued' 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
    }

    // ── Worker Heartbeat ──────────────────────────────────────────────────────
    public static function pingWorker(): void
    {
        $now = time();
        Database::execute(
            "INSERT INTO settings (key_name, payload) VALUES ('worker_ping', :time1) ON DUPLICATE KEY UPDATE payload = :time2",
            [':time1' => $now, ':time2' => $now]
        );
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public static function create(
        string $sku,
        string $sourcePath,
        string $destinationPath,
        array  $config = []
    ): int {
        Database::execute(
            'INSERT INTO jobs (sku, source_path, destination_path, status, config)
             VALUES (:sku, :source, :dest, :status, :config)',
            [
                ':sku'    => strtoupper(trim($sku)),
                ':source' => $sourcePath,
                ':dest'   => $destinationPath,
                ':status' => 'queued',
                ':config' => json_encode($config),
            ]
        );
        return (int) Database::lastInsertId();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public static function updateStatus(int $id, string $status, ?string $errorMessage = null): void
    {
        $params = [':status' => $status, ':id' => $id];
        $extra  = '';

        if ($status === 'scanning' || $status === 'copying') {
            $extra            .= ', started_at = COALESCE(started_at, NOW())';
        }
        if (in_array($status, ['completed', 'completed_with_errors', 'failed', 'cancelled'], true)) {
            $extra            .= ', finished_at = NOW()';
        }
        if ($errorMessage !== null) {
            $extra             .= ', error_message = :err';
            $params[':err']    = $errorMessage;
        }

        Database::execute(
            "UPDATE jobs SET status = :status{$extra} WHERE id = :id",
            $params
        );
    }

    public static function updateCursor(int $id, string $cursor): void
    {
        Database::execute(
            'UPDATE jobs SET folder_cursor = :cursor WHERE id = :id',
            [':cursor' => $cursor, ':id' => $id]
        );
    }

    public static function incrementCounters(int $id, array $increments): void
    {
        $parts  = [];
        $params = [':id' => $id];

        $allowed = [
            'total_files_scanned', 'total_files_matched',
            'total_copied', 'total_failed', 'total_images', 'total_videos',
        ];

        foreach ($increments as $col => $val) {
            if (in_array($col, $allowed, true) && $val !== 0) {
                $parts[]         = "{$col} = {$col} + :{$col}";
                $params[":{$col}"] = (int) $val;
            }
        }

        if (empty($parts)) {
            return;
        }

        Database::execute(
            'UPDATE jobs SET ' . implode(', ', $parts) . ' WHERE id = :id',
            $params
        );
    }

    /**
     * Check if cancel has been requested (re-read from DB to get latest value).
     */
    public static function isCancelRequested(int $id): bool
    {
        $row = Database::fetchOne(
            'SELECT cancel_requested FROM jobs WHERE id = :id',
            [':id' => $id]
        );
        return (bool) ($row['cancel_requested'] ?? false);
    }

    public static function requestCancel(int $id): void
    {
        Database::execute(
            'UPDATE jobs SET cancel_requested = 1 WHERE id = :id',
            [':id' => $id]
        );
    }

    // ── Logs ──────────────────────────────────────────────────────────────────

    public static function addLog(int $jobId, string $level, string $message, array $context = []): void
    {
        Database::execute(
            'INSERT INTO job_logs (job_id, level, message, context) VALUES (:jid, :lvl, :msg, :ctx)',
            [
                ':jid' => $jobId,
                ':lvl' => $level,
                ':msg' => $message,
                ':ctx' => $context ? json_encode($context) : null,
            ]
        );
        
        // Echo to terminal so the user can see live progress
        if (php_sapi_name() === 'cli') {
            echo "[" . date('Y-m-d H:i:s') . "] [Job #{$jobId}] [" . strtoupper($level) . "] {$message}\n";
        }

        // Also write to a file log if configured
        try {
            $logPath = \App\Config::get('WORKER_LOG_FILE');
            if ($logPath) {
                // Root is 2 levels up from src/Jobs
                $root = dirname(__DIR__, 2);
                $fullLogPath = $root . '/' . ltrim($logPath, '/');
                $logDir = dirname($fullLogPath);
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                $contextStr = $context ? ' ' . json_encode($context) : '';
                $line = sprintf("[%s] [Job #%d] [%s] %s%s\n", date('Y-m-d H:i:s'), $jobId, strtoupper($level), $message, $contextStr);
                @file_put_contents($fullLogPath, $line, FILE_APPEND | LOCK_EX);
            }
        } catch (\Throwable $e) {
            // Ignore logging errors to prevent breaking the worker
        }
    }

    public static function getLogs(int $jobId, ?string $level = null, ?int $afterId = null): array
    {
        $where  = ['job_id = :jid'];
        $params = [':jid' => $jobId];

        if ($level) {
            $where[]      = 'level = :lvl';
            $params[':lvl'] = $level;
        }
        if ($afterId !== null) {
            $where[]       = 'id > :aid';
            $params[':aid'] = $afterId;
        }

        return Database::fetchAll(
            'SELECT * FROM job_logs WHERE ' . implode(' AND ', $where) . ' ORDER BY id ASC LIMIT 500',
            $params
        );
    }
}
