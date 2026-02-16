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
                ->arrayNode('filesystems')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('adapter')->isRequired()->info('Service id of the Gaufrette adapter')->end()
                            ->arrayNode('cache')
                                ->canBeEnabled()
                                ->children()
                                    ->scalarNode('source')->isRequired()->info('Service id of the source adapter to cache')->end()
                                    ->scalarNode('cache')->isRequired()->info('Service id of the cache adapter')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
