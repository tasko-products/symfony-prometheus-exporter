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
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProviderInterface;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\ConfigurationAwareTrait;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\EnvelopeMethodesTrait;

class MessagesInTransportMetricEventSubscriber implements EventSubscriberInterface
{
    use EnvelopeMethodesTrait;
    use ConfigurationAwareTrait;

    private RegistryInterface $registry;
    private bool $enabled = false;
    private string $namespace = '';
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
        $this->configurationPrefix = 'event_subscribers.messages_in_transport';

        $this->enabled = $this->maybeBoolConfig('enabled') ?? false;
        $this->namespace = $this->maybeStrConfig('namespace') ?? 'messenger_events';
        $this->metricName = $this->maybeStrConfig('metric_name') ?? 'messages_in_transport';
        $this->helpText = $this->maybeStrConfig('help_text') ?? 'Messages In Transport';
        $this->labels = $this->maybeArrayConfig('labels') ?? [
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
        $this->messagesInTransportGauge()->inc($this->messagesInTransportLabelValues($event));
    }

    public function onWorkerMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        if ($this->isRedelivered($event->getEnvelope())) {
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
