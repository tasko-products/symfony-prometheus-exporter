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

namespace TaskoProducts\SymfonyPrometheusExporterBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TaskoProductsSymfonyPrometheusExporterBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
