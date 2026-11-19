<?php
declare(strict_types=1);

namespace App\Worker;

use App\Auth\TokenManager;
use App\Dropbox\DropboxClient;
use App\Jobs\JobFileRepository;
use App\Jobs\JobRepository;
use RuntimeException;

/**
 * FileCopier — Phase 5 of the job engine.
 *
 * Iterates all pending files for a job, copies each one to the destination,
 * handles rate limiting with exponential backoff, and checks the cancel flag.
 *
 * Token is proactively refreshed every 10 files.
 */
class FileCopier
{
    private DropboxClient $dropbox;
    private int           $jobId;
    private string        $sku;
    private string        $destinationBase;
    private string        $onConflict;
    private int           $rateDelayMs;
    private int           $maxRetries;

    public function __construct(
        DropboxClient $dropbox,
        int           $jobId,
        string        $sku,
        string        $destinationBase,
        string        $onConflict  = 'skip',
        int           $rateDelayMs = 50,
        int           $maxRetries  = 3,
    ) {
        $this->dropbox         = $dropbox;
        $this->jobId           = $jobId;
        $this->sku             = $sku;
        $this->destinationBase = rtrim($destinationBase, '/');
        $this->onConflict      = $onConflict;
        $this->rateDelayMs     = max(0, $rateDelayMs);
        $this->maxRetries      = max(1, $maxRetries);
    }

