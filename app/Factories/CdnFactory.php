<?php

namespace App\Factories;

use App\Contracts\CdnDriverInterface;
use App\Services\CDNs\LocalDriver;
use App\Services\CDNs\S3Driver;

class CdnFactory
{
    protected static ?CdnDriverInterface $instance = null;

    public static function make(): CdnDriverInterface
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = match (strtolower($_ENV['STORAGE_DRIVER'] ?? 'local')) {
            's3' => new S3Driver(),
            default => new LocalDriver(),
        };

        return self::$instance;
    }
}
