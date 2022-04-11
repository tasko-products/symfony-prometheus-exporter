<?php declare(strict_types=1);
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2020
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

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
