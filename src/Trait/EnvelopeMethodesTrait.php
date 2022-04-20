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

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

trait EnvelopeMethodesTrait
{
    private function extractBusName(Envelope $envelope): string
    {
        $busName = 'default_messenger';
        $stamp = $envelope->last(BusNameStamp::class);

        if ($stamp instanceof BusNameStamp === true) {
            $busName = \str_replace('.', '_', $stamp->getBusName());
        }

        return $busName;
    }

    private function messageClassPathLabel(Envelope $envelope): string
    {
        return \get_class($envelope->getMessage());
    }

    private function messageClassLabel(Envelope $envelope): string
    {
        return \substr((string)\strrchr($this->messageClassPathLabel($envelope), '\\'), 1);
    }

    private function isRedelivered(Envelope $envelope): bool
    {
        return $envelope->last(RedeliveryStamp::class) !== null;
    }
}
