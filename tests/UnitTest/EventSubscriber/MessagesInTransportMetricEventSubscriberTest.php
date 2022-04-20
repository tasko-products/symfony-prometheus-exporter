<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus\RegistryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber\MessagesInTransportMetricEventSubscriber;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Fixture\FooBarMessage;

class MessagesInTransportMetricEventSubscriberTest extends TestCase
{
    private RegistryInterface $registry;
    private MessagesInTransportMetricEventSubscriber $subscriber;

    private const NAMESPACE = 'messenger_events';
    private const METRIC = 'messages_in_transport';

    protected function setUp(): void
    {
        $this->registry = PrometheusCollectorRegistryFactory::create();
        $this->subscriber = new MessagesInTransportMetricEventSubscriber($this->registry);
    }

    public function testRequiredMessagesInProcessEventsSubscribed(): void
    {
        $this->assertEquals(
            [
                SendMessageToTransportsEvent::class,
                WorkerMessageReceivedEvent::class,
            ],
            array_keys(MessagesInTransportMetricEventSubscriber::getSubscribedEvents()),
        );
    }

    public function testCollectSendMessageToTransportsMetricSuccessfully(): void
    {
        $this->subscriber->onSendMessageToTransports(
            new SendMessageToTransportsEvent(
                new Envelope(
                    new FooBarMessage(),
                    [new BusNameStamp('foobar_bus')],
                ),
            ),
        );

        $gauge = $this->registry->getGauge(self::NAMESPACE, self::METRIC);
        $this->assertEquals(
            ['message_path', 'message_class', 'bus'],
            $gauge->getLabelNames(),
        );

        $expectedMetricGauge = 1;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
        $this->assertEquals(
            [
                FooBarMessage::class,
                'FooBarMessage',
                'foobar_bus',
            ],
            $samples[0]->getLabelValues(),
        );
    }

    public function testCollectWorkerMessageReceivedMetricSuccessfully(): void
    {
        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent(new Envelope(new FooBarMessage()), ''),
        );

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $expectedMetricGauge = -1;

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }

    public function testCollectSendMessageToTransportsAndWorkerMessageReceivedMetricSuccessfully(): void
    {
        $envelope = new Envelope(new FooBarMessage(), [new BusNameStamp('foobar_bus')]);

        $this->subscriber->onSendMessageToTransports(
            new SendMessageToTransportsEvent($envelope),
        );

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent($envelope, ''),
        );

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $expectedMetricGauge = 0;

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }

    public function testIgnoreRedeliveredWorkerMessageReceivedEvents(): void
    {
        $this->expectException(MetricNotFoundException::class);

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent(
                new Envelope(
                    new FooBarMessage(),
                    [new RedeliveryStamp(1)]
                ),
                '',
            ),
        );

        $this->registry->getGauge(self::NAMESPACE, self::METRIC);
    }

    public function testCollectMessagesInTransportMetricNotNegativOnRetry(): void
    {
        $envelope = new Envelope(new FooBarMessage(), [new BusNameStamp('foobar_bus')]);

        $this->subscriber->onSendMessageToTransports(
            new SendMessageToTransportsEvent($envelope),
        );

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent($envelope, ''),
        );

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent($envelope->with(new RedeliveryStamp(1)), ''),
        );

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $expectedMetricGauge = 0;

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }
}
