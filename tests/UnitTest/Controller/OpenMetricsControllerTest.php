<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Controller;

use PHPUnit\Framework\TestCase;
use Prometheus\RenderTextFormat;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Messenger\WorkerMetadata;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProvider;
use TaskoProducts\SymfonyPrometheusExporterBundle\Controller\OpenMetricsController;
use TaskoProducts\SymfonyPrometheusExporterBundle\EventSubscriber\ActiveWorkersMetricEventSubscriber;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;

class OpenMetricsControllerTest extends TestCase
{
    public function testGetOpenMetricsForActiveWorkers(): void
    {
        $registry = PrometheusCollectorRegistryFactory::create();
        $worker = $this->getMockBuilder(Worker::class)
                       ->disableOriginalConstructor()
                       ->getMock();

        $worker->expects($this->any())
               ->method('getMetadata')
               ->willReturn(new WorkerMetadata([
                   'transportNames' => ['transport', 'prio_transport'],
                   'queueNames' => [
                    'foobar_worker_queue',
                    'priority_foobar_worker_queue',
                   ]
               ]));

        $subscriber = new ActiveWorkersMetricEventSubscriber(
            $registry,
            new ConfigurationProvider(
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
            ),
        );

        $subscriber->onWorkerStarted(new WorkerStartedEvent($worker));

        $controller = new OpenMetricsController($registry, new RenderTextFormat());

        $metricsResponse = $controller->metrics();

        $this->assertEquals(200, $metricsResponse->getStatusCode());

        /**
         * @var string $content
         */
        $content = $metricsResponse->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString(
            '# HELP test_namespace_test_metric test help text',
            $content,
        );
    }
}
