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
        return \substr(
            (string)\strrchr($this->messageClassPathLabel($envelope), '\\'),
            offset: 1,
        );
    }

    private function isRedelivered(Envelope $envelope): bool
    {
        return $envelope->last(RedeliveryStamp::class) !== null;
    }
}
