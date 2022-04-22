<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
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
            'testbundle',
            new ParameterBag(
                [
                    'testbundle.testconfig' => [
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
            ),
        );

        $actualConfig = $config->config();

        $this->assertIsArray($actualConfig);
        $this->assertEquals(
            [
                'testbundle.testconfig' => [
                    'config 1' => [
                        'value 1' => true,
                        'value 2' => 'foo',
                        'value 3' => [
                            'foo' => 'bar',
                            'bar' => 'baz',
                        ],
                    ],
                ]
            ],
            $actualConfig,
        );
    }
}
