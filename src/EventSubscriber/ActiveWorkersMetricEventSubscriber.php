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
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProviderInterface;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\ConfigurationAwareTrait;

class ActiveWorkersMetricEventSubscriber implements EventSubscriberInterface
{
    use ConfigurationAwareTrait;

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
        ConfigurationProviderInterface $configurationProvider,
    ) {
        $this->registry = $registry;
        $this->configurationProvider = $configurationProvider;
        $this->configurationPrefix = 'event_subscribers.active_workers';

        $this->messengerNamespace = $this->maybeStrConfig('namespace') ?? 'messenger_events';
        $this->activeWorkersMetricName = $this->maybeStrConfig('metric_name') ?? 'active_workers';
        $this->helpText = $this->maybeStrConfig('help_text') ?? 'Active Workers';
        $this->labels = $this->maybeArrayConfig('labels') ?? [
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
