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

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Controller;

use Prometheus\RegistryInterface;
use Prometheus\RendererInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OpenMetricsController extends AbstractController
{
    private const MIME_TYPE = 'text/plain; version=0.0.4';

    public function __construct(
        private RegistryInterface $registry,
        private RendererInterface $renderer,
    ) {
    }

    #[Route(methods: 'GET')]
    public function metrics(): Response
    {
        return new Response(
            content: $this->renderer->render($this->registry->getMetricFamilySamples()),
            headers: [
                'content-type' => self::MIME_TYPE,
            ]
        );
    }
}
