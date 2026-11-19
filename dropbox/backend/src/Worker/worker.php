<?php
declare(strict_types=1);

/**
 * Worker CLI Entry Point
 *
 * Run this under Supervisor (or manually for testing):
 *   php backend/src/Worker/worker.php
 *
 * The worker runs in an infinite loop, polling the `jobs` table every N seconds
 * for queued jobs. It processes one job at a time (single-threaded by design).
 *
 * Supervisor config example (Linux):
 *   [program:sku-copier-worker]
 *   command=php /var/www/html/drop/backend/src/Worker/worker.php
 *   autostart=true
 *   autorestart=true
 *   stderr_logfile=/var/log/worker.err.log
 *   stdout_logfile=/var/log/worker.out.log
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────

// Resolve project root (2 levels up from src/Worker/)
$root = dirname(__DIR__, 2);

// Load Composer autoloader
$autoloader = $root . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    fwrite(STDERR, "[FATAL] Composer autoloader not found. Run: cd backend && composer install\n");
    exit(1);
}
require $autoloader;

use App\Config;
use App\Database;
use App\Dropbox\DropboxClient;
use App\Jobs\JobRepository;
use App\Worker\JobProcessor;

// Load .env
Config::load($root);

// Increase time limit — jobs can run for hours
set_time_limit(0);
ini_set('memory_limit', '256M');

// Logging helper (writes to STDOUT + optional log file)
$logFile    = Config::get('WORKER_LOG_FILE') ? $root . '/../' . Config::get('WORKER_LOG_FILE') : null;
$pollInterval = Config::getInt('WORKER_POLL_INTERVAL', 5);

function workerLog(string $level, string $message, ?string $logFile = null): void
{
    $line = sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message);
    echo $line;
    if ($logFile) {
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}

// ── Main Loop ─────────────────────────────────────────────────────────────────

// Ensure only one worker runs at a time
$lockFile = sys_get_temp_dir() . '/sku_copier_worker.lock';
$fp = fopen($lockFile, 'w+');
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    exit; // Another worker is already running
}

workerLog('info', "Worker started. Checking for queued jobs.", $logFile);

while (true) {
    try {
        // Connect to DB (singleton — reconnects automatically on loss)
        Database::connect();

        // Pick up next queued job
        $job = JobRepository::nextQueued();

        if (!$job) {
            workerLog('debug', 'No queued jobs. Exiting...', $logFile);
            break; // Exit worker, will be restarted on next trigger
        }

        workerLog('info', "Picked up Job #{$job['id']} — SKU: {$job['sku']}", $logFile);

        // Run the job
        $dropbox   = new DropboxClient();
        $processor = new JobProcessor($dropbox, $job);
        $processor->run();

        // Re-read final status for logging
        $done = JobRepository::findById((int) $job['id']);
        workerLog('info', "Job #{$job['id']} finished with status: {$done['status']}", $logFile);

    } catch (Throwable $e) {
        workerLog('error', "Unhandled worker error: " . $e->getMessage(), $logFile);
        workerLog('error', $e->getTraceAsString(), $logFile);

        // Prevent tight crash loops
        sleep($pollInterval);
    }
}
