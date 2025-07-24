<?php

namespace App\Factories;

use App\Contracts\CdnDriverInterface;
use App\Services\CDNs\LocalDriver;
use App\Services\CDNs\S3Driver;

class CdnFactory
{
    public static function make(): CdnDriverInterface
    {
        return match (strtolower($_ENV['STORAGE_DRIVER'] ?? 'local')) {
            's3' => new S3Driver(),
            default => new LocalDriver(),
        };
    }
}
