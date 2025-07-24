<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\FileService;
use App\Services\UploadQueueService;

$jobsDir = base_path('storage/jobs');

foreach (glob("$jobsDir/*.json") as $jobFile) {
    $job = json_decode(file_get_contents($jobFile), true);

    if ($job['status'] === 'queued') {
        $service = new FileService();
        $service->uploadFromWorker($job['file_id'], $job['temp_path'], $job['original_name']);
        UploadQueueService::markComplete($job['file_id']);
    }
}
