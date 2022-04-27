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
use TaskoProducts\SymfonyPrometheusExporterBundle\Controller\OpenMetricsController;

class OpenMetricsControllerTest extends TestCase
{
    public function testGetOpenMetricsForActiveWorkers(): void
    {
        $controller = new OpenMetricsController();

        $metricsResponse = $controller->metrics();

        $this->assertEquals(200, $metricsResponse->getStatusCode());
    }
}
