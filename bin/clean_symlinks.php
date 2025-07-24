<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\DotenvLoader;
use App\Services\SymlinkManager;

DotenvLoader::load(base_path('.env'));

SymlinkManager::cleanOldLinks();