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

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Configuration;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final readonly class ConfigurationProvider implements
    ConfigurationProviderInterface
{
    private const BUNDLE_NODE_KEY = 'tasko_products_symfony_prometheus_exporter';

    public function __construct(
        private ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function get(?string $path = null): array|bool|string|int|float|\UnitEnum|null
    {
        if (count($this->parameterBag->all()) === 0) {
            return null;
        }

        $currentNode = $this->getBundleNode();

        if ($path === null) {
            return $currentNode;
        }

        $splittedPath = explode('.', $path);

        for ($i = 0; $i < count($splittedPath); $i++) {
            if (!is_array($currentNode)) {
                break;
            }

            $currentNode = $this->tryFindNextNodeForPath($currentNode, $splittedPath[$i]);
        }

        return $currentNode;
    }

    /** @inheritDoc */
    public function maybeGetBool(string $path): ?bool
    {
        $config = $this->get($path);

        if (!is_bool($config)) {
            return null;
        }

        return $config;
    }

    /** @inheritDoc */
    public function maybeGetString(string $path): ?string
    {
        $config = $this->get($path);

        if (!is_string($config) && !$config instanceof \Stringable) {
            return null;
        }

        return (string)$config;
    }

    /** @inheritDoc */
    public function maybeGetArray(string $path): ?array
    {
        $config = $this->get($path);

        if (!is_array($config)) {
            return null;
        }

        return $config;
    }

    private function getBundleNode(): array|bool|string|int|float|\UnitEnum|null
    {
        if (!$this->parameterBag->has(self::BUNDLE_NODE_KEY)) {
            return null;
        }

        return $this->parameterBag->get(self::BUNDLE_NODE_KEY);
    }

    private function tryFindNextNodeForPath(
        array $currentNode,
        string $needle,
    ): array|bool|string|int|float|\UnitEnum|null {
        $haystack = array_keys($currentNode);

        /**
         * @var int|string|false $result
         */
        $result = array_search($needle, $haystack, true);
        if (!is_int($result)) {
            return null;
        }

        /** @var array|bool|string|int|float|\UnitEnum|null */
        return $currentNode[$haystack[$result]];
    }
}