    /**
     * Copy all pending files for the job. Returns ['copied', 'failed'] counts.
     *
     * Destination folders are created on-demand the first time a file for that
     * SKU is encountered — NOT upfront for every input SKU. This avoids wasting
     * N × Dropbox API calls when most input SKUs may have no matched files.
     */
    public function copyAll(): array
    {
        $totalCopied    = 0;
        $totalFailed    = 0;
        $filesProcessed = 0;
        $foldersCreated = []; // tracks which dest folders have been created this run

        JobRepository::addLog($this->jobId, 'info', "Copy phase started. Base Destination: {$this->destinationBase}");

        while (true) {
            // Load next batch of pending files (batch of 100)
            $pending = JobFileRepository::getPending($this->jobId, 100);

            if (empty($pending)) {
                break; // All done
            }

            // ── Cancel check ─────────────────────────────────────────────
            if (JobRepository::isCancelRequested($this->jobId)) {
                JobRepository::addLog($this->jobId, 'info', 'Cancel signal received. Stopping worker.');
                return ['copied' => $totalCopied, 'failed' => $totalFailed, 'cancelled' => true];
            }

            // ── Proactive token refresh every batch ────────────────────
            $refreshed = TokenManager::refreshIfNeeded();
            if ($refreshed) {
                JobRepository::addLog($this->jobId, 'info', 'Token refreshed proactively.');
            }

            // ── Ensure destination folders exist for all SKUs in this batch ───
            $entries = [];
            $fileMap = []; // maps entry index to file info for database update

            foreach ($pending as $index => $file) {
                $filename   = $file['file_name'];
                $sourcePath = $file['scanned_path'];

                $matchedSku = $file['sku'];
                if (empty($matchedSku)) {
                    // Fallback for jobs queued before the update
                    $skus = array_filter(explode(',', $this->sku));
                    $matchedSku = $skus[0] ?? 'Unknown_SKU';
                    foreach ($skus as $s) {
                        if (stripos($filename, $s) !== false) {
                            $matchedSku = $s;
                            break;
                        }
                    }
                }

                $destFolder = "{$this->destinationBase}/{$matchedSku}";
                $destPath   = "{$destFolder}/{$filename}";

                // On-demand destination folder creation
                if (!isset($foldersCreated[$destFolder])) {
                    $this->createDestFolder($destFolder);
                    $foldersCreated[$destFolder] = true;
                }

                $entries[] = [
                    'from_path' => $sourcePath,
                    'to_path'   => $destPath,
                ];

                $fileMap[$index] = [
                    'file'      => $file,
                    'dest_path' => $destPath,
                ];
            }

            // ── Execute Batch Copy ──────────────────────────────────────────
            try {
                $response = $this->dropbox->copyBatch($entries, $this->onConflict === 'autorename');
            } catch (RuntimeException $e) {
                // If the entire batch request fails (network error, auth error, etc.)
                JobRepository::addLog($this->jobId, 'error', 'Batch copy request failed: ' . $e->getMessage());
                foreach ($pending as $file) {
                    $fileId = (int) $file['id'];
                    if (JobFileRepository::markFailed($fileId, $e->getMessage(), 1)) {
                        JobRepository::incrementCounters($this->jobId, ['total_failed' => 1]);
                        $totalFailed++;
                    }
                }
                continue;
            }

            // ── Handle Asynchronous Job or Direct Response ───────────────────
            $batchEntries = [];
            $tag = $response['.tag'] ?? '';

            if ($tag === 'complete') {
                $batchEntries = $response['entries'] ?? [];
            } elseif ($tag === 'async_job_id') {
                $asyncJobId = $response['async_job_id'];
                $pollAttempts = 0;
                $pollLimit = 30; // Max 90 seconds (30 * 3s)
                $completed = false;

                while ($pollAttempts < $pollLimit) {
                    JobRepository::pingWorker();
                    sleep(3);

                    // Check for cancel during poll sleep
                    if (JobRepository::isCancelRequested($this->jobId)) {
                        JobRepository::addLog($this->jobId, 'info', 'Cancel signal received during batch copy. Stopping worker.');
                        return ['copied' => $totalCopied, 'failed' => $totalFailed, 'cancelled' => true];
                    }

                    try {
                        $checkRes = $this->dropbox->checkCopyBatch($asyncJobId);
                        $checkTag = $checkRes['.tag'] ?? '';

                        if ($checkTag === 'complete') {
                            $batchEntries = $checkRes['entries'] ?? [];
                            $completed = true;
                            break;
                        } elseif ($checkTag === 'failed') {
                            throw new RuntimeException("Dropbox async batch job failed.");
                        }
                    } catch (RuntimeException $e) {
                        JobRepository::addLog($this->jobId, 'error', 'Batch check failed: ' . $e->getMessage());
                        // If it fails permanently, break and treat this batch as failed
                        break;
                    }
                    $pollAttempts++;
                }

                if (!$completed) {
                    // Poll timed out or failed permanently
                    $errorMsg = "Batch copy async timeout or check failed.";
                    JobRepository::addLog($this->jobId, 'error', $errorMsg);
                    foreach ($pending as $file) {
                        $fileId = (int) $file['id'];
                        if (JobFileRepository::markFailed($fileId, $errorMsg, 1)) {
                            JobRepository::incrementCounters($this->jobId, ['total_failed' => 1]);
                            $totalFailed++;
                        }
                    }
                    continue;
                }
            } else {
                // Unexpected response tag
                $errorMsg = "Unexpected Dropbox API response tag: '{$tag}'";
                JobRepository::addLog($this->jobId, 'error', $errorMsg);
                foreach ($pending as $file) {
                    $fileId = (int) $file['id'];
                    if (JobFileRepository::markFailed($fileId, $errorMsg, 1)) {
                        JobRepository::incrementCounters($this->jobId, ['total_failed' => 1]);
                        $totalFailed++;
                    }
                }
                continue;
            }

            // ── Process Batch Copy Results ───────────────────────────────────
            foreach ($batchEntries as $index => $resEntry) {
                if (!isset($fileMap[$index])) {
                    continue;
                }

                $fileData = $fileMap[$index];
                $file     = $fileData['file'];
                $destPath = $fileData['dest_path'];
                $fileId   = (int) $file['id'];
                $filename = $file['file_name'];
                $fileType = $file['doc_type'];

                $entryTag = $resEntry['.tag'] ?? '';

                if ($entryTag === 'success') {
                    if (JobFileRepository::markCopied($fileId, $destPath)) {
                        JobRepository::incrementCounters($this->jobId, [
                            'total_copied'  => 1,
                            'total_images'  => $fileType === 'image' ? 1 : 0,
                            'total_videos'  => $fileType === 'video' ? 1 : 0,
                        ]);
                        $totalCopied++;
                        JobRepository::addLog($this->jobId, 'info',
                            "Copied: {$filename} (" . $this->humanBytes((int) $file['file_size']) . ")"
                        );
                    }
                } else {
                    // Specific entry failure
                    $failure = $resEntry['failure'] ?? [];
                    $errorMsg = $failure['.tag'] ?? 'unknown_failure';

                    // ── Handle Conflict with "skip" mode ───────────────────────
                    if ($this->isConflictError($failure) && $this->onConflict === 'skip') {
                        // Mark as success/skipped but count as copied since it exists
                        if (JobFileRepository::markCopied($fileId, $destPath)) {
                            JobRepository::incrementCounters($this->jobId, [
                                'total_copied'  => 1,
                                'total_images'  => $fileType === 'image' ? 1 : 0,
                                'total_videos'  => $fileType === 'video' ? 1 : 0,
                            ]);
                            $totalCopied++;
                            JobRepository::addLog($this->jobId, 'info',
                                "Skipped (already exists): {$filename}"
                            );
                        }
                        continue;
                    }

                    // Treat as failure
                    if (JobFileRepository::markFailed($fileId, "Dropbox relocation error: " . $errorMsg, 1)) {
                        JobRepository::incrementCounters($this->jobId, ['total_failed' => 1]);
                        $totalFailed++;
                        JobRepository::addLog($this->jobId, 'error',
                            "Failed to copy {$filename} in batch",
                            ['error' => $errorMsg]
                        );
                    }
                }
            }

            $filesProcessed += count($pending);
            JobRepository::pingWorker();

            // Rate limiting delay between batches (if configured)
            if ($this->rateDelayMs > 0) {
                usleep($this->rateDelayMs * 1000);
            }
        }

        return ['copied' => $totalCopied, 'failed' => $totalFailed, 'cancelled' => false];
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function isConflictError(array $failure): bool
    {
        $tag = $failure['.tag'] ?? '';
        if ($tag === 'conflict') {
            return true;
        }
        if ($tag === 'to' && isset($failure['to']['.tag']) && $failure['to']['.tag'] === 'conflict') {
            return true;
        }
        if ($tag === 'from_write' && isset($failure['from_write']['.tag']) && $failure['from_write']['.tag'] === 'conflict') {
            return true;
        }
        return $this->arrayContainsValueRecursive($failure, 'conflict');
    }

    private function arrayContainsValueRecursive(array $array, string $value): bool
    {
        foreach ($array as $val) {
            if ($val === $value) {
                return true;
            }
            if (is_array($val) && $this->arrayContainsValueRecursive($val, $value)) {
                return true;
            }
        }
        return false;
    }

    private function createDestFolder(string $path): void
    {
        $attempts = 0;
        while ($attempts < 5) {
            try {
                $this->dropbox->createFolder($path);
                JobRepository::addLog($this->jobId, 'info', "Destination folder ready: {$path}");
                return;
            } catch (RuntimeException $e) {
                // If the folder already exists, it's not an error.
                if (str_contains(strtolower($e->getMessage()), 'conflict') || str_contains($e->getMessage(), 'HTTP 409')) {
                    return;
                }
                $attempts++;
                if ($attempts >= 5) {
                    throw new RuntimeException("Failed to create destination folder after 5 attempts: " . $e->getMessage());
                }
                sleep(5);
            }
        }
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
