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
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\EnvelopeMethodesTrait;

class MessagesInTransportMetricEventSubscriber implements EventSubscriberInterface
{
    use EnvelopeMethodesTrait;

    /**
     * @param string[] $labels
     */
    public function __construct(
        private RegistryInterface $registry,
        private string $messengerNamespace = 'messenger_events',
        private string $messagesInTransportMetricName = 'messages_in_transport',
        private string $helpText = 'Messages In Transport',
        private array  $labels = ['message_path', 'message_class', 'bus'],
    ) {
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
        $this->messagesInTransportGauge()->inc($this->messagesInTransportLabels($event));
    }

    public function onWorkerMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        if ($this->isRedelivered($event->getEnvelope())) {
            return;
        }

        $this->messagesInTransportGauge()->dec($this->messagesInTransportLabels($event));
    }

    private function isRedelivered(Envelope $envelope): bool
    {
        return $envelope->last(RedeliveryStamp::class) !== null;
    }

    private function messagesInTransportGauge(): Gauge
    {
        return $this->registry->getOrRegisterGauge(
            $this->messengerNamespace,
            $this->messagesInTransportMetricName,
            $this->helpText,
            $this->labels,
        );
    }

    /**
     * @return string[]
     */
    private function messagesInTransportLabels(SendMessageToTransportsEvent|WorkerMessageReceivedEvent $event): array
    {
        $envelope = $event->getEnvelope();

        return [
            $this->messageClassPathLabel($envelope),
            $this->messageClassLabel($envelope),
            $this->extractBusName($envelope),
        ];
    }
}
