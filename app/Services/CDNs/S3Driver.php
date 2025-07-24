<?php

namespace App\Services\CDNs;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use App\Contracts\CdnDriverInterface;

class S3Driver implements CdnDriverInterface
{
    private S3Client $client;
    private string $bucket;

    public function __construct()
    {
        $this->bucket = $_ENV['CDN_BUCKET'] ?? '';
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $_ENV['CDN_REGION'] ?? 'us-east-1',
            'credentials' => [
                'key' => $_ENV['CDN_KEY'],
                'secret' => $_ENV['CDN_SECRET'],
            ],
        ]);
    }

    public function upload(string $path, string $key): string
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'SourceFile' => $path,
                'ACL' => 'public-read',
            ]);

            return $this->client->getObjectUrl($this->bucket, $key);
        } catch (S3Exception $e) {
            throw new \Exception('S3 upload failed: ' . $e->getMessage());
        }
    }
}
