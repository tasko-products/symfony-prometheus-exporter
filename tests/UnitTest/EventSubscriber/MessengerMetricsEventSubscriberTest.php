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
use TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber\MessengerMetricsEventSubscriber;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;

class MessengerMetricsEventSubscriberTest extends TestCase
{
    private RegistryInterface $registry;

    private const NAMESPACE = 'messenger_events';
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
        $subscriber = new MessengerMetricsEventSubscriber();

        $subscriber->onWorkerStarted();

        $counter = $this->registry->getCounter(self::NAMESPACE, self::METRIC_NAME);

        $this->assertEquals(self::NAMESPACE . '_' . self::METRIC_NAME, $counter->getName());
    }
}
