<?php
declare(strict_types=1);

namespace App\Worker;

use App\Dropbox\DropboxClient;
use App\Jobs\JobRepository;
use RuntimeException;
use Throwable;

/**
 * JobProcessor — orchestrates all 6 phases for a single job.
 *
 * Phase 1: Dispatch (job already in DB with status 'queued')
 * Phase 2: Folder scan (recursive with cursor checkpoint)
 * Phase 3: SKU matching (done inside FolderScanner during scan)
 * Phase 4: Create destination folder
 * Phase 5: Copy matched files with backoff + cancel check
 * Phase 6: Mark job completed / completed_with_errors / failed
 */
class JobProcessor
{
    private DropboxClient $dropbox;
    private int           $jobId;
    private array         $job;

    public function __construct(DropboxClient $dropbox, array $job)
    {
        $this->dropbox = $dropbox;
        $this->jobId   = (int) $job['id'];
        $this->job     = $job;
    }

    /**
     * Run the full job pipeline.
     */
    public function run(): void
    {
        $jobId = $this->jobId;

        try {
            // ── Phase 1: Mark as scanning ────────────────────────────────────
            if ($this->job['status'] !== 'copying') {
                JobRepository::updateStatus($jobId, 'scanning');
            }
            JobRepository::addLog($jobId, 'info', "Worker spawned. Resuming job. Source: {$this->job['source_path']}");

            $config  = json_decode($this->job['config'] ?? '{}', true) ?? [];
            $matcher = new SkuMatcher($this->job['sku']);

            // ── Phase 2 + 3: Folder scan + SKU matching ──────────────────────
            // Only scan if not already in copying phase
            if ($this->job['status'] !== 'copying') {
                $scanner = new FolderScanner($this->dropbox, $matcher, $jobId);
                $scanResult = $scanner->scan(
                    sourcePath:   $this->job['source_path'],
                    resumeCursor: $this->job['folder_cursor'] ?: null
                );

                if ($scanResult['cancelled'] ?? false) {
                    JobRepository::updateStatus($jobId, 'cancelled');
                    return;
                }
            }

            // ── Phase 4 + 5: Create dest folder + copy files ─────────────────
            JobRepository::updateStatus($jobId, 'copying');

            $copier = new FileCopier(
                dropbox:         $this->dropbox,
                jobId:           $jobId,
                sku:             $this->job['sku'],
                destinationBase: $this->job['destination_path'],
                onConflict:      $config['on_conflict']    ?? 'skip',
                rateDelayMs:     (int) ($config['rate_delay_ms'] ?? 50),
                maxRetries:      (int) ($config['max_retries']   ?? 3),
            );

            $result = $copier->copyAll();

            // ── Phase 6: Mark completion ──────────────────────────────────────
            if ($result['cancelled'] ?? false) {
                JobRepository::updateStatus($jobId, 'cancelled');
                return;
            }

            // Re-read final counters from DB for accuracy
            $finalJob  = JobRepository::findById($jobId);
            $hasFailed = (int) ($finalJob['total_failed'] ?? 0) > 0;

            JobRepository::updateStatus(
                $jobId,
                $hasFailed ? 'completed_with_errors' : 'completed'
            );

            $copied = $finalJob['total_copied']  ?? 0;
            $failed = $finalJob['total_failed']  ?? 0;
            JobRepository::addLog($jobId, 'info',
                "Job finished. Copied: {$copied}, Failed: {$failed}."
            );

        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $isNetworkError = str_contains($msg, 'Dropbox API') || str_contains($msg, 'cURL error') || str_contains($msg, '429') || str_contains($msg, '500') || str_contains($msg, 'Failed to create destination folder');
            
            if ($isNetworkError) {
                JobRepository::updateStatus($jobId, 'queued', 'API Error, auto-resuming: ' . $msg);
                JobRepository::addLog($jobId, 'warning', 'Job paused due to Dropbox API limits/error. Will automatically resume shortly. Details: ' . $msg);
            } else {
                JobRepository::updateStatus($jobId, 'failed', $msg);
                JobRepository::addLog($jobId, 'error', 'Job failed: ' . $msg, [
                    'exception' => get_class($e),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ]);
            }
        }
    }
}
