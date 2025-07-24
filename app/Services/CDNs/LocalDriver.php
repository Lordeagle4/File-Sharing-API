<?php

namespace App\Services\CDNs;

use App\Contracts\CdnDriverInterface;
use App\Services\SymlinkManager;

class LocalDriver implements CdnDriverInterface
{
    public function upload(string $path, string $key): string
    {
        $relativePath = $_ENV['UPLOAD_PATH'] ?? 'storage/uploads';
        $uploadsDir = base_path($relativePath);

        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        $destPath = $uploadsDir . '/' .$key;
        copy($path, $destPath);

        // Create signed symlink
        $accessCode = bin2hex(random_bytes(16));
        SymlinkManager::create($destPath, $accessCode);

        return $_ENV['APP_URL'] . '/download/' . $accessCode;
    }
}
