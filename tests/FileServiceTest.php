<?php

use PHPUnit\Framework\TestCase;
use App\Services\FileService;
use App\Contracts\StorageDriverInterface;
use App\Contracts\CdnDriverInterface;
use App\Factories\CdnFactory as CdnManager;


class FileServiceTest extends TestCase
{
    public function test_queued_upload()
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tmpPath, 'dummy content');

        $file = [
            'name' => 'test.txt',
            'tmp_name' => $tmpPath,
            'type' => 'text/plain',
            'size' => filesize($tmpPath),
            'error' => 0,
        ];

        // Mock the storage driver
        $mockStorage = $this->createMock(StorageDriverInterface::class);
        $mockStorage->expects($this->once())
            ->method('queueUpload')
            ->with($this->callback(function ($job) {
                return isset($job['id'], $job['temp_path'], $job['original_name']);
            }));

        // Mock CDN manager
        $cdn = $this->createMock(CdnDriverInterface::class);

        // Inject mocks into FileService
        $service = new FileService($mockStorage, $cdn);

        // Run upload with queue
        $result = $service->uploadFile($file, ['queue' => true, 'user' => 'test', 'type' => 'docs']);

        // Cleanup
        unlink($tmpPath);

        // Assertions
        $this->assertIsArray($result);
        $this->assertTrue($result['queued']);
        $this->assertArrayHasKey('id', $result);
    }

    public function test_direct_upload()
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'upload_direct_');
        file_put_contents($tmpPath, 'direct content');

        $file = [
            'name' => 'direct.txt',
            'tmp_name' => $tmpPath,
            'type' => 'text/plain',
            'size' => filesize($tmpPath),
            'error' => 0,
        ];

        $mockStorage = $this->createMock(StorageDriverInterface::class);
        $mockStorage->expects($this->never())->method('queueUpload');
        $mockStorage->expects($this->once())->method('saveMeta'); // optional

        $cdn = $this->createMock(CdnDriverInterface::class);
        $cdn->method('upload')->willReturn($tmpPath); // 👈 this prevents `null` return

        $service = new FileService($mockStorage, $cdn);

        $result = $service->uploadFile($file, ['queue' => false, 'user' => 'guest', 'type' => 'general']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('path', $result);
        $this->assertEquals('direct.txt', $result['name']);
        $this->assertFileExists($result['path']);

        unlink($tmpPath);
        unlink($result['path']);
    }

    public function test_upload_fails_on_file_error()
    {
        $file = [
            'name' => 'broken.txt',
            'tmp_name' => '/tmp/fakefile.txt',
            'type' => 'text/plain',
            'size' => 0,
            'error' => UPLOAD_ERR_CANT_WRITE,
        ];

        $mockStorage = $this->createMock(StorageDriverInterface::class);
        $cdn = $this->createMock(CdnDriverInterface::class);

        $service = new FileService($mockStorage, $cdn);
        (new \ReflectionClass($service))->getProperty('cdnManager')->setValue($service, $cdn);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Upload failed with error code: ' . UPLOAD_ERR_CANT_WRITE);

        $service->uploadFile($file, ['queue' => false]);
    }

    public function test_upload_triggers_cdn_upload_if_enabled()
    {
        putenv('CDN_ENABLED=true');

        $tmpPath = tempnam(sys_get_temp_dir(), 'cdn_file_');
        file_put_contents($tmpPath, 'cdn test');

        $file = [
            'name' => 'cdn.txt',
            'tmp_name' => $tmpPath,
            'type' => 'text/plain',
            'size' => filesize($tmpPath),
            'error' => 0,
        ];

        $mockStorage = $this->createMock(StorageDriverInterface::class);

        $cdn = $this->getMockBuilder(CdnDriverInterface::class)
            ->onlyMethods(['upload'])
            ->getMock();

        $cdn->expects($this->once())->method('upload')
            ->with(
                $tmpPath,
                $this->isType('string')
            )
            ->willReturn('https://cdn.example.com/cdn.txt');

        $service = new FileService($mockStorage, $cdn);
        $service->uploadFile($file, ['queue' => false]);

        unlink($tmpPath);
    }
}
