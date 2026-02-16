<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\Service;

use Gaufrette\FilesystemMapInterface;

final class FileStorage
{
    /** Fichier masqué : segment de chemin (ex. nom de fichier) commençant par '.' */
    private const HIDDEN_PREFIX = '.';

    /** Extensions par type de média (lowercase). Voir README pour la liste complète. */
    private const TYPE_EXTENSIONS = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'],
        'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'],
        'video' => ['mp4', 'webm', 'avi', 'mov', 'mkv', 'm4v'],
    ];

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
     * Crée un répertoire (à la racine ou dans un sous-chemin).
     * Implémentation portable : écrit un fichier placeholder path/.keep.
     * Idempotent : si le répertoire existe déjà (path/.keep présent), ne fait rien.
     *
     * @param string $path chemin du répertoire (ex. "nouveau" ou "parent/enfant")
     *
     * @throws \InvalidArgumentException si path est vide ou contient '..'
     */
    public function createDirectory(string $filesystem, string $path): void
    {
        $path = $this->normalizeDirectoryPath($path);
        $placeholderKey = $path . '/.keep';

        if ($this->has($filesystem, $path)) {
            throw new \InvalidArgumentException('A file already exists at this path.');
        }

        if ($this->has($filesystem, $placeholderKey)) {
            return;
        }

        $fs = $this->getFilesystem($filesystem);
        $fs->write($placeholderKey, '', true);
    }

    /**
     * @throws \InvalidArgumentException si path vide ou contient '..'
     */
    private function normalizeDirectoryPath(string $path): string
    {
        $path = trim($path, '/');
        if ($path === '') {
            throw new \InvalidArgumentException('Directory path cannot be empty.');
        }
        if (str_contains($path, '..')) {
            throw new \InvalidArgumentException('Directory path cannot contain "..".');
        }
        return $path;
    }

    /**
     * Liste les clés (fichiers) du filesystem. Exclut les fichiers masqués (nom commençant par '.').
     *
     * @param string      $filesystem nom du filesystem
     * @param string|null $type       filtre optionnel : 'image', 'audio' ou 'video' (par extension)
     * @param string|null $sort       tri optionnel : 'asc' ou 'desc' (par nom de clé)
     *
     * @return list<string>
     *
     * @throws \InvalidArgumentException if filesystem does not exist
     */
    public function list(string $filesystem, ?string $type = null, ?string $sort = null): array
    {
        $fs = $this->getFilesystem($filesystem);
        $result = $fs->listKeys('');
        $keys = $result['keys'] ?? [];

        $keys = array_filter($keys, fn (string $key): bool => !$this->isHidden($key));

        if ($type !== null && $type !== '') {
            $extensions = self::TYPE_EXTENSIONS[$type] ?? null;
            if ($extensions !== null) {
                $keys = array_filter($keys, fn (string $key): bool => $this->matchesType($key, $extensions));
            }
        }

        $keys = array_values($keys);

        if ($sort === 'desc') {
            rsort($keys, \SORT_STRING);
        } else {
            sort($keys, \SORT_STRING);
        }

        return $keys;
    }

    private function isHidden(string $key): bool
    {
        $basename = basename($key);
        return $basename !== '' && str_starts_with($basename, self::HIDDEN_PREFIX);
    }

    /**
     * @param list<string> $extensions
     */
    private function matchesType(string $key, array $extensions): bool
    {
        $ext = strtolower(pathinfo($key, \PATHINFO_EXTENSION));
        return $ext !== '' && \in_array($ext, $extensions, true);
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
