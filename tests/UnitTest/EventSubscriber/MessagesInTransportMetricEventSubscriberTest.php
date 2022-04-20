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
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber\MessagesInTransportMetricEventSubscriber;

class MessagesInTransportMetricEventSubscriberTest extends TestCase
{
    public function testRequiredMessagesInProcessEventsSubscribed(): void
    {
        $this->assertEquals(
            [
                SendMessageToTransportsEvent::class,
            ],
            array_keys(MessagesInTransportMetricEventSubscriber::getSubscribedEvents()),
        );
    }
}
