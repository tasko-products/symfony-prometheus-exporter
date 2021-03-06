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

$finder = new PhpCsFixer\Finder();
$finder->in(__DIR__ . '/src/')
       ->in(__DIR__ . '/tests/');

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true,
        'single_quote' => true,
        'single_trait_insert_per_statement' => true,
        'fully_qualified_strict_types' => true,
        'binary_operator_spaces' => true,
        'no_unused_imports' => true,
        'method_argument_space' => [
            'keep_multiple_spaces_after_comma' => false
        ]
    ])->setFinder($finder);
