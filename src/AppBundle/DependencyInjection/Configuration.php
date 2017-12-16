<?php

namespace AppBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @inheritdoc
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder()
    {
        // Initialize
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('afsy_chat');

        // Add AFSY Chat configuration
        $rootNode
            ->children()
                ->arrayNode('websocket')
                    ->children()
                        ->scalarNode('host')->end()
                        ->integerNode('port')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
