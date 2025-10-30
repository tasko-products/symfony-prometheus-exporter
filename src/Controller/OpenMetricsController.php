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

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Controller;

use Prometheus\RegistryInterface;
use Prometheus\RendererInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class OpenMetricsController extends AbstractController
{
    private const MIME_TYPE = 'text/plain; version=0.0.4';

    public function __construct(
        private readonly RegistryInterface $registry,
        private readonly RendererInterface $renderer,
    ) {
    }

    #[Route(methods: 'GET')]
    public function metrics(): Response
    {
        return new Response(
            content: $this->renderer->render($this->registry->getMetricFamilySamples()),
            headers: [
                'content-type' => self::MIME_TYPE,
            ],
        );
    }
}
