<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Middleware;

use PHPUnit\Framework\TestCase;
use Prometheus\RegistryInterface;
use Symfony\Component\Messenger\Middleware\AddBusNameStampMiddleware;
use TaskoProducts\SymfonyPrometheusExporterBundle\Middleware\MessengerEventMiddleware;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\MessageBusFactory;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Model\FooBarMessage;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Model\FooBarMessageHandler;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Model\FooBarMessageHandlerWithException;

class MessengerEventMiddlewareTest extends TestCase
{
    private RegistryInterface $registry;

    private const BUS_NAME = 'message_bus';
    private const METRIC_NAME = 'messenger';
    private const ERROR_METRIC_NAME = self::METRIC_NAME . '_error';

    protected function setUp(): void
    {
        $this->registry = PrometheusCollectorRegistryFactory::create();
    }

    public function testCollectFooBarMessageSuccessfully(): void
    {
        $givenRouting = [FooBarMessage::class => [new FooBarMessageHandler()]];

        $messageBus = MessageBusFactory::create(
            $givenRouting,
            new AddBusNameStampMiddleware(self::BUS_NAME),
            new MessengerEventMiddleware($this->registry, self::METRIC_NAME)
        );

        $messageBus->dispatch(new FooBarMessage());

        $counter = $this->registry->getCounter(self::BUS_NAME, self::METRIC_NAME);

        $this->assertEquals(self::BUS_NAME . '_' . self::METRIC_NAME, $counter->getName());
        $this->assertEquals(['message', 'label'], $counter->getLabelNames());

        $expectedMetricCounter = 1;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[0]->getSamples();

        $this->assertEquals(self::BUS_NAME . '_' . self::METRIC_NAME, $samples[0]->getName());
        $this->assertEquals($expectedMetricCounter, $samples[0]->getValue());
        $this->assertEquals([FooBarMessage::class, 'FooBarMessage'], $samples[0]->getLabelValues());
    }

    public function testErrorMetricIsInitialisedWithZeroOnSuccessfulMessage(): void
    {
        $givenRouting = [FooBarMessage::class => [new FooBarMessageHandler()]];

        $messageBus = MessageBusFactory::create(
            $givenRouting,
            new AddBusNameStampMiddleware(self::BUS_NAME),
            new MessengerEventMiddleware($this->registry, self::METRIC_NAME)
        );

        $messageBus->dispatch(new FooBarMessage());

        $counter = $this->registry->getCounter(self::BUS_NAME, self::ERROR_METRIC_NAME);

        $this->assertEquals(self::BUS_NAME . '_' . self::ERROR_METRIC_NAME, $counter->getName());
        $this->assertEquals(['message', 'label'], $counter->getLabelNames());

        $expectedMetricCounter = 0;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $this->assertEquals(self::BUS_NAME . '_' . self::ERROR_METRIC_NAME, $samples[0]->getName());
        $this->assertEquals($expectedMetricCounter, $samples[0]->getValue());
        $this->assertEquals([FooBarMessage::class, 'FooBarMessage'], $samples[0]->getLabelValues());
    }

    public function testCollectMessengerExceptionsSuccessfully(): void
    {
        $givenRouting = [FooBarMessage::class => [new FooBarMessageHandlerWithException()]];

        $messageBus = MessageBusFactory::create(
            $givenRouting,
            new AddBusNameStampMiddleware(self::BUS_NAME),
            new MessengerEventMiddleware($this->registry, self::METRIC_NAME)
        );

        try {
            $messageBus->dispatch(new FooBarMessage());
        } catch (\Throwable $exception) {
        }

        $counter = $this->registry->getCounter(self::BUS_NAME, self::ERROR_METRIC_NAME);

        $this->assertEquals(self::BUS_NAME . '_' . self::ERROR_METRIC_NAME, $counter->getName());
        $this->assertEquals(['message', 'label'], $counter->getLabelNames());

        $expectedMetricCounter = 1;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $this->assertEquals(self::BUS_NAME . '_' . self::ERROR_METRIC_NAME, $samples[0]->getName());
        $this->assertEquals($expectedMetricCounter, $samples[0]->getValue());
        $this->assertEquals([FooBarMessage::class, 'FooBarMessage'], $samples[0]->getLabelValues());
    }

    public function testInvalidCharactersInBusName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $givenInvalidBusName = 'invalid#message#bus';
        $givenValidMetricName = 'valid_metric_name';

        $messageBus = MessageBusFactory::create(
            [FooBarMessage::class => [new FooBarMessageHandler()]],
            new AddBusNameStampMiddleware($givenInvalidBusName),
            new MessengerEventMiddleware(
                $this->registry,
                $givenValidMetricName
            )
        );

        $messageBus->dispatch(new FooBarMessage());
    }

    public function testInvalidCharactersInMetricName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $givenValidBusName = 'valid_message_bus';
        $givenInvalidMetricName = 'invalid.metric.name';

        $messageBus = MessageBusFactory::create(
            [FooBarMessage::class => [new FooBarMessageHandler()]],
            new AddBusNameStampMiddleware($givenValidBusName),
            new MessengerEventMiddleware(
                $this->registry,
                $givenInvalidMetricName
            )
        );

        $messageBus->dispatch(new FooBarMessage());
    }
}
