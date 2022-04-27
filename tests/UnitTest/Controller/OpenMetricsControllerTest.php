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
use Prometheus\RendererInterface;
use TaskoProducts\SymfonyPrometheusExporterBundle\Controller\OpenMetricsController;
use TaskoProducts\SymfonyPrometheusExporterBundle\Tests\Factory\PrometheusCollectorRegistryFactory;

class OpenMetricsControllerTest extends TestCase
{
    public function testGetOpenMetricsForActiveWorkers(): void
    {
        $registry = PrometheusCollectorRegistryFactory::create();
        $renderer = $this->getMockBuilder(RendererInterface::class)->getMock();

        $givenMetric = <<<EOD
        # HELP test_namespace_test_metric test help text
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
    }
}
