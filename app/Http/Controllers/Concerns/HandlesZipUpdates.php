<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\File;
use ZipArchive;

trait HandlesZipUpdates
{
    private function syncFiles($source, $destination)
    {
        $excluded = ['.env', '.git', 'storage', 'vendor', 'node_modules'];

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $destPath = $destination . '/' . $items->getSubPathName();

            // Skip excluded files and directories at the root of the source
            $relativePath = str_replace($source . DIRECTORY_SEPARATOR, '', $item->getPathname());
            $pathSegments = explode(DIRECTORY_SEPARATOR, $relativePath);
            if (in_array($pathSegments[0], $excluded)) {
                continue;
            }

            if ($item->isDir()) {
                if (!File::isDirectory($destPath)) {
                    File::makeDirectory($destPath, 0755, true);
                }
            } else {
                File::copy($item, $destPath);
            }
        }
    }
}
