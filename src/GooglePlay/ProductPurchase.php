<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractTransaction;
use ReceiptValidator\Environment;

/**
 * A one-time (in-app) product purchase as returned by `purchases.products.get`.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.products#ProductPurchase
 */
final readonly class ProductPurchase extends AbstractTransaction
{
    /** Resource kind, always "androidpublisher#productPurchase". */
    public ?string $kind;

    /** The purchase token this purchase was looked up with. */
    public ?string $purchaseToken;

    /** The order ID associated with the purchase. */
    public ?string $orderId;

    /** When the product was purchased. */
    public ?CarbonImmutable $purchaseTime;

    /** The purchase state, or null if Google sent an unknown value. */
    public ?ProductPurchaseState $purchaseState;

    /** The consumption state, or null if Google sent an unknown value. */
    public ?ConsumptionState $consumptionState;

    /** Whether the purchase has been acknowledged. */
    public AcknowledgementState $acknowledgementState;

    /** How the purchase was obtained. Absent for regular purchases. */
    public ?PurchaseType $purchaseType;

    /** Developer-specified payload supplied at acknowledgement time. */
    public ?string $developerPayload;

    /** The obfuscated account ID set via `setObfuscatedAccountId()` in the billing client. */
    public ?string $obfuscatedExternalAccountId;

    /** The obfuscated profile ID set via `setObfuscatedProfileId()` in the billing client. */
    public ?string $obfuscatedExternalProfileId;

    /** ISO 3166-1 alpha-2 billing country of the user. */
    public ?string $regionCode;

    /** For quantity-based partial refunds, the remaining refundable quantity. */
    public ?int $refundableQuantity;

    /** SANDBOX for licence-tester purchases (`purchaseType` 0), otherwise PRODUCTION. */
    public Environment $environment;

    /**
     * @param array<string, mixed> $data The decoded `ProductPurchase` JSON.
     * @param string|null $purchaseToken The token used for the lookup (Google omits it from newer responses).
     */
    public function __construct(array $data = [], ?string $purchaseToken = null)
    {
        parent::__construct(
            rawData: $data,
            quantity: $this->toInt($data, 'quantity') ?? 1,
            productId: $this->toString($data, 'productId'),
            transactionId: $this->toString($data, 'orderId'),
        );

        $this->kind          = $this->toString($data, 'kind');
        $this->purchaseToken = $purchaseToken ?? $this->toString($data, 'purchaseToken');
        $this->orderId       = $this->toString($data, 'orderId');
        $this->purchaseTime  = $this->toDateFromMs($data, 'purchaseTimeMillis');

        $purchaseState    = $this->toInt($data, 'purchaseState');
        $consumptionState = $this->toInt($data, 'consumptionState');
        $purchaseType     = $this->toInt($data, 'purchaseType');

        $this->purchaseState        = $purchaseState !== null ? ProductPurchaseState::tryFrom($purchaseState) : null;
        $this->consumptionState     = $consumptionState !== null ? ConsumptionState::tryFrom($consumptionState) : null;
        $this->acknowledgementState = AcknowledgementState::fromProductState($this->toInt($data, 'acknowledgementState'));
        $this->purchaseType         = $purchaseType !== null ? PurchaseType::tryFrom($purchaseType) : null;

        $this->developerPayload            = $this->toString($data, 'developerPayload');
        $this->obfuscatedExternalAccountId = $this->toString($data, 'obfuscatedExternalAccountId');
        $this->obfuscatedExternalProfileId = $this->toString($data, 'obfuscatedExternalProfileId');
        $this->regionCode                  = $this->toString($data, 'regionCode');
        $this->refundableQuantity          = $this->toInt($data, 'refundableQuantity');

        $this->environment = $this->purchaseType === PurchaseType::TEST ? Environment::SANDBOX : Environment::PRODUCTION;
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function getPurchaseToken(): ?string
    {
        return $this->purchaseToken;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getPurchaseTime(): ?CarbonInterface
    {
        return $this->purchaseTime;
    }

    public function getPurchaseState(): ?ProductPurchaseState
    {
        return $this->purchaseState;
    }

    public function isPurchased(): bool
    {
        return $this->purchaseState === ProductPurchaseState::PURCHASED;
    }

    public function isPending(): bool
    {
        return $this->purchaseState === ProductPurchaseState::PENDING;
    }

    public function isCanceled(): bool
    {
        return $this->purchaseState === ProductPurchaseState::CANCELED;
    }

    public function getConsumptionState(): ?ConsumptionState
    {
        return $this->consumptionState;
    }

    public function isConsumed(): bool
    {
        return $this->consumptionState === ConsumptionState::CONSUMED;
    }

    public function getAcknowledgementState(): AcknowledgementState
    {
        return $this->acknowledgementState;
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledgementState === AcknowledgementState::ACKNOWLEDGED;
    }

    public function getPurchaseType(): ?PurchaseType
    {
        return $this->purchaseType;
    }

    public function isTestPurchase(): bool
    {
        return $this->purchaseType === PurchaseType::TEST;
    }

    public function getDeveloperPayload(): ?string
    {
        return $this->developerPayload;
    }

    public function getObfuscatedExternalAccountId(): ?string
    {
        return $this->obfuscatedExternalAccountId;
    }

    public function getObfuscatedExternalProfileId(): ?string
    {
        return $this->obfuscatedExternalProfileId;
    }

    public function getRegionCode(): ?string
    {
        return $this->regionCode;
    }

    public function getRefundableQuantity(): ?int
    {
        return $this->refundableQuantity;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }
}
