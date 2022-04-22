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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
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

        $this->subscriber = new ActiveWorkersMetricEventSubscriber(
            $this->registry,
            new ParameterBag(),
        );
    }

    public function testRequiredActiveWorkerEventsSubscribed(): void
    {
        $this->assertEquals(
            [
                WorkerStartedEvent::class,
                WorkerStoppedEvent::class,
            ],
            array_keys(ActiveWorkersMetricEventSubscriber::getSubscribedEvents()),
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

    public function testCollectWorkerStoppedMetricSuccessfully(): void
    {
        $this->subscriber->onWorkerStarted(new WorkerStartedEvent($this->worker));
        $this->subscriber->onWorkerStopped(new WorkerStoppedEvent($this->worker));

        $expectedMetricGauge = 0;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }

    public function testConfigureSubscriberViaParameterBag(): void
    {
        $this->subscriber = new ActiveWorkersMetricEventSubscriber(
            $this->registry,
            new ParameterBag(
                [
                    'prometheus_metrics.event_subscribers' => [
                        'active_workers' => [
                            'enabled' => true,
                            'namespace' => 'test_namespace',
                            'metric_name' => 'test_metric',
                            'help_text' => 'test help text',
                            'labels' => [
                                'queue_names' => 'test_queue_names',
                                'transport_names' => 'test_transport_names',
                            ],
                        ],
                    ],
                ],
            ),
        );

        $this->subscriber->onWorkerStarted(new WorkerStartedEvent($this->worker));
        $this->subscriber->onWorkerStopped(new WorkerStoppedEvent($this->worker));

        $gauge = $this->registry->getGauge('test_namespace', 'test_metric');
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $this->assertEquals('test_namespace_test_metric', $gauge->getName());
        $this->assertEquals(
            [
                'test_queue_names',
                'test_transport_names'
            ],
            $gauge->getLabelNames()
        );
    }
}
