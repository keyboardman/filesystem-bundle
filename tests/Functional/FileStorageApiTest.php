<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\Tests\Functional;

use Keyboardman\FilesystemBundle\Tests\App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class FileStorageApiTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testUploadSuccess(): void
    {
        $client = static::createClient();
        $tmp = $this->createTempFile('hello');
        $client->request('POST', '/api/filesystem/upload', [
            'filesystem' => 'default',
            'key' => 'test.txt',
        ], [
            'file' => $tmp,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('default', $json['filesystem']);
        $this->assertSame('test.txt', $json['path']);
    }

    public function testUploadUnknownFilesystem(): void
    {
        $client = static::createClient();
        $tmp = $this->createTempFile('x');
        $client->request('POST', '/api/filesystem/upload', [
            'filesystem' => 'unknown',
        ], [
            'file' => $tmp,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUploadMultipleSuccess(): void
    {
        $client = static::createClient();
        $a = $this->createTempFile('a');
        $b = $this->createTempFile('b');
        $client->request('POST', '/api/filesystem/upload-multiple', [
            'filesystem' => 'default',
        ], [
            'files' => [$a, $b],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('paths', $json);
        $this->assertCount(2, $json['paths']);
    }

    public function testRenameSuccess(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $storage->write('default', 'old.txt', 'content');
        $client->request('POST', '/api/filesystem/rename', [
            'filesystem' => 'default',
            'source' => 'old.txt',
            'target' => 'new.txt',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($storage->has('default', 'new.txt'));
        $this->assertFalse($storage->has('default', 'old.txt'));
    }

    public function testRenameFileNotFound(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/filesystem/rename', [
            'filesystem' => 'default',
            'source' => 'missing.txt',
            'target' => 'new.txt',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testMoveSuccess(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $storage->write('default', 'from.txt', 'content');
        $client->request('POST', '/api/filesystem/move', [
            'filesystem' => 'default',
            'source' => 'from.txt',
            'target' => 'sub/to.txt',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($storage->has('default', 'sub/to.txt'));
        $this->assertFalse($storage->has('default', 'from.txt'));
    }

    public function testDeleteSuccess(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $storage->write('default', 'todelete.txt', 'x');
        $client->request('POST', '/api/filesystem/delete', [
            'filesystem' => 'default',
            'path' => 'todelete.txt',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertFalse($storage->has('default', 'todelete.txt'));
    }

    public function testDeleteNotFound(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/filesystem/delete', [
            'filesystem' => 'default',
            'path' => 'missing.txt',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testMultiFilesystem(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $storage->write('default', 'a.txt', 'a');
        $storage->write('other', 'b.txt', 'b');
        $this->assertTrue($storage->has('default', 'a.txt'));
        $this->assertTrue($storage->has('other', 'b.txt'));
        $this->assertSame('a', $storage->read('default', 'a.txt'));
        $this->assertSame('b', $storage->read('other', 'b.txt'));
    }

    private function createTempFile(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'luc_fs');
        file_put_contents($path, $content);
        return new UploadedFile($path, basename($path), null, \UPLOAD_ERR_OK, true);
    }
}
