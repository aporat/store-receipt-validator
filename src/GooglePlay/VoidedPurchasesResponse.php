<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\AbstractResponse;
use ReceiptValidator\Environment;

/**
 * One page of results from `purchases.voidedpurchases.list`.
 *
 * When {@see hasMore()} is true, pass {@see getNextPageToken()} as
 * {@see VoidedPurchasesParams::$token} to fetch the next page.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.voidedpurchases/list#response-body
 *
 * @extends AbstractResponse<VoidedPurchase>
 */
final class VoidedPurchasesResponse extends AbstractResponse
{
    /** Token for the next page, if any. */
    public readonly ?string $nextPageToken;

    /** Token for the previous page, if any. */
    public readonly ?string $previousPageToken;

    /** Total number of results across all pages, when Google reports it. */
    public readonly ?int $totalResults;

    /** Results per page, when Google reports it. */
    public readonly ?int $resultPerPage;

    /** Index of the first result on this page, when Google reports it. */
    public readonly ?int $startIndex;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [], Environment $environment = Environment::PRODUCTION)
    {
        parent::__construct($data, $environment);

        $pagination = is_array($data['tokenPagination'] ?? null) ? $data['tokenPagination'] : [];
        $pageInfo   = is_array($data['pageInfo'] ?? null) ? $data['pageInfo'] : [];

        $this->nextPageToken     = $this->toString($pagination, 'nextPageToken');
        $this->previousPageToken = $this->toString($pagination, 'previousPageToken');
        $this->totalResults      = $this->toInt($pageInfo, 'totalResults');
        $this->resultPerPage     = $this->toInt($pageInfo, 'resultPerPage');
        $this->startIndex        = $this->toInt($pageInfo, 'startIndex');

        $items = [];
        foreach (is_array($data['voidedPurchases'] ?? null) ? $data['voidedPurchases'] : [] as $item) {
            if (is_array($item)) {
                $items[] = new VoidedPurchase($item);
            }
        }
        $this->setTransactions($items);
    }

    /**
     * The voided purchases on this page. Alias of {@see getTransactions()}.
     *
     * @return array<VoidedPurchase>
     */
    public function getVoidedPurchases(): array
    {
        return $this->getTransactions();
    }

    public function getNextPageToken(): ?string
    {
        return $this->nextPageToken;
    }

    public function getPreviousPageToken(): ?string
    {
        return $this->previousPageToken;
    }

    public function hasMore(): bool
    {
        return $this->nextPageToken !== null;
    }

    public function getTotalResults(): ?int
    {
        return $this->totalResults;
    }

    public function getResultPerPage(): ?int
    {
        return $this->resultPerPage;
    }

    public function getStartIndex(): ?int
    {
        return $this->startIndex;
    }
}
