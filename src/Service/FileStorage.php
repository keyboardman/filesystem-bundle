<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\Service;

use Gaufrette\FilesystemMapInterface;

final class FileStorage
{
    public function __construct(
        private readonly FilesystemMapInterface $filesystemMap,
    ) {
    }

    public function hasFilesystem(string $name): bool
    {
        return $this->filesystemMap->has($name);
    }

    public function write(string $filesystem, string $key, string $content, bool $overwrite = false): int
    {
        $fs = $this->getFilesystem($filesystem);
        return $fs->write($key, $content, $overwrite);
    }

    public function read(string $filesystem, string $key): string
    {
        $fs = $this->getFilesystem($filesystem);
        return $fs->read($key);
    }

    public function has(string $filesystem, string $key): bool
    {
        $fs = $this->getFilesystem($filesystem);
        return $fs->has($key);
    }

    public function rename(string $filesystem, string $sourceKey, string $targetKey): void
    {
        $fs = $this->getFilesystem($filesystem);
        $fs->rename($sourceKey, $targetKey);
    }

    public function delete(string $filesystem, string $key): void
    {
        $fs = $this->getFilesystem($filesystem);
        $fs->delete($key);
    }

    /**
     * @throws \InvalidArgumentException if filesystem does not exist
     */
    private function getFilesystem(string $name): \Gaufrette\FilesystemInterface
    {
        if (!$this->filesystemMap->has($name)) {
            throw new \InvalidArgumentException(sprintf('There is no filesystem defined having "%s" name.', $name));
        }
        return $this->filesystemMap->get($name);
    }
}
