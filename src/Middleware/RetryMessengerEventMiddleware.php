<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Middleware;

use Prometheus\RegistryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProviderInterface;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\ConfigurationAwareTrait;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\EnvelopeMethodesTrait;

class RetryMessengerEventMiddleware implements MiddlewareInterface
{
    use EnvelopeMethodesTrait;
    use ConfigurationAwareTrait;

    private RegistryInterface $registry;
    private string $metricName = '';
    private string $helpText = '';
    /**
     * @var string[]
     */
    private array $labels = [];

    public function __construct(
        RegistryInterface $registry,
        ConfigurationProviderInterface $configurationProvider,
    ) {
        $this->registry = $registry;
        $this->configurationProvider = $configurationProvider;
        $this->configurationPrefix = 'middlewares.retry_event_middleware';

        $this->metricName = $this->maybeStrConfig('metric_name') ?? 'retry_message';
        $this->helpText = $this->maybeStrConfig('help_text') ?? 'Retried Messages';
        $this->labels = $this->maybeArrayConfig('labels') ?? [
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
            $this->extractBusName($envelope),
            $this->metricName,
            $this->helpText,
            $this->retryEventMiddlewareLabels(),
        );

        $redeliveryStamp = $this->lastRedeliveryStamp($envelope);

        $messageLabels = [
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
