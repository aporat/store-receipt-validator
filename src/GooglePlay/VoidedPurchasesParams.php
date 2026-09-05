<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonInterface;

/**
 * Query parameters for `purchases.voidedpurchases.list`.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.voidedpurchases/list#query-parameters
 */
final readonly class VoidedPurchasesParams
{
    /**
     * @param CarbonInterface|null    $startTime  Only purchases voided at or after this time (defaults to 30 days ago at Google).
     * @param CarbonInterface|null    $endTime    Only purchases voided before this time (defaults to now at Google).
     * @param int|null                $maxResults Page size (1–1000).
     * @param int|null                $startIndex Zero-based index of the first result (index pagination).
     * @param string|null             $token      Continuation token from a previous response (token pagination).
     * @param VoidedPurchaseType|null $type       Whether to include voided subscriptions.
     * @param bool|null               $includeQuantityBasedPartialRefund Include partial refunds of multi-quantity purchases.
     */
    public function __construct(
        public ?CarbonInterface $startTime = null,
        public ?CarbonInterface $endTime = null,
        public ?int $maxResults = null,
        public ?int $startIndex = null,
        public ?string $token = null,
        public ?VoidedPurchaseType $type = null,
        public ?bool $includeQuantityBasedPartialRefund = null,
    ) {
    }

    /**
     * @return array<string, string|int>
     */
    public function toQueryParams(): array
    {
        $params = [];

        if ($this->startTime !== null) {
            $params['startTime'] = $this->startTime->getTimestampMs();
        }
        if ($this->endTime !== null) {
            $params['endTime'] = $this->endTime->getTimestampMs();
        }
        if ($this->maxResults !== null) {
            $params['maxResults'] = $this->maxResults;
        }
        if ($this->startIndex !== null) {
            $params['startIndex'] = $this->startIndex;
        }
        if ($this->token !== null && $this->token !== '') {
            $params['token'] = $this->token;
        }
        if ($this->type !== null) {
            $params['type'] = $this->type->value;
        }
        if ($this->includeQuantityBasedPartialRefund !== null) {
            $params['includeQuantityBasedPartialRefund'] = $this->includeQuantityBasedPartialRefund ? 'true' : 'false';
        }

        return $params;
    }
}
