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
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMessageLimitListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Worker;
use TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber\MessengerMetricsEventSubscriber;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Fixture\FooBarMessage;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Fixture\FooBarReceiver;

class MessengerMetricsEventSubscriberTest extends TestCase
{
    private RegistryInterface $registry;

    private const NAMESPACE = 'messenger_events';

    protected function setUp(): void
    {
        $this->registry = PrometheusCollectorRegistryFactory::create();
    }

    public function testWorkerStartedEventIsSubscribedByMessengerMetricsEventSubscriber(): void
    {
        $this->assertArrayHasKey(
            WorkerStartedEvent::class,
            MessengerMetricsEventSubscriber::getSubscribedEvents()
        );
    }

    public function testCollectWorkerStartedMetricSuccessfully(): void
    {
        $transports = [
            'transport' => new FooBarReceiver([[new Envelope(new FooBarMessage())]]),
            'prio_transport' => new FooBarReceiver([[new Envelope(new FooBarMessage())]]),
        ];
        $bus = $this->createMock(MessageBusInterface::class);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(
            new MessengerMetricsEventSubscriber($this->registry)
        );
        $dispatcher->addSubscriber(new StopWorkerOnMessageLimitListener(1));

        (new Worker(
            $transports,
            $bus,
            $dispatcher
        ))->run(['queues' => ['foobar_worker_queue', 'priority_foobar_worker_queue']]);

        $activeWorkerMetric = 'active_workers';
        $gauge = $this->registry->getGauge(self::NAMESPACE, $activeWorkerMetric);

        $this->assertEquals(self::NAMESPACE . '_' . $activeWorkerMetric, $gauge->getName());
        $this->assertEquals(['queue_names', 'transport_names'], $gauge->getLabelNames());

        $expectedMetricGauge = 1;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
        $this->assertEquals(
            [
                'foobar_worker_queue, priority_foobar_worker_queue',
                'transport, prio_transport',
            ],
            $samples[0]->getLabelValues(),
        );
    }
}
