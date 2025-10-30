<?php

/**
 * @link         http://www.tasko-products.de/ tasko Products GmbH
 * @copyright    (c) tasko Products GmbH
 * @license      http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * This file is part of tasko-products/symfony-prometheus-exporter.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

    private const NAMESPACE = 'middleware';
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

        $counter = $this->registry->getCounter(self::NAMESPACE, self::METRIC_NAME);

        $this->assertEquals(self::NAMESPACE . '_' . self::METRIC_NAME, $counter->getName());
        $this->assertEquals(['bus', 'message', 'label'], $counter->getLabelNames());

        $expectedMetricCounter = 1;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[0]->getSamples();

        $this->assertEquals(self::NAMESPACE . '_' . self::METRIC_NAME, $samples[0]->getName());
        $this->assertEquals($expectedMetricCounter, $samples[0]->getValue());
        $this->assertEquals(
            [
                self::BUS_NAME,
                FooBarMessage::class,
                'FooBarMessage',
            ],
            $samples[0]->getLabelValues(),
        );
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

        $counter = $this->registry->getCounter(self::NAMESPACE, self::ERROR_METRIC_NAME);

        $this->assertEquals(self::NAMESPACE . '_' . self::ERROR_METRIC_NAME, $counter->getName());
        $this->assertEquals(['bus', 'message', 'label'], $counter->getLabelNames());

        $expectedMetricCounter = 0;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $this->assertEquals(self::NAMESPACE . '_' . self::ERROR_METRIC_NAME, $samples[0]->getName());
        $this->assertEquals($expectedMetricCounter, $samples[0]->getValue());
        $this->assertEquals(
            [
                self::BUS_NAME,
                FooBarMessage::class,
                'FooBarMessage',
            ],
            $samples[0]->getLabelValues(),
        );
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

        $counter = $this->registry->getCounter(self::NAMESPACE, self::ERROR_METRIC_NAME);

        $this->assertEquals(self::NAMESPACE . '_' . self::ERROR_METRIC_NAME, $counter->getName());
        $this->assertEquals(['bus', 'message', 'label'], $counter->getLabelNames());

        $expectedMetricCounter = 1;
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[1]->getSamples();

        $this->assertEquals(self::NAMESPACE . '_' . self::ERROR_METRIC_NAME, $samples[0]->getName());
        $this->assertEquals($expectedMetricCounter, $samples[0]->getValue());
        $this->assertEquals(
            [
                self::BUS_NAME,
                FooBarMessage::class,
                'FooBarMessage',
            ],
            $samples[0]->getLabelValues(),
        );
    }

    public function testIgnoreInvalidMetricNameCharactersInBusNameDueToLabel(): void
    {
        $givenBusName = 'invalid/metric§name%characters=in#message?bus';
        $givenConfiguration = new ConfigurationProvider(new ParameterBag());

        $messageBus = MessageBusFactory::create(
            [FooBarMessage::class => [new FooBarMessageHandler()]],
            new AddBusNameStampMiddleware($givenBusName),
            new MessengerEventMiddleware(
                $this->registry,
                $givenConfiguration,
            ),
        );

        $messageBus->dispatch(new FooBarMessage());

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[0]->getSamples();

        $this->assertContains($givenBusName, $samples[0]->getLabelValues());
    }

    public function testInvalidCharactersInMetricName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $givenValidBusName = 'valid_message_bus';
        $givenConfiguration = new ConfigurationProvider(
            new ParameterBag(
                [
                    'tasko_products_symfony_prometheus_exporter' => [
                        'middlewares' => [
                            'event_middleware' => [
                                'metric_name' => 'invalid.metric.name',
                            ],
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
                            'tasko_products_symfony_prometheus_exporter' => [
                                'middlewares' => [
                                    'event_middleware' => [
                                        'namespace' => 'test_namespace',
                                        'metric_name' => 'test_metric',
                                        'help_text' => 'test help text',
                                        'labels' => [
                                            'bus' => 'test_bus',
                                            'message' => 'test_message',
                                            'label' => 'test_label',
                                        ],
                                        'error_help_text' => 'test error help text',
                                        'error_labels' => [
                                            'bus' => 'test_bus',
                                            'message' => 'test_message',
                                            'label' => 'test_label',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ),
                ),
            ),
        );

        $messageBus->dispatch(new FooBarMessage());

        $counter = $this->registry->getCounter('test_namespace', 'test_metric');

        $this->assertEquals('test_namespace_test_metric', $counter->getName());
        $this->assertEquals(
            [
                'test_bus',
                'test_message',
                'test_label',
            ],
            $counter->getLabelNames(),
        );

        $errorCounter = $this->registry->getCounter('test_namespace', 'test_metric_error');

        $this->assertEquals('test_namespace_test_metric_error', $errorCounter->getName());
        $this->assertEquals(
            [
                'test_bus',
                'test_message',
                'test_label',
            ],
            $errorCounter->getLabelNames(),
        );
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
                            'tasko_products_symfony_prometheus_exporter' => [
                                'middlewares' => [
                                    'event_middleware' => [
                                        'namespace' => 'test_namespace',
                                        'metric_name' => 'test_metric',
                                        'help_text' => 'test help text',
                                        'labels' => [
                                            'bus' => 'test_bus',
                                            'message' => 'test_message',
                                            'label' => 'test_label',
                                        ],
                                        'error_help_text' => 'test error help text',
                                        'error_labels' => [
                                            'bus' => 'test_bus',
                                            'message' => 'test_message',
                                            'label' => 'test_label',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ),
                ),
            ),
        );

        try {
            $messageBus->dispatch(new FooBarMessage());
        } catch (\Throwable $exception) {
        }

        $counter = $this->registry->getCounter('test_namespace', 'test_metric');

        $this->assertEquals('test_namespace_test_metric', $counter->getName());
        $this->assertEquals(
            [
                'test_bus',
                'test_message',
                'test_label',
            ],
            $counter->getLabelNames(),
        );

        $errorCounter = $this->registry->getCounter('test_namespace', 'test_metric_error');

        $this->assertEquals('test_namespace_test_metric_error', $errorCounter->getName());
        $this->assertEquals(
            [
                'test_bus',
                'test_message',
                'test_label',
            ],
            $errorCounter->getLabelNames(),
        );
    }

    public function testMessageBusNameNotIncludedInMetricName(): void
    {
        $expectedNamespace = 'test_namespace';
        $expectedMetric = 'test_metric';

        $givenRouting = [FooBarMessage::class => [new FooBarMessageHandler()]];

        $messageBus = MessageBusFactory::create(
            $givenRouting,
            new AddBusNameStampMiddleware(self::BUS_NAME),
            new MessengerEventMiddleware(
                $this->registry,
                new ConfigurationProvider(
                    new ParameterBag(
                        [
                            'tasko_products_symfony_prometheus_exporter' => [
                                'middlewares' => [
                                    'event_middleware' => [
                                        'namespace' => 'test_namespace',
                                        'metric_name' => 'test_metric',
                                    ],
                                ],
                            ],
                        ],
                    ),
                ),
            ),
        );

        $messageBus->dispatch(new FooBarMessage());

        $counter = $this->registry->getCounter($expectedNamespace, $expectedMetric);

        $this->assertEquals($expectedNamespace . '_' . $expectedMetric, $counter->getName());
        $this->assertEquals(['bus', 'message', 'label'], $counter->getLabelNames());

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[0]->getSamples();

        $this->assertEquals($expectedNamespace . '_' . $expectedMetric, $samples[0]->getName());
        $this->assertEquals(
            [
                self::BUS_NAME,
                FooBarMessage::class,
                'FooBarMessage',
            ],
            $samples[0]->getLabelValues(),
        );
    }
}
