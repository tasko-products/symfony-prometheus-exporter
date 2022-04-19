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
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Messenger\WorkerMetadata;
use TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber\ActiveWorkersMetricEventSubscriber;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;

class ActiveWorkersMetricEventSubscriberTest extends TestCase
{
    private RegistryInterface $registry;
    private ActiveWorkersMetricEventSubscriber $subscriber;
    private Worker $worker;

    private const NAMESPACE = 'messenger_events';

    protected function setUp(): void
    {
        $this->registry = PrometheusCollectorRegistryFactory::create();

        $this->worker = $this->getMockBuilder(Worker::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        $this->worker->expects($this->any())
                     ->method('getMetadata')
                     ->willReturn(new WorkerMetadata([
                         'transportNames' => ['transport', 'prio_transport'],
                         'queueNames' => [
                            'foobar_worker_queue',
                            'priority_foobar_worker_queue'
                         ]
                     ]));

        $this->subscriber = new ActiveWorkersMetricEventSubscriber($this->registry);
    }

    public function testWorkerStartedEventIsSubscribedByActiveWorkersMetricEventSubscriber(): void
    {
        $this->assertArrayHasKey(
            WorkerStartedEvent::class,
            ActiveWorkersMetricEventSubscriber::getSubscribedEvents()
        );
    }

    public function testCollectWorkerStartedMetricSuccessfully(): void
    {
        $this->subscriber->onWorkerStarted(new WorkerStartedEvent($this->worker));

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

    public function testWorkerStoppedEventIsSubscribedByActiveWorkersMetricEventSubscriber(): void
    {
        $this->assertArrayHasKey(
            WorkerStoppedEvent::class,
            ActiveWorkersMetricEventSubscriber::getSubscribedEvents()
        );
    }

    public function testCollectWorkerStoppedMetricSuccessfully(): void
    {
        $this->subscriber->onWorkerStarted(new WorkerStartedEvent($this->worker));
        $this->subscriber->onWorkerStopped(new WorkerStoppedEvent($this->worker));

        $expectedMetricGauge = 0;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }
}
