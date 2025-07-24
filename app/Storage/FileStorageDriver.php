<?php

namespace App\Storage;

use App\Contracts\StorageDriverInterface;

class FileStorageDriver implements StorageDriverInterface
{
    protected string $metaDir;
    protected string $queueFile;
    protected string $statusDir;

    public function __construct()
    {
        $base = base_path('storage/filemeta');

        $this->metaDir = $base . '/meta';
        $this->statusDir = $base . '/status';
        $this->queueFile = $base . '/queue.json';

        foreach ([$this->metaDir, $this->statusDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        if (!file_exists($this->queueFile)) {
            file_put_contents($this->queueFile, json_encode([]));
        }
    }

    public function saveMeta(array $meta): void
    {
        $path = $this->metaDir . '/' . $meta['id'] . '.json';
        file_put_contents($path, json_encode($meta));
    }

    public function getMeta(string $id): ?array
    {
        $path = $this->metaDir . '/' . $id . '.json';
        return file_exists($path) ? json_decode(file_get_contents($path), true) : null;
    }

    public function updateMeta(string $id, array $updates): void
    {
        $meta = $this->getMeta($id);
        if (!$meta) return;

        $meta = array_merge($meta, $updates);
        $this->saveMeta($meta);
    }

    public function queueUpload(array $job): void
    {
        $jobs = $this->getQueuedJobs();
        $jobs[] = $job;
        file_put_contents($this->queueFile, json_encode($jobs));
        $this->updateJobStatus($job['id'], 'queued');
    }

    public function getQueuedJobs(): array
    {
        return json_decode(file_get_contents($this->queueFile), true) ?? [];
    }

    public function updateJobStatus(string $jobId, string $status): void
    {
        file_put_contents($this->statusDir . '/' . $jobId . '.status', $status);
    }

    public function getJobStatus(string $jobId): ?string
    {
        $path = $this->statusDir . '/' . $jobId . '.status';
        return file_exists($path) ? file_get_contents($path) : null;
    }

    public function findBy(string $field, string $value): ?array
{
    $files = glob($this->metaDir . '/*.json');

    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);

        if (isset($data[$field]) && $data[$field] === $value) {
            return $data;
        }
    }

    return null;
}

}
