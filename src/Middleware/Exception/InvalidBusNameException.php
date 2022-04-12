<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2020
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Middleware\Exception;

use Prometheus\Collector;

class InvalidBusNameException extends \InvalidArgumentException
{
    public static function with(string $busName): self
    {
        return new self(\sprintf(
            'Invalid character in your bus name %s: validation failed against: %s',
            $busName,
            Collector::RE_METRIC_LABEL_NAME
        ));
    }
}
