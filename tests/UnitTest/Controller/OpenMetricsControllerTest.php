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

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Controller;

use PHPUnit\Framework\TestCase;
use Prometheus\RegistryInterface;
use Prometheus\RendererInterface;
use Symfony\Component\HttpFoundation\Response;
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
                 ->willReturn($givenMetric)
        ;

        $controller = new OpenMetricsController($registry, $renderer);

        $metricsResponse = $controller->metrics();

        $this->assertEquals(Response::HTTP_OK, $metricsResponse->getStatusCode());

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
