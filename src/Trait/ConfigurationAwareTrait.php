<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Trait;

use Stringable;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProviderInterface;

trait ConfigurationAwareTrait
{
    private ConfigurationProviderInterface $configurationProvider;

    private string $configurationPrefix = '';

    private function config(?string $path): array|bool|string|int|float|\UnitEnum|null
    {
        $configQuery = $path;

        if ($this->configurationPrefix !== '') {
            $configQuery = $this->configurationPrefix . '.' . $path;
        }

        return $this->configurationProvider->get($configQuery);
    }

    private function maybeStrConfig(?string $path): ?string
    {
        $config = $this->config($path);

        if (!is_string($config) && !$config instanceof Stringable) {
            return null;
        }

        return (string)$config;
    }

    private function maybeArrayConfig(?string $path): ?array
    {
        $config = $this->config($path);

        if (!is_array($config)) {
            return null;
        }

        return $config;
    }

    private function maybeBoolConfig(?string $path): ?bool
    {
        $config = $this->config($path);

        if (!is_bool($config)) {
            return null;
        }

        return $config;
    }
}
