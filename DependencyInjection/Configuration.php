<?php

namespace Intaro\CustomIndexBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private const AVAILABLE_INDEX_TYPES = ['btree', 'hash', 'gin', 'gist'];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('intaro_custom_index');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                // if true update indexes in all db schemas
                // else update only in current schema
                ->booleanNode('search_in_all_schemas')
                    ->defaultTrue()
                ->end()
                ->arrayNode('allowed_index_types')
                    ->prototype('scalar')
                        ->validate()
                            ->ifNotInArray(self::AVAILABLE_INDEX_TYPES)
                            ->thenInvalid('Unknown index type. Allowed types: ' . implode(', ', self::AVAILABLE_INDEX_TYPES) . '.')
                        ->end()
                    ->end()
                    ->cannotBeEmpty()
                    ->defaultValue(self::AVAILABLE_INDEX_TYPES)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
