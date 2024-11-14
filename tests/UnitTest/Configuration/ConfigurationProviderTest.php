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

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Configuration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProvider;

class ConfigurationProviderTest extends TestCase
{
    public function testGetBundleConfigurationArray(): void
    {
        $config = new ConfigurationProvider(
            new ParameterBag(
                [
                    'tasko_products_symfony_prometheus_exporter' => [
                        'testconfig' => [
                            'config 1' => [
                                'value 1' => true,
                                'value 2' => 'foo',
                                'value 3' => [
                                    'foo' => 'bar',
                                    'bar' => 'baz',
                                ],
                            ],
                        ],
                    ],
                ],
            ),
        );

        $actualConfig = $config->get();

        $this->assertIsArray($actualConfig);
        $this->assertEquals(
            [
                'testconfig' => [
                    'config 1' => [
                        'value 1' => true,
                        'value 2' => 'foo',
                        'value 3' => [
                            'foo' => 'bar',
                            'bar' => 'baz',
                        ],
                    ],
                ],
            ],
            $actualConfig,
        );
    }

    public function testGetNullOnEmptyBundleConfiguration(): void
    {
        $config = new ConfigurationProvider(new ParameterBag());

        $actualConfig = $config->get();

        $this->assertNull($actualConfig);
    }

    public function testGetIntegerValueByConfigPath(): void
    {
        $config = new ConfigurationProvider(
            new ParameterBag(
                [
                    'tasko_products_symfony_prometheus_exporter' => [
                        'testconfig' => [
                            'config' => [
                                'value' => 1337,
                            ],
                        ],
                    ],
                ],
            ),
        );

        $actualConfig = $config->get('testconfig.config.value');

        $this->assertNotNull($actualConfig);

        $expectedValue = 1337;
        $this->assertEquals($expectedValue, $actualConfig);
    }

    public function testGetConfigForNotExistingConfigPath(): void
    {
        $config = new ConfigurationProvider(
            new ParameterBag(
                [
                    'tasko_products_symfony_prometheus_exporter' => [
                        'testconfig' => [
                            'config' => [
                                'value' => 1337,
                            ],
                        ],
                    ],
                ],
            ),
        );

        $actualConfig = $config->get('testconfig.missing');

        $this->assertNull($actualConfig);
    }

    public function testGetConfigForNotExistingRootConfig(): void
    {
        $config = new ConfigurationProvider(
            new ParameterBag(
                [
                    'other_config' => [
                        'testconfig' => [
                            'config' => [
                                'value' => 1337,
                            ],
                        ],
                    ],
                ],
            ),
        );

        $actualConfig = $config->get('testconfig.config.value');

        $this->assertNull($actualConfig);
    }

    public function testMaybeGetStringForExistingValueReturnsString(): void
    {
        $config = new ConfigurationProvider(
            new ParameterBag(
                [
                    'tasko_products_symfony_prometheus_exporter' => [
                        'testconfig' => [
                            'config' => [
                                'str_value' => 'foobar',
                            ],
                        ],
                    ],
                ],
            ),
        );

        $actualConfig = $config->maybeGetString('testconfig.config.str_value');

        $this->assertNotNull($actualConfig);

        $expectedValue = 'foobar';
        $this->assertEquals($expectedValue, $actualConfig);
    }

    public function testMaybeGetStringForNotExistingValueReturnsNull(): void
    {
        $config = new ConfigurationProvider(
            new ParameterBag(
                [
                    'tasko_products_symfony_prometheus_exporter' => [
                        'testconfig' => [
                            'config' => [
                                'other_value' => 'foobar',
                            ],
                        ],
                    ],
                ],
            ),
        );

        $actualConfig = $config->maybeGetString('testconfig.config.str_value');

        $this->assertNull($actualConfig);
    }

    public function testMaybeGetBoolForExistingValueReturnsTrue(): void
    {
        $config = new ConfigurationProvider(
            new ParameterBag(
                [
                    'tasko_products_symfony_prometheus_exporter' => [
                        'testconfig' => [
                            'config' => [
                                'bool_value' => true,
                            ],
                        ],
                    ],
                ],
            ),
        );

        $actualConfig = $config->maybeGetBool('testconfig.config.bool_value');

        $this->assertNotNull($actualConfig);
        $this->assertTrue($actualConfig);
    }

    public function testMaybeGetBoolForExistingValueReturnsFalse(): void
    {
        $config = new ConfigurationProvider(
            new ParameterBag(
                [
                    'tasko_products_symfony_prometheus_exporter' => [
                        'testconfig' => [
                            'config' => [
                                'bool_value' => false,
                            ],
                        ],
                    ],
                ],
            ),
        );

        $actualConfig = $config->maybeGetBool('testconfig.config.bool_value');

        $this->assertNotNull($actualConfig);
        $this->assertFalse($actualConfig);
    }

    public function testMaybeGetBoolForNotExistingValueReturnsNull(): void
    {
        $config = new ConfigurationProvider(
            new ParameterBag(
                [
                    'tasko_products_symfony_prometheus_exporter' => [
                        'testconfig' => [
                            'config' => [
                                'other_value' => true,
                            ],
                        ],
                    ],
                ],
            ),
        );

        $actualConfig = $config->maybeGetBool('testconfig.config.bool_value');

        $this->assertNull($actualConfig);
    }

    public function testMaybeGetArrayForExistingValueReturnsArray(): void
    {
        $config = new ConfigurationProvider(
            new ParameterBag(
                [
                    'tasko_products_symfony_prometheus_exporter' => [
                        'testconfig' => [
                            'config' => [
                                'array_value' => [
                                    'foo' => 'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ),
        );

        $actualConfig = $config->maybeGetArray('testconfig.config.array_value');

        $this->assertNotNull($actualConfig);

        $expectedValue = ['foo' => 'bar'];
        $this->assertEquals($expectedValue, $actualConfig);
    }

    public function testMaybeGetArrayForNotExistingValueReturnsNull(): void
    {
        $config = new ConfigurationProvider(
            new ParameterBag(
                [
                    'tasko_products_symfony_prometheus_exporter' => [
                        'testconfig' => [
                            'config' => [
                                'other_value' => [
                                    'foo' => 'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ),
        );

        $actualConfig = $config->maybeGetArray('testconfig.config.array_value');

        $this->assertNull($actualConfig);
    }
}
