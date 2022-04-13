<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2020
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

class RetryMessengerEventMiddleware implements MiddlewareInterface
{
    use EnvelopeMethodesTrait;

    /**
     * @param string[] $labels
     */
    public function __construct(
        public RegistryInterface $registry,
        public string $metricName = 'retry_message',
        public string $helpText = 'Retried Messages',
        public array  $labels = ['message', 'label'],
    ) {
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
            $this->labels
        );

        $messageLabels = [
            $this->messageClassPathLabel($envelope),
            $this->messageClassLabel($envelope),
        ];

        if ($envelope->last(RedeliveryStamp::class) === null) {
            $counter->incBy(0, $messageLabels);

            return $stack->next()->handle($envelope, $stack);
        }

        $counter->inc($messageLabels);

        return $stack->next()->handle($envelope, $stack);
    }
}
