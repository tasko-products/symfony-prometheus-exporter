<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         http://www.opensource.org/licenses/mit-license.html MIT License
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
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

        $counter = $this->registry->getCounter(self::BUS_NAME, self::METRIC_NAME);

        $this->assertEquals(self::BUS_NAME . '_' . self::METRIC_NAME, $counter->getName());
        $this->assertEquals(['message', 'label', 'retry'], $counter->getLabelNames());

        $expectedMetricCounter = 1;
        $expectedLabelValues = [FooBarMessage::class, 'FooBarMessage', '1'];
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

        $counter = $this->registry->getCounter(self::BUS_NAME, self::METRIC_NAME);

        $this->assertEquals(self::BUS_NAME . '_' . self::METRIC_NAME, $counter->getName());
        $this->assertEquals(['message', 'label', 'retry'], $counter->getLabelNames());

        $expectedMetricCounter = 0;
        $expectedLabelValues = [FooBarMessage::class, 'FooBarMessage', '0'];
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
                                        'metric_name' => 'test_metric',
                                        'help_text' => 'test help text',
                                        'labels' => [
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

        $counter = $this->registry->getCounter(self::BUS_NAME, 'test_metric');

        $this->assertEquals(self::BUS_NAME . '_test_metric', $counter->getName());
        $this->assertEquals([
            'test_message',
            'test_label',
            'test_retry',
        ], $counter->getLabelNames());
    }
}
