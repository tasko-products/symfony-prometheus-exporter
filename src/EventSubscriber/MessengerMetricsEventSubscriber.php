<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber;

use Prometheus\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;

class MessengerMetricsEventSubscriber implements EventSubscriberInterface
{
    /**
     * @param string[] $labels
     */
    public function __construct(
        private RegistryInterface $registry,
        private string $activeWorkersMetricName = 'active_workers',
        private string $helpText = 'Active Workers',
        private array  $labels = ['queue_names', 'transport_names'],
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerStartedEvent::class => 'onWorkerStarted'
        ];
    }

    public function onWorkerStarted(WorkerStartedEvent $event): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            'message_bus',
            $this->activeWorkersMetricName,
            $this->helpText,
            $this->labels
        );

        $data = $event->getWorker()->getMetadata();

        $gauge->inc([
            \implode(', ', $data->getQueueNames() ?: []),
            \implode(', ', $data->getTransportNames() ?: []),
        ]);
    }
}
