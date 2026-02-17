<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\DependencyInjection;

use Keyboardman\FilesystemBundle\Flysystem\FilesystemMap;
use Keyboardman\FilesystemBundle\Service\FileStorage;
use Keyboardman\FilesystemBundle\Service\UploadValidator;
use League\Flysystem\Filesystem;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class KeyboardmanFilesystemExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $filesystemMapDef = new Definition(FilesystemMap::class);

        foreach ($config['filesystems'] as $name => $fsConfig) {
            $fsDef = new Definition(Filesystem::class, [
                new Reference($fsConfig['adapter']),
            ]);
            $fsDef->setPublic(false);
            $fsId = 'keyboardman_filesystem.filesystem.' . $name;
            $container->setDefinition($fsId, $fsDef);
            $filesystemMapDef->addMethodCall('set', [$name, new Reference($fsId)]);
        }

        $container->setDefinition('keyboardman_filesystem.filesystem_map', $filesystemMapDef);

        $storageDef = new Definition(FileStorage::class, [
            new Reference('keyboardman_filesystem.filesystem_map'),
        ]);
        $storageDef->setPublic(true);
        $container->setDefinition(FileStorage::class, $storageDef);

        $apiConfig = $config['api'] ?? ['allowed_types' => ['image', 'audio', 'video'], 'max_upload_size' => 10_485_760];
        $validatorDef = new Definition(UploadValidator::class, [
            $apiConfig['allowed_types'],
            $apiConfig['max_upload_size'],
        ]);
        $validatorDef->setPublic(false);
        $container->setDefinition(UploadValidator::class, $validatorDef);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}
