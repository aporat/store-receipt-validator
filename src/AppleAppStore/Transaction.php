<?php

namespace ReceiptValidator\AppleAppStore;

use Carbon\Carbon;
use ReceiptValidator\AbstractTransaction;
use ReceiptValidator\Environment;

/**
 * Encapsulates a single transaction from the Apple App Store Server API.
 *
 * This immutable data object provides structured access to the properties of a
 * single signed transaction (JWS), as defined by Apple's API.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/jwstransaction
 */
final class Transaction extends AbstractTransaction
{
    /**
     * The original transaction identifier of a purchase.
     */
    public readonly ?string $originalTransactionId;

    /**
     * The unique identifier of subscription-purchase events across devices.
     */
    public readonly ?string $webOrderLineItemId;

    /**
     * The bundle identifier of an app.
     */
    public readonly ?string $bundleId;

    /**
     * The identifier of the subscription group.
     */
    public readonly ?string $subscriptionGroupIdentifier;

    /**
     * The time the App Store charged the user's account for the transaction.
     */
    public readonly ?Carbon $purchaseDate;

    /**
     * The purchase date of the transaction that corresponds to the original transaction identifier.
     */
    public readonly ?Carbon $originalPurchaseDate;

    /**
     * The expiration date for an auto-renewable subscription.
     */
    public readonly ?Carbon $expiresDate;

    /**
     * The type of the in-app purchase.
     */
    public readonly ?string $type;

    /**
     * A UUID that maps a customer's in-app purchase with its App Store transaction.
     */
    public readonly ?string $appAccountToken;

    /**
     * Describes whether the transaction was purchased or is available via Family Sharing.
     */
    public readonly ?string $inAppOwnershipType;

    /**
     * The time the App Store signed the JWS data.
     */
    public readonly ?Carbon $signedDate;

    /**
     * The reason for a refunded or revoked transaction.
     */
    public readonly ?string $revocationReason;

    /**
     * The time of a transaction's refund or revocation.
     */
    public readonly ?Carbon $revocationDate;

    /**
     * A Boolean value that indicates whether the user upgraded to another subscription.
     */
    public readonly ?bool $isUpgraded;

    /**
     * The type of a promotional offer.
     */
    public readonly ?string $offerType;

    /**
     * The identifier for a promotional offer.
     */
    public readonly ?string $offerIdentifier;

    /**
     * The three-letter ISO 4217 currency code for the App Store storefront.
     */
    public readonly ?string $storefront;

    /**
     * A value that identifies the App Store storefront.
     */
    public readonly ?string $storefrontId;

    /**
     * The reason for the transaction.
     */
    public readonly ?string $transactionReason;

    /**
     * The ISO 4217 currency code for the price.
     */
    public readonly ?string $currency;

    /**
     * The price, in milliunits, of the in-app purchase.
     */
    public readonly ?int $price;

    /**
     * The payment mode for a promotional offer.
     */
    public readonly ?string $offerDiscountType;

    /**
     * The server environment that signed the transaction.
     */
    public readonly Environment $environment;

    /**
     * The unique identifier for the app purchase transaction.
     */
    public readonly ?string $appTransactionId;

    /**
     * The duration of the promotional offer.
     */
    public readonly ?string $offerPeriod;

    /**
     * Constructs the Transaction object and initializes its state from raw JWS claims.
     *
     * @param array<string, mixed> $data The decoded claims from a JWS transaction.
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Initialize parent's readonly properties from Apple-specific fields
        $this->quantity = (int) ($data['quantity'] ?? 0);
        $this->productId = $data['productId'] ?? null;
        $this->transactionId = $data['transactionId'] ?? null;

        // Initialize all other properties from the raw data
        $this->originalTransactionId = $data['originalTransactionId'] ?? null;
        $this->webOrderLineItemId = $data['webOrderLineItemId'] ?? null;
        $this->bundleId = $data['bundleId'] ?? null;
        $this->subscriptionGroupIdentifier = $data['subscriptionGroupIdentifier'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->appAccountToken = $data['appAccountToken'] ?? null;
        $this->inAppOwnershipType = $data['inAppOwnershipType'] ?? null;
        $this->revocationReason = $data['revocationReason'] ?? null;
        $this->isUpgraded = $data['isUpgraded'] ?? null;
        $this->offerType = $data['offerType'] ?? null;
        $this->offerIdentifier = $data['offerIdentifier'] ?? null;
        $this->storefront = $data['storefront'] ?? null;
        $this->storefrontId = $data['storefrontId'] ?? null;
        $this->transactionReason = $data['transactionReason'] ?? null;
        $this->currency = $data['currency'] ?? null;
        $this->price = $data['price'] ?? null;
        $this->offerDiscountType = $data['offerDiscountType'] ?? null;
        $this->appTransactionId = $data['appTransactionId'] ?? null;
        $this->offerPeriod = $data['offerPeriod'] ?? null;
        $this->environment = ($data['environment'] ?? 'Sandbox') === 'Production' ? Environment::PRODUCTION : Environment::SANDBOX;

        // Parse millisecond timestamps into Carbon objects
        $this->purchaseDate = isset($data['purchaseDate']) ? Carbon::createFromTimestampMs($data['purchaseDate']) : null;
        $this->originalPurchaseDate = isset($data['originalPurchaseDate']) ? Carbon::createFromTimestampMs($data['originalPurchaseDate']) : null;
        $this->expiresDate = isset($data['expiresDate']) ? Carbon::createFromTimestampMs($data['expiresDate']) : null;
        $this->signedDate = isset($data['signedDate']) ? Carbon::createFromTimestampMs($data['signedDate']) : null;
        $this->revocationDate = isset($data['revocationDate']) ? Carbon::createFromTimestampMs($data['revocationDate']) : null;
    }

    // --- GETTER METHODS (Preserved for backward compatibility) ---

    /**
     * Returns the original transaction identifier.
     */
    public function getOriginalTransactionId(): ?string
    {
        return $this->originalTransactionId;
    }

