<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Router;

/**
 * JobController — REST handlers for all /jobs/* endpoints.
 *
 * Routes registered in public/index.php:
 *   POST   /jobs                     → create
 *   GET    /jobs                     → index
 *   GET    /jobs/:id                 → show
 *   GET    /jobs/:id/progress        → progress  (polling endpoint)
 *   GET    /jobs/:id/files           → files
 *   GET    /jobs/:id/logs            → logs
 *   POST   /jobs/:id/cancel          → cancel
 *   GET    /jobs/:id/report          → report
 */
class JobController
{
    // ── POST /jobs ────────────────────────────────────────────────────────────

    public function create(array $params): void
    {
        $body = Router::jsonBody();

        $sku  = trim($body['sku'] ?? '');
        $src  = trim($body['source_path'] ?? '');
        $dest = trim($body['destination_path'] ?? '');

        if (empty($sku) || empty($src) || empty($dest)) {
            Router::error('VALIDATION_ERROR', 'sku, source_path, and destination_path are required.');
        }

        // Validate paths start with /
        if (!str_starts_with($src, '/') || !str_starts_with($dest, '/')) {
            Router::error('VALIDATION_ERROR', 'source_path and destination_path must start with /');
        }

        // Parse and normalize SKU list (split by comma or whitespace, filter empty, deduplicate)
        $skuList = array_unique(array_filter(preg_split('/[\s,]+/', strtoupper($sku))));
        if (empty($skuList)) {
            Router::error('VALIDATION_ERROR', 'No valid SKUs provided.');
        }

        // Validate each SKU
        foreach ($skuList as $s) {
            if (!preg_match('/^[A-Za-z0-9\-\.]+$/', $s)) {
                Router::error('VALIDATION_ERROR', "Invalid SKU format: '{$s}'. SKUs must contain only letters, numbers, dashes, and dots.");
            }
        }
        
        // Store as a clean comma-separated string
        $normalizedSku = implode(',', $skuList);

        $config = [
            'rate_delay_ms' => (int) ($body['config']['rate_delay_ms'] ?? 50),
            'max_retries'   => (int) ($body['config']['max_retries']   ?? 3),
            'on_conflict'   => in_array($body['config']['on_conflict'] ?? '', ['skip', 'overwrite', 'autorename'])
                                    ? $body['config']['on_conflict']
                                    : 'skip',
        ];

        $jobId = JobRepository::create($normalizedSku, $src, $dest, $config);
        $job   = JobRepository::findById($jobId);

        $this->triggerWorker();

        Router::json(['success' => true, 'job' => $job], 201);
    }

    // ── GET /jobs ─────────────────────────────────────────────────────────────

