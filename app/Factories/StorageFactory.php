<?php
namespace App\Factories;

use App\Contracts\StorageDriverInterface;
use App\Storage\FileStorageDriver;
use App\Storage\DatabaseStorageDriver;
use App\Storage\RedisStorageDriver;

class StorageFactory
{
    public static function make(): StorageDriverInterface
    {
        return match ($_ENV['STORAGE_BACKEND'] ?? 'file') {
            'redis' => new RedisStorageDriver(),
            'database' => new DatabaseStorageDriver(),
            default => new FileStorageDriver(),
        };
    }
}
