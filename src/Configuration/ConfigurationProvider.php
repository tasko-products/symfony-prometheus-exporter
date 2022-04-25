<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Configuration;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigurationProvider implements ConfigurationProviderInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private string $configRoot = 'prometheus_metrics',
    ) {
    }

    /**
     * @inheritDoc
     */
    public function config(?string $path = null): array|bool|string|int|float|\UnitEnum|null
    {
        if (count($this->parameterBag->all()) === 0) {
            return null;
        }

        if ($path === null) {
            return $this->parameterBag->all();
        }

        $splittedPath = explode('.', $path);
        $currentNode = $this->parameterBag->get($this->configRoot . '.' . $splittedPath[0]);

        for ($i = 1; $i < count($splittedPath); $i++) {
            if (!is_array($currentNode)) {
                break;
            }

            $currentNode = $this->tryFindNextNodeForPath($currentNode, $splittedPath[$i]);
        }

        return $currentNode;
    }

    private function tryFindNextNodeForPath(
        array $currentNode,
        string $needle,
    ): array|bool|string|int|float|\UnitEnum|null {
        $haystack = array_keys($currentNode);

        /**
         * @var int|bool $result
         */
        $result = array_search($needle, $haystack);

        if ($result === false) {
            return null;
        }

        return $currentNode[$haystack[$result]];
    }
}
