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
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\EnvelopeMethodesTrait;

class MessagesInProcessMetricEventSubscriber implements EventSubscriberInterface
{
    use EnvelopeMethodesTrait;

    /**
     * @param string[] $labels
     */
    public function __construct(
        private RegistryInterface $registry,
        private string $messengerNamespace = 'messenger_events',
        private string $messagesInProcessMetricName = 'messages_in_process',
        private string $helpText = 'Messages In Process',
        private array  $labels = ['message_path', 'message_class', 'receiver', 'bus'],
    ) {
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
        $this->messagesInProcessGauge()->inc($this->messagesInProcessLabels($event));
    }

    public function onWorkerMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $this->decMetric($event);
    }

    public function onWorkerMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $this->decMetric($event);
    }

    private function decMetric(WorkerMessageHandledEvent|WorkerMessageFailedEvent $event): void
    {
        $this->messagesInProcessGauge()->dec($this->messagesInProcessLabels($event));
    }

    private function messagesInProcessGauge(): Gauge
    {
        return $this->registry->getOrRegisterGauge(
            $this->messengerNamespace,
            $this->messagesInProcessMetricName,
            $this->helpText,
            $this->labels,
        );
    }

    /**
     * @return string[]
     */
    private function messagesInProcessLabels(AbstractWorkerMessageEvent $event): array
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
