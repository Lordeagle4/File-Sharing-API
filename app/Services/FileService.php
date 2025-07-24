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
    $size = $file['size'];

    $isQueued = $options['queue'] ?? false;
    $user = $options['user'] ?? 'guest';
    $type = $options['type'] ?? 'general';

    $id = md5(uniqid($originalName, true));

    // Persistent path to store temp file for queued uploads
    if ($isQueued) {
        $safeTempDir = base_path('storage/uploads/tmp');
        if (!is_dir($safeTempDir)) {
            mkdir($safeTempDir, 0777, true);
        }

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeTempPath = $safeTempDir . '/' . $id . '.' . $extension;

        // Move the uploaded temp file to a persistent location
        if (!move_uploaded_file($tmpPath, $safeTempPath)) {
            throw new \Exception('Failed to persist temp file for queued upload.');
        }

        $this->storage->queueUpload([
            'id' => $id,
            'temp_path' => $safeTempPath,
            'original_name' => $originalName,
            'user' => $user,
            'type' => $type,
        ]);

        return ['id' => $id, 'queued' => true];
    }

    return $this->processUpload($id, $tmpPath, $originalName, $user, $type, $size);
}


public function uploadFromWorker(string $fileId, string $tempPath, string $originalName, string $user = 'guest', string $type = 'general'): void
{
    $this->processUpload($fileId, $tempPath, $originalName, $user, $type);
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


    public function resolveDownloadPath(string $accessCode): ?string
    {
        $downloadsDir = base_path('public/downloads');
        $symlinkPath = "$downloadsDir/$accessCode";

        if (file_exists($symlinkPath)) {
            return $symlinkPath;
        }

        $expectedLink = rtrim($_ENV['APP_URL'], '/') . '/download/' . $accessCode;

        $meta = $this->storage->findBy('link', $expectedLink);
        if (isset($meta['path'])) {
            $absolutePath = base_path($meta['path']);
            return $absolutePath;
        }

        return null;
    }

    public function getFileByAccessCode(string $accessCode): ?array
    {
        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $accessCode)) {
            return null;
        }
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

    private function processUpload(string $id, string $tempPath, string $originalName, string $user, string $type, ?int $size = null): ?array
{
    $config = config('upload');

    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    if (!in_array(strtolower($extension), $config['allowed_types'])) {
        throw new \Exception("File type '$extension' is not allowed.");
    }

    if (!$size) {
        $size = filesize($tempPath);
    }

    if ($size > $config['max_size']) {
        throw new \Exception("File exceeds max upload size of {$config['max_file_size']} bytes.");
    }

    $subfolder = date('Y/m/d') . '/' . $user . '/' . $type;
    $fullDir = $this->uploadDir . '/' . $subfolder;

    if (!is_dir(base_path($fullDir))) {
        mkdir(base_path($fullDir), 0777, true);
    }

    $filename = uniqid() . '.' . $extension;
    $destPath = "$fullDir/$filename";

    if (!is_writable($fullDir)) {
        throw new \Exception("Upload directory '$fullDir' is not writable.");
    }

    if (!copy($tempPath, $destPath)) {
        throw new \Exception("Failed to copy file to destination.");
    }

    $link = $this->cdnManager->upload($destPath, "$subfolder/$filename");

    $meta = [
        'id'       => $id,
        'name'     => $originalName,
        'path'     => $_ENV['STORAGE_DRIVER'] === 'local' ? $destPath : $link,
        'user'     => $user,
        'type'     => $type,
        'size'     => $size,
        'link'     => $link,
        'views'    => 0,
        'downloads'=> 0,
        'status'   => 'completed'
    ];

    $this->storage->saveMeta($meta);

    if (str_contains($tempPath, sys_get_temp_dir())) {
        unlink($tempPath);
    }

    return $meta;
}


}
