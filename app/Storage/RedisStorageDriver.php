<?php

namespace App\Storage;

use App\Contracts\StorageDriverInterface;
use Predis\Client as RedisClient;

class RedisStorageDriver implements StorageDriverInterface
{
    protected RedisClient $redis;
    protected string $metaPrefix = 'file:meta:';
    protected string $statusPrefix = 'upload:status:';
    protected string $queueKey = 'upload:queue';

    public function __construct()
    {
        $this->redis = new RedisClient([
            'scheme' => 'tcp',
            'host'   => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port'   => $_ENV['REDIS_PORT'] ?? 6379,
        ]);
    }

    public function saveMeta(array $meta): void
    {
        $this->redis->set($this->metaPrefix . $meta['id'], json_encode($meta));
    }

    public function getMeta(string $id): ?array
    {
        $raw = $this->redis->get($this->metaPrefix . $id);
        return $raw ? json_decode($raw, true) : null;
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
        $this->redis->rpush($this->queueKey, json_encode($job));
        $this->updateJobStatus($job['id'], 'queued');
    }

    public function getQueuedJobs(): array
    {
        $jobs = $this->redis->lrange($this->queueKey, 0, -1);
        return array_map(fn($job) => json_decode($job, true), $jobs);
    }

    public function updateJobStatus(string $jobId, string $status): void
    {
        $this->redis->set($this->statusPrefix . $jobId, $status);
    }

    public function getJobStatus(string $jobId): ?string
    {
        return $this->redis->get($this->statusPrefix . $jobId);
    }

    public function findBy(string $field, string $value): ?array
{
    $keys = $this->redis->keys($this->metaPrefix . '*');

    foreach ($keys as $key) {
        $meta = json_decode($this->redis->get($key), true);

        if (isset($meta[$field]) && $meta[$field] === $value) {
            return $meta;
        }
    }

    return null;
}

}