    public function index(array $params): void
    {
        $filters = [
            'status' => Router::query('status'),
            'sku'    => Router::query('sku'),
            'page'   => (int) (Router::query('page', 1)),
            'limit'  => (int) (Router::query('limit', 25)),
        ];

        $jobs  = JobRepository::list($filters);
        $total = JobRepository::countAll($filters);

        // Fetch global stats
        $stats = \App\Database::fetchOne("
            SELECT 
                COALESCE(SUM(total_copied), 0) as total_copied,
                COALESCE(SUM(total_files_scanned), 0) as total_files_scanned,
                COALESCE(SUM(CASE WHEN status IN ('scanning', 'copying') THEN 1 ELSE 0 END), 0) as active_count,
                COALESCE(SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END), 0) as queued_count
            FROM jobs
        ");

        // Fetch the most recent active job details for the banner
        $activeJob = \App\Database::fetchOne("
            SELECT id, sku, status, total_files_scanned, total_files_matched, total_copied, started_at
            FROM jobs
            WHERE status IN ('scanning', 'copying')
            ORDER BY created_at DESC
            LIMIT 1
        ");

        Router::json([
            'data'       => $jobs,
            'total'      => $total,
            'page'       => $filters['page'],
            'limit'      => $filters['limit'],
            'stats'      => [
                'total_copied'  => (int) $stats['total_copied'],
                'total_scanned' => (int) $stats['total_files_scanned'],
                'active_count'  => (int) $stats['active_count'],
                'queued_count'  => (int) $stats['queued_count'],
            ],
            'active_job' => $activeJob ?: null,
        ]);
    }

    // ── GET /jobs/:id ─────────────────────────────────────────────────────────

    public function show(array $params): void
    {
        $job = $this->findOrFail((int) ($params['id'] ?? 0));
        $job['config'] = $job['config'] ? json_decode($job['config'], true) : null;
        $job['sku_stats'] = $this->getSkuStats((int) $job['id']);
        Router::json($job);
    }

    // ── GET /jobs/:id/progress ────────────────────────────────────────────────

    public function progress(array $params): void
    {
        $job = $this->findOrFail((int) ($params['id'] ?? 0));

        $matched = (int) $job['total_files_matched'];
        $copied  = (int) $job['total_copied'];

        // Calculate progress percent
        $pct = 0;
        if (in_array($job['status'], ['completed', 'completed_with_errors'], true)) {
            $pct = 100;
        } elseif ($job['status'] === 'copying' && $matched > 0) {
            $pct = (int) round(($copied / $matched) * 100);
        } elseif ($job['status'] === 'scanning') {
            $pct = min(50, (int) round(((int) $job['total_files_scanned'] / max(1, (int) $job['total_files_scanned'] + 1000)) * 50));
        }

        // Auto-respawn worker if it died (e.g. Hostinger 60s timeout)
        if (in_array($job['status'], ['queued', 'scanning', 'copying'], true)) {
            $lastPingStr = \App\Database::fetchOne("SELECT payload FROM settings WHERE key_name = 'worker_ping'");
            $lastPing    = (int) ($lastPingStr ?: 0);
            if ((time() - $lastPing) >= 30) {
                $this->triggerWorker();
            }
        }

        Router::json([
            'id'                  => (int) $job['id'],
            'status'              => $job['status'],
            'total_files_scanned' => (int) $job['total_files_scanned'],
            'total_files_matched' => $matched,
            'total_copied'        => $copied,
            'total_failed'        => (int) $job['total_failed'],
            'progress_percent'    => $pct,
            'eta_seconds'         => $this->estimateEta($job),
            'updated_at'          => date('c'),
            'sku_stats'           => $this->getSkuStats((int) $job['id']),
        ]);
    }

    // ── GET /jobs/:id/files ───────────────────────────────────────────────────

    public function files(array $params): void
    {
        $jobId  = (int) ($params['id'] ?? 0);
        $this->findOrFail($jobId);

        $status = Router::query('status');
        $page   = max(1, (int) Router::query('page', 1));
        $limit  = min(100, max(1, (int) Router::query('limit', 50)));

        $files  = JobFileRepository::list($jobId, $status ?: null, $page, $limit);
        $counts = JobFileRepository::countByStatus($jobId);

        Router::json([
            'data'   => $files,
            'counts' => $counts,
            'page'   => $page,
            'limit'  => $limit,
        ]);
    }

    // ── GET /jobs/:id/logs ────────────────────────────────────────────────────

    public function logs(array $params): void
    {
        $jobId   = (int) ($params['id'] ?? 0);
        $this->findOrFail($jobId);

        $level   = Router::query('level');
        $afterId = Router::query('after_id') !== null ? (int) Router::query('after_id') : null;

        $logs = JobRepository::getLogs($jobId, $level ?: null, $afterId);

        Router::json(['data' => $logs]);
    }

    // ── POST /jobs/:id/cancel ─────────────────────────────────────────────────

    public function cancel(array $params): void
    {
        $job = $this->findOrFail((int) ($params['id'] ?? 0));

        $active = ['queued', 'scanning', 'copying'];
        if (!in_array($job['status'], $active, true)) {
            Router::error('INVALID_STATE', "Job cannot be cancelled in status: {$job['status']}.");
        }

        JobRepository::requestCancel((int) $job['id']);
        JobRepository::updateStatus((int) $job['id'], 'cancelled');
        JobRepository::addLog((int) $job['id'], 'warning', 'Job cancelled by user.');

        Router::json(['success' => true, 'message' => 'Job cancelled successfully.']);
    }

    // ── POST /jobs/:id/start ──────────────────────────────────────────────────

    public function start(array $params): void
    {
        $jobId = (int) ($params['id'] ?? 0);
        $job   = $this->findOrFail($jobId);

        if ($job['status'] !== 'queued') {
            Router::error('INVALID_STATE', "Only queued jobs can be started manually.");
        }

        // Bump created_at to the current time so it's picked first by the LIFO queue worker (ORDER BY created_at DESC)
        \App\Database::execute(
            "UPDATE jobs SET created_at = NOW() WHERE id = :id",
            [':id' => $jobId]
        );

        JobRepository::addLog($jobId, 'info', "Job manually started by user.");

        $this->triggerWorker();

        Router::json(['success' => true, 'message' => 'Job prioritized and worker triggered.']);
    }

    // ── POST /jobs/:id/restart ────────────────────────────────────────────────

    public function restart(array $params): void
    {
        $jobId = (int) ($params['id'] ?? 0);
        $job   = $this->findOrFail($jobId);

        $allowed = ['completed', 'completed_with_errors', 'failed', 'cancelled'];
        if (!in_array($job['status'], $allowed, true)) {
            Router::error('INVALID_STATE', "Job cannot be restarted in status: {$job['status']}.");
        }

        // Reset job state
        \App\Database::execute(
            "UPDATE jobs SET status = 'queued', folder_cursor = NULL, total_files_scanned = 0, total_files_matched = 0, total_copied = 0, total_failed = 0, total_images = 0, total_videos = 0, error_message = NULL, started_at = NULL, finished_at = NULL, cancel_requested = 0 WHERE id = :id",
            [':id' => $jobId]
        );
        \App\Database::execute("DELETE FROM scanned_result WHERE job_id = :id", [':id' => $jobId]);
        JobRepository::addLog($jobId, 'info', "Job restarted by user. Worker will begin scanning from scratch.");

        $this->triggerWorker();

        Router::json(['success' => true, 'message' => 'Job restarted successfully. Worker has been triggered.']);
    }

    // ── POST /jobs/start-worker ───────────────────────────────────────────────

    public function startWorker(array $params): void
    {
        // Run in background without blocking the client
        ignore_user_abort(true);
        set_time_limit(0);

        echo json_encode(['success' => true]);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            header('Connection: close');
            header('Content-Length: ' . ob_get_length());
            ob_end_flush();
            @ob_flush();
            flush();
        }

        // ── Lock: Database-based heartbeat ───────────────────────────────────
        // flock() is buggy on Windows Apache because the PID belongs to httpd.exe
        // which never dies. We use a simple database heartbeat instead.
        $lastPingStr = \App\Database::fetchOne("SELECT payload FROM settings WHERE key_name = 'worker_ping'");
        $lastPing    = (int) ($lastPingStr ?: 0);
        
        // If worker pinged within the last 30 seconds, assume it's alive.
        if ((time() - $lastPing) < 30) {
            exit;
        }

        // We are the new worker, claim the lock
        $now = time();
        \App\Database::execute(
            "INSERT INTO settings (key_name, payload) VALUES ('worker_ping', :time1) ON DUPLICATE KEY UPDATE payload = :time2",
            [':time1' => $now, ':time2' => $now]
        );

        // ── Process ONE job at a time (LIFO queue) ────────────────────────────
        \App\Jobs\JobRepository::pingWorker();

        $job = JobRepository::nextQueued();
        if (!$job) {
            return; // No queued jobs, exit — re-triggered on next create
        }

        $dropbox   = new \App\Dropbox\DropboxClient();
        $processor = new \App\Worker\JobProcessor($dropbox, $job);
        $processor->run();

        // After this job finishes, trigger a new worker for the next queued job
        $this->triggerWorker();
    }



