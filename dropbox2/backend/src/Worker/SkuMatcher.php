<?php
declare(strict_types=1);

namespace App\Worker;

use App\Jobs\JobFileRepository;

/**
 * SkuMatcher — case-insensitive substring matching against file basenames.
 *
 * A file matches if the SKU appears anywhere in its filename.
 * Example: "K627-6000,K1251CS-5000,K1170-2500.jpg" matches SKU "K627".
 */
class SkuMatcher
{
    private string $rawSkuString;
    private array  $skus;

    public function __construct(string $skuString)
    {
        $this->rawSkuString = strtoupper(trim($skuString));
        $skus               = array_filter(explode(',', $this->rawSkuString));

        // Sort SKUs by length in descending order to prevent substring collisions
        // (e.g., matching "MALA199B" before "MALA199")
        usort($skus, function($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        $this->skus = $skus;
    }

    /**
     * Test whether a Dropbox entry (file metadata array) matches any SKU.
     * Returns the matching SKU string if found, otherwise false.
     */
    public function matches(array $entry): string|false
    {
        // Only match file entries, not folders
        if (($entry['.tag'] ?? '') !== 'file') {
            return false;
        }

        $filename = basename($entry['path_lower'] ?? $entry['path_display'] ?? '');
        
        foreach ($this->skus as $sku) {
            if (stripos($filename, $sku) !== false) {
                return $sku;
            }
        }
        
        return false;
    }

    /**
     * Filter an array of entries down to only matching files.
     * Returns an array of matching entries with resolved metadata.
     */
    public function filterEntries(array $entries): array
    {
        return array_filter($entries, fn(array $e) => $this->matches($e) !== false);
    }

    /**
     * Determine file type for a matched entry.
     */
    public function fileType(array $entry): string
    {
        $filename = basename($entry['path_lower'] ?? '');
        return JobFileRepository::detectFileType($filename);
    }

    public function getSkuString(): string
    {
        if (strlen($this->rawSkuString) > 50) {
            return substr($this->rawSkuString, 0, 50) . '... (and ' . (count($this->skus) - 1) . ' more)';
        }
        return $this->rawSkuString;
    }
}
