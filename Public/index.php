<?php

use App\Core\DotenvLoader;
use App\Core\Router;

require_once __DIR__ . '/../vendor/autoload.php';

DotenvLoader::load(base_path('.env'));

// Set common headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load routes and dispatch request
require_once base_path('routes/api.php');
Router::dispatch();
