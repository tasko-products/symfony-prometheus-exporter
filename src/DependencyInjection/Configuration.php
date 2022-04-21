<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
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
        $treeBuilder = new TreeBuilder('prometheus_metrics');
        $rootNode = $treeBuilder->getRootNode();

        if (!$rootNode instanceof ArrayNodeDefinition) {
            throw new \Exception('The root node must be of type ' . ArrayNodeDefinition::class);
        }

        return $rootNode->children()
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
                                            ->scalarNode('metric_name')->end()
                                            ->scalarNode('help_text')->end()
                                            ->arrayNode('labels')
                                                ->children()
                                                    ->scalarNode('message')->end()
                                                    ->scalarNode('label')->end()
                                                ->end()
                                            ->end()
                                            ->scalarNode('error_help_text')->end()
                                            ->arrayNode('error_labels')
                                                ->children()
                                                    ->scalarNode('message')->end()
                                                    ->scalarNode('label')->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('retry_event_middleware')
                                        ->children()
                                            ->scalarNode('metric_name')->end()
                                            ->scalarNode('help_text')->end()
                                            ->arrayNode('labels')
                                                ->children()
                                                    ->scalarNode('message')->end()
                                                    ->scalarNode('label')->end()
                                                    ->scalarNode('retry')->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end();
    }
}
