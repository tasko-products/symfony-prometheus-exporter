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

use Prometheus\Counter;
use Prometheus\RegistryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

class MessengerEventMiddleware implements MiddlewareInterface
{
    public function __construct(
        public RegistryInterface $registry,
        public string $metricName = 'message',
        public string $helpText = 'Executed Messages',
        public array $labels = ['message', 'label'],
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $counter = $this->getCounter(
            $this->extractBusName($envelope),
            $this->metricName,
            $this->helpText,
            $this->labels
        );

        $counter->inc([
            \get_class($envelope->getMessage()),
            substr((string)strrchr(get_class($envelope->getMessage()), '\\'), 1)
        ]);

        $envelope = $stack->next()->handle($envelope, $stack);

        return $envelope;
    }

    private function getCounter(string $busName, string $name, string $helperText, array $labels = null): Counter
    {
        return $this->registry->getOrRegisterCounter(
            $busName,
            $name,
            $helperText,
            $labels
        );
    }

    private function extractBusName(Envelope $envelope): string
    {
        $busName = 'default_messenger';
        $stamp = $envelope->last(BusNameStamp::class);

        if ($stamp instanceof BusNameStamp === true) {
            $busName = str_replace('.', '_', $stamp->getBusName());
        }

        return $busName;
    }
}
