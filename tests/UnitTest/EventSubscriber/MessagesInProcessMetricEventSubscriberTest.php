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
use Prometheus\RegistryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber\MessagesInProcessMetricEventSubscriber;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Fixture\FooBarMessage;

class MessagesInProcessMetricEventSubscriberTest extends TestCase
{
    private RegistryInterface $registry;
    private MessagesInProcessMetricEventSubscriber $subscriber;

    private const NAMESPACE = 'messenger_events';

    protected function setUp(): void
    {
        $this->registry = PrometheusCollectorRegistryFactory::create();
        $this->subscriber = new MessagesInProcessMetricEventSubscriber($this->registry);
    }

    public function testWorkerMessageReceivedEventIsSubscribedByMessagesInProcessMetricEventSubscriber(): void
    {
        $this->assertArrayHasKey(
            WorkerMessageReceivedEvent::class,
            MessagesInProcessMetricEventSubscriber::getSubscribedEvents()
        );
    }

    public function testCollectWorkerMessageReceivedMetricSuccessfully(): void
    {
        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent(
                new Envelope(
                    new FooBarMessage(),
                    [new BusNameStamp('foobar_bus')],
                ),
                'foobar_receiver'
            )
        );

        $messagesInProcessMetric = 'messages_in_process';
        $gauge = $this->registry->getGauge(self::NAMESPACE, $messagesInProcessMetric);

        $this->assertEquals(self::NAMESPACE . '_' . $messagesInProcessMetric, $gauge->getName());
        $this->assertEquals(
            ['message_path', 'message_class', 'receiver', 'bus'],
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
                'foobar_receiver',
                'foobar_bus',
            ],
            $samples[0]->getLabelValues(),
        );
    }

    public function testWorkerMessageHandledEventIsSubscribedByMessagesInProcessMetricEventSubscriber(): void
    {
        $this->assertArrayHasKey(
            WorkerMessageHandledEvent::class,
            MessagesInProcessMetricEventSubscriber::getSubscribedEvents()
        );
    }

    public function testCollectWorkerMessageHandledMetricSuccessfully(): void
    {
        $this->subscriber->onWorkerMessageHandled(
            new WorkerMessageHandledEvent(new Envelope(new FooBarMessage()), 'foobar_receiver')
        );

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $expectedMetricGauge = -1;

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }
}
