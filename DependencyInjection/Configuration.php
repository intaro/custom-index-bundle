<?php

namespace Intaro\CustomIndexBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface
{
    protected static $availableIndexTypes = ['btree', 'hash', 'gin', 'gist'];

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('intaro_custom_index');
        $rootNode = \method_exists($treeBuilder, 'getRootNode')
            ? $treeBuilder->getRootNode() : $treeBuilder->root('intaro_custom_index');

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
                            ->ifNotInArray(self::$availableIndexTypes)
                            ->thenInvalid("Unknown index type. Allowed types: ".implode(', ', self::$availableIndexTypes).".")
                        ->end()
                    ->end()
                    ->cannotBeEmpty()
                    ->defaultValue(self::$availableIndexTypes)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
