<?php

namespace Intaro\CustomIndexBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('intaro_custom_index');

        $rootNode
            ->children()
                // if true update indexes in all db schemas
                // else update only in current schema
                ->booleanNode('search_in_all_schemas')
                    ->defaultTrue()
                ->end()
            ->end();

        return $treeBuilder;
    }
}