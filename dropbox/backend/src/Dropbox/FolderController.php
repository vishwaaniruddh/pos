<?php
declare(strict_types=1);

namespace App\Dropbox;

use App\Router;

/**
 * FolderController — GET /dropbox/folders
 *
 * Allows the frontend folder picker to browse the connected Dropbox account.
 * Query param: ?path=/ProductImages
 */
class FolderController
{
    public function browse(array $params): void
    {
        $path    = Router::query('path', '/');
        $client  = new DropboxClient();
        $entries = $client->browseFolder($path);

        // Return only folder entries sorted by name
        $folders = [];
        $files   = [];

        foreach ($entries as $entry) {
            $item = [
                'name'         => $entry['name'],
                'path_display' => $entry['path_display'],
                'is_folder'    => $entry['.tag'] === 'folder',
            ];

            if ($entry['.tag'] === 'folder') {
                $folders[] = $item;
            } else {
                $item['size'] = $entry['size'] ?? 0;
                $files[]      = $item;
            }
        }

        // Sort alphabetically
        usort($folders, fn($a, $b) => strcmp($a['name'], $b['name']));
        usort($files,   fn($a, $b) => strcmp($a['name'], $b['name']));

        Router::json([
            'path'    => $path,
            'folders' => $folders,
            'files'   => $files,
        ]);
    }
}
