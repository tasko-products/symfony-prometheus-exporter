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

namespace TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber;

use Prometheus\Gauge;
use Prometheus\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\WorkerMetadata;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProviderInterface;

final class ActiveWorkersMetricEventSubscriber implements
    EventSubscriberInterface
{
    private bool $enabled = false;
    private string $namespace = '';
    private string $metricName = '';
    private string $helpText = '';

    /** @var array{queue_names: string, transport_names: string} */
    private array $labels;

    public function __construct(
        private readonly RegistryInterface $registry,
        ConfigurationProviderInterface $config,
    ) {
        $configPrefix = 'event_subscribers.active_workers';

        $this->enabled = $config->maybeGetBool("{$configPrefix}.enabled") ?? false;
        $this->namespace = $config->maybeGetString("{$configPrefix}.namespace")
            ?? 'messenger_events';
        $this->metricName = $config->maybeGetString("{$configPrefix}.metric_name")
            ?? 'active_workers';
        $this->helpText = $config->maybeGetString("{$configPrefix}.help_text") ?? 'Active Workers';

        /** @var array{queue_names?: string, transport_names?: string} */
        $labels = $config->maybeGetArray("{$configPrefix}.labels") ?? [];
        $labels['queue_names'] ??= 'queue_names';
        $labels['transport_names'] ??= 'transport_names';

        $this->labels = $labels;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerStartedEvent::class => 'onWorkerStarted',
            WorkerStoppedEvent::class => 'onWorkerStopped',
        ];
    }

    public function onWorkerStarted(WorkerStartedEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $data = $event->getWorker()->getMetadata();

        $this->activeWorkersGauge()->inc(
            $this->activeWorkersLabelValues($data),
        );
    }

    public function onWorkerStopped(WorkerStoppedEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $data = $event->getWorker()->getMetadata();

        $this->activeWorkersGauge()->dec(
            $this->activeWorkersLabelValues($data),
        );
    }

    private function activeWorkersGauge(): Gauge
    {
        return $this->registry->getOrRegisterGauge(
            $this->namespace,
            $this->metricName,
            $this->helpText,
            $this->activeWorkersLabels(),
        );
    }

    /**
     * @return list<string>
     */
    private function activeWorkersLabels(): array
    {
        return [
            $this->labels['queue_names'],
            $this->labels['transport_names'],
        ];
    }

    /**
     * @return list<string>
     */
    private function activeWorkersLabelValues(WorkerMetadata $data): array
    {
        return [
            \implode(', ', $data->getQueueNames() ?: []),
            \implode(', ', $data->getTransportNames() ?: []),
        ];
    }
}
