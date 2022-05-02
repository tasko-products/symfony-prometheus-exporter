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
     * @param string|null $path Specification of the config path, separated with dots. E.g.
     * `prometheus_metrics.event_subscribers.active_workers.namespace`. To get the full bundle
     * config, just call `get()` without arguments.
     *
     * @return null If nothing is found under the specified configuration path, then the function
     * returns null.
     *
     * @return array|bool|string|int|float|\UnitEnum Otherwise, the value found under the specified
     * path is returned.
     */
    public function get(?string $path = null): array|bool|string|int|float|\UnitEnum|null;

    /**
     * Gets a type-safe boolean service configuration by its path.
     *
     * @param string $path Specification of the config path, separated with dots. E.g.
     * `prometheus_metrics.event_subscribers.active_workers.enabled`.
     *
     * @return null If nothing is found under the specified configuration path or the found value is
     * of the wrong type, then the function returns null.
     *
     * @return bool Otherwise, the boolean value found under the specified path is returned.
     */
    public function maybeGetBool(string $path): ?bool;

    /**
     * Gets a type-safe string service configuration by its path.
     *
     * @param string $path Specification of the config path, separated with dots. E.g.
     * `prometheus_metrics.event_subscribers.active_workers.metric_name`.
     *
     * @return null If nothing is found under the specified configuration path or the found value is
     * of the wrong type, then the function returns null.
     *
     * @return string Otherwise, the string value found under the specified path is returned.
     */
    public function maybeGetString(string $path): ?string;

    /**
     * Gets a type-safe array service configuration by its path.
     *
     * @param string $path Specification of the config path, separated with dots. E.g.
     * `prometheus_metrics.event_subscribers.active_workers.labels`.
     *
     * @return null If nothing is found under the specified configuration path or the found value is
     * of the wrong type, then the function returns null.
     *
     * @return array Otherwise, the array value found under the specified path is returned.
     */
    public function maybeGetArray(string $path): ?array;
}
