<?php

namespace App\Services;

use App\Contracts\StorageDriverInterface;
use App\Contracts\CdnDriverInterface;
use App\Factories\CdnFactory;
use App\Factories\StorageFactory;

class FileService
{
    protected string $uploadDir;
    protected CdnDriverInterface $cdnManager;
    protected StorageDriverInterface $storage;

    public function __construct(
        ?StorageDriverInterface $storage = null,
        ?CdnDriverInterface $cdnManager = null
    ) {
        $this->uploadDir = $_ENV['UPLOAD_PATH'] ?? 'storage/uploads';
        $this->storage = $storage ?? StorageFactory::make();
        $this->cdnManager = $cdnManager ?? CdnFactory::make();

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function uploadFile(array $file, array $options = []): ?array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed with error code: ' . $file['error']);
        }
        $originalName = $file['name'];
        $tmpPath = $file['tmp_name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $id = md5($filename . time());

        $isQueued = $options['queue'] ?? false;
        $user = $options['user'] ?? 'guest';
        $type = $options['type'] ?? 'general';

        $subfolder = date('Y/m/d') . '/' . $user . '/' . $type;
        $fullDir = $this->uploadDir . '/' . $subfolder;

        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0777, true);
        }

        $destination = "$subfolder/$filename";

        try {
            if ($isQueued) {
                $this->storage->queueUpload([
                    'id' => $id,
                    'temp_path' => $tmpPath,
                    'original_name' => $originalName
                ]);
                return ['id' => $id, 'queued' => true];
            }

            // Handle CDN upload
            $link = $this->cdnManager->upload($tmpPath, $destination);
            if (!$link) {
                return null; // CDN upload failed
            }
            $meta = [
                'id' => $id,
                'name' => $originalName,
                'path' => $_ENV['STORAGE_DRIVER'] == 'local' ? $this->uploadDir . '/' . $destination : $link,
                'user' => $user,
                'link' => $link,
                'views' => 0,
                'downloads' => 0
            ];

            // Save metadata
            $this->storage->saveMeta($meta);

            return $meta;
        } catch (\Exception $e) {
            return null;
        }
    }


    public function fetchFile(string $fileId): ?array
    {
        return $this->storage->getMeta($fileId);
    }

    public function addViewCount(string $fileId): void
    {
        $meta = $this->storage->getMeta($fileId);
        if (!$meta) return;

        $updated = ['views' => $meta['views'] + 1];
        $this->storage->updateMeta($fileId, $updated);
    }

    public function addDownloadCount(string $fileId): void
    {
        $meta = $this->storage->getMeta($fileId);
        if (!$meta) return;

        $updated = ['downloads' => $meta['downloads'] + 1];
        $this->storage->updateMeta($fileId, $updated);
    }

    public function getJStats(): array
    {
        // This assumes your driver has access to all stored metadata,
        // otherwise you’ll need to implement a method like `getAllMeta()`
        $allJobs = $this->storage->getQueuedJobs(); // or similar accessor
        $stats = [];

        foreach ($allJobs as $job) {
            $meta = $this->storage->getMeta($job['id']);
            if ($meta) {
                $stats[] = [
                    'id' => $meta['id'],
                    'name' => $meta['name'],
                    'views' => $meta['views'],
                    'downloads' => $meta['downloads'],
                ];
            }
        }

        return $stats;
    }

    public function getStats(string $fileId): ?array
    {
        $meta = $this->fetchFile($fileId);

        if (!$meta) {
            return null;
        }

        return [
            'views' => $meta['views'] ?? 0,
            'downloads' => $meta['downloads'] ?? 0,
        ];
    }


    /*   public function getStats(): array
    {
        $files = glob($this->uploadDir . '/*.json');
        $stats = [];

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                $stats[] = [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'views' => $data['views'],
                    'downloads' => $data['downloads'],
                ];
            }
        }

        return $stats;
    }
*/


    private function generateOrganizedPath(string $filename, ?string $user = null, ?string $type = null): string
    {
        $date = date('Y-m-d');
        $user = $user ?? 'guest';
        $type = $type ?? 'general';

        $folder = base_path($_ENV['UPLOAD_PATH'] . "/$date/$user/$type");

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        return $folder . '/' . uniqid() . '_' . basename($filename);
    }

    public function uploadFromWorker(string $fileId, string $tempPath, string $originalName): void
    {
        $user = 'guest';
        $type = 'general';

        $destPath = $this->generateOrganizedPath($originalName, $user, $type);
        copy($tempPath, $destPath);

        $this->cdnManager->upload($destPath, basename($destPath));

        unlink($tempPath);
    }

    public function resolveDownloadPath(string $accessCode): ?string
    {
        $downloadsDir = base_path('public/downloads');
        $symlinkPath = "$downloadsDir/$accessCode";

        // Check symlink
        if (file_exists($symlinkPath)) {
            var_dump("Symlink found at: $symlinkPath");
            return $symlinkPath;
        }

        // Fallback: metadata
        $expectedLink = rtrim($_ENV['APP_URL'], '/') . '/download/' . $accessCode;

        $meta = $this->storage->findBy('link', $expectedLink);

        // Convert relative path to full absolute path
        if (isset($meta['path'])) {
            $absolutePath = base_path($meta['path']);
            return $absolutePath;
        }

        return null;
    }

    public function getFileByAccessCode(string $accessCode): ?array
    {
        // Check if the access code is a valid UUID
        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $accessCode)) {
            return null; // Invalid access code format
        }

        // Fetch file metadata by access code
        return $this->storage->findBy('access_code', $accessCode);
    }

    public function getQueuedJobs(): array
    {
        return $this->storage->getQueuedJobs();
    }
    public function getJobStatus(string $jobId): ?string
    {
        return $this->storage->getJobStatus($jobId);
    }
    public function updateJobStatus(string $jobId, string $status): void
    {
        $this->storage->updateJobStatus($jobId, $status);
    }
    
}
