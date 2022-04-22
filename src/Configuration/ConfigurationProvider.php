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

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigurationProvider implements ConfigurationProviderInterface
{
    public function __construct(
        private string $configRoot = 'prometheus_metrics',
        private ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function config(?string $path = null): array|bool|string|int|float|\UnitEnum|null
    {
        return $this->parameterBag->all();
    }
}
