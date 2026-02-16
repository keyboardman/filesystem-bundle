<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\Service;

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

    /**
     * @param object $filesystemMap Gaufrette\FilesystemMapInterface (has(string), get(string))
     */
    public function __construct(
        private readonly object $filesystemMap,
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

    /**
     * Supprime un fichier ou un répertoire (créé via createDirectory).
     * Un répertoire n'est supprimable que s'il est vide (aucun fichier ni sous-dossier).
     *
     * @throws \InvalidArgumentException si path vide, contient '..', répertoire non vide, ou ressource introuvable
     */
    public function delete(string $filesystem, string $key): void
    {
        $key = trim($key, '/');
        if ($key === '') {
            throw new \InvalidArgumentException('Path cannot be empty.');
        }
        if (str_contains($key, '..')) {
            throw new \InvalidArgumentException('Path cannot contain "..".');
        }

        $fs = $this->getFilesystem($filesystem);

        if ($this->has($filesystem, $key)) {
            $fs->delete($key);
            return;
        }

        $keepKey = $key . '/.keep';
        if ($this->has($filesystem, $keepKey)) {
            $result = $fs->listKeys($key . '/');
            $keys = $result['keys'] ?? [];
            $otherKeys = array_filter($keys, fn (string $k): bool => $k !== $keepKey);
            if ($otherKeys !== []) {
                throw new \InvalidArgumentException('Directory is not empty.');
            }
            $fs->delete($keepKey);
            return;
        }

        throw new \InvalidArgumentException('File or directory not found.');
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
     * Liste uniquement les fichiers et dossiers du répertoire courant (un niveau, pas de sous-dossiers).
     * Exclut les fichiers masqués (nom commençant par '.').
     *
     * @param string      $filesystem nom du filesystem
     * @param string|null $type       filtre optionnel : 'image', 'audio' ou 'video' (par extension)
     * @param string|null $sort       tri optionnel : 'asc' ou 'desc' (par nom de clé)
     * @param string|null $path       répertoire à lister (vide ou null = racine), ex. "subdir" ou "parent/enfant"
     *
     * @return list<string> clés (fichiers ou dossiers avec slash final)
     *
     * @throws \InvalidArgumentException if filesystem does not exist or path contains '..'
     */
    public function list(string $filesystem, ?string $type = null, ?string $sort = null, ?string $path = null): array
    {
        $path = $path !== null && $path !== '' ? $this->normalizeListPath($path) : '';

        $fs = $this->getFilesystem($filesystem);
        $prefix = $path === '' ? '' : $path . '/';
        $result = $fs->listKeys($prefix);
        $keys = $result['keys'] ?? [];

        $keys = array_filter($keys, fn (string $key): bool => $this->isDirectChild($key, $path));

        $dirPlaceholders = [];
        $keys = array_filter($keys, function (string $key) use (&$dirPlaceholders): bool {
            if (str_ends_with($key, '/.keep')) {
                $dirPlaceholders[] = substr($key, 0, -\strlen('/.keep')) . '/';
                return false;
            }
            return !$this->isHidden($key);
        });
        $keys = array_merge(array_values($keys), $dirPlaceholders);

        if ($type !== null && $type !== '') {
            $extensions = self::TYPE_EXTENSIONS[$type] ?? null;
            if ($extensions !== null) {
                $keys = array_filter($keys, fn (string $key): bool => str_ends_with($key, '/') || $this->matchesType($key, $extensions));
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

    /**
     * True si la clé est un enfant direct du répertoire (un seul niveau, pas de récursion).
     */
    private function isDirectChild(string $key, string $directory): bool
    {
        if ($directory === '') {
            if (str_contains($key, '/')) {
                return str_ends_with($key, '/.keep') && substr_count($key, '/') === 1;
            }
            return true;
        }
        $prefix = $directory . '/';
        if (!str_starts_with($key, $prefix)) {
            return false;
        }
        $remainder = substr($key, \strlen($prefix));
        if ($remainder === '' || str_contains($remainder, '/')) {
            return $remainder !== '' && str_ends_with($key, '/.keep') && substr_count($remainder, '/') === 1;
        }
        return true;
    }

    /**
     * @throws \InvalidArgumentException si path contient '..'
     */
    private function normalizeListPath(string $path): string
    {
        $path = trim($path, '/');
        if (str_contains($path, '..')) {
            throw new \InvalidArgumentException('Path cannot contain "..".');
        }
        return $path;
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
     * @return object Filesystem instance (Gaufrette\FilesystemInterface)
     * @throws \InvalidArgumentException if filesystem does not exist
     */
    private function getFilesystem(string $name): object
    {
        if (!$this->filesystemMap->has($name)) {
            throw new \InvalidArgumentException(sprintf('There is no filesystem defined having "%s" name.', $name));
        }
        return $this->filesystemMap->get($name);
    }
}
