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

use Prometheus\Counter;
use Prometheus\RegistryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class MessengerEventMiddleware implements MiddlewareInterface
{
    use EnvelopeMethodesTrait;

    /**
     * @param string[] $labels
     * @param string[] $errorLabels
     */
    public function __construct(
        public RegistryInterface $registry,
        public string $metricName = 'message',
        public string $helpText = 'Executed Messages',
        public array  $labels = ['message', 'label'],
        public string $errorHelpText = 'Failed Messages',
        public array  $errorLabels = ['message', 'label'],
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $busName = $this->extractBusName($envelope);

        $counter = $this->getCounter(
            $busName,
            $this->metricName,
            $this->helpText,
            $this->labels
        );

        $errCounter = $this->getErrorCounter(
            $busName,
            $this->metricName,
            $this->errorHelpText,
            $this->errorLabels
        );

        $messageLabels = [
            $this->messageClassPathLabel($envelope),
            $this->messageClassLabel($envelope),
        ];

        try {
            $counter->inc($messageLabels);
            $errCounter->incBy(0, $messageLabels);

            $envelope = $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $exception) {
            $errCounter->inc($messageLabels);

            throw $exception;
        }

        return $envelope;
    }

    /**
     * @param string[] $labels
     */
    private function getCounter(
        string $busName,
        string $name,
        string $helperText,
        array $labels
    ): Counter {
        return $this->registry->getOrRegisterCounter(
            $busName,
            $name,
            $helperText,
            $labels
        );
    }

    /**
     * @param string[] $labels
     */
    private function getErrorCounter(
        string $busName,
        string $name,
        string $helperText,
        array $labels
    ): Counter {
        return $this->getCounter(
            $busName,
            $name . '_error',
            $helperText,
            $labels
        );
    }
}
