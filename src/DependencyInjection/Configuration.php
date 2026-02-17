<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('keyboardman_filesystem', 'array');
        /** @var ArrayNodeDefinition $root */
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('allowed_types')
                            ->info('Media types allowed for upload: image, audio, video. Extensions align with FileStorage TYPE_EXTENSIONS.')
                            ->defaultValue(['image', 'audio', 'video'])
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('max_upload_size')
                            ->info('Max file size per file in bytes, or human-readable (e.g. 10M, 50M). Default 10 MiB.')
                            ->defaultValue(10_485_760)
                            ->beforeNormalization()
                                ->ifString()
                                ->then(static function (string $v): int {
                                    $v = trim($v);
                                    $suffix = strtoupper(substr($v, -1));
                                    $base = (int) rtrim($v, 'kKmMgG');
                                    return match ($suffix) {
                                        'K' => $base * 1024,
                                        'M' => $base * 1_048_576,
                                        'G' => $base * 1_073_741_824,
                                        default => (int) $v,
                                    };
                                })
                            ->end()
                            ->validate()
                                ->ifTrue(static fn ($v) => $v < 1)
                                ->thenInvalid('max_upload_size must be >= 1')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('filesystems')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('adapter')->isRequired()->info('Service id of the Flysystem adapter (League\\Flysystem\\FilesystemAdapter)')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
