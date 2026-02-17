<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\Controller;

use Keyboardman\FilesystemBundle\Service\FileStorage;
use Keyboardman\FilesystemBundle\Service\UploadValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FileStorageController
{
    public function __construct(
        private readonly FileStorage $fileStorage,
        private readonly UploadValidator $uploadValidator,
    ) {
    }

    #[Route('/upload', name: 'keyboardman_filesystem_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $filesystem = $request->request->getString('filesystem', 'default');
        
        // Accepter 'path' ou 'key' pour le chemin de destination
        $pathParam = $request->request->getString('path', '');
        $keyParam = $request->request->getString('key', '');
        $destinationPath = $pathParam !== '' ? $pathParam : $keyParam;

        if (!$this->fileStorage->hasFilesystem($filesystem)) {
            return new JsonResponse(['error' => 'Unknown filesystem'], Response::HTTP_BAD_REQUEST);
        }

        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return new JsonResponse(['error' => 'No valid file provided'], Response::HTTP_BAD_REQUEST);
        }

        $error = $this->uploadValidator->validate($file);
        if ($error !== null) {
            return new JsonResponse(['error' => $error], Response::HTTP_BAD_REQUEST);
        }

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            return new JsonResponse(['error' => 'Could not read file'], Response::HTTP_BAD_REQUEST);
        }

        // Déterminer le chemin final
        $fileName = $file->getClientOriginalName();
        
        if ($destinationPath !== '') {
            // Si le chemin fourni se termine par '/', c'est un répertoire : ajouter le nom du fichier
            if (str_ends_with($destinationPath, '/')) {
                $path = rtrim($destinationPath, '/') . '/' . $fileName;
            } else {
                // Vérifier si le chemin fourni est un répertoire existant
                if ($this->fileStorage->isDirectory($filesystem, $destinationPath)) {
                    // C'est un répertoire : créer le fichier à l'intérieur avec le nom original
                    $path = rtrim($destinationPath, '/') . '/' . $fileName;
                } else {
                    // Utiliser le chemin tel quel
                    $path = $destinationPath;
                }
            }
        } else {
            // Pas de chemin fourni : utiliser le nom du fichier original
            $path = $fileName;
        }
        
        try {
            $this->fileStorage->write($filesystem, $path, $content, true);
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'already a directory')) {
                return new JsonResponse(['error' => 'Cannot write to a path that is already a directory'], Response::HTTP_CONFLICT);
            }
            if (str_contains($msg, 'already exists')) {
                return new JsonResponse(['error' => $msg], Response::HTTP_CONFLICT);
            }
            return new JsonResponse(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'filesystem' => $filesystem,
            'path' => $path,
        ], Response::HTTP_CREATED);
    }

    #[Route('/upload-multiple', name: 'keyboardman_filesystem_upload_multiple', methods: ['POST'])]
    public function uploadMultiple(Request $request): JsonResponse
    {
        $filesystem = $request->request->getString('filesystem', 'default');

        if (!$this->fileStorage->hasFilesystem($filesystem)) {
            return new JsonResponse(['error' => 'Unknown filesystem'], Response::HTTP_BAD_REQUEST);
        }

        $all = $request->files->all();
        $files = [];
        foreach ($all as $v) {
            if ($v instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $files[] = $v;
            } elseif (is_array($v)) {
                foreach ($v as $f) {
                    if ($f instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                        $files[] = $f;
                    }
                }
            }
        }
        if ($files === []) {
            return new JsonResponse(['error' => 'No files provided'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                return new JsonResponse(['error' => 'No valid file provided'], Response::HTTP_BAD_REQUEST);
            }
            $error = $this->uploadValidator->validate($file);
            if ($error !== null) {
                return new JsonResponse(['error' => $error], Response::HTTP_BAD_REQUEST);
            }
        }

        $paths = [];
        foreach ($files as $file) {
            $content = file_get_contents($file->getRealPath());
            if ($content === false) {
                continue;
            }
            $path = $file->getClientOriginalName();
            try {
                $this->fileStorage->write($filesystem, $path, $content, true);
                $paths[] = $path;
            } catch (\InvalidArgumentException $e) {
                $msg = $e->getMessage();
                if (str_contains($msg, 'already a directory')) {
                    return new JsonResponse(['error' => 'Cannot write to a path that is already a directory'], Response::HTTP_CONFLICT);
                }
                if (str_contains($msg, 'already exists')) {
                    return new JsonResponse(['error' => $msg], Response::HTTP_CONFLICT);
                }
                return new JsonResponse(['error' => $msg], Response::HTTP_BAD_REQUEST);
            }
        }

        return new JsonResponse([
            'filesystem' => $filesystem,
            'paths' => $paths,
        ], Response::HTTP_CREATED);
    }

    #[Route('/rename', name: 'keyboardman_filesystem_rename', methods: ['POST'])]
    public function rename(Request $request): JsonResponse
    {
        $payload = $this->getRequestPayload($request);
        $filesystem = $payload['filesystem'] ?? $request->request->getString('filesystem', 'default');
        $source = $payload['source'] ?? $request->request->getString('source', '');
        $target = $payload['target'] ?? $request->request->getString('target', '');

        if (!\is_string($source) || trim($source) === '' || !\is_string($target) || trim($target) === '') {
            return new JsonResponse(['error' => 'source and target are required'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->fileStorage->hasFilesystem($filesystem)) {
            return new JsonResponse(['error' => 'Unknown filesystem'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->fileStorage->pathExists($filesystem, $source)) {
            return new JsonResponse(['error' => 'File or directory not found'], Response::HTTP_NOT_FOUND);
        }
        if ($this->fileStorage->pathExists($filesystem, $target)) {
            return new JsonResponse(['error' => 'Target already exists'], Response::HTTP_CONFLICT);
        }

        try {
            $this->fileStorage->rename($filesystem, $source, $target);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['filesystem' => $filesystem, 'path' => trim($target, '/')], Response::HTTP_OK);
    }

    #[Route('/move', name: 'keyboardman_filesystem_move', methods: ['POST'])]
    public function move(Request $request): JsonResponse
    {
        $payload = $this->getRequestPayload($request);
        $filesystem = $payload['filesystem'] ?? $request->request->getString('filesystem', 'default');
        $source = $payload['source'] ?? $request->request->getString('source', '');
        $target = $payload['target'] ?? $request->request->getString('target', '');

        if (!\is_string($source) || trim($source) === '' || !\is_string($target) || trim($target) === '') {
            return new JsonResponse(['error' => 'source and target are required'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->fileStorage->hasFilesystem($filesystem)) {
            return new JsonResponse(['error' => 'Unknown filesystem'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->fileStorage->pathExists($filesystem, $source)) {
            return new JsonResponse(['error' => 'File or directory not found'], Response::HTTP_NOT_FOUND);
        }
        if ($this->fileStorage->pathExists($filesystem, $target)) {
            return new JsonResponse(['error' => 'Target already exists'], Response::HTTP_CONFLICT);
        }

        try {
            $this->fileStorage->rename($filesystem, $source, $target);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
        return new JsonResponse(['filesystem' => $filesystem, 'path' => $target], Response::HTTP_OK);
    }

    #[Route('/create-directory', name: 'keyboardman_filesystem_create_directory', methods: ['POST'])]
    public function createDirectory(Request $request): JsonResponse
    {
        $payload = $this->getRequestPayload($request);
        $filesystem = $payload['filesystem'] ?? $request->request->getString('filesystem', 'default');
        $path = $payload['path'] ?? $request->request->getString('path', '');

        if (!\is_string($path) || trim($path) === '') {
            return new JsonResponse(['error' => 'path is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->fileStorage->hasFilesystem($filesystem)) {
            return new JsonResponse(['error' => 'Unknown filesystem'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->fileStorage->createDirectory($filesystem, $path);
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
            $status = str_contains($message, 'already exists') ? Response::HTTP_CONFLICT : Response::HTTP_BAD_REQUEST;
            return new JsonResponse(['error' => $message], $status);
        }

        $normalizedPath = trim($path, '/');
        return new JsonResponse([
            'filesystem' => $filesystem,
            'path' => $normalizedPath,
        ], Response::HTTP_CREATED);
    }

    #[Route('/delete', name: 'keyboardman_filesystem_delete', methods: ['POST'])]
    public function delete(Request $request): JsonResponse
    {
        $payload = $this->getRequestPayload($request);
        $filesystem = $payload['filesystem'] ?? $request->request->getString('filesystem', 'default');
        $path = $payload['path'] ?? $payload['key'] ?? $request->request->getString('path', $request->request->getString('key', ''));

        if (!\is_string($path) || trim($path) === '') {
            return new JsonResponse(['error' => 'path is required'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->fileStorage->hasFilesystem($filesystem)) {
            return new JsonResponse(['error' => 'Unknown filesystem'], Response::HTTP_BAD_REQUEST);
        }

        $normalizedPath = trim($path, '/');
        try {
            $this->fileStorage->delete($filesystem, $normalizedPath);
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();
            if ($msg === 'File or directory not found.') {
                // Suppression idempotente : déjà supprimé = succès (évite erreur sur double-clic / double requête)
                return new JsonResponse([
                    'deleted' => true,
                    'filesystem' => $filesystem,
                    'path' => $normalizedPath,
                ], Response::HTTP_OK);
            }
            if ($msg === 'Directory is not empty.') {
                return new JsonResponse(['error' => 'Directory is not empty'], Response::HTTP_CONFLICT);
            }
            return new JsonResponse(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'deleted' => true,
            'filesystem' => $filesystem,
            'path' => $normalizedPath,
        ], Response::HTTP_OK);
    }

    #[Route('/list', name: 'keyboardman_filesystem_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $filesystem = $request->query->getString('filesystem', 'default');
        $type = $request->query->get('type');
        $sort = $request->query->get('sort');
        $path = $request->query->get('path');

        if (!$this->fileStorage->hasFilesystem($filesystem)) {
            return new JsonResponse(['error' => 'Unknown filesystem'], Response::HTTP_BAD_REQUEST);
        }

        $typeParam = \is_string($type) && $type !== '' ? $type : null;
        $sortParam = \is_string($sort) && ($sort === 'asc' || $sort === 'desc') ? $sort : null;
        $pathParam = \is_string($path) ? $path : null;

        try {
            $items = $this->fileStorage->list($filesystem, $typeParam, $sortParam, $pathParam);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $paths = array_map(fn (array $item): string => $item['path'], $items);

        return new JsonResponse([
            'filesystem' => $filesystem,
            'items' => $items,
            'paths' => $paths,
        ], Response::HTTP_OK);
    }

    /**
     * Retourne le body de la requête (JSON ou form) pour supporter les deux types d'envoi.
     */
    private function getRequestPayload(Request $request): array
    {
        if (method_exists($request, 'getPayload')) {
            return $request->getPayload()->all();
        }
        $content = $request->getContent();
        if (\is_string($content) && $content !== '') {
            $decoded = json_decode($content, true);
            if (\is_array($decoded)) {
                return $decoded;
            }
        }

        return $request->request->all();
    }
}
