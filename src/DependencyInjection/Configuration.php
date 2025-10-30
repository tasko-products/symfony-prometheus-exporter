<?php

/**
 * @link         http://www.tasko-products.de/ tasko Products GmbH
 * @copyright    (c) tasko Products GmbH
 * @license      http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * This file is part of tasko-products/symfony-prometheus-exporter.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     *
     * @throws \Exception the configuration fails when the config root node is not an array node.
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('tasko_products_symfony_prometheus_exporter');

        /**
         * just a phpstan thing – error because of getRootNode's NodeDefinition response type:
         * "Call to an undefined method Symfony\Component\Config\Definition\Builder\NodeDefinition::children()"
         */
        if (!$treeBuilder->getRootNode() instanceof ArrayNodeDefinition) {
            throw new \Exception('The root node must be of type ' . ArrayNodeDefinition::class);
        }

        $treeBuilder->getRootNode()->children()
                            ->arrayNode('event_subscribers')
                                ->children()
                                    ->arrayNode('active_workers')
                                        ->children()
                                            ->booleanNode('enabled')->end()
                                            ->scalarNode('namespace')->end()
                                            ->scalarNode('metric_name')->end()
                                            ->scalarNode('help_text')->end()
                                            ->arrayNode('labels')
                                                ->children()
                                                    ->scalarNode('queue_names')->end()
                                                    ->scalarNode('transport_names')->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('messages_in_process')
                                        ->children()
                                            ->booleanNode('enabled')->end()
                                            ->scalarNode('namespace')->end()
                                            ->scalarNode('metric_name')->end()
                                            ->scalarNode('help_text')->end()
                                            ->arrayNode('labels')
                                                ->children()
                                                    ->scalarNode('message_path')->end()
                                                    ->scalarNode('message_class')->end()
                                                    ->scalarNode('receiver')->end()
                                                    ->scalarNode('bus')->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('messages_in_transport')
                                        ->children()
                                            ->booleanNode('enabled')->end()
                                            ->scalarNode('namespace')->end()
                                            ->scalarNode('metric_name')->end()
                                            ->scalarNode('help_text')->end()
                                            ->arrayNode('labels')
                                                ->children()
                                                    ->scalarNode('message_path')->end()
                                                    ->scalarNode('message_class')->end()
                                                    ->scalarNode('receiver')->end()
                                                    ->scalarNode('bus')->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('middlewares')
                                ->children()
                                    ->arrayNode('event_middleware')
                                        ->children()
                                            ->scalarNode('namespace')->end()
                                            ->scalarNode('metric_name')->end()
                                            ->scalarNode('help_text')->end()
                                            ->arrayNode('labels')
                                                ->children()
                                                    ->scalarNode('bus')->end()
                                                    ->scalarNode('message')->end()
                                                    ->scalarNode('label')->end()
                                                ->end()
                                            ->end()
                                            ->scalarNode('error_help_text')->end()
                                            ->arrayNode('error_labels')
                                                ->children()
                                                    ->scalarNode('bus')->end()
                                                    ->scalarNode('message')->end()
                                                    ->scalarNode('label')->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('retry_event_middleware')
                                        ->children()
                                            ->scalarNode('namespace')->end()
                                            ->scalarNode('metric_name')->end()
                                            ->scalarNode('help_text')->end()
                                            ->arrayNode('labels')
                                                ->children()
                                                    ->scalarNode('bus')->end()
                                                    ->scalarNode('message')->end()
                                                    ->scalarNode('label')->end()
                                                    ->scalarNode('retry')->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
        ;

        return $treeBuilder;
    }
}
