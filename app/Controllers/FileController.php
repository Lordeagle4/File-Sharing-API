<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\FileService;
use App\Services\UploadQueueService;

class FileController
{
    protected FileService $service;

    public function __construct()
    {
        $this->service = new FileService();
    }

    public function upload(): void
{
    Request::require(['file'], 'file');

    $file = Request::file('file');

    $allowedTypes = config('upload.allowed_types', []);
    $maxSize = config('upload.max_size', 5 * 1024 * 1024);

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $size = $file['size'];

    if (!in_array($extension, $allowedTypes)) {
        Response::json(['error' => 'File type not allowed'], 400);
        return;
    }

    if ($size > $maxSize) {
        Response::json(['error' => 'File size exceeds maximum allowed'], 400);
        return;
    }

    $queue = Request::input('queue');
    $queue = $queue === 'true' || $queue === true;

    $validUsers = ['admin', 'user', 'guest'];
    $user = strtolower(Request::input('user') ?? 'guest');
    $user = in_array($user, $validUsers) ? $user : 'guest';

    $type = Request::input('type') ?? 'general';

    $options = [
        'queue' => $queue,
        'user'  => $user,
        'type'  => $type,
    ];

    $result = $this->service->uploadFile($file, $options);

    if ($result) {
        Response::json(['message' => 'Upload successful', 'data' => $result]);
    } else {
        Response::json(['error' => 'Upload failed'], 500);
    }
}


    public function fetch(): void
    {
        Request::require(['file_id']);

        $fileId = Request::input('file_id');
        $file = $this->service->fetchFile($fileId);

        $file ? Response::json(['data' => $file]) : Response::json(['error' => 'File not found'], 404);
    }

    public function addViewCount(): void
    {
        Request::require(['file_id']);

        $fileId = Request::input('file_id');
        $this->service->addViewCount($fileId);

        Response::json(['message' => 'View count updated']);
    }

    public function addDownloadCount(): void
    {

        Request::require(['file_id']);

        $fileId = Request::input('file_id');
        $this->service->addDownloadCount($fileId);

        Response::json(['message' => 'Download count updated']);
    }

    public function fetchStats(string $id): void
    {
        $stats = $this->service->getStats($id);

        if (!$stats) {
            Response::json(['error' => 'File not found'], 404);
            return;
        }

        Response::json(['data' => $stats]);
    }



    public function uploadProgress(?string $id): void
{
    $fileId = $id;

    if (!$fileId) {
        Request::require(['file_id'], 'get');
        $fileId = Request::get('file_id');
    }

    $status = $this->service->getJobStatus($fileId);

    Response::json([
        'file_id' => $fileId,
        'status'  => $status
    ]);
}


    public function download(?string $accessCode): void
    {
        if (!$accessCode) {
            Request::require(['access_code'], 'get');
            $accessCode = Request::get('access_code');
        }
        $filePath = $this->service->resolveDownloadPath($accessCode);

        if (!$filePath || !file_exists($filePath)) {
            Response::json(['error' => 'File not found'], 404);
            return;
        }

        Response::download($filePath);
    }
}
