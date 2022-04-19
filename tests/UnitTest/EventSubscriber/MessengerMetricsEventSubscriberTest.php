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
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Messenger\WorkerMetadata;
use TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber\MessengerMetricsEventSubscriber;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;

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
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(
            new MessengerMetricsEventSubscriber($this->registry)
        );

        /**
         * @var Worker $workerMock
        */
        $workerMock = $this->getMockBuilder(Worker::class)
                           ->disableOriginalConstructor()
                           ->getMock();

        $workerMock->expects($this->once())
                   ->method('getMetadata')
                   ->willReturn(new WorkerMetadata([
                        'transportNames' => ['transport', 'prio_transport'],
                        'queueNames' => [
                           'foobar_worker_queue',
                           'priority_foobar_worker_queue'
                        ]
                    ]));

        (new MessengerMetricsEventSubscriber($this->registry))
            ->onWorkerStarted(new WorkerStartedEvent($workerMock));

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

    public function testCollectWorkerStoppedMetricSuccessfully(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(
            new MessengerMetricsEventSubscriber($this->registry)
        );

        /**
         * @var Worker $workerMock
         */
        $workerMock = $this->getMockBuilder(Worker::class)
                           ->disableOriginalConstructor()
                           ->getMock();

        $workerMock->expects($this->atLeast(2))
                   ->method('getMetadata')
                   ->willReturn(new WorkerMetadata([
                        'transportNames' => ['transport', 'prio_transport'],
                        'queueNames' => [
                           'foobar_worker_queue',
                           'priority_foobar_worker_queue'
                        ]
                    ]));

        $subscriber = new MessengerMetricsEventSubscriber($this->registry);
        $subscriber->onWorkerStarted(new WorkerStartedEvent($workerMock));
        $subscriber->onWorkerStopped(new WorkerStoppedEvent($workerMock));

        $expectedMetricGauge = 0;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }
}
