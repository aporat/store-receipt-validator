<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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
final readonly class Transaction extends AbstractTransaction
{
    /** The original transaction identifier of a purchase. */
    public ?string $originalTransactionId;

    /** The unique identifier of subscription-purchase events across devices. */
    public ?string $webOrderLineItemId;

    /** The bundle identifier of an app. */
    public ?string $bundleId;

    /** The identifier of the subscription group. */
    public ?string $subscriptionGroupIdentifier;

    /** The time the App Store charged the user's account for the transaction. */
    public ?CarbonImmutable $purchaseDate;

    /** The purchase date of the transaction that corresponds to the original transaction identifier. */
    public ?CarbonImmutable $originalPurchaseDate;

    /** The expiration date for an auto-renewable subscription. */
    public ?CarbonImmutable $expiresDate;

    /** The type of the in-app purchase. */
    public ?string $type;

    /** A UUID that maps a customer's in-app purchase with its App Store transaction. */
    public ?string $appAccountToken;

    /** Describes whether the transaction was purchased or is available via Family Sharing. */
    public ?string $inAppOwnershipType;

    /** The time the App Store signed the JWS data. */
    public ?CarbonImmutable $signedDate;

    /** The reason for a refunded or revoked transaction. */
    public ?string $revocationReason;

    /** The time of a transaction's refund or revocation. */
    public ?CarbonImmutable $revocationDate;

    /** A Boolean value that indicates whether the user upgraded to another subscription. */
    public bool $isUpgraded;

    /** The type of promotional offer. */
    public ?string $offerType;

    /** The identifier for a promotional offer. */
    public ?string $offerIdentifier;

    /** The three-letter ISO 4217 currency code for the App Store storefront. */
    public ?string $storefront;

    /** A value that identifies the App Store storefront. */
    public ?string $storefrontId;

    /** The reason for the transaction. */
    public ?string $transactionReason;

    /** The ISO 4217 currency code for the price. */
    public ?string $currency;

    /** The price, in milliunits, of the in-app purchase. */
    public ?int $price;

    /** The payment mode for a promotional offer. */
    public ?string $offerDiscountType;

    /** The server environment that signed the transaction. */
    public Environment $environment;

    /** The unique identifier for the app purchase transaction. */
    public ?string $appTransactionId;

    /** The duration of the promotional offer. */
    public ?string $offerPeriod;

    /**
     * @param array<string, mixed> $data The decoded claims from a JWS transaction.
     */
    public function __construct(array $data = [])
    {
        parent::__construct(
            rawData: $data,
            quantity: (int) ($data['quantity'] ?? 1),
            productId: $this->toString($data, 'productId'),
            transactionId: $this->toString($data, 'transactionId'),
        );

        $this->originalTransactionId       = $this->toString($data, 'originalTransactionId');
        $this->webOrderLineItemId          = $this->toString($data, 'webOrderLineItemId');
        $this->bundleId                    = $this->toString($data, 'bundleId');
        $this->subscriptionGroupIdentifier = $this->toString($data, 'subscriptionGroupIdentifier');
        $this->type                        = $this->toString($data, 'type');
        $this->appAccountToken             = $this->toString($data, 'appAccountToken');
        $this->inAppOwnershipType          = $this->toString($data, 'inAppOwnershipType');
        $this->revocationReason            = $this->toString($data, 'revocationReason');
        $this->offerType                   = $this->toString($data, 'offerType');
        $this->offerIdentifier             = $this->toString($data, 'offerIdentifier');
        $this->storefront                  = $this->toString($data, 'storefront');
        $this->storefrontId                = $this->toString($data, 'storefrontId');
        $this->transactionReason           = $this->toString($data, 'transactionReason');
        $this->currency                    = $this->toString($data, 'currency');
        $this->price                       = $this->toInt($data, 'price');
        $this->offerDiscountType           = $this->toString($data, 'offerDiscountType');
        $this->appTransactionId            = $this->toString($data, 'appTransactionId');
        $this->offerPeriod                 = $this->toString($data, 'offerPeriod');
        $this->isUpgraded = $this->toBool($data, 'isUpgraded');
        $this->environment                 = $this->toEnvironment($data, 'environment');
        $this->purchaseDate         = $this->toDateFromMs($data, 'purchaseDate');
        $this->originalPurchaseDate = $this->toDateFromMs($data, 'originalPurchaseDate');
        $this->expiresDate          = $this->toDateFromMs($data, 'expiresDate');
        $this->signedDate           = $this->toDateFromMs($data, 'signedDate');
        $this->revocationDate       = $this->toDateFromMs($data, 'revocationDate');
    }

    public function getOriginalTransactionId(): ?string
    {
        return $this->originalTransactionId;
    }
    public function getWebOrderLineItemId(): ?string
    {
        return $this->webOrderLineItemId;
    }
    public function getBundleId(): ?string
    {
        return $this->bundleId;
    }
    public function getSubscriptionGroupIdentifier(): ?string
    {
        return $this->subscriptionGroupIdentifier;
    }
    public function getPurchaseDate(): ?CarbonInterface
    {
        return $this->purchaseDate;
    }
    public function getOriginalPurchaseDate(): ?CarbonInterface
    {
        return $this->originalPurchaseDate;
    }
    public function getExpiresDate(): ?CarbonInterface
    {
        return $this->expiresDate;
    }
    public function getType(): ?string
    {
        return $this->type;
    }
    public function getAppAccountToken(): ?string
    {
        return $this->appAccountToken;
    }
    public function getInAppOwnershipType(): ?string
    {
        return $this->inAppOwnershipType;
    }
    public function getSignedDate(): ?CarbonInterface
    {
        return $this->signedDate;
    }
    public function getRevocationReason(): ?string
    {
        return $this->revocationReason;
    }
    public function getRevocationDate(): ?CarbonInterface
    {
        return $this->revocationDate;
    }
    public function isUpgraded(): bool
    {
        return $this->isUpgraded;
    }
    public function getOfferType(): ?string
    {
        return $this->offerType;
    }
    public function getOfferIdentifier(): ?string
    {
        return $this->offerIdentifier;
    }
    public function getStorefront(): ?string
    {
        return $this->storefront;
    }
    public function getStorefrontId(): ?string
    {
        return $this->storefrontId;
    }
    public function getTransactionReason(): ?string
    {
        return $this->transactionReason;
    }
    public function getCurrency(): ?string
    {
        return $this->currency;
    }
    public function getPrice(): ?int
    {
        return $this->price;
    }
    public function getOfferDiscountType(): ?string
    {
        return $this->offerDiscountType;
    }
    public function getAppTransactionId(): ?string
    {
        return $this->appTransactionId;
    }
    public function getOfferPeriod(): ?string
    {
        return $this->offerPeriod;
    }
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }
}
