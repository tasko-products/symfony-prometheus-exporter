<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2020
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory;

use Prometheus\CollectorRegistry;
use Prometheus\RegistryInterface;
use Prometheus\Storage\InMemory;

class PrometheusCollectorRegistryFactory
{
    public static function create(): RegistryInterface
    {
        return new CollectorRegistry(new InMemory());
    }
}
