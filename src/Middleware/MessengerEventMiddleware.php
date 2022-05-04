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

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Middleware;

use Prometheus\Counter;
use Prometheus\RegistryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use TaskoProducts\SymfonyPrometheusExporterBundle\Configuration\ConfigurationProviderInterface;
use TaskoProducts\SymfonyPrometheusExporterBundle\Trait\EnvelopeMethodesTrait;

class MessengerEventMiddleware implements MiddlewareInterface
{
    use EnvelopeMethodesTrait;

    private RegistryInterface $registry;
    private string $metricName = '';
    private string $helpText = '';
    /** @var string[] */
    private array $labels = [];
    private string $errorHelpText = '';
    /** @var string[] */
    private array $errorLabels = [];

    public function __construct(
        RegistryInterface $registry,
        ConfigurationProviderInterface $config,
    ) {
        $this->registry = $registry;

        $configPrefix = 'middlewares.event_middleware.';

        $this->metricName = $config->maybeGetString($configPrefix . 'metric_name')
            ?? 'message';
        $this->helpText = $config->maybeGetString($configPrefix . 'help_text')
            ?? 'Executed Messages';
        $this->labels = $config->maybeGetArray($configPrefix . 'labels') ?? [
            'message' => 'message',
            'label' => 'label',
        ];
        $this->errorHelpText = $config->maybeGetString($configPrefix . 'error_help_text')
            ?? 'Failed Messages';
        $this->errorLabels = $config->maybeGetArray($configPrefix . 'error_labels') ?? [
            'message' => 'message',
            'label' => 'label',
        ];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $busName = $this->extractBusName($envelope);

        $counter = $this->getCounter(
            $busName,
            $this->metricName,
            $this->helpText,
            $this->eventMiddlewareLabels(),
        );

        $errCounter = $this->getErrorCounter(
            $busName,
            $this->metricName,
            $this->errorHelpText,
            $this->eventMiddlewareErrorLabels(),
        );

        $messageLabels = [
            $this->messageClassPathLabel($envelope),
            $this->messageClassLabel($envelope),
        ];

        try {
            $counter->inc($messageLabels);
            $errCounter->incBy(0, $messageLabels);

            $envelope = $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $exception) {
            $errCounter->inc($messageLabels);

            throw $exception;
        }

        return $envelope;
    }

    /**
     * @return string[]
     */
    private function eventMiddlewareLabels(): array
    {
        return [
            $this->labels['message'],
            $this->labels['label'],
        ];
    }

    /**
     * @return string[]
     */
    private function eventMiddlewareErrorLabels(): array
    {
        return [
            $this->errorLabels['message'],
            $this->errorLabels['label'],
        ];
    }

    /**
     * @param string[] $labels
     */
    private function getCounter(
        string $busName,
        string $name,
        string $helperText,
        array $labels
    ): Counter {
        return $this->registry->getOrRegisterCounter(
            $busName,
            $name,
            $helperText,
            $labels
        );
    }

    /**
     * @param string[] $labels
     */
    private function getErrorCounter(
        string $busName,
        string $name,
        string $helperText,
        array $labels
    ): Counter {
        return $this->getCounter(
            $busName,
            $name . '_error',
            $helperText,
            $labels
        );
    }
}
