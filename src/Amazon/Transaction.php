<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractTransaction;

/**
 * A purchase as reported by the Amazon Receipt Verification Service (RVS).
 *
 * RVS returns a single object per receipt; this class exposes every documented field
 * of that object. Dates are epoch milliseconds in the API and are exposed as UTC
 * Carbon instances.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#response-syntax
 */
final readonly class Transaction extends AbstractTransaction
{
    /** The type of product purchased. Null when the response carries an unknown type. */
    public ?ProductType $productType;

    /** Reserved by Amazon for future use; currently always null. */
    public ?string $parentProductId;

    /** The date of the initial purchase. For subscriptions, renewals do not change it. */
    public ?CarbonImmutable $purchaseDate;

    /**
     * The date the purchase was cancelled or the subscription expired. Null while the
     * receipt is valid; when set, the customer lost access on this date.
     */
    public ?CarbonImmutable $cancellationDate;

    /** Why the purchase was cancelled. Null when it was not. */
    public ?CancelReason $cancelReason;

    /** The date an auto-renewing subscription next renews. */
    public ?CarbonImmutable $renewalDate;

    /** The end of the current grace period. Null when the subscription is not in one. */
    public ?CarbonImmutable $gracePeriodEndDate;

    /** The end of the free trial. Null when the subscription is not in a trial. */
    public ?CarbonImmutable $freeTrialEndDate;

    /**
     * For tiered subscriptions with a deferred plan change, the date the new plan takes
     * effect. Null once the change has happened.
     */
    public ?CarbonImmutable $deferredDate;

    /** For tiered subscriptions with a deferred plan change, the SKU of the upcoming plan. */
    public ?string $deferredSku;

    /** Whether the subscription will renew automatically. */
    public bool $autoRenewing;

    /** The subscription term, such as "1 Week" or "2 Months". */
    public ?string $term;

    /**
     * The SKU of the subscription term. The RVS sandbox appends "_term" to this value;
     * production does not.
     */
    public ?string $termSku;

    /** When the app acknowledged fulfillment of a subscription. Null until it does. */
    public ?CarbonImmutable $fulfillmentDate;

    /** The fulfillment status the app reported for a subscription. Null until it does. */
    public ?FulfillmentResult $fulfillmentResult;

    /**
     * Promotional prices and retention offers applied to a subscription purchase.
     *
     * @var array<Promotion>
     */
    public array $promotions;

    /**
     * For an add-on subscription, the receipt IDs of the base subscriptions it belongs to.
     * Null for any other purchase.
     *
     * @var array<string>|null
     */
    public ?array $baseReceipts;

    /**
     * Extra purchase metadata. Amazon currently sets {"QuickSubscribe": "true"} for
     * subscriptions started through Quick Subscribe and null otherwise.
     *
     * @var array<string, string>|null
     */
    public ?array $purchaseMetadataMap;

    /** The customer's country of residence as recorded by Amazon (ISO 3166-1 alpha-2). */
    public ?string $countryCode;

    /** Whether the product is a Live App Testing (beta) product. */
    public bool $betaProduct;

    /** Whether the purchase was made as part of Amazon's publishing and testing process. */
    public bool $testTransaction;

    /**
     * @param array<string, mixed> $data The decoded RVS response.
     */
    public function __construct(array $data = [])
    {
        $fields = self::normaliseKeys($data);

        parent::__construct(
            rawData: $data,
            quantity: $this->toInt($fields, 'quantity') ?? 1,
            productId: $this->toString($fields, 'productId'),
            transactionId: $this->toString($fields, 'receiptId'),
        );

        $this->productType     = ProductType::tryFrom($this->toString($fields, 'productType') ?? '');
        $this->parentProductId = $this->toString($fields, 'parentProductId');

        $this->purchaseDate       = $this->toDateFromMs($fields, 'purchaseDate');
        $this->cancellationDate   = $this->toDateFromMs($fields, 'cancelDate');
        $this->cancelReason       = CancelReason::tryFrom($this->toInt($fields, 'cancelReason') ?? -1);
        $this->renewalDate        = $this->toDateFromMs($fields, 'renewalDate');
        $this->gracePeriodEndDate = $this->toDateFromMs($fields, 'gracePeriodEndDate');
        $this->freeTrialEndDate   = $this->toDateFromMs($fields, 'freeTrialEndDate');
        $this->deferredDate       = $this->toDateFromMs($fields, 'deferredDate');
        $this->deferredSku        = $this->toString($fields, 'deferredSku');

        $this->autoRenewing = $this->toBool($fields, 'autoRenewing');
        $this->term         = $this->toString($fields, 'term');
        $this->termSku      = $this->toString($fields, 'termSku');

        $this->fulfillmentDate   = $this->toDateFromMs($fields, 'fulfillmentDate');
        $this->fulfillmentResult = FulfillmentResult::tryFrom($this->toString($fields, 'fulfillmentResult') ?? '');

        $promotions = [];
        foreach (is_array($fields['promotions'] ?? null) ? $fields['promotions'] : [] as $promotion) {
            if (is_array($promotion)) {
                $promotions[] = new Promotion($promotion);
            }
        }
        $this->promotions = $promotions;

        $this->baseReceipts = is_array($fields['baseReceipts'] ?? null)
            ? array_values(array_map(strval(...), array_filter($fields['baseReceipts'], is_scalar(...))))
            : null;

        $this->purchaseMetadataMap = is_array($fields['purchaseMetadataMap'] ?? null)
            ? array_map(strval(...), array_filter($fields['purchaseMetadataMap'], is_scalar(...)))
            : null;

        $this->countryCode     = $this->toString($fields, 'countryCode');
        $this->betaProduct     = $this->toBool($fields, 'betaProduct');
        $this->testTransaction = $this->toBool($fields, 'testTransaction');
    }

    /**
     * Amazon documents every field in camelCase. Older documentation and fixtures spelled
     * two of them with a leading capital, so those spellings are accepted as a fallback.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function normaliseKeys(array $data): array
    {
        foreach (['AutoRenewing' => 'autoRenewing', 'GracePeriodEndDate' => 'gracePeriodEndDate'] as $legacy => $key) {
            if (array_key_exists($legacy, $data) && !array_key_exists($key, $data)) {
                $data[$key] = $data[$legacy];
            }
        }

        return $data;
    }

    public function getProductType(): ?ProductType
    {
        return $this->productType;
    }

    public function isSubscription(): bool
    {
        return $this->productType?->isSubscription() ?? false;
    }

    public function isConsumable(): bool
    {
        return $this->productType?->isConsumable() ?? false;
    }

    public function isEntitlement(): bool
    {
        return $this->productType?->isEntitlement() ?? false;
    }

    public function getParentProductId(): ?string
    {
        return $this->parentProductId;
    }

    public function getPurchaseDate(): ?CarbonInterface
    {
        return $this->purchaseDate;
    }

    public function getCancellationDate(): ?CarbonInterface
    {
        return $this->cancellationDate;
    }

    public function getCancelReason(): ?CancelReason
    {
        return $this->cancelReason;
    }

    /** Whether the purchase was cancelled or the subscription expired. */
    public function isCanceled(): bool
    {
        return $this->cancellationDate !== null;
    }

    public function getRenewalDate(): ?CarbonInterface
    {
        return $this->renewalDate;
    }

    public function getGracePeriodEndDate(): ?CarbonInterface
    {
        return $this->gracePeriodEndDate;
    }

    public function getFreeTrialEndDate(): ?CarbonInterface
    {
        return $this->freeTrialEndDate;
    }

    public function getDeferredDate(): ?CarbonInterface
    {
        return $this->deferredDate;
    }

    public function getDeferredSku(): ?string
    {
        return $this->deferredSku;
    }

    public function isAutoRenewing(): bool
    {
        return $this->autoRenewing;
    }

    public function getTerm(): ?string
    {
        return $this->term;
    }

    public function getTermSku(): ?string
    {
        return $this->termSku;
    }

    public function getFulfillmentDate(): ?CarbonInterface
    {
        return $this->fulfillmentDate;
    }

    public function getFulfillmentResult(): ?FulfillmentResult
    {
        return $this->fulfillmentResult;
    }

    /** @return array<Promotion> */
    public function getPromotions(): array
    {
        return $this->promotions;
    }

    /** The promotion the customer is currently benefiting from, if any. */
    public function getActivePromotion(): ?Promotion
    {
        foreach ($this->promotions as $promotion) {
            if ($promotion->isActive()) {
                return $promotion;
            }
        }

        return null;
    }

    /** @return array<string>|null */
    public function getBaseReceipts(): ?array
    {
        return $this->baseReceipts;
    }

    /** Whether this receipt is for an add-on subscription attached to a base subscription. */
    public function isAddOnSubscription(): bool
    {
        return $this->baseReceipts !== null && $this->baseReceipts !== [];
    }

    /** @return array<string, string>|null */
    public function getPurchaseMetadataMap(): ?array
    {
        return $this->purchaseMetadataMap;
    }

    /** Whether the subscription was started through Amazon's Quick Subscribe. */
    public function isQuickSubscribe(): bool
    {
        return filter_var($this->purchaseMetadataMap['QuickSubscribe'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function isBetaProduct(): bool
    {
        return $this->betaProduct;
    }

    public function isTestTransaction(): bool
    {
        return $this->testTransaction;
    }

    /** Whether the subscription is in its free trial at the given time (default: now). */
    public function isInFreeTrial(?CarbonInterface $now = null): bool
    {
        return $this->freeTrialEndDate !== null && $this->freeTrialEndDate->greaterThan($now ?? CarbonImmutable::now());
    }

    /** Whether the subscription is in a billing grace period at the given time (default: now). */
    public function isInGracePeriod(?CarbonInterface $now = null): bool
    {
        return $this->gracePeriodEndDate !== null
            && $this->gracePeriodEndDate->greaterThan($now ?? CarbonImmutable::now());
    }

    /**
     * When access granted by a subscription ends unless it renews.
     *
     * The cancellation date when the subscription was cancelled, otherwise the end of the
     * grace period when in one, otherwise the next renewal date. Null for consumables and
     * entitlements, which do not expire.
     */
    public function getExpiresAt(): ?CarbonInterface
    {
        if (!$this->isSubscription()) {
            return null;
        }

        return $this->cancellationDate ?? $this->gracePeriodEndDate ?? $this->renewalDate;
    }

    /**
     * Whether the customer should currently have access to what this receipt granted.
     *
     * A cancelled or expired receipt never grants access. A subscription grants access until
     * the end of its grace period or its next renewal date; Amazon keeps the receipt valid
     * during a grace period while it retries payment. Consumables and entitlements grant
     * access as long as the receipt has not been cancelled.
     */
    public function isEntitled(?CarbonInterface $now = null): bool
    {
        if ($this->isCanceled()) {
            return false;
        }

        if (!$this->isSubscription()) {
            return true;
        }

        $expiresAt = $this->gracePeriodEndDate ?? $this->renewalDate;

        return $expiresAt === null || $expiresAt->greaterThan($now ?? CarbonImmutable::now());
    }
}
