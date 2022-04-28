<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\DependencyInjection;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProviderInterface;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\ConfigurationAwareTrait;

class OpenMetricsRouteLoader extends Loader
{
    use ConfigurationAwareTrait;

    private bool $isLoaded = false;
    private bool $isEnabled = false;
    private string $openMetricsRouteName = '';
    private string $openMetricsPath = '';
    private array $openMetricsController = [];

    public function __construct(ConfigurationProviderInterface $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
        $this->configurationPrefix = 'routes.open_metrics';

        $this->isEnabled = $this->maybeBoolConfig('enabled') ?? false;
        $this->openMetricsRouteName = $this->maybeStrConfig('name') ?? 'open_metrics';
        $this->openMetricsPath = $this->maybeStrConfig('path') ?? '/metrics';
        $this->openMetricsController = [
            '_controller' => $this->maybeStrConfig('controller') ?? 'TaskoProducts\SymfonyPrometheusExporterBundle\Controller\OpenMetricsController::metrics',
        ];
    }

    /**
     * @throws \RuntimeException The bundles route loader must not added twice
     */
    public function load(mixed $resource, string $type = null): mixed
    {
        if ($this->isLoaded) {
            throw new \RuntimeException('Do not add the bundles route loader twice');
        }

        if (!$this->isEnabled) {
            return null;
        }

        $routes = new RouteCollection();

        $routes->add(
            $this->openMetricsRouteName,
            new Route(
                $this->openMetricsPath,
                $this->openMetricsController,
            ),
        );

        $this->isLoaded = true;

        return $routes;
    }

    public function supports($resource, string $type = null)
    {
        return 'open_metrics' === $type;
    }
}
