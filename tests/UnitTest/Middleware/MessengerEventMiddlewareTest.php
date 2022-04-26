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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Messenger\Middleware\AddBusNameStampMiddleware;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProvider;
use TaskoProducts\SymfonyPrometheusExporterBundle\Middleware\MessengerEventMiddleware;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\MessageBusFactory;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Fixture\FooBarMessage;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Fixture\FooBarMessageHandler;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Fixture\FooBarMessageHandlerWithException;

class MessengerEventMiddlewareTest extends TestCase
{
    private RegistryInterface $registry;

    private const BUS_NAME = 'message_bus';
    private const METRIC_NAME = 'message';
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
            new MessengerEventMiddleware(
                $this->registry,
                new ConfigurationProvider(new ParameterBag()),
            ),
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
            new MessengerEventMiddleware(
                $this->registry,
                new ConfigurationProvider(new ParameterBag()),
            ),
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
            new MessengerEventMiddleware(
                $this->registry,
                new ConfigurationProvider(new ParameterBag()),
            ),
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
        $givenConfiguration = new ConfigurationProvider(new ParameterBag());

        $messageBus = MessageBusFactory::create(
            [FooBarMessage::class => [new FooBarMessageHandler()]],
            new AddBusNameStampMiddleware($givenInvalidBusName),
            new MessengerEventMiddleware(
                $this->registry,
                $givenConfiguration,
            ),
        );

        $messageBus->dispatch(new FooBarMessage());
    }

    public function testInvalidCharactersInMetricName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $givenValidBusName = 'valid_message_bus';
        $givenConfiguration = new ConfigurationProvider(
            new ParameterBag(
                [
                    'prometheus_metrics.middlewares' => [
                        'event_middleware' => [
                            'metric_name' => 'invalid.metric.name',
                        ],
                    ],
                ],
            ),
        );

        $messageBus = MessageBusFactory::create(
            [FooBarMessage::class => [new FooBarMessageHandler()]],
            new AddBusNameStampMiddleware($givenValidBusName),
            new MessengerEventMiddleware(
                $this->registry,
                $givenConfiguration,
            ),
        );

        $messageBus->dispatch(new FooBarMessage());
    }

    public function testConfigureMiddlewareViaConfiguration(): void
    {
        $givenRouting = [FooBarMessage::class => [new FooBarMessageHandler()]];

        $messageBus = MessageBusFactory::create(
            $givenRouting,
            new AddBusNameStampMiddleware(self::BUS_NAME),
            new MessengerEventMiddleware(
                $this->registry,
                new ConfigurationProvider(
                    new ParameterBag(
                        [
                            'prometheus_metrics.middlewares' => [
                                'event_middleware' => [
                                    'metric_name' => 'test_metric',
                                    'help_text' => 'test help text',
                                    'labels' => [
                                        'message' => 'test_message',
                                        'label' => 'test_label',
                                    ],
                                    'error_help_text' => 'test error help text',
                                    'error_labels' => [
                                        'message' => 'test_message',
                                        'label' => 'test_label',
                                    ],
                                ],
                            ],
                        ],
                    ),
                ),
            )
        );

        $messageBus->dispatch(new FooBarMessage());

        $counter = $this->registry->getCounter(self::BUS_NAME, 'test_metric');

        $this->assertEquals(self::BUS_NAME . '_test_metric', $counter->getName());
        $this->assertEquals(['test_message', 'test_label'], $counter->getLabelNames());


        $errorCounter = $this->registry->getCounter(self::BUS_NAME, 'test_metric_error');

        $this->assertEquals(self::BUS_NAME . '_test_metric_error', $errorCounter->getName());
        $this->assertEquals(['test_message', 'test_label'], $errorCounter->getLabelNames());
    }

    public function testConfigureErrorMiddlewareViaConfiguration(): void
    {
        $givenRouting = [FooBarMessage::class => [new FooBarMessageHandlerWithException()]];

        $messageBus = MessageBusFactory::create(
            $givenRouting,
            new AddBusNameStampMiddleware(self::BUS_NAME),
            new MessengerEventMiddleware(
                $this->registry,
                new ConfigurationProvider(
                    new ParameterBag(
                        [
                            'prometheus_metrics.middlewares' => [
                                'event_middleware' => [
                                    'metric_name' => 'test_metric',
                                    'help_text' => 'test help text',
                                    'labels' => [
                                        'message' => 'test_message',
                                        'label' => 'test_label',
                                    ],
                                    'error_help_text' => 'test error help text',
                                    'error_labels' => [
                                        'message' => 'test_message',
                                        'label' => 'test_label',
                                    ],
                                ],
                            ],
                        ],
                    ),
                ),
            )
        );

        try {
            $messageBus->dispatch(new FooBarMessage());
        } catch (\Throwable $exception) {
        }

        $counter = $this->registry->getCounter(self::BUS_NAME, 'test_metric');

        $this->assertEquals(self::BUS_NAME . '_test_metric', $counter->getName());
        $this->assertEquals(['test_message', 'test_label'], $counter->getLabelNames());

        $errorCounter = $this->registry->getCounter(self::BUS_NAME, 'test_metric_error');

        $this->assertEquals(self::BUS_NAME . '_test_metric_error', $errorCounter->getName());
        $this->assertEquals(['test_message', 'test_label'], $errorCounter->getLabelNames());
    }
}