    /**
     * Returns the unique identifier for subscription purchase events.
     */
    public function getWebOrderLineItemId(): ?string
    {
        return $this->webOrderLineItemId;
    }

    /**
     * Returns the bundle identifier of the app.
     */
    public function getBundleId(): ?string
    {
        return $this->bundleId;
    }

    /**
     * Returns the subscription group identifier.
     */
    public function getSubscriptionGroupIdentifier(): ?string
    {
        return $this->subscriptionGroupIdentifier;
    }

    /**
     * Returns the time the user was charged.
     */
    public function getPurchaseDate(): ?Carbon
    {
        return $this->purchaseDate;
    }

    /**
     * Returns the original purchase date.
     */
    public function getOriginalPurchaseDate(): ?Carbon
    {
        return $this->originalPurchaseDate;
    }

    /**
     * Returns the subscription expiration date.
     */
    public function getExpiresDate(): ?Carbon
    {
        return $this->expiresDate;
    }

    /**
     * Returns the type of the in-app purchase.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Returns the UUID that maps a purchase to the customer's account.
     */
    public function getAppAccountToken(): ?string
    {
        return $this->appAccountToken;
    }

    /**
     * Returns the ownership type of the in-app purchase.
     */
    public function getInAppOwnershipType(): ?string
    {
        return $this->inAppOwnershipType;
    }

    /**
     * Returns the time the JWS data was signed.
     */
    public function getSignedDate(): ?Carbon
    {
        return $this->signedDate;
    }

    /**
     * Returns the reason for a refund or revocation.
     */
    public function getRevocationReason(): ?string
    {
        return $this->revocationReason;
    }

    /**
     * Returns the time of a refund or revocation.
     */
    public function getRevocationDate(): ?Carbon
    {
        return $this->revocationDate;
    }

    /**
     * Returns true if the user upgraded to another subscription.
     */
    public function isUpgraded(): ?bool
    {
        return $this->isUpgraded;
    }

    /**
     * Returns the type of a promotional offer.
     */
    public function getOfferType(): ?string
    {
        return $this->offerType;
    }

    /**
     * Returns the identifier for a promotional offer.
     */
    public function getOfferIdentifier(): ?string
    {
        return $this->offerIdentifier;
    }

    /**
     * Returns the App Store storefront country code.
     */
    public function getStorefront(): ?string
    {
        return $this->storefront;
    }

    /**
     * Returns the App Store storefront identifier.
     */
    public function getStorefrontId(): ?string
    {
        return $this->storefrontId;
    }

    /**
     * Returns the reason for the transaction.
     */
    public function getTransactionReason(): ?string
    {
        return $this->transactionReason;
    }

    /**
     * Returns the three-letter currency code.
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * Returns the price in milliunits.
     */
    public function getPrice(): ?int
    {
        return $this->price;
    }

    /**
     * Returns the payment mode for a promotional offer.
     */
    public function getOfferDiscountType(): ?string
    {
        return $this->offerDiscountType;
    }

    /**
     * Returns the app transaction identifier.
     */
    public function getAppTransactionId(): ?string
    {
        return $this->appTransactionId;
    }

    /**
     * Returns the duration of the promotional offer.
     */
    public function getOfferPeriod(): ?string
    {
        return $this->offerPeriod;
    }

    /**
     * Returns the server environment that signed the transaction.
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }
}
