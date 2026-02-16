<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadValidator
{
    /** Extensions par type (lowercase), aligné avec FileStorage::TYPE_EXTENSIONS */
    private const TYPE_EXTENSIONS = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'],
        'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'],
        'video' => ['mp4', 'webm', 'avi', 'mov', 'mkv', 'm4v'],
    ];

    /** @var list<string> extensions autorisées (lowercase) */
    private readonly array $allowedExtensions;

    public function __construct(
        array $allowedTypes,
        private readonly int $maxUploadSize,
    ) {
        $extensions = [];
        foreach ($allowedTypes as $type) {
            $typeExt = self::TYPE_EXTENSIONS[$type] ?? null;
            if ($typeExt !== null) {
                $extensions = array_merge($extensions, $typeExt);
            }
        }
        $this->allowedExtensions = array_values(array_unique(array_map('strtolower', $extensions)));
    }

    /**
     * Valide le fichier uploadé. Retourne null si valide, sinon le message d'erreur.
     */
    public function validate(UploadedFile $file): ?string
    {
        $ext = strtolower(pathinfo($file->getClientOriginalName(), \PATHINFO_EXTENSION));
        if ($ext === '' || !\in_array($ext, $this->allowedExtensions, true)) {
            return 'File type not allowed';
        }

        $size = $file->getSize();
        if ($size === false || $size > $this->maxUploadSize) {
            return 'File size exceeds limit';
        }

        return null;
    }
}
