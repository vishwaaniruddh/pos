<?php
declare(strict_types=1);

namespace App\Worker;

use App\Dropbox\DropboxClient;
use App\Jobs\JobFileRepository;
use App\Jobs\JobRepository;
use RuntimeException;

/**
 * FolderScanner — Phase 2 of the job engine.
 *
 * Recursively lists the Dropbox source folder using cursor-based pagination.
 * After each page, the cursor is persisted to jobs.folder_cursor so the job
 * can resume from where it left off after a server crash or restart.
 *
 * Matched files are inserted into scanned_result with status = 'pending'.
 */
class FolderScanner
{
    private DropboxClient $dropbox;
    private SkuMatcher    $matcher;
    private int           $jobId;

    public function __construct(DropboxClient $dropbox, SkuMatcher $matcher, int $jobId)
    {
        $this->dropbox = $dropbox;
        $this->matcher = $matcher;
        $this->jobId   = $jobId;
    }

    /**
     * Run the full scan. Returns ['scanned', 'matched'] counts.
     *
     * @param string      $sourcePath    Dropbox path to scan recursively
     * @param string|null $resumeCursor  Existing cursor from DB (for crash resume)
     */
    public function scan(string $sourcePath, ?string $resumeCursor = null): array
    {
        $totalScanned = 0;
        $totalMatched = 0;
        $cursor       = $resumeCursor;

        JobRepository::addLog($this->jobId, 'info',
            $cursor
                ? "Resuming folder scan from saved cursor."
                : "Starting recursive folder scan: {$sourcePath}"
        );

        do {
            // Fetch next page
            try {
                if ($cursor === null) {
                    $response = $this->dropbox->listFolder($sourcePath, recursive: true);
                } else {
                    $response = $this->dropbox->listFolderContinue($cursor);
                }
            } catch (RuntimeException $e) {
                // Source folder not found
                if (str_contains($e->getMessage(), 'not_found') || str_contains($e->getMessage(), 'path')) {
                    throw new RuntimeException("Source folder not found: {$sourcePath}. " . $e->getMessage());
                }
                throw $e;
            }

            $entries  = $response['entries']  ?? [];
            $cursor   = $response['cursor']   ?? null;
            $hasMore  = (bool) ($response['has_more'] ?? false);

            // Process this page
            [$pageScanned, $pageMatched] = $this->processPage($entries);

            $totalScanned += $pageScanned;
            $totalMatched += $pageMatched;

            // Checkpoint cursor immediately so crash recovery works
            if ($cursor) {
                JobRepository::updateCursor($this->jobId, $cursor);
            }

            // Update running counters every page
            JobRepository::incrementCounters($this->jobId, [
                'total_files_scanned' => $pageScanned,
                'total_files_matched' => $pageMatched,
            ]);

            JobRepository::pingWorker();

            // Progress log every 5,000 files
            if ($totalScanned % 5000 < count($entries)) {
                JobRepository::addLog($this->jobId, 'info',
                    "Scanned {$totalScanned} files — {$totalMatched} matches for SKU(s) \"{$this->matcher->getSkuString()}\""
                );
            }
            
            // Check for cancellation
            if (JobRepository::isCancelRequested($this->jobId)) {
                JobRepository::addLog($this->jobId, 'info', 'Cancel signal received during scan. Stopping worker.');
                return ['scanned' => $totalScanned, 'matched' => $totalMatched, 'cancelled' => true];
            }

        } while ($hasMore);

        JobRepository::addLog($this->jobId, 'info',
            "Scan complete. {$totalScanned} files scanned, {$totalMatched} matched."
        );

        return ['scanned' => $totalScanned, 'matched' => $totalMatched];
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function processPage(array $entries): array
    {
        $pageScanned = 0;
        $pageMatched = 0;

        foreach ($entries as $entry) {
            if (($entry['.tag'] ?? '') !== 'file') {
                continue; // Skip folder entries
            }

            $pageScanned++;

            $matchedSku = $this->matcher->matches($entry);
            if ($matchedSku !== false) {
                JobFileRepository::insertOne($this->jobId, $entry, $matchedSku);
                $pageMatched++;
            }
        }

        return [$pageScanned, $pageMatched];
    }
}
