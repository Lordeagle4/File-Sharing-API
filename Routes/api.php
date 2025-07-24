<?php

use App\Core\Router;
use App\Controllers\FileController;

Router::post('/upload', [FileController::class, 'upload']);
Router::post('/fetch', [FileController::class, 'fetch']);
Router::post('/addViewCount', [FileController::class, 'addViewCount']);
Router::post('/addDownloadCount', [FileController::class, 'addDownloadCount']);
Router::get('/fetchStats/{id}', [FileController::class, 'fetchStats']);
Router::get('/upload/progress/{id}', [FileController::class, 'uploadProgress']);
Router::get('/download/{accessCode}', [FileController::class, 'download']);

