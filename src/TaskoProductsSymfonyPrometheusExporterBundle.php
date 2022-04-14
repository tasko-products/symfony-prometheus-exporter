<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TaskoProductsSymfonyPrometheusExporterBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
