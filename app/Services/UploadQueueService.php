<?php

namespace App\Services;

class UploadQueueService
{
    public static function queue(string $fileId, string $tempPath, string $originalName): void
    {
        $job = [
            'file_id' => $fileId,
            'temp_path' => $tempPath,
            'original_name' => $originalName,
            'status' => 'queued',
            'timestamp' => time(),
        ];

        file_put_contents(base_path("storage/jobs/{$fileId}.json"), json_encode($job));
    }

    public static function getProgress(string $fileId): string
    {
        $path = base_path("storage/jobs/{$fileId}.json");
        if (!file_exists($path)) return 'not_found';

        $job = json_decode(file_get_contents($path), true);
        return $job['status'] ?? 'unknown';
    }

    public static function markComplete(string $fileId): void
    {
        $path = base_path("storage/jobs/{$fileId}.json");
        if (file_exists($path)) {
            $job = json_decode(file_get_contents($path), true);
            $job['status'] = 'completed';
            file_put_contents($path, json_encode($job));
        }
    }
}
