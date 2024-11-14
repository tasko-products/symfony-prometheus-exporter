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
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProviderInterface;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\EnvelopeMethodesTrait;

class MessagesInTransportMetricEventSubscriber implements EventSubscriberInterface
{
    use EnvelopeMethodesTrait;

    private RegistryInterface $registry;
    private bool $enabled = false;
    private string $namespace = '';
    private string $metricName = '';
    private string $helpText = '';
    /** @var string[] */
    private array $labels = [];

    public function __construct(
        RegistryInterface $registry,
        ConfigurationProviderInterface $config,
    ) {
        $this->registry = $registry;

        $configPrefix = 'event_subscribers.messages_in_transport.';

        $this->enabled = $config->maybeGetBool($configPrefix . 'enabled') ?? false;
        $this->namespace = $config->maybeGetString($configPrefix . 'namespace')
            ?? 'messenger_events';
        $this->metricName = $config->maybeGetString($configPrefix . 'metric_name')
            ?? 'messages_in_transport';
        $this->helpText = $config->maybeGetString($configPrefix . 'help_text')
            ?? 'Messages In Transport';
        $this->labels = $config->maybeGetArray($configPrefix . 'labels') ?? [
            'message_path' => 'message_path',
            'message_class' => 'message_class',
            'bus' => 'bus',
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SendMessageToTransportsEvent::class => 'onSendMessageToTransports',
            WorkerMessageReceivedEvent::class => 'onWorkerMessageReceived',
        ];
    }

    public function onSendMessageToTransports(SendMessageToTransportsEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->messagesInTransportGauge()->inc($this->messagesInTransportLabelValues($event));
    }

    public function onWorkerMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        if (!$this->enabled || $this->isRedelivered($event->getEnvelope())) {
            return;
        }

        $this->messagesInTransportGauge()->dec($this->messagesInTransportLabelValues($event));
    }

    private function messagesInTransportGauge(): Gauge
    {
        return $this->registry->getOrRegisterGauge(
            $this->namespace,
            $this->metricName,
            $this->helpText,
            $this->messagesInTransportLabels(),
        );
    }

    /**
     * @return string[]
     */
    private function messagesInTransportLabels(): array
    {
        return [
            $this->labels['message_path'],
            $this->labels['message_class'],
            $this->labels['bus'],
        ];
    }

    /**
     * @return string[]
     */
    private function messagesInTransportLabelValues(SendMessageToTransportsEvent|WorkerMessageReceivedEvent $event): array
    {
        $envelope = $event->getEnvelope();

        return [
            $this->messageClassPathLabel($envelope),
            $this->messageClassLabel($envelope),
            $this->extractBusName($envelope),
        ];
    }
}
