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

class MessagesInTransportMetricEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RegistryInterface $registry,
        private string $messengerNamespace = 'messenger_events',
        private string $messagesInTransportMetricName = 'messages_in_transport',
        private string $helpText = 'Messages In Transport',
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SendMessageToTransportsEvent::class => 'onSendMessageToTransports',
        ];
    }

    public function onSendMessageToTransports(SendMessageToTransportsEvent $event): void
    {
        $this->messagesInTransportGauge()->inc();
    }

    private function messagesInTransportGauge(): Gauge
    {
        return $this->registry->getOrRegisterGauge(
            $this->messengerNamespace,
            $this->messagesInTransportMetricName,
            $this->helpText,
        );
    }
}
