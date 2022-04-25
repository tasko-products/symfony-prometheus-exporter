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
use Symfony\Component\Messenger\Event\AbstractWorkerMessageEvent;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProviderInterface;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\ConfigurationAwareTrait;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\EnvelopeMethodesTrait;

class MessagesInProcessMetricEventSubscriber implements EventSubscriberInterface
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
        $this->configurationPrefix = 'event_subscribers.messages_in_process';

        $this->enabled = $this->maybeBoolConfig('enabled') ?? false;
        $this->namespace = $this->maybeStrConfig('namespace') ?? 'messenger_events';
        $this->metricName = $this->maybeStrConfig('metric_name') ?? 'messages_in_process';
        $this->helpText = $this->maybeStrConfig('help_text') ?? 'Messages In Process';
        $this->labels = $this->maybeArrayConfig('labels') ?? [
            'message_path' => 'message_path',
            'message_class' => 'message_class',
            'receiver' => 'receiver',
            'bus' => 'bus',
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageReceivedEvent::class => 'onWorkerMessageReceived',
            WorkerMessageHandledEvent::class => 'onWorkerMessageHandled',
            WorkerMessageFailedEvent::class => 'onWorkerMessageFailed',
        ];
    }

    public function onWorkerMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        if ($this->isRedelivered($event->getEnvelope())) {
            return;
        }

        $this->messagesInProcessGauge()->inc($this->messagesInProcessLabelValues($event));
    }

    public function onWorkerMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $this->decMetric($event);
    }

    public function onWorkerMessageFailed(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        $this->decMetric($event);
    }

    private function decMetric(WorkerMessageHandledEvent|WorkerMessageFailedEvent $event): void
    {
        $this->messagesInProcessGauge()->dec($this->messagesInProcessLabelValues($event));
    }

    private function messagesInProcessGauge(): Gauge
    {
        return $this->registry->getOrRegisterGauge(
            $this->namespace,
            $this->metricName,
            $this->helpText,
            $this->messagesInProcessLabels(),
        );
    }

    /**
     * @return string[]
     */
    private function messagesInProcessLabels(): array
    {
        return [
            $this->labels['message_path'],
            $this->labels['message_class'],
            $this->labels['receiver'],
            $this->labels['bus'],
        ];
    }

    /**
     * @return string[]
     */
    private function messagesInProcessLabelValues(AbstractWorkerMessageEvent $event): array
    {
        $envelope = $event->getEnvelope();

        return [
            $this->messageClassPathLabel($envelope),
            $this->messageClassLabel($envelope),
            $event->getReceiverName(),
            $this->extractBusName($envelope),
        ];
    }
}
