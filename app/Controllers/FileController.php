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
        $result = $this->service->uploadFile($file);

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



    public function uploadProgress(): void
    {
        Request::require(['file_id'], 'get');

        $fileId = Request::get('file_id');
        $status = UploadQueueService::getProgress($fileId);

        Response::json(['file_id' => $fileId, 'status' => $status]);
    }

    public function download(string $accessCode): void
    {
        $filePath = $this->service->resolveDownloadPath($accessCode);

        if (!$filePath || !file_exists($filePath)) {
            Response::json(['error' => 'File not found'], 404);
            return;
        }

        Response::download($filePath);
    }
}
