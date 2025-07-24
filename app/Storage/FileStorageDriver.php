<?php

namespace App\Storage;

use App\Contracts\StorageDriverInterface;

class FileStorageDriver implements StorageDriverInterface
{
    protected string $metaDir;
    protected string $queueFile;
    protected string $statusDir;
    protected string $progressDir;

    public function __construct()
    {
        $base = base_path('storage/filemeta');

        $this->metaDir     = $base . '/meta';
        $this->statusDir   = $base . '/status';
        $this->progressDir = $base . '/progress';
        $this->queueFile   = $base . '/queue.json';

        foreach ([$this->metaDir, $this->statusDir, $this->progressDir] as $dir) {
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
        file_put_contents($path, json_encode($meta, JSON_PRETTY_PRINT));
    }

    public function getMeta(string $id): ?array
    {
        $path = $this->metaDir . '/' . $id . '.json';
        if (!file_exists($path)) return null;

        return json_decode(file_get_contents($path), true) ?? null;
    }

    public function updateMeta(string $id, array $updates): void
    {
        $meta = $this->getMeta($id);
        if (!$meta) return;

        $this->saveMeta(array_merge($meta, $updates));
    }

    public function queueUpload(array $job): void
    {
        $jobs = $this->getQueuedJobs();
        $jobs[] = $job;

        file_put_contents($this->queueFile, json_encode($jobs, JSON_PRETTY_PRINT));
        $this->updateJobStatus($job['id'], 'queued');
        $this->updateJobProgress($job['id'], 0);
    }

    public function getQueuedJobs(): array
    {
        if (!file_exists($this->queueFile)) return [];

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

    public function updateJobProgress(string $jobId, int $progress): void
    {
        $progress = max(0, min(100, $progress));
        file_put_contents($this->progressDir . '/' . $jobId . '.progress', $progress);
    }

    public function getJobProgress(string $jobId): ?int
    {
        $path = $this->progressDir . '/' . $jobId . '.progress';
        return file_exists($path) ? (int) file_get_contents($path) : null;
    }

    public function findBy(string $field, string $value): ?array
    {
        foreach (glob($this->metaDir . '/*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);

            if (isset($data[$field]) && $data[$field] === $value) {
                return $data;
            }
        }

        return null;
    }

}
