<?php

namespace App\Storage;

use App\Contracts\StorageDriverInterface;
use PDO;

class DatabaseStorageDriver implements StorageDriverInterface
{
    protected PDO $pdo;

    public function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $dbname = $_ENV['DB_NAME'] ?? 'uploads';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host=$host;dbname=$dbname";


        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public function saveMeta(array $meta): void
    {
        $sql = "REPLACE INTO file_uploads (id, name, path, link, views, downloads)
                VALUES (:id, :name, :path, :link, :views, :downloads)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $meta['id'],
            ':name' => $meta['name'],
            ':path' => $meta['path'],
            ':link' => $meta['link'],
            ':views' => $meta['views'],
            ':downloads' => $meta['downloads'],
        ]);
    }

    public function getMeta(string $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM file_uploads WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateMeta(string $id, array $updates): void
    {
        $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($updates)));
        $updates['id'] = $id;

        $sql = "UPDATE file_uploads SET $set WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($updates);
    }

    public function findBy(string $field, string $value): ?array
    {
        $allowedFields = ['id', 'name', 'path', 'link'];

        if (!in_array($field, $allowedFields, true)) {
            return null;
        }

        $sql = "SELECT * FROM file_uploads WHERE `$field` = :value LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':value' => $value]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }


    public function queueUpload(array $job): void
    {
        $sql = "INSERT INTO upload_jobs (id, temp_path, original_name, status)
                VALUES (:id, :temp_path, :original_name, :status)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $job['id'],
            ':temp_path' => $job['temp_path'],
            ':original_name' => $job['original_name'],
            ':status' => 'queued',
        ]);
    }

    public function getQueuedJobs(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM upload_jobs WHERE status = 'queued' ORDER BY created_at ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateJobStatus(string $jobId, string $status): void
    {
        $stmt = $this->pdo->prepare("UPDATE upload_jobs SET status = :status WHERE id = :id");
        $stmt->execute([':id' => $jobId, ':status' => $status]);
    }

    public function getJobStatus(string $jobId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT status FROM upload_jobs WHERE id = :id");
        $stmt->execute([':id' => $jobId]);
        return $stmt->fetchColumn() ?: null;
    }

    public function updateJobProgress(string $id, int $progress): void
{
    $stmt = $this->pdo->prepare("
        UPDATE upload_jobs 
        SET progress = :progress, updated_at = CURRENT_TIMESTAMP 
        WHERE id = :id
    ");

    $stmt->execute([
        ':progress' => max(0, min(100, $progress)),
        ':id' => $id,
    ]);
}

public function getJobProgress(string $jobId): ?int
{
    $stmt = $this->pdo->prepare("SELECT progress FROM upload_jobs WHERE id = :id");
    $stmt->execute([':id' => $jobId]);

    $value = $stmt->fetchColumn();
    return $value !== false ? (int) $value : null;
}


}
