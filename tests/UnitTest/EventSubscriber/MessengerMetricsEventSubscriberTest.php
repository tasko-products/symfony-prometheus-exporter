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
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Worker;
use TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber\MessengerMetricsEventSubscriber;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;

class MessengerMetricsEventSubscriberTest extends TestCase
{
    private RegistryInterface $registry;

    private const BUS_NAME = 'message_bus';
    private const METRIC_NAME = 'event';

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
        $subscriber = new MessengerMetricsEventSubscriber($this->registry, self::METRIC_NAME);

        $subscriber->onWorkerStarted(
            new WorkerStartedEvent(
                new Worker(
                    [],
                    new MessageBus()
                )
            )
        );

        $gauge = $this->registry->getGauge(self::BUS_NAME, self::METRIC_NAME);

        $this->assertEquals(self::BUS_NAME . '_' . self::METRIC_NAME, $gauge->getName());
        $this->assertEquals(['queue_names', 'transport_names'], $gauge->getLabelNames());

        $expectedMetricGauge = 1;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $this->assertEquals($expectedMetricGauge, $samples[0]->getValue());
    }
}
