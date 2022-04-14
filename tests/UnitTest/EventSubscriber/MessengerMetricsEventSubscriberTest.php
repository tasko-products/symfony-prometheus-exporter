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
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber\MessengerMetricsEventSubscriber;

class MessengerMetricsEventSubscriberTest extends TestCase
{
    public function testWorkerStartedEventIsSubscribedByMessengerMetricsEventSubscriber(): void
    {
        $this->assertArrayHasKey(
            WorkerStartedEvent::class,
            MessengerMetricsEventSubscriber::getSubscribedEvents()
        );
    }
}
