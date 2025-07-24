<?php
namespace App\Contracts;

interface StorageDriverInterface
{
    public function saveMeta(array $meta): void;
    public function getMeta(string $id): ?array;
    public function updateMeta(string $id, array $updates): void;
    public function findBy(string $field, string $value): ?array;

    public function queueUpload(array $job): void;
    public function getQueuedJobs(): array;
    public function updateJobStatus(string $jobId, string $status): void;
    public function getJobStatus(string $jobId): ?string;

    public function updateJobProgress(string $jobId, int $progress):void;
    public function getJobProgress(string $jobId):?int;
}
