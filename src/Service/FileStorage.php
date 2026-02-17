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

    /** Extension → type MIME (fallback quand l’adapter ne fournit pas mimeType). */
    private const EXTENSION_MIME = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
        'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'bmp' => 'image/bmp', 'ico' => 'image/x-icon',
        'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4',
        'aac' => 'audio/aac', 'flac' => 'audio/flac',
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'avi' => 'video/x-msvideo', 'mov' => 'video/quicktime',
        'mkv' => 'video/x-matroska', 'm4v' => 'video/x-m4v',
        'txt' => 'text/plain', 'json' => 'application/json', 'pdf' => 'application/pdf',
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
        
        // Vérifier si le chemin existe déjà comme répertoire
        if ($fs->directoryExists($key)) {
            throw new \InvalidArgumentException('Cannot write to a path that is already a directory.');
        }
        
        // Vérifier si le fichier existe déjà et que l'overwrite n'est pas autorisé
        if (!$overwrite && $fs->fileExists($key)) {
            throw new \InvalidArgumentException('File already exists and overwrite is disabled.');
        }
        
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

        if ($fs->directoryExists($sourceKey)) {
            $this->renameDirectory($fs, $filesystem, $sourceKey, $targetKey);

            return;
        }

        $fs->move($sourceKey, $targetKey);
    }

    /**
     * Retourne true si le chemin désigne un répertoire (répertoire natif Flysystem).
     */
    public function isDirectory(string $filesystem, string $path): bool
    {
        $path = trim($path, '/');
        if ($path === '') {
            return false;
        }
        $fs = $this->filesystemMap->get($filesystem);

        return $fs->directoryExists($path);
    }

    /**
     * Retourne true si le chemin existe comme fichier ou comme répertoire.
     */
    public function pathExists(string $filesystem, string $path): bool
    {
        $path = trim($path, '/');
        if ($path === '') {
            return false;
        }
        $fs = $this->filesystemMap->get($filesystem);

        return $this->has($filesystem, $path) || $fs->directoryExists($path);
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

        $this->ensureParentDirectory($fs, $targetKey . '/');
        if ($allPaths === []) {
            $fs->createDirectory($targetKey);
        } else {
            foreach ($allPaths as $path) {
                $suffix = substr($path, \strlen($sourceKey));
                $targetPath = $targetKey . $suffix;
                $this->ensureParentDirectory($fs, $targetPath);
                $fs->move($path, $targetPath);
            }
        }
        $fs->deleteDirectory($sourceKey);
    }

    /**
     * @return list<string> toutes les clés de fichiers sous le préfixe (récursif).
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

        if (!$this->pathExists($filesystem, $key)) {
            throw new \InvalidArgumentException('File or directory not found.');
        }

        $fs = $this->filesystemMap->get($filesystem);

        if ($fs->fileExists($key)) {
            $fs->delete($key);

            return;
        }

        if ($fs->directoryExists($key)) {
            $children = $this->listDirectChildKeys($fs, $key . '/');
            if ($children !== []) {
                throw new \InvalidArgumentException('Directory is not empty.');
            }
            $fs->deleteDirectory($key);

            return;
        }

        throw new \InvalidArgumentException('File or directory not found.');
    }

    /**
     * Crée un répertoire (à la racine ou dans un sous-chemin).
     * Utilise le répertoire natif Flysystem (createDirectory).
     * Idempotent : si le répertoire existe déjà, ne fait rien.
     *
     * @param string $path chemin du répertoire (ex. "nouveau" ou "parent/enfant")
     *
     * @throws \InvalidArgumentException si path est vide ou contient '..'
     */
    public function createDirectory(string $filesystem, string $path): void
    {
        $path = $this->normalizeDirectoryPath($path);
        $fs = $this->filesystemMap->get($filesystem);

        if ($this->has($filesystem, $path)) {
            throw new \InvalidArgumentException('A file already exists at this path.');
        }

        if ($fs->directoryExists($path)) {
            return;
        }

        $this->ensureParentDirectory($fs, $path . '/');
        $fs->createDirectory($path);
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
     * @return list<array{path: string, type: string, mimeType?: string|null, size?: int|null}> entrées (path, type 'file'|'dir', mimeType et size pour les fichiers)
     *
     * @throws \InvalidArgumentException if filesystem does not exist or path contains '..'
     */
    public function list(string $filesystem, ?string $type = null, ?string $sort = null, ?string $path = null): array
    {
        $path = $path !== null && $path !== '' ? $this->normalizeListPath($path) : '';

        $fs = $this->filesystemMap->get($filesystem);
        $listPath = $path === '' ? '' : $path;

        $items = [];
        foreach ($fs->listContents($listPath, false) as $item) {
            \assert($item instanceof StorageAttributes);
            $itemPath = $item->path();
            if ($item instanceof FileAttributes) {
                if (!$this->isHidden($itemPath)) {
                    $mimeType = $item->mimeType() ?? $this->mimeTypeFromExtension($itemPath);
                    $items[] = [
                        'path' => $itemPath,
                        'type' => 'file',
                        'mimeType' => $mimeType,
                        'size' => $item->fileSize(),
                    ];
                }
            } elseif ($item instanceof DirectoryAttributes) {
                $dirPath = rtrim($itemPath, '/') . '/';
                $items[] = [
                    'path' => $dirPath,
                    'type' => 'dir',
                ];
            }
        }

        $items = array_filter($items, fn (array $entry): bool => $this->isDirectChild($entry['path'], $path));

        if ($type !== null && $type !== '') {
            $extensions = self::TYPE_EXTENSIONS[$type] ?? null;
            if ($extensions !== null) {
                $items = array_filter($items, fn (array $entry): bool => $entry['type'] === 'dir' || $this->matchesType($entry['path'], $extensions));
            }
        }

        $items = array_values($items);

        if ($sort === 'desc') {
            usort($items, fn (array $a, array $b): int => strcmp($b['path'], $a['path']));
        } else {
            usort($items, fn (array $a, array $b): int => strcmp($a['path'], $b['path']));
        }

        return $items;
    }

    private function mimeTypeFromExtension(string $path): ?string
    {
        $ext = strtolower(pathinfo($path, \PATHINFO_EXTENSION));

        return self::EXTENSION_MIME[$ext] ?? null;
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
            return !str_contains($key, '/') || (substr_count($key, '/') === 1 && str_ends_with($key, '/'));
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
