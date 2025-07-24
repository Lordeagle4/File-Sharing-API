<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Services\FileService;
use App\Factories\StorageFactory;
use App\Core\DotenvLoader;

DotenvLoader::load(base_path('.env'));

$storage = StorageFactory::make();

$service = new FileService($storage);

while (true) {
    $jobs = $storage->getQueuedJobs();

    foreach ($jobs as $index => $job) {
        $id = $job['id'] ?? null;
        $temp = $job['temp_path'] ?? null;
        $name = $job['original_name'] ?? null;

        // Skip if job is already being processed or missing required fields
        if (!$id || !$temp || !$name || $storage->getJobStatus($id) !== 'queued') {
            continue;
        }

        try {
            $storage->updateJobStatus($id, 'processing');
            $service->uploadFromWorker($id, $temp, $name);
            $storage->updateJobStatus($id, 'completed');

            // optionally: remove from queue (or implement persistent job table instead)
            // you can refactor `getQueuedJobs` to support a remove-by-id
        } catch (\Throwable $e) {
            $storage->updateJobStatus($id, 'failed');
            error_log("Worker error: " . $e->getMessage());
        }
    }

    sleep(1);
}
