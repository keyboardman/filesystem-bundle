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
        $tmp = $this->createTempFile('hello', 'test.jpg');
        $client->request('POST', '/api/filesystem/upload', [
            'filesystem' => 'default',
            'key' => 'test.jpg',
        ], [
            'file' => $tmp,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('default', $json['filesystem']);
        $this->assertSame('test.jpg', $json['path']);
    }

    public function testUploadUnknownFilesystem(): void
    {
        $client = static::createClient();
        $tmp = $this->createTempFile('x', 'x.jpg');
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
        $a = $this->createTempFile('a', 'a.png');
        $b = $this->createTempFile('b', 'b.mp3');
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

    public function testCreateDirectoryAtRootSuccess(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $client->request('POST', '/api/filesystem/create-directory', [
            'filesystem' => 'default',
            'path' => 'nouveau-dossier',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('default', $json['filesystem']);
        $this->assertSame('nouveau-dossier', $json['path']);
        $this->assertTrue($storage->has('default', 'nouveau-dossier/.keep'));
    }

    public function testCreateDirectoryNestedSuccess(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $client->request('POST', '/api/filesystem/create-directory', [
            'filesystem' => 'default',
            'path' => 'parent/enfant/sous-dossier',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('parent/enfant/sous-dossier', $json['path']);
        $this->assertTrue($storage->has('default', 'parent/enfant/sous-dossier/.keep'));
    }

    public function testCreateDirectoryUnknownFilesystem(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/filesystem/create-directory', [
            'filesystem' => 'unknown',
            'path' => 'dossier',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateDirectoryPathExistsAsFile(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $storage->write('default', 'existing.txt', 'content');
        $client->request('POST', '/api/filesystem/create-directory', [
            'filesystem' => 'default',
            'path' => 'existing.txt',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('A file already exists at this path.', $json['error']);
    }

    public function testCreateDirectoryEmptyPath(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/filesystem/create-directory', [
            'filesystem' => 'default',
            'path' => '',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateDirectoryPathWithParentRef(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/filesystem/create-directory', [
            'filesystem' => 'default',
            'path' => 'foo/../bar',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateDirectoryIdempotent(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $client->request('POST', '/api/filesystem/create-directory', [
            'filesystem' => 'default',
            'path' => 'idempotent-dir',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $client->request('POST', '/api/filesystem/create-directory', [
            'filesystem' => 'default',
            'path' => 'idempotent-dir',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertTrue($storage->has('default', 'idempotent-dir/.keep'));
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

    public function testListSuccess(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $storage->write('other', 'list-a.txt', 'a');
        $storage->write('other', 'list-b.txt', 'b');
        $client->request('GET', '/api/filesystem/list', ['filesystem' => 'other']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('paths', $json);
        $this->assertSame('other', $json['filesystem']);
        $this->assertContains('list-a.txt', $json['paths']);
        $this->assertContains('list-b.txt', $json['paths']);
    }

    public function testListSortedAsc(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $storage->write('other', 'list-z.txt', 'z');
        $storage->write('other', 'list-aa.txt', 'a');
        $client->request('GET', '/api/filesystem/list', ['filesystem' => 'other', 'sort' => 'asc']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $json = json_decode($client->getResponse()->getContent(), true);
        $paths = $json['paths'];
        $posAa = array_search('list-aa.txt', $paths, true);
        $posZ = array_search('list-z.txt', $paths, true);
        $this->assertNotFalse($posAa);
        $this->assertNotFalse($posZ);
        $this->assertLessThan($posZ, $posAa, 'asc: list-aa.txt must appear before list-z.txt');
    }

    public function testListSortedDesc(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $storage->write('other', 'list-aa.txt', 'a');
        $storage->write('other', 'list-z.txt', 'z');
        $client->request('GET', '/api/filesystem/list', ['filesystem' => 'other', 'sort' => 'desc']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $json = json_decode($client->getResponse()->getContent(), true);
        $paths = $json['paths'];
        $posAa = array_search('list-aa.txt', $paths, true);
        $posZ = array_search('list-z.txt', $paths, true);
        $this->assertNotFalse($posAa);
        $this->assertNotFalse($posZ);
        $this->assertLessThan($posAa, $posZ, 'desc: list-z.txt must appear before list-aa.txt');
    }

    public function testListFiltersByTypeImage(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $storage->write('other', 'list-photo.jpg', 'x');
        $storage->write('other', 'list-doc.txt', 'y');
        $client->request('GET', '/api/filesystem/list', ['filesystem' => 'other', 'type' => 'image']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertContains('list-photo.jpg', $json['paths']);
        $this->assertNotContains('list-doc.txt', $json['paths']);
    }

    public function testListExcludesHiddenFiles(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $storage->write('other', 'list-visible.txt', 'v');
        $storage->write('other', '.list-hidden', 'h');
        $client->request('GET', '/api/filesystem/list', ['filesystem' => 'other']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertContains('list-visible.txt', $json['paths']);
        $this->assertNotContains('.list-hidden', $json['paths']);
    }

    public function testListUnknownFilesystem(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/filesystem/list', ['filesystem' => 'unknown']);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUploadVideoAccepted(): void
    {
        $client = static::createClient();
        $tmp = $this->createTempFile('fake-video-content', 'clip.mp4');
        $client->request('POST', '/api/filesystem/upload', [
            'filesystem' => 'default',
        ], [
            'file' => $tmp,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('clip.mp4', $json['path']);
    }

    public function testUploadFileTypeNotAllowed(): void
    {
        $client = static::createClient();
        $tmp = $this->createTempFile('content', 'script.php');
        $client->request('POST', '/api/filesystem/upload', [
            'filesystem' => 'default',
        ], [
            'file' => $tmp,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('File type not allowed', $json['error']);
    }

    public function testUploadFileSizeExceedsLimit(): void
    {
        $client = static::createClient();
        // Test config sets max_upload_size: 100
        $largeContent = str_repeat('x', 200);
        $tmp = $this->createTempFile($largeContent, 'large.jpg');
        $client->request('POST', '/api/filesystem/upload', [
            'filesystem' => 'default',
        ], [
            'file' => $tmp,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('File size exceeds limit', $json['error']);
    }

    public function testUploadMultipleWithInvalidFileRejectsEntireRequest(): void
    {
        $client = static::createClient();
        $storage = $client->getContainer()->get(\Keyboardman\FilesystemBundle\Service\FileStorage::class);
        $valid = $this->createTempFile('a', 'valid.png');
        $invalid = $this->createTempFile('b', 'invalid.exe');
        $client->request('POST', '/api/filesystem/upload-multiple', [
            'filesystem' => 'default',
        ], [
            'files' => [$valid, $invalid],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertFalse($storage->has('default', 'valid.png'));
    }

    private function createTempFile(string $content, ?string $originalName = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'luc_fs');
        file_put_contents($path, $content);
        return new UploadedFile($path, $originalName ?? basename($path), null, \UPLOAD_ERR_OK, true);
    }
}
