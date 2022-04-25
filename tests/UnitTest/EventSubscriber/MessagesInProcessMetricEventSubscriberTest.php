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

use Exception;
use PHPUnit\Framework\TestCase;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus\RegistryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProvider;
use TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber\MessagesInProcessMetricEventSubscriber;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Fixture\FooBarMessage;

class MessagesInProcessMetricEventSubscriberTest extends TestCase
{
    private RegistryInterface $registry;
    private MessagesInProcessMetricEventSubscriber $subscriber;

    private const NAMESPACE = 'messenger_events';
    private const METRIC = 'messages_in_process';

    protected function setUp(): void
    {
        $this->registry = PrometheusCollectorRegistryFactory::create();
        $this->subscriber = new MessagesInProcessMetricEventSubscriber(
            $this->registry,
            new ConfigurationProvider(new ParameterBag()),
        );
    }

    public function testRequiredMessagesInProcessEventsSubscribed(): void
    {
        $this->assertEquals(
            [
                WorkerMessageReceivedEvent::class,
                WorkerMessageHandledEvent::class,
                WorkerMessageFailedEvent::class,
            ],
            array_keys(MessagesInProcessMetricEventSubscriber::getSubscribedEvents()),
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
                'foobar_receiver',
            ),
        );

        $gauge = $this->registry->getGauge(self::NAMESPACE, self::METRIC);

        $this->assertEquals(self::NAMESPACE . '_' . self::METRIC, $gauge->getName());
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

    public function testCollectWorkerMessageHandledMetricSuccessfully(): void
    {
        $this->subscriber->onWorkerMessageHandled(
            new WorkerMessageHandledEvent(new Envelope(new FooBarMessage()), 'foobar_receiver'),
        );

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $expectedMetricGauge = -1;

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }

    public function testCollectWorkerMessageReceivedAndHandledMetricSuccessfully(): void
    {
        $envelope = new Envelope(new FooBarMessage());
        $receiver = 'foobar_receiver';

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent($envelope, $receiver),
        );

        $this->subscriber->onWorkerMessageHandled(
            new WorkerMessageHandledEvent($envelope, $receiver),
        );

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $expectedMetricGauge = 0;

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }

    public function testCollectWorkerMessageFailedMetricSuccessfully(): void
    {
        $this->subscriber->onWorkerMessageFailed(
            new WorkerMessageFailedEvent(
                new Envelope(new FooBarMessage()),
                'foobar_receiver',
                new Exception('boom!'),
            ),
        );

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $expectedMetricGauge = -1;

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }

    public function testCollectWorkerMessageReceivedAndFailedMetricSuccessfully(): void
    {
        $envelope = new Envelope(new FooBarMessage());
        $receiver = 'foobar_receiver';

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent($envelope, $receiver),
        );

        $this->subscriber->onWorkerMessageFailed(
            new WorkerMessageFailedEvent($envelope, $receiver, new Exception('boom!')),
        );

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $expectedMetricGauge = 0;

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }

    public function testIgnoreWorkerMessageFailedWithWillRetryTrue(): void
    {
        $this->expectException(MetricNotFoundException::class);

        $event = new WorkerMessageFailedEvent(
            new Envelope(new FooBarMessage()),
            'foobar_receiver',
            new Exception('boom!'),
        );

        $event->setForRetry();

        $this->subscriber->onWorkerMessageFailed($event);

        $this->registry->getGauge(self::NAMESPACE, self::METRIC);
    }

    public function testCollectWorkerMessageReceivedMetricSuccessfullyAndIgnoreRetryingFailureEvent(): void
    {
        $envelope = new Envelope(new FooBarMessage());
        $receiver = 'foobar_receiver';

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent($envelope, $receiver),
        );

        $failureEvent = new WorkerMessageFailedEvent($envelope, $receiver, new Exception('boom!'));

        $failureEvent->setForRetry();

        $this->subscriber->onWorkerMessageFailed($failureEvent);

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $expectedMetricGauge = 1;

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }

    public function testIgnoreRedeliveredWorkerMessageReceivedEvents(): void
    {
        $this->expectException(MetricNotFoundException::class);

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent(
                new Envelope(
                    new FooBarMessage(),
                    [new RedeliveryStamp(1)],
                ),
                '',
            ),
        );

        $this->registry->getGauge(self::NAMESPACE, self::METRIC);
    }

    public function testCollectMessagesInProgressMetricIgnoresRedeliveredWorkerMessageReceived(): void
    {
        $envelope = new Envelope(new FooBarMessage(), [new BusNameStamp('foobar_bus')]);

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent($envelope, ''),
        );

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent($envelope->with(new RedeliveryStamp(1)), ''),
        );

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent($envelope->with(new RedeliveryStamp(2)), ''),
        );

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent($envelope->with(new RedeliveryStamp(3)), ''),
        );

        $this->subscriber->onWorkerMessageHandled(
            new WorkerMessageHandledEvent($envelope, ''),
        );


        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $expectedMetricGauge = 0;

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }

    public function testConfigureSubscriberViaConfiguration(): void
    {
        $this->subscriber = new MessagesInProcessMetricEventSubscriber(
            $this->registry,
            new ConfigurationProvider(
                new ParameterBag(
                    [
                        'prometheus_metrics.event_subscribers' => [
                            'messages_in_process' => [
                                'enabled' => true,
                                'namespace' => 'test_namespace',
                                'metric_name' => 'test_metric',
                                'help_text' => 'test help text',
                                'labels' => [
                                    'message_path' => 'test_message_path',
                                    'message_class' => 'test_message_class',
                                    'receiver' => 'test_receiver',
                                    'bus' => 'test_bus',
                                ],
                            ],
                        ],
                    ],
                ),
            ),
        );

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent(
                new Envelope(new FooBarMessage()),
                'foobar_receiver',
            ),
        );

        $gauge = $this->registry->getGauge('test_namespace', 'test_metric');

        $this->assertEquals('test_namespace_test_metric', $gauge->getName());
        $this->assertEquals(
            [
                'test_message_path',
                'test_message_class',
                'test_receiver',
                'test_bus',
            ],
            $gauge->getLabelNames()
        );
    }

    public function testDisableSubscriberViaConfiguration(): void
    {
        $this->expectException(MetricNotFoundException::class);

        $this->subscriber = new MessagesInProcessMetricEventSubscriber(
            $this->registry,
            new ConfigurationProvider(
                new ParameterBag(
                    [
                        'prometheus_metrics.event_subscribers' => [
                            'messages_in_process' => [
                                'enabled' => false,
                            ],
                        ],
                    ],
                ),
            ),
        );

        $this->subscriber->onWorkerMessageReceived(
            new WorkerMessageReceivedEvent(
                new Envelope(new FooBarMessage()),
                'foobar_receiver',
            ),
        );

        $this->registry->getGauge('messenger_events', 'messages_in_process');
    }
}