    // ── GET /jobs/:id/report ──────────────────────────────────────────────────

    public function report(array $params): void
    {
        $job    = $this->findOrFail((int) ($params['id'] ?? 0));
        $format = Router::query('format', 'json');
        $files  = JobFileRepository::list((int) $job['id'], null, 1, 10000);
        $counts = JobFileRepository::countByStatus((int) $job['id']);

        if ($format === 'csv') {
            $this->sendCsv($job, $files);
            return;
        }

        $config = $job['config'] ? json_decode($job['config'], true) : null;

        Router::json([
            'job'     => array_merge($job, ['config' => $config]),
            'summary' => [
                'total_scanned' => (int) $job['total_files_scanned'],
                'total_matched' => (int) $job['total_files_matched'],
                'total_copied'  => (int) $job['total_copied'],
                'total_failed'  => (int) $job['total_failed'],
                'total_images'  => (int) $job['total_images'],
                'total_videos'  => (int) $job['total_videos'],
                'total_skipped' => $counts['skipped'],
            ],
            'files'   => $files,
        ]);
    }

    // ── GET /jobs/:id/clean-scan ──────────────────────────────────────────────

    public function cleanScan(array $params): void
    {
        $job = $this->findOrFail((int) ($params['id'] ?? 0));
        $limit = (int) Router::query('limit', 100);
        if ($limit < 1 || $limit > 1000) {
            $limit = 100;
        }

        try {
            $files = \App\Database::fetchAll("
                SELECT 
                    file_name, 
                    sku, 
                    scanned_path as source_path, 
                    copied_path as destination_path, 
                    file_size, 
                    doc_type as file_type
                FROM scanned_result
                WHERE job_id = :id AND status = 'success'
                LIMIT {$limit}
            ", [':id' => $job['id']]);
        } catch (\Throwable $e) {
            Router::error('SCAN_ERROR', 'Failed to fetch scan results: ' . $e->getMessage());
        }

        Router::json([
            'success' => true,
            'job_id' => $job['id'],
            'files' => $files,
        ]);
    }

    // ── POST /jobs/:id/clean-delete ───────────────────────────────────────────

    public function cleanDelete(array $params): void
    {
        $job = $this->findOrFail((int) ($params['id'] ?? 0));
        $body = Router::jsonBody();
        $rawPaths = $body['paths'] ?? [];

        if (!is_array($rawPaths) || empty($rawPaths)) {
            Router::error('VALIDATION_ERROR', 'No paths provided for deletion.');
        }

        // Limit deletion to 1000 paths
        $rawPaths = array_slice($rawPaths, 0, 1000);

        $paths = [];
        foreach ($rawPaths as $path) {
            if (is_string($path)) {
                if (str_contains($path, '/')) {
                    $paths[] = $path;
                } else {
                    $decoded = base64_decode(str_replace(['-', '_'], ['+', '/'], $path), true);
                    if ($decoded === false) {
                        $paths[] = $path;
                    } else {
                        $paths[] = $decoded;
                    }
                }
            } else {
                $paths[] = $path;
            }
        }

        $dropbox = new \App\Dropbox\DropboxClient();
        $successCount = 0;
        $failedPaths = [];

        foreach ($paths as $path) {
            try {
                $dropbox->delete($path);
                $successCount++;
                // Remove from scanned_result database cache
                \App\Database::execute(
                    "DELETE FROM scanned_result WHERE job_id = :id AND scanned_path = :path",
                    [':id' => $job['id'], ':path' => $path]
                );
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                // If it is already not found or path_lookup error (meaning deleted), count as success
                if (str_contains(strtolower($msg), 'not_found') || str_contains(strtolower($msg), 'path_lookup')) {
                    $successCount++;
                    // Remove from database cache too
                    \App\Database::execute(
                        "DELETE FROM scanned_result WHERE job_id = :id AND scanned_path = :path",
                        [':id' => $job['id'], ':path' => $path]
                    );
                } else {
                    $failedPaths[] = [
                        'path' => $path,
                        'error' => $msg
                    ];
                }
            }
        }

        // Log to DB
        $deletedList = implode(', ', array_map('basename', $paths));
        if ($successCount > 0) {
            JobRepository::addLog((int) $job['id'], 'warning', "Clean activity: Deleted {$successCount} file(s) from source folder: {$deletedList}");
        }
        if (count($failedPaths) > 0) {
            $failedList = implode(', ', array_map(fn($f) => basename($f['path']) . ': ' . $f['error'], $failedPaths));
            JobRepository::addLog((int) $job['id'], 'error', "Clean activity: Failed to delete " . count($failedPaths) . " file(s): {$failedList}");
        }

        Router::json([
            'success' => true,
            'deleted_count' => $successCount,
            'failed_paths' => $failedPaths,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function findOrFail(int $id): array
    {
        if ($id <= 0) {
            Router::error('VALIDATION_ERROR', 'Invalid job ID.', 400);
        }

        $job = JobRepository::findById($id);
        if (!$job) {
            Router::error('NOT_FOUND', "Job #{$id} not found.", 404);
        }

        return $job;
    }

    private function getSkuStats(int $jobId): array
    {
        $dbStats = \App\Database::fetchAll("
            SELECT 
                sku, 
                COUNT(*) as total_matched,
                SUM(CASE WHEN doc_type = 'image' THEN 1 ELSE 0 END) as images_count,
                SUM(CASE WHEN doc_type = 'video' THEN 1 ELSE 0 END) as videos_count,
                SUM(CASE WHEN doc_type = 'other' THEN 1 ELSE 0 END) as other_count,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as total_done,
                SUM(CASE WHEN status = 'fail' THEN 1 ELSE 0 END) as total_fail
            FROM scanned_result
            WHERE job_id = :id AND sku IS NOT NULL
            GROUP BY sku
        ", [':id' => $jobId]);

        $statsBySku = [];
        foreach ($dbStats as $row) {
            $statsBySku[strtoupper($row['sku'])] = $row;
        }

        $job = JobRepository::findById($jobId);
        if (!$job || empty($job['sku'])) {
            return array_values($statsBySku);
        }

        $requestedSkus = explode(',', $job['sku']);
        $finalStats = [];

        foreach ($requestedSkus as $s) {
            $skuUpper = strtoupper(trim($s));
            if (isset($statsBySku[$skuUpper])) {
                $finalStats[] = [
                    'sku'           => $s,
                    'total_matched' => (int) $statsBySku[$skuUpper]['total_matched'],
                    'images_count'  => (int) $statsBySku[$skuUpper]['images_count'],
                    'videos_count'  => (int) $statsBySku[$skuUpper]['videos_count'],
                    'other_count'   => (int) $statsBySku[$skuUpper]['other_count'],
                    'total_done'    => (int) $statsBySku[$skuUpper]['total_done'],
                    'total_fail'    => (int) $statsBySku[$skuUpper]['total_fail'],
                ];
            } else {
                $finalStats[] = [
                    'sku'           => $s,
                    'total_matched' => 0,
                    'images_count'  => 0,
                    'videos_count'  => 0,
                    'other_count'   => 0,
                    'total_done'    => 0,
                    'total_fail'    => 0,
                ];
            }
        }

        return $finalStats;
    }

    private function estimateEta(array $job): ?int
    {
        if ($job['status'] !== 'copying') {
            return null;
        }

        $matched  = (int) $job['total_files_matched'];
        $copied   = (int) $job['total_copied'];
        $remaining = $matched - $copied;

        if ($remaining <= 0 || !$job['started_at']) {
            return null;
        }

        $elapsed  = time() - strtotime($job['started_at']);
        $rate     = $copied > 0 ? $elapsed / $copied : 5; // seconds per file
        return (int) ($remaining * $rate);
    }

    private function sendCsv(array $job, array $files): void
    {
        $filename = "job-{$job['id']}-{$job['sku']}-report.csv";
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $out = fopen('php://output', 'w');
        fputcsv($out, ['File Name', 'File Type', 'Size (bytes)', 'Status', 'Source Path', 'Destination Path', 'Retry Count', 'Error Message', 'Processed At']);

        foreach ($files as $f) {
            fputcsv($out, [
                $f['file_name'],
                $f['doc_type'],
                $f['file_size'],
                $f['status'],
                $f['scanned_path'],
                $f['copied_path'] ?? '',
                $f['retry_count'],
                $f['error_message'] ?? '',
                $f['updated_at'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }

    private function triggerWorker(): void
    {
        // Derive the worker URL from the current request — this works regardless
        // of which subfolder the API lives in (e.g., /pos/dropbox/api on Hostinger).
        // SCRIPT_NAME = e.g. /pos/dropbox/api/index.php
        // dirname() → /pos/dropbox/api  → append /jobs/start-worker
        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? '';

        if ($host) {
            $apiDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/api/index.php'), '/');
            $url    = $scheme . '://' . $host . $apiDir . '/jobs/start-worker';
        } else {
            // CLI fallback (should not happen for HTTP trigger)
            $url = \App\Config::appUrl() . '/pos/dropbox/api/jobs/start-worker';
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Allow up to 5s for loopback DNS resolution and connection
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass local/self-signed SSL cert checks
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'); // Bypass firewalls blocking empty user agent
        curl_exec($ch);
        @curl_close($ch);
    }
}
