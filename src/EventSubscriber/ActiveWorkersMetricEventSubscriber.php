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

use Prometheus\Gauge;
use Prometheus\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\WorkerMetadata;

class ActiveWorkersMetricEventSubscriber implements EventSubscriberInterface
{
    /**
     * @param string[] $labels
     */
    public function __construct(
        private RegistryInterface $registry,
        private string $messengerNamespace = 'messenger_events',
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
            WorkerStartedEvent::class => 'onWorkerStarted',
            WorkerStoppedEvent::class => 'onWorkerStopped'
        ];
    }

    public function onWorkerStarted(WorkerStartedEvent $event): void
    {
        $data = $event->getWorker()->getMetadata();

        $this->activeWorkersGauge()->inc(
            $this->activeWorkersLabels($data),
        );
    }

    public function onWorkerStopped(WorkerStoppedEvent $event): void
    {
        $data = $event->getWorker()->getMetadata();

        $this->activeWorkersGauge()->dec(
            $this->activeWorkersLabels($data),
        );
    }

    private function activeWorkersGauge(): Gauge
    {
        return $this->registry->getOrRegisterGauge(
            $this->messengerNamespace,
            $this->activeWorkersMetricName,
            $this->helpText,
            $this->labels
        );
    }

    /**
     * @return string[]
     */
    private function activeWorkersLabels(WorkerMetadata $data): array
    {
        return [
            \implode(', ', $data->getQueueNames() ?: []),
            \implode(', ', $data->getTransportNames() ?: []),
        ];
    }
}
