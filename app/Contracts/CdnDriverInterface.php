<?php

namespace App\Contracts;

interface CdnDriverInterface
{
    /**
     * Upload a file and return a public URL to access it
     */
    public function upload(string $path, string $key): string;
}
