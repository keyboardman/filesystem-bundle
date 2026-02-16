<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\Controller;

use Keyboardman\FilesystemBundle\Service\FileStorage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FileStorageController
{
    public function __construct(
        private readonly FileStorage $fileStorage,
    ) {
    }

    #[Route('/upload', name: 'keyboardman_filesystem_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $filesystem = $request->request->getString('filesystem', 'default');
        $key = $request->request->getString('key', '');

        if (!$this->fileStorage->hasFilesystem($filesystem)) {
            return new JsonResponse(['error' => 'Unknown filesystem'], Response::HTTP_BAD_REQUEST);
        }

        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return new JsonResponse(['error' => 'No valid file provided'], Response::HTTP_BAD_REQUEST);
        }

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            return new JsonResponse(['error' => 'Could not read file'], Response::HTTP_BAD_REQUEST);
        }

        $path = $key !== '' ? $key : $file->getClientOriginalName();
        $this->fileStorage->write($filesystem, $path, $content, true);

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

        $paths = [];
        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }
            $content = file_get_contents($file->getRealPath());
            if ($content === false) {
                continue;
            }
            $path = $file->getClientOriginalName();
            $this->fileStorage->write($filesystem, $path, $content, true);
            $paths[] = $path;
        }

        return new JsonResponse([
            'filesystem' => $filesystem,
            'paths' => $paths,
        ], Response::HTTP_CREATED);
    }

    #[Route('/rename', name: 'keyboardman_filesystem_rename', methods: ['POST'])]
    public function rename(Request $request): JsonResponse
    {
        $filesystem = $request->request->getString('filesystem', 'default');
        $source = $request->request->getString('source', '');
        $target = $request->request->getString('target', '');

        if ($source === '' || $target === '') {
            return new JsonResponse(['error' => 'source and target are required'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->fileStorage->hasFilesystem($filesystem)) {
            return new JsonResponse(['error' => 'Unknown filesystem'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->fileStorage->has($filesystem, $source)) {
            return new JsonResponse(['error' => 'File not found'], Response::HTTP_NOT_FOUND);
        }
        if ($this->fileStorage->has($filesystem, $target)) {
            return new JsonResponse(['error' => 'Target already exists'], Response::HTTP_CONFLICT);
        }

        $this->fileStorage->rename($filesystem, $source, $target);
        return new JsonResponse(['filesystem' => $filesystem, 'path' => $target], Response::HTTP_OK);
    }

    #[Route('/move', name: 'keyboardman_filesystem_move', methods: ['POST'])]
    public function move(Request $request): JsonResponse
    {
        $filesystem = $request->request->getString('filesystem', 'default');
        $source = $request->request->getString('source', '');
        $target = $request->request->getString('target', '');

        if ($source === '' || $target === '') {
            return new JsonResponse(['error' => 'source and target are required'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->fileStorage->hasFilesystem($filesystem)) {
            return new JsonResponse(['error' => 'Unknown filesystem'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->fileStorage->has($filesystem, $source)) {
            return new JsonResponse(['error' => 'File not found'], Response::HTTP_NOT_FOUND);
        }
        if ($this->fileStorage->has($filesystem, $target)) {
            return new JsonResponse(['error' => 'Target already exists'], Response::HTTP_CONFLICT);
        }

        $this->fileStorage->rename($filesystem, $source, $target);
        return new JsonResponse(['filesystem' => $filesystem, 'path' => $target], Response::HTTP_OK);
    }

    #[Route('/delete', name: 'keyboardman_filesystem_delete', methods: ['POST'])]
    public function delete(Request $request): JsonResponse
    {
        $filesystem = $request->request->getString('filesystem', 'default');
        $path = $request->request->getString('path', '');

        if ($path === '') {
            return new JsonResponse(['error' => 'path is required'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->fileStorage->hasFilesystem($filesystem)) {
            return new JsonResponse(['error' => 'Unknown filesystem'], Response::HTTP_BAD_REQUEST);
        }
        if (!$this->fileStorage->has($filesystem, $path)) {
            return new JsonResponse(['error' => 'File not found'], Response::HTTP_NOT_FOUND);
        }

        $this->fileStorage->delete($filesystem, $path);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
