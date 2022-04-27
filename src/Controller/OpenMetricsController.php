<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Controller;

use Prometheus\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OpenMetricsController extends AbstractController
{
    public function __construct(private RegistryInterface $registry)
    {
    }

    #[Route('/metrics', name: 'open_metrics', methods: ['GET'])]
    public function metrics(): Response
    {
        return new Response();
    }
}
