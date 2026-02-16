<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\DependencyInjection;

use Gaufrette\Filesystem;
use Gaufrette\FilesystemMap;
use Keyboardman\FilesystemBundle\Service\FileStorage;
use Keyboardman\FilesystemBundle\Service\UploadValidator;
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
            $adapterRef = ($fsConfig['cache']['enabled'] ?? false)
                ? $this->createCachedAdapter($container, $name, $fsConfig['cache']['source'], $fsConfig['cache']['cache'])
                : new Reference($fsConfig['adapter']);

            $fsDef = new Definition(Filesystem::class, [$adapterRef]);
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

    private function createCachedAdapter(ContainerBuilder $container, string $name, string $sourceId, string $cacheId): Reference
    {
        $cacheAdapterClass = 'Gaufrette\Adapter\Cache';
        if (!class_exists($cacheAdapterClass)) {
            throw new \LogicException('Gaufrette cache adapter not available. Ensure knplabs/gaufrette provides the Cache adapter.');
        }
        $def = new Definition($cacheAdapterClass, [
            new Reference($cacheId),
            new Reference($sourceId),
        ]);
        $def->setPublic(false);
        $id = 'keyboardman_filesystem.cached_adapter.' . $name;
        $container->setDefinition($id, $def);
        return new Reference($id);
    }
}
