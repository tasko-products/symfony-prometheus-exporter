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
use Prometheus\RegistryInterface;
use Prometheus\RendererInterface;
use TaskoProducts\SymfonyPrometheusExporterBundle\Controller\OpenMetricsController;

class OpenMetricsControllerTest extends TestCase
{
    public function testGetOpenMetrics(): void
    {
        $registry = $this->getMockBuilder(RegistryInterface::class)->getMock();
        $renderer = $this->getMockBuilder(RendererInterface::class)->getMock();

        $givenMetric = <<<EOD
        # HELP messenger_events_active_workers Active Workers
        # TYPE messenger_events_active_workers gauge
        messenger_events_active_workers{queue_names="default_queue, priority_queue",transport_names="async"} 1
        EOD;

        $renderer->expects($this->once())
                 ->method('render')
                 ->willReturn($givenMetric);

        $controller = new OpenMetricsController($registry, $renderer);

        $metricsResponse = $controller->metrics();

        $this->assertEquals(200, $metricsResponse->getStatusCode());

        /**
         * @var string $content
         */
        $content = $metricsResponse->getContent();

        $this->assertIsString($content);
        $this->assertEquals(
            $givenMetric,
            $content,
        );
        $this->assertEquals(
            'text/plain; version=0.0.4',
            $metricsResponse->headers->get('content-type'),
        );
    }
}
