<?php

namespace Sli\DoctrineEntityDataMapperBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sli_doctrine_entity_data_mapper');

        $rootNode
            ->children()
                ->arrayNode('preferences_provider_formats')
                    ->children()
                        ->scalarNode('date')->defaultValue('d.m.y')->end()
                        ->scalarNode('datetime')->defaultValue('d.m.y H:i')->end()
                        ->scalarNode('month')->defaultValue('m.Y')->end()
                    ->end()
                ->end()
                ->scalarNode('preferences_provider')
                    ->defaultValue('sli_doctrine_entity_data_mapper.preferences.semantic_provider')
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
