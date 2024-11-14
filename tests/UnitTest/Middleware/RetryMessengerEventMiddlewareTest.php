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
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\AddBusNameStampMiddleware;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProvider;
use TaskoProducts\SymfonyPrometheusExporterBundle\Middleware\RetryMessengerEventMiddleware;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\MessageBusFactory;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Fixture\FooBarMessage;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Fixture\FooBarMessageHandler;

class RetryMessengerEventMiddlewareTest extends TestCase
{
    private RegistryInterface $registry;

    private const NAMESPACE = 'middleware';
    private const BUS_NAME = 'message_bus';
    private const METRIC_NAME = 'retry_message';

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
            new RetryMessengerEventMiddleware(
                $this->registry,
                new ConfigurationProvider(new ParameterBag()),
            ),
        );

        $messageBus->dispatch((new Envelope(new FooBarMessage()))->with(new RedeliveryStamp(1)));

        $counter = $this->registry->getCounter(self::NAMESPACE, self::METRIC_NAME);

        $this->assertEquals(self::NAMESPACE . '_' . self::METRIC_NAME, $counter->getName());
        $this->assertEquals(['bus', 'message', 'label', 'retry'], $counter->getLabelNames());

        $expectedMetricCounter = 1;
        $expectedLabelValues = [self::BUS_NAME, FooBarMessage::class, 'FooBarMessage', '1'];
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[0]->getSamples();

        $this->assertEquals($expectedMetricCounter, $samples[0]->getValue());
        $this->assertEquals($expectedLabelValues, $samples[0]->getLabelValues());
    }

    public function testCollectDefaultRetryMetricWhenRedeliveryStampIsNotSet(): void
    {
        $givenRouting = [FooBarMessage::class => [new FooBarMessageHandler()]];

        $messageBus = MessageBusFactory::create(
            $givenRouting,
            new AddBusNameStampMiddleware(self::BUS_NAME),
            new RetryMessengerEventMiddleware(
                $this->registry,
                new ConfigurationProvider(new ParameterBag()),
            ),
        );

        $messageBus->dispatch(new FooBarMessage());

        $counter = $this->registry->getCounter(self::NAMESPACE, self::METRIC_NAME);

        $this->assertEquals(self::NAMESPACE . '_' . self::METRIC_NAME, $counter->getName());
        $this->assertEquals(['bus', 'message', 'label', 'retry'], $counter->getLabelNames());

        $expectedMetricCounter = 0;
        $expectedLabelValues = [self::BUS_NAME, FooBarMessage::class, 'FooBarMessage', '0'];
        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[0]->getSamples();

        $this->assertEquals($expectedMetricCounter, $samples[0]->getValue());
        $this->assertEquals($expectedLabelValues, $samples[0]->getLabelValues());
    }

    public function testConfigureMiddlewareViaConfiguration(): void
    {
        $givenRouting = [FooBarMessage::class => [new FooBarMessageHandler()]];

        $messageBus = MessageBusFactory::create(
            $givenRouting,
            new AddBusNameStampMiddleware(self::BUS_NAME),
            new RetryMessengerEventMiddleware(
                $this->registry,
                new ConfigurationProvider(
                    new ParameterBag(
                        [
                            'tasko_products_symfony_prometheus_exporter' => [
                                'middlewares' => [
                                    'retry_event_middleware' => [
                                        'namespace' => 'test_namespace',
                                        'metric_name' => 'test_metric',
                                        'help_text' => 'test help text',
                                        'labels' => [
                                            'bus' => 'test_bus',
                                            'message' => 'test_message',
                                            'label' => 'test_label',
                                            'retry' => 'test_retry',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ),
                ),
            ),
        );

        $messageBus->dispatch(new Envelope(new FooBarMessage()))->with(new RedeliveryStamp(1));

        $counter = $this->registry->getCounter('test_namespace', 'test_metric');

        $this->assertEquals('test_namespace_test_metric', $counter->getName());
        $this->assertEquals([
            'test_bus',
            'test_message',
            'test_label',
            'test_retry',
        ], $counter->getLabelNames());
    }

    public function testMessageBusNameNotIncludedInMetricName(): void
    {
        $expectedNamespace = 'test_namespace';
        $expectedMetric = 'test_metric';

        $givenRouting = [FooBarMessage::class => [new FooBarMessageHandler()]];

        $messageBus = MessageBusFactory::create(
            $givenRouting,
            new AddBusNameStampMiddleware(self::BUS_NAME),
            new RetryMessengerEventMiddleware(
                $this->registry,
                new ConfigurationProvider(
                    new ParameterBag(
                        [
                            'tasko_products_symfony_prometheus_exporter' => [
                                'middlewares' => [
                                    'retry_event_middleware' => [
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
        $this->assertEquals(['bus', 'message', 'label', 'retry'], $counter->getLabelNames());

        $metrics = $this->registry->getMetricFamilySamples();
        $samples = $metrics[0]->getSamples();

        $this->assertEquals($expectedNamespace . '_' . $expectedMetric, $samples[0]->getName());
        $this->assertEquals(
            [
                self::BUS_NAME,
                FooBarMessage::class,
                'FooBarMessage',
                '0',
            ],
            $samples[0]->getLabelValues(),
        );
    }
}
