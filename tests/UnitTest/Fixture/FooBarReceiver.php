<?php
/**
 * @link            https://tasko.de/ tasko Products GmbH
 * @copyright       (c) tasko Products GmbH 2022
 * @license         tbd
 * @author          Lukas Rotermund <lukas.rotermund@tasko.de>
 * @version         1.0.0
 */

declare(strict_types=1);

namespace TaskoProducts\SymfonyPrometheusExporterBundle\Tests\UnitTest\Fixture;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Receiver\QueueReceiverInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;

class FooBarReceiver implements ReceiverInterface, QueueReceiverInterface
{
    /**
     * @var Envelope[][] $deliveriesOfEnvelopes
     */
    private array $deliveriesOfEnvelopes;
    /**
     * @var Envelope[] $acknowledgedEnvelopes
     */
    private array $acknowledgedEnvelopes;#
    private int $acknowledgeCount = 0;
    private int $rejectCount = 0;

    /**
     * @param Envelope[][] $deliveriesOfEnvelopes
     */
    public function __construct(array $deliveriesOfEnvelopes)
    {
        $this->deliveriesOfEnvelopes = $deliveriesOfEnvelopes;
    }

    public function get(): iterable
    {
        $val = array_shift($this->deliveriesOfEnvelopes);

        return $val ?? [];
    }

    public function ack(Envelope $envelope): void
    {
        $this->acknowledgeCount++;
        $this->acknowledgedEnvelopes[] = $envelope;
    }

    public function reject(Envelope $envelope): void
    {
        $this->rejectCount++;
    }

    public function getAcknowledgeCount(): int
    {
        return $this->acknowledgeCount;
    }

    public function getRejectCount(): int
    {
        return $this->rejectCount;
    }

    /**
     * @return Envelope[]
     */
    public function getAcknowledgedEnvelopes(): array
    {
        return $this->acknowledgedEnvelopes;
    }


    public function getFromQueues(array $queueNames): iterable
    {
        return $this->get();
    }
}
