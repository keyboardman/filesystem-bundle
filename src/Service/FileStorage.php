<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\Service;

use Keyboardman\FilesystemBundle\Flysystem\FilesystemMap;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

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
        private readonly FilesystemMap $filesystemMap,
    ) {
    }

    public function hasFilesystem(string $name): bool
    {
        return $this->filesystemMap->has($name);
    }

    public function write(string $filesystem, string $key, string $content, bool $overwrite = false): int
    {
        $fs = $this->filesystemMap->get($filesystem);
        $this->ensureParentDirectory($fs, $key);
        $fs->write($key, $content);

        return \strlen($content);
    }

    public function read(string $filesystem, string $key): string
    {
        $fs = $this->filesystemMap->get($filesystem);

        return $fs->read($key);
    }

    public function has(string $filesystem, string $key): bool
    {
        $fs = $this->filesystemMap->get($filesystem);

        return $fs->has($key);
    }

    /**
     * Renomme ou déplace un fichier ou un répertoire (créé via createDirectory).
     * Pour un répertoire, déplace récursivement tout son contenu vers la cible.
     *
     * @throws \InvalidArgumentException si chemin vide, contient '..', ou cible sous la source
     */
    public function rename(string $filesystem, string $sourceKey, string $targetKey): void
    {
        $sourceKey = trim($sourceKey, '/');
        $targetKey = trim($targetKey, '/');
        if ($sourceKey === '' || $targetKey === '') {
            throw new \InvalidArgumentException('Source and target paths cannot be empty.');
        }
        if (str_contains($sourceKey, '..') || str_contains($targetKey, '..')) {
            throw new \InvalidArgumentException('Paths cannot contain "..".');
        }

        $fs = $this->filesystemMap->get($filesystem);

        $sourceKeepKey = $sourceKey . '/.keep';
        if ($this->has($filesystem, $sourceKeepKey)) {
            $this->renameDirectory($fs, $filesystem, $sourceKey, $targetKey);

            return;
        }

        $fs->move($sourceKey, $targetKey);
    }

    /**
     * Retourne true si le chemin désigne un répertoire (présence de path/.keep).
     */
    public function isDirectory(string $filesystem, string $path): bool
    {
        $path = trim($path, '/');
        if ($path === '') {
            return false;
        }

        return $this->has($filesystem, $path . '/.keep');
    }

    /**
     * Retourne true si le chemin existe comme fichier ou comme répertoire (path/.keep).
     */
    public function pathExists(string $filesystem, string $path): bool
    {
        $path = trim($path, '/');
        if ($path === '') {
            return false;
        }

        return $this->has($filesystem, $path) || $this->has($filesystem, $path . '/.keep');
    }

    /**
     * Renomme un répertoire : déplace récursivement tout le contenu (fichiers et sous-répertoires).
     */
    private function renameDirectory(FilesystemOperator $fs, string $filesystem, string $sourceKey, string $targetKey): void
    {
        if ($targetKey === $sourceKey) {
            return;
        }
        $prefix = $sourceKey . '/';
        if (str_starts_with($targetKey . '/', $prefix)) {
            throw new \InvalidArgumentException('Target path cannot be inside source directory.');
        }

        $allPaths = $this->listAllKeysRecursively($fs, $sourceKey);
        usort($allPaths, fn (string $a, string $b): int => substr_count($a, '/') <=> substr_count($b, '/'));

        foreach ($allPaths as $path) {
            $suffix = substr($path, \strlen($sourceKey));
            $targetPath = $targetKey . $suffix;
            $this->ensureParentDirectory($fs, $targetPath);
            $fs->move($path, $targetPath);
        }

        // Supprimer le fichier .keep du répertoire source après avoir déplacé tout le contenu
        $sourceKeepKey = $sourceKey . '/.keep';
        if ($this->has($filesystem, $sourceKeepKey)) {
            $fs->delete($sourceKeepKey);
        }
    }

    /**
     * @return list<string> toutes les clés de fichiers sous le préfixe (récursif). Les répertoires (convention .keep) sont représentés par leur fichier .keep.
     */
    private function listAllKeysRecursively(FilesystemOperator $fs, string $prefix): array
    {
        $prefix = trim($prefix, '/');
        $keys = [];
        foreach ($fs->listContents($prefix, true) as $item) {
            \assert($item instanceof StorageAttributes);
            if ($item instanceof FileAttributes) {
                $keys[] = $item->path();
            }
        }

        return $keys;
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

        $fs = $this->filesystemMap->get($filesystem);

        if ($fs->fileExists($key)) {
            $fs->delete($key);

            return;
        }

        $keepKey = $key . '/.keep';
        if ($this->has($filesystem, $keepKey)) {
            $otherKeys = $this->listDirectChildKeys($fs, $key . '/');
            $otherKeys = array_filter($otherKeys, fn (string $k): bool => $k !== $keepKey);
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

        $fs = $this->filesystemMap->get($filesystem);
        $this->ensureParentDirectory($fs, $placeholderKey);
        $fs->write($placeholderKey, '');
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

        $fs = $this->filesystemMap->get($filesystem);
        $listPath = $path === '' ? '' : $path;

        $keys = [];
        foreach ($fs->listContents($listPath, false) as $item) {
            \assert($item instanceof StorageAttributes);
            $itemPath = $item->path();
            if ($item instanceof FileAttributes) {
                if (str_ends_with($itemPath, '/.keep')) {
                    $keys[] = substr($itemPath, 0, -\strlen('/.keep')) . '/';
                } elseif (!$this->isHidden($itemPath)) {
                    $keys[] = $itemPath;
                }
            } elseif ($item instanceof DirectoryAttributes) {
                $keys[] = rtrim($itemPath, '/') . '/';
            }
        }

        $keys = array_filter($keys, fn (string $key): bool => $this->isDirectChild($key, $path));

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
     * @return list<string> full keys under the given prefix (one level)
     */
    private function listDirectChildKeys(object $fs, string $prefix): array
    {
        $keys = [];
        foreach ($fs->listContents(trim($prefix, '/'), false) as $item) {
            \assert($item instanceof StorageAttributes);
            $keys[] = $item->path();
        }

        return $keys;
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
     * True si la clé est un enfant direct du répertoire (un seul niveau, pas de récursion).
     */
    private function isDirectChild(string $key, string $directory): bool
    {
        if ($directory === '') {
            return !str_contains($key, '/') || (substr_count($key, '/') === 1 && (str_ends_with($key, '/') || str_ends_with($key, '/.keep')));
        }
        $prefix = $directory . '/';
        if (!str_starts_with($key, $prefix)) {
            return false;
        }
        $remainder = substr($key, \strlen($prefix));
        if ($remainder === '') {
            return false;
        }
        return !str_contains(rtrim($remainder, '/'), '/');
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
     * S'assure que le répertoire parent du chemin donné existe.
     * Crée récursivement tous les répertoires parents nécessaires.
     *
     * @param FilesystemOperator $fs instance de FilesystemOperator
     * @param string             $path chemin du fichier ou répertoire
     */
    private function ensureParentDirectory(FilesystemOperator $fs, string $path): void
    {
        $parentPath = \dirname($path);
        if ($parentPath === '.' || $parentPath === '') {
            return; // Pas de répertoire parent (racine)
        }

        // Normaliser le chemin parent (enlever les slashes finaux)
        $parentPath = rtrim($parentPath, '/');

        // Vérifier si le répertoire parent existe déjà
        if ($fs->directoryExists($parentPath)) {
            return;
        }

        // Créer récursivement les répertoires parents
        $parts = explode('/', $parentPath);
        $currentPath = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $currentPath = $currentPath === '' ? $part : $currentPath . '/' . $part;
            if (!$fs->directoryExists($currentPath)) {
                $fs->createDirectory($currentPath);
            }
        }
    }
}
