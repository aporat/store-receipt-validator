<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\Support\ValueCasting;

/**
 * Commitment details of an installment subscription (`installmentDetails`).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#InstallmentPlan
 */
final readonly class InstallmentPlan
{
    use ValueCasting;

    /** Payments committed to in the first commitment period. */
    public ?int $initialCommittedPaymentsCount;

    /** Payments committed to in each renewal of the commitment, if it renews. */
    public ?int $subsequentCommittedPaymentsCount;

    /** Payments still owed in the current commitment period. */
    public ?int $remainingCommittedPaymentsCount;

    /** True when the subscription is scheduled to cancel at the end of the commitment. */
    public bool $pendingCancellation;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->initialCommittedPaymentsCount    = $this->toInt($data, 'initialCommittedPaymentsCount');
        $this->subsequentCommittedPaymentsCount = $this->toInt($data, 'subsequentCommittedPaymentsCount');
        $this->remainingCommittedPaymentsCount  = $this->toInt($data, 'remainingCommittedPaymentsCount');
        $this->pendingCancellation              = array_key_exists('pendingCancellation', $data) && $data['pendingCancellation'] !== null;
    }

    public function getInitialCommittedPaymentsCount(): ?int
    {
        return $this->initialCommittedPaymentsCount;
    }

    public function getSubsequentCommittedPaymentsCount(): ?int
    {
        return $this->subsequentCommittedPaymentsCount;
    }

    public function getRemainingCommittedPaymentsCount(): ?int
    {
        return $this->remainingCommittedPaymentsCount;
    }

    public function hasPendingCancellation(): bool
    {
        return $this->pendingCancellation;
    }
}
