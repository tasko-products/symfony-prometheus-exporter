<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Configuration;

interface ConfigurationProviderInterface
{
    /**
     * Gets a service configuration by its path.
     *
     * @param string $path Specification of the config path, separated with dots. E.g.
     * `prometheus_metrics.event_subscribers.active_workers.namespace`. To get the full bundle
     * config, just call `config()` without arguments.
     *
     * @return null If nothing is found under the specified configuration path, then the function
     * returns null.
     *
     * @return array|bool|string|int|float|\UnitEnum Otherwise, the value found under the specified path is returned.
     */
    public function config(?string $path = null): array|bool|string|int|float|\UnitEnum|null;
}
