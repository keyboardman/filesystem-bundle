<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\Flysystem;

use League\Flysystem\FilesystemOperator;

/**
 * Map de filesystems nommés (nom → FilesystemOperator).
 * Utilisé par FileStorage pour résoudre le filesystem par nom.
 */
final class FilesystemMap
{
    /** @var array<string, FilesystemOperator> */
    private array $filesystems = [];

    public function set(string $name, FilesystemOperator $filesystem): void
    {
        $this->filesystems[$name] = $filesystem;
    }

    public function has(string $name): bool
    {
        return isset($this->filesystems[$name]);
    }

    public function get(string $name): FilesystemOperator
    {
        if (!isset($this->filesystems[$name])) {
            throw new \InvalidArgumentException(sprintf('There is no filesystem defined having "%s" name.', $name));
        }
        return $this->filesystems[$name];
    }
}
