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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\WorkerMetadata;

class ActiveWorkersMetricEventSubscriber implements EventSubscriberInterface
{
    private RegistryInterface $registry;
    private string $messengerNamespace = '';
    private string $activeWorkersMetricName = '';
    private string $helpText = '';
    /**
     * @var string[]
     */
    private array $labels = [];

    public function __construct(
        RegistryInterface $registry,
        ParameterBagInterface $parameterBag,
    ) {
        $this->registry = $registry;

        $pbKey = 'prometheus_metrics.event_subscribers';

        /**
         * @var array $subscriberConfig
         */
        $subscriberConfig = $parameterBag->has($pbKey)
            ? $parameterBag->get($pbKey)['active_workers']
            : [];

        $this->messengerNamespace = $subscriberConfig['namespace'] ?? 'messenger_events';
        $this->activeWorkersMetricName = $subscriberConfig['metric_name'] ?? 'active_workers';
        $this->helpText = $subscriberConfig['help_text'] ?? 'Active Workers';
        $this->labels = $subscriberConfig['labels'] ?? [
            'queue_names' => 'queue_names',
            'transport_names' => 'transport_names',
        ];
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
            $this->activeWorkersLabelValues($data),
        );
    }

    public function onWorkerStopped(WorkerStoppedEvent $event): void
    {
        $data = $event->getWorker()->getMetadata();

        $this->activeWorkersGauge()->dec(
            $this->activeWorkersLabelValues($data),
        );
    }

    private function activeWorkersGauge(): Gauge
    {
        return $this->registry->getOrRegisterGauge(
            $this->messengerNamespace,
            $this->activeWorkersMetricName,
            $this->helpText,
            $this->activeWorkersLabels(),
        );
    }

    /**
     * @return string[]
     */
    private function activeWorkersLabels(): array
    {
        return [
            $this->labels['queue_names'],
            $this->labels['transport_names'],
        ];
    }

    /**
     * @return string[]
     */
    private function activeWorkersLabelValues(WorkerMetadata $data): array
    {
        return [
            \implode(', ', $data->getQueueNames() ?: []),
            \implode(', ', $data->getTransportNames() ?: []),
        ];
    }
}
