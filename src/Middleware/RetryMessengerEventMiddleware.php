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

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Middleware;

use Prometheus\RegistryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProviderInterface;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\EnvelopeMethodesTrait;

class RetryMessengerEventMiddleware implements MiddlewareInterface
{
    use EnvelopeMethodesTrait;

    private RegistryInterface $registry;
    private string $namespace = '';
    private string $metricName = '';
    private string $helpText = '';
    /**
     * @var string[]
     */
    private array $labels = [];

    public function __construct(
        RegistryInterface $registry,
        ConfigurationProviderInterface $config,
    ) {
        $this->registry = $registry;

        $configPrefix = 'middlewares.retry_event_middleware.';

        $this->namespace = $config->maybeGetString($configPrefix . 'namespace')
            ?? 'middleware';
        $this->metricName = $config->maybeGetString($configPrefix . 'metric_name')
            ?? 'retry_message';
        $this->helpText = $config->maybeGetString($configPrefix . 'help_text')
            ?? 'Retried Messages';
        $this->labels = $config->maybeGetArray($configPrefix . 'labels') ?? [
            'bus' => 'bus',
            'message' => 'message',
            'label' => 'label',
            'retry' => 'retry',
        ];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $counter = $this->registry->getOrRegisterCounter(
            $this->namespace,
            $this->metricName,
            $this->helpText,
            $this->retryEventMiddlewareLabels(),
        );

        $redeliveryStamp = $this->lastRedeliveryStamp($envelope);

        $messageLabels = [
            $this->extractBusName($envelope),
            $this->messageClassPathLabel($envelope),
            $this->messageClassLabel($envelope),
            $this->messageRetryLabel($redeliveryStamp),
        ];

        $counter->incBy($redeliveryStamp ? 1 : 0, $messageLabels);

        return $stack->next()->handle($envelope, $stack);
    }

    /**
     * @return string[]
     */
    private function retryEventMiddlewareLabels(): array
    {
        return [
            $this->labels['bus'],
            $this->labels['message'],
            $this->labels['label'],
            $this->labels['retry'],
        ];
    }

    private function lastRedeliveryStamp(Envelope $envelope): ?RedeliveryStamp
    {
        $stamp = $envelope->last(RedeliveryStamp::class);

        if (!$stamp instanceof RedeliveryStamp) {
            return null;
        }

        return $stamp;
    }

    private function messageRetryLabel(?RedeliveryStamp $stamp): string
    {
        return (string) ($stamp?->getRetryCount() ?: 0);
    }
}
