<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\Tests\Functional;

use Keyboardman\FilesystemBundle\Service\FileStorage;
use Keyboardman\FilesystemBundle\Tests\App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Vérifie qu'un filesystem (avec ou sans cache) permet read/write.
 * La config "cache" du bundle utilise le pattern Gaufrette (adapter cache + source).
 * Si Gaufrette\Adapter\Cache n'est pas disponible, les filesystems sans cache sont utilisés.
 */
final class CacheFilesystemTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testFilesystemReadWriteBehavior(): void
    {
        self::bootKernel();
        $storage = self::getContainer()->get(FileStorage::class);
        $storage->write('default', 'cached.txt', 'content');
        self::assertTrue($storage->has('default', 'cached.txt'));
        self::assertSame('content', $storage->read('default', 'cached.txt'));
    }
}
