<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The developer's answer to a chargeback review (`orders.reviewrefund`).
 *
 * Start from the pending refund token on a {@see PendingRefundReviewNotification}.
 * Everything beyond the token and preference is optional evidence Google weighs when
 * deciding the chargeback.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders/reviewrefund#request-body
 */
final readonly class ReviewRefundRequest
{
    /**
     * @param string                              $pendingRefundToken    From the pending refund review notification.
     * @param RefundPreference                    $refundPreference      Whether you recommend approving or declining.
     * @param bool|null                           $sampleContentProvided Whether the user received a sample before buying.
     * @param int|null                            $consumptionPercentageMilliunits How much of the content was consumed, 0 to 100000.
     * @param array<int, array<string, mixed>>    $consumptionUsageEvents Raw `ConsumptionUsageEvent` objects (obfuscatedAccountId,
     *                                                                    consumptionTime, ipAddress, consumptionItemDescription, location).
     */
    public function __construct(
        public string $pendingRefundToken,
        public RefundPreference $refundPreference = RefundPreference::NEUTRAL,
        public ?bool $sampleContentProvided = null,
        public ?int $consumptionPercentageMilliunits = null,
        public array $consumptionUsageEvents = [],
    ) {
    }

    /**
     * Convenience for a plain percentage (0 to 100) instead of milliunits.
     */
    public function withConsumptionPercent(float $percent): self
    {
        return new self(
            $this->pendingRefundToken,
            $this->refundPreference,
            $this->sampleContentProvided,
            (int) round(max(0.0, min(100.0, $percent)) * 1000),
            $this->consumptionUsageEvents,
        );
    }

    /**
     * @return array<string, mixed> The JSON request body.
     */
    public function toArray(): array
    {
        $body = [
            'pendingRefundToken' => $this->pendingRefundToken,
            'refundPreference'   => $this->refundPreference->value,
        ];

        if ($this->sampleContentProvided !== null) {
            $body['sampleContentProvided'] = $this->sampleContentProvided;
        }
        if ($this->consumptionPercentageMilliunits !== null) {
            $body['consumptionPercentageMilliunits'] = $this->consumptionPercentageMilliunits;
        }
        if ($this->consumptionUsageEvents !== []) {
            $body['consumptionUsageEvents'] = array_values($this->consumptionUsageEvents);
        }

        return $body;
    }
}
