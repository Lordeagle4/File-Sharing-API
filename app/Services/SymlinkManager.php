<?php

namespace App\Services;

class SymlinkManager
{
    public static function create(string $actualFilePath, string $accessCode): void
    {
        $downloadsDir = base_path('public/downloads');

        if (!is_dir($downloadsDir)) {
            mkdir($downloadsDir, 0777, true);
        }

        $symlinkPath = "$downloadsDir/$accessCode";

        if (file_exists($symlinkPath)) {
            unlink($symlinkPath);
        }

        try {
            symlink($actualFilePath, $symlinkPath);
        } catch (\Throwable $e) {
            copy($actualFilePath, $symlinkPath);
        }
    }

    public function cleanOldLinks():void
    {
        
    }
}
