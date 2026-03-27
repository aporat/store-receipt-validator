<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * Optional query parameters for the Get Transaction History endpoint.
 *
 * All properties are nullable / have sensible defaults so callers only set what they need.
 * Build the query-parameter array via {@see toQueryParams()} before passing it to the
 * HTTP layer.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/get-transaction-history
 */
final class TransactionHistoryParams
{
    // -------------------------------------------------------------------------
    // Sort order
    // -------------------------------------------------------------------------

    public const string SORT_ASCENDING  = 'ASCENDING';
    public const string SORT_DESCENDING = 'DESCENDING';

    // -------------------------------------------------------------------------
    // Product types
    // -------------------------------------------------------------------------

    public const string PRODUCT_TYPE_AUTO_RENEWABLE_SUBSCRIPTION = 'AUTO_RENEWABLE_SUBSCRIPTION';
    public const string PRODUCT_TYPE_NON_CONSUMABLE              = 'NON_CONSUMABLE';
    public const string PRODUCT_TYPE_CONSUMABLE                  = 'CONSUMABLE';
    public const string PRODUCT_TYPE_NON_RENEWING_SUBSCRIPTION   = 'NON_RENEWING_SUBSCRIPTION';

    // -------------------------------------------------------------------------
    // In-app ownership types
    // -------------------------------------------------------------------------

    public const string OWNERSHIP_PURCHASED     = 'PURCHASED';
    public const string OWNERSHIP_FAMILY_SHARED = 'FAMILY_SHARED';

    // -------------------------------------------------------------------------
    // Properties
    // -------------------------------------------------------------------------

    /**
     * A token returned in the previous response that you use to get the next page
     * of up to 20 transactions.
     */
    public ?string $revision = null;

    /**
     * The start date of the timespan for the transaction history records you're requesting,
     * in UNIX time milliseconds.
     */
    public ?int $startDate = null;

    /**
     * The end date of the timespan for the transaction history records you're requesting,
     * in UNIX time milliseconds. The endDate must be later than the startDate.
     */
    public ?int $endDate = null;

    /**
     * An array of product identifiers to filter the results. When provided, only
     * transactions for the listed products are returned.
     *
     * @var string[]|null
     */
    public ?array $productId = null;

    /**
     * An array of product types to filter the results. Possible values are the
     * PRODUCT_TYPE_* constants on this class.
     *
     * @var string[]|null
     */
    public ?array $productType = null;

    /**
     * The sort order for the returned transactions.
     *
     * Use the SORT_* constants on this class. Defaults to DESCENDING.
     */
    public string $sort = self::SORT_DESCENDING;

    /**
     * An array of subscription group identifiers to filter the results.
     *
     * @var string[]|null
     */
    public ?array $subscriptionGroupIdentifier = null;

    /**
     * Filters results by in-app ownership type. Use the OWNERSHIP_* constants on this class.
     */
    public ?string $inAppOwnershipType = null;

    /**
     * When true, the response includes only revoked transactions.
     * When false, revoked transactions are excluded.
     * When null (default), both are included.
     */
    public ?bool $revoked = null;

    // -------------------------------------------------------------------------
    // Builder
    // -------------------------------------------------------------------------

    /**
     * Return the parameters as a flat associative array suitable for URL encoding.
     *
     * Array-valued parameters (productId, productType, subscriptionGroupIdentifier)
     * are kept as arrays so that the HTTP layer can repeat the key for each value,
     * matching the format Apple's API expects (e.g. productId=x&productId=y).
     *
     * @return array<string, mixed>
     */
    public function toQueryParams(): array
    {
        $params = ['sort' => $this->sort];

        if ($this->revision !== null) {
            $params['revision'] = $this->revision;
        }

        if ($this->startDate !== null) {
            $params['startDate'] = $this->startDate;
        }

        if ($this->endDate !== null) {
            $params['endDate'] = $this->endDate;
        }

        if (!empty($this->productId)) {
            $params['productId'] = array_values($this->productId);
        }

        if (!empty($this->productType)) {
            $params['productType'] = array_values($this->productType);
        }

        if (!empty($this->subscriptionGroupIdentifier)) {
            $params['subscriptionGroupIdentifier'] = array_values($this->subscriptionGroupIdentifier);
        }

        if ($this->inAppOwnershipType !== null) {
            $params['inAppOwnershipType'] = $this->inAppOwnershipType;
        }

        if ($this->revoked !== null) {
            $params['revoked'] = $this->revoked ? 'true' : 'false';
        }

        return $params;
    }
}
